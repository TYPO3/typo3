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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Configuration\FormDefinition\Validators;

use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\CreatablePropertyCollectionElementPropertiesValidator;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CreatablePropertyCollectionElementPropertiesValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validatePropertyCollectionElementPredefinedDefaultValueThrowsExceptionIfValueDoesNotMatch(): void
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528591502);

        $validationDto = new ValidationDto('standard', null, 'test-1', 'label', 'validators', 'StringLength');
        $typeConverter = $this->getAccessibleMock(
            CreatablePropertyCollectionElementPropertiesValidator::class,
            ['getConfigurationService'],
            [[], '', $validationDto]
        );
        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService->method(
            'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup'
        )->willReturn('default');
        $typeConverter->method('getConfigurationService')->willReturn($configurationService);

        $input = 'xxx';
        $typeConverter->_call('validatePropertyCollectionElementPredefinedDefaultValue', $input, $validationDto);
    }

    /**
     * @test
     */
    public function validatePropertyCollectionElementPredefinedDefaultValueThrowsNoExceptionIfValueMatches(): void
    {
        $validationDto = new ValidationDto(null, null, 'test-1', 'label', 'validators', 'StringLength');
        $typeConverter = $this->getAccessibleMock(
            CreatablePropertyCollectionElementPropertiesValidator::class,
            ['getConfigurationService'],
            [[], '', $validationDto]
        );
        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService->method(
            'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup'
        )->willReturn('default');
        $typeConverter->method('getConfigurationService')->willReturn($configurationService);

        $input = 'default';

        $failed = false;
        try {
            $typeConverter->_call('validatePropertyCollectionElementPredefinedDefaultValue', $input, $validationDto);
        } catch (PropertyException $e) {
            $failed = true;
        }
        self::assertFalse($failed);
    }

    public function validatePropertyCollectionPropertyValueThrowsExceptionIfValueDoesNotMatchDataProvider(): array
    {
        return [
            [
                'input' => 'foo',
                'allowedValues' => [],
                'untranslatedAllowedValues' => [],
            ],
            [
                'input' => 'foo',
                'allowedValues' => ['bar', 'baz'],
                'untranslatedAllowedValues' => ['bar', 'baz'],
            ],
            [
                'input' => 1,
                'allowedValues' => ['1', 2],
                'untranslatedAllowedValues' => ['1', 2],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validatePropertyCollectionPropertyValueThrowsExceptionIfValueDoesNotMatchDataProvider
     */
    public function validatePropertyCollectionPropertyValueThrowsExceptionIfValueDoesNotMatch($input, array $allowedValues, array $untranslatedAllowedValues): void
    {
        $this->expectException(PropertyException::class);

        $validationDto = new ValidationDto('standard', null, 'test-1', 'label', 'validators', 'StringLength');
        $validatorMock = $this->getAccessibleMock(
            CreatablePropertyCollectionElementPropertiesValidator::class,
            ['getConfigurationService'],
            [[], '', $validationDto]
        );

        $configurationServiceMock = $this->createMock(ConfigurationService::class);
        $configurationServiceMock->method(
            'getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetup'
        )->willReturnMap([
            [$validationDto, true, $allowedValues],
            [$validationDto, false, $untranslatedAllowedValues],
        ]);

        $validatorMock->method('getConfigurationService')->willReturn($configurationServiceMock);

        $validatorMock->_call('validatePropertyCollectionPropertyValue', $input, $validationDto);
    }

    public function validatePropertyCollectionPropertyValueThrowsNoExceptionIfValueMatchesDataProvider(): array
    {
        return [
            [
                'input' => 'foo',
                'allowedValues' => ['foo'],
                'untranslatedAllowedValues' => ['foo'],
                'allPossibleAllowedValuesTranslations' => [],
            ],
            [
                'input' => 'foo',
                'allowedValues' => ['bar'],
                'untranslatedAllowedValues' => ['foo'],
                'allPossibleAllowedValuesTranslations' => [],
            ],
            [
                'input' => 'foo',
                'allowedValues' => ['bar'],
                'untranslatedAllowedValues' => ['baz'],
                'allPossibleAllowedValuesTranslations' => ['default' => ['foo'], 'de' => ['bar']],
            ],
            [
                'input' => 'foo',
                'allowedValues' => ['foo', 'baz'],
                'untranslatedAllowedValues' => ['foo', 'baz'],
                'allPossibleAllowedValuesTranslations' => [],
            ],
            [
                'input' => 1,
                'allowedValues' => ['1', 1, 2],
                'untranslatedAllowedValues' => ['1', 1, 2],
                'allPossibleAllowedValuesTranslations' => [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validatePropertyCollectionPropertyValueThrowsNoExceptionIfValueMatchesDataProvider
     */
    public function validatePropertyCollectionPropertyValueThrowsNoExceptionIfValueMatches($input, array $allowedValues, array $untranslatedAllowedValues, array $allPossibleAllowedValuesTranslations): void
    {
        $validationDto = new ValidationDto('standard', null, 'test-1', 'label', 'validators', 'StringLength');
        $validatorMock = $this->getAccessibleMock(
            CreatablePropertyCollectionElementPropertiesValidator::class,
            ['getConfigurationService'],
            [[], '', $validationDto]
        );

        $configurationServiceMock = $this->createMock(ConfigurationService::class);
        $configurationServiceMock->method(
            'getAllowedValuesForPropertyCollectionPropertyFromFormEditorSetup'
        )->willReturnMap([
            [$validationDto, true, $allowedValues],
            [$validationDto, false, $untranslatedAllowedValues],
        ]);
        $configurationServiceMock->method('getAllBackendTranslationsForTranslationKeys')->willReturn($allPossibleAllowedValuesTranslations);
        $validatorMock->method('getConfigurationService')->willReturn($configurationServiceMock);

        $failed = false;
        try {
            $validatorMock->_call('validatePropertyCollectionPropertyValue', $input, $validationDto);
        } catch (PropertyException $e) {
            $failed = true;
        }
        self::assertFalse($failed);
    }
}
