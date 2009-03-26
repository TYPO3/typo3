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
 * @version $Id: ViewHelperNode.php 2037 2009-03-24 14:09:37Z sebastian $
 */

/**
 * Node which will call a ViewHelper associated with this node.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: ViewHelperNode.php 2037 2009-03-24 14:09:37Z sebastian $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_Core_SyntaxTree_ViewHelperNode extends Tx_Fluid_Core_SyntaxTree_AbstractNode {

	/**
	 * Namespace of view helper
	 * @var string
	 */
	protected $viewHelperClassName;

	/**
	 * Arguments of view helper - References to RootNodes.
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * VariableContainer storing the currently available variables.
	 * @var Tx_Fluid_Core_VariableContainer
	 */
	protected $variableContainer;

	/**
	 * Constructor.
	 *
	 * @param string $viewHelperClassName Fully qualified class Tx_Fluid_Core_SyntaxTree_name of the view helper
	 * @param array $arguments Arguments of view helper - each value is a RootNode.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct($viewHelperClassName, array $arguments) {
		$this->viewHelperClassName = $viewHelperClassName;
		$this->arguments = $arguments;
	}

	/**
	 * Get class Tx_Fluid_Core_SyntaxTree_name of view helper
	 *
	 * @return string Class Name of associated view helper
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getViewHelperClassName() {
		return $this->viewHelperClassName;
	}

	/**
	 * Call the view helper associated with this object.
	 *
	 * First, it evaluates the arguments of the view helper.
	 *
	 * If the view helper implements Tx_Fluid_Core_Facets_ChildNodeAccessInterface,
	 * it calls setChildNodes(array childNodes) on the view helper.
	 *
	 * Afterwards, checks that the view helper did not leave a variable lying around.
	 *
	 * @param Tx_Fluid_Core_VariableContainer $variableContainer The Variable Container in which the variables are stored
	 * @return object evaluated node after the view helper has been called.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo Handle initializeArguments()
	 * @todo Component manager
	 */
	public function evaluate(Tx_Fluid_Core_VariableContainer $variableContainer) {
		$this->variableContainer = $variableContainer;

		$objectFactory = $variableContainer->getObjectFactory();
		$viewHelper = $objectFactory->create($this->viewHelperClassName);

		$contextVariables = $variableContainer->getAllIdentifiers();

		$evaluatedArguments = array();
		foreach ($this->arguments as $argumentName => $argumentValue) {
			$evaluatedArguments[$argumentName] = $argumentValue->evaluate($variableContainer);
		}

		$viewHelper->arguments = $objectFactory->create('Tx_Fluid_Core_ViewHelperArguments', $evaluatedArguments);
		$viewHelper->variableContainer = $variableContainer;
		$viewHelper->setViewHelperNode($this);

		if ($viewHelper instanceof Tx_Fluid_Core_Facets_ChildNodeAccessInterface) {
			$viewHelper->setChildNodes($this->childNodes);
		}

		$viewHelper->validateArguments();
		$out = $viewHelper->render();

		if ($contextVariables != $variableContainer->getAllIdentifiers()) {
			$endContextVariables = $variableContainer->getAllIdentifiers();
			$diff = array_intersect($endContextVariables, $contextVariables);

			throw new Tx_Fluid_Core_RuntimeException('The following context variable has been changed after the view helper "' . $this->viewHelperClassName . '" has been called: ' .implode(', ', $diff), 1236081302);
		}
		return $out;
	}
}


?>
