<?php

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

namespace TYPO3\CMS\Fluid\Core\Rendering;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheInterface;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\Configuration;
use TYPO3Fluid\Fluid\Core\Parser\InterceptorInterface;
use TYPO3Fluid\Fluid\Core\Parser\TemplateParser;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInvoker;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Class RenderingContext
 */
class RenderingContext extends \TYPO3Fluid\Fluid\Core\Rendering\RenderingContext
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $controllerName = 'Default';

    /**
     * @var string
     */
    protected $controllerAction = 'Default';

    /**
     * @internal constructor, use `RenderingContextFactory->create()` instead
     */
    public function __construct(
        ViewHelperResolver $viewHelperResolver,
        FluidCacheInterface $cache,
        array $templateProcessors,
        array $expressionNodeTypes
    ) {
        // Reproduced partial initialisation from parent::__construct; minus the custom implementations we attach below.
        $this->setTemplateParser(new TemplateParser());
        $this->setTemplateCompiler(new TemplateCompiler());
        $this->setViewHelperInvoker(new ViewHelperInvoker());
        $this->setViewHelperVariableContainer(new ViewHelperVariableContainer());
        $this->setVariableProvider(new StandardVariableProvider());

        $this->setTemplateProcessors($templateProcessors);

        $this->setExpressionNodeTypes($expressionNodeTypes);
        $this->setTemplatePaths(GeneralUtility::makeInstance(TemplatePaths::class));
        $this->setViewHelperResolver($viewHelperResolver);

        $this->setCache($cache);
    }

    /**
     * Alternative to buildParserConfiguration, called only in Fluid 3.0
     *
     * @return Configuration
     */
    public function getParserConfiguration(): Configuration
    {
        $parserConfiguration = parent::getParserConfiguration();
        $this->addInterceptorsToParserConfiguration($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['interceptors'], $parserConfiguration);
        return $parserConfiguration;
    }

    /**
     * Build parser configuration
     *
     * @return Configuration
     * @throws \InvalidArgumentException if a class not implementing InterceptorInterface was registered
     */
    public function buildParserConfiguration()
    {
        $parserConfiguration = parent::buildParserConfiguration();
        $this->addInterceptorsToParserConfiguration($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['interceptors'], $parserConfiguration);
        return $parserConfiguration;
    }

    protected function addInterceptorsToParserConfiguration(iterable $interceptors, Configuration $parserConfiguration): void
    {
        foreach ($interceptors as $className) {
            $interceptor = GeneralUtility::makeInstance($className);
            if (!$interceptor instanceof InterceptorInterface) {
                throw new \InvalidArgumentException('Interceptor "' . $className . '" needs to implement ' . InterceptorInterface::class . '.', 1462869795);
            }
            $parserConfiguration->addInterceptor($interceptor);
        }
    }

    /**
     * @param string $action
     */
    public function setControllerAction($action)
    {
        $dotPosition = strpos($action, '.');
        if ($dotPosition !== false) {
            $action = substr($action, 0, $dotPosition);
        }
        $this->controllerAction = $action;
        if ($this->request) {
            $this->request->setControllerActionName(lcfirst($action));
        }
    }

    /**
     * @param string $controllerName
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
     */
    public function setControllerName($controllerName)
    {
        $this->controllerName = $controllerName;
        if ($this->request instanceof Request) {
            $this->request->setControllerName($controllerName);
        }
    }

    /**
     * @return string
     */
    public function getControllerName()
    {
        return $this->request instanceof Request ? $this->request->getControllerName() : $this->controllerName;
    }

    /**
     * @return string
     */
    public function getControllerAction()
    {
        return $this->request instanceof Request ? $this->request->getControllerActionName() : $this->controllerAction;
    }

    /**
     * @param Request $request
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
     * @internal this might change to use a PSR-7 compliant request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
        $this->setControllerAction($request->getControllerActionName());
        $this->setControllerName($request->getControllerName());
    }

    /**
     * @return Request
     * @internal this might change to use a PSR-7 compliant request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return UriBuilder
     * @internal this is subject to change
     */
    public function getUriBuilder(): UriBuilder
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        return $uriBuilder;
    }
}
