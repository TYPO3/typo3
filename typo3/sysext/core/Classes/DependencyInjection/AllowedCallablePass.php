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
use TYPO3\CMS\Core\Security\AllowedCallableAssertion;

final readonly class AllowedCallablePass implements CompilerPassInterface
{
    public function __construct(private string $tagName) {}

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(AllowedCallableAssertion::class)) {
            return;
        }
        $definition = $container->findDefinition(AllowedCallableAssertion::class);
        $definition->setArgument('$items', $this->resolveItems($container));
    }

    /**
     * @return list<array{class-string, string}>
     */
    private function resolveItems(ContainerBuilder $container): array
    {
        $items = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            foreach ($tags as $tag) {
                $items[] = [$id, $tag['method']];
            }
        }
        return $items;
    }
}
