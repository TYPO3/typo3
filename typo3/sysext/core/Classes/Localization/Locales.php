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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Defines all available TYPO3 system languages, as they differ from actual ISO 639-1 codes.
 * User-defined system languages can be added to $GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user']
 *
 * These system languages are used for determining the proper language labels of XLF files.
 */
class Locales implements SingletonInterface
{
    /**
     * Supported TYPO3 languages with locales
     *
     * @var array<non-empty-string, non-empty-string>
     */
    protected array $languages = [
        'default' => 'English',
        'af' => 'Afrikaans',
        'ar' => 'Arabic',
        'bs' => 'Bosnian',
        'bg' => 'Bulgarian',
        'ca' => 'Catalan',
        'ch' => 'Chinese (Simple)',
        'cs' => 'Czech',
        'cy' => 'Welsh',
        'da' => 'Danish',
        'de' => 'German',
        'el' => 'Greek',
        'eo' => 'Esperanto',
        'es' => 'Spanish',
        'et' => 'Estonian',
        'eu' => 'Basque',
        'fa' => 'Persian',
        'fi' => 'Finnish',
        'fo' => 'Faroese',
        'fr' => 'French',
        'fr_CA' => 'French (Canada)',
        'gl' => 'Galician',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hr' => 'Croatian',
        'hu' => 'Hungarian',
        'is' => 'Icelandic',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'ka' => 'Georgian',
        'kl' => 'Greenlandic',
        'km' => 'Khmer',
        'ko' => 'Korean',
        'lb' => 'Luxembourgish',
        'lt' => 'Lithuanian',
        'lv' => 'Latvian',
        'mi' => 'Maori',
        'mk' => 'Macedonian',
        'ms' => 'Malay',
        'nl' => 'Dutch',
        'no' => 'Norwegian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'pt_BR' => 'Brazilian Portuguese',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'rw' => 'Kinyarwanda',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sn' => 'Shona (Bantu)',
        'sq' => 'Albanian',
        'sr' => 'Serbian',
        'sv' => 'Swedish',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'vi' => 'Vietnamese',
        'zh' => 'Chinese (Traditional)',
        'zh_CN' => 'Chinese (Simplified)',
        'zh_HK' => 'Chinese (Simplified Hong Kong)',
        'zh_Hans_CN' => 'Chinese (Simplified Han)',
    ];

    /**
     * Reversed mapping for backward compatibility codes
     *
     * Key => real ISO code
     * value => the value that TYPO3 understands (which is wrong, obviously)
     *
     * Example:
     * "da" => official ISO 639-1 code
     * "dk" (wrong)" => the shortcut that TYPO3 uses for danish within the system for labels.
     *
     * @var array<non-empty-string, non-empty-string>
     * @deprecated will be removed in TYPO3 v13.0. backwards-compatibility is not needed anymore.
     */
    protected array $isoReverseMapping = [
        'bs' => 'ba', // Bosnian
        'cs' => 'cz', // Czech
        'da' => 'dk', // Danish
        'el' => 'gr', // Greek
        'fr_CA' => 'qc', // French (Canada)
        'gl' => 'ga', // Galician
        'ja' => 'jp', // Japanese
        'ka' => 'ge', // Georgian
        'kl' => 'gl', // Greenlandic
        'ko' => 'kr', // Korean
        'ms' => 'my', // Malay
        'pt_BR' => 'br', // Portuguese (Brazil)
        'sl' => 'si', // Slovenian
        'sv' => 'se', // Swedish
        'uk' => 'ua', // Ukrainian
        'vi' => 'vn', // Vietnamese
        'zh' => 'hk', // Chinese (China)
        'zh_CN' => 'ch', // Chinese (Simplified)
        'zh_HK' => 'hk', // Chinese (Simplified Hong Kong)
        'zh_Hans_CN' => 'ch', // Chinese (Simplified Han)
    ];

    /**
     * Dependencies for locales.
     * By default, locales with a country/region suffix such as "de_AT" will automatically have the "de"
     * locale as fallback. This way TYPO3 only needs to know about the actual "base" language, however
     * also allows to use country-specific languages.
     * However, when a specific locale such as "lb" has a dependency to a different "de" suffix, this should
     * is defined here.
     * With
     *   $GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['dependencies']
     * it is possible to extend the dependency list.
     *
     * Example:
     * If "lb" is chosen, but no label was found, a fallback to the label in "de" is used.
     */
    protected array $localeDependencies = [
        'lb' => ['de'],
    ];

