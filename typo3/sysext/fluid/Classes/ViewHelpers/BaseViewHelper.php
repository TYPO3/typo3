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
 * View helper which creates a <base href="..."></base> tag.
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:base />
 * </code>
 * 
 * Output:
 * <base href="http://yourdomain.tld/"></base>
 * (depending on your domain)
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_BaseViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * Render the "Base" tag by outputting $request->getBaseURI()
	 *
	 * Note: renders as <base></base>, because IE6 will else refuse to display
	 * the page...
	 *
	 * @return string "base"-Tag.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render() {
		$currentRequest = $this->variableContainer->get('view')->getRequest();
		return '<base href="' . $currentRequest->getBaseURI() . '"></base>';
	}
}

?>
