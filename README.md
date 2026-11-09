# Pulse Analytics for Statamic

This addon adds privacy-first analytics to a Statamic site. Pulse Analytics
sets no cookies and never stores IP addresses, and the script the addon adds to
your pages is under 3 KB.

[Pulse Analytics](https://pulse.ciphera.net) is built by
[Ciphera](https://ciphera.net), a company in Belgium, and hosted in
Switzerland.

## Requirements

The addon requires Statamic 6 and PHP 8.2 or later. It has no database tables
or front-end dependencies. You need a Pulse Analytics account with a site
registered for the same domain. The free plan is enough to start.

## Install

```bash
composer require ciphera/statamic-pulse-analytics
```

Add the tag inside `<head>` in your layout, the same way you would add
`{{ seo_pro:meta }}`:

```antlers
{{ pulse:tracker }}
```

A single-domain site does not need any further setup. With nothing configured,
the tracker uses the browser's own hostname. To pin a domain explicitly, go to
**Tools → Addons → Pulse Analytics** in the Control Panel and set **Domain** to
the domain this site is registered under in Pulse.

## Settings

You can configure these from **Tools → Addons → Pulse Analytics**, or per call
as tag parameters (see below).

| Setting | Default | What it does |
|---|---|---|
| Domain | empty | The domain this site is registered under in Pulse. Empty means auto-detect. |
| Record clicks, copies and form submits | off | Loads the companion script as a second, separate request (see below). |
| Custom API origin | empty | Route events through your own proxy origin. Enter the origin only, without a path. |

### Per-call overrides

Every setting can be overridden on the tag itself, which is useful on a
multi-site install where one site needs a different domain or proxy than
whatever is saved in Tools → Addons:

```antlers
{{ pulse:tracker domain="example.com" companion="true" api="https://proxy.example.com" }}
```

## Four things worth knowing

**What lands in your pages is the literal tag:**

```html
<script defer data-domain="example.com" src="https://js.ciphera.net/script.js"></script>
```

The addon adds no inline JavaScript, so **a strict Content-Security-Policy does
not need a nonce or `unsafe-inline` for this addon**. The policy only has to
allow `script-src https://js.ciphera.net` and
`connect-src https://pulse-api.ciphera.net`. That is pinned by a test that
renders the tag through a real Statamic app and reads the HTML back, so it
cannot quietly stop being true.

**There is no "also track administration pages" setting, on purpose.** The
Drupal, Joomla and TYPO3 integrations auto-inject on every request, including
their admin screens, so each of them needs a guard and a setting to turn
tracking off there. Statamic's Control Panel is a separate Vue/Inertia
application, and Antlers does not render its pages, so `{{ pulse:tracker }}`
has nowhere to run inside it even if you tried to put it there. The addon's
tag only ever executes where you call it, in a front-end layout or template.
Admin exclusion here is structural, and a toggle that could never do anything
would be worse than no toggle at all.

**The interaction capture is a second request on purpose.** The core
script's size is a published claim, and nothing is folded into it to make a
feature look free.

**A mistyped domain is rejected at the settings form** instead of being saved
and quietly dropped. An analytics install that reports nothing while looking
fine is the worst outcome this addon can produce, so the form shows an error
message. If a domain ever does reach storage some other way (a hand-edited
`resources/addons/statamic-pulse-analytics.yaml`, or settings carried across
from an older version), the tag drops it and falls back to auto-detection.
That is the same two-layer guard the Drupal, Joomla and TYPO3 integrations use.

## Why a tag instead of automatic injection

Drupal, Joomla and TYPO3 all hook into their own page-rendering pipeline and
add the tag to every response without the site owner writing anything.
Statamic has no equivalent single rendering choke point to hook, because a
site's layout is itself the template the site owner authors. The idiomatic
Statamic shape, the one `statamic/seo-pro` itself uses for its own
`{{ seo_pro:meta }}` tag, is a tag the layout calls once. That is one line in
one file, and unlike an addon that hooks a framework-wide render event, it is
visible in the source you already own.

## What Pulse Analytics measures

Pulse Analytics measures pageviews, referrers, countries, devices, time on page and scroll depth, plus goals, funnels and campaigns.
It sets no cookies and never stores IP addresses.
Visitors whose browser sends Do Not Track or Global Privacy Control are not counted, so if you test in Brave or Firefox and see nothing, the tracker is behaving correctly.
Safari is the easiest browser to verify an install in.

## Licence

Copyright 2026 Ciphera BV. Licensed under the Apache License, Version 2.0. The
`LICENSE` file is the licence text verbatim, so the copyright line lives here.

Statamic is a registered trademark of Statamic, LLC. This is an independent
addon and is not affiliated with or endorsed by Statamic or Statamic, LLC.
