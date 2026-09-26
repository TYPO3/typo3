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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\DTO\FormConfiguration\Prototype;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\DefinitionConfiguration;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\FormEditorDefinition;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\FormEngineDefinition;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormEditorDefinitionTest extends UnitTestCase
{
    #[Test]
    public function preservesOpenConfigurationAndProvidesPropertyGridPaths(): void
    {
        $configuration = [
            'label' => 'LLL:EXT:site/element.label',
            'iconIdentifier' => 'form-icon',
            'group' => 'input',
            'groupSorting' => 100,
            'predefinedDefaults' => ['custom' => ['value' => 'kept']],
            'editors' => [
                20 => [
                    'identifier' => 'label',
                    'templateName' => 'Inspector-TextEditor',
                    'label' => 'LLL:EXT:site/editor.label',
                    'description' => 'LLL:EXT:site/editor.description',
                    'propertyPath' => 'label',
                ],
                90 => ['templateName' => 'Inspector-PropertyGridEditor', 'propertyPath' => 'properties.options'],
            ],
            'propertyCollections' => [
                10 => [
                    'identifier' => 'CustomFinisher',
                    'editors' => [
                        50 => ['templateName' => 'Inspector-PropertyGridEditor', 'propertyPath' => 'options.recipients'],
                    ],
                    'thirdPartyOption' => ['preserve' => true],
                ],
            ],
            'customEditorOption' => ['template' => 'Vendor/Inspector'],
        ];

        $definition = FormEditorDefinition::fromArray($configuration);

        self::assertSame('LLL:EXT:site/element.label', $definition->label);
        self::assertSame('form-icon', $definition->iconIdentifier);
        self::assertSame('input', $definition->group);
        self::assertSame(100, $definition->groupSorting);
        self::assertSame(['custom' => ['value' => 'kept']], $definition->predefinedDefaults);
        $editor = iterator_to_array($definition->editors)[20];
        self::assertSame('label', $editor->identifier);
        self::assertSame('LLL:EXT:site/editor.label', $editor->label);
        self::assertSame('LLL:EXT:site/editor.description', $editor->description);
        self::assertSame(['properties.options'], $definition->getPropertyGridPropertyPaths());
        self::assertSame(['options.recipients'], $definition->getPropertyGridPropertyPathsForCollection('CustomFinisher'));
        self::assertSame(['template' => 'Vendor/Inspector'], $definition->get('customEditorOption'));
        self::assertSame([20, 90], array_keys(iterator_to_array($definition->editors)));
        self::assertSame([10], array_keys(iterator_to_array($definition->propertyCollections)));
        self::assertSame($configuration['editors'], $definition->editors->getRaw());
        self::assertSame($configuration['propertyCollections'], $definition->propertyCollections->getRaw());
    }

    #[Test]
    public function ignoresMalformedKnownValuesWithoutLosingRawConfiguration(): void
    {
        $definition = FormEditorDefinition::fromArray([
            'label' => ['invalid'],
            'iconIdentifier' => 42,
            'group' => 42,
            'groupSorting' => 'invalid',
            'predefinedDefaults' => 'invalid',
            'editors' => 'invalid',
            'propertyCollections' => 'invalid',
        ]);

        self::assertNull($definition->label);
        self::assertNull($definition->iconIdentifier);
        self::assertNull($definition->group);
        self::assertNull($definition->groupSorting);
        self::assertSame([], $definition->predefinedDefaults);
        self::assertSame([], iterator_to_array($definition->editors));
        self::assertSame([], iterator_to_array($definition->propertyCollections));
        self::assertSame('invalid', $definition->get('editors'));
    }

    #[Test]
    public function definitionConfigurationKeepsRegistryIdentifierAndRawData(): void
    {
        $definition = DefinitionConfiguration::fromArray('FancyCaptcha', ['implementationClassName' => 'Vendor\\Captcha']);

        self::assertSame('FancyCaptcha', $definition->identifier);
        self::assertSame('Vendor\\Captcha', $definition->get('implementationClassName'));
    }

    #[Test]
    public function formEngineDefinitionNormalizesKnownValuesAndKeepsOpenElements(): void
    {
        $definition = FormEngineDefinition::fromArray([
            'label' => 'LLL:EXT:site/finisher.label',
            'elements' => [20 => ['identifier' => 'recipient', 'custom' => ['kept' => true]]],
            'translationFiles' => [10 => 'EXT:site/locallang.xlf'],
            'customFormEngineOption' => ['kept' => true],
        ]);

        self::assertSame('LLL:EXT:site/finisher.label', $definition->label);
        self::assertSame([20 => ['identifier' => 'recipient', 'custom' => ['kept' => true]]], $definition->elements);
        self::assertSame([10 => 'EXT:site/locallang.xlf'], $definition->translationFiles);
        self::assertSame(['kept' => true], $definition->get('customFormEngineOption'));
    }
}
