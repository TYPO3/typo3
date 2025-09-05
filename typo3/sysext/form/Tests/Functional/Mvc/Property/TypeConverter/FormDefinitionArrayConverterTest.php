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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Property\TypeConverter;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\FormDefinitionArrayConverter;
use TYPO3\CMS\Form\Type\FormDefinitionArray;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormDefinitionArrayConverterTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
    public function convertsJsonStringToFormDefinitionArray(): void
    {
        $formDefinitionValidationServiceMock = $this->createMock(FormDefinitionValidationService::class);
        $formDefinitionValidationServiceMock->expects($this->atLeastOnce())->method('validateFormDefinitionProperties')->with(self::anything());
        $formDefinitionValidationServiceMock->method('isPropertyValueEqualToHistoricalValue')->with(self::anything())->willReturn(true);

        $subjectMock = $this->getAccessibleMock(
            FormDefinitionArrayConverter::class,
            ['getFormDefinitionValidationService', 'retrieveSessionToken'],
            [],
            '',
            false
        );
        $subjectMock->method('retrieveSessionToken')->willReturn('123');
        $subjectMock->method('getFormDefinitionValidationService')->willReturn($formDefinitionValidationServiceMock);

        $expected = [
            'prototypeName' => 'standard',
            'identifier' => 'test',
            'type' => 'Text',
            'enabled' => false,
            'properties' => [
                'options' => [
                    'value' => 'label',
                ],
            ],
        ];
        $result = $subjectMock->convertFrom(
            json_encode([
                'prototypeName' => 'standard',
                'identifier' => 'test',
                'type' => 'Text',
                'enabled' => false,
                'properties' => [
                    'options' => [
                        [
                            '_label' => 'label',
                            '_value' => 'value',
                        ],
                    ],
                ],
                '_orig_prototypeName' => [
                    'value' => 'standard',
                    'hmac' => (new HashService())->hmac(serialize(['test', 'prototypeName', 'standard']), '123'),
                ],
                '_orig_identifier' => [
                    'value' => 'test',
                    'hmac' => (new HashService())->hmac(serialize(['test', 'identifier', 'test']), '123'),
                ],
            ]),
            FormDefinitionArray::class
        );

        self::assertInstanceOf(FormDefinitionArray::class, $result);
        self::assertSame($expected, $result->getArrayCopy());
    }

    #[Test]
    public function convertFromThrowsExceptionIfJsonIsInvalid(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1512578002);
        (new FormDefinitionArrayConverter())->convertFrom('{"francine":"stan",', FormDefinitionArray::class);
    }

    #[Test]
    public function transformMultiValueElementsForFormFrameworkTransformValues(): void
    {
        $input = [
            'foo1' => 'bar',
            'foo2' => [
                'foo3' => [
                    [
                        '_label' => 'xxx1',
                        '_value' => 'yyy1',
                    ],
                    [
                        '_label' => 'xxx2',
                        '_value' => 'yyy2',
                    ],
                    [
                        '_label' => 'xxx3',
                        '_value' => 'yyy2',
                    ],
                ],
                '_label' => 'xxx',
                '_value' => 'yyy',
            ],
            '_label' => 'xxx',
            '_value' => 'yyy',
        ];
        $expected = [
            'foo1' => 'bar',
            'foo2' => [
                'foo3' => [
                    'yyy1' => 'xxx1',
                    'yyy2' => 'xxx3',
                ],
                '_label' => 'xxx',
                '_value' => 'yyy',
            ],
            '_label' => 'xxx',
            '_value' => 'yyy',
        ];
        $subjectMock = $this->getAccessibleMock(FormDefinitionArrayConverter::class, null, [], '', false);
        self::assertSame($expected, $subjectMock->_call('transformMultiValueElementsForFormFramework', $input));
    }

    #[Test]
    public function convertFromThrowsExceptionIfPrototypeNameWasChanged(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528538322);
        $input = [
            'prototypeName' => 'foo',
            'identifier' => 'test',
            '_orig_prototypeName' => [
                'value' => 'standard',
                'hmac' => (new HashService())->hmac(serialize(['test', 'prototypeName', 'standard']), '123'),
            ],
            '_orig_identifier' => [
                'value' => 'test',
                'hmac' => (new HashService())->hmac(serialize(['test', 'identifier', 'test']), '123'),
            ],
        ];
        $typeConverter = $this->getAccessibleMock(FormDefinitionArrayConverter::class, ['retrieveSessionToken'], [], '', false);
        $typeConverter->method('retrieveSessionToken')->willReturn('123');
        $typeConverter->convertFrom(json_encode($input), FormDefinitionArray::class);
    }

    #[Test]
    public function convertFromThrowsExceptionIfIdentifierWasChanged(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528538322);
        $input = [
            'prototypeName' => 'standard',
            'identifier' => 'xxx',
            '_orig_prototypeName' => [
                'value' => 'standard',
                'hmac' => (new HashService())->hmac(serialize(['test', 'prototypeName', 'standard']), '123'),
            ],
            '_orig_identifier' => [
                'value' => 'test',
                'hmac' => (new HashService())->hmac(serialize(['test', 'prototypeName', 'test']), '123'),
            ],
        ];
        $typeConverter = $this->getAccessibleMock(FormDefinitionArrayConverter::class, ['retrieveSessionToken'], [], '', false);
        $typeConverter->method('retrieveSessionToken')->willReturn('123');
        $typeConverter->convertFrom(json_encode($input), FormDefinitionArray::class);
    }
}
