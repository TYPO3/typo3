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
 * The abstract base class for all view helpers.
 *
 * @version $Id: AbstractViewHelper.php 2043 2010-03-16 08:49:45Z sebastian $
 * @package Fluid
 * @subpackage Core\ViewHelper
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
abstract class Tx_Fluid_Core_ViewHelper_AbstractViewHelper implements Tx_Fluid_Core_ViewHelper_ViewHelperInterface {

	/**
	 * TRUE if arguments have already been initialized
	 * @var boolean
	 */
	private $argumentsInitialized = FALSE;

	/**
	 * Stores all Tx_Fluid_ArgumentDefinition instances
	 * @var array
	 */
	private $argumentDefinitions = array();

	/**
	 * Current view helper node
	 * @var Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode
	 */
	private $viewHelperNode;

	/**
	 * Arguments accessor.
	 * @var Tx_Fluid_Core_ViewHelper_Arguments
	 * @api
	 */
	protected $arguments;

	/**
	 * Current variable container reference.
	 * @var Tx_Fluid_Core_ViewHelper_TemplateVariableContainer
	 * @api
	 */
	protected $templateVariableContainer;

	/**
	 * Controller Context to use
	 * @var Tx_Extbase_MVC_Controller_ControllerContext
	 * @api
	 */
	protected $controllerContext;

	/**
	 * ViewHelper Variable Container
	 * @var Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer
	 * @api
	 */
	protected $viewHelperVariableContainer;

	/**
	 * Reflection service
	 * @var Tx_Extbase_Reflection_Service
	 */
	private $reflectionService;

	/**
	 * With this flag, you can disable the escaping interceptor inside this ViewHelper.
	 * THIS MIGHT CHANGE WITHOUT NOTICE, NO PUBLIC API!
	 * @var boolean
	 * @internal
	 */
	protected $escapingInterceptorEnabled = TRUE;

	/**
	 * @param Tx_Fluid_Core_ViewHelper_Arguments $arguments
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArguments(Tx_Fluid_Core_ViewHelper_Arguments $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * @param Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $templateVariableContainer Variable Container to be used for rendering
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setTemplateVariableContainer(Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $templateVariableContainer) {
		$this->templateVariableContainer = $templateVariableContainer;
	}

	/**
	 * @param Tx_Extbase_MVC_Controller_ControllerContext $controllerContext Controller context which is available inside the view
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setControllerContext(Tx_Extbase_MVC_Controller_ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * @param Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer $viewHelperVariableContainer
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setViewHelperVariableContainer(Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer $viewHelperVariableContainer) {
		$this->viewHelperVariableContainer = $viewHelperVariableContainer;
	}

	/**
	 * Inject a Reflection service
	 * @param Tx_Extbase_Reflection_Service $reflectionService Reflection service
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Returns whether the escaping interceptor should be disabled or enabled inside the tags contents.
	 *
	 * THIS METHOD MIGHT CHANGE WITHOUT NOTICE; NO PUBLIC API!
	 *
	 * @internal
	 * @return boolean
	 */
	public function isEscapingInterceptorEnabled() {
		return $this->escapingInterceptorEnabled;
	}

	/**
	 * Register a new argument. Call this method from your ViewHelper subclass
	 * inside the initializeArguments() method.
	 *
	 * @param string $name Name of the argument
	 * @param string $type Type of the argument
	 * @param string $description Description of the argument
	 * @param boolean $required If TRUE, argument is required. Defaults to FALSE.
	 * @param mixed $defaultValue Default value of argument
	 * @return Tx_Fluid_Core_ViewHelper_AbstractViewHelper $this, to allow chaining.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo Object Factory usage!
	 * @api
	 */
	protected function registerArgument($name, $type, $description, $required = FALSE, $defaultValue = NULL) {
		if (array_key_exists($name, $this->argumentDefinitions)) {
			throw new Tx_Fluid_Core_ViewHelper_Exception('Argument "' . $name . '" has already been defined, thus it should not be defined again.', 1253036401);
		}
		$this->argumentDefinitions[$name] = new Tx_Fluid_Core_ViewHelper_ArgumentDefinition($name, $type, $description, $required, $defaultValue);
		return $this;
	}

