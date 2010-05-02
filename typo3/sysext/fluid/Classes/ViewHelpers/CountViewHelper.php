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
 * Counts the number of elements of a given property
 *
 * @package TYPO3
 * @subpackage Fluid
 * @version $Id: CountViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
 */
class Tx_Fluid_ViewHelpers_CountViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Counts the items of a given property.
	 *
	 * @param array $subject The array or ObjectStorage to iterated over
	 * @return string The bumber of elements
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 * @api
	 */
	public function render($subject) {
		return count($subject);
	}
}
?>