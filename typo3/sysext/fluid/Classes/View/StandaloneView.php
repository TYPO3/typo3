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
 *
 * @deprecated: since TYPO3 v13, will be removed in v14. Use ext:core ViewFactoryInterface instead.
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
        $renderingContext = $this->baseRenderingContext;
        if ($renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $renderingContext->getAttribute(ServerRequestInterface::class);
            if ($request instanceof RequestInterface) {
                $request = $request->withFormat($format);
                $renderingContext->setAttribute(ServerRequestInterface::class, $request);
            }
        }
        $this->baseRenderingContext->getTemplatePaths()->setFormat($format);
    }

    /**
     * @internal
     */
    public function setRequest(?ServerRequestInterface $request = null): void
    {
        if ($request) {
            $this->baseRenderingContext->setAttribute(ServerRequestInterface::class, $request);
        }
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
        } catch (InvalidTemplateResourceException) {
            return false;
        }
    }
}
