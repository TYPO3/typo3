<?php
namespace TYPO3\CMS\Extbase\Utility;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Localization helper which should be used to fetch localized labels.
 */
class LocalizationUtility
{
    /**
     * @var string
     */
    protected static $locallangPath = 'Resources/Private/Language/';

    /**
     * Local Language content
     *
     * @var array
     */
    protected static $LOCAL_LANG = [];

    /**
     * Contains those LL keys, which have been set to (empty) in TypoScript.
     * This is necessary, as we cannot distinguish between a nonexisting
     * translation and a label that has been cleared by TS.
     * In both cases ['key'][0]['target'] is "".
     *
     * @var array
     */
    protected static $LOCAL_LANG_UNSET = [];

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected static $configurationManager;

    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param string|null $extensionName The name of the extension
     * @param array $arguments The arguments of the extension, being passed over to vsprintf
     * @param string $languageKey The language key or null for using the current language from the system
     * @param string[] $alternativeLanguageKeys The alternative language keys if no translation was found. If null and we are in the frontend, then the language_alt from TypoScript setup will be used
     * @return string|null The value from LOCAL_LANG or null if no translation was found.
     */
    public static function translate($key, $extensionName = null, $arguments = null, string $languageKey = null, array $alternativeLanguageKeys = null)
    {
        if ((string)$key === '') {
            // Early return guard: returns null if the key was empty, because the key may be a dynamic value
            // (from for example Fluid). Returning null allows null coalescing to a default value when that happens.
            return null;
        }
        $value = null;
        if (GeneralUtility::isFirstPartOfStr($key, 'LLL:')) {
            $keyParts = explode(':', $key);
            unset($keyParts[0]);
            $key = array_pop($keyParts);
            $languageFilePath = implode(':', $keyParts);
        } else {
            if (empty($extensionName)) {
                throw new \InvalidArgumentException(
                    'Parameter $extensionName cannot be empty if a fully-qualified key is not specified.',
                    1498144052
                );
            }
            $languageFilePath = static::getLanguageFilePath($extensionName);
        }
        $languageKeys = static::getLanguageKeys();
        if ($languageKey === null) {
            $languageKey = $languageKeys['languageKey'];
        }
        if (empty($alternativeLanguageKeys)) {
            $alternativeLanguageKeys = $languageKeys['alternativeLanguageKeys'];
        }
        static::initializeLocalization($languageFilePath, $languageKey, $alternativeLanguageKeys, $extensionName);

        // The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
        if (!empty(self::$LOCAL_LANG[$languageFilePath][$languageKey][$key][0]['target'])
            || isset(self::$LOCAL_LANG_UNSET[$languageFilePath][$languageKey][$key])
        ) {
            // Local language translation for key exists
            $value = self::$LOCAL_LANG[$languageFilePath][$languageKey][$key][0]['target'];
        } elseif (!empty($alternativeLanguageKeys)) {
            $languages = array_reverse($alternativeLanguageKeys);
            foreach ($languages as $language) {
                if (!empty(self::$LOCAL_LANG[$languageFilePath][$language][$key][0]['target'])
                    || isset(self::$LOCAL_LANG_UNSET[$languageFilePath][$language][$key])
                ) {
                    // Alternative language translation for key exists
                    $value = self::$LOCAL_LANG[$languageFilePath][$language][$key][0]['target'];
                    break;
                }
            }
        }
        if ($value === null && (!empty(self::$LOCAL_LANG[$languageFilePath]['default'][$key][0]['target'])
            || isset(self::$LOCAL_LANG_UNSET[$languageFilePath]['default'][$key]))
        ) {
            // Default language translation for key exists
            // No charset conversion because default is English and thereby ASCII
            $value = self::$LOCAL_LANG[$languageFilePath]['default'][$key][0]['target'];
        }

        if (is_array($arguments) && $value !== null) {
            // This unrolls arguments from $arguments - instead of calling vsprintf which receives arguments as an array.
            // The reason is that only sprintf() will return an error message if the number of arguments does not match
            // the number of placeholders in the format string. Whereas, vsprintf would silently return nothing.
            return sprintf($value, ...array_values($arguments)) ?: sprintf('Error: could not translate key "%s" with value "%s" and %d argument(s)!', $key, $value, count($arguments));
        }
        return $value;
    }

    /**
     * Loads local-language values by looking for a "locallang.xlf" (or "locallang.xml") file in the plugin resources directory and if found includes it.
     * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.xlf" file.
     *
     * @param string $languageFilePath
     * @param string $languageKey
     * @param string[] $alternativeLanguageKeys
     * @param string $extensionName
     */
    protected static function initializeLocalization(string $languageFilePath, string $languageKey, array $alternativeLanguageKeys, string $extensionName = null)
    {
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);

