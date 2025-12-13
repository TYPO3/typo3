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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Fluid\Event\ModifyComponentDefinitionEvent;
use TYPO3\CMS\Fluid\Event\ProvideStaticVariablesToComponentEvent;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\Core\Component\ComponentAdapter;
use TYPO3Fluid\Fluid\Core\Component\ComponentDefinition;
use TYPO3Fluid\Fluid\Core\Component\ComponentDefinitionProviderInterface;
use TYPO3Fluid\Fluid\Core\Component\ComponentRendererInterface;
use TYPO3Fluid\Fluid\Core\Component\ComponentTemplateResolverInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\TemplateStructureViewHelperResolver;
use TYPO3Fluid\Fluid\Core\ViewHelper\UnresolvableViewHelperException;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolverDelegateInterface;

/**
 * @internal
 */
#[Autoconfigure(autowire: false)]
final readonly class DeclarativeComponentCollection implements ViewHelperResolverDelegateInterface, ComponentDefinitionProviderInterface, ComponentTemplateResolverInterface
{
    public function __construct(
        private FrontendInterface $cache,
        private EventDispatcherInterface $eventDispatcher,
        private string $namespace,
        private array $templatePaths,
        private string $templateNamePattern = '{path}/{name}/{name}',
        private bool $additionalArgumentsAllowed = false,
    ) {}

    public function withTemplateNamePattern(string $templateNamePattern): static
    {
        return new static($this->cache, $this->eventDispatcher, $this->namespace, $this->templatePaths, $templateNamePattern, $this->additionalArgumentsAllowed);
    }

    public function withAdditionalArgumentsAllowed(bool $additionalArgumentsAllowed): static
    {
        return new static($this->cache, $this->eventDispatcher, $this->namespace, $this->templatePaths, $this->templateNamePattern, $additionalArgumentsAllowed);
    }

    public function resolveTemplateName(string $viewHelperName): string
    {
        $fragments = array_map(ucfirst(...), explode('.', $viewHelperName));
        $name = array_pop($fragments);
        $path = implode('/', $fragments);
        return str_replace(['{path}', '{name}'], [$path, $name], $this->templateNamePattern);
    }

    public function getTemplatePaths(): TemplatePaths
    {
        $templatePaths = new TemplatePaths();
        $templatePaths->setTemplateRootPaths($this->templatePaths);
        return $templatePaths;
    }

    public function getAdditionalVariables(string $viewHelperName): array
    {
        // Allow to provide additional variables to the component template.
        // Note that this deliberately cannot depend on runtime characteristics,
        // such as the request, as this should be done in the renderer.
        $event = $this->eventDispatcher->dispatch(
            new ProvideStaticVariablesToComponentEvent($this, $viewHelperName)
        );
        return $event->getStaticVariables();
    }

    public function getComponentDefinition(string $viewHelperName): ComponentDefinition
    {
        $cacheIdentifier = hash('xxh3', $this->namespace . '--' . $viewHelperName);
        $componentDefinition = $this->cache->get($cacheIdentifier);
        if ($componentDefinition instanceof ComponentDefinition) {
            return $componentDefinition;
        }
        $templateName = $this->resolveTemplateName($viewHelperName);
        /**
         * Extract component definition from component template
         * This part is an ugly workaround because of shortcomings in the Fluid parser.
         * Once this has been resolved on the Fluid side, there will most likely be a better API.
         * @see \TYPO3Fluid\Fluid\Core\Component\AbstractComponentCollection
         */
        $renderingContext = new RenderingContext();
        $renderingContext->setViewHelperResolver(new TemplateStructureViewHelperResolver());
        $parsedTemplate = $renderingContext->getTemplateParser()->parse(
            $this->getTemplatePaths()->getTemplateSource('Default', $templateName),
            $this->getTemplatePaths()->getTemplateIdentifier('Default', $templateName),
        );
        $componentDefinition = new ComponentDefinition(
            $viewHelperName,
            $parsedTemplate->getArgumentDefinitions(),
            $this->additionalArgumentsAllowed,
            $parsedTemplate->getAvailableSlots(),
        );
        // Allow modification of component definition before it's written to cache.
        // Note that this deliberately cannot depend on runtime characteristics,
        // such as the request, as this should be done during rendering (e. g. by allowing
        // arbitrary arguments)
        $event = $this->eventDispatcher->dispatch(
            new ModifyComponentDefinitionEvent($this->namespace, $componentDefinition)
        );
        $componentDefinition = $event->getComponentDefinition();
        $this->cache->set($cacheIdentifier, $componentDefinition);
        return $componentDefinition;
    }

    public function getComponentRenderer(): ComponentRendererInterface
    {
        return new EventBasedComponentRenderer($this->eventDispatcher, $this);
    }

    public function resolveViewHelperClassName(string $name): string
    {
        $expectedTemplateName = $this->resolveTemplateName($name);
        if (!$this->getTemplatePaths()->resolveTemplateFileForControllerAndActionAndFormat('Default', $expectedTemplateName)) {
            throw new UnresolvableViewHelperException(sprintf(
                'Based on your spelling, the system would load the component template "%s.%s" in "%s", however this file does not exist.',
                $expectedTemplateName,
                $this->getTemplatePaths()->getFormat(),
                implode(', ', $this->getTemplatePaths()->getTemplateRootPaths()),
            ), 1765711586);
        }
        return ComponentAdapter::class;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }
}
