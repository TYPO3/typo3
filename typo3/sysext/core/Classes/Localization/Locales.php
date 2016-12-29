<?php
namespace TYPO3\CMS\Core\Localization;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Locales.
 *
 * Used to define backend system languages
 * When adding new keys, remember to:
 * - Update 'setup' extension labels (sysext/setup/Resources/Private/Language/locallang.xlf)
 * That's it!
 */
class Locales implements \TYPO3\CMS\Core\SingletonInterface
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
        'ch' => 'Chinese (Simpl.)',
        'cs' => 'Czech',
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
        'ms' => 'Malay',
        'nl' => 'Dutch',
        'no' => 'Norwegian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'pt_BR' => 'Brazilian Portuguese',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sq' => 'Albanian',
        'sr' => 'Serbian',
        'sv' => 'Swedish',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'vi' => 'Vietnamese',
        'zh' => 'Chinese (Trad.)'
    ];

    /**
     * Reversed mapping with codes used by TYPO3 4.5 and below
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
        'zh_Hans_CN' => 'ch' // Chinese (Simplified Han)
    ];

    /**
     * Mapping with codes used by TYPO3 4.5 and below
     *
     * @var array
     */
    protected $isoMapping;

    /**
     * Dependencies for locales
     *
     * @var array
     */
    protected $localeDependencies;

    /**
     * Initializes the languages.
     */
    public static function initialize()
    {
        /** @var $instance Locales */
        $instance = GeneralUtility::makeInstance(self::class);
        $instance->isoMapping = array_flip($instance->isoReverseMapping);
        // Allow user-defined locales
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user'] as $locale => $name) {
                if (!isset($instance->languages[$locale])) {
                    $instance->languages[$locale] = $name;
                }
            }
        }
        // Initializes the locale dependencies with TYPO3 supported locales
        $instance->localeDependencies = [];
        foreach ($instance->languages as $locale => $name) {
            if (strlen($locale) === 5) {
                $instance->localeDependencies[$locale] = [substr($locale, 0, 2)];
            }
        }
        // Merge user-provided locale dependencies
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['dependencies']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['dependencies'])) {
            ArrayUtility::mergeRecursiveWithOverrule($instance->localeDependencies, $GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['dependencies']);
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
        return $this->isoMapping;
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
            if (strpos($preferredLanguage, ';q=') !== false) {
                list($preferredLanguage, $quality) = explode(';q=', $preferredLanguage);
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
            list($preferredLanguage, ) = explode('-', $preferredLanguage);
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
}
