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

use League\Flysystem\FilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Attribute\AsFileStorageDriver;
use TYPO3\CMS\Core\DependencyInjection\FileStorageDriverPass;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\Driver\FilesystemAdapterFactoryInterface;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileStorageDriverPassTest extends UnitTestCase
{
    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(DriverRegistry::class, new Definition(DriverRegistry::class));
        return $container;
    }

    private function addTaggedService(ContainerBuilder $container, string $id, string $className, string $identifier): void
    {
        $definition = new Definition($className);
        $definition->addTag(AsFileStorageDriver::TAG_NAME, ['identifier' => $identifier]);
        $container->setDefinition($id, $definition);
    }

    #[Test]
    public function driverClassesAndFactoriesAreHandedToTheRegistry(): void
    {
        $adapterFactory = new class implements FilesystemAdapterFactoryInterface {
            public function createAdapter(array $configuration): FilesystemAdapter
            {
                throw new \BadMethodCallException('Not implemented', 1792161905);
            }
        };
        $container = $this->createContainer();
        $this->addTaggedService($container, LocalDriver::class, LocalDriver::class, 'Local');
        $this->addTaggedService($container, 'adapter-factory', $adapterFactory::class, 'S3');

        new FileStorageDriverPass(AsFileStorageDriver::TAG_NAME)->process($container);

        $registry = $container->getDefinition(DriverRegistry::class);
        self::assertSame(['Local' => LocalDriver::class], $registry->getArgument('$driverClasses'));
        self::assertInstanceOf(Reference::class, $registry->getArgument('$driverFactories'));
    }

    #[Test]
    public function taggedServiceOfOtherClassThrowsException(): void
    {
        $container = $this->createContainer();
        $this->addTaggedService($container, \stdClass::class, \stdClass::class, 'Other');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1792161903);
        new FileStorageDriverPass(AsFileStorageDriver::TAG_NAME)->process($container);
    }

    #[Test]
    public function identifierRegisteredTwiceThrowsException(): void
    {
        $container = $this->createContainer();
        $this->addTaggedService($container, 'first', LocalDriver::class, 'Local');
        $this->addTaggedService($container, 'second', LocalDriver::class, 'Local');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1792161902);
        new FileStorageDriverPass(AsFileStorageDriver::TAG_NAME)->process($container);
    }
}
