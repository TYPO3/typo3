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
     * Controller Context to use
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     * @api
     */
    protected $controllerContext;

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
     */
    public function setRenderingContext(\TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        parent::setRenderingContext($renderingContext);
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
     * Call the render() method and handle errors.
     *
     * @return string the rendered ViewHelper
     * @throws Exception
     */
    protected function callRenderMethod()
    {
        $renderMethodParameters = [];
        foreach ($this->argumentDefinitions as $argumentName => $argumentDefinition) {
            if ($argumentDefinition instanceof \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition && $argumentDefinition->isMethodParameter()) {
                $renderMethodParameters[$argumentName] = $this->arguments[$argumentName];
            }
        }

        try {
            return call_user_func_array([$this, 'render'], $renderMethodParameters);
        } catch (Exception $exception) {
            if (GeneralUtility::getApplicationContext()->isProduction()) {
                $this->getLogger()->error('A Fluid ViewHelper Exception was captured: ' . $exception->getMessage() . ' (' . $exception->getCode() . ')', ['exception' => $exception]);
                return '';
            }
            throw $exception;
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
     * Register method arguments for "render" by analysing the doc comment above.
     *
     * @throws \TYPO3Fluid\Fluid\Core\Parser\Exception
     */
    protected function registerRenderMethodArguments()
    {
        $methodParameters = $this->reflectionService->getMethodParameters(get_class($this), 'render');
        if (count($methodParameters) === 0) {
            return;
        }

        $methodTags = $this->reflectionService->getMethodTagsValues(get_class($this), 'render');

        $paramAnnotations = [];
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
