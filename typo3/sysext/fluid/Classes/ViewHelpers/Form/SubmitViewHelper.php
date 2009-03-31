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
 * @package
 * @subpackage
 * @version $Id: SubmitViewHelper.php 1962 2009-03-03 12:10:41Z k-fish $
 */
/**
 * [Enter description here...]
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: SubmitViewHelper.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Form_SubmitViewHelper extends Tx_Fluid_Core_TagBasedViewHelper {

	/**
	 * Initialize the arguments.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Renders the submit button.
	 *
	 * @return string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render() {
		$out = '<button type="submit"' . $this->renderTagAttributes() . '>';
		$out .= $this->renderChildren();
		$out .= '</button>';

		return $out;
	}
}



?>
