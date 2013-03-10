<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Constraint syntax tree node fixture
 */
class ConstraintSyntaxTreeNode extends \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode {
	public $callProtocol = array();

	/**
	 * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer
	 */
	protected $variableContainer;

	public function __construct(\TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer) {
		$this->variableContainer = $variableContainer;
	}

	public function evaluateChildNodes(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		$identifiers = $this->variableContainer->getAllIdentifiers();
		$callElement = array();
		foreach ($identifiers as $identifier) {
			$callElement[$identifier] = $this->variableContainer->get($identifier);
		}
		$this->callProtocol[] = $callElement;
	}

	public function evaluate(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
	}
}

?>