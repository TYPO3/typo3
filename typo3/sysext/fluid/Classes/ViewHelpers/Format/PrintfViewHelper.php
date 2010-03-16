<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
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
 * A view helper for formatting values with printf. Either supply an array for
 * the arguments or a single value.
 * See http://www.php.net/manual/en/function.sprintf.php
 *
 * = Examples =
 *
 * <code title="Scientific notation">
 * <f:format.printf arguments="{number : 362525200}">%.3e</f:format.printf>
 * </code>
 *
 * Output:
 * 3.625e+8
 *
 * <code title="Argument swapping">
 * <f:format.printf arguments="{0: 3,1: 'Kasper'}">%2$s is great, TYPO%1$d too. Yes, TYPO%1$d is great and so is %2$s!</f:format.printf>
 * </code>
 *
 * Output:
 * Kasper is great, TYPO3 too. Yes, TYPO3 is great and so is Kasper!
 *
 * <code title="Single argument">
 * <f:format.printf arguments="{1:'TYPO3'}">We love %s</f:format.printf>
 * </code>
 *
 * Output:
 * We love TYPO3
 *
 * @version $Id: PrintfViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage ViewHelpers\Format
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Format_PrintfViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Format the arguments with the given printf format string.
	 *
	 * @param array $arguments The arguments for vsprintf
	 * @return string The formatted value
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function render(array $arguments) {
		$format = $this->renderChildren();
		return vsprintf($format, $arguments);
	}
}
?>