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
 * @version $Id: AbstractViewHelper.php 2082 2009-03-26 14:24:59Z sebastian $
 */

/**
 * The abstract base class Tx_Fluid_Core_for all view helpers.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: AbstractViewHelper.php 2082 2009-03-26 14:24:59Z sebastian $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
abstract class Tx_Fluid_Core_AbstractViewHelper implements Tx_Fluid_Core_ViewHelperInterface {

	/**
	 * Stores all Tx_Fluid_ArgumentDefinition instances
	 * @var array
	 */
	private $argumentDefinitions = array();

	/**
	 * Current view helper node
	 * @var Tx_Fluid_Core_SyntaxTree_ViewHelperNode
	 */
	private $viewHelperNode;

	/**
	 * Arguments accessor. Must be public, because it is set from the framework.
	 * @var Tx_Fluid_Core_ViewHelperArguments
	 */
	public $arguments;

	/**
	 * Current variable container reference. Must be public because it is set by the framework
	 * @var Tx_Fluid_Core_VariableContainer
	 */
	public $variableContainer;

	/**
	 * Validator resolver
	 * @var Tx_Fluid_Compatibility_Validation_ValidatorResolver
	 */
	protected $validatorResolver;

	/**
	 * Reflection service
	 * @var Tx_Fluid_Compatibility_ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Inject a validator resolver
	 * @param Tx_Fluid_Compatibility_Validation_ValidatorResolver $validatorResolver Validator Resolver
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	public function injectValidatorResolver(Tx_Fluid_Compatibility_Validation_ValidatorResolver $validatorResolver) {
		$this->validatorResolver = $validatorResolver;
	}

	/**
	 * Inject a Reflection service
	 * @param Tx_Fluid_Compatibility_ReflectionService $reflectionService Reflection service
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	public function injectReflectionService(Tx_Fluid_Compatibility_ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Register a new argument. Call this method from your ViewHelper subclass
	 * inside the initializeArguments() method.
	 *
	 * @param string $name Name of the argument
	 * @param string $type Type of the argument
	 * @param string $description Description of the argument
	 * @param boolean $required If TRUE, argument is required. Defaults to FALSE.
	 * @return Tx_Fluid_Core_AbstractViewHelper $this, to allow chaining.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo Component manager usage!
	 */
	protected function registerArgument($name, $type, $description, $required = FALSE) {
		$this->argumentDefinitions[$name] = new Tx_Fluid_Core_ArgumentDefinition($name, $type, $description, $required);
		return $this;
	}

	/**
	 * Sets all needed attributes needed for the rendering. Called by the
	 * framework. Populates $this->viewHelperNode
	 *
	 * @param Tx_Fluid_Core_SyntaxTree_ViewHelperNode $node View Helper node to be set.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	final public function setViewHelperNode(Tx_Fluid_Core_SyntaxTree_ViewHelperNode $node) {
		$this->viewHelperNode = $node;
	}

	/**
	 * Helper method which triggers the rendering of everything between the
	 * opening and the closing tag.
	 *
	 * @return string The finally rendered string.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function renderChildren() {
		return $this->viewHelperNode->renderChildNodes();
	}

	/**
	 * Initialize all arguments and return them
	 *
	 * @return array Array of Tx_Fluid_Core_ArgumentDefinition instances.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	public function prepareArguments() {
		$this->registerRenderMethodArguments();
		$this->initializeArguments();
		return $this->argumentDefinitions;
	}

	/**
	 * Register method arguments for "render" by analysing the doc comment above.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	private function registerRenderMethodArguments() {
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), 'render');

		$methodTags = $this->reflectionService->getMethodTagsValues(get_class($this), 'render');

		$paramAnnotations = array();
		if (isset($methodTags['param'])) {
			$paramAnnotations = $methodTags['param'];
		}

		$i = 0;
		if (!count($methodParameters)) return;
		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = 'Text';

			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}

			$description = '';
			if (isset($paramAnnotations[$i])) {
				$explodedAnnotation = explode(' ', $paramAnnotations[$i]);
				array_shift($explodedAnnotation);
				array_shift($explodedAnnotation);
				$description = implode(' ', $explodedAnnotation);
			}
			$this->registerArgument($parameterName, $dataType, $description, ($parameterInfo['optional'] === FALSE));
			$i++;
		}
	}

	/**
	 * Validate arguments, and throw exception if arguments do not validate.
	 *
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @internal
	 */
	public function validateArguments() {
		$argumentDefinitions = $this->prepareArguments();
		if (!count($argumentDefinitions)) return;

		foreach ($argumentDefinitions as $argumentName => $registeredArgument) {
			if ($this->arguments->offsetExists($argumentName)) {
				$type = $registeredArgument->getType();
				if ($type === 'array') {
					if (!is_array($this->arguments[$argumentName])) {
						throw new Tx_Fluid_Core_RuntimeException('An argument "' . $argumentName . '" was registered with type array, but it is no array.', 1237900529);
					}
				} else {
					$validator = $this->validatorResolver->getValidator($type);
					if (is_null($validator)) {
						throw new Tx_Fluid_Core_RuntimeException('No validator found for argument name "' . $argumentName . '" with type "' . $type . '".', 1237900534);
					}
					$errors = new Tx_Fluid_Compatibility_Validation_Errors();

					if (!$validator->isValid($this->arguments[$argumentName], $errors)) {
						throw new Tx_Fluid_Core_RuntimeException('Validation for argument name "' . $argumentName . '" FAILED.', 1237900686);
					}
				}
			}
		}
	}

	/**
	 * Initialize all arguments. You need to override this method and call
	 * $this->registerArgument(...) inside this method, to register all your arguments.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
	}

	/**
	 * Render method you need to implement for your custom view helper.
	 * Available objects at this point are $this->arguments, and $this->variableContainer.
	 *
	 * Besides, you often need $this->renderChildren().
	 *
	 * @return string rendered string, view helper specific
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	//abstract public function render();

}

?>