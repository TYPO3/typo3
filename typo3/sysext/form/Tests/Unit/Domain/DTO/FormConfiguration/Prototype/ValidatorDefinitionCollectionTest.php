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
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\ValidatorDefinitionCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ValidatorDefinitionCollectionTest extends UnitTestCase
{
    #[Test]
    public function preservesCustomValidatorDefinitionsAndExposesKnownConfiguration(): void
    {
        $configuration = [
            'StringLength' => [
                'implementationClassName' => 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator',
                'formEditor' => [
                    'iconIdentifier' => 'form-validator',
                    'label' => 'formEditor.elements.TextMixin.editor.validators.StringLength.label',
                    'predefinedDefaults' => ['options' => ['minimum' => '', 'maximum' => '']],
                ],
            ],
            'FancyCaptcha' => [
                'implementationClassName' => 'Vendor\\Site\\Validation\\FancyCaptchaValidator',
                'options' => ['threshold' => ['score' => 0.5]],
            ],
        ];

        $collection = ValidatorDefinitionCollection::fromArray($configuration);

        self::assertSame(['StringLength', 'FancyCaptcha'], array_keys(iterator_to_array($collection)));
        $stringLength = $collection->get('StringLength');
        self::assertNotNull($stringLength);
        self::assertSame('StringLength', $stringLength->identifier);
        self::assertSame('TYPO3\\CMS\\Extbase\\Validation\\Validator\\StringLengthValidator', $stringLength->implementationClassName);
        self::assertSame([], $stringLength->options);
        self::assertSame('form-validator', $stringLength->formEditor->iconIdentifier);
        self::assertSame('formEditor.elements.TextMixin.editor.validators.StringLength.label', $stringLength->formEditor->label);
        self::assertSame(['options' => ['minimum' => '', 'maximum' => '']], $stringLength->formEditor->predefinedDefaults);
        $fancyCaptcha = $collection->get('FancyCaptcha');
        self::assertNotNull($fancyCaptcha);
        self::assertSame(['threshold' => ['score' => 0.5]], $fancyCaptcha->options);
        self::assertFalse($fancyCaptcha->has('formEditor'));
        self::assertSame($configuration, $collection->getRaw());
    }

    #[Test]
    public function ignoresMalformedEntriesWithoutLosingRawConfiguration(): void
    {
        $configuration = [
            10 => ['implementationClassName' => 'Vendor\\Site\\Validation\\NumericKeyValidator'],
            'Scalar' => 'invalid',
            'Malformed' => [
                'implementationClassName' => ['invalid'],
                'options' => 'invalid',
                'formEditor' => 'invalid',
            ],
        ];

        $collection = ValidatorDefinitionCollection::fromArray($configuration);

        self::assertSame(['Malformed'], array_keys(iterator_to_array($collection)));
        $malformed = $collection->get('Malformed');
        self::assertNotNull($malformed);
        self::assertNull($malformed->implementationClassName);
        self::assertSame([], $malformed->options);
        self::assertNull($malformed->formEditor->label);
        self::assertSame('invalid', $malformed->get('formEditor'));
        self::assertSame($configuration, $collection->getRaw());
    }

    #[Test]
    public function getReturnsNullForUnknownValidator(): void
    {
        self::assertNull(ValidatorDefinitionCollection::fromArray([])->get('Unknown'));
    }
}
