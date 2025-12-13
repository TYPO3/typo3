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

namespace TYPO3\CMS\Fluid\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3Fluid\Fluid\Core\Component\ComponentDefinitionProviderInterface;
use TYPO3Fluid\Fluid\Core\Component\ComponentTemplateResolverInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolverDelegateInterface;

/**
 * Event to modify/replace the rendering behavior of Fluid components.
 * This can be used both to take over the whole rendering (by filling the
 * $renderedContent property) and to introduce side effects while leaving
 * the default rendering intact, like for embedding related frontent assets
 * automatically. Also, both arguments and slots can be altered.
 *
 * The event chain stops with the first listener that takes over the
 * rendering completely. If no listener stops the chain, Fluid's default
 * component rendering will be triggered afterwards.
 *
 * Note that the provided parentRenderingContext must not be used to
 * render child templates as this might have side effects on the parent
 * template. Instead, a new rendering context (or a clone of the parent)
 * must be used.
 *
 * @see \TYPO3Fluid\Fluid\Core\Component\ComponentRenderer
 */
final class RenderComponentEvent implements StoppableEventInterface
{
    private ?string $renderedComponent = null;

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, callable> $slots
     */
    public function __construct(
        private readonly ViewHelperResolverDelegateInterface&ComponentDefinitionProviderInterface&ComponentTemplateResolverInterface $componentCollection,
        private readonly string $viewHelperName,
        private array $arguments,
        private array $slots,
        private readonly RenderingContextInterface $parentRenderingContext,
        private readonly ?ServerRequestInterface $request,
    ) {}

    public function getComponentCollection(): ViewHelperResolverDelegateInterface&ComponentDefinitionProviderInterface&ComponentTemplateResolverInterface
    {
        return $this->componentCollection;
    }

    public function getViewHelperName(): string
    {
        return $this->viewHelperName;
    }

    public function getParentRenderingContext(): RenderingContextInterface
    {
        return $this->parentRenderingContext;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    /**
     * @return array<string, callable>
     */
    public function getSlots(): array
    {
        return $this->slots;
    }

    /**
     * @param array<string, callable> $slots
     */
    public function setSlots(array $slots): void
    {
        $this->slots = $slots;
    }

    public function setRenderedComponent(string $renderedComponent): void
    {
        $this->renderedComponent = $renderedComponent;
    }

    public function getRenderedComponent(): ?string
    {
        return $this->renderedComponent;
    }

    public function isPropagationStopped(): bool
    {
        return $this->renderedComponent !== null;
    }
}
