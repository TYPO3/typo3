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
class Tx_Fluid_ViewHelpers_TranslateViewHelper extends Tx_Fluid_Core_AbstractViewHelper {
	protected static $initialized = FALSE;

	/**
	 * @var string
	 */
	protected $languagePath = 'Resources/Private/Language/';

	/**
	 * Local Language content
	 *
	 * @var string
	 **/
	protected $LOCAL_LANG = array();

	/**
	 * Local Language content charset for individual labels (overriding)
	 *
	 * @var string
	 **/
	protected $LOCAL_LANG_charset = array();

	/**
	 * Key of the language to use
	 *
	 * @var string
	 **/
	protected $languageKey = 'default';

	/**
	 * Pointer to alternative fall-back language to use
	 *
	 * @var string
	 **/
	protected $alternativeLanguageKey = '';
	
	/**
	 * Translate a given key or use the tag body as default.
	 *
	 * @param string $key The locallang key
	 * @param boolean $htmlEscape TRUE if the result should be htmlescaped
	 * @return string The translated key or tag body if key doesn't exist
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render($key, $htmlEscape = FALSE) {
		if (!self::$initialized) {
			$this->initializeLocalization();
		}
		$defaultValue = $this->renderChildren();
		$translation = $this->translate($key, $defaultValue, $htmlEscape);
		return (is_string($translation) && !empty($translation)) ? $translation : '';
	}
	
	/**
	 * Loads local-language values by looking for a "locallang.php" file in the plugin class directory ($this->scriptRelPath) and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 *
	 * @return	void
	 */
	protected function initializeLocalization() {
		$extensionName = $this->variableContainer->get('view')->getRequest()->getExtensionName();
		$languageFilePath = t3lib_extMgm::extPath(strtolower($extensionName)) . $this->languagePath . 'locallang.php';

		if ($GLOBALS['TSFE']->config['config']['language'])	{
			$this->languageKey = $GLOBALS['TSFE']->config['config']['language'];
			if ($GLOBALS['TSFE']->config['config']['language_alt'])	{
				$this->alternativeLanguageKey = $GLOBALS['TSFE']->config['config']['language_alt'];
			}
		}

		$this->LOCAL_LANG = t3lib_div::readLLfile($languageFilePath, $this->languageKey, $GLOBALS['TSFE']->renderCharset);
		if ($this->alternativeLanguageKey)	{
			$tempLOCAL_LANG = t3lib_div::readLLfile($languageFilePath, $this->alternativeLanguageKey);
			$this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array(), $tempLOCAL_LANG);
		}

		$configurationManager = t3lib_div::makeInstance('Tx_ExtBase_Configuration_Manager');
		$settings = $configurationManager->getSettings($extensionName);
		if (is_array($settings['_LOCAL_LANG'])) {
			foreach ($settings['_LOCAL_LANG'] as $k => $lA) {
				if (is_array($lA)) {
					foreach($lA as $llK => $llV) {
						if (!is_array($llV)) {
							$this->LOCAL_LANG[$k][$llK] = $llV;
								// For labels coming from the TypoScript (database) the charset is assumed to be "forceCharset" and if that is not set, assumed to be that of the individual system languages
							$this->LOCAL_LANG_charset[$k][$llK] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : $GLOBALS['TSFE']->csConvObj->charSetArray[$k];
						}
					}
				}
			}
		}
	}
	
	/**
	 * Returns the localized label of the LOCAL_LANG key, $key
	 * Notice that for debugging purposes prefixes for the output values can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix
	 *
	 * @param	string		The key from the LOCAL_LANG array for which to return the value.
	 * @param	string		Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
	 * @param	boolean		If true, the output label is passed through htmlspecialchars()
	 * @return	string		The value from LOCAL_LANG.
	 */
	function translate($key, $default = '', $filterTranslation = FALSE)	{
		// The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
		if (isset($this->LOCAL_LANG[$this->languageKey][$key]))	{
			$translation = $GLOBALS['TSFE']->csConv($this->LOCAL_LANG[$this->languageKey][$key], $this->LOCAL_LANG_charset[$this->languageKey][$key]);
		} elseif ($this->alternativeLanguageKey && isset($this->LOCAL_LANG[$this->alternativeLanguageKey][$key]))	{
			$translation = $GLOBALS['TSFE']->csConv($this->LOCAL_LANG[$this->alternativeLanguageKey][$key], $this->LOCAL_LANG_charset[$this->alternativeLanguageKey][$key]);
		} elseif (isset($this->LOCAL_LANG['default'][$key]))	{
			$translation = $this->LOCAL_LANG['default'][$key];	// No charset conversion because default is english and thereby ASCII
		} else {
			$translation = $default;
		}
		return $filterTranslation === TRUE ? htmlspecialchars($translation) : $translation;
	}
}


?>