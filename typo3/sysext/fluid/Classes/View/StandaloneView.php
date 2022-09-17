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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * A standalone template view.
 * Should be used as view if you want to use Fluid without Extbase extensions
 */
class StandaloneView extends AbstractTemplateView
{
    public function __construct()
    {
        $renderingContext = GeneralUtility::makeInstance(RenderingContextFactory::class)->create();
        // @todo: This is very unfortunate. This creates an extbase request by default. Standalone
        //        usage is typically *not* extbase context. Controllers that want to get rid of this
        //        have to ->setRequest($myServerRequestInterface), or even ->setRequest(null) after
        //        object construction to get rid of an extbase request again.
        $renderingContext->setRequest(GeneralUtility::makeInstance(Request::class));
        parent::__construct($renderingContext);
    }

    /**
     * Sets the format of the current request (default format is "html")
     *
     * @param string $format
     * @throws \RuntimeException
     */
    public function setFormat($format)
    {
        if (!$this->baseRenderingContext instanceof RenderingContext) {
            throw new \RuntimeException('The rendering context must be of type ' . RenderingContext::class, 1482251886);
        }
        $request = $this->baseRenderingContext->getRequest();
        if ($request instanceof RequestInterface) {
            $request = $request->withFormat($format);
            $this->baseRenderingContext->setRequest($request);
        }
        $this->baseRenderingContext->getTemplatePaths()->setFormat($format);
    }

    /**
     * Returns the format of the current request (defaults is "html")
     *
     * @return string $format
     * @throws \RuntimeException
     * @todo: deprecate?!
     */
    public function getFormat()
    {
        if ($this->baseRenderingContext instanceof RenderingContext) {
            return $this->baseRenderingContext->getRequest()->getFormat();
        }
        throw new \RuntimeException('The rendering context must be of type ' . RenderingContext::class, 1482251887);
    }

    /**
     * @internal Currently used especially in functional tests. May change.
     */
    public function setRequest(?ServerRequestInterface $request = null): void
    {
        if ($this->baseRenderingContext instanceof RenderingContext) {
            $this->baseRenderingContext->setRequest($request);
        }
    }

    /**
     * Returns the current request object
     *
     * @throws \RuntimeException
     * @internal
     * @todo: deprecate?!
     */
    public function getRequest(): ?ServerRequestInterface
    {
        if ($this->baseRenderingContext instanceof RenderingContext) {
            return $this->baseRenderingContext->getRequest();
        }
        throw new \RuntimeException('The rendering context must be of type ' . RenderingContext::class, 1482251888);
    }

    /**
     * Returns the absolute path to a Fluid template file if it was specified with setTemplatePathAndFilename() before.
     * If the template filename was never specified, Fluid attempts to resolve the file based on controller and action.
     *
     * NB: If TemplatePaths was previously told to use the specific template path and filename it will short-circuit
     * and return that template path and filename directly, instead of attempting to resolve it.
     *
     * @return string Fluid template path
     */
    public function getTemplatePathAndFilename()
    {
        $templatePaths = $this->baseRenderingContext->getTemplatePaths();
        return $templatePaths->resolveTemplateFileForControllerAndActionAndFormat(
            $this->baseRenderingContext->getControllerName(),
            $this->baseRenderingContext->getControllerAction(),
            $templatePaths->getFormat()
        );
    }

    /**
     * Sets the Fluid template source
     * You can use setTemplatePathAndFilename() alternatively if you only want to specify the template path
     *
     * @param string $templateSource Fluid template source code
     */
    public function setTemplateSource($templateSource)
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplateSource($templateSource);
    }

    /**
     * Checks whether a template can be resolved for the current request
     *
     * @return bool
     */
    public function hasTemplate()
    {
        try {
            $this->baseRenderingContext->getTemplatePaths()->getTemplateSource(
                $this->baseRenderingContext->getControllerName(),
                $this->baseRenderingContext->getControllerAction()
            );
            return true;
        } catch (InvalidTemplateResourceException $e) {
            return false;
        }
    }
}
