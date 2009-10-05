<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
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
 * @package Extbase
 * @subpackage Utility
 * @version $ID:$
 * @api
 */
class Tx_Extbase_Utility_Localization {

	/**
	 * @var string
	 */
	protected static $locallangPath = 'Resources/Private/Language/';

	/**
	 * Local Language content
	 *
	 * @var string
	 **/
	protected static $LOCAL_LANG = array();

	/**
	 * Local Language content charset for individual labels (overriding)
	 *
	 * @var string
	 **/
	protected static $LOCAL_LANG_charset = array();

	/**
	 * Key of the language to use
	 *
	 * @var string
	 **/
	protected static $languageKey = 'default';

	/**
	 * Pointer to alternative fall-back language to use
	 *
	 * @var string
	 **/
	protected static $alternativeLanguageKey = '';

	/**
	 * Returns the localized label of the LOCAL_LANG key, $key.
	 *
	 * @param string $key The key from the LOCAL_LANG array for which to return the value.
	 * @return string The value from LOCAL_LANG or NULL if no translation was found.
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 * @api
	 */
	public function translate($key, $extensionName) {
		if (t3lib_div::isFirstPartOfStr($key, 'LLL:')) {
			return $GLOBALS['LANG']->sL($key);
		}

		self::initializeLocalization($extensionName);

		// The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
		if (isset(self::$LOCAL_LANG[$extensionName][self::$languageKey][$key])) {
			$value = self::$LOCAL_LANG[$extensionName][self::$languageKey][$key];
			if (isset(self::$LOCAL_LANG_charset[$extensionName][self::$languageKey][$key])) {
				$value = self::convertCharset($value, self::$LOCAL_LANG_charset[$extensionName][self::$languageKey][$key]);
			}
			return $value;
		}

		if (self::$alternativeLanguageKey !== '' && isset(self::$LOCAL_LANG[$extensionName][self::$alternativeLanguageKey][$key])) {
			$value = self::$LOCAL_LANG[$extensionName][self::$alternativeLanguageKey][$key];
			if (isset(self::$LOCAL_LANG_charset[$extensionName][self::$alternativeLanguageKey][$key])) {
				$value = self::convertCharset($value, self::$LOCAL_LANG_charset[$extensionName][self::$alternativeLanguageKey][$key]);
			}
		}

		if (isset(self::$LOCAL_LANG[$extensionName]['default'][$key])) {
			return self::$LOCAL_LANG[$extensionName]['default'][$key]; // No charset conversion because default is english and thereby ASCII
		}

		return NULL;
	}

	/**
	 * Loads local-language values by looking for a "locallang.php" (or "locallang.xml") file in the plugin resources directory and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected static function initializeLocalization($extensionName) {
		if (isset(self::$LOCAL_LANG[$extensionName])) {
			return;
		}
		$locallangPathAndFilename = t3lib_extMgm::extPath(t3lib_div::camelCaseToLowerCaseUnderscored($extensionName), self::$locallangPath . 'locallang.php');

		self::setLanguageKeys();

		$renderCharset = (TYPO3_MODE === 'FE' ? $GLOBALS['TSFE']->renderCharset : $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
		self::$LOCAL_LANG[$extensionName] = t3lib_div::readLLfile($locallangPathAndFilename, self::$languageKey, $renderCharset);
		if (self::$alternativeLanguageKey !== '') {
			$alternativeLocalLang = t3lib_div::readLLfile($locallangPathAndFilename, self::$alternativeLanguageKey);
			self::$LOCAL_LANG[$extensionName] = array_merge(self::$LOCAL_LANG[$extensionName], $alternativeLocalLang);
		}
		self::loadTypoScriptLabels($extensionName);
	}

	/**
	 * Sets the currently active language/language_alt keys.
	 * Default values are "default" for language key and "" for language_alt key.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setLanguageKeys() {
		self::$languageKey = 'default';
		self::$alternativeLanguageKey = '';
		if (TYPO3_MODE === 'FE') {
			if (isset($GLOBALS['TSFE']->config['config']['language'])) {
				self::$languageKey = $GLOBALS['TSFE']->config['config']['language'];
				if (isset($GLOBALS['TSFE']->config['config']['language_alt'])) {
					self::$alternativeLanguageKey = $GLOBALS['TSFE']->config['config']['language_alt'];
				}
			}
		} elseif (strlen($GLOBALS['BE_USER']->uc['lang']) > 0) {
			self::$languageKey = $GLOBALS['BE_USER']->uc['lang'];
		}
	}

	/**
	 * Overwrites labels that are set via typoscript.
	 * TS locallang labels have to be configured like:
	 * plugin.tx_myextension._LOCAL_LANG.languageKey.key = value
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function loadTypoScriptLabels($extensionName) {
		$extbaseFrameworkConfiguration = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
		if (!is_array($extbaseFrameworkConfiguration['_LOCAL_LANG'])) {
			return;
		}
		foreach ($extbaseFrameworkConfiguration['_LOCAL_LANG'] as $languageKey => $labels) {
			if (!is_array($labels)) {
				continue;
			}
			foreach($labels as $labelKey => $labelValue) {
				if (is_string($labelValue)) {
					self::$LOCAL_LANG[$extensionName][$languageKey][$labelKey] = $labelValue;
						// For labels coming from the TypoScript (database) the charset is assumed to be "forceCharset" and if that is not set, assumed to be that of the individual system languages
					self::$LOCAL_LANG_charset[$extensionName][$languageKey][$labelKey] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : $GLOBALS['LANG']->csConvObj->charSetArray[$languageKey];
				}
			}
		}
	}

	/**
	 * Converts a string to the specified charset
	 *
	 * @param string $value string to be converted
	 * @param string $charset charset
	 * @return string converted string
	 */
	protected function convertCharset($value, $charset) {
		if (TYPO3_MODE === 'FE') {
			return $GLOBALS['TSFE']->csConv($value, $charset);
		} else {
			$covertedValue = $GLOBALS['LANG']->csConvObj->conv($value, $GLOBALS['LANG']->csConvObj->parse_charset($charset), $GLOBALS['LANG']->renderCharset, 1);
			return $covertedValue !== NULL ? $covertedValue : $value;
		}
	}
}
?>