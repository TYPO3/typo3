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
 *
 * @package
 * @subpackage
 * @version $Id: DebugViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
 */
class Tx_Fluid_ViewHelpers_DebugViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Wrapper for TYPO3s famous debug()
	 *
	 * @param string $title
	 * @return string the altered string. 
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($title = NULL) {
		ob_start();
		t3lib_div::debug($this->renderChildren(), $title);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
}


?>