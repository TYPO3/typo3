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

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Messenger\BusFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
final class MessengerMiddlewarePass implements CompilerPassInterface
{
    private string $tagName;

    private DependencyOrderingService $orderer;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
        $this->orderer = new DependencyOrderingService();
    }

    public function process(ContainerBuilder $container): void
    {
        $busFactory = $container->findDefinition(BusFactory::class);
        $groupedMiddlewares = $this->collectMiddlewares($container);
        $middlewares = [];
        foreach ($groupedMiddlewares as $bus => $unorderedMiddlewares) {
            $middlewares[$bus] = [];
            foreach ($this->orderer->orderByDependencies($unorderedMiddlewares) as $middleware) {
                $middlewares[$bus][] = new Reference($middleware['service']);
            }
        }
        $busFactory->setArgument('$middlewares', array_map(
            fn (array $busMiddlewares): IteratorArgument => new IteratorArgument($busMiddlewares),
            $middlewares
        ));
    }

    /**
     * Collects all messenger middlewares from the container and prepares them for ordering
     */
    private function collectMiddlewares(ContainerBuilder $container): array
    {
        $unorderedMiddlewares = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $service = $container->findDefinition($serviceName);
            foreach ($tags as $attributes) {
                $bus = $attributes['bus'] ?? 'default';
                $unorderedMiddlewares[$bus][$serviceName] = [
                    'service' => $serviceName,
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                ];
            }
        }
        return $unorderedMiddlewares;
    }
}
