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

namespace TYPO3\CMS\Core\Tests\Functional\Site\Set;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SetRegistryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_sets',
    ];

    #[Test]
    public function setsRegisteredInSetRegistry(): void
    {
        $setRegistry = $this->get(SetRegistry::class);

        self::assertTrue($setRegistry->hasSet('typo3tests/set-1'));
        self::assertInstanceOf(SetDefinition::class, $setRegistry->getSet('typo3tests/set-1'));
    }

    #[Test]
    public function setDependenciesAreResolvedWithOrdering(): void
    {
        $setRegistry = $this->get(SetRegistry::class);

        $expected = [
            // set-2 and set-3 depend on set-4, therefore set-4 needs to be ordered before 2 and 3.
            'typo3tests/set-4',
            // set-1 depends on set-2 and set-3
            'typo3tests/set-2',
            'typo3tests/set-3',
            // set-5 is an optional dependency of set-1 and needs to be loaded automatically
            'typo3tests/set-5',
            'typo3tests/set-1',
        ];
        $setDefinitions = $setRegistry->getSets('typo3tests/set-1');
        $setDefinitionsNames = array_map(static fn(SetDefinition $d): string => $d->name, $setDefinitions);

        self::assertEquals($expected, $setDefinitionsNames);
    }

    #[Test]
    public function optionalSetDependenciesAreResolvedWithOrderingWhenOptionalIsRequestedAsRequiredDependency(): void
    {
        $setRegistry = $this->get(SetRegistry::class);

        $expected = [
            // set-2 and set-3 depend on set-4, therefore set-4 needs to be ordered before 2 and 3.
            'typo3tests/set-4',
            // set-1 depends on set-2 and set-3
            'typo3tests/set-2',
            'typo3tests/set-3',
            // set-5 is an optional dependency of set-1 and needs to be ordered before
            'typo3tests/set-5',
            'typo3tests/set-1',
        ];
        $setDefinitions = $setRegistry->getSets('typo3tests/set-1', 'typo3tests/set-5');
        $setDefinitionsNames = array_map(static fn(SetDefinition $d): string => $d->name, $setDefinitions);

        self::assertEquals($expected, $setDefinitionsNames);
    }

    #[Test]
    public function unavailableOptionalSetDependenciesAreIgnored(): void
    {
        $setRegistry = $this->get(SetRegistry::class);
        $setDefinitions = $setRegistry->getSets('typo3tests/set-1');
        $setDefinitionsNames = array_map(static fn(SetDefinition $d): string => $d->name, $setDefinitions);

        self::assertNotContains('typo3tests/set-unavailable', $setDefinitionsNames);
    }

    #[Test]
    public function unavailableOptionalSetDependenciesAreSkippedEvenIfExplicitlyRequested(): void
    {
        $setRegistry = $this->get(SetRegistry::class);
        $setDefinitions = $setRegistry->getSets('typo3tests/set-1', 'typo3tests/set-unavailable');
        $setDefinitionsNames = array_map(static fn(SetDefinition $d): string => $d->name, $setDefinitions);

        self::assertNotContains('typo3tests/set-unavailable', $setDefinitionsNames);
        self::assertContains('typo3tests/set-1', $setDefinitionsNames);
    }

    #[Test]
    public function invalidSetsAreSkipped(): void
    {
        $setRegistry = $this->get(SetRegistry::class);
        self::assertFalse($setRegistry->hasSet('typo3tests/invalid-dependency'));

        $setDefinitions = $setRegistry->getSets('typo3tests/invalid-dependency');
        $setDefinitionsNames = array_map(static fn(SetDefinition $d): string => $d->name, $setDefinitions);

        self::assertEmpty($setDefinitionsNames);
    }
}
