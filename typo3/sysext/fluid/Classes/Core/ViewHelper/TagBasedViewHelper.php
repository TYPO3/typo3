<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @deprecated. Extend Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper instead!
 *
 * @api
 */
abstract class Tx_Fluid_Core_ViewHelper_TagBasedViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper {

	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		t3lib_div::deprecationLog('the ViewHelper "' . get_class($this) . '" extends "Tx_Fluid_Core_ViewHelper_TagBasedViewHelper". This is deprecated since TYPO3 4.5. Please extend the class "Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper"');
		parent::__construct();
	}
}
?>