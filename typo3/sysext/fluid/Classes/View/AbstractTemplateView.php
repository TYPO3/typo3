<?php
namespace TYPO3\CMS\Fluid\View;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Abstract Fluid Template View.
 *
 * Contains the fundamental methods which any Fluid based template view needs.
 */
abstract class AbstractTemplateView extends TemplateView implements \TYPO3\CMS\Extbase\Mvc\View\ViewInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * Initializes this view.
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
            $context = GeneralUtility::makeInstance(ObjectManager::class)->get(RenderingContext::class, $this);
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
     */
    public function canRender(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext)
    {
        return true;
    }

    /**
     * Sets the current controller context
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @internal
     */
    public function setControllerContext(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext)
    {
        $request = $controllerContext->getRequest();
        $this->controllerContext = $controllerContext;
        $this->baseRenderingContext->getTemplatePaths()->fillDefaultsByPackageName($request->getControllerExtensionKey());
        $this->baseRenderingContext->getTemplatePaths()->setFormat($request->getFormat());
        if ($this->baseRenderingContext instanceof RenderingContext) {
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
