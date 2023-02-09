<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Localization;

/**
 * A representation of
 *    language key (based on ISO 639-1 / ISO 639-2)
 *   - the optional four-letter script code that can follow the language code according to the Unicode ISO 15924 Registry (e.g. Hans in zh_Hans)
 *   - region / country (based on ISO 3166-1)
 * separated with a "-".
 *
 * This conforms to IETF - RFC 5646 (see https://datatracker.ietf.org/doc/rfc5646/) in a simplified form.
 */
class Locale implements \Stringable
{
    protected string $locale;
    protected string $languageCode;
    protected ?string $languageScript = null;
    protected ?string $countryCode = null;
    protected ?string $codeSet = null;
    // see https://wiki.archlinux.org/title/locale#Generating_locales
    protected ?string $charsetModifier = null;

    // taken from https://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code
    protected const RIGHT_TO_LEFT_LANGUAGE_CODES = [
        'ar', // Arabic
        'arc', // Aramaic
        'arz', // Egyptian Arabic
        'ckb', // Kurdish (Sorani)
        'dv', // Divehi
        'fa', // Persian
        'ha', // Hausa
        'he', // Hebrew
        'khw', // Khowar
        'ks', // Kashmiri
        'ps', // Pashto
        'sd', // Sindhi
        'ur', // Urdu
        'uz-AF', // Uzbeki Afghanistan
        'yi', // Yiddish
    ];

    /**
     * List of language dependencies for an actual language. This setting is used for local variants of a language
     * that depend on their "main" language, like Brazilian Portuguese or Canadian French.
     *
     * @var array<int, string>
     */
    protected array $dependencies = [];

    public function __construct(
        string $locale = 'en',
        array $dependencies = []
    ) {
        $locale = $this->normalize($locale);
        if (str_contains($locale, '@')) {
            [$locale, $this->charsetModifier] = explode('@', $locale);
        }
        if (str_contains($locale, '.')) {
            [$locale, $this->codeSet] = explode('.', $locale);
        }
        if (strtolower($locale) === 'c') {
            $this->codeSet = 'C';
            $locale = 'en';
        } elseif (strtolower($locale) === 'posix') {
            $this->codeSet = 'POSIX';
            $locale = 'en';
        }
        if (str_contains($locale, '-')) {
            [$this->languageCode, $tail] = explode('-', $locale, 2);
            if (str_contains($tail, '-')) {
                [$this->languageScript, $this->countryCode] = explode('-', $tail);
            } elseif (strlen($tail) === 4) {
                $this->languageScript = $tail;
            } else {
                $this->countryCode = $tail ?: null;
            }
            $this->languageCode = strtolower($this->languageCode);
            $this->languageScript = $this->languageScript ? ucfirst(strtolower($this->languageScript)) : null;
            $this->countryCode = $this->countryCode ? strtoupper($this->countryCode) : null;
        } else {
            $this->languageCode = strtolower($locale);
        }

        $this->locale = $this->languageCode . ($this->languageScript ? '-' . $this->languageScript : '') . ($this->countryCode ? '-' . $this->countryCode : '');
        $this->dependencies = array_map(fn ($dep) => $this->normalize($dep), $dependencies);
    }

    public function getName(): string
    {
        return $this->locale;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function isRightToLeftLanguageDirection(): bool
    {
        return in_array($this->languageCode, self::RIGHT_TO_LEFT_LANGUAGE_CODES, true) || in_array($this->locale, self::RIGHT_TO_LEFT_LANGUAGE_CODES, true);
    }

    public function getLanguageScriptCode(): ?string
    {
        return $this->languageScript;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * Return the locale as ISO/IEC 15897 format, including a possible POSIX charset
     * "cs_CZ.UTF-8"
     * see https://en.wikipedia.org/wiki/ISO/IEC_15897
     * https://en.wikipedia.org/wiki/Locale_(computer_software)#POSIX_platforms
     * @internal
     */
    public function posixFormatted(): string
    {
        $charsetModifier = $this->charsetModifier ? '@' . $this->charsetModifier : '';
        if ($this->codeSet === 'C' || $this->codeSet === 'POSIX') {
            return $this->codeSet . $charsetModifier;
        }
        $formatted = $this->languageCode;
        if ($this->countryCode) {
            $formatted .= '_' . $this->countryCode;
        }
        if ($this->codeSet) {
            $formatted .= '.' . $this->codeSet;
        }
        return $formatted . $charsetModifier;
    }

    /**
     * @internal
     */
    public function getPosixCodeSet(): ?string
    {
        return $this->codeSet;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    protected function normalize(string $locale): string
    {
        if ($locale === 'default') {
            return 'en';
        }
        if (str_contains($locale, '_')) {
            $locale = str_replace('_', '-', $locale);
        }

        return $locale;
    }

    public function __toString(): string
    {
        return $this->locale;
    }
}
