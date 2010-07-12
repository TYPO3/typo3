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
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 */

/**
 * Use this view helper to crop the text between its opening and closing tags.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.crop maxCharacters="10">This is some very long text</f:format.crop>
 * </code>
 *
 * Output:
 * This is...
 *
 * <code title="Custom suffix">
 * <f:format.crop maxCharacters="17" append="&nbsp;[more]">This is some very long text</f:format.crop>
 * </code>
 *
 * Output:
 * This is some&nbsp;[more]
 *
 * <code title="Don't respect word boundaries">
 * <f:format.crop maxCharacters="10" respectWordBoundaries="false">This is some very long text</f:format.crop>
 * </code>
 *
 * Output:
 * This is so...
 *
 * <code title="Don't respect HTML tags">
 * <f:format.crop maxCharacters="28" respectWordBoundaries="false" respectHtml="false">This is some text with <strong>HTML</strong> tags</f:format.crop>
 * </code>
 *
 * Output:
 * This is some text with <stro
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Format_CropViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @var	tslib_cObj
	 */
	protected $contentObject;

	/**
	 * @var	t3lib_fe
	 */
	protected $tsfeBackup;

	/**
	 * Constructor. Used to create an instance of tslib_cObj used by the render() method.
	 *
	 * @param tslib_cObj $contentObject injector for tslib_cObj (optional)
	 * @return void
	 */
	public function __construct($contentObject = NULL) {
		$this->contentObject = $contentObject !== NULL ? $contentObject : t3lib_div::makeInstance('tslib_cObj');
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
			$this->setUpBackendEnvironment();
		}

		if ($respectHtml) {
			return $this->contentObject->cropHTML($stringToTruncate, $maxCharacters . '|' . $append . '|' . $respectWordBoundaries);
		} else {
			return $this->contentObject->crop($stringToTruncate, $maxCharacters . '|' . $append . '|' . $respectWordBoundaries);
		}

		if (TYPO3_MODE === 'BE') {
			$this->resetBackendEnvironment();
		}
	}

	/**
	 * Sets the global variables $GLOBALS['TSFE']->csConvObj and $GLOBALS['TSFE']->renderCharset in Backend mode
	 * This somewhat hacky work around is currently needed because the crop() and cropHTML() functions of tslib_cObj rely on those variables to be set
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUpBackendEnvironment() {
		$this->tsfeBackup = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : NULL;

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
	 * Resets $GLOBALS['TSFE'] if it was previously changed by setUpBackendEnvironment()
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see setUpBackendEnvironment()
	 */
	protected function resetBackendEnvironment() {
		if (isset($this->tsfeBackup)) {
			$GLOBALS['TSFE'] = $this->tsfeBackup;
		}
	}
}


?>
