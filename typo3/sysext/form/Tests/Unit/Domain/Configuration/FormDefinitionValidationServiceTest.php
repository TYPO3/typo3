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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FormDefinitionValidationServiceTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
    }

    public function tearDown(): void
    {
        GeneralUtility::resetSingletonInstances([]);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function validateAllFormElementPropertyValuesByHmacThrowsExceptionIfHmacIsInvalid(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528588036);

        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, ['dummy'], [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier);

        $input = [
            'label' => 'xxx',
            '_orig_label' => [
                'value' => 'aaa',
                'hmac' => GeneralUtility::hmac(serialize([$identifier, 'label', 'aaa']), $sessionToken),
            ],
        ];

        $typeConverter->_call('validateAllFormElementPropertyValuesByHmac', $input, $sessionToken, $validationDto);
    }

    /**
     * @test
     */
    public function validateAllFormElementPropertyValuesByHmacThrowsExceptionIfHmacDoesNotExists(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528588037);

        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, ['dummy'], [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier);

        $input = [
            'label' => 'xxx',
        ];

        $typeConverter->_call('validateAllFormElementPropertyValuesByHmac', $input, $sessionToken, $validationDto);
    }

    /**
     * @test
     */
    public function validateAllFormElementPropertyValuesByHmacThrowsNoExceptionIfHmacIsValid(): void
    {
        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, ['dummy'], [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier);

        $input = [
            'label' => 'aaa',
            '_orig_label' => [
                'value' => 'aaa',
                'hmac' => GeneralUtility::hmac(serialize([$identifier, 'label', 'aaa']), $sessionToken),
            ],
        ];

        $failed = false;
        try {
            $typeConverter->_call(
                'validateAllFormElementPropertyValuesByHmac',
                $input,
                $sessionToken,
                $validationDto
            );
        } catch (PropertyException $e) {
            $failed = true;
        }
        self::assertFalse($failed);
    }

    /**
     * @test
     */
    public function validateAllPropertyCollectionElementValuesByHmacThrowsExceptionIfHmacIsInvalid(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528591586);

        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, ['dummy'], [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier, null, 'validators');

        $input = [
            'identifier' => 'StringLength',
            '_orig_identifier' => [
                'value' => 'StringLength',
                'hmac' => GeneralUtility::hmac(serialize([$identifier, 'validators', 'StringLength', 'identifier', 'StringLength']), $sessionToken),
            ],
            'options' => [
                'test' => 'xxx',
                '_orig_test' => [
                    'value' => 'aaa',
                    'hmac' => GeneralUtility::hmac(serialize([$identifier, 'validators', 'StringLength', 'options.test', 'aaa']), $sessionToken),
                ],
            ],
        ];

        $typeConverter->_call(
            'validateAllPropertyCollectionElementValuesByHmac',
            $input,
            $sessionToken,
            $validationDto
        );
    }

    /**
     * @test
     */
    public function validateAllPropertyCollectionElementValuesByHmacThrowsExceptionIfHmacDoesNotExists(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528591585);

        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, ['dummy'], [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier, null, 'validators');

        $input = [
            'identifier' => 'StringLength',
            '_orig_identifier' => [
                'value' => 'StringLength',
                'hmac' => GeneralUtility::hmac(serialize([$identifier, 'validators', 'StringLength', 'identifier', 'StringLength']), $sessionToken),
            ],
            'options' => [
                'test' => 'xxx',
            ],
        ];

        $typeConverter->_call(
            'validateAllPropertyCollectionElementValuesByHmac',
            $input,
            $sessionToken,
            $validationDto
        );
    }

    /**
     * @test
     */
    public function validateAllPropertyCollectionElementValuesByHmacThrowsNoExceptionIfHmacIsValid(): void
    {
        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, ['dummy'], [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier, null, 'validators');

        $input = [
            'identifier' => 'StringLength',
            '_orig_identifier' => [
                'value' => 'StringLength',
                'hmac' => GeneralUtility::hmac(serialize([$identifier, 'validators', 'StringLength', 'identifier', 'StringLength']), $sessionToken),
            ],
            'options' => [
                'test' => 'aaa',
                '_orig_test' => [
                    'value' => 'aaa',
                    'hmac' => GeneralUtility::hmac(serialize([$identifier, 'validators', 'StringLength', 'options.test', 'aaa']), $sessionToken),
                ],
            ],
        ];

        $failed = false;
        try {
            $typeConverter->_call(
                'validateAllPropertyCollectionElementValuesByHmac',
                $input,
                $sessionToken,
                $validationDto
            );
        } catch (PropertyException $e) {
            $failed = true;
        }
        self::assertFalse($failed);
    }

    /**
     * @test
     * @dataProvider validateAllPropertyValuesFromCreatableFormElementDataProvider
     * @param array $mockConfiguration
     * @param array $formElement
     * @param string $sessionToken
     * @param int $exceptionCode
     * @param ValidationDto $validationDto
     */
    public function validateAllPropertyValuesFromCreatableFormElement(
        array $mockConfiguration,
        array $formElement,
        string $sessionToken,
        int $exceptionCode,
        ValidationDto $validationDto
    ): void {
        $typeConverter = $this->getAccessibleMock(
            FormDefinitionValidationService::class,
            ['getConfigurationService'],
            [],
            '',
            false
        );

        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService->expects(self::any())
            ->method('isFormElementPropertyDefinedInFormEditorSetup')
            ->willReturn($mockConfiguration['isFormElementPropertyDefinedInFormEditorSetup']);
        $configurationService->expects(self::any())->method(
            'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup'
        )->willReturn($mockConfiguration['isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup']);
        $configurationService->expects(self::any())
            ->method('getFormElementPredefinedDefaultValueFromFormEditorSetup')
            ->willReturn($mockConfiguration['getFormElementPredefinedDefaultValueFromFormEditorSetup']);
        $typeConverter->expects(self::any())->method('getConfigurationService')->willReturn($configurationService);
        $formDefinitionValidationService = $this->getAccessibleMock(FormDefinitionValidationService::class, ['getConfigurationService']);
        $formDefinitionValidationService->expects(self::any())->method('getConfigurationService')->willReturn($configurationService);
        GeneralUtility::setSingletonInstance(FormDefinitionValidationService::class, $formDefinitionValidationService);
        GeneralUtility::setSingletonInstance(ConfigurationService::class, $configurationService);

        $returnedExceptionCode = -1;
        try {
            $typeConverter->_call(
                'validateAllPropertyValuesFromCreatableFormElement',
                $formElement,
                $sessionToken,
                $validationDto
            );
        } catch (PropertyException $e) {
            $returnedExceptionCode = $e->getCode();
        }
        self::assertEquals($returnedExceptionCode, $exceptionCode);
    }

    /**
     * @test
     * @dataProvider validateAllPropertyValuesFromCreatablePropertyCollectionElementDataProvider
     * @param array $mockConfiguration
     * @param array $formElement
     * @param string $sessionToken
     * @param int $exceptionCode
     * @param ValidationDto $validationDto
     */
    public function validateAllPropertyValuesFromCreatablePropertyCollectionElement(
        array $mockConfiguration,
        array $formElement,
        string $sessionToken,
        int $exceptionCode,
        ValidationDto $validationDto
    ): void {
        $typeConverter = $this->getAccessibleMock(
            FormDefinitionValidationService::class,
            ['getConfigurationService'],
            [],
            '',
            false
        );

        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService->expects(self::any())
            ->method('isPropertyCollectionPropertyDefinedInFormEditorSetup')
            ->willReturn($mockConfiguration['isPropertyCollectionPropertyDefinedInFormEditorSetup']);
        $configurationService->expects(self::any())->method(
            'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup'
        )->willReturn($mockConfiguration['isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup']);
        $configurationService->expects(self::any())->method(
            'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup'
        )->willReturn($mockConfiguration['getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup']);
        $typeConverter->expects(self::any())->method('getConfigurationService')->willReturn($configurationService);
        $formDefinitionValidationService = $this->getAccessibleMock(FormDefinitionValidationService::class, ['getConfigurationService']);
        $formDefinitionValidationService->expects(self::any())->method('getConfigurationService')->willReturn($configurationService);
        GeneralUtility::setSingletonInstance(FormDefinitionValidationService::class, $formDefinitionValidationService);
        GeneralUtility::setSingletonInstance(ConfigurationService::class, $configurationService);

        $returnedExceptionCode = -1;
        try {
            $typeConverter->_call(
                'validateAllPropertyValuesFromCreatablePropertyCollectionElement',
                $formElement,
                $sessionToken,
                $validationDto
            );
        } catch (PropertyException $e) {
            $returnedExceptionCode = $e->getCode();
        }
        self::assertEquals($returnedExceptionCode, $exceptionCode);
    }

    /**
     * @return array
     */
    public function validateAllPropertyValuesFromCreatableFormElementDataProvider(): array
    {
        $encryptionKeyBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';

        $sessionToken = '54321';
        $identifier = 'text-1';

        $validationDto = new ValidationDto('standard', 'Text', $identifier);
        $formElement = [
            'test' => 'xxx',
            '_orig_test' => [
                'value' => 'xxx',
                'hmac' => GeneralUtility::hmac(serialize([$identifier, 'test', 'xxx']), $sessionToken),
            ],
        ];

        $formElementWithoutHmac = [
            'test' => 'xxx',
        ];

        $invalidFormElement = [
            'test' => 'xxx1',
            '_orig_test' => [
                'value' => 'xxx',
                'hmac' => GeneralUtility::hmac(serialize([$identifier, 'test', 'xxx']), $sessionToken),
            ],
        ];

        // be aware that backup globals does not impact globals used in data providers as these are called before the setUp/tearDown is done
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $encryptionKeyBackup;

        return [
            [
                [
                    'isFormElementPropertyDefinedInFormEditorSetup' => true,
                    'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getFormElementPredefinedDefaultValueFromFormEditorSetup' => '',
                ],
                $formElement,
                $sessionToken,
                -1,
                $validationDto,
            ],
            [
                [
                    'isFormElementPropertyDefinedInFormEditorSetup' => false,
                    'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getFormElementPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                $formElement,
                $sessionToken,
                -1,
                $validationDto,
            ],
            [
                [
                    'isFormElementPropertyDefinedInFormEditorSetup' => false,
                    'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getFormElementPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                ['test' => 'xxx'],
                $sessionToken,
                1528588037,
                $validationDto,
            ],
            [
                [
                    'isFormElementPropertyDefinedInFormEditorSetup' => false,
                    'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getFormElementPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                ['test' => 'xxx', '_orig_test' => []],
                $sessionToken,
                1528538222,
                $validationDto,
            ],
            [
                [
                    'isFormElementPropertyDefinedInFormEditorSetup' => false,
                    'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getFormElementPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                ['test' => 'xxx', '_orig_test' => ['hmac' => '4242']],
                $sessionToken,
                1528538252,
                $validationDto,
            ],
            [
                [
                    'isFormElementPropertyDefinedInFormEditorSetup' => false,
                    'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getFormElementPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                $invalidFormElement,
                $sessionToken,
                1528588036,
                $validationDto,
            ],
            [
                [
                    'isFormElementPropertyDefinedInFormEditorSetup' => false,
                    'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => true,
                    'getFormElementPredefinedDefaultValueFromFormEditorSetup' => 'xxx',
                ],
                $formElement,
                $sessionToken,
                -1,
                $validationDto,
            ],
            [
                [
                    'isFormElementPropertyDefinedInFormEditorSetup' => false,
                    'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => true,
                    'getFormElementPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                $formElement,
                $sessionToken,
                -1,
                $validationDto,
            ],
            [
                [
                    'isFormElementPropertyDefinedInFormEditorSetup' => false,
                    'isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => true,
                    'getFormElementPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                $formElementWithoutHmac,
                $sessionToken,
                1528588035,
                $validationDto,
            ],
        ];
    }

    /**
     * @return array
     */
    public function validateAllPropertyValuesFromCreatablePropertyCollectionElementDataProvider(): array
    {
        $encryptionKeyBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';

        $sessionToken = '54321';
        $identifier = 'text-1';

        $validationDto = new ValidationDto('standard', 'Text', $identifier, null, 'validators', 'StringLength');
        $formElement = [
            'test' => 'xxx',
            '_orig_test' => [
                'value' => 'xxx',
                'hmac' => GeneralUtility::hmac(serialize([$identifier, 'validators', 'StringLength', 'test', 'xxx']), $sessionToken),
            ],
        ];

        $formElementWithoutHmac = [
            'test' => 'xxx',
        ];

        $invalidFormElement = [
            'test' => 'xxx1',
            '_orig_test' => [
                'value' => 'xxx',
                'hmac' => GeneralUtility::hmac(serialize([$identifier, 'validators', 'StringLength', 'test', 'xxx']), $sessionToken),
            ],
        ];

        // be aware that backup globals does not impact globals used in data providers as these are called before the setUp/tearDown is done
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $encryptionKeyBackup;

        return [
            [
                [
                    'isPropertyCollectionPropertyDefinedInFormEditorSetup' => true,
                    'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                $formElement,
                $sessionToken,
                -1,
                $validationDto,
            ],
            [
                [
                    'isPropertyCollectionPropertyDefinedInFormEditorSetup' => false,
                    'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                $formElement,
                $sessionToken,
                -1,
                $validationDto,
            ],
            [
                [
                    'isPropertyCollectionPropertyDefinedInFormEditorSetup' => false,
                    'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                ['test' => 'xxx'],
                $sessionToken,
                1528591585,
                $validationDto,
            ],
            [
                [
                    'isPropertyCollectionPropertyDefinedInFormEditorSetup' => false,
                    'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                ['test' => 'xxx', '_orig_test' => []],
                $sessionToken,
                1528538222,
                $validationDto,
            ],
            [
                [
                    'isPropertyCollectionPropertyDefinedInFormEditorSetup' => false,
                    'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                ['test' => 'xxx', '_orig_test' => ['hmac' => '4242']],
                $sessionToken,
                1528538252,
                $validationDto,
            ],
            [
                [
                    'isPropertyCollectionPropertyDefinedInFormEditorSetup' => false,
                    'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => false,
                    'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                $invalidFormElement,
                $sessionToken,
                1528591586,
                $validationDto,
            ],

            [
                [
                    'isPropertyCollectionPropertyDefinedInFormEditorSetup' => false,
                    'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => true,
                    'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup' => 'xxx',
                ],
                $formElement,
                $sessionToken,
                -1,
                $validationDto,
            ],
            [
                [
                    'isPropertyCollectionPropertyDefinedInFormEditorSetup' => false,
                    'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => true,
                    'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                $formElement,
                $sessionToken,
                -1,
                $validationDto,
            ],
            [
                [
                    'isPropertyCollectionPropertyDefinedInFormEditorSetup' => false,
                    'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup' => true,
                    'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup' => 'default',
                ],
                $formElementWithoutHmac,
                $sessionToken,
                1528591502,
                $validationDto,
            ],
        ];
    }
}
