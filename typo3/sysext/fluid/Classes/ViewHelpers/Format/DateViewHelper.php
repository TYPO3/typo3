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
 * A simple date format view helper.
 *
 * @package
 * @subpackage
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_Format_DateViewHelper extends Tx_Fluid_Core_AbstractViewHelper {
	/**
	 * Render the supplied DateTime object as a formatted date.
	 *
	 * @param DateTime $value The DateTime object to format
	 * @param string $format The date format in date() syntax
	 * @return string Formatted date
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render(DateTime $value, $format = 'Y-m-d H:i') {
		$formattedDate = '';
		if ($value != NULL) {
			$formattedDate = $value->format($format);
		}
		return $formattedDate;
	}
}


?>