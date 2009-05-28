<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
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
 * @package Fluid
 * @subpackage Core
 * @version $Id: ViewHelperNode.php 2411 2009-05-26 22:00:04Z sebastian $
 */

/**
 * Node which will call a ViewHelper associated with this node.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: ViewHelperNode.php 2411 2009-05-26 22:00:04Z sebastian $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @intenral
 */
class Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode extends Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode {

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
	 * Constructor.
	 *
	 * @param string $viewHelperClassName Fully qualified class name of the view helper
	 * @param array $arguments Arguments of view helper - each value is a RootNode.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	public function __construct($viewHelperClassName, array $arguments) {
		$this->viewHelperClassName = $viewHelperClassName;
		$this->arguments = $arguments;
	}

	/**
	 * Get class name of view helper
	 *
	 * @return string Class Name of associated view helper
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	public function getViewHelperClassName() {
		return $this->viewHelperClassName;
	}

	/**
	 * Call the view helper associated with this object.
	 *
	 * First, it evaluates the arguments of the view helper.
	 *
	 * If the view helper implements Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface,
	 * it calls setChildNodes(array childNodes) on the view helper.
	 *
	 * Afterwards, checks that the view helper did not leave a variable lying around.
	 *
	 * @return object evaluated node after the view helper has been called.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	public function evaluate() {
		if ($this->renderingContext === NULL) {
			throw new Tx_Fluid_Core_RuntimeException('RenderingContext is null in ViewHelperNode, but necessary. If this error appears, please report a bug!', 1242669031);
		}

		$objectFactory = $this->renderingContext->getObjectFactory();
		$viewHelper = $objectFactory->create($this->viewHelperClassName);
		$argumentDefinitions = $viewHelper->prepareArguments();

		$contextVariables = $this->renderingContext->getTemplateVariableContainer()->getAllIdentifiers();

		$evaluatedArguments = array();
		$renderMethodParameters = array();
		$this->renderingContext->setArgumentEvaluationMode(TRUE);
		if (count($argumentDefinitions)) {
			foreach ($argumentDefinitions as $argumentName => $argumentDefinition) {
				if (isset($this->arguments[$argumentName])) {
					$argumentValue = $this->arguments[$argumentName];
					$argumentValue->setRenderingContext($this->renderingContext);
					$evaluatedArguments[$argumentName] = $this->convertArgumentValue($argumentValue->evaluate(), $argumentDefinition->getType());
				} else {
					$evaluatedArguments[$argumentName] = $argumentDefinition->getDefaultValue();
				}
				if ($argumentDefinition->isMethodParameter()) {
					$renderMethodParameters[$argumentName] = $evaluatedArguments[$argumentName];
				}
			}
		}
		$this->renderingContext->setArgumentEvaluationMode(FALSE);

		$viewHelperArguments = $objectFactory->create('Tx_Fluid_Core_ViewHelper_Arguments', $evaluatedArguments);
		$viewHelper->setArguments($viewHelperArguments);
		$viewHelper->setTemplateVariableContainer($this->renderingContext->getTemplateVariableContainer());
		$viewHelper->setControllerContext($this->renderingContext->getControllerContext());
		$viewHelper->setViewHelperVariableContainer($this->renderingContext->getViewHelperVariableContainer());
		$viewHelper->setViewHelperNode($this);

		if ($viewHelper instanceof Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface) {
			$viewHelper->setChildNodes($this->childNodes);
			$viewHelper->setRenderingContext($this->renderingContext);
		}

		$viewHelper->validateArguments();
		$viewHelper->initialize();
		try {
			$output = call_user_func_array(array($viewHelper, 'render'), $renderMethodParameters);
		} catch (Tx_Fluid_Core_ViewHelper_Exception $exception) {
			// @todo [BW] rethrow exception, log, ignore.. depending on the current context
			$output = $exception->getMessage();
		}

		if ($contextVariables != $this->renderingContext->getTemplateVariableContainer()->getAllIdentifiers()) {
			$endContextVariables = $this->renderingContext->getTemplateVariableContainer();
			$diff = array_intersect($endContextVariables, $contextVariables);

			throw new Tx_Fluid_Core_RuntimeException('The following context variable has been changed after the view helper "' . $this->viewHelperClassName . '" has been called: ' .implode(', ', $diff), 1236081302);
		}
		return $output;
	}

	/**
	 * Convert argument strings to their equivalents. Needed to handle strings with a boolean meaning.
	 *
	 * @param mixed $value Value to be converted
	 * @param string $type Target type
	 * @return mixed New value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function convertArgumentValue($value, $type) {
		if ($type === 'boolean') {
			return $this->convertToBoolean($value);
		}
		return $value;
	}

	/**
	 * Convert argument strings to their equivalents. Needed to handle strings with a boolean meaning.
	 *
	 * @param mixed $value Value to be converted to boolean
	 * @return mixed New value
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @todo this should be moved to another class
	 */
	protected function convertToBoolean($value) {
		if (is_bool($value)) {
			return $value;
		}
		if (is_string($value)) {
			return (strtolower($value) !== 'false' && !empty($value));
		}
		if (is_numeric($value)) {
			return $value > 0;
		}
		if (is_array($value) || (is_object($value) && $value instanceof Countable)) {
			return count($value) > 0;
		}
		if (is_object($value)) {
			return TRUE;
		}
		return FALSE;
	}
}

?>