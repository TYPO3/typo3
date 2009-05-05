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
 * "ELSE" -> only has an effect inside of "IF". See If-ViewHelper for documentation.
 * @see Tx_Fluid_ViewHelpers_IfViewHelper
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_ElseViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * @return string the rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render() {
		return $this->renderChildren();
	}
}

?>
