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
 * @package 
 * @subpackage 
 * @version $Id$
 */
/**
 * [Enter description here]
 *
 * @package
 * @subpackage
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode extends Tx_Fluid_Core_SyntaxTree_ViewHelperNode {
	public $callProtocol = array();
	
	public function __construct(Tx_Fluid_Core_VariableContainer $variableContainer) {
		$this->variableContainer = $variableContainer;
	}
	
	public function evaluateChildNodes() {
		$identifiers = $this->variableContainer->getAllIdentifiers();
		$callElement = array();
		foreach ($identifiers as $identifier) {
			$callElement[$identifier] = $this->variableContainer->get($identifier);
		}
		$this->callProtocol[] = $callElement;
	}
	
	public function evaluate(Tx_Fluid_Core_VariableContainer $container) {}
}


?>
