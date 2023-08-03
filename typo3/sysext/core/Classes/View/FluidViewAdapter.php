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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext as CoreRenderingContext;
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
        throw new \RuntimeException('view must be an instance of ext:fluid \TYPO3Fluid\Fluid\View\AbstractTemplateView', 1721578954);
    }

    public function setRenderingContext(RenderingContextInterface $renderingContext): void
    {
        if ($this->view instanceof FluidStandaloneAbstractTemplateView) {
            $this->view->setRenderingContext($renderingContext);
            // @todo: This is needed because $this->view->setRenderingContext() sets *its own view* as
            //        view within VariableContainer, but we want to keep *$this*. In the end, this entire
            //        thing is a loop dependency and getView() on ViewHelperVariableContainer should fully
            //        vanish somehow.
            $this->view->getRenderingContext()->getViewHelperVariableContainer()->setView($this);
            return;
        }
        throw new \RuntimeException('view must be an instance of ext:fluid \TYPO3Fluid\Fluid\View\AbstractTemplateView', 1721578954);
    }

    public function renderSection($sectionName, array $variables = [], $ignoreUnknown = false): string
    {
        if ($this->view instanceof FluidStandaloneAbstractTemplateView) {
            return $this->view->renderSection($sectionName, $variables, $ignoreUnknown);
        }
        throw new \RuntimeException('view must be an instance of ext:fluid \TYPO3Fluid\Fluid\View\AbstractTemplateView', 1721746411);
    }

    public function renderPartial($partialName, $sectionName, array $variables, $ignoreUnknown = false): string
    {
        if ($this->view instanceof FluidStandaloneAbstractTemplateView) {
            return $this->view->renderPartial($partialName, $sectionName, $variables, $ignoreUnknown);
        }
        throw new \RuntimeException('view must be an instance of ext:fluid \TYPO3Fluid\Fluid\View\AbstractTemplateView', 1721746412);
    }

    public function setTemplate(string $templateName): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->setControllerAction() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->setControllerAction($templateName);
    }

    public function initializeRenderingContext(): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getViewHelperVariableContainer()->setView($view) instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getViewHelperVariableContainer()->setView($this);
    }

    public function setCache(FluidCacheInterface $cache): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->setCache($cache) instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->setCache($cache);
    }

    public function getTemplatePaths(): TemplatePaths
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getTemplatePaths();
    }

    public function getViewHelperResolver(): ViewHelperResolver
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getViewHelperResolver() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getViewHelperResolver();
    }

    public function setTemplatePathAndFilename(string $templatePathAndFilename): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename($templatePathAndFilename);
    }

    public function setTemplateRootPaths(array $templateRootPaths): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setTemplateRootPaths() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setTemplateRootPaths($templateRootPaths);
    }

    public function getTemplateRootPaths(): array
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getTemplateRootPaths() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getTemplatePaths()->getTemplateRootPaths();
    }

    public function setPartialRootPaths(array $partialRootPaths): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setPartialRootPaths() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setPartialRootPaths($partialRootPaths);
    }

    public function getPartialRootPaths(): array
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getPartialRootPaths() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getTemplatePaths()->getPartialRootPaths();
    }

    public function getLayoutRootPaths(): array
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->getLayoutRootPaths() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRenderingContext()->getTemplatePaths()->getLayoutRootPaths();
    }

    public function setLayoutRootPaths(array $layoutRootPaths): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setLayoutRootPaths() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setLayoutRootPaths($layoutRootPaths);
    }

    public function setLayoutPathAndFilename(string $layoutPathAndFilename): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setLayoutPathAndFilename() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setLayoutPathAndFilename($layoutPathAndFilename);
    }

    public function setFormat(string $format): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getAttribute(ServerRequestInterface::class)->withFormat()'
            . ' and getRenderingContext()->getTemplatePaths()->setFormat() instead.',
            E_USER_DEPRECATED
        );
        if (!$this->getRenderingContext() instanceof CoreRenderingContext) {
            // @todo: This needs a transition towards hasAttribute()
            throw new \RuntimeException('The rendering context must be of type ' . CoreRenderingContext::class, 1482251886);
        }
        $request = $this->getRenderingContext()->getRequest();
        if ($request instanceof RequestInterface) {
            $request = $request->withFormat($format);
            $this->getRenderingContext()->setRequest($request);
        }
        $this->getRenderingContext()->getTemplatePaths()->setFormat($format);
    }

    public function setRequest(?ServerRequestInterface $request = null): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->setAttribute(ServerRequestInterface::class) instead.',
            E_USER_DEPRECATED
        );
        // @todo: This needs a transition towards setAttribute()
        if ($this->getRenderingContext() instanceof CoreRenderingContext) {
            $this->getRenderingContext()->setRequest($request);
        }
    }

    public function setTemplateSource(string $templateSource): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14. Use getRenderingContext()->getTemplatePaths()->setTemplateSource() instead.',
            E_USER_DEPRECATED
        );
        $this->getRenderingContext()->getTemplatePaths()->setTemplateSource($templateSource);
    }

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
