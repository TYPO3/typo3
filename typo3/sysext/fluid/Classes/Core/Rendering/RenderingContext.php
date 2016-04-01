<?php
namespace TYPO3\CMS\Fluid\Core\Rendering;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache;
use TYPO3\CMS\Fluid\Core\Parser\PreProcessor\XmlnsNamespaceTemplatePreProcessor;
use TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\Expression\LegacyNamespaceExpressionNode;
use TYPO3\CMS\Fluid\Core\Variables\CmsVariableProvider;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\CastingExpressionNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\MathExpressionNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\Expression\TernaryExpressionNode;
use TYPO3Fluid\Fluid\Core\Parser\TemplateParser;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInvoker;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Class RenderingContext
 */
class RenderingContext extends \TYPO3Fluid\Fluid\Core\Rendering\RenderingContext
{
    /**
     * Template Variable Container. Contains all variables available through object accessors in the template
     *
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer
     */
    protected $templateVariableContainer;

    /**
     * Object manager which is bubbled through. The ViewHelperNode cannot get an ObjectManager injected because
     * the whole syntax tree should be cacheable
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Controller context being passed to the ViewHelper
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * ViewHelper Variable Container
     *
     * @var \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer
     */
    protected $viewHelperVariableContainer;

    /**
     * Use legacy behavior? Can be overridden using setLegacyMode().
     *
     * @var bool
     */
    protected $legacyMode = false;

    /**
     * List of class names implementing ExpressionNodeInterface
     * which will be consulted when an expression does not match
     * any built-in parser expression types.
     *
     * @var string
     */
    protected $expressionNodeTypes = [
        LegacyNamespaceExpressionNode::class,
        CastingExpressionNode::class,
        MathExpressionNode::class,
        TernaryExpressionNode::class
    ];

    /**
     * Alternative ExpressionNodeInterface implementers for use
     * when put into legacy mode.
     *
     * @var string
     */
    protected $legacyExpressionNodeTypes = [
        LegacyNamespaceExpressionNode::class
    ];

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer $viewHelperVariableContainer
     */
    public function injectViewHelperVariableContainer(\TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer $viewHelperVariableContainer)
    {
        $this->viewHelperVariableContainer = $viewHelperVariableContainer;
    }

    /**
     * @param ViewInterface $view
     */
    public function __construct(ViewInterface $view = null)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        if ($view) {
            $this->view = $view;
        }
        $this->setTemplateParser(new TemplateParser());
        $this->setTemplateCompiler(new TemplateCompiler());
        $this->setViewHelperInvoker(new ViewHelperInvoker());
        $this->setViewHelperVariableContainer(new ViewHelperVariableContainer());
        $this->setTemplatePaths($objectManager->get(TemplatePaths::class));
        $this->setViewHelperResolver($objectManager->get(ViewHelperResolver::class));
        $this->setVariableProvider($objectManager->get(CmsVariableProvider::class));
        $this->setTemplateProcessors(array(
            $objectManager->get(XmlnsNamespaceTemplatePreProcessor::class),
        ));
        /** @var FluidTemplateCache $cache */
        $cache = $objectManager->get(CacheManager::class)->getCache('fluid_template');
        if (is_a($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template']['frontend'], FluidTemplateCache::class, true)) {
            $this->setCache($cache);
        }
    }

    /**
     * Set legacy compatibility mode on/off by boolean.
     * If set to FALSE, the ViewHelperResolver will only load a limited sub-set of ExpressionNodes,
     * making Fluid behave like the legacy version of the CMS core extension.
     *
     * @param bool $legacyMode
     * @return void
     */
    public function setLegacyMode($legacyMode)
    {
        $this->legacyMode = $legacyMode;
    }

    /**
     * @return string
     */
    public function getExpressionNodeTypes()
    {
        return $this->legacyMode ? $this->legacyExpressionNodeTypes : $this->expressionNodeTypes;
    }

    /**
     * Returns the object manager. Only the ViewHelperNode should do this.
     *
     * @return \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Get the template variable container (DEPRECATED; use getVariableProvider instead)
     *
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9 - use getVariableProvider instead
     * @see getVariableProvider
     * @return \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer The Template Variable Container
     */
    public function getTemplateVariableContainer()
    {
        GeneralUtility::deprecationLog(
            'getTemplateVariableContainer is deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9' .
            ' - use getVariableProvider instead'
        );
        return $this->variableProvider;
    }

    /**
     * Get the controller context which will be passed to the ViewHelper
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext The controller context to set
     */
    public function getControllerContext()
    {
        return $this->controllerContext;
    }

    /**
     * @param string $action
     * @return void
     */
    public function setControllerAction($action)
    {
        $action = lcfirst(pathinfo($action, PATHINFO_FILENAME));
        parent::setControllerAction($action);
        $this->controllerContext->getRequest()->setControllerActionName($action);
    }

    /**
     * @param string $controllerName
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException
     */
    public function setControllerName($controllerName)
    {
        parent::setControllerName($controllerName);
        $this->controllerContext->getRequest()->setControllerName($controllerName);
    }

    /**
     * Set the controller context which will be passed to the ViewHelper
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext The controller context to set
     */
    public function setControllerContext(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext)
    {
        $request = $controllerContext->getRequest();
        $this->controllerContext = $controllerContext;
        $this->setControllerAction($request->getControllerActionName());
        // Check if Request is using a sub-package key; in which case we translate this
        // for our RenderingContext as an emulated plain old sub-namespace controller.
        $controllerName = $request->getControllerName();
        if ($request->getControllerSubpackageKey() && !strpos($controllerName, '\\')) {
            $this->setControllerName($request->getControllerSubpackageKey() . '\\' . $controllerName);
        } else {
            $this->setControllerName($controllerName);
        }
    }

    /**
     * Get the ViewHelperVariableContainer
     *
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer
     */
    public function getViewHelperVariableContainer()
    {
        return $this->viewHelperVariableContainer;
    }
}
