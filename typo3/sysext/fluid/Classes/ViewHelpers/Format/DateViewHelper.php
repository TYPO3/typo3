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
 * @version $Id: DateViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 */

/**
 * Formats a DateTime object.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.date date="{dateObject}" />
 * </code>
 * 
 * Output:
 * 1980-12-13
 * (depending on the current date)
 * 
 * <code title="Custom date format">
 * <f:format.date date="{dateObject}" format="H:i" />
 * </code>
 * 
 * Output:
 * 01:23
 * (depending on the current time)
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: DateViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Format_DateViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * Render the supplied DateTime object as a formatted date.
	 *
	 * @param DateTime $date The DateTime object to format
	 * @param string $format Format String which is taken to format the Date/Time
	 * @return string Formatted date
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($date, $format = 'Y-m-d') {
		if ($date === NULL || !($date instanceof DateTime)) {
			return '';
		}
		return $date->format($format);
	}
}
?>