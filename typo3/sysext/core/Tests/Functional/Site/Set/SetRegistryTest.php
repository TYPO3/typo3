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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetError;
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
    public function emptySettingsFileIsAccepted(): void
    {
        $setRegistry = $this->get(SetRegistry::class);
        $setDefinitions = $setRegistry->getSets('typo3tests/empty-settings');
        $setDefinitionsNames = array_map(static fn(SetDefinition $d): string => $d->name, $setDefinitions);

        self::assertContains('typo3tests/empty-settings', $setDefinitionsNames);
        self::assertEmpty($setDefinitions[0]->settings);
    }

    public static function invalidSetsDataProvider(): \Generator
    {
        yield [
            'set' => 'typo3tests/invalid-set-missing-label',
            'error' => SetError::invalidSet,
            'context' => 'Invalid set definition: {"name":"typo3tests\/invalid-set-missing-label"} – Missing properties: label',
        ];
        yield [
            'set' => 'typo3tests/invalid-dependency',
            'error' => SetError::missingDependency,
            'context' => 'typo3tests/not-available',
        ];
        yield [
            'set' => 'typo3tests/invalid-chained-dependency',
            'error' => SetError::missingDependency,
            'context' => 'typo3tests/invalid-dependency[typo3tests/not-available]',
        ];
        yield [
            'set' => 'typo3tests/invalid-settings-definitions-missing-properties',
            'error' => SetError::invalidSettingsDefinitions,
            'context' => 'Invalid setting definition "foo.bar": {"type":"string"} – Missing properties: default, label',
        ];
        yield [
            'set' => 'typo3tests/invalid-settings-definitions-invalid-type',
            'error' => SetError::invalidSettingsDefinitions,
            'context' => 'Invalid settings type "invalidtype" in setting "foo.bar"',
        ];
        yield [
            'set' => 'typo3tests/invalid-settings',
            'error' => SetError::invalidSettings,
            'context' => 'Invalid settings format. Source: EXT:test_sets/Configuration/Sets/InvalidSettings/settings.yaml',
        ];
    }

    #[DataProvider('invalidSetsDataProvider')]
    #[Test]
    public function invalidSetsAreSkippedAndLogged(
        string $set,
        SetError $error,
        string $context,
    ): void {
        $setRegistry = $this->get(SetRegistry::class);
        self::assertFalse($setRegistry->hasSet($set));

        $setDefinitions = $setRegistry->getSets($set);
        $setDefinitionsNames = array_map(static fn(SetDefinition $d): string => $d->name, $setDefinitions);

        self::assertEmpty($setDefinitionsNames);

        $invalidSets = $setRegistry->getInvalidSets();
        self::assertArrayHasKey($set, $invalidSets);

        $setError = $invalidSets[$set];
        self::assertSame($error, $setError['error']);
        self::assertSame($set, $setError['name']);
        self::assertSame($context, $setError['context']);
    }
}
