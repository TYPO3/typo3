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
 * @version $Id: PrintfViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 */

/**
 * A view helper for formatting values with printf. Either supply an array for
 * the arguments or a single value.
 * See http://www.php.net/manual/en/function.sprintf.php
 *
 * = Examples =
 * 
 * <code title="Scientific notation">
 * <f:format.printf format="%.3e" arguments="{number : 362525200}" />
 * </code>
 *
 * Output:
 * 3.625e+8
 * 
 * <code title="Argument swapping">
 * <f:format.printf format="%2$s is great, TYPO%1$d too. Yes, TYPO%1$d is great and so is %2$s!" arguments="{0: 3,1: 'Kasper'}" />
 * </code>
 * 
 * Output:
 * Kasper is great, TYPO3 too. Yes, TYPO3 is great and so is Kasper!
 *
 * <code title="Single argument">
 * <f:format.printf format="We love %s" arguments="{1:'TYPO3'}" />
 * </code>
 * 
 * Output:
 * We love TYPO3
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: PrintfViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Format_PrintfViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * Format the arguments with the given printf format string.
	 *
	 * @param string $format The printf format string
	 * @param array $arguments The arguments for vsprintf
	 * @return string The formatted value
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render($format, array $arguments) {
		return vsprintf($format, $arguments);
	}
}
?>