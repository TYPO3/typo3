<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * [Enter description here]
 *
 */
class Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode extends Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode {
	public $callProtocol = array();

	public function __construct(Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer) {
		$this->variableContainer = $variableContainer;
	}

	public function evaluateChildNodes(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {
		$identifiers = $this->variableContainer->getAllIdentifiers();
		$callElement = array();
		foreach ($identifiers as $identifier) {
			$callElement[$identifier] = $this->variableContainer->get($identifier);
		}
		$this->callProtocol[] = $callElement;
	}

	public function evaluate(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext) {}
}


?>
