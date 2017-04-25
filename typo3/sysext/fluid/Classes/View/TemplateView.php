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

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * The main template view. Should be used as view if you want Fluid Templating
 *
 * @api
 */
class TemplateView extends AbstractTemplateView
{
    /**
     * Init view
     */
    public function initializeView()
    {
    }

    /**
     * @param $templateName
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
     * @api
     */
    public function setTemplatePathAndFilename($templatePathAndFilename)
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplatePathAndFilename($templatePathAndFilename);
    }

    /**
     * Sets the path and name of the layout file. Overrides the dynamic resolving of the layout file.
     *
     * @param string $layoutPathAndFilename Path and filename of the layout file
     * @api
     */
    public function setLayoutPathAndFilename($layoutPathAndFilename)
    {
        $this->baseRenderingContext->getTemplatePaths()->setLayoutPathAndFilename($layoutPathAndFilename);
    }

    /**
     * Resolves the template root to be used inside other paths.
     *
     * @return array Path(s) to template root directory
     */
    public function getTemplateRootPaths()
    {
        return $this->baseRenderingContext->getTemplatePaths()->getTemplateRootPaths();
    }

    /**
     * Set the root path(s) to the templates.
     * If set, overrides the one determined from $this->templateRootPathPattern
     *
     * @param array $templateRootPaths Root path(s) to the templates. If set, overrides the one determined from $this->templateRootPathPattern
     * @api
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplateRootPaths($templateRootPaths);
    }

    /**
     * Set the root path(s) to the partials.
     * If set, overrides the one determined from $this->partialRootPathPattern
     *
     * @param array $partialRootPaths Root paths to the partials. If set, overrides the one determined from $this->partialRootPathPattern
     * @api
     */
    public function setPartialRootPaths(array $partialRootPaths)
    {
        $this->baseRenderingContext->getTemplatePaths()->setPartialRootPaths($partialRootPaths);
    }

    /**
     * Set the root path(s) to the layouts.
     * If set, overrides the one determined from $this->layoutRootPathPattern
     *
     * @param array $layoutRootPaths Root path to the layouts. If set, overrides the one determined from $this->layoutRootPathPattern
     * @api
     */
    public function setLayoutRootPaths(array $layoutRootPaths)
    {
        $this->baseRenderingContext->getTemplatePaths()->setLayoutRootPaths($layoutRootPaths);
    }

    /**
     * Checks whether a template can be resolved for the current request context.
     *
     * @param ControllerContext $controllerContext Controller context which is available inside the view
     * @return bool
     * @api
     */
    public function canRender(ControllerContext $controllerContext)
    {
        try {
            $request = $controllerContext->getRequest();
            $this->setControllerContext($controllerContext);
            $this->baseRenderingContext->getTemplatePaths()->setFormat($request->getFormat());
            $this->baseRenderingContext->getTemplatePaths()->getTemplateSource($request->getControllerName(), $request->getControllerActionName());
            return true;
        } catch (InvalidTemplateResourceException $e) {
            return false;
        }
    }
}
