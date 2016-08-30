<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The abstract base class for all view helpers.
 *
 * @api
 */
abstract class AbstractViewHelper
{
    /**
     * TRUE if arguments have already been initialized
     *
     * @var bool
     */
    private $argumentsInitialized = false;

    /**
     * Stores all \TYPO3\CMS\Fluid\ArgumentDefinition instances
     *
     * @var array
     */
    private $argumentDefinitions = [];

    /**
     * Cache of argument definitions; the key is the ViewHelper class name, and the
     * value is the array of argument definitions.
     *
     * In our benchmarks, this cache leads to a 40% improvement when using a certain
     * ViewHelper class many times throughout the rendering process.
     *
     * @var array
     */
    private static $argumentDefinitionCache = [];

    /**
     * Current view helper node
     *
     * @var \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
     */
    private $viewHelperNode;

    /**
     * Arguments array.
     *
     * @var array
     * @api
     */
    protected $arguments;

    /**
     * Current variable container reference.
     *
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer
     * @api
     */
    protected $templateVariableContainer;

    /**
     * Controller Context to use
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     * @api
     */
    protected $controllerContext;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface
     */
    protected $renderingContext;

    /**
     * @var \Closure
     */
    protected $renderChildrenClosure = null;

    /**
     * ViewHelper Variable Container
     *
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer
     * @api
     */
    protected $viewHelperVariableContainer;

    /**
     * Reflection service
     *
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    private $reflectionService;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * With this flag, you can disable the escaping interceptor inside this ViewHelper.
     * THIS MIGHT CHANGE WITHOUT NOTICE, NO PUBLIC API!
     * @var bool
     */
    protected $escapingInterceptorEnabled = true;

    /**
     * @param array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return void
     */
    public function setRenderingContext(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        $this->renderingContext = $renderingContext;
        $this->templateVariableContainer = $renderingContext->getTemplateVariableContainer();
        if ($renderingContext->getControllerContext() !== null) {
            $this->controllerContext = $renderingContext->getControllerContext();
        }
        $this->viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
    }

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Inject a Reflection service
     *
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService Reflection service
     */
    public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Returns whether the escaping interceptor should be disabled or enabled inside the tags contents.
     *
     * THIS METHOD MIGHT CHANGE WITHOUT NOTICE; NO PUBLIC API!
     *
     * @return bool
     */
    public function isEscapingInterceptorEnabled()
    {
        return $this->escapingInterceptorEnabled;
    }

    /**
     * Register a new argument. Call this method from your ViewHelper subclass
     * inside the initializeArguments() method.
     *
     * @param string $name Name of the argument
     * @param string $type Type of the argument
     * @param string $description Description of the argument
     * @param bool $required If TRUE, argument is required. Defaults to FALSE.
     * @param mixed $defaultValue Default value of argument
     * @return \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper $this, to allow chaining.
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     * @api
     */
    protected function registerArgument($name, $type, $description, $required = false, $defaultValue = null)
    {
        if (array_key_exists($name, $this->argumentDefinitions)) {
            throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('Argument "' . $name . '" has already been defined, thus it should not be defined again.', 1253036401);
        }
        $this->argumentDefinitions[$name] = new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition($name, $type, $description, $required, $defaultValue);
        return $this;
    }

    /**
     * Overrides a registered argument. Call this method from your ViewHelper subclass
     * inside the initializeArguments() method if you want to override a previously registered argument.
     *
     * @see registerArgument()
     * @param string $name Name of the argument
     * @param string $type Type of the argument
     * @param string $description Description of the argument
     * @param bool $required If TRUE, argument is required. Defaults to FALSE.
     * @param mixed $defaultValue Default value of argument
     * @return \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper $this, to allow chaining.
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     * @api
     */
    protected function overrideArgument($name, $type, $description, $required = false, $defaultValue = null)
    {
        if (!array_key_exists($name, $this->argumentDefinitions)) {
            throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('Argument "' . $name . '" has not been defined, thus it can\'t be overridden.', 1279212461);
        }
        $this->argumentDefinitions[$name] = new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition($name, $type, $description, $required, $defaultValue);
        return $this;
    }

