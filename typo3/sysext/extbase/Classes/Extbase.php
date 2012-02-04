<?php
/*                                                                        *
 * This script belongs to the Extbase framework                           *
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
 */

/**
 * A helper class providing some methods in an easier namespace
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_Extbase {

	/**
	 * calls Tx_Extbase_Error_Debugger::var_dump()
	 * @param mixed $variable The variable to display a dump of
	 * @param string $title optional custom title for the debug output
	 * @param integer $recursionDepth Sets the max recursion depth of the dump. De- or increase the number according to your needs and memory limit.
	 * @param boolean $return if TRUE, the dump is returned for displaying it embedded in custom HTML. If FALSE (default), the variable dump is directly displayed.
	 * @param boolean $plaintext If TRUE, the dump is in plain text, if FALSE the debug output is in HTML format. If not specified, the dump is in HTML format.
	 * @return void/string if $return is TRUE, the variable dump is returned. By default, the dump is directly displayed, and nothing is returned.
	 */
	static public function var_dump($variable, $title = NULL, $recursionDepth = 15, $return = FALSE, $plaintext = FALSE) {
		return Tx_Extbase_Error_Debugger::var_dump($variable, $title, $recursionDepth, $return, $plaintext);
	}
}
?>