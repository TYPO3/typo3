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
 * A view helper for formatting values with printf. Either supply an array for
 * the arguments or a single value.
 *
 * Example:
 * <f:format.printf format="%.3e" arguments="{number : 362525200}" />
 *
 * Output:
 * 3.625e+8
 *
 * @see http://www.php.net/manual/en/function.sprintf.php
 *
 * @package
 * @subpackage
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_Format_PrintfViewHelper extends Tx_Fluid_Core_AbstractViewHelper {
	/**
	 * Format the arguments with the given printf format string.
	 *
	 * @param string $format The printf format string
	 * @param array|string $arguments The arguments for printf
	 * @return string The formatted value
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render($format, $arguments) {
		return vsprintf($format, $arguments);
	}
}


?>