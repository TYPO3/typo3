<?php

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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Property\TypeConverter;

use Prophecy\Argument;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\FormDefinitionArrayConverter;
use TYPO3\CMS\Form\Type\FormDefinitionArray;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for TYPO3\CMS\Form\Mvc\Property\TypeConverter\FormDefinitionArrayConverter
 */
class FormDefinitionArrayConverterTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
    }

    /**
     * @test
     */
    public function convertsJsonStringToFormDefinitionArray()
    {
        $sessionToken = '123';

        $data = [
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
                 'hmac' => GeneralUtility::hmac(serialize(['test', 'prototypeName', 'standard']), $sessionToken),
             ],
             '_orig_identifier' => [
                 'value' => 'test',
                 'hmac' => GeneralUtility::hmac(serialize(['test', 'identifier', 'test']), $sessionToken),
             ],
        ];

        $typeConverter = $this->getAccessibleMock(FormDefinitionArrayConverter::class, ['getFormDefinitionValidationService', 'retrieveSessionToken'], [], '', false);
        $formDefinitionValidationService = $this->prophesize(FormDefinitionValidationService::class);
        $formDefinitionValidationService->validateFormDefinitionProperties(Argument::cetera())->shouldBeCalled();
        $formDefinitionValidationService->isPropertyValueEqualToHistoricalValue(Argument::cetera())->willReturn(true);

        $typeConverter->expects(self::any())->method(
            'retrieveSessionToken'
        )->willReturn($sessionToken);

        $typeConverter->expects(self::any())->method(
            'getFormDefinitionValidationService'
        )->willReturn($formDefinitionValidationService->reveal());

        $input = json_encode($data);
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
        $result = $typeConverter->convertFrom($input, FormDefinitionArray::class);

        self::assertInstanceOf(FormDefinitionArray::class, $result);
        self::assertSame($expected, $result->getArrayCopy());
    }

    /**
     * @test
     */
    public function convertFromThrowsExceptionIfJsonIsInvalid()
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1512578002);

        $typeConverter = new FormDefinitionArrayConverter();
        $input = '{"francine":"stan",';

        $typeConverter->convertFrom($input, FormDefinitionArray::class);
    }

    /**
     * @test
     */
    public function transformMultiValueElementsForFormFrameworkTransformValues()
    {
        $typeConverter = $this->getAccessibleMock(FormDefinitionArrayConverter::class, ['dummy'], [], '', false);

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

        self::assertSame($expected, $typeConverter->_call('transformMultiValueElementsForFormFramework', $input));
    }

    /**
     * @test
     */
    public function convertFromThrowsExceptionIfPrototypeNameWasChanged()
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528538322);

        $sessionToken = '123';
        $typeConverter = $this->getAccessibleMock(FormDefinitionArrayConverter::class, ['retrieveSessionToken'], [], '', false);

        $typeConverter->expects(self::any())->method(
            'retrieveSessionToken'
        )->willReturn($sessionToken);

        $input = [
            'prototypeName' => 'foo',
            'identifier' => 'test',
             '_orig_prototypeName' => [
                 'value' => 'standard',
                 'hmac' => GeneralUtility::hmac(serialize(['test', 'prototypeName', 'standard']), $sessionToken),
             ],
             '_orig_identifier' => [
                 'value' => 'test',
                 'hmac' => GeneralUtility::hmac(serialize(['test', 'identifier', 'test']), $sessionToken),
             ],
        ];

        $typeConverter->convertFrom(json_encode($input), FormDefinitionArray::class);
    }

    /**
     * @test
     */
    public function convertFromThrowsExceptionIfIdentifierWasChanged()
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528538322);

        $sessionToken = '123';
        $typeConverter = $this->getAccessibleMock(FormDefinitionArrayConverter::class, ['retrieveSessionToken'], [], '', false);

        $typeConverter->expects(self::any())->method(
            'retrieveSessionToken'
        )->willReturn($sessionToken);

        $input = [
            'prototypeName' => 'standard',
            'identifier' => 'xxx',
             '_orig_prototypeName' => [
                 'value' => 'standard',
                 'hmac' => GeneralUtility::hmac(serialize(['test', 'prototypeName', 'standard']), $sessionToken),
             ],
             '_orig_identifier' => [
                 'value' => 'test',
                 'hmac' => GeneralUtility::hmac(serialize(['test', 'prototypeName', 'test']), $sessionToken),
             ],
        ];

        $typeConverter->convertFrom(json_encode($input), FormDefinitionArray::class);
    }
}
