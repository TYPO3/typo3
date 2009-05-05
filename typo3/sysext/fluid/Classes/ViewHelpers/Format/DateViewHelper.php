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
 * <f:format.date>{dateObject}</f:format.date>
 * </code>
 * 
 * Output:
 * 1980-12-13
 * (depending on the current date)
 * 
 * <code title="Custom date format">
 * <f:format.date format="H:i">{dateObject}</f:format.date>
 * </code>
 * 
 * Output:
 * 01:23
 * (depending on the current time)
 *
 * <code title="strtotime string">
 * <f:format.date format="d.m.Y - H:i:s">+1 week 2 days 4 hours 2 seconds</f:format.date>
 * </code>
 * 
 * Output:
 * 13.12.1980 - 21:03:42 
 * (depending on the current time, see http://www.php.net/manual/en/function.strtotime.php)
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
	 * @param string $format Format String which is taken to format the Date/Time
	 * @return string Formatted date
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($format = 'Y-m-d') {
		$stringToFormat = $this->renderChildren();
		if ($stringToFormat instanceof DateTime) {
			$date = $stringToFormat;
		} else {
			if ($stringToFormat === NULL) {
				return '';
			}
			try {
				$date = new DateTime($stringToFormat);
			} catch (Exception $e) {
				// @todo re-throw exception
				return $e->getMessage();
			}
		}
		return $date->format($format);
	}
}
?>