        if (empty(self::$LOCAL_LANG[$languageFilePath][$languageKey])) {
            $parsedData = $languageFactory->getParsedData($languageFilePath, $languageKey);
            foreach ($parsedData as $tempLanguageKey => $data) {
                if (!empty($data)) {
                    self::$LOCAL_LANG[$languageFilePath][$tempLanguageKey] = $data;
                }
            }
        }
        if ($languageKey !== 'default') {
            foreach ($alternativeLanguageKeys as $alternativeLanguageKey) {
                if (empty(self::$LOCAL_LANG[$languageFilePath][$alternativeLanguageKey])) {
                    $tempLL = $languageFactory->getParsedData($languageFilePath, $alternativeLanguageKey);
                    if (isset($tempLL[$alternativeLanguageKey])) {
                        self::$LOCAL_LANG[$languageFilePath][$alternativeLanguageKey] = $tempLL[$alternativeLanguageKey];
                    }
                }
            }
        }
        if (!empty($extensionName)) {
            static::loadTypoScriptLabels($extensionName, $languageFilePath);
        }
    }

    /**
     * Returns the default path and filename for an extension
     *
     * @param string $extensionName
     * @return string
     */
    protected static function getLanguageFilePath(string $extensionName): string
    {
        return 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/' . self::$locallangPath . 'locallang.xlf';
    }

    /**
     * Sets the currently active language/language_alt keys.
     * Default values are "default" for language key and an empty array for language_alt key.
     *
     * @return array
     */
    protected static function getLanguageKeys(): array
    {
        $languageKeys = [
            'languageKey' => 'default',
            'alternativeLanguageKeys' => [],
        ];
        if (TYPO3_MODE === 'FE') {
            $tsfe = static::getTypoScriptFrontendController();
            $siteLanguage = self::getCurrentSiteLanguage();

            // Get values from site language, which takes precedence over TypoScript settings
            if ($siteLanguage instanceof SiteLanguage) {
                $languageKeys['languageKey'] = $siteLanguage->getTypo3Language();
            } elseif (isset($tsfe->config['config']['language'])) {
                $languageKeys['languageKey'] = $tsfe->config['config']['language'];
                if (isset($tsfe->config['config']['language_alt'])) {
                    $languageKeys['alternativeLanguageKeys'][] = $tsfe->config['config']['language_alt'];
                }
            }

            if (empty($languageKeys['alternativeLanguageKeys'])) {
                $locales = GeneralUtility::makeInstance(Locales::class);
                if (in_array($languageKeys['languageKey'], $locales->getLocales())) {
                    foreach ($locales->getLocaleDependencies($languageKeys['languageKey']) as $language) {
                        $languageKeys['alternativeLanguageKeys'][] = $language;
                    }
                }
            }
        } elseif (!empty($GLOBALS['BE_USER']->uc['lang'])) {
            $languageKeys['languageKey'] = $GLOBALS['BE_USER']->uc['lang'];
        } elseif (!empty(static::getLanguageService()->lang)) {
            $languageKeys['languageKey'] = static::getLanguageService()->lang;
        }
        return $languageKeys;
    }

    /**
     * Overwrites labels that are set via TypoScript.
     * TS locallang labels have to be configured like:
     * plugin.tx_myextension._LOCAL_LANG.languageKey.key = value
     *
     * @param string $extensionName
     * @param string $languageFilePath
     */
    protected static function loadTypoScriptLabels($extensionName, $languageFilePath)
    {
        $configurationManager = static::getConfigurationManager();
        $frameworkConfiguration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName);
        if (!is_array($frameworkConfiguration['_LOCAL_LANG'] ?? false)) {
            return;
        }
        self::$LOCAL_LANG_UNSET[$languageFilePath] = [];
        foreach ($frameworkConfiguration['_LOCAL_LANG'] as $languageKey => $labels) {
            if (!is_array($labels)) {
                continue;
            }
            foreach ($labels as $labelKey => $labelValue) {
                if (is_string($labelValue)) {
                    self::$LOCAL_LANG[$languageFilePath][$languageKey][$labelKey][0]['target'] = $labelValue;
                    if ($labelValue === '') {
                        self::$LOCAL_LANG_UNSET[$languageFilePath][$languageKey][$labelKey] = '';
                    }
                } elseif (is_array($labelValue)) {
                    $labelValue = self::flattenTypoScriptLabelArray($labelValue, $labelKey);
                    foreach ($labelValue as $key => $value) {
                        self::$LOCAL_LANG[$languageFilePath][$languageKey][$key][0]['target'] = $value;
                        if ($value === '') {
                            self::$LOCAL_LANG_UNSET[$languageFilePath][$languageKey][$key] = '';
                        }
                    }
                }
            }
        }
    }

    /**
     * Flatten TypoScript label array; converting a hierarchical array into a flat
     * array with the keys separated by dots.
     *
     * Example Input:  array('k1' => array('subkey1' => 'val1'))
     * Example Output: array('k1.subkey1' => 'val1')
     *
     * @param array $labelValues Hierarchical array of labels
     * @param string $parentKey the name of the parent key in the recursion; is only needed for recursion.
     * @return array flattened array of labels.
     */
    protected static function flattenTypoScriptLabelArray(array $labelValues, $parentKey = '')
    {
        $result = [];
        foreach ($labelValues as $key => $labelValue) {
            if (!empty($parentKey)) {
                if ($key === '_typoScriptNodeValue') {
                    $key = $parentKey;
                } else {
                    $key = $parentKey . '.' . $key;
                }
            }
            if (is_array($labelValue)) {
                $labelValue = self::flattenTypoScriptLabelArray($labelValue, $key);
                $result = array_merge($result, $labelValue);
            } else {
                $result[$key] = $labelValue;
            }
        }
        return $result;
    }

    /**
     * Returns instance of the configuration manager
     *
     * @return \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected static function getConfigurationManager()
    {
        if (static::$configurationManager !== null) {
            return static::$configurationManager;
        }
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
        static::$configurationManager = $configurationManager;
        return $configurationManager;
    }

    /**
     * Returns the currently configured "site language" if a site is configured (= resolved)
     * in the current request.
     *
     * @return SiteLanguage|null
     */
    protected static function getCurrentSiteLanguage(): ?SiteLanguage
    {
        if ($GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            return $GLOBALS['TYPO3_REQUEST']->getAttribute('language', null);
        }
        return null;
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected static function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
