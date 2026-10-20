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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Site\Set\InvalidSetException;
use TYPO3\CMS\Core\Site\Set\SetCollector;
use TYPO3\CMS\Core\Site\Set\SetError;
use TYPO3\CMS\Core\Site\Set\YamlSetDefinitionProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class YamlSetDefinitionProviderTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_sets',
    ];

    private YamlSetDefinitionProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->get(YamlSetDefinitionProvider::class);
    }

    public static function malformedSetDefinitionIsReportedAsInvalidSetDataProvider(): \Generator
    {
        yield 'scalar instead of a mapping' => ["not-a-mapping\n"];
        yield 'list instead of a mapping' => ["- typo3tests/a-list\n"];
        yield 'empty file' => [''];
        yield 'numeric name' => ["name: 123\nlabel: Numeric name\n"];
        yield 'name as a mapping' => ["name:\n  foo: bar\nlabel: Mapping as name\n"];
    }

    #[DataProvider('malformedSetDefinitionIsReportedAsInvalidSetDataProvider')]
    #[Test]
    public function malformedSetDefinitionIsReportedAsInvalidSet(string $yaml): void
    {
        $setPath = Environment::getVarPath() . '/tests/malformed-set/';
        GeneralUtility::mkdir_deep($setPath);
        file_put_contents($setPath . 'config.yaml', $yaml);

        try {
            $this->subject->get(new \SplFileInfo($setPath . 'config.yaml'));
            self::fail('Malformed set definition was accepted');
        } catch (InvalidSetException $e) {
            self::assertSame('', $e->getSetName());
            self::assertStringContainsString($setPath . 'config.yaml', $e->getMessage());
        }
    }

    #[Test]
    public function allMalformedSetDefinitionsAreReportedTogether(): void
    {
        $packagePath = Environment::getVarPath() . '/tests/malformed-sets/';
        $definitions = iterator_to_array(self::malformedSetDefinitionIsReportedAsInvalidSetDataProvider());
        foreach ($definitions as $name => [$yaml]) {
            $setPath = $packagePath . 'Configuration/Sets/' . $name . '/';
            GeneralUtility::mkdir_deep($setPath);
            file_put_contents($setPath . 'config.yaml', $yaml);
        }

        $collector = AbstractServiceProvider::configureSetCollector(
            $this->getContainer(),
            new SetCollector(),
            $packagePath,
            'typo3tests/test-sets',
        );
        $invalidSets = $collector->getInvalidSets();

        self::assertCount(count($definitions), $invalidSets);
        self::assertSame([], $collector->getSetDefinitions());
        foreach (array_keys($definitions) as $name) {
            $source = 'EXT:test_sets/Configuration/Sets/' . $name . '/config.yaml';
            $invalidSet = array_find($invalidSets, static fn(array $error): bool => str_contains($error['context'], $source));

            self::assertNotNull($invalidSet, $name);
            self::assertSame(SetError::invalidSet, $invalidSet['error']);
            self::assertNotSame('', $invalidSet['name']);
        }
    }

    #[Test]
    public function getLoadsSettingsYamlFileWithProcessedImports(): void
    {
        $setPath = $this->instancePath . '/typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_sets/Configuration/Sets/Set6/config.yaml';

        $expected = [
            'foo.baz' => 'bar',
        ];

        $actual = $this->subject->get(new \SplFileInfo($setPath));

        self::assertSame($expected, $actual->settings);
    }

    #[Test]
    public function getDerivesEnumSettingLabelsAndDescriptionFromLabelsXlf(): void
    {
        $setPath = $this->instancePath . '/typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_sets/Configuration/Sets/SetEnumLabels/config.yaml';

        $setDefinition = $this->subject->get(new \SplFileInfo($setPath), 'EXT:test_sets/Configuration/Sets/SetEnumLabels/');
        $settingsDefinitions = [];
        foreach ($setDefinition->settingsDefinitions as $settingDefinition) {
            $settingsDefinitions[$settingDefinition->key] = $settingDefinition;
        }

        self::assertSame(
            'LLL:EXT:test_sets/Configuration/Sets/SetEnumLabels/labels.xlf:settings.foo.enumDerived',
            $settingsDefinitions['foo.enumDerived']->label
        );
        self::assertSame(
            'LLL:EXT:test_sets/Configuration/Sets/SetEnumLabels/labels.xlf:settings.description.foo.enumDerived',
            $settingsDefinitions['foo.enumDerived']->description
        );
        self::assertSame(
            [
                'optionDerivedLiteralA' => 'Derived literal option A',
                'optionDerivedLiteralB' => 'Derived literal option B',
            ],
            $settingsDefinitions['foo.enumDerived']->enum
        );
        self::assertSame(
            [
                'optionExplicitLll' => 'LLL:EXT:test_sets/Configuration/Sets/SetEnumLabels/labels.xlf:settings.explicit.foo.enumExplicit.optionExplicitLll',
                'optionExplicitLiteral' => 'Explicit literal option',
                'optionExplicitKeyOnly' => 'optionExplicitKeyOnly',
                'optionExplicitEmptyLabel' => '',
            ],
            $settingsDefinitions['foo.enumExplicit']->enum
        );
        self::assertSame(
            'LLL:EXT:test_sets/Configuration/Sets/SetEnumLabels/labels.xlf:settings.foo.enumList',
            $settingsDefinitions['foo.enumList']->label
        );
        self::assertSame(
            'LLL:EXT:test_sets/Configuration/Sets/SetEnumLabels/labels.xlf:settings.description.foo.enumList',
            $settingsDefinitions['foo.enumList']->description
        );
        self::assertSame(
            [
                'optionListA' => 'LLL:EXT:test_sets/Configuration/Sets/SetEnumLabels/labels.xlf:settings.foo.enumList.enum.optionListA',
                'optionListB' => 'LLL:EXT:test_sets/Configuration/Sets/SetEnumLabels/labels.xlf:settings.foo.enumList.enum.optionListB',
            ],
            $settingsDefinitions['foo.enumList']->enum
        );
    }
}
