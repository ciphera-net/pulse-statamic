#!/usr/bin/env bash
# Install a real Statamic site in Docker, install this addon into it from the
# repo, and read the tag out of the served HTML.
#
# 🔴 This is the check that matters, and the unit/feature PHPUnit suites are
# not a substitute for it. They prove TagBuilder's logic and that Antlers
# renders the tag's returned string unescaped when invoked as a TRUSTED
# template (Statamic\Facades\Antlers::parse($str, [], true) — see
# tests/Feature/TrackerTagTest.php and RELEASING.md for why the third
# argument matters). What they cannot prove is what a real, composer-built
# Statamic site actually ships: whether addon auto-discovery finds this
# package from a path repository, whether the settings page really persists
# to resources/addons/statamic-pulse-analytics.yaml the way the docs say, and
# whether the Control Panel genuinely carries nothing — not because we wrote
# a guard, but because it structurally cannot, as README.md claims.
#
# DOES NOT NEED A DATABASE. Statamic's default storage (the "stache") is flat
# files, so unlike the Drupal/Joomla/TYPO3 harnesses in this queue, there is
# no second service to wait on.
#
#   ./scripts/verify.sh            # pulls statamic/statamic ^6.0 (current)
#   ./scripts/verify.sh --mutate   # proves the invalid-domain guard is real
#
# Needs Docker. Every container, volume and network this script creates is
# named "pulse-verify-statamic-*" and nothing else; teardown removes exactly
# those, by name, and never prunes anything else on the machine.
#
# Exits non-zero if any case does not match (normal mode), or if a mutation
# FAILS TO turn a check red (--mutate mode — see the bottom of this file).

set -uo pipefail

ADDON_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PREFIX="pulse-verify-statamic"
VOLUME="${PREFIX}-data"
NETWORK="${PREFIX}-net"
BUILD_CONTAINER="${PREFIX}-build"
APP_CONTAINER="${PREFIX}-app"
HTTP_PORT="${HTTP_PORT:-18200}"
PHP_IMAGE="${PHP_IMAGE:-php:8.3-cli}"
STATAMIC_CONSTRAINT="${STATAMIC_CONSTRAINT:-^6.0}"
MUTATE=0
[ "${1:-}" = "--mutate" ] && MUTATE=1

command -v docker >/dev/null || { echo "REFUSING: docker is not on PATH"; exit 1; }
command -v curl >/dev/null || { echo "REFUSING: curl is not on PATH"; exit 1; }

# ── Teardown: ONLY the four named resources above, by name, never a prune ──
teardown() {
  docker rm -f "$BUILD_CONTAINER" "$APP_CONTAINER" >/dev/null 2>&1
  docker volume rm "$VOLUME" >/dev/null 2>&1
  docker network rm "$NETWORK" >/dev/null 2>&1
}
trap teardown EXIT
teardown # in case a previous run was interrupted before it could clean up

docker network create "$NETWORK" >/dev/null
docker volume create "$VOLUME" >/dev/null

# ── Optionally mutate the addon's own guard before it is copied in ─────────
# --mutate proves the "an invalid domain is dropped, never injected" check is
# a real guard and not a tautology: it patches TagBuilder::isValidDomain() to
# accept EVERYTHING, copies that mutated tree instead of the real one, and
# this script's own normal-mode assertion for that one case is expected to
# FAIL. If it does not fail, the check was never exercising the guard.
SOURCE_DIR="$ADDON_DIR"
if [ "$MUTATE" -eq 1 ]; then
  MUTATED="${TMPDIR:-/tmp}/${PREFIX}-mutated"
  rm -rf "$MUTATED"
  cp -R "$ADDON_DIR" "$MUTATED"
  rm -rf "$MUTATED/vendor" "$MUTATED/.git"
  # Replace the whole method body with an unconditional TRUE. sed -i differs
  # between GNU and BSD; this uses a portable perl one-liner instead.
  perl -0pi -e 's/public static function isValidDomain\(string \$domain\): bool\s*\{.*?\n    \}/public static function isValidDomain(string \$domain): bool\n    {\n        return true; \/\/ MUTATED by scripts\/verify.sh --mutate\n    }/s' \
    "$MUTATED/src/TagBuilder.php"
  grep -q 'MUTATED by scripts/verify.sh' "$MUTATED/src/TagBuilder.php" || {
    echo "REFUSING: the mutation did not apply — TagBuilder.php's shape changed since this script was written"
    exit 1
  }
  SOURCE_DIR="$MUTATED"
  echo "==> MUTATE mode: isValidDomain() patched to always return true"
