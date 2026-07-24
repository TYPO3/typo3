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
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;

/**
 * Compiler pass to validate tagged text extractors at container compile time.
 *
 * @internal
 */
final class TextExtractorPass implements CompilerPassInterface
{
    public function __construct(private readonly string $tagName) {}

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);
            if ($definition->isAbstract()) {
                continue;
            }
            $className = $definition->getClass() ?? $id;
            if (!is_a($className, TextExtractorInterface::class, true)) {
                throw new \InvalidArgumentException(
                    'Service "' . $id . '" is tagged as "' . $this->tagName . '", but its class "' . $className . '" does not implement ' . TextExtractorInterface::class . '.',
                    1784879763
                );
            }
        }
    }
}
