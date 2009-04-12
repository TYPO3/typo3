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
 * This class is a parseFunc view helper for the Fluid templating engine.
 *
 * Example: <f:parsefunc path="lib.parseFunc_RTE">...</f:parsefunc>
 *
 * @package TYPO3
 * @subpackage Fluid
 * @version $Id$
 */
class Tx_Fluid_ViewHelpers_ParsefuncViewHelper extends Tx_Fluid_Core_AbstractViewHelper {
	/**
	 * Processes the children with the parseFunc TypoScript configuration
	 * specified in the TypoScript setup path $path.
	 *
	 * @param string $path the TypoScript setup path with the parseFunc configuration
	 * @return string the parsed content
	 * @author Niels Pardon <mail@niels-pardon.de>
	 */
	public function render($path) {
		return $GLOBALS['TSFE']->cObj->parseFunc(
			$this->renderChildren(), array(), '< ' . $path
		);
	}
}
?>