fi

# ── Build: composer create-project a real Statamic site, require this addon
#    from a path repository (the same mechanism a private-addon install uses
#    per statamic.dev/addons/building-an-addon), as one disposable container
#    writing into the named volume ─────────────────────────────────────────
echo "==> building a Statamic $STATAMIC_CONSTRAINT site in the $VOLUME volume (first run, a few minutes)"
docker run --rm --name "$BUILD_CONTAINER" \
  --network "$NETWORK" \
  -v "$VOLUME:/site" \
  -v "$SOURCE_DIR:/addon:ro" \
  -w /site \
  "$PHP_IMAGE" bash -euc '
    apt-get update -qq && apt-get install -qq -y unzip git >/dev/null
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    composer create-project "statamic/statamic:'"$STATAMIC_CONSTRAINT"'" . --no-interaction --no-progress --prefer-dist
    php artisan key:generate --force
    cp -R /addon /site-addon
    rm -rf /site-addon/vendor /site-addon/.git
    composer config repositories.pulse path /site-addon --no-interaction
    composer require ciphera/statamic-pulse-analytics:@dev --no-interaction --no-progress -W
  '

echo "==> serving the site"
docker run -d --name "$APP_CONTAINER" \
  --network "$NETWORK" \
  -v "$VOLUME:/site" \
  -p "127.0.0.1:${HTTP_PORT}:8080" \
  -w /site \
  "$PHP_IMAGE" php artisan serve --host=0.0.0.0 --port=8080 >/dev/null

for _ in $(seq 1 40); do
  curl -sf -o /dev/null "http://127.0.0.1:${HTTP_PORT}/" && break
  sleep 1
done

FAILURES=0
CORE='https://js\.ciphera\.net/script\.js'
COMP='https://js\.ciphera\.net/script\.interactions\.js'

write_settings() {
  # The exact file FileSettingsRepository reads: resources/addons/<slug>.yaml.
  # Writing it directly is the same mechanism Tools -> Addons saves through —
  # it is not a shortcut around the real persistence path, it is that path,
  # the same way the sibling Joomla/TYPO3 harnesses seed via the real DB row
  # or the real config file rather than driving a browser.
  docker exec -i "$APP_CONTAINER" sh -c "mkdir -p resources/addons && cat > resources/addons/statamic-pulse-analytics.yaml" <<YAML
domain: '$1'
companion: $2
api: '$3'
YAML
}

# $1 label, $2 must-match ERE, $3 must-NOT-match ERE (may be empty)
check() {
  local html tags
  html=$(curl -s "http://127.0.0.1:${HTTP_PORT}/")
  tags=$(printf '%s' "$html" | grep -oE '<script[^>]*ciphera[^>]*></script>' | tr '\n' ' ')
  if printf '%s' "$tags" | grep -qE "$2"; then
    echo "    ✅ $1"
  else
    echo "    ❌ $1"
    echo "       expected to match: $2"
    echo "       got: ${tags:-<nothing>}"
    FAILURES=$((FAILURES + 1))
  fi
  if [ -n "${3:-}" ] && printf '%s' "$tags" | grep -qE "$3"; then
    echo "    ❌ $1 — unexpectedly present: $3"
    echo "       got: $tags"
    FAILURES=$((FAILURES + 1))
  fi
}

echo "==> negative control: the homepage before the tag is ever added"
HOMEPAGE_HITS=$(curl -s "http://127.0.0.1:${HTTP_PORT}/" | grep -c 'ciphera' || true)
if [ "$HOMEPAGE_HITS" -eq 0 ]; then
  echo "    ✅ no reference to ciphera on a stock Statamic homepage"
