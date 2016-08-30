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

use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Localization helper which should be used to fetch localized labels.
 *
 * @api
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
     * Key of the language to use
     *
     * @var string
     */
    protected static $languageKey = 'default';

    /**
     * Pointer to alternative fall-back language to use
     *
     * @var array
     */
    protected static $alternativeLanguageKeys = [];

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected static $configurationManager = null;

    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param string $extensionName The name of the extension
     * @param array $arguments the arguments of the extension, being passed over to vsprintf
     * @return string|NULL The value from LOCAL_LANG or NULL if no translation was found.
     * @api
     * @todo : If vsprintf gets a malformed string, it returns FALSE! Should we throw an exception there?
     */
    public static function translate($key, $extensionName, $arguments = null)
    {
        $value = null;
        if (GeneralUtility::isFirstPartOfStr($key, 'LLL:')) {
            $value = self::translateFileReference($key);
        } else {
            self::initializeLocalization($extensionName);
            // The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
            if (!empty(self::$LOCAL_LANG[$extensionName][self::$languageKey][$key][0]['target'])
                || isset(self::$LOCAL_LANG_UNSET[$extensionName][self::$languageKey][$key])
            ) {
                // Local language translation for key exists
                $value = self::$LOCAL_LANG[$extensionName][self::$languageKey][$key][0]['target'];
            } elseif (!empty(self::$alternativeLanguageKeys)) {
                $languages = array_reverse(self::$alternativeLanguageKeys);
                foreach ($languages as $language) {
                    if (!empty(self::$LOCAL_LANG[$extensionName][$language][$key][0]['target'])
                        || isset(self::$LOCAL_LANG_UNSET[$extensionName][$language][$key])
                    ) {
                        // Alternative language translation for key exists
                        $value = self::$LOCAL_LANG[$extensionName][$language][$key][0]['target'];
                        break;
                    }
                }
            }
            if ($value === null && (!empty(self::$LOCAL_LANG[$extensionName]['default'][$key][0]['target'])
                || isset(self::$LOCAL_LANG_UNSET[$extensionName]['default'][$key]))
            ) {
                // Default language translation for key exists
                // No charset conversion because default is English and thereby ASCII
                $value = self::$LOCAL_LANG[$extensionName]['default'][$key][0]['target'];
            }
        }
        if (is_array($arguments) && $value !== null) {
            return vsprintf($value, $arguments);
        } else {
            return $value;
        }
    }

    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param string $key The language key including the path to a custom locallang file ("LLL:path:key").
     * @return string The value from LOCAL_LANG or NULL if no translation was found.
     * @see language::sL()
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::sL()
     */
    protected static function translateFileReference($key)
    {
        if (TYPO3_MODE === 'FE') {
            $value = self::getTypoScriptFrontendController()->sL($key);
            return $value !== false ? $value : null;
        } elseif (is_object($GLOBALS['LANG'])) {
            $value = self::getLanguageService()->sL($key);
            return $value !== '' ? $value : null;
        } else {
            return $key;
        }
    }

    /**
     * Loads local-language values by looking for a "locallang.xlf" (or "locallang.xml") file in the plugin resources directory and if found includes it.
     * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.xlf" file.
     *
     * @param string $extensionName
     * @return void
     */
    protected static function initializeLocalization($extensionName)
    {
        if (isset(self::$LOCAL_LANG[$extensionName])) {
            return;
        }
        $locallangPathAndFilename = 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/' . self::$locallangPath . 'locallang.xlf';
        self::setLanguageKeys();
        $renderCharset = TYPO3_MODE === 'FE' ? self::getTypoScriptFrontendController()->renderCharset : self::getLanguageService()->charSet;

        /** @var $languageFactory LocalizationFactory */
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);

        self::$LOCAL_LANG[$extensionName] = $languageFactory->getParsedData($locallangPathAndFilename, self::$languageKey, $renderCharset);
        foreach (self::$alternativeLanguageKeys as $language) {
            $tempLL = $languageFactory->getParsedData($locallangPathAndFilename, $language, $renderCharset);
            if (self::$languageKey !== 'default' && isset($tempLL[$language])) {
                self::$LOCAL_LANG[$extensionName][$language] = $tempLL[$language];
            }
        }
        self::loadTypoScriptLabels($extensionName);
    }

    /**
     * Sets the currently active language/language_alt keys.
     * Default values are "default" for language key and "" for language_alt key.
     *
     * @return void
     */
    protected static function setLanguageKeys()
    {
        self::$languageKey = 'default';
        self::$alternativeLanguageKeys = [];
        if (TYPO3_MODE === 'FE') {
            if (isset(self::getTypoScriptFrontendController()->config['config']['language'])) {
                self::$languageKey = self::getTypoScriptFrontendController()->config['config']['language'];
                if (isset(self::getTypoScriptFrontendController()->config['config']['language_alt'])) {
                    self::$alternativeLanguageKeys[] = self::getTypoScriptFrontendController()->config['config']['language_alt'];
                } else {
                    /** @var $locales \TYPO3\CMS\Core\Localization\Locales */
                    $locales = GeneralUtility::makeInstance(Locales::class);
                    if (in_array(self::$languageKey, $locales->getLocales())) {
                        foreach ($locales->getLocaleDependencies(self::$languageKey) as $language) {
                            self::$alternativeLanguageKeys[] = $language;
                        }
                    }
                }
            }
        } elseif (!empty($GLOBALS['BE_USER']->uc['lang'])) {
            self::$languageKey = $GLOBALS['BE_USER']->uc['lang'];
        } elseif (!empty(self::getLanguageService()->lang)) {
            self::$languageKey = self::getLanguageService()->lang;
        }
    }

    /**
     * Overwrites labels that are set via TypoScript.
     * TS locallang labels have to be configured like:
     * plugin.tx_myextension._LOCAL_LANG.languageKey.key = value
     *
     * @param string $extensionName
     * @return void
     */
    protected static function loadTypoScriptLabels($extensionName)
    {
        $configurationManager = static::getConfigurationManager();
        $frameworkConfiguration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName);
        if (!is_array($frameworkConfiguration['_LOCAL_LANG'])) {
            return;
        }
        self::$LOCAL_LANG_UNSET[$extensionName] = [];
        foreach ($frameworkConfiguration['_LOCAL_LANG'] as $languageKey => $labels) {
            if (!(is_array($labels) && isset(self::$LOCAL_LANG[$extensionName][$languageKey]))) {
                continue;
            }
            foreach ($labels as $labelKey => $labelValue) {
                if (is_string($labelValue)) {
                    self::$LOCAL_LANG[$extensionName][$languageKey][$labelKey][0]['target'] = $labelValue;
                    if ($labelValue === '') {
                        self::$LOCAL_LANG_UNSET[$extensionName][$languageKey][$labelKey] = '';
                    }
                } elseif (is_array($labelValue)) {
                    $labelValue = self::flattenTypoScriptLabelArray($labelValue, $labelKey);
                    foreach ($labelValue as $key => $value) {
                        self::$LOCAL_LANG[$extensionName][$languageKey][$key][0]['target'] = $value;
                        if ($value === '') {
                            self::$LOCAL_LANG_UNSET[$extensionName][$languageKey][$key] = '';
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
                $key = $parentKey . '.' . $key;
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
        if (!is_null(static::$configurationManager)) {
            return static::$configurationManager;
        }
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
        static::$configurationManager = $configurationManager;
        return $configurationManager;
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected static function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
