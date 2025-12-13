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

namespace TYPO3\CMS\Fluid\Core\Component;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Fluid\Event\RenderComponentEvent;
use TYPO3Fluid\Fluid\Core\Component\ComponentDefinitionProviderInterface;
use TYPO3Fluid\Fluid\Core\Component\ComponentRenderer as FluidComponentRenderer;
use TYPO3Fluid\Fluid\Core\Component\ComponentRendererInterface;
use TYPO3Fluid\Fluid\Core\Component\ComponentTemplateResolverInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolverDelegateInterface;

/**
 * @internal
 */
#[Autoconfigure(autowire: false)]
final readonly class EventBasedComponentRenderer implements ComponentRendererInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ViewHelperResolverDelegateInterface&ComponentDefinitionProviderInterface&ComponentTemplateResolverInterface $componentCollection,
    ) {}

    public function renderComponent(string $viewHelperName, array $arguments, array $slots, RenderingContextInterface $parentRenderingContext): string
    {
        $request =
            $parentRenderingContext->hasAttribute(ServerRequestInterface::class) ?
            $parentRenderingContext->getAttribute(ServerRequestInterface::class) :
            null;
        $event = $this->eventDispatcher->dispatch(
            new RenderComponentEvent($this->componentCollection, $viewHelperName, $arguments, $slots, $parentRenderingContext, $request)
        );
        if ($event->getRenderedComponent() !== null) {
            return $event->getRenderedComponent();
        }
        return (new FluidComponentRenderer($this->componentCollection))->renderComponent(
            $viewHelperName,
            $event->getArguments(),
            $event->getSlots(),
            $parentRenderingContext,
        );
    }
}
