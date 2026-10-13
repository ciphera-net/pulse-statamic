<?php

namespace Ciphera\StatamicPulseAnalytics\Tests\Feature;

use Ciphera\StatamicPulseAnalytics\ServiceProvider;
use Ciphera\StatamicPulseAnalytics\Tags\PulseTags;
use Statamic\Facades\Addon;
use Statamic\Facades\Antlers;
use Statamic\Testing\AddonTestCase;

/**
 * Renders {{ pulse:tracker }} through a real (headless) Statamic app and
 * reads the markup back.
 *
 * This is the test that matters, and the unit tests are not a substitute for
 * it. They assert what TagBuilder::buildTag() returns as a PHP string; this
 * asserts what Antlers actually puts on the page — whether the addon's
 * settings are found at all, whether a Tags class returning a literal string
 * gets echoed as-is or HTML-escaped a second time by the template engine, and
 * whether a mistyped domain is really rejected by the settings blueprint
 * rather than merely by a comment saying it should be.
 */
final class TrackerTagTest extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Settings are a real YAML file on disk (resources/addons/<slug>.yaml
        // in the host app), so it survives past the in-memory app Testbench
        // rebuilds for every test. Start every test from "never configured".
        $path = Addon::get($this->getPackage())->settings()->path();
        @unlink($path);
    }

    protected function tearDown(): void
    {
        $path = Addon::get($this->getPackage())->settings()->path();
        @unlink($path);

        parent::tearDown();
    }

    protected function setSetting(string $key, $value): void
    {
        Addon::get($this->getPackage())->settings()->set($key, $value)->save();
    }

    /**
     * Renders a string the way Statamic renders a real layout file: as a
     * TRUSTED template, not as user-submitted data.
     *
     * Antlers::parse()'s third argument defaults to false, which is the
     * sandboxed mode Statamic uses for content that came from a visitor or an
     * entry field — and tags are not executed in that mode. A layout file
     * calling {{ pulse:tracker }} is never evaluated that way in a real site,
     * so a test that left the default would be testing a mode this addon
     * never actually runs in, and {{ pulse:tracker }} would silently render
     * as nothing — which is exactly what the untrusted default did the first
     * time this test was written.
     *
     * The \r\n -> \n normalisation below is a second, separate finding: a
     * two-line return value comes back from Antlers with the join between
     * the two <script> tags rewritten to CRLF, measured with bin2hex() on the
     * raw output. That changes nothing a browser or this addon's contract
     * cares about — two sibling <script> elements parse identically either
     * way — so this test suite treats it as formatting, not a byte it needs
     * to pin, unlike an attribute's own serialisation (see RELEASING.md).
     */
    protected function render(string $template): string
    {
        return str_replace("\r\n", "\n", trim(Antlers::parse($template, [], true)));
    }

    public function test_the_tag_is_registered_under_the_pulse_handle(): void
    {
        $property = new \ReflectionProperty(PulseTags::class, 'handle');
        $property->setAccessible(true);

        $this->assertSame('pulse', $property->getValue());
        $this->assertTrue(method_exists(PulseTags::class, 'tracker'));
    }

    public function test_with_no_settings_saved_it_auto_detects(): void
    {
        $this->assertSame(
            '<script defer src="https://js.ciphera.net/script.js"></script>',
            $this->render('{{ pulse:tracker }}')
        );
    }

    public function test_bare_pulse_tag_is_the_same_as_pulse_tracker(): void
    {
        $this->assertSame(
            $this->render('{{ pulse:tracker }}'),
            $this->render('{{ pulse }}')
        );
    }

    public function test_reads_the_saved_domain(): void
    {
        $this->setSetting('domain', 'example.com');

        $this->assertSame(
            '<script defer data-domain="example.com" src="https://js.ciphera.net/script.js"></script>',
            $this->render('{{ pulse:tracker }}')
        );
    }

    public function test_reads_the_saved_companion_and_api_settings(): void
    {
        $this->setSetting('domain', 'chosen.dev');
        $this->setSetting('companion', true);
        $this->setSetting('api', 'https://proxy.example.com');

        $html = $this->render('{{ pulse:tracker }}');
        $lines = explode("\n", $html);

        $this->assertSame(
            '<script defer data-domain="chosen.dev" data-api="https://proxy.example.com" src="https://js.ciphera.net/script.js"></script>',
            $lines[0]
        );
        $this->assertSame(
            '<script defer src="https://js.ciphera.net/script.interactions.js"></script>',
            $lines[1]
        );
        // The companion never carries a domain of its own.
        $this->assertSame(1, substr_count($html, 'data-domain='));
    }

    public function test_tag_parameters_override_saved_settings(): void
    {
        $this->setSetting('domain', 'from-settings.example.com');

        $this->assertSame(
            '<script defer data-domain="from-param.example.com" src="https://js.ciphera.net/script.js"></script>',
            $this->render('{{ pulse:tracker domain="from-param.example.com" }}')
        );
    }

    public function test_a_domain_that_survived_outside_the_form_is_still_never_injected(): void
    {
        // The settings blueprint's own validation is what stops this from
        // being saved through the Control Panel in the first place — but a
        // hand-edited resources/addons/*.yaml file, or an older version's
        // settings carried across an upgrade, can still hold something that
        // never went through that check. The tag must not trust the file.
        $this->setSetting('domain', 'bad" onload="alert(1)');

        $html = $this->render('{{ pulse:tracker }}');

        $this->assertStringNotContainsString('onload', $html);
        $this->assertSame(
            '<script defer src="https://js.ciphera.net/script.js"></script>',
            $html
        );
    }

    public function test_the_settings_blueprint_rejects_a_mistyped_domain(): void
    {
        $addon = Addon::get($this->getPackage());

        $rules = collect($addon->settingsBlueprint()->fields()->all())
            ->flatMap(fn ($field) => $field->rules())
            ->all();

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['domain' => 'not a hostname', 'companion' => false, 'api' => ''],
            $rules
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('domain', $validator->errors()->toArray());
    }

    public function test_the_settings_blueprint_accepts_a_real_domain(): void
    {
        $addon = Addon::get($this->getPackage());

        $rules = collect($addon->settingsBlueprint()->fields()->all())
            ->flatMap(fn ($field) => $field->rules())
            ->all();

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['domain' => 'example.com', 'companion' => false, 'api' => ''],
            $rules
        );

        $this->assertFalse($validator->fails());
    }

    public function test_an_api_origin_without_a_scheme_is_rejected(): void
    {
        $addon = Addon::get($this->getPackage());

        $rules = collect($addon->settingsBlueprint()->fields()->all())
            ->flatMap(fn ($field) => $field->rules())
            ->all();

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['domain' => '', 'companion' => false, 'api' => 'proxy.example.com'],
            $rules
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('api', $validator->errors()->toArray());
    }

    public function test_nothing_it_emits_is_an_inline_script(): void
    {
        $this->setSetting('domain', 'example.com');
        $this->setSetting('companion', true);
        $this->setSetting('api', 'https://proxy.example.com');

        $html = $this->render('{{ pulse:tracker }}');

        foreach (explode("\n", $html) as $line) {
            $this->assertMatchesRegularExpression(
                '#^<script [^>]*src="https://js\.ciphera\.net/[^"]+"></script>$#',
                $line
            );
        }
    }

    public function test_composer_json_name_matches_the_hardcoded_addon_id(): void
    {
        // PulseTags::ADDON_ID is a literal string, independent of composer.json,
        // because the Tags class has no reflection-based way to ask "which
        // addon am I" the way AddonServiceProvider::getAddon() can. This test
        // is the drift guard: if the package is ever renamed, this fails
        // instead of the tag silently finding no settings.
        $this->assertSame($this->getPackage(), PulseTags::ADDON_ID);
    }
}