    public function __construct()
    {
        // Allow user-defined locales
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user'] ?? [] as $locale => $name) {
            if (!is_string($locale) || $locale === '') {
                continue;
            }
            if (!isset($this->languages[$locale])) {
                $this->languages[$locale] = $name;
            }
        }
        // Merge user-provided locale dependencies
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['dependencies'] ?? null)) {
            $this->localeDependencies = array_replace_recursive(
                $this->localeDependencies,
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['dependencies']
            );
        }
    }

    public function createLocale(string $localeKey, array $alternativeDependencies = null): Locale
    {
        if (strpos($localeKey, '.')) {
            [$sanitizedLocaleKey] = explode('.', $localeKey);
        }
        // Find the requested language in this list based on the $languageKey
        // Language is found. Configure it:
        if ($localeKey === 'en' || $this->isValidLanguageKey($sanitizedLocaleKey ?? $localeKey)) {
            return new Locale($localeKey, $alternativeDependencies ?? $this->getLocaleDependencies($sanitizedLocaleKey ?? $localeKey));
        }
        return new Locale();
    }

    /**
     * Returns the locales.
     * @return array<int, non-empty-string>
     */
    public function getLocales(): array
    {
        return array_keys($this->languages);
    }

    public function isValidLanguageKey(string $locale): bool
    {
        // "en" implicitly equals "default", so this is OK
        if ($locale === 'en' || $locale === 'default') {
            return true;
        }
        if (!isset($this->languages[$locale])) {
            // the given locale is not found in the current locales, let us see if
            // the base language (iso-639-1) is in the list of supported locales.
            if (str_contains($locale, '_')) {
                [$baseIsoCodeLanguageKey] = explode('_', $locale);
                return $this->isValidLanguageKey($baseIsoCodeLanguageKey);
            }
            if (str_contains($locale, '-')) {
                [$baseIsoCodeLanguageKey] = explode('-', $locale);
                return $this->isValidLanguageKey($baseIsoCodeLanguageKey);
            }
            return false;
        }
        return true;
    }

    /**
     * Returns the supported languages indexed by their corresponding locale.
     * @return array<non-empty-string, non-empty-string>
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * Returns a list of all ISO codes / TYPO3 languages that have active language packs, but also includes "default".
     * @return array<int, non-empty-string>
     */
    public function getActiveLanguages(): array
    {
        return array_merge(
            ['default'],
            array_filter(array_values($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages'] ?? []))
        );
    }

    public function isLanguageKeyAvailable(string $languageKey): bool
    {
        return in_array($languageKey, $this->getActiveLanguages()) || is_dir(Environment::getLabelsPath() . '/' . $languageKey);
    }

    /**
     * Returns the mapping between TYPO3 (old) language codes and ISO codes.
     *
     * @return array<non-empty-string, non-empty-string>
     * @deprecated will be removed in TYPO3 v13.0.
     */
    public function getIsoMapping(): array
    {
        trigger_error('Locales->getIsoMapping() will be removed in TYPO3 v13.0. Migrate to real locales instead.', E_USER_DEPRECATED);
        return array_flip($this->isoReverseMapping);
    }

    /**
     * Returns the dependencies of a given locale, if any.
     *
     * @return array<int, non-empty-string>
     */
    public function getLocaleDependencies(string $locale): array
    {
        $dependencies = [];
        if (isset($this->localeDependencies[$locale])) {
            $dependencies = $this->localeDependencies[$locale];
            // Search for dependencies recursively
            $localeDependencies = $dependencies;
            foreach ($localeDependencies as $dependency) {
                if (isset($this->localeDependencies[$dependency])) {
                    $dependencies = array_merge($dependencies, $this->getLocaleDependencies($dependency));
                }
            }
        }
        // Use automatic dependency resolving.
        // "de_AT" automatically has a dependency on "de".
        // but only do this if the actual "de_AT" does not have a custom dependency already defined in
        // $this->localeDependencies
        if ($dependencies === [] && str_contains($locale, '_')) {
            [$languageIsoCode] = explode('_', $locale);
            // "en" = "default" is always implicitly the default fallback dependency
            if ($languageIsoCode !== 'en') {
                $dependencies[] = $languageIsoCode;
                $dependencies = array_merge($dependencies, $this->getLocaleDependencies($languageIsoCode));
            }
        } elseif ($dependencies === [] && str_contains($locale, '-')) {
            [$languageIsoCode] = explode('-', $locale);
            // "en" = "default" is always implicitly the default fallback dependency
            if ($languageIsoCode !== 'en') {
                $dependencies[] = $languageIsoCode;
                $dependencies = array_merge($dependencies, $this->getLocaleDependencies($languageIsoCode));
            }
        }
        return array_unique($dependencies);
    }

    /**
     * Converts the language codes that we get from the client (usually HTTP_ACCEPT_LANGUAGE)
     * into a TYPO3-readable language code
     *
     * @param string $languageCodesList List of language codes. something like 'de,en-us;q=0.9,de-de;q=0.7,es-cl;q=0.6,en;q=0.4,es;q=0.3,zh;q=0.1'
     * @return non-empty-string A preferred language that TYPO3 supports, or "default" if none found
     */
    public function getPreferredClientLanguage(string $languageCodesList): string
    {
        $allLanguageCodesFromLocales = ['en' => 'default'];
        foreach ($this->languages as $locale => $localeTitle) {
            $locale = str_replace('_', '-', $locale);
            $allLanguageCodesFromLocales[$locale] = $locale;
        }
        $selectedLanguage = 'default';
        $preferredLanguages = GeneralUtility::trimExplode(',', $languageCodesList);
        // Order the preferred languages after they key
        $sortedPreferredLanguages = [];
        foreach ($preferredLanguages as $preferredLanguage) {
            $quality = 1.0;
            if (str_contains($preferredLanguage, ';q=')) {
                [$preferredLanguage, $quality] = explode(';q=', $preferredLanguage);
            }
            $sortedPreferredLanguages[$preferredLanguage] = $quality;
        }
        // Loop through the languages, with the highest priority first
        arsort($sortedPreferredLanguages, SORT_NUMERIC);
        foreach ($sortedPreferredLanguages as $preferredLanguage => $quality) {
            if (isset($allLanguageCodesFromLocales[$preferredLanguage])) {
                $selectedLanguage = $allLanguageCodesFromLocales[$preferredLanguage];
                break;
            }
            // Strip the country code from the end
            [$preferredLanguage] = explode('-', $preferredLanguage);
            if (isset($allLanguageCodesFromLocales[$preferredLanguage])) {
                $selectedLanguage = $allLanguageCodesFromLocales[$preferredLanguage];
                break;
            }
        }
        if (!$selectedLanguage || $selectedLanguage === 'en') {
            $selectedLanguage = 'default';
        }
        return str_replace('-', '_', $selectedLanguage);
    }

    /**
     * Setting locale based on a SiteLanguage's defined locale.
     * Used for frontend rendering, previously set within TSFE->settingLocale
     *
     * @return bool whether the locale was found on the system (and could be set properly) or not
     */
    public static function setSystemLocaleFromSiteLanguage(SiteLanguage $siteLanguage): bool
    {
        $locale = $siteLanguage->getLocale()->posixFormatted();
        if ($locale === '') {
            return false;
        }
        return self::setLocale($locale, $locale);
    }

    /**
     * Internal method, which calls itself again, in order to avoid multiple logging issues.
     * The main reason for this method is that it calls itself again by trying again to set
     * the locale. Due to sensible defaults, people used the locale "de_AT.utf-8" with the POSIX platform
     * (see https://en.wikipedia.org/wiki/Locale_(computer_software)#POSIX_platforms) in their site configuration,
     * even though the target system has "de_AT" and not "de_AT.UTF-8" defined.
     * "setLocale()" is now called again without the POSIX platform suffix and is checked again if the locale
     * is then available, and then logs the failed information.
     */
    protected static function setLocale(string $locale, string $localeStringForTrigger): bool
    {
        $incomingLocale = $locale;
        $availableLocales = GeneralUtility::trimExplode(',', $locale, true);
        // If LC_NUMERIC is set e.g. to 'de_DE' PHP parses float values locale-aware resulting in strings with comma
        // as decimal point which causes problems with value conversions - so we set all locale types except LC_NUMERIC
        // @see https://bugs.php.net/bug.php?id=53711
        $locale = setlocale(LC_COLLATE, ...$availableLocales);
        if ($locale) {
            // As str_* methods are locale aware and turkish has no upper case I
            // Class autoloading and other checks depending on case changing break with turkish locale LC_CTYPE
            // @see http://bugs.php.net/bug.php?id=35050
            if (!str_starts_with($locale, 'tr')) {
                setlocale(LC_CTYPE, ...$availableLocales);
            }
            setlocale(LC_MONETARY, ...$availableLocales);
            setlocale(LC_TIME, ...$availableLocales);
        } else {
            // Retry again without the "utf-8" POSIX platform suffix if this is given.
            if (str_contains($incomingLocale, '.')) {
                [$localeWithoutModifier] = explode('.', $incomingLocale);
                return self::setLocale($localeWithoutModifier, $incomingLocale);
            }
            if ($localeStringForTrigger === $locale) {
                GeneralUtility::makeInstance(LogManager::class)
                    ->getLogger(__CLASS__)
                    ->error('Locale "' . htmlspecialchars($localeStringForTrigger) . '" not found.');
            } else {
                GeneralUtility::makeInstance(LogManager::class)
                    ->getLogger(__CLASS__)
                    ->error('Locale "' . htmlspecialchars($localeStringForTrigger) . '" and  "' . htmlspecialchars($incomingLocale) . '" not found.');
            }
            return false;
        }
        return true;
    }
}
