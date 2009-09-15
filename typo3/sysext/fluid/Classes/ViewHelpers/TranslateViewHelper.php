<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Translate a key from locallang. The files are loaded from the folder
 * "Resources/Private/Language/".
 *
 * @package TYPO3
 * @subpackage Fluid
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_TranslateViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @var string
	 */
	protected $locallangPath = 'Resources/Private/Language/';

	/**
	 * @var string
	 */
	protected $locallangPathAndFilename = NULL;

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
	 * The extension name for which this instance of the view helper was called.
	 *
	 * @var string
	 */
	protected $extensionName = '';

	/**
	 * Is called before render() to initialize localization.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function initialize() {
		parent::initialize();
		$this->extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		if (!isset(self::$LOCAL_LANG[$this->extensionName])) {
			$this->initializeLocalization();
		}
	}

	/**
	 * Translate a given key or use the tag body as default.
	 *
	 * @param string $key The locallang key
	 * @param boolean $htmlEscape TRUE if the result should be htmlescaped
	 * @return string The translated key or tag body if key doesn't exist
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($key, $htmlEscape = TRUE) {
		if (t3lib_div::isFirstPartOfStr($key, 'LLL:')) {
			$value = $GLOBALS['TSFE']->sL($key);
		} else {
			$value = $this->translate($key);
		}
		if ($value === NULL) {
			$value = $this->renderChildren();
		} elseif ($htmlEscape) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}

	/**
	 * Loads local-language values by looking for a "locallang.php" (or "locallang.xml") file in the plugin resources directory and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function initializeLocalization() {
		$this->locallangPathAndFilename = t3lib_extMgm::extPath(t3lib_div::camelCaseToLowerCaseUnderscored($this->extensionName), $this->locallangPath . 'locallang.php');

		$this->setLanguageKeys();
		self::$LOCAL_LANG[$this->extensionName] = t3lib_div::readLLfile($this->locallangPathAndFilename, self::$languageKey, $GLOBALS['TSFE']->renderCharset);
		if (self::$alternativeLanguageKey === '') {
			$alternativeLocalLang = t3lib_div::readLLfile($this->locallangPathAndFilename, self::$alternativeLanguageKey);
			self::$LOCAL_LANG[$this->extensionName] = array_merge(self::$LOCAL_LANG[$this->extensionName], $alternativeLocalLang);
		}
		$this->loadTypoScriptLabels();
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
		if (isset($GLOBALS['TSFE']->config['config']['language'])) {
			self::$languageKey = $GLOBALS['TSFE']->config['config']['language'];
			if (isset($GLOBALS['TSFE']->config['config']['language_alt'])) {
				self::$alternativeLanguageKey = $GLOBALS['TSFE']->config['config']['language_alt'];
			}
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
	protected function loadTypoScriptLabels() {
		$configurationManager = Tx_Extbase_Dispatcher::getConfigurationManager();
		$settings = $configurationManager->getSettings($this->extensionName);
		if (!is_array($settings['_LOCAL_LANG'])) {
			return;
		}
		foreach ($settings['_LOCAL_LANG'] as $languageKey => $labels) {
			if (!is_array($labels)) {
				continue;
			}
			foreach($labels as $labelKey => $labelValue) {
				if (is_string($labelValue)) {
					self::$LOCAL_LANG[$this->extensionName][$languageKey][$labelKey] = $labelValue;
						// For labels coming from the TypoScript (database) the charset is assumed to be "forceCharset" and if that is not set, assumed to be that of the individual system languages
					self::$LOCAL_LANG_charset[$this->extensionName][$languageKey][$labelKey] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : $GLOBALS['TSFE']->csConvObj->charSetArray[$languageKey];
				}
			}
		}
	}

	/**
	 * Returns the localized label of the LOCAL_LANG key, $key
	 * Notice that for debugging purposes prefixes for the output values can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix
	 *
	 * @param string $key The key from the LOCAL_LANG array for which to return the value.
	 * @return string The value from LOCAL_LANG or NULL if no translation was found.
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function translate($key) {
		// The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
		if (isset(self::$LOCAL_LANG[$this->extensionName][self::$languageKey][$key])) {
			$value = self::$LOCAL_LANG[$this->extensionName][self::$languageKey][$key];
			if (isset(self::$LOCAL_LANG_charset[$this->extensionName][self::$languageKey][$key])) {
				$value = $GLOBALS['TSFE']->csConv($value, self::$LOCAL_LANG_charset[$this->extensionName][self::$languageKey][$key]);
			}
			return $value;
		}

		if (self::$alternativeLanguageKey !== '' && isset(self::$LOCAL_LANG[$this->extensionName][self::$alternativeLanguageKey][$key])) {
			$value = self::$LOCAL_LANG[$this->extensionName][self::$alternativeLanguageKey][$key];
			if (isset(self::$LOCAL_LANG_charset[$this->extensionName][self::$alternativeLanguageKey][$key])) {
				$value = $GLOBALS['TSFE']->csConv($value, self::$LOCAL_LANG_charset[$this->extensionName][self::$alternativeLanguageKey][$key]);
			}
		}

		if (isset(self::$LOCAL_LANG[$this->extensionName]['default'][$key])) {
			return self::$LOCAL_LANG[$this->extensionName]['default'][$key]; // No charset conversion because default is english and thereby ASCII
		}

		return NULL;
	}
}

?>