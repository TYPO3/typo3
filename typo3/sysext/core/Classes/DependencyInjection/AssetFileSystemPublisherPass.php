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

namespace TYPO3\CMS\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\SystemResource\Publishing\DefaultSystemResourcePublisher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
final readonly class AssetFileSystemPublisherPass implements CompilerPassInterface
{
    private DependencyOrderingService $orderingService;

    public function __construct(private string $tagName)
    {
        $this->orderingService = new DependencyOrderingService();
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(DefaultSystemResourcePublisher::class)) {
            // If there's no default system resource publisher registered to begin with, don't bother registering file system publishers with it.
            return;
        }
        $publishers = [];
        $unorderedPublishers = $this->collectPublishers($container);
        foreach ($this->orderingService->orderByDependencies($unorderedPublishers) as $publisher) {
            $publishers[] = $container->findDefinition($publisher['service']);
        }
        $publisherDefinition = $container->findDefinition(DefaultSystemResourcePublisher::class);
        $publisherDefinition->addArgument($publishers);
    }

    /**
     * Collects all listeners from the container.
     */
    private function collectPublishers(ContainerBuilder $container): array
    {
        $unorderedPublishers = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            foreach ($tags as $attributes) {
                $publisherIdentifier = $attributes['identifier'] ?? $serviceName;
                $unorderedPublishers[$publisherIdentifier] = [
                    'service' => $serviceName,
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                ];
            }
        }
        return $unorderedPublishers;
    }
}
