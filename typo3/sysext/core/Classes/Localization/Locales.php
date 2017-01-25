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
 * Defining backend system languages
 * When adding new keys, remember to:
 * - Add character encoding for lang. key in \TYPO3\CMS\Core\Charset\CharsetConverter
 * (default for new languages is "utf-8")
 * - Add mappings for language in \TYPO3\CMS\Core\Charset\CharsetConverter
 * (TYPO3/ISO, language/script, script/charset)
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
        'zh_HK' => 'hk'
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
     *
     * @return void
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
}
