<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The abstract base class for all view helpers.
 *
 * @api
 */
abstract class AbstractViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface
{
    /**
     * TRUE if arguments have already been initialized
     *
     * @var bool
     */
    private $argumentsInitialized = false;

    /**
     * Cache of argument definitions; the key is the ViewHelper class name, and the
     * value is the array of argument definitions.
     *
     * In our benchmarks, this cache leads to a 40% improvement when using a certain
     * ViewHelper class many times throughout the rendering process.
     *
     * @var array
     */
    private static $argumentDefinitionCache = array();

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
     * @var \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
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
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return void
     */
    public function setRenderingContext(\TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        $this->renderingContext = $renderingContext;
        $this->templateVariableContainer = $renderingContext->getVariableProvider();
        $this->viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        if ($renderingContext instanceof \TYPO3\CMS\Fluid\Core\Rendering\RenderingContext) {
            $this->controllerContext = $renderingContext->getControllerContext();
        }
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
     * Register a new argument. Call this method from your ViewHelper subclass
     * inside the initializeArguments() method.
     *
     * @param string $name Name of the argument
     * @param string $type Type of the argument
     * @param string $description Description of the argument
     * @param bool $required If TRUE, argument is required. Defaults to FALSE.
     * @param mixed $defaultValue Default value of argument
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface $this, to allow chaining.
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     * @api
     */
    protected function registerArgument($name, $type, $description, $required = false, $defaultValue = null)
    {
        if (array_key_exists($name, $this->argumentDefinitions)) {
            throw new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception('Argument "' . $name . '" has already been defined, thus it should not be defined again.', 1253036401);
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
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface $this, to allow chaining.
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
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
        $renderMethodParameters = array();
        foreach ($this->argumentDefinitions as $argumentName => $argumentDefinition) {
            if ($argumentDefinition instanceof \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition && $argumentDefinition->isMethodParameter()) {
                $renderMethodParameters[$argumentName] = $this->arguments[$argumentName];
            }
        }

        try {
            return call_user_func_array(array($this, 'render'), $renderMethodParameters);
        } catch (Exception $exception) {
            if (GeneralUtility::getApplicationContext()->isProduction()) {
                $this->getLogger()->error('A Fluid ViewHelper Exception was captured: ' . $exception->getMessage() . ' (' . $exception->getCode() . ')', array('exception' => $exception));
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
     * Register method arguments for "render" by analysing the doc comment above.
     *
     * @return void
     * @throws \TYPO3Fluid\Fluid\Core\Parser\Exception
     */
    protected function registerRenderMethodArguments()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(get_class($this), 'render');
        if (count($methodParameters) === 0) {
            return;
        }

        $methodTags = $this->reflectionService->getMethodTagsValues(get_class($this), 'render');

        $paramAnnotations = array();
        if (isset($methodTags['param'])) {
            $paramAnnotations = $methodTags['param'];
        }

        $i = 0;
        foreach ($methodParameters as $parameterName => $parameterInfo) {
            $dataType = null;
            if (isset($parameterInfo['type'])) {
                $dataType = isset($parameterInfo['array']) && (bool)$parameterInfo['array'] ? 'array' : $parameterInfo['type'];
            } else {
                throw new \TYPO3\CMS\Fluid\Core\Exception('Could not determine type of argument "' . $parameterName . '" of the render-method in ViewHelper "' . get_class($this) . '". Either the methods docComment is invalid or some PHP optimizer strips off comments.', 1242292003);
            }

            $description = '';
            if (isset($paramAnnotations[$i])) {
                $explodedAnnotation = explode(' ', $paramAnnotations[$i]);
                array_shift($explodedAnnotation);
                array_shift($explodedAnnotation);
                $description = implode(' ', $explodedAnnotation);
            }
            $defaultValue = null;
            if (isset($parameterInfo['defaultValue'])) {
                $defaultValue = $parameterInfo['defaultValue'];
            }
            $this->argumentDefinitions[$parameterName] = new ArgumentDefinition($parameterName, $dataType, $description, ($parameterInfo['optional'] === false), $defaultValue, true);
            $i++;
        }
    }

    /**
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition[]
     * @throws \TYPO3Fluid\Fluid\Core\Parser\Exception
     */
    public function prepareArguments()
    {
        if (method_exists($this, 'registerRenderMethodArguments')) {
            $this->registerRenderMethodArguments();
        }
        return parent::prepareArguments();
    }
}