	/**
	 * Sets all needed attributes needed for the rendering. Called by the
	 * framework. Populates $this->viewHelperNode.
	 * This is PURELY INTERNAL! Never override this method!!
	 *
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode $node View Helper node to be set.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setViewHelperNode(Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode $node) {
		$this->viewHelperNode = $node;
	}

	/**
	 * Initializes the view helper before invoking the render method.
	 *
	 * Override this method to solve tasks before the view helper content is rendered.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function initialize() {
	}

	/**
	 * Helper method which triggers the rendering of everything between the
	 * opening and the closing tag.
	 *
	 * @return mixed The finally rendered child nodes.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	protected function renderChildren() {
		return $this->viewHelperNode->evaluateChildNodes();
	}

	/**
	 * Initialize all arguments and return them
	 *
	 * @return array Array of Tx_Fluid_Core_ViewHelper_ArgumentDefinition instances.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function prepareArguments() {
		if (!$this->argumentsInitialized) {
			$this->registerRenderMethodArguments();
			$this->initializeArguments();
			$this->argumentsInitialized = TRUE;
		}
		return $this->argumentDefinitions;
	}

	/**
	 * Register method arguments for "render" by analysing the doc comment above.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	private function registerRenderMethodArguments() {
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), 'render');
		if (count($methodParameters) === 0) {
			return;
		}

		if (Tx_Fluid_Fluid::$debugMode) {
			$methodTags = $this->reflectionService->getMethodTagsValues(get_class($this), 'render');

			$paramAnnotations = array();
			if (isset($methodTags['param'])) {
				$paramAnnotations = $methodTags['param'];
			}
		}

		$i = 0;
		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = NULL;
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			if ($dataType === NULL) {
				throw new Tx_Fluid_Core_Parser_Exception('could not determine type of argument "' . $parameterName .'" of the render-method in ViewHelper "' . get_class($this) . '". Either the methods docComment is invalid or some PHP optimizer strips off comments.', 1242292003);
			}

			$description = '';
			if (Tx_Fluid_Fluid::$debugMode && isset($paramAnnotations[$i])) {
				$explodedAnnotation = explode(' ', $paramAnnotations[$i]);
				array_shift($explodedAnnotation);
				array_shift($explodedAnnotation);
				$description = implode(' ', $explodedAnnotation);
			}
			$defaultValue = NULL;
			if (isset($parameterInfo['defaultValue'])) {
				$defaultValue = $parameterInfo['defaultValue'];
			}
			$this->argumentDefinitions[$parameterName] = new Tx_Fluid_Core_ViewHelper_ArgumentDefinition($parameterName, $dataType, $description, ($parameterInfo['optional'] === FALSE), $defaultValue, TRUE);
			$i++;
		}
	}

	/**
	 * Validate arguments, and throw exception if arguments do not validate.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function validateArguments() {
		$argumentDefinitions = $this->prepareArguments();
		if (!count($argumentDefinitions)) return;

		foreach ($argumentDefinitions as $argumentName => $registeredArgument) {
			if ($this->arguments->offsetExists($argumentName)) {
				$type = $registeredArgument->getType();
				if ($this->arguments[$argumentName] === $registeredArgument->getDefaultValue()) continue;

				if ($type === 'array') {
					if (!is_array($this->arguments[$argumentName]) && !$this->arguments[$argumentName] instanceof ArrayAccess && !$this->arguments[$argumentName] instanceof Traversable) {
						throw new InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "array", but is of type "' . gettype($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '"', 1237900529);
					}
				} elseif ($type === 'boolean') {
					if (!is_bool($this->arguments[$argumentName])) {
						throw new InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "boolean", but is of type "' . gettype($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '".', 1240227732);
					}
				} elseif (class_exists($type, FALSE)) {
					if (! ($this->arguments[$argumentName] instanceof $type)) {
						if (is_object($this->arguments[$argumentName])) {
							throw new InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "' . $type . '", but is of type "' . get_class($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '".', 1256475114);
						} else {
							throw new InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "' . $type . '", but is of type "' . gettype($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '".', 1256475113);
						}
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
	 * @api
	 */
	public function initializeArguments() {
	}

	/**
	 * Render method you need to implement for your custom view helper.
	 * Available objects at this point are $this->arguments, and $this->templateVariableContainer.
	 *
	 * Besides, you often need $this->renderChildren().
	 *
	 * @return string rendered string, view helper specific
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	//abstract public function render();
}

?>