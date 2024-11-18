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

use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\AbstractTemplateView as FluidStandaloneAbstractTemplateView;
use TYPO3Fluid\Fluid\View\TemplateAwareViewInterface as FluidStandaloneTemplateAwareViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidStandaloneViewInterface;

/**
 * A view adapter that handles a Typo3Fluid view and implements generic ext:core ViewInterface.
 *
 * @internal This is a specific view adapter is not considered part of the Public TYPO3 API.
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
        $renderedView = $this->view->render($templateFileName);
        if ($renderedView !== null && !is_scalar($renderedView) && !$renderedView instanceof \Stringable) {
            throw new \RuntimeException('The rendered Fluid view can not be turned into string', 1731959329);
        }
        return (string)$renderedView;
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
}
