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

namespace TYPO3\CMS\Core\View;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3Fluid\Fluid\View\AbstractTemplateView as FluidStandaloneAbstractTemplateView;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;
use TYPO3Fluid\Fluid\View\TemplateAwareViewInterface as FluidStandaloneTemplateAwareViewInterface;
use TYPO3Fluid\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidStandaloneViewInterface;

/**
 * A view adapter that handles a Typo3Fluid view and implements generic ext:core ViewInterface.
 */
readonly class FluidViewAdapter implements CoreViewInterface, FluidStandaloneViewInterface, FluidStandaloneTemplateAwareViewInterface
{
    public function __construct(
        protected FluidStandaloneViewInterface&FluidStandaloneTemplateAwareViewInterface $view,
    ) {}

    public function assign(string $key, mixed $value): self
    {
        $this->view->assign($key, $value);
        return $this;
    }

    public function assignMultiple(array $values): self
    {
        $this->view->assignMultiple($values);
        return $this;
    }

    public function render(string $templateFileName = ''): string
    {
        return $this->view->render($templateFileName);
    }

    public function getRenderingContext(): RenderingContextInterface
    {
        if ($this->view instanceof FluidStandaloneAbstractTemplateView) {
            return $this->view->getRenderingContext();
        }
        throw new \RuntimeException('view must be an instance of ext:fluid \TYPO3Fluid\Fluid\View\AbstractTemplateView', 1721889095);
    }

    public function setRenderingContext(RenderingContextInterface $renderingContext): void
    {
        if ($this->view instanceof FluidStandaloneAbstractTemplateView) {
            $this->view->setRenderingContext($renderingContext);
            return;
        }
        throw new \RuntimeException('view must be an instance of ext:fluid \TYPO3Fluid\Fluid\View\AbstractTemplateView', 1721578954);
    }

    public function renderSection($sectionName, array $variables = [], $ignoreUnknown = false): mixed
    {
        if ($this->view instanceof FluidStandaloneAbstractTemplateView) {
            return $this->view->renderSection($sectionName, $variables, $ignoreUnknown);
        }
        throw new \RuntimeException('view must be an instance of ext:fluid \TYPO3Fluid\Fluid\View\AbstractTemplateView', 1721746411);
    }

    public function renderPartial($partialName, $sectionName, array $variables, $ignoreUnknown = false): mixed
    {
        if ($this->view instanceof FluidStandaloneAbstractTemplateView) {
            return $this->view->renderPartial($partialName, $sectionName, $variables, $ignoreUnknown);
        }
        throw new \RuntimeException('view must be an instance of ext:fluid \TYPO3Fluid\Fluid\View\AbstractTemplateView', 1721746412);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->setControllerAction() instead.
     */
    public function setTemplate(string $templateName): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->setControllerAction() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->setControllerAction($templateName);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getViewHelperVariableContainer()->setView($view) instead.
     */
    public function initializeRenderingContext(): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getViewHelperVariableContainer()->setView($view) instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getViewHelperVariableContainer()->setView($this);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->setCache($cache) instead.
     */
    public function setCache(FluidCacheInterface $cache): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->setCache($cache) instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->setCache($cache);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths() instead.
     */
    public function getTemplatePaths(): TemplatePaths
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getTemplatePaths();
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getViewHelperResolver() instead.
     */
    public function getViewHelperResolver(): ViewHelperResolver
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getViewHelperResolver() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getViewHelperResolver();
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename() instead.
     */
    public function setTemplatePathAndFilename(string $templatePathAndFilename): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename($templatePathAndFilename);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setTemplateRootPaths() instead.
     */
    public function setTemplateRootPaths(array $templateRootPaths): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setTemplateRootPaths() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setTemplateRootPaths($templateRootPaths);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getTemplateRootPaths() instead.
     */
    public function getTemplateRootPaths(): array
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getTemplateRootPaths() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getTemplatePaths()->getTemplateRootPaths();
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setPartialRootPaths() instead.
     */
    public function setPartialRootPaths(array $partialRootPaths): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setPartialRootPaths() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setPartialRootPaths($partialRootPaths);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getPartialRootPaths() instead.
     */
    public function getPartialRootPaths(): array
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getPartialRootPaths() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getTemplatePaths()->getPartialRootPaths();
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getLayoutRootPaths() instead.
     */
    public function getLayoutRootPaths(): array
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getLayoutRootPaths() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getTemplatePaths()->getLayoutRootPaths();
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setLayoutRootPaths() instead.
     */
    public function setLayoutRootPaths(array $layoutRootPaths): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setLayoutRootPaths() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setLayoutRootPaths($layoutRootPaths);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setLayoutPathAndFilename() instead.
     */
    public function setLayoutPathAndFilename(string $layoutPathAndFilename): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setLayoutPathAndFilename() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setLayoutPathAndFilename($layoutPathAndFilename);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getAttribute(ServerRequestInterface::class)->withFormat() and getRenderingContext()->getTemplatePaths()->setFormat() instead.
     */
    public function setFormat(string $format): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getAttribute(ServerRequestInterface::class)->withFormat()'
            . ' and getRenderingContext()->getTemplatePaths()->setFormat() instead.',
            E_USER_DEPRECATED
        );
        $renderingContext = $this->getRenderingContext();
        if ($renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $renderingContext->getAttribute(ServerRequestInterface::class);
            if ($request instanceof RequestInterface) {
                $request = $request->withFormat($format);
                $renderingContext->setAttribute(ServerRequestInterface::class, $request);
            }
        }
        $this->getRenderingContext()->getTemplatePaths()->setFormat($format);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->setAttribute(ServerRequestInterface::class) instead.
     */
    public function setRequest(?ServerRequestInterface $request = null): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->setAttribute(ServerRequestInterface::class) instead.',
            E_USER_DEPRECATED
        );
        if ($request) {
            $this->getRenderingContext()->setAttribute(ServerRequestInterface::class, $request);
        }
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setTemplateSource() instead.
     */
    public function setTemplateSource(string $templateSource): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setTemplateSource() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setTemplateSource($templateSource);
    }

    /**
     * @deprecated Will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getTemplateSource() instead.
     */
    public function hasTemplate(): bool
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getTemplateSource() instead.',
            E_USER_DEPRECATED
        );
        try {
            $this->getRenderingContext()->getTemplatePaths()->getTemplateSource(
                $this->getRenderingContext()->getControllerName(),
                $this->getRenderingContext()->getControllerAction()
            );
            return true;
        } catch (InvalidTemplateResourceException) {
            return false;
        }
    }
}
