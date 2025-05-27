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

namespace TYPO3\CMS\Backend\Template\Components;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbContext;
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbProviderInterface;
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;

/**
 * Breadcrumb component, building breadcrumbs for the backend doc header.
 *
 * This component uses a provider-based architecture:
 * - BreadcrumbProviderInterface implementations generate breadcrumb nodes
 * - Providers are selected based on the context type (record, resource, etc.)
 *
 * @internal This class is a specific Backend implementation and is not part of the TYPO3's Core API.
 */
#[Autoconfigure(public: true)]
final class Breadcrumb implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param iterable<BreadcrumbProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {}

    /**
     * Generates breadcrumb nodes from a breadcrumb context.
     *
     * @param ServerRequestInterface|null $request The current request for module detection
     * @param BreadcrumbContext|null $context The breadcrumb context containing main entity and suffix nodes
     * @return BreadcrumbNode[] Array of breadcrumb nodes
     */
    public function getBreadcrumb(?ServerRequestInterface $request, ?BreadcrumbContext $context): array
    {
        // Generate nodes from providers (works for both null and non-null context)
        $nodes = $this->generateNodesFromContext($context, $request);

        // Append suffix nodes only if context is not null
        if ($context !== null && $context->hasSuffixNodes()) {
            foreach ($context->suffixNodes as $suffixNode) {
                $nodes[] = $suffixNode;
            }
        }

        return $nodes;
    }

    /**
     * Generates breadcrumb nodes from a context using providers.
     *
     * @return BreadcrumbNode[]
     */
    private function generateNodesFromContext(?BreadcrumbContext $context, ?ServerRequestInterface $request): array
    {
        $provider = $this->findProvider($context);

        if ($provider === null) {
            $this->logger?->warning(
                'No breadcrumb provider found for context',
                ['context_type' => get_debug_type($context)]
            );
            return [];
        }

        try {
            return $provider->generate($context, $request);
        } catch (\Exception $e) {
            $this->logger?->error(
                'Failed to generate breadcrumb from provider',
                [
                    'provider' => get_class($provider),
                    'context_type' => get_debug_type($context),
                    'exception' => $e->getMessage(),
                ]
            );
            return [];
        }
    }

    /**
     * Finds the most suitable provider for the given context.
     *
     * Providers are checked in priority order (highest first).
     */
    private function findProvider(?BreadcrumbContext $context): ?BreadcrumbProviderInterface
    {
        $providers = iterator_to_array($this->providers);

        // Sort by priority (highest first)
        usort($providers, static fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        foreach ($providers as $provider) {
            if ($provider->supports($context)) {
                return $provider;
            }
        }

        return null;
    }
}
