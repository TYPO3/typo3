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

namespace TYPO3\CMS\Backend\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TYPO3\CMS\Backend\DependencyInjection\SidebarComponentsPass;
use TYPO3\CMS\Backend\Sidebar\ModuleMenuSidebarComponent;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentsRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SidebarComponentsPassTest extends UnitTestCase
{
    private const TAG_NAME = 'backend.sidebar.component';

    #[Test]
    public function componentsAreOrderedByBeforeDependency(): void
    {
        $container = $this->createContainerWithComponents([
            'component-a' => [
                'identifier' => 'component-a',
            ],
            'component-b' => [
                'identifier' => 'component-b',
                'before' => 'component-a',
            ],
        ]);

        $pass = new SidebarComponentsPass(self::TAG_NAME);
        $pass->process($container);

        $orderedIdentifiers = $this->getOrderedIdentifiers($container);

        self::assertSame(['component-b', 'component-a'], $orderedIdentifiers);
    }

    #[Test]
    public function componentsAreOrderedByAfterDependency(): void
    {
        $container = $this->createContainerWithComponents([
            'component-a' => [
                'identifier' => 'component-a',
            ],
            'component-c' => [
                'identifier' => 'component-c',
                'after' => 'component-a',
            ],
        ]);

        $pass = new SidebarComponentsPass(self::TAG_NAME);
        $pass->process($container);

        $orderedIdentifiers = $this->getOrderedIdentifiers($container);

        self::assertSame(['component-a', 'component-c'], $orderedIdentifiers);
    }

    #[Test]
    public function complexOrderingWithBeforeAndAfter(): void
    {
        // Register in random order: C (after A), A, B (before A)
        // Expected: B, A, C
        $container = $this->createContainerWithComponents([
            'component-c' => [
                'identifier' => 'component-c',
                'after' => 'component-a',
            ],
            'component-a' => [
                'identifier' => 'component-a',
            ],
            'component-b' => [
                'identifier' => 'component-b',
                'before' => 'component-a',
            ],
        ]);

        $pass = new SidebarComponentsPass(self::TAG_NAME);
        $pass->process($container);

        $orderedIdentifiers = $this->getOrderedIdentifiers($container);

        self::assertSame(['component-b', 'component-a', 'component-c'], $orderedIdentifiers);
    }

    #[Test]
    public function throwsExceptionForMissingIdentifier(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1765923036);

        $container = $this->createContainerWithComponents([
            'service-without-identifier' => [
                // Missing 'identifier'
            ],
        ]);

        $pass = new SidebarComponentsPass(self::TAG_NAME);
        $pass->process($container);
    }

    #[Test]
    public function throwsExceptionForInvalidComponentClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1734373001);

        $container = new ContainerBuilder();

        $registryDefinition = new Definition(SidebarComponentsRegistry::class);
        $container->setDefinition(SidebarComponentsRegistry::class, $registryDefinition);

        // Register a service with a class that doesn't implement SidebarComponentInterface
        $definition = new Definition(\stdClass::class);
        $definition->setAutoconfigured(true);
        $definition->addTag(self::TAG_NAME, [
            'identifier' => 'invalid-component',
        ]);
        $container->setDefinition('invalid-component', $definition);

        $pass = new SidebarComponentsPass(self::TAG_NAME);
        $pass->process($container);
    }

    /**
     * @param array<string, array<string, mixed>> $components Service ID => tag attributes
     */
    private function createContainerWithComponents(array $components): ContainerBuilder
    {
        $container = new ContainerBuilder();

        // Register the registry definition
        $registryDefinition = new Definition(SidebarComponentsRegistry::class);
        $container->setDefinition(SidebarComponentsRegistry::class, $registryDefinition);

        // Register component services with tags
        foreach ($components as $serviceId => $tagAttributes) {
            $definition = new Definition(ModuleMenuSidebarComponent::class);
            $definition->setAutoconfigured(true);
            $definition->addTag(self::TAG_NAME, $tagAttributes);
            $container->setDefinition($serviceId, $definition);
        }

        return $container;
    }

    private function getOrderedIdentifiers(ContainerBuilder $container): array
    {
        $registryDefinition = $container->findDefinition(SidebarComponentsRegistry::class);
        $componentsArgument = $registryDefinition->getArgument('$sidebarComponents');

        return array_keys($componentsArgument);
    }
}
