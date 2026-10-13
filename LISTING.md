# Statamic Marketplace listing — Pulse Analytics

The text a Marketplace product page needs, kept here so it has version
control the product page itself does not. Keep it in step with the addon.

**Status:** 🔴 **NOT YET SUBMITTED.** No statamic.com seller account exists
(owner-held, per `RELEASING.md`), and a Marketplace product links a Packagist
package that does not exist yet either. Nothing in this file has been tested
against the live submission form.

## Prerequisites, as of 14-10-2026

| | State |
|---|---|
| Packagist listing | 🔴 not yet — `RELEASING.md` step 1 |
| statamic.com seller account | 🔴 not yet — owner-held |
| GitHub repository | 🔴 not yet — this repo was built with no remote, per this task's scope |
| Verified on current Statamic major | ✅ Statamic 6 (`^v6.0.0`), measured against `statamic/cms` v6.35.1 and `statamic/seo-pro`'s own constraint |
| Licence | ✅ Apache-2.0 — `statamic/seo-pro` requires none, so this is a free choice, not a forced one (see `RELEASING.md`) |

## Listing fields

**Name:** Pulse Analytics

**Short description** (card/search-result length):

> Privacy-first web analytics. No cookies, no personal data, and the script it adds to your pages is under 3 KB.

**Long description:**

Pulse Analytics adds privacy-first web analytics to a Statamic site with one
addon. It sets no cookies, stores no personal data, and the script it adds to
your pages is under 3 KB.

How it works

1. `composer require ciphera/statamic-pulse-analytics`.
2. Add `{{ pulse:tracker }}` inside `<head>` in your layout — the same way
   you would add `{{ seo_pro:meta }}`.
3. Optionally go to **Tools → Addons → Pulse Analytics** to pin a domain, turn
   on interaction tracking, or route events through your own proxy. With
   nothing configured, the tracker uses the browser's own hostname — correct
   for a site served on one domain.
4. Your dashboard at pulse.ciphera.net shows visitors as they arrive.

What it measures

Pageviews, referrers, countries, devices, time on page and scroll depth, plus
the goals, funnels and campaigns you set up, in one dashboard hosted in
Switzerland. Visitors whose browser sends Do Not Track or Global Privacy
Control are not counted at all.

Settings

A domain (validated at save time, so a typo is caught immediately rather than
silently producing an install that reports nothing); a companion script for
clicks, copies and form submits, loaded as a second request so you only pay
for it if you want it; and a custom API origin, if you route events through
your own proxy. Every setting can also be overridden per call on the tag
itself, for a multi-site install.

You need

Statamic 6, PHP 8.2 or later, and a Pulse Analytics account with a site
registered for the same domain. The free plan is enough to start — one site,
5,000 pageviews a month, no card.

**Price:** Free.

**Category/tags:** Analytics · Utility.

**Licence:** Apache-2.0.

**Links:** Website `https://pulse.ciphera.net` · Documentation
`https://docs.ciphera.net/pulse` · Source
`https://github.com/ciphera-net/pulse-statamic` (not yet created — see
Status above).

## Screenshots needed

Statamic Marketplace product pages are driven by screenshots far more than
JED or drupal.org's text-heavy listings. At minimum, before submitting:

1. **The Control Panel settings page** (Tools → Addons → Pulse Analytics)
   showing the three fields — Domain, the companion toggle, Custom API
   origin — with their instructions text visible.
2. **A validation error on a mistyped domain**, to show the save-time check
   rather than describe it.
3. **The `{{ pulse:tracker }}` line in a layout file**, in a code editor or
   the Statamic Control Panel's own file view, to make the one-line
   integration visible rather than asserted.

None of these exist yet — they need a real Control Panel session against a
site with this addon installed, which needs the Docker harness
(`scripts/verify.sh`) or an equivalent local install, neither done as part of
this task.

## What stays true regardless of where this is submitted

Same facts as every other public Pulse integration's listing: Pulse Analytics
is built by Ciphera BV, a company in Belgium; data is hosted in Switzerland;
no cookies, no personal data stored; the tracking script is under 3 KB
gzipped; free plan is one site, 5,000 pageviews a month, no card; Business
plan from €11/month; every feature on every plan. Never a legal claim such as
"GDPR compliant", "no tracking" or "no consent banner needed", and never a
point script-size figure — "under 3 KB" is the only size claim this addon or
its listing makes.
