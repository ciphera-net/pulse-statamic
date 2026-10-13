# Releasing statamic-pulse-analytics

## Status: built and verified locally, 14-10-2026 — not yet published anywhere

No tag, no Packagist listing, no Statamic Marketplace product. This file
records the exact steps and the account each one needs, written before any of
them have been taken, the same shape as `pulse-typo3`'s and `pulse-joomla`'s
`RELEASING.md` before their first release.

## Platform version targeted

**Statamic 6** (`statamic/cms: ^6.0`), PHP 8.2+. Verified live on Packagist
and GitHub on 14-10-2026: `packagist.org/packages/statamic/cms` serves
**v6.35.1** (2026-10-06) as the newest stable tag, and `statamic/seo-pro`'s
own `composer.json` — Statamic's own first-party addon — already requires
`statamic/cms: ^v6.0.0`. Statamic 5 still receives maintenance releases
(`v5.74.x`) but is not the current major; this addon does not attempt to
support it. The full-site starter metapackage, `statamic/statamic`, pins
`php: ^8.3`, but that is the SITE's floor, not the addon's — `statamic/cms`
itself (what an addon actually depends on) declares `^8.2`, so that is what
`composer.json` here requires.

## Local verification, 14-10-2026 (PHP 8.5.10, Composer 2.10.3)

