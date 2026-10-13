<?php

namespace Ciphera\StatamicPulseAnalytics\Tags;

use Ciphera\StatamicPulseAnalytics\TagBuilder;
use Statamic\Facades\Addon;
use Statamic\Tags\Tags;

/**
 * The {{ pulse:tracker }} tag — the SEO Pro pattern.
 *
 * Add it inside <head> in the site's layout, same as {{ seo_pro:meta }}:
 *
 *     {{ pulse:tracker }}
 *
 * By default it reads the Control Panel settings (Tools -> Addons -> Pulse
 * Analytics). Each value can be overridden per call, which is useful on a
 * multi-site install where one site needs a different domain or proxy than
 * whatever is saved:
 *
 *     {{ pulse:tracker domain="example.com" companion="true" }}
 */
class PulseTags extends Tags
{
    protected static $handle = 'pulse';

    /**
     * Must match composer.json's "name" exactly — it is the key Statamic
     * indexes addon settings under. ServiceProviderTest asserts the two never
     * drift apart.
     */
    public const ADDON_ID = 'ciphera/statamic-pulse-analytics';

    /**
     * The {{ pulse:tracker }} tag.
     */
    public function tracker(): string
    {
        $settings = $this->settings();

        $domain = (string) $this->params->get('domain', $settings->get('domain', ''));
        $domain = TagBuilder::normalizeDomain($domain);

        $companion = $this->params->bool('companion', (bool) $settings->get('companion', false));

        $api = (string) $this->params->get('api', $settings->get('api', ''));
        $api = rtrim(trim($api), '/');

        return TagBuilder::buildTag($domain, $companion, $api);
    }

    /**
     * {{ pulse }} on its own is the tracker — there is only one thing this
     * addon's tag does, so there is no reason to make the method name
     * mandatory.
     */
    public function index(): string
    {
        return $this->tracker();
    }

    /**
     * The addon's saved settings, or an empty bag if the addon record has not
     * been discovered yet (seen in some test harnesses; never in a real
     * install, where Statamic always knows about an installed addon).
     */
    protected function settings(): \Statamic\Contracts\Addons\Settings
    {
        $addon = Addon::get(self::ADDON_ID);

        if (! $addon) {
            throw new \RuntimeException(
                'The ciphera/statamic-pulse-analytics addon is not registered with Statamic. '
                .'Run `composer dump-autoload` after installing it.'
            );
        }

        return $addon->settings();
    }
}
