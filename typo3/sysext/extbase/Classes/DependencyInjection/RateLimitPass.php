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

namespace TYPO3\CMS\Extbase\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Extbase\Attribute\RateLimit;
use TYPO3\CMS\Extbase\Mvc\Controller\RateLimitRegistry;

/**
 * Scans all extbase action controllers for #[RateLimit] attributes
 * and registers their configurations in the {@see RateLimitRegistry}.
 *
 * @internal
 */
final readonly class RateLimitPass implements CompilerPassInterface
{
    public function __construct(private string $tagName) {}

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(RateLimitRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(RateLimitRegistry::class);

        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $definition = $container->findDefinition($serviceName);
            if ($definition->isAbstract()) {
                continue;
            }

            $className = $definition->getClass() ?? $serviceName;
            $reflectionClass = $container->getReflectionClass($className);
            if ($reflectionClass === null) {
                continue;
            }

            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (!str_ends_with($method->getName(), 'Action')) {
                    continue;
                }

                $attributes = $method->getAttributes(RateLimit::class);
                if ($attributes === []) {
                    continue;
                }

                $rateLimit = $attributes[0]->newInstance();
                $registryDefinition->addMethodCall('add', [
                    $className,
                    $method->getName(),
                    $rateLimit->limit,
                    $rateLimit->interval,
                    $rateLimit->policy,
                    $rateLimit->message,
                ]);
            }
        }
    }
}
