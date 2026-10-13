<?php

namespace Ciphera\StatamicPulseAnalytics;

/**
 * Builds the Pulse Analytics tag.
 *
 * Pure PHP: no Statamic facade, no Laravel container, no addon settings —
 * everything comes in as arguments and an HTML string comes out. That is
 * deliberate, so the part that decides what the tag says is unit-testable
 * without booting Statamic, and so it can be read side by side with the same
 * decisions in the Drupal, Joomla and TYPO3 integrations.
 *
 * The URLs, the domain grammar and the rule that the companion script is a
 * SECOND request are copied from those integrations rather than re-derived.
 * Pulse integrations disagreeing about what the tag looks like is a drift bug
 * waiting to happen.
 */
final class TagBuilder
{
    /**
     * The Pulse tracker.
     */
    public const SCRIPT_URL = 'https://js.ciphera.net/script.js';

    /**
     * The companion script: clicks, copies and form submits.
     */
    public const COMPANION_URL = 'https://js.ciphera.net/script.interactions.js';

    /**
     * A registrable hostname.
     *
     * Labels of letters, digits and hyphens, at least one dot, a letter-only
     * TLD. Deliberately strict — the value lands inside an HTML attribute, and
     * this shape cannot carry a quote, a space or a bracket. The attribute is
     * still escaped on the way out, so this is the first of two guards rather
     * than the only one; it is kept anyway because a domain that cannot be a
     * hostname is a typo, and a typo is an install that reports nothing and
     * looks fine.
     */
    public const DOMAIN_PATTERN = '/^(?=.{1,253}$)(?!-)[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*\.[a-z]{2,63}$/';

    /**
     * Lower-cases a domain and strips a scheme, path, port and trailing dot.
     *
     * Keeps "www." — the Pulse site may be registered either way and whoever
     * fills the setting in can edit it either way.
     */
    public static function normalizeDomain(string $input): string
    {
        $value = mb_strtolower(trim($input));
        $value = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $value);
        $value = preg_replace('#[/?\#].*$#', '', $value);
        $value = preg_replace('/:.*$/', '', $value);

        return rtrim($value, '.');
    }

    /**
     * Whether a normalised domain is a registrable hostname.
     */
    public static function isValidDomain(string $domain): bool
    {
        return (bool) preg_match(self::DOMAIN_PATTERN, $domain);
    }

    /**
     * Builds the literal HTML for the tag, ready to echo in a layout.
     *
     * @param  string  $domain  The domain the site is registered under in
     *     Pulse, or '' to let the tracker auto-detect the browser's hostname.
     *     An invalid non-empty domain is dropped rather than injected, and the
     *     tag falls back to auto-detection — the same working install, one
     *     attribute short. Callers are expected to have already run this
     *     through normalizeDomain(), but an unnormalised value that still
     *     validates will render as given.
     * @param  bool  $companion  Whether to also emit the companion script.
     * @param  string  $api  A custom API origin, or '' for the default.
     *     Trailing slashes are not stripped here — callers do that, the same
     *     place they decide the value came from a trusted setting.
     * @return string  One or two <script> tags, newline-joined. Never a
     *     self-closing tag: a self-closed <script> swallows the rest of the
     *     head in every browser, which is why this always writes an explicit
     *     closing tag rather than leaning on a templating engine's attribute
     *     renderer to decide.
     */
    public static function buildTag(string $domain = '', bool $companion = false, string $api = ''): string
    {
        $html = '<script defer';

        if ($domain !== '' && self::isValidDomain($domain)) {
            $html .= ' data-domain="'.htmlspecialchars($domain, ENT_QUOTES, 'UTF-8').'"';
        }

        if ($api !== '') {
            $html .= ' data-api="'.htmlspecialchars($api, ENT_QUOTES, 'UTF-8').'"';
        }

        $html .= ' src="'.self::SCRIPT_URL.'"></script>';

        if ($companion) {
            // No data-domain on the companion: the tracker resolves the
            // domain once, from the core script, and a second copy is a
            // second thing to keep in step.
            $html .= "\n".'<script defer src="'.self::COMPANION_URL.'"></script>';
        }

        return $html;
    }
}
