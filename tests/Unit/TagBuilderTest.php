<?php

namespace Ciphera\StatamicPulseAnalytics\Tests\Unit;

use Ciphera\StatamicPulseAnalytics\TagBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests TagBuilder in isolation, with no Statamic bootstrap at all.
 *
 * This is the suite that matters for most of the contract: the literal bytes
 * of the tag, domain normalisation and validation, and attribute presence.
 * What it cannot prove is that Statamic's Antlers renderer echoes the
 * returned string unescaped rather than HTML-encoding it a second time —
 * that is tests/Feature/TrackerTagTest.php's job.
 */
final class TagBuilderTest extends TestCase
{
    public function test_normalize_domain_strips_everything_but_the_host(): void
    {
        $this->assertSame('example.com', TagBuilder::normalizeDomain('https://example.com/docs?a=1#b'));
        $this->assertSame('example.com', TagBuilder::normalizeDomain('http://example.com:8080'));
        $this->assertSame('example.com', TagBuilder::normalizeDomain('  ExAmPle.COM  '));
        $this->assertSame('www.example.com', TagBuilder::normalizeDomain('www.example.com.'));
    }

    public function test_is_valid_domain_accepts_registrable_hostnames(): void
    {
        $this->assertTrue(TagBuilder::isValidDomain('example.com'));
        $this->assertTrue(TagBuilder::isValidDomain('docs.example.co.uk'));
        $this->assertTrue(TagBuilder::isValidDomain('my-site.dev'));
    }

    public function test_is_valid_domain_rejects_everything_else(): void
    {
        // A bare label, so "localhost" can never be registered as a site.
        $this->assertFalse(TagBuilder::isValidDomain('localhost'));
        $this->assertFalse(TagBuilder::isValidDomain('-bad.com'));
        $this->assertFalse(TagBuilder::isValidDomain('example.123'));
        $this->assertFalse(TagBuilder::isValidDomain(''));
        // Anything that could break out of an HTML attribute.
        $this->assertFalse(TagBuilder::isValidDomain('example.com" onload="x'));
        $this->assertFalse(TagBuilder::isValidDomain('example.com x'));
        $this->assertFalse(TagBuilder::isValidDomain('example.com>'));
    }

    public function test_emits_the_literal_pulse_tag(): void
    {
        $this->assertSame(
            '<script defer data-domain="example.com" src="https://js.ciphera.net/script.js"></script>',
            TagBuilder::buildTag('example.com')
        );
    }

    public function test_omits_data_domain_when_there_is_none_to_use(): void
    {
        $this->assertSame(
            '<script defer src="https://js.ciphera.net/script.js"></script>',
            TagBuilder::buildTag('')
        );
    }

    public function test_drops_invalid_domain_rather_than_injecting_it(): void
    {
        // A caller is expected to normalize first, but buildTag() is a second,
        // independent guard — an imported setting or a hand-edited YAML file
        // can still carry something that is not a hostname, and dropping it
        // leaves a working install that auto-detects, which is strictly
        // better than injecting rubbish.
        $tag = TagBuilder::buildTag('bad" onload="alert(1)');

        $this->assertStringNotContainsString('data-domain', $tag);
        $this->assertStringNotContainsString('onload', $tag);
        $this->assertSame(
            '<script defer src="https://js.ciphera.net/script.js"></script>',
            $tag
        );
    }

    public function test_escapes_an_api_origin_that_carries_html_special_characters(): void
    {
        // Unlike domain, the API origin has no character-class regex of its
        // own in buildTag() — only "starts with http(s)://", checked where
        // the setting is saved, not here. A hand-edited settings file could
        // still carry something that was never through that check, so this is
        // the one place in the tag that genuinely depends on escaping rather
        // than on a value shape that cannot carry the dangerous characters.
        $tag = TagBuilder::buildTag('', 'https://evil.example.com"><script>alert(1)</script>');

        $this->assertStringNotContainsString('"><script>', $tag);
        $this->assertStringContainsString(
            'data-api="https://evil.example.com&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"',
            $tag
        );
    }

    public function test_carries_a_custom_api_origin(): void
    {
        $this->assertSame(
            '<script defer data-domain="example.com" data-api="https://proxy.example.com" src="https://js.ciphera.net/script.js"></script>',
            TagBuilder::buildTag('example.com', 'https://proxy.example.com')
        );
    }

    public function test_omits_data_api_by_default(): void
    {
        $this->assertStringNotContainsString('data-api', TagBuilder::buildTag('example.com'));
    }

    public function test_emits_one_tag_and_no_flag_attributes(): void
    {
        $tag = TagBuilder::buildTag('example.com', 'https://proxy.example.com');

        $this->assertSame(1, substr_count($tag, '<script'));
        $this->assertStringNotContainsString('interactions', $tag);
        $this->assertDoesNotMatchRegularExpression('/data-(no|ignore)-/', $tag);
        $this->assertStringContainsString('data-api="https://proxy.example.com"', $tag);
    }

    public function test_there_is_no_companion_url(): void
    {
        $this->assertFalse(defined(TagBuilder::class.'::COMPANION_URL'));
    }

    public function test_points_at_js_ciphera_net_and_nowhere_else(): void
    {
        $this->assertSame('https://js.ciphera.net/script.js', TagBuilder::SCRIPT_URL);
    }

    public function test_nothing_it_emits_is_an_inline_script(): void
    {
        // The whole point of a <script src> tag over an inline bootstrap is
        // that a site with a strict Content-Security-Policy needs no nonce
        // and no 'unsafe-inline' for this addon. Pin it here so a future
        // change cannot silently add inline JavaScript without a test
        // noticing.
        foreach (explode("\n", TagBuilder::buildTag('example.com', 'https://proxy.example.com')) as $line) {
            $this->assertMatchesRegularExpression(
                '#^<script [^>]*src="https://js\.ciphera\.net/[^"]+"></script>$#',
                $line
            );
        }
    }
}
