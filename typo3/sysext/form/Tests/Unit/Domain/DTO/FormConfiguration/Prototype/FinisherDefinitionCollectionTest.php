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
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\FinisherDefinition;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\FinisherDefinitionCollection;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\FormEngineDefinition;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FinisherDefinitionCollectionTest extends UnitTestCase
{
    #[Test]
    public function preservesCustomFinisherDefinitionsAndExposesKnownConfiguration(): void
    {
        $collection = FinisherDefinitionCollection::fromArray([
            'CustomFinisher' => [
                'implementationClassName' => 'Vendor\\Site\\Form\\CustomFinisher',
                'options' => [
                    'recipientTree' => [
                        'primary' => ['address' => 'team@example.test'],
                    ],
                ],
                'formEditor' => [
                    'label' => 'Custom finisher',
                    'iconIdentifier' => 'form-custom-finisher',
                ],
                'FormEngine' => [
                    'label' => 'Custom finisher options',
                    'elements' => [30 => ['custom' => 'first'], 90 => ['custom' => 'second']],
                    'translationFiles' => [20 => 'EXT:site/Resources/Private/Language/Custom.xlf'],
                    'customFormEngineOption' => ['kept' => true],
                ],
            ],
        ]);

        $definition = $collection->require('CustomFinisher');

        self::assertSame(['CustomFinisher'], array_keys(iterator_to_array($collection)));
        self::assertSame('Vendor\\Site\\Form\\CustomFinisher', $definition->implementationClassName);
        self::assertSame(['primary' => ['address' => 'team@example.test']], $definition->options['recipientTree']);
        self::assertSame('Custom finisher', $definition->formEditor->label);
        self::assertSame([30, 90], array_keys($definition->formEngine->elements ?? []));
        self::assertSame([20 => 'EXT:site/Resources/Private/Language/Custom.xlf'], $definition->formEngine?->translationFiles);
    }

    #[Test]
    public function resolvesTranslationFilesFromFinisherBeforePrototypeFallback(): void
    {
        $prototypeFormEngine = FormEngineDefinition::fromArray([
            'translationFiles' => [10 => 'EXT:site/Resources/Private/Language/Prototype.xlf'],
        ]);
        $finisherWithoutTranslationFiles = FinisherDefinition::fromArray('CustomFinisher', [
            'FormEngine' => ['elements' => []],
        ]);
        $finisherWithTranslationFiles = FinisherDefinition::fromArray('CustomFinisher', [
            'FormEngine' => [
                'elements' => [],
                'translationFiles' => [90 => 'EXT:site/Resources/Private/Language/Finisher.xlf'],
            ],
        ]);

        self::assertSame(
            [10 => 'EXT:site/Resources/Private/Language/Prototype.xlf'],
            $finisherWithoutTranslationFiles->getTranslationFiles($prototypeFormEngine),
        );
        self::assertSame(
            [90 => 'EXT:site/Resources/Private/Language/Finisher.xlf'],
            $finisherWithTranslationFiles->getTranslationFiles($prototypeFormEngine),
        );
        self::assertSame([], $finisherWithoutTranslationFiles->getTranslationFiles(null));
    }
}
