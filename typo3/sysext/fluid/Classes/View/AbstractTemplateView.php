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

namespace TYPO3\CMS\Fluid\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\AbstractTemplateView as Typo3FluidAbstractTemplateView;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * Abstract Fluid Template View.
 *
 * Contains the fundamental methods which any Fluid based template view needs.
 *
 * @todo v12: Drop 'implements ViewInterface' together with removal of extbase ViewInterface
 */
abstract class AbstractTemplateView extends Typo3FluidAbstractTemplateView implements ViewInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     * @deprecated since v11, will be removed with v12.
     */
    protected $controllerContext;

    /**
     * Initializes this view.
     *
     * @deprecated since v11, will be removed with v12. Drop together with removal of extbase ViewInterface.
     */
    public function initializeView()
    {
    }

    /**
     * @param RenderingContextInterface $context
     * @internal
     */
    public function __construct(RenderingContextInterface $context = null)
    {
        if (!$context) {
            $context = GeneralUtility::makeInstance(RenderingContextFactory::class)->create();
        }
        parent::__construct($context);
    }

    /**
     * Tells if the view implementation can render the view for the given context.
     *
     * By default we assume that the view implementation can handle all kinds of
     * contexts. Override this method if that is not the case.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext Controller context which is available inside the view
     * @return bool TRUE if the view has something useful to display, otherwise FALSE
     * @deprecated since TYPO3 v11, will be removed in v12. Legacy method, not part of ViewInterface anymore.
     */
    public function canRender(ControllerContext $controllerContext)
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v11 and will be removed with v12.', E_USER_DEPRECATED);
        return true;
    }

    /**
     * Sets the current controller context
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @internal
     * @deprecated since v11, will be removed with v12.
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $request = $controllerContext->getRequest();
        $this->controllerContext = $controllerContext;

        // @todo: Move these two lines elsewhere when dropping the method
        $this->baseRenderingContext->getTemplatePaths()->fillDefaultsByPackageName($request->getControllerExtensionKey());
        $this->baseRenderingContext->getTemplatePaths()->setFormat($request->getFormat());

        if ($this->baseRenderingContext instanceof RenderingContext) {
            $this->baseRenderingContext->setRequest($request);
            $this->baseRenderingContext->setControllerContext($controllerContext);
        }
    }

    /**
     * @param string $templateName
     * @internal
     */
    public function setTemplate($templateName)
    {
        $this->baseRenderingContext->setControllerAction($templateName);
    }

    /**
     * Sets the path and name of of the template file. Effectively overrides the
     * dynamic resolving of a template file.
     *
     * @param string $templatePathAndFilename Template file path
     */
    public function setTemplatePathAndFilename($templatePathAndFilename)
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplatePathAndFilename($templatePathAndFilename);
    }

    /**
     * Set the root path(s) to the templates.
     * If set, overrides the one determined from $this->templateRootPathPattern
     *
     * @param string[] $templateRootPaths Root path(s) to the templates. If set, overrides the one determined from $this->templateRootPathPattern
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplateRootPaths($templateRootPaths);
    }

    /**
     * Resolves the template root to be used inside other paths.
     *
     * @return string[] Path(s) to template root directory
     */
    public function getTemplateRootPaths()
    {
        return $this->baseRenderingContext->getTemplatePaths()->getTemplateRootPaths();
    }
    /**
     * Set the root path(s) to the partials.
     * If set, overrides the one determined from $this->partialRootPathPattern
     *
     * @param string[] $partialRootPaths Root paths to the partials. If set, overrides the one determined from $this->partialRootPathPattern
     */
    public function setPartialRootPaths(array $partialRootPaths)
    {
        $this->baseRenderingContext->getTemplatePaths()->setPartialRootPaths($partialRootPaths);
    }

    /**
     * Returns the absolute path to the folder that contains Fluid partial files
     *
     * @return string[] Fluid partial root paths
     * @throws InvalidTemplateResourceException
     */
    public function getPartialRootPaths()
    {
        return $this->baseRenderingContext->getTemplatePaths()->getPartialRootPaths();
    }

    /**
     * Resolves the layout root to be used inside other paths.
     *
     * @return string[] Fluid layout root paths
     * @throws InvalidTemplateResourceException
     */
    public function getLayoutRootPaths()
    {
        return $this->baseRenderingContext->getTemplatePaths()->getLayoutRootPaths();
    }

    /**
     * Set the root path(s) to the layouts.
     * If set, overrides the one determined from $this->layoutRootPathPattern
     *
     * @param string[] $layoutRootPaths Root path to the layouts. If set, overrides the one determined from $this->layoutRootPathPattern
     */
    public function setLayoutRootPaths(array $layoutRootPaths)
    {
        $this->baseRenderingContext->getTemplatePaths()->setLayoutRootPaths($layoutRootPaths);
    }
}
