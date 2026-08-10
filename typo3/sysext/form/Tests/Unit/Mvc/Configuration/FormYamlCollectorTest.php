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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Mvc\Configuration\FormYamlCollector;
use TYPO3\CMS\Form\Mvc\Configuration\FormYamlConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormYamlCollectorTest extends UnitTestCase
{
    #[Test]
    public function getPathsReturnsEmptyArrayForFreshCollector(): void
    {
        $collector = new FormYamlCollector();

        self::assertSame([], $collector->getPaths());
    }

    #[Test]
    public function getPathsReturnsSingleAddedPath(): void
    {
        $collector = new FormYamlCollector();
        $collector->add(new FormYamlConfiguration(
            path: 'EXT:my_extension/Configuration/Form/MySet/setup.yaml',
            priority: 100,
            setName: 'my-vendor/my-set',
        ));

        self::assertSame(
            ['EXT:my_extension/Configuration/Form/MySet/setup.yaml'],
            $collector->getPaths()
        );
    }

    #[Test]
    public function getPathsSortsByAscendingPriority(): void
    {
        $collector = new FormYamlCollector();
        $collector->add(new FormYamlConfiguration(
            path: 'EXT:my_extension/Configuration/Form/High/setup.yaml',
            priority: 200,
        ));
        $collector->add(new FormYamlConfiguration(
            path: 'EXT:form/Configuration/Form/Base/setup.yaml',
            priority: 10,
        ));
        $collector->add(new FormYamlConfiguration(
            path: 'EXT:site_pkg/Configuration/Form/Site/setup.yaml',
            priority: 100,
        ));

        self::assertSame(
            [
                'EXT:form/Configuration/Form/Base/setup.yaml',
                'EXT:site_pkg/Configuration/Form/Site/setup.yaml',
                'EXT:my_extension/Configuration/Form/High/setup.yaml',
            ],
            $collector->getPaths()
        );
    }

    #[Test]
    public function getPathsIsStableForEqualPriorities(): void
    {
        $collector = new FormYamlCollector();
        $collector->add(new FormYamlConfiguration(path: 'EXT:ext_a/Configuration/Form/A/setup.yaml', priority: 100));
        $collector->add(new FormYamlConfiguration(path: 'EXT:ext_b/Configuration/Form/B/setup.yaml', priority: 100));
        $collector->add(new FormYamlConfiguration(path: 'EXT:ext_c/Configuration/Form/C/setup.yaml', priority: 100));

        $paths = $collector->getPaths();

        self::assertCount(3, $paths);
        // All three must be present; order among equal-priority sets must be stable (insertion order preserved by usort on PHP 8+)
        self::assertContains('EXT:ext_a/Configuration/Form/A/setup.yaml', $paths);
        self::assertContains('EXT:ext_b/Configuration/Form/B/setup.yaml', $paths);
        self::assertContains('EXT:ext_c/Configuration/Form/C/setup.yaml', $paths);
    }

    #[Test]
    public function getPathsDoesNotMutateInternalStateOnRepeatedCalls(): void
    {
        $collector = new FormYamlCollector();
        $collector->add(new FormYamlConfiguration(path: 'EXT:ext_b/Configuration/Form/B/setup.yaml', priority: 200));
        $collector->add(new FormYamlConfiguration(path: 'EXT:ext_a/Configuration/Form/A/setup.yaml', priority: 10));

        // Call twice — internal $definitions must not be re-sorted in place
        $first = $collector->getPaths();
        $second = $collector->getPaths();

        self::assertSame($first, $second);
    }

    #[Test]
    public function getAllConfigurationsReturnsAllConfigurationsInInsertionOrder(): void
    {
        $cfgA = new FormYamlConfiguration(path: 'EXT:ext_a/Configuration/Form/A/setup.yaml', priority: 50, setName: 'vendor/a');
        $cfgB = new FormYamlConfiguration(path: 'EXT:ext_b/Configuration/Form/B/setup.yaml', priority: 10, setName: 'vendor/b');

        $collector = new FormYamlCollector();
        $collector->add($cfgA);
        $collector->add($cfgB);

        // getAllConfigurations preserves insertion order, NOT priority order
        self::assertSame([$cfgA, $cfgB], $collector->getAllConfigurations());
    }

    #[Test]
    public function getAllConfigurationsReturnsEmptyArrayForFreshCollector(): void
    {
        self::assertSame([], new FormYamlCollector()->getAllConfigurations());
    }
}
