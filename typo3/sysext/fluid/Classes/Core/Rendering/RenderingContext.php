<?php

declare(strict_types=1);

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

class RenderingContext extends \TYPO3Fluid\Fluid\Core\Rendering\RenderingContext
{
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
        array $expressionNodeTypes,
        TemplatePaths $templatePaths,
    ) {
        // Partially cloning parent::__construct() but with custom implementations.
        $this->setTemplateParser(new TemplateParser());
        $this->setTemplateCompiler(new TemplateCompiler());
        $this->setViewHelperInvoker(new ViewHelperInvoker());
        $this->setViewHelperVariableContainer(new ViewHelperVariableContainer());
        $this->setVariableProvider(new StandardVariableProvider());
        $this->setTemplateProcessors($templateProcessors);
        $this->setExpressionNodeTypes($expressionNodeTypes);
        $this->setTemplatePaths($templatePaths);
        $this->setViewHelperResolver($viewHelperResolver);
        $this->setCache($cache);
    }

    /**
     * Build parser configuration. Adds custom fluid interceptors from configuration.
     *
     * @throws \InvalidArgumentException if a class not implementing InterceptorInterface was registered
     */
    public function buildParserConfiguration(): Configuration
    {
        $parserConfiguration = parent::buildParserConfiguration();
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['interceptors'] as $className) {
            $interceptor = GeneralUtility::makeInstance($className);
            if (!$interceptor instanceof InterceptorInterface) {
                throw new \InvalidArgumentException(
                    'Interceptor "' . $className . '" needs to implement ' . InterceptorInterface::class . '.',
                    1462869795
                );
            }
            $parserConfiguration->addInterceptor($interceptor);
        }
        return $parserConfiguration;
    }

    /**
     * @param string $action
     */
    public function setControllerAction($action): void
    {
        $dotPosition = strpos($action, '.');
        if ($dotPosition !== false) {
            $action = substr($action, 0, $dotPosition);
        }
        $this->controllerAction = $action;
    }

    /**
     * @param string $controllerName
     */
    public function setControllerName($controllerName): void
    {
        $this->controllerName = $controllerName;
    }

    public function getControllerName(): string
    {
        return $this->controllerName;
    }

    public function getControllerAction(): string
    {
        return $this->controllerAction;
    }
}
