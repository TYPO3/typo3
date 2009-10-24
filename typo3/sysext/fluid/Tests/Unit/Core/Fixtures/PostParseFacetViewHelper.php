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
 * @version $Id$
 * @package Fluid
 * @subpackage Core\Fixtures
 */
/**
 * Enter description here...
 * @scope prototype
 */
class Tx_Fluid_Core_Fixtures_PostParseFacetViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper implements Tx_Fluid_Core_ViewHelper_Facets_PostParseInterface {

	public static $wasCalled = FALSE;

	public function __construct() {
	}

	static public function postParseEvent(Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode $viewHelperNode, array $arguments, Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer) {
		self::$wasCalled = TRUE;
	}

	public function initializeArguments() {
	}

	public function render() {
	}
}

?>