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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\AbstractTemplateView as Typo3FluidAbstractTemplateView;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * Abstract Fluid Template View.
 *
 * Contains the fundamental methods which any Fluid based template view needs.
 */
abstract class AbstractTemplateView extends Typo3FluidAbstractTemplateView
{
    /**
     * @internal
     */
    public function __construct(?RenderingContextInterface $context = null)
    {
        if (!$context) {
            $context = GeneralUtility::makeInstance(RenderingContextFactory::class)->create();
        }
        parent::__construct($context);
    }

    /**
     * @internal
     */
    public function setTemplate(string $templateName): void
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
