<?php
namespace TYPO3\CMS\Extbase\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Localization helper which should be used to fetch localized labels.
 *
 * @api
 */
class LocalizationUtility {

	/**
	 * @var string
	 */
	static protected $locallangPath = 'Resources/Private/Language/';

	/**
	 * Local Language content
	 *
	 * @var array
	 */
	static protected $LOCAL_LANG = array();

	/**
	 * Contains those LL keys, which have been set to (empty) in TypoScript.
	 * This is necessary, as we cannot distinguish between a nonexisting
	 * translation and a label that has been cleared by TS.
	 * In both cases ['key'][0]['target'] is "".
	 *
	 * @var array
	 */
	static protected $LOCAL_LANG_UNSET = array();

	/**
	 * Local Language content charset for individual labels (overriding)
	 *
	 * @var array
	 */
	static protected $LOCAL_LANG_charset = array();

	/**
	 * Key of the language to use
	 *
	 * @var string
	 */
	static protected $languageKey = 'default';

	/**
	 * Pointer to alternative fall-back language to use
	 *
	 * @var array
	 */
	static protected $alternativeLanguageKeys = array();

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
	static public function translate($key, $extensionName, $arguments = NULL) {
		$value = NULL;
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($key, 'LLL:')) {
			$value = self::translateFileReference($key);
		} else {
			self::initializeLocalization($extensionName);
			// The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
			if (!empty(self::$LOCAL_LANG[$extensionName][self::$languageKey][$key][0]['target'])
				|| isset(self::$LOCAL_LANG_UNSET[$extensionName][self::$languageKey][$key])
			) {
				// Local language translation for key exists
				$value = self::$LOCAL_LANG[$extensionName][self::$languageKey][$key][0]['target'];
				if (!empty(self::$LOCAL_LANG_charset[$extensionName][self::$languageKey][$key])) {
					$value = self::convertCharset($value, self::$LOCAL_LANG_charset[$extensionName][self::$languageKey][$key]);
				}
			} elseif (count(self::$alternativeLanguageKeys)) {
				$languages = array_reverse(self::$alternativeLanguageKeys);
				foreach ($languages as $language) {
					if (!empty(self::$LOCAL_LANG[$extensionName][$language][$key][0]['target'])
						|| isset(self::$LOCAL_LANG_UNSET[$extensionName][$language][$key])
					) {
						// Alternative language translation for key exists
						$value = self::$LOCAL_LANG[$extensionName][$language][$key][0]['target'];
						if (!empty(self::$LOCAL_LANG_charset[$extensionName][$language][$key])) {
							$value = self::convertCharset($value, self::$LOCAL_LANG_charset[$extensionName][$language][$key]);
						}
						break;
					}
				}
			}
			if ($value === NULL && (!empty(self::$LOCAL_LANG[$extensionName]['default'][$key][0]['target'])
				|| isset(self::$LOCAL_LANG_UNSET[$extensionName]['default'][$key]))
			) {
				// Default language translation for key exists
				// No charset conversion because default is English and thereby ASCII
				$value = self::$LOCAL_LANG[$extensionName]['default'][$key][0]['target'];
			}
		}
		if (is_array($arguments) && $value !== NULL) {
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
	 * @see tslib_fe::sL()
	 */
	static protected function translateFileReference($key) {
		if (TYPO3_MODE === 'FE') {
			$value = $GLOBALS['TSFE']->sL($key);
			return $value !== FALSE ? $value : NULL;
		} elseif (is_object($GLOBALS['LANG'])) {
			$value = $GLOBALS['LANG']->sL($key);
			return $value !== '' ? $value : NULL;
		} else {
			return $key;
		}
	}

	/**
	 * Loads local-language values by looking for a "locallang.php" (or "locallang.xml") file in the plugin resources directory and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 *
	 * @param string $extensionName
	 * @return void
	 */
	static protected function initializeLocalization($extensionName) {
		if (isset(self::$LOCAL_LANG[$extensionName])) {
			return;
		}
		$locallangPathAndFilename = 'EXT:' . \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/' . self::$locallangPath . 'locallang.xml';
		self::setLanguageKeys();
		$renderCharset = TYPO3_MODE === 'FE' ? $GLOBALS['TSFE']->renderCharset : $GLOBALS['LANG']->charSet;
		self::$LOCAL_LANG[$extensionName] = \TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile($locallangPathAndFilename, self::$languageKey, $renderCharset);
		foreach (self::$alternativeLanguageKeys as $language) {
			$tempLL = \TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile($locallangPathAndFilename, $language, $renderCharset);
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
	protected function setLanguageKeys() {
		self::$languageKey = 'default';
		self::$alternativeLanguageKeys = array();
		if (TYPO3_MODE === 'FE') {
			if (isset($GLOBALS['TSFE']->config['config']['language'])) {
				self::$languageKey = $GLOBALS['TSFE']->config['config']['language'];
				if (isset($GLOBALS['TSFE']->config['config']['language_alt'])) {
					self::$alternativeLanguageKeys[] = $GLOBALS['TSFE']->config['config']['language_alt'];
				} else {
					/** @var $locales \TYPO3\CMS\Core\Localization\Locales */
					$locales = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Localization\\Locales');
					if (in_array(self::$languageKey, $locales->getLocales())) {
						foreach ($locales->getLocaleDependencies(self::$languageKey) as $language) {
							self::$alternativeLanguageKeys[] = $language;
						}
					}
				}
			}
		} elseif (strlen($GLOBALS['BE_USER']->uc['lang']) > 0) {
			self::$languageKey = $GLOBALS['BE_USER']->uc['lang'];
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
	static protected function loadTypoScriptLabels($extensionName) {
		$configurationManager = static::getConfigurationManager();
		$frameworkConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName);
		if (!is_array($frameworkConfiguration['_LOCAL_LANG'])) {
			return;
		}
		self::$LOCAL_LANG_UNSET[$extensionName] = array();
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
					if (is_object($GLOBALS['LANG'])) {
						self::$LOCAL_LANG_charset[$extensionName][$languageKey][$labelKey] = $GLOBALS['LANG']->csConvObj->charSetArray[$languageKey];
					} else {
						self::$LOCAL_LANG_charset[$extensionName][$languageKey][$labelKey] = $GLOBALS['TSFE']->csConvObj->charSetArray[$languageKey];
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
	protected function flattenTypoScriptLabelArray(array $labelValues, $parentKey = '') {
		$result = array();
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
	 * Converts a string from the specified character set to the current.
	 * The current charset is defined by the TYPO3 mode.
	 *
	 * @param string $value string to be converted
	 * @param string $charset The source charset
	 * @return string converted string
	 */
	protected function convertCharset($value, $charset) {
		if (TYPO3_MODE === 'FE') {
			return $GLOBALS['TSFE']->csConv($value, $charset);
		} else {
			$convertedValue = $GLOBALS['LANG']->csConvObj->conv($value, $GLOBALS['LANG']->csConvObj->parse_charset($charset), $GLOBALS['LANG']->charSet, 1);
			return $convertedValue !== NULL ? $convertedValue : $value;
		}
	}

	/**
	 * Returns instance of the configuration manager
	 *
	 * @return \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	static protected function getConfigurationManager() {
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		return $configurationManager;
	}
}

?>