`composer install` resolves clean on this machine's PHP 8.5 — `statamic/cms`
and `orchestra/testbench` both installed with no conflicts and no PHP-version
warning, unlike some PHP 8.5 fixture issues measured elsewhere in this queue
(Drupal 10's SQLite driver deprecations). Installed: Laravel **v12.70.0**,
`orchestra/testbench` **v10.12.0**, `phpunit/phpunit` **v11.5.57**,
`statamic/cms` **v6.35.1**.

```
$ vendor/bin/phpunit
.........................                                         25 / 25 (100%)
OK (25 tests, 50 assertions)
```

Two suites:

- **Unit** (13 tests) — `TagBuilder` alone, zero Statamic bootstrap. Runs on
  any PHP toolchain with just `phpunit/phpunit` installed.
- **Feature** (12 tests) — `Statamic\Testing\AddonTestCase` (Orchestra
  Testbench underneath), the same base class Statamic's own addons use.
  Boots a real, headless Statamic app, renders `{{ pulse:tracker }}` through
  the actual Antlers engine, and round-trips settings through the real
  `FileSettingsRepository` (a real YAML file under
  `resources/addons/statamic-pulse-analytics.yaml`, in the testbench
  skeleton app's own directory — never this repository).

Repeated under `--order-by=random` twice with the same result, because the
settings file is a real file on disk that outlives any one test's in-memory
app: `setUp()`/`tearDown()` delete it explicitly so no test can inherit
another's state.

## 🔑 `Antlers::parse()` defaults to a mode where tags do not run

Found while writing `tests/Feature/TrackerTagTest.php`, and worth recording
because it looks exactly like a broken tag. `Statamic\Facades\Antlers::parse(
$str, $variables = [], $trusted = false)` sets
`GlobalRuntimeState::$isEvaluatingUserData = !$trusted` — **the default is
untrusted**, which is the mode Statamic uses for content that came from a
visitor or an entry field, and tags do not execute in it. Calling
`Antlers::parse('{{ pulse:tracker }}')` with no third argument renders as
nothing: no exception, no log line, an empty string, in every manual probe
that preceded finding this (direct tag registration lookup
`app('statamic.extensions')`, direct method invocation on a constructed
`PulseTags` instance — both worked). The fix is the third argument:
`Antlers::parse($str, [], true)`.

This is not a defect in the addon or a workaround for a defect in Statamic —
`{{ pulse:tracker }}` inside a real layout file is compiled and rendered as a
**trusted template**, the same path every other tag in that file runs
through, and never hits the untrusted branch at all. It only matters here
because `Antlers::parse()` is the facade method used to test a tag's output
directly, and its default argument is the one mode a real layout never uses.
`render()` in the test file wraps this with an explanation so the next
person does not have to re-discover it.

## 🔑 A multi-line tag return comes back through Antlers with `\r\n`, not `\n`

`TagBuilder::buildTag()` joins the tracker tag and the companion tag with a
bare `"\n"`. Measured with `bin2hex()` on the rendered output: Antlers hands
it back with the join rewritten to `\r\n`. Confirmed harmless — two sibling
`<script>` elements parse identically regardless of the byte between them,
and nothing in this addon's contract depends on it — but it is exactly the
shape of finding this queue has paid for before (Docusaurus re-serialising
`defer` to `defer="defer"` through its HTML minifier): **the same PHP string,
a different byte sequence once the platform has touched it.** The test
suite's `render()` helper normalises `\r\n` back to `\n` before asserting,
with a comment explaining why that normalisation is safe here and would not
be if the difference were in an attribute's own serialisation rather than
inter-tag whitespace.

## `scripts/verify.sh` — written, not run

This task's constraints forbid running Docker, and the real-install harness
needs it (a full `statamic/statamic` site has no SQLite-only shortcut the way
TYPO3's harness does, but it does need no database at all — Statamic's
default storage is flat files, so there is one fewer moving part than the
Drupal/Joomla/TYPO3 siblings). `scripts/verify.sh` is written from the same
facts this file already states — the real settings file path, the real
addon-id string, the real Antlers-is-always-trusted behaviour of an actual
rendered page — but it has not been exercised against a live container.
**Run it before trusting it**, and expect the first run to need at least one
fix: the layout-file-shape assumption it names inline is the most likely
place.

```bash
./scripts/verify.sh            # normal mode: every case must pass
./scripts/verify.sh --mutate   # proves the invalid-domain guard is real
```

Every container, volume and network it creates is named
`pulse-verify-statamic-*`; teardown removes exactly those four resources by
name and never prunes.

## Licence: Apache-2.0 — unforced, unlike three siblings in this queue

Verified from Statamic's own addon, not assumed: `statamic/seo-pro`'s
`composer.json` declares no required licence at all, and neither the official
"Building an Addon" guide nor the Marketplace publishing section mandates
one. That is the opposite constraint from Drupal (GPL-2.0-or-later, hosting
requirement), Joomla (GPL, JED requirement) and TYPO3 (GPL-2.0-only or
-or-later, "single combined program" position) — none of which apply to a
Composer package consumed by a Laravel application. **Apache-2.0**, matching
every other public Pulse integration except those three.

## Publishing — three surfaces, three accounts

### 1. Packagist — the composer.json name is the only unclaimed-name risk

```bash
composer validate --no-check-publish
git tag v1.0.0 && git push origin v1.0.0
```

Then, on <https://packagist.org>, **Submit** the GitHub repository URL once a
`ciphera-net/pulse-statamic` GitHub remote exists (this repo currently has
none — it was built with no remote, per this task's constraints). Packagist
needs:

- A packagist.org account (GitHub OAuth is the normal path) — **owner-held**,
  same account every other public Pulse package publishes under.
- A GitHub webhook on the repo (Packagist can install this itself once the
  account is linked), so a future tag auto-updates the listing with no manual
  re-submit step.

`composer require ciphera/statamic-pulse-analytics` resolves from Packagist
the moment this is done; nothing about it is reviewed.

### 2. GitHub — the source of truth for Packagist and for the Marketplace link

`ciphera-net/pulse-statamic`, created empty, this repository pushed to
`main`. No branch protection decision has been made yet — follow the
convention the rest of this queue's public repos use (`main` protected, PR
required, 0 approvals) once a CI pipeline is wired up. **This addon has no CI
pipeline of any kind yet.** Given `pulse-drupal`'s GitLab-native and
`pulse-joomla`/`pulse-typo3`'s "local harness only" precedents, and that
`composer.json`'s own test suite (`vendor/bin/phpunit`) is the natural gate
here, a CI job running `composer install && vendor/bin/phpunit --testsuite
Unit` (the Feature suite needs the same heavier Statamic+Testbench install
this file already measured as working, so there is no reason to leave it out
of CI too) is the obvious next step before a real release — deliberately
left undone here, since adding CI was not part of this task's scope.

### 3. Statamic Marketplace — statamic.com/marketplace

Per `statamic.dev/addons/building-an-addon` → "Publishing to the Marketplace":

1. A packagist.org listing must exist first (step 1 above) — the Marketplace
   product links to it, it does not host the package itself.
2. A **statamic.com seller account**, created at
   `statamic.com/creator/begin` — **owner-held, not yet created.**
3. In the seller dashboard, create a product: link the Packagist package,
   set the price (**free**, per this addon's whole premise — Pulse Analytics
   itself has a free plan), write the listing copy (`LISTING.md` carries it),
   attach screenshots.
4. The product starts as a **draft** — previewable and editable before
   anything is public.
5. Publish when ready. The addon then appears both on the public Marketplace
   page and inside every site's own Control Panel → Addons browser.

Statamic's docs describe no review gate beyond the seller account itself
(contrast with Drupal's DrupalCode-access gate or the JED's 60-day
flag-and-requeue window) — this is recorded as read from the docs, not
re-measured against a live submission, since no seller account exists yet to
test it against.

## What a bump touches

1. `composer.json`'s `require.statamic/cms` constraint, if the floor moves
   (e.g. Statamic 7 becomes current — re-run the same Packagist/GitHub check
   this file's "Platform version targeted" section describes, against
   `statamic/seo-pro` or another first-party addon, not against this file's
   memory of today's date).
2. `vendor/bin/phpunit` — both suites — before tagging.
3. `scripts/verify.sh` against the new major, once it has been run for the
   first time at all.
4. A git tag (`vN.N.N`, no `v`-prefix ambiguity the way Drupal's packaging
   script forced — plain semver, Packagist reads tags directly).

## Notes for the next person

- 🔴 **`PulseTags::ADDON_ID` is a literal string**
  (`ciphera/statamic-pulse-analytics`), not derived from `composer.json` at
  runtime. `AddonServiceProvider::getAddon()` can resolve "which addon am I"
  by matching the calling class's namespace against the registered manifest,
  but that method is `protected` on the service provider — a `Tags` class has
  no equivalent. `test_composer_json_name_matches_the_hardcoded_addon_id` is
  the drift guard: rename the package without updating the constant, and this
  fails instead of the tag silently finding no settings.
- The settings blueprint's `validate` rules are plain Laravel rule arrays
  (`['nullable', 'regex:' . TagBuilder::DOMAIN_PATTERN]`), not a custom `Rule`
  object. A `Rule` instance was considered and rejected: addon settings
  blueprints are plain PHP arrays sent to the Control Panel's Vue frontend as
  part of the field's config, and an unserializable object in that array is
  an unnecessary risk for no benefit a regex string does not already give.
- Statamic's own `statamic/seo-pro` was read for the real shape of
  `extends AddonServiceProvider` / `bootAddon()`, and
  `oliweb/statamic-privacy-analytics` (a real, published, privacy-analytics
  Statamic addon) for `"type": "statamic-addon"` and the `require` shape —
  both confirmed live from their GitHub source, not from documentation
  alone, per this queue's "read the ecosystem's own accepted artefacts" rule.
