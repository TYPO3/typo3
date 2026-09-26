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
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\PrototypeCollection;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\PrototypeConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PrototypeConfigurationTest extends UnitTestCase
{
    #[Test]
    public function fromArrayBuildsTypedFormEditorConfiguration(): void
    {
        $configuration = PrototypeConfiguration::fromArray([
            'formEditor' => [
                'maximumUndoSteps' => 10,
            ],
        ]);

        self::assertSame(10, $configuration->formEditor->maximumUndoSteps);
    }

    #[Test]
    public function fromArrayProvidesEmptyFormEditorConfigurationWhenMissing(): void
    {
        $configuration = PrototypeConfiguration::fromArray([]);

        self::assertSame(0, $configuration->formEditor->maximumUndoSteps);
        self::assertNull($configuration->formEngine);
    }

    #[Test]
    public function fromArrayCreatesPrototypeFormEngineDtoAndPreservesTranslationFileKeys(): void
    {
        $configuration = PrototypeConfiguration::fromArray([
            'formEngine' => [
                'translationFiles' => [10 => 'EXT:site/locallang.xlf', 90 => 'EXT:site/extra.xlf'],
            ],
        ]);

        self::assertSame([10 => 'EXT:site/locallang.xlf', 90 => 'EXT:site/extra.xlf'], $configuration->formEngine?->translationFiles);
        self::assertSame(
            [10 => 'EXT:site/locallang.xlf', 90 => 'EXT:site/extra.xlf'],
            $configuration->formEngine->get('translationFiles'),
        );
    }

    #[Test]
    public function malformedPrototypeFormEngineTranslationFilesNormalizeToEmptyAndRemainRaw(): void
    {
        $configuration = PrototypeConfiguration::fromArray([
            'formEngine' => ['translationFiles' => 'invalid'],
        ]);

        self::assertSame([], $configuration->formEngine?->translationFiles);
        self::assertSame('invalid', $configuration->formEngine->get('translationFiles'));
    }

    #[Test]
    public function rawConfigurationIsPreservedAsEscapeHatch(): void
    {
        $configuration = PrototypeConfiguration::fromArray([
            'formElementsDefinition' => [
                'Form' => ['renderingOptions' => ['submitButtonLabel' => 'Submit']],
            ],
        ]);

        self::assertSame(
            'Submit',
            $configuration->get('formElementsDefinition.Form.renderingOptions.submitButtonLabel'),
        );
        self::assertSame(
            ['submitButtonLabel' => 'Submit'],
            $configuration->formElements->require('Form')->renderingOptions,
        );
        self::assertNull($configuration->finishers->get('CustomFinisher'));
        self::assertTrue($configuration->has('formElementsDefinition.Form'));
        self::assertFalse($configuration->has('finishersDefinition'));
    }

    #[Test]
    public function reducesDefinitionRegistriesToFormEditorConfiguration(): void
    {
        $configuration = PrototypeConfiguration::fromArray([
            'formElementsDefinition' => [
                'FancyCaptcha' => [
                    'formEditor' => ['label' => 'Fancy captcha'],
                ],
            ],
            'finishersDefinition' => [
                'CustomFinisher' => [
                    'formEditor' => ['label' => 'Custom finisher'],
                ],
            ],
            'validatorsDefinition' => [
                'FancyCaptcha' => [
                    'implementationClassName' => 'Vendor\\Extension\\Validation\\FancyCaptchaValidator',
                    'formEditor' => ['label' => 'Fancy captcha validation'],
                ],
                'WithoutEditor' => [
                    'implementationClassName' => 'Vendor\\Extension\\Validation\\WithoutEditorValidator',
                ],
            ],
            'customRegistryDefinition' => [
                'Custom' => ['formEditor' => ['label' => 'Custom registry entry']],
            ],
            'formEditor' => [
                'formElementPropertyValidatorsDefinition' => [
                    'CustomValidator' => ['label' => 'Custom validator'],
                ],
            ],
        ]);

        self::assertSame([
            'formElements' => [
                'FancyCaptcha' => ['label' => 'Fancy captcha'],
            ],
            'finishers' => [
                'CustomFinisher' => ['label' => 'Custom finisher'],
            ],
            'validators' => [
                'FancyCaptcha' => ['label' => 'Fancy captcha validation'],
                'WithoutEditor' => ['implementationClassName' => 'Vendor\\Extension\\Validation\\WithoutEditorValidator'],
            ],
            'customRegistry' => [
                'Custom' => ['label' => 'Custom registry entry'],
            ],
            'formElementPropertyValidators' => [
                'CustomValidator' => ['label' => 'Custom validator'],
            ],
        ], $configuration->getFormEditorDefinitions());
        self::assertSame('Custom finisher', $configuration->finishers->require('CustomFinisher')->formEditor->label);
    }

    #[Test]
    public function getFinisherPropertyCollectionsReadsFinisherEditorsOfFormElement(): void
    {
        $configuration = PrototypeConfiguration::fromArray([
            'formElementsDefinition' => [
                'Form' => [
                    'formEditor' => [
                        'propertyCollections' => [
                            'finishers' => [
                                10 => ['identifier' => 'CustomFinisher', 'editors' => []],
                            ],
                        ],
                    ],
                ],
                'Text' => [
                    'formEditor' => [
                        'propertyCollections' => [
                            'validators' => [
                                10 => ['identifier' => 'StringLength', 'editors' => []],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $finisherCollections = iterator_to_array($configuration->getFinisherPropertyCollections());

        self::assertSame([10], array_keys($finisherCollections));
        self::assertSame('CustomFinisher', $finisherCollections[10]->identifier);
        self::assertSame([], iterator_to_array(PrototypeConfiguration::fromArray([])->getFinisherPropertyCollections()));
    }

    #[Test]
    public function retainsCustomValidatorDefinitionAndEditorMetadata(): void
    {
        $configuration = PrototypeConfiguration::fromArray([
            'validatorsDefinition' => [
                'FancyCaptcha' => [
                    'implementationClassName' => 'Vendor\\Extension\\Validation\\FancyCaptchaValidator',
                    'options' => ['customOption' => ['enabled' => true]],
                    'formEditor' => ['label' => 'Fancy captcha validation'],
                ],
            ],
        ]);

        $validator = $configuration->validators->get('FancyCaptcha');

        self::assertNotNull($validator);
        self::assertSame('Vendor\\Extension\\Validation\\FancyCaptchaValidator', $validator->implementationClassName);
        self::assertSame(['customOption' => ['enabled' => true]], $validator->options);
        self::assertSame('Fancy captcha validation', $validator->formEditor->label);
        self::assertSame(['enabled' => true], $validator->get('options.customOption'));
    }

    #[Test]
    public function collectionRetainsCustomPrototypeIdentifiersAndRawConfiguration(): void
    {
        $collection = PrototypeCollection::fromArray([
            'standard' => ['formEditor' => ['maximumUndoSteps' => 10]],
            'simple' => ['formEditor' => ['maximumUndoSteps' => 5]],
        ]);

        self::assertSame(['standard', 'simple'], array_keys(iterator_to_array($collection)));
        self::assertSame(5, $collection->get('simple')?->get('formEditor.maximumUndoSteps'));
        self::assertSame(['maximumUndoSteps' => 5], $collection->require('simple')->get('formEditor'));
    }

    #[Test]
    public function collectionThrowsExistingExceptionForMissingPrototype(): void
    {
        $collection = PrototypeCollection::fromArray([]);

        $this->expectException(PrototypeNotFoundException::class);
        $this->expectExceptionCode(1475924277);
        $collection->require('standard');
    }
}
