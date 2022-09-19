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

namespace TYPO3\CMS\Fluid\View;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * A standalone template view.
 * Should be used as view if you want to use Fluid without Extbase extensions
 */
class StandaloneView extends AbstractTemplateView
{
    /**
     * Sets the format of the current request (default format is "html")
     *
     * @throws \RuntimeException
     */
    public function setFormat(string $format): void
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
     * @deprecated since v12, will be removed in v13: Views should be data sinks, not data sources. No substitution.
     */
    public function getFormat()
    {
        trigger_error(
            'Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13. Do not use StandaloneView as data source.',
            E_USER_DEPRECATED
        );
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
     * @deprecated since v12, will be removed in v13: Views should be data sinks, not data sources. No substitution.
     */
    public function getRequest(): ?ServerRequestInterface
    {
        trigger_error(
            'Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13. Do not use StandaloneView as data source.',
            E_USER_DEPRECATED
        );
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
     * @deprecated since v12, will be removed in v13: Views should be data sinks, not data sources. No substitution.
     */
    public function getTemplatePathAndFilename()
    {
        trigger_error(
            'Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13. Do not use StandaloneView as data source.',
            E_USER_DEPRECATED
        );
        $templatePaths = $this->baseRenderingContext->getTemplatePaths();
        return $templatePaths->resolveTemplateFileForControllerAndActionAndFormat(
            $this->baseRenderingContext->getControllerName(),
            $this->baseRenderingContext->getControllerAction(),
            $templatePaths->getFormat()
        );
    }

    /**
     * Sets a Fluid template source string directly.
     * You can use setTemplatePathAndFilename() alternatively if you only want to specify the template path
     */
    public function setTemplateSource(string $templateSource): void
    {
        $this->baseRenderingContext->getTemplatePaths()->setTemplateSource($templateSource);
    }

    /**
     * Checks whether a template can be resolved for the current request
     */
    public function hasTemplate(): bool
    {
        try {
            $this->baseRenderingContext->getTemplatePaths()->getTemplateSource(
                $this->baseRenderingContext->getControllerName(),
                $this->baseRenderingContext->getControllerAction()
            );
            return true;
        } catch (InvalidTemplateResourceException $_) {
            return false;
        }
    }
}
