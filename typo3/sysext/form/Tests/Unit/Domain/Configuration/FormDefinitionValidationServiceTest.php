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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @todo: Refactor this mess towards a functional test with less
 *        mocking and have DI in FormDefinitionValidationService
 */
final class FormDefinitionValidationServiceTest extends UnitTestCase
{
    protected HashService $hashService;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $this->hashService = new HashService();
    }

    public function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function validateAllFormElementPropertyValuesByHmacThrowsExceptionIfHmacIsInvalid(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528588036);

        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier);

        $input = [
            'label' => 'xxx',
            '_orig_label' => [
                'value' => 'aaa',
                'hmac' => $this->hashService->hmac(serialize([$identifier, 'label', 'aaa']), $sessionToken),
            ],
        ];

        $typeConverter->_call('validateAllFormElementPropertyValuesByHmac', $input, $sessionToken, $validationDto);
    }

    #[Test]
    public function validateAllFormElementPropertyValuesByHmacThrowsExceptionIfHmacDoesNotExists(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528588037);

        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier);

        $input = [
            'label' => 'xxx',
        ];

        $typeConverter->_call('validateAllFormElementPropertyValuesByHmac', $input, $sessionToken, $validationDto);
    }

    #[Test]
    public function validateAllFormElementPropertyValuesByHmacThrowsNoExceptionIfHmacIsValid(): void
    {
        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier);

        $input = [
            'label' => 'aaa',
            '_orig_label' => [
                'value' => 'aaa',
                'hmac' => $this->hashService->hmac(serialize([$identifier, 'label', 'aaa']), $sessionToken),
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
        } catch (PropertyException) {
            $failed = true;
        }
        self::assertFalse($failed);
    }

    #[Test]
    public function validateAllPropertyCollectionElementValuesByHmacThrowsExceptionIfHmacIsInvalid(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528591586);

        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier, null, 'validators');

        $input = [
            'identifier' => 'StringLength',
            '_orig_identifier' => [
                'value' => 'StringLength',
                'hmac' => $this->hashService->hmac(serialize([$identifier, 'validators', 'StringLength', 'identifier', 'StringLength']), $sessionToken),
            ],
            'options' => [
                'test' => 'xxx',
                '_orig_test' => [
                    'value' => 'aaa',
                    'hmac' => $this->hashService->hmac(serialize([$identifier, 'validators', 'StringLength', 'options.test', 'aaa']), $sessionToken),
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

    #[Test]
    public function validateAllPropertyCollectionElementValuesByHmacThrowsExceptionIfHmacDoesNotExists(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528591585);

        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier, null, 'validators');

        $input = [
            'identifier' => 'StringLength',
            '_orig_identifier' => [
                'value' => 'StringLength',
                'hmac' => $this->hashService->hmac(serialize([$identifier, 'validators', 'StringLength', 'identifier', 'StringLength']), $sessionToken),
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

    #[Test]
    public function validateAllPropertyCollectionElementValuesByHmacThrowsNoExceptionIfHmacIsValid(): void
    {
        $typeConverter = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);

        $prototypeName = 'standard';
        $identifier = 'some-text';

        $sessionToken = '123';

        $validationDto = new ValidationDto($prototypeName, 'Text', $identifier, null, 'validators');

        $input = [
            'identifier' => 'StringLength',
            '_orig_identifier' => [
                'value' => 'StringLength',
                'hmac' => $this->hashService->hmac(serialize([$identifier, 'validators', 'StringLength', 'identifier', 'StringLength']), $sessionToken),
            ],
            'options' => [
                'test' => 'aaa',
                '_orig_test' => [
                    'value' => 'aaa',
                    'hmac' => $this->hashService->hmac(serialize([$identifier, 'validators', 'StringLength', 'options.test', 'aaa']), $sessionToken),
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
        } catch (PropertyException) {
            $failed = true;
        }
        self::assertFalse($failed);
    }

    public static function validateAllPropertyValuesFromCreatableFormElementDataProvider(): array
    {
        // Be aware that the $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] assignment in setUp is done
        // after the dataProvider intitialization. Therefore, the encryption key must also be defined here.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';

        $sessionToken = '54321';
        $identifier = 'text-1';

        $hashService = new HashService();
        $validationDto = new ValidationDto('standard', 'Text', $identifier);
        $formElement = [
            'test' => 'xxx',
            '_orig_test' => [
                'value' => 'xxx',
                'hmac' => $hashService->hmac(serialize([$identifier, 'test', 'xxx']), $sessionToken),
            ],
        ];

        $formElementWithoutHmac = [
            'test' => 'xxx',
        ];

        $invalidFormElement = [
            'test' => 'xxx1',
            '_orig_test' => [
                'value' => 'xxx',
                'hmac' => $hashService->hmac(serialize([$identifier, 'test', 'xxx']), $sessionToken),
            ],
        ];

        // Unset global encryption key, so following tests do not use it. Data providers are not covered by phpunit backupGlobals.
        // @todo: Refactor this out of the data provider.
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);

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

    #[DataProvider('validateAllPropertyValuesFromCreatableFormElementDataProvider')]
    #[Test]
    public function validateAllPropertyValuesFromCreatableFormElement(
        array $mockConfiguration,
        array $formElement,
        string $sessionToken,
        int $exceptionCode,
        ValidationDto $validationDto
    ): void {
        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService
            ->method('isFormElementPropertyDefinedInFormEditorSetup')
            ->willReturn($mockConfiguration['isFormElementPropertyDefinedInFormEditorSetup']);
        $configurationService
            ->method('isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup')
            ->willReturn($mockConfiguration['isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup']);
        $configurationService
            ->method('getFormElementPredefinedDefaultValueFromFormEditorSetup')
            ->willReturn($mockConfiguration['getFormElementPredefinedDefaultValueFromFormEditorSetup']);
        $formDefinitionValidationService = $this->getAccessibleMock(FormDefinitionValidationService::class, null);
        GeneralUtility::setSingletonInstance(FormDefinitionValidationService::class, $formDefinitionValidationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);

        $subject = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $returnedExceptionCode = -1;
        try {
            $subject->_call(
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

    public static function validateAllPropertyValuesFromCreatablePropertyCollectionElementDataProvider(): array
    {
        // Be aware that the $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] assignment in setUp is done
        // after the dataProvider intitialization. Therefore, the encryption key must also be defined here.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';

        $sessionToken = '54321';
        $identifier = 'text-1';

        $validationDto = new ValidationDto('standard', 'Text', $identifier, null, 'validators', 'StringLength');
        $hashService = new HashService();
        $formElement = [
            'test' => 'xxx',
            '_orig_test' => [
                'value' => 'xxx',
                'hmac' => $hashService->hmac(serialize([$identifier, 'validators', 'StringLength', 'test', 'xxx']), $sessionToken),
            ],
        ];

        $formElementWithoutHmac = [
            'test' => 'xxx',
        ];

        $invalidFormElement = [
            'test' => 'xxx1',
            '_orig_test' => [
                'value' => 'xxx',
                'hmac' => $hashService->hmac(serialize([$identifier, 'validators', 'StringLength', 'test', 'xxx']), $sessionToken),
            ],
        ];

        // Unset global encryption key, so following tests do not use it. Data providers are not covered by phpunit backupGlobals.
        // @todo: Refactor this out of the data provider.
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);

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

    #[DataProvider('validateAllPropertyValuesFromCreatablePropertyCollectionElementDataProvider')]
    #[Test]
    public function validateAllPropertyValuesFromCreatablePropertyCollectionElement(
        array $mockConfiguration,
        array $formElement,
        string $sessionToken,
        int $exceptionCode,
        ValidationDto $validationDto
    ): void {
        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService
            ->method('isPropertyCollectionPropertyDefinedInFormEditorSetup')
            ->willReturn($mockConfiguration['isPropertyCollectionPropertyDefinedInFormEditorSetup']);
        $configurationService->method(
            'isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup'
        )->willReturn($mockConfiguration['isPropertyCollectionPropertyDefinedInPredefinedDefaultsInFormEditorSetup']);
        $configurationService->method(
            'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup'
        )->willReturn($mockConfiguration['getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup']);
        $formDefinitionValidationService = $this->getAccessibleMock(FormDefinitionValidationService::class, null);
        GeneralUtility::setSingletonInstance(FormDefinitionValidationService::class, $formDefinitionValidationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);
        GeneralUtility::addInstance(ConfigurationService::class, $configurationService);

        $subject = $this->getAccessibleMock(FormDefinitionValidationService::class, null, [], '', false);
        $returnedExceptionCode = -1;
        try {
            $subject->_call(
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
}
