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
 * Wrapper for PHPs nl2br function.
 * @see http://www.php.net/manual/en/function.nl2br.php
 * 
 * = Examples =
 *
 * <code title="Example">
 * <f:format.nl2br>{text_with_linebreaks}</f:format.nl2br>
 * </code>
 *
 * Output:
 * text with line breaks replaced by <br />
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Format_Nl2brViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * Replaces newline characters by HTML line breaks.
	 *
	 * @return string the altered string. 
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render() {
		$content = $this->renderChildren();
		return nl2br($content);
	}
}
?>