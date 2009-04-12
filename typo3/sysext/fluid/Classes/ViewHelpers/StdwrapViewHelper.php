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
 * This class is a stdWrap view helper for the Fluid templating engine.
 *
 * Example: <f:stdwrap path="path.to.stdWrapConfig">..</f:stdwrap>
 *
 * @package TYPO3
 * @subpackage Fluid
 * @version $Id$
 */
class Tx_Fluid_ViewHelpers_StdwrapViewHelper extends Tx_Fluid_Core_AbstractViewHelper {
	/**
	 * Processes the children with the stdWrap TypoScript configuration
	 * specified in the TypoScript setup path $path.
	 *
	 * @param string the TypoScript setup path of the stdWrap TypoScript configuration
	 * @return string the processed content
	 * @author Niels Pardon <mail@niels-pardon.de>
	 */
	public function render($path) {
		$data = $GLOBALS['TSFE']->tmpl->setup;

		$pathSegments = t3lib_div::trimExplode('.', $path);

		foreach ($pathSegments as $segment) {
			if (!array_key_exists($segment . '.', $data)) {
				$data = array();
				break;
			}

			$data =& $data[$segment . '.'];
		}

		return $GLOBALS['TSFE']->cObj->stdWrap(
			$this->renderChildren(), $data
		);
	}
}
?>