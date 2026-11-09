<?php

namespace Ciphera\StatamicPulseAnalytics;

use Statamic\Providers\AddonServiceProvider;

/**
 * Wires the addon into Statamic.
 *
 * The {{ pulse:tracker }} tag itself needs no wiring — Statamic autoloads a
 * Tags class from src/Tags the same way it does for every other addon
 * component, which is why this class only has one job: register the settings
 * blueprint that backs the Control Panel page under Tools -> Addons.
 */
class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon()
    {
        $this->registerSettingsBlueprint(function () {
            return [
                'tabs' => [
                    'main' => [
                        'sections' => [
                            [
                                'display' => 'Pulse Analytics',
                                'fields' => [
                                    [
                                        'handle' => 'domain',
                                        'field' => [
                                            'type' => 'text',
                                            'display' => 'Domain',
                                            'instructions' => "Enter the domain this site is registered under in [Pulse Analytics](https://pulse.ciphera.net), for example `example.com`. If you leave it empty, the tracker uses the browser's own hostname, which is right for a site served on one domain.",
                                            'placeholder' => 'example.com',
                                            'width' => 50,
                                            // A mistyped domain is rejected here, at save time, rather
                                            // than stored and silently dropped at render time. An
                                            // analytics install that reports nothing while looking fine
                                            // is the worst outcome this addon can produce, so it is
                                            // worth an error message on the settings page.
                                            'validate' => ['nullable', 'regex:'.TagBuilder::DOMAIN_PATTERN],
                                        ],
                                    ],
                                    [
                                        'handle' => 'companion',
                                        'field' => [
                                            'type' => 'toggle',
                                            'display' => 'Record clicks, copies and form submits',
                                            'instructions' => 'This adds a second script as a separate request, so your pages only load it when you switch this on.',
                                            'default' => false,
                                            'width' => 50,
                                        ],
                                    ],
                                    [
                                        'handle' => 'api',
                                        'field' => [
                                            'type' => 'text',
                                            'display' => 'Custom API origin',
                                            'instructions' => 'If you route events through your own proxy, enter its origin here, for example `https://example.com`. Enter the origin only, without a path, because the tracker adds its own. Leave this empty if you have not set up a proxy.',
                                            'placeholder' => 'https://example.com',
                                            'width' => 50,
                                            'validate' => ['nullable', 'regex:#^https?://#i'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        });
    }
}
