<?php

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

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Locales. Used to define TYPO3- system languages
 * When adding new keys, remember to:
 * - Update 'setup' extension labels (sysext/setup/Resources/Private/Language/locallang.xlf)
 * That's it!
 */
class Locales implements SingletonInterface
{
    /**
     * Supported TYPO3 languages with locales
     *
     * @var array
     */
    protected $languages = [
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
        'zh' => 'Chinese (Trad)',
    ];

    /**
     * Reversed mapping for backward compatibility codes
     *
     * @var array
     */
    protected $isoReverseMapping = [
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
     * Dependencies for locales
     * This is a reverse mapping for the built-in languages within $this->languages that contain 5-letter codes.
     *
     * @var array
     */
    protected $localeDependencies = [
        'pt_BR' => ['pt'],
        'fr_CA' => ['fr'],
    ];

    public function __construct()
    {
        // Allow user-defined locales
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user'] ?? [] as $locale => $name) {
            if (!isset($this->languages[$locale])) {
                $this->languages[$locale] = $name;
            }
            // Initializes the locale dependencies with TYPO3 supported locales
            if (strlen($locale) === 5) {
                $this->localeDependencies[$locale] = [substr($locale, 0, 2)];
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

    /**
     * Returns the locales.
     *
     * @return array
     */
    public function getLocales()
    {
        return array_keys($this->languages);
    }

    /**
     * Returns the supported languages indexed by their corresponding locale.
     *
     * @return array
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * Returns the mapping between TYPO3 (old) language codes and ISO codes.
     *
     * @return array
     */
    public function getIsoMapping()
    {
        return array_flip($this->isoReverseMapping);
    }

    /**
     * Returns the dependencies of a given locale, if any.
     *
     * @param string $locale
     * @return array
     */
    public function getLocaleDependencies($locale)
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
        return $dependencies;
    }

    /**
     * Converts the language codes that we get from the client (usually HTTP_ACCEPT_LANGUAGE)
     * into a TYPO3-readable language code
     *
     * @param string $languageCodesList List of language codes. something like 'de,en-us;q=0.9,de-de;q=0.7,es-cl;q=0.6,en;q=0.4,es;q=0.3,zh;q=0.1'
     * @return string A preferred language that TYPO3 supports, or "default" if none found
     */
    public function getPreferredClientLanguage($languageCodesList)
    {
        $allLanguageCodesFromLocales = ['en' => 'default'];
        foreach ($this->isoReverseMapping as $isoLang => $typo3Lang) {
            $isoLang = str_replace('_', '-', $isoLang);
            $allLanguageCodesFromLocales[$isoLang] = $typo3Lang;
        }
        foreach ($this->getLocales() as $locale) {
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
            [$preferredLanguage, ] = explode('-', $preferredLanguage);
            if (isset($allLanguageCodesFromLocales[$preferredLanguage])) {
                $selectedLanguage = $allLanguageCodesFromLocales[$preferredLanguage];
                break;
            }
        }
        if (!$selectedLanguage || $selectedLanguage === 'en') {
            $selectedLanguage = 'default';
        }
        return $selectedLanguage;
    }

    /**
     * Setting locale based on a SiteLanguage's defined locale.
     * Used for frontend rendering, previously set within TSFE->settingLocale
     *
     * @param SiteLanguage $siteLanguage
     * @return bool whether the locale was found on the system (and could be set properly) or not
     */
    public static function setSystemLocaleFromSiteLanguage(SiteLanguage $siteLanguage): bool
    {
        $locale = $siteLanguage->getLocale();
        // No locale was given, so return false;
        if (!$locale) {
            return false;
        }
        $availableLocales = GeneralUtility::trimExplode(',', $locale, true);
        // If LC_NUMERIC is set e.g. to 'de_DE' PHP parses float values locale-aware resulting in strings with comma
        // as decimal point which causes problems with value conversions - so we set all locale types except LC_NUMERIC
        // @see https://bugs.php.net/bug.php?id=53711
        $locale = setlocale(LC_COLLATE, ...$availableLocales);
        if ($locale) {
            // As str_* methods are locale aware and turkish has no upper case I
            // Class autoloading and other checks depending on case changing break with turkish locale LC_CTYPE
            // @see http://bugs.php.net/bug.php?id=35050
            if (strpos($locale, 'tr') !== 0) {
                setlocale(LC_CTYPE, ...$availableLocales);
            }
            setlocale(LC_MONETARY, ...$availableLocales);
            setlocale(LC_TIME, ...$availableLocales);
        } else {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(__CLASS__)
                ->error('Locale "' . htmlspecialchars($siteLanguage->getLocale()) . '" not found.');
            return false;
        }
        return true;
    }
}
