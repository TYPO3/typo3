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
 * @subpackage Core
 * @version $Id: ParsedTemplateInterface.php 1962 2009-03-03 12:10:41Z k-fish $
 */

/**
 * This interface Tx_Fluid_Core_is returned by Tx_Fluid_Core_TemplateParser->parse() method.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: ParsedTemplateInterface.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface Tx_Fluid_Core_ParsedTemplateInterface {

	/**
	 * Get root node of this parsing state.
	 *
	 * @return Tx_Fluid_Core_SyntaxTree_RootNode The root node
	 */
	public function getRootNode();

	/**
	 * Returns a variable container used in the PostParse Facet.
	 *
	 * @return Tx_Fluid_Core_VariableContainer
	 */
	public function getVariableContainer();
}

?>