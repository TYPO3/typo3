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

namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TYPO3\CMS\Core\Attribute\AsMetaTagManager;
use TYPO3\CMS\Core\DependencyInjection\MetaTagManagerPass;
use TYPO3\CMS\Core\MetaTag\EdgeMetaTagManager;
use TYPO3\CMS\Core\MetaTag\GenericMetaTagManager;
use TYPO3\CMS\Core\MetaTag\Html5MetaTagManager;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MetaTagManagerPassTest extends UnitTestCase
{
    private function buildContainer(array $taggedServices): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(MetaTagManagerRegistry::class, new Definition(MetaTagManagerRegistry::class));
        foreach ($taggedServices as $className => $tagAttributes) {
            $definition = new Definition($className);
            $definition->addTag(AsMetaTagManager::TAG_NAME, $tagAttributes);
            $container->setDefinition($className, $definition);
        }
        return $container;
    }

    #[Test]
    public function managersAreOrderedWithGenericManagerLast(): void
    {
        $container = $this->buildContainer([
            GenericMetaTagManager::class => ['identifier' => 'generic'],
            EdgeMetaTagManager::class => ['identifier' => 'edge', 'after' => 'html5'],
            Html5MetaTagManager::class => ['identifier' => 'html5'],
        ]);

        new MetaTagManagerPass(AsMetaTagManager::TAG_NAME)->process($container);

        $argument = $container->getDefinition(MetaTagManagerRegistry::class)->getArgument('$registeredManagers');
        self::assertInstanceOf(IteratorArgument::class, $argument);
        self::assertSame(['html5', 'edge', 'generic'], array_keys($argument->getValues()));
    }

    #[Test]
    public function taggedServiceNotImplementingMetaTagManagerInterfaceThrowsException(): void
    {
        $container = $this->buildContainer([
            \stdClass::class => ['identifier' => 'invalid'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1784904501);

        new MetaTagManagerPass(AsMetaTagManager::TAG_NAME)->process($container);
    }

    #[Test]
    public function taggedServiceWithoutIdentifierThrowsException(): void
    {
        $container = $this->buildContainer([
            Html5MetaTagManager::class => [],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1784904502);

        new MetaTagManagerPass(AsMetaTagManager::TAG_NAME)->process($container);
    }
}
