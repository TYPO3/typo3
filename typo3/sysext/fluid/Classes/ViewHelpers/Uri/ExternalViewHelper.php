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
 * A view helper for creating URIs to external targets.
 * Currently the specified URI is simply passed through.
 *
 * = Examples =
 * 
 * <code>
 * <f:uri.external uri="http://www.typo3.org" />
 * </code>
 *
 * Output:
 * http://www.typo3.org
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: ExternalViewHelper.php 725 2009-05-28 21:45:46Z sebastian $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Uri_ExternalViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @param string $uri the target URI
	 * @return string rendered URI
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($uri) {
		return $uri;
	}
}


?>