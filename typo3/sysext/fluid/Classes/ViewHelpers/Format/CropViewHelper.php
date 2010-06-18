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
 * @version $Id: CropViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
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
 * WARNING: This tag doesn't care about multibyte charsets currently.
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: CropViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Format_CropViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @var	tslib_cObj
	 */
	protected $contentObject;

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
	 * @param boolean $respectBoundaries If TRUE and division is in the middle of a word, the remains of that word is removed. This is currently ignored in backend mode!
	 * @return string cropped text
	 * @author Andreas Pattynama <andreas.pattynama@innocube.ch>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Felix Oertel <oertel@networkteam.com>
	 */
	public function render($maxCharacters, $append = '...', $respectWordBoundaries = TRUE) {
		$stringToTruncate = $this->renderChildren();
		if (TYPO3_MODE === 'BE') {
			if (strlen($stringToTruncate) > $maxCharacters) {
				$stringToTruncate = substr($stringToTruncate, 0, ($maxCharacters - strlen($append))) . $append;
			}
			return $stringToTruncate;
		} else {
			if (strip_tags($stringToTruncate) != $stringToTruncate) {
				return $this->contentObject->cropHTML($stringToTruncate, $maxCharacters . '|' . $append . '|' . $respectWordBoundaries);
			} else {
				return $this->contentObject->crop($stringToTruncate, $maxCharacters . '|' . $append . '|' . $respectWordBoundaries);
			}
		}
	}
}


?>
