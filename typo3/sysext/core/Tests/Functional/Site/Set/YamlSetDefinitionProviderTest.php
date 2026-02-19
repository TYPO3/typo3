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
use TYPO3\CMS\Core\Site\Set\YamlSetDefinitionProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class YamlSetDefinitionProviderTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_sets',
    ];

    private YamlSetDefinitionProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->get(YamlSetDefinitionProvider::class);
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
    }
}
