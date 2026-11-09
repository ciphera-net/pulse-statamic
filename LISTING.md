# Statamic Marketplace listing — Pulse Analytics

The text a Marketplace product page needs, kept here so it has version
control the product page itself does not. Keep it in step with the addon.

**Status:** 🔴 **NOT YET SUBMITTED.** No statamic.com seller account exists
(see `RELEASING.md`), and a Marketplace product is created in the
seller dashboard. The package it links is ready:
`ciphera/statamic-pulse-analytics` serves v1.0.1 on Packagist, and the GitHub
repository is public. Nothing in this file has been tested against the live
submission form.

## Prerequisites, as of 09-11-2026

| | State |
|---|---|
| Packagist listing | ✅ `ciphera/statamic-pulse-analytics` serves v1.0.1 (on Packagist since 14-10-2026 as v1.0.0) |
| statamic.com seller account | 🔴 not yet |
| GitHub repository | ✅ public: `ciphera-net/pulse-statamic` |
| Verified on current Statamic major | ✅ Statamic 6 (`^v6.0.0`), measured against `statamic/cms` v6.35.1 and `statamic/seo-pro`'s own constraint |
| Licence | ✅ Apache-2.0 — `statamic/seo-pro` requires none, so this is a free choice, not a forced one (see `RELEASING.md`) |

## Listing fields

**Name:** Pulse Analytics

**Short description** (card/search-result length):

> Pulse Analytics counts visitors without cookies or stored IP addresses. The script this addon adds to your pages is under 3 KB.

**Long description:**

Pulse Analytics adds privacy-first web analytics to a Statamic site with one addon. It sets no cookies and never stores IP addresses. The script it adds to your pages is under 3 KB.

How it works

1. Run `composer require ciphera/statamic-pulse-analytics`.
2. Add `{{ pulse:tracker }}` inside `<head>` in your layout, the same way you would add `{{ seo_pro:meta }}`.
3. Optionally go to **Tools → Addons → Pulse Analytics** to pin a domain, turn on interaction tracking, or route events through your own proxy. With nothing configured, the tracker uses the browser's own hostname, which is right for a site served on one domain.
4. Your dashboard at pulse.ciphera.net shows visitors as they arrive.

What it measures

Pulse counts pageviews, referrers, countries, devices, time on page and scroll depth, plus the goals, funnels and campaigns you set up, in one dashboard hosted in Switzerland. It does not count visitors whose browser sends Do Not Track or Global Privacy Control.

Settings

The domain is validated when you save, so a typo is caught at once and does not leave you with an install that reports nothing. A companion script for clicks, copies and form submits is optional. It loads as a second request, so you only pay for it if you want it. A custom API origin is there for sites that route events through their own proxy. On a multi-site install, every setting can be overridden per call on the tag itself.

You need

Statamic 6, PHP 8.2 or later, and a Pulse Analytics account with a site registered for the same domain. The free plan is enough to start: it covers one site and 10,000 events a month, and you do not need a card.

**Price:** Free.

**Category/tags:** Analytics · Utility.

**Licence:** Apache-2.0.

**Links:** Website `https://pulse.ciphera.net` · Documentation
`https://docs.ciphera.net/pulse` · Source
`https://github.com/ciphera-net/pulse-statamic`.

## Icon

`icon.svg` in the repository root is the addon's icon: the Pulse mark on the
dark ground `#0a0a0a`, 512 x 512, copied byte for byte from the brand
library (`https://cdn.ciphera.net/brand/v7/kit/statamic/pulse/icon.svg`,
SHA-256 `d9295298ebc311bee6b10977adc24fca9f899aa641cb4aa31cda6832e18f3264`).

The Marketplace does not read it from the repository. A product's image is
uploaded in the seller account and served from statamic.com's own storage
(`/storage/products/<hash>.png`), and the product page draws it at 64 px,
96 px on wider screens, with rounded corners and `object-fit: cover`
(measured on three live product pages, 19-10-2026). So upload a PNG export
of `icon.svg` when the product is created. The mark keeps at least 18 % of
the image clear on every side, so the rounded corners never touch it.
The three first-party addon repositories checked (`statamic/importer`,
`statamic/ssg`, `statamic/eloquent-driver`) ship no Marketplace icon either;
`statamic/importer`'s `resources/svg/icon.svg` is its Control Panel
navigation icon.

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
it sets no cookies and never stores IP addresses; the tracking script is under
3 KB gzipped; the free plan covers one site and 10,000 events a month, with no
card; Business starts at €9 a month with VAT; every plan has every feature.
Never a legal claim such as
"GDPR compliant", "no tracking" or "no consent banner needed", and never a
point script-size figure — "under 3 KB" is the only size claim this addon or
its listing makes.