else
  echo "    ❌ a stock homepage already carries $HOMEPAGE_HITS reference(s) to ciphera — this harness cannot tell a real install from a broken one"
  FAILURES=$((FAILURES + 1))
fi

# ⚠️ Not yet run against a live `statamic/statamic` scaffold (no Docker in the
# task this harness was written under — see RELEASING.md). The default
# starter content is assumed to ship resources/views/layout.antlers.html with
# a literal </head>; if a future statamic/statamic release names or shapes it
# differently, this step is where to look first, before assuming the addon
# itself regressed.
echo "==> adding {{ pulse:tracker }} to the layout, the way the README says to"
docker exec "$APP_CONTAINER" sh -c '
  LAYOUT=$(find resources/views -iname "layout.antlers.html" | head -1)
  if [ -z "$LAYOUT" ]; then echo "REFUSING: no layout.antlers.html found"; exit 1; fi
  if ! grep -q "</head>" "$LAYOUT"; then echo "REFUSING: layout has no </head>"; exit 1; fi
  sed -i "s#</head>#{{ pulse:tracker }}\n</head>#" "$LAYOUT"
'

echo "==> checking the served HTML"

write_settings "example.com" "false" ""
check "domain only" \
  "<script defer data-domain=\"example\.com\" src=\"$CORE\"></script>" \
  ""

write_settings "chosen.dev" "true" "https://proxy.example.com"
# grep -oE extracts one match per line, and tr '\n' ' ' joins them with
# exactly one space — so the two tags are expected one literal space apart,
# the same convention the Joomla and TYPO3 harnesses in this queue use.
check "domain + api + companion, companion carries no domain" \
  "<script defer data-domain=\"chosen\.dev\" data-api=\"https://proxy\.example\.com\" src=\"$CORE\"></script> <script defer src=\"$COMP\"></script>" \
  ""

write_settings "" "false" ""
check "no domain configured means auto-detect, not a broken attribute" \
  "^<script defer src=\"$CORE\"></script>\$" \
  "data-domain"

# 🔴 This is the case --mutate targets. With isValidDomain() patched to
# return true unconditionally, the value below is treated as valid and
# injected verbatim — the must-NOT-match assertion fails, which is what
# proves the guard was real in normal mode.
write_settings 'bad" onload="x' "false" ""
check "an invalid domain is dropped, never injected" \
  "^<script defer src=\"$CORE\"></script>\$" \
  "onload"

echo "==> checking the Control Panel carries nothing"
# Not a guard this addon wrote — README.md's claim is that the Control Panel
# is a separate Vue/Inertia application that never evaluates Antlers at all,
# so there is structurally nowhere for {{ pulse:tracker }} to run. This
# measures that claim against a real response rather than asserting it.
CP_HITS=$(curl -s "http://127.0.0.1:${HTTP_PORT}/cp/login" | grep -c 'ciphera' || true)
if [ "$CP_HITS" -eq 0 ]; then
  echo "    ✅ the Control Panel carries no reference to ciphera"
else
  echo "    ❌ the Control Panel carries $CP_HITS reference(s) to ciphera"
  FAILURES=$((FAILURES + 1))
fi

echo
if [ "$MUTATE" -eq 1 ]; then
  # Inverted exit contract: success here means a check went red as expected.
  if [ "$FAILURES" -gt 0 ]; then
    echo "MUTATION PROVED THE GUARD IS REAL ($FAILURES check(s) went red, as expected)"
    exit 0
  else
    echo "MUTATION FAILURE: every check still passed with isValidDomain() patched to return true"
    echo "the 'invalid domain is dropped' check is not exercising a real guard"
    exit 1
  fi
fi

if [ "$FAILURES" -eq 0 ]; then
  echo "Statamic $STATAMIC_CONSTRAINT: ALL CHECKS PASSED"
else
  echo "Statamic $STATAMIC_CONSTRAINT: $FAILURES FAILURE(S)"
fi
exit "$FAILURES"