    /**
     * Sets all needed attributes needed for the rendering. Called by the
     * framework. Populates $this->viewHelperNode.
     * This is PURELY INTERNAL! Never override this method!!
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $node View Helper node to be set.
     * @return void
     */
    public function setViewHelperNode(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $node)
    {
        $this->viewHelperNode = $node;
    }

    /**
     * Called when being inside a cached template.
     *
     * @param \Closure $renderChildrenClosure
     * @return void
     */
    public function setRenderChildrenClosure(\Closure $renderChildrenClosure)
    {
        $this->renderChildrenClosure = $renderChildrenClosure;
    }

    /**
     * Initialize the arguments of the ViewHelper, and call the render() method of the ViewHelper.
     *
     * @return string the rendered ViewHelper.
     */
    public function initializeArgumentsAndRender()
    {
        $this->validateArguments();
        $this->initialize();

        return $this->callRenderMethod();
    }

    /**
     * Call the render() method and handle errors.
     *
     * @return string the rendered ViewHelper
     * @throws Exception
     */
    protected function callRenderMethod()
    {
        $renderMethodParameters = [];
        foreach ($this->argumentDefinitions as $argumentName => $argumentDefinition) {
            if ($argumentDefinition->isMethodParameter()) {
                $renderMethodParameters[$argumentName] = $this->arguments[$argumentName];
            }
        }

        try {
            return call_user_func_array([$this, 'render'], $renderMethodParameters);
        } catch (Exception $exception) {
            if (GeneralUtility::getApplicationContext()->isProduction()) {
                $this->getLogger()->error('A Fluid ViewHelper Exception was captured: ' . $exception->getMessage() . ' (' . $exception->getCode() . ')', ['exception' => $exception]);
                return '';
            } else {
                throw $exception;
            }
        }
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Initializes the view helper before invoking the render method.
     *
     * Override this method to solve tasks before the view helper content is rendered.
     *
     * @return void
     * @api
     */
    public function initialize()
    {
    }

    /**
     * Helper method which triggers the rendering of everything between the
     * opening and the closing tag.
     *
     * @return mixed The finally rendered child nodes.
     * @api
     */
    public function renderChildren()
    {
        if ($this->renderChildrenClosure !== null) {
            $closure = $this->renderChildrenClosure;
            return $closure();
        }
        return $this->viewHelperNode->evaluateChildNodes($this->renderingContext);
    }

    /**
     * Helper which is mostly needed when calling renderStatic() from within
     * render().
     *
     * No public API yet.
     *
     * @return \Closure
     */
    protected function buildRenderChildrenClosure()
    {
        $self = $this;
        return function () use ($self) {
            return $self->renderChildren();
        };
    }

    /**
     * Initialize all arguments and return them
     *
     * @return array Array of \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition instances.
     */
    public function prepareArguments()
    {
        if (!$this->argumentsInitialized) {
            $thisClassName = get_class($this);
            if (isset(self::$argumentDefinitionCache[$thisClassName])) {
                $this->argumentDefinitions = self::$argumentDefinitionCache[$thisClassName];
            } else {
                $this->registerRenderMethodArguments();
                $this->initializeArguments();
                self::$argumentDefinitionCache[$thisClassName] = $this->argumentDefinitions;
            }
            $this->argumentsInitialized = true;
        }
        return $this->argumentDefinitions;
    }

    /**
     * Register method arguments for "render" by analysing the doc comment above.
     *
     * @return void
     * @throws \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    private function registerRenderMethodArguments()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(get_class($this), 'render');
        if (count($methodParameters) === 0) {
            return;
        }

        if (\TYPO3\CMS\Fluid\Fluid::$debugMode) {
            $methodTags = $this->reflectionService->getMethodTagsValues(get_class($this), 'render');

            $paramAnnotations = [];
            if (isset($methodTags['param'])) {
                $paramAnnotations = $methodTags['param'];
            }
        }

        $i = 0;
        foreach ($methodParameters as $parameterName => $parameterInfo) {
            $dataType = null;
            if (isset($parameterInfo['type'])) {
                $dataType = $parameterInfo['type'];
            } elseif ($parameterInfo['array']) {
                $dataType = 'array';
            }
            if ($dataType === null) {
                throw new \TYPO3\CMS\Fluid\Core\Parser\Exception('could not determine type of argument "' . $parameterName . '" of the render-method in ViewHelper "' . get_class($this) . '". Either the methods docComment is invalid or some PHP optimizer strips off comments.', 1242292003);
            }

            $description = '';
            if (\TYPO3\CMS\Fluid\Fluid::$debugMode && isset($paramAnnotations[$i])) {
                $explodedAnnotation = explode(' ', $paramAnnotations[$i]);
                array_shift($explodedAnnotation);
                array_shift($explodedAnnotation);
                $description = implode(' ', $explodedAnnotation);
            }
            $defaultValue = null;
            if (isset($parameterInfo['defaultValue'])) {
                $defaultValue = $parameterInfo['defaultValue'];
            }
            $this->argumentDefinitions[$parameterName] = new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition($parameterName, $dataType, $description, ($parameterInfo['optional'] === false), $defaultValue, true);
            $i++;
        }
    }

    /**
     * Validate arguments, and throw exception if arguments do not validate.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function validateArguments()
    {
        $argumentDefinitions = $this->prepareArguments();
        if (!count($argumentDefinitions)) {
            return;
        }
        foreach ($argumentDefinitions as $argumentName => $registeredArgument) {
            if ($this->hasArgument($argumentName)) {
                if ($this->arguments[$argumentName] === $registeredArgument->getDefaultValue()) {
                    continue;
                }

                $type = $registeredArgument->getType();
                if ($type === 'array') {
                    if (!is_array($this->arguments[$argumentName]) && !$this->arguments[$argumentName] instanceof \ArrayAccess && !$this->arguments[$argumentName] instanceof \Traversable) {
                        throw new \InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "array", but is of type "' . gettype($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '"', 1237900529);
                    }
                } elseif ($type === 'boolean') {
                    if (!is_bool($this->arguments[$argumentName])) {
                        throw new \InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "boolean", but is of type "' . gettype($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '".', 1240227732);
                    }
                } elseif (class_exists($type, false)) {
                    if (!($this->arguments[$argumentName] instanceof $type)) {
                        if (is_object($this->arguments[$argumentName])) {
                            throw new \InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "' . $type . '", but is of type "' . get_class($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '".', 1256475114);
                        } else {
                            throw new \InvalidArgumentException('The argument "' . $argumentName . '" was registered with type "' . $type . '", but is of type "' . gettype($this->arguments[$argumentName]) . '" in view helper "' . get_class($this) . '".', 1256475113);
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
     * @api
     */
    public function initializeArguments()
    {
    }

    /**
     * Render method you need to implement for your custom view helper.
     * Available objects at this point are $this->arguments, and $this->templateVariableContainer.
     *
     * Besides, you often need $this->renderChildren().
     *
     * @return string rendered string, view helper specific
     * @api
     */
    //abstract public function render();

    /**
     * Tests if the given $argumentName is set, and not NULL.
     *
     * @param string $argumentName
     * @return bool TRUE if $argumentName is found, FALSE otherwise
     * @api
     */
    protected function hasArgument($argumentName)
    {
        return isset($this->arguments[$argumentName]) && $this->arguments[$argumentName] !== null;
    }

    /**
     * Default implementation for CompilableInterface. By default,
     * inserts a renderStatic() call to itself.
     *
     * You only should override this method *when you absolutely know what you
     * are doing*, and really want to influence the generated PHP code during
     * template compilation directly.
     *
     * @param string $argumentsVariableName
     * @param string $renderChildrenClosureVariableName
     * @param string $initializationPhpCode
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode
     * @param \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler
     * @return string
     * @see \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface
     */
    public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode $syntaxTreeNode, \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler)
    {
        return sprintf('%s::renderStatic(%s, %s, $renderingContext)',
                get_class($this), $argumentsVariableName, $renderChildrenClosureVariableName);
    }

    /**
     * Default implementation for CompilableInterface. See CompilableInterface
     * for a detailed description of this method.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return mixed
     * @see \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        return null;
    }

    /**
     * Resets the ViewHelper state.
     *
     * Overwrite this method if you need to get a clean state of your ViewHelper.
     *
     * @return void
     */
    public function resetState()
    {
    }
}
