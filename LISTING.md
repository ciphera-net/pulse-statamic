# Statamic Marketplace listing — Pulse Analytics

The text a Marketplace product page needs, kept here so it has version
control the product page itself does not. Keep it in step with the addon.

**Status:** 🟡 **Submitted, pending review** (10-11-2026, 22:08 UTC). Product 1104 on
statamic.com holds every field below, the thumbnail and the three screenshots; its page reads "Awaiting review.
Approval will publish your product." The draft preview (`statamic.com/addons/ciphera/pulse-analytics`) answers 404
to anyone not signed in as the creator until then.

The statamic.com account is connected to GitHub through the **Statamic Marketplace GitHub App**, installed on the
`ciphera-net` organisation for **`pulse-statamic` only** (Creator Shop → Settings → Source Control shows "ciphera-net
(Organisation)"); the repository lookup lists that one repository. The product's GitHub Repo field is
`ciphera-net/pulse-statamic`.

Statamic reviews every new product against its submission guidelines
(`https://statamic.com/marketplace/submission-guidelines`, revised 02-11-2026),
one product in review at a time per creator. The form also asks who uses the
product; that answer stays private to the review team.

## Prerequisites, as of 10-11-2026

| | State |
|---|---|
| Packagist listing | ✅ `ciphera/statamic-pulse-analytics` serves v1.0.3 (on Packagist since 14-10-2026 as v1.0.0) |
| statamic.com creator profile | ✅ "Ciphera", product 1104 submitted 10-11-2026, pending review |
| GitHub connection on statamic.com | ✅ the Statamic Marketplace GitHub App on `ciphera-net`, this repository only |
| GitHub repository | ✅ public: `ciphera-net/pulse-statamic`, issues on (the product page's "Get Support" link opens them) |
| Release notes | ✅ every tag has a GitHub Release; statamic.com builds the Release Notes tab from them |
| Verified on current Statamic major | ✅ Statamic 6 (`^6.0`); the latest stable is v6.40.0 (10-11-2026), and `scripts/verify.sh` installs `statamic/statamic ^6.0` |
| Licence | ✅ Apache-2.0 (`statamic/seo-pro` requires none, so this is a free choice, not a forced one; see `RELEASING.md`) |

## Listing fields, as saved on 10-11-2026

**Name:** Pulse Analytics. **Slug:** `pulse-analytics`
(`statamic.com/addons/ciphera/pulse-analytics`). **Type:** Addon.

**Categories:** Analytics (primary), Integration.

**Summary:**

> Pulse Analytics counts visitors without cookies or stored IP addresses. The script this addon adds to your pages is under 3 KB.

**Who is using this product, or has specifically asked for it, and what problem
does it solve for them?** (private to the review team)

> It is for Statamic site owners who use Pulse Analytics, our privacy-first analytics service, which sets no cookies and never stores IP addresses. Without the addon they paste the tracking tag into a template by hand and can mistype the domain without noticing. The addon gives them one {{ pulse:tracker }} tag for the layout, checks the domain when they save it, and lets each site on a multi-site install use its own settings. We also maintain Pulse integrations for WordPress, Drupal, Joomla, TYPO3 and Craft. Before each release we install this addon on a fresh Statamic 6 site and check the tag in the served HTML.

**Product description:** "Write a description" (not the README, which is
written for developers). Markdown, **ASCII only**: statamic.com decodes the description's UTF-8 as Latin-1, so on the
preview of 10-11-2026 "→" showed as "â" and "€" as "â¬" (the same fault as the Odoo store). Write ">" and "euros":

````markdown
Pulse Analytics adds privacy-first web analytics to a Statamic site with one addon. It sets no cookies and never stores IP addresses. The script it adds to your pages is under 3 KB.

## How it works

1. Run `composer require ciphera/statamic-pulse-analytics`.
2. Add `{{ pulse:tracker }}` inside `<head>` in your layout, the same way you would add `{{ seo_pro:meta }}`.
3. Optionally open **Tools > Addons > Pulse Analytics** in the Control Panel to pin a domain, turn on interaction tracking, or route events through your own proxy. With nothing configured, the tracker uses the browser's own hostname, which is right for a site served on one domain.
4. Your dashboard at [pulse.ciphera.net](https://pulse.ciphera.net) shows visitors as they arrive.

## What it measures

Pulse counts pageviews, referrers, countries, devices, time on page and scroll depth, plus the goals, funnels and campaigns you set up, in one dashboard hosted in Switzerland. It does not count visitors whose browser sends Do Not Track or Global Privacy Control.

## What it sends

The tag loads the tracker from js.ciphera.net. The tracker sends each pageview and event to pulse-api.ciphera.net, or to your own proxy if you set a custom API origin. The addon itself makes no requests from your server.

## Settings

The domain is checked when you save, so a typo is caught at once. A mistyped domain would otherwise leave you with an install that reports nothing. A companion script for clicks, copies and form submits is optional. It loads as a second request, so a site that leaves it off never downloads it. A custom API origin is there for sites that route events through their own proxy. On a multi-site install, every setting can be overridden per call on the tag itself:

```antlers
{{ pulse:tracker domain="example.com" companion="true" api="https://proxy.example.com" }}
```

## Requirements

You need Statamic 6, PHP 8.2 or later, and a Pulse Analytics account with a site registered for the same domain. The free plan is enough to start: it covers one site and 10,000 events a month, and you do not need a card. Past that, the Business plan starts at 9 euros a month with VAT.
````

**Media:** the thumbnail and the three screenshots below. No demo URL or video:
the addon has no interface of its own beyond its settings page.

**Installation:** Composer package `ciphera/statamic-pulse-analytics`; no minimum
version or end of life; actively maintained. GitHub Repo: waits for the GitHub
connection above.

**Support:** external docs at `https://pulse.ciphera.net/integrations/statamic`;
Official support.

**Pricing:** Free, no Stripe account connected. **Editions:** none.

## Icon and thumbnail

The Marketplace does not read anything from the repository for its image. The
product form's Media tab takes a **thumbnail**: "Square artwork, at least
600 × 600 px", PNG, JPG, GIF or WebP, 5 MB max (measured 10-11-2026). Product
pages draw it at 64 px, 96 px on wider screens, with rounded corners and
`object-fit: cover`. The uploaded file is the brand library's
`https://cdn.ciphera.net/brand/v12/kit/statamic/pulse/thumbnail-1200.png`
(1200 × 1200, SHA-256
`7e072ad73263ce6af627de18a148f0b836085a0dd961d43dca4ff734ed5afcb3`): the Pulse
mark in white layers on the brand orange `#FD5E0F`, the same mark as Ciphera's
profile pictures. statamic.com stores it byte for byte (checked 10-11-2026).

`icon.svg` in the repository root is the same mark as a 512 x 512 vector, copied
byte for byte from the brand library
(`https://cdn.ciphera.net/brand/v12/kit/statamic/pulse/icon.svg`, SHA-256
`2255d21f32933e8f33a919943cc8423dda260343a12088347e5ab183930e0abe`). Nothing on
statamic.com reads it.

Screenshots are drawn whole (`fit=scale-down`, up to 1280 px wide on the
product page), so their 16:10 and wide shapes are shown as captured.

## Screenshots

Three screenshots were captured on 09-11-2026 from a real Statamic 6 site running the addon at 1.0.2. They are
kept outside this repository.

1. **The settings page** (Addons → Pulse Analytics, 2560 x 1600), saved with the domain `example.com`.
2. **A validation error** (2560 x 1600): the same page after saving `example com`, which Statamic refuses with
   "Format is invalid." before anything is stored.
3. **The layout file** (2750 x 895): `resources/views/layout.antlers.html` with `{{ pulse:tracker }}` on line 9,
   inside `<head>`.

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
