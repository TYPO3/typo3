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
 */

/**
 * Use this view helper to crop the text between its opening and closing tags.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.crop maxCharacters="10">This is some very long text</f:format.crop>
 * </code>
 * <output>
 * This is...
 * </output>
 *
 * <code title="Custom suffix">
 * <f:format.crop maxCharacters="17" append="&nbsp;[more]">This is some very long text</f:format.crop>
 * </code>
 * <output>
 * This is some&nbsp;[more]
 * </output>
 *
 * <code title="Don't respect word boundaries">
 * <f:format.crop maxCharacters="10" respectWordBoundaries="false">This is some very long text</f:format.crop>
 * </code>
 * <output>
 * This is so...
 * </output>
 *
 * <code title="Don't respect HTML tags">
 * <f:format.crop maxCharacters="28" respectWordBoundaries="false" respectHtml="false">This is some text with <strong>HTML</strong> tags</f:format.crop>
 * </code>
 * <output>
 * This is some text with <stro
 * </output>
 *
 * <code title="Inline notation">
 * {someLongText -> f:format.crop(maxCharacters: 10)}
 * </code>
 * <output>
 * someLongText cropped after 10 characters...
 * (depending on the value of {someLongText})
 * </output>
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Fluid_ViewHelpers_Format_CropViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @var	tslib_cObj
	 */
	protected $contentObject;

	/**
	 * @var	t3lib_fe contains a backup of the current $GLOBALS['TSFE'] if used in BE mode
	 */
	protected $tsfeBackup;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->contentObject = $this->configurationManager->getContentObject();
	}

	/**
	 * Render the cropped text
	 *
	 * @param integer $maxCharacters Place where to truncate the string
	 * @param string $append What to append, if truncation happened
	 * @param boolean $respectBoundaries If TRUE and division is in the middle of a word, the remains of that word is removed.
	 * @param boolean $respectHtml If TRUE the cropped string will respect HTML tags and entities. Technically that means, that cropHTML() is called rather than crop()
	 * @return string cropped text
	 * @author Andreas Pattynama <andreas.pattynama@innocube.ch>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Felix Oertel <oertel@networkteam.com>
	 */
	public function render($maxCharacters, $append = '...', $respectWordBoundaries = TRUE, $respectHtml = TRUE) {
		$stringToTruncate = $this->renderChildren();
		if (TYPO3_MODE === 'BE') {
			$this->simulateFrontendEnvironment();
		}

		if ($respectHtml) {
			$content = $this->contentObject->cropHTML($stringToTruncate, $maxCharacters . '|' . $append . '|' . $respectWordBoundaries);
		} else {
			$content = $this->contentObject->crop($stringToTruncate, $maxCharacters . '|' . $append . '|' . $respectWordBoundaries);
		}

		if (TYPO3_MODE === 'BE') {
			$this->resetFrontendEnvironment();
		}
		return $content;
	}

	/**
	 * Sets the global variables $GLOBALS['TSFE']->csConvObj and $GLOBALS['TSFE']->renderCharset in Backend mode
	 * This somewhat hacky work around is currently needed because the crop() and cropHTML() functions of tslib_cObj rely on those variables to be set
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function simulateFrontendEnvironment() {
		$this->tsfeBackup = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : NULL;
		$GLOBALS['TSFE'] = new stdClass();

			// preparing csConvObj
		if (!is_object($GLOBALS['TSFE']->csConvObj)) {
			if (is_object($GLOBALS['LANG'])) {
				$GLOBALS['TSFE']->csConvObj = $GLOBALS['LANG']->csConvObj;
			} else {
				$GLOBALS['TSFE']->csConvObj = t3lib_div::makeInstance('t3lib_cs');
			}
		}

			// preparing renderCharset
		if (!is_object($GLOBALS['TSFE']->renderCharset)) {
			if (is_object($GLOBALS['LANG'])) {
				$GLOBALS['TSFE']->renderCharset = $GLOBALS['LANG']->charSet;
			} else {
				$GLOBALS['TSFE']->renderCharset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];
			}
		}
	}

	/**
	 * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see simulateFrontendEnvironment()
	 */
	protected function resetFrontendEnvironment() {
		$GLOBALS['TSFE'] = $this->tsfeBackup;
	}
}


?>
