<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Configuration\FormDefinition\Validators;

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

use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PropertyException;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\CreatableFormElementPropertiesValidator;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators\ValidationDto;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CreatableFormElementPropertiesValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validateFormElementPredefinedDefaultValueThrowsExceptionIfValueDoesNotMatch()
    {
        $this->expectException(PropertyException::class);
        $this->expectExceptionCode(1528588035);

        $validationDto = new ValidationDto('standard', null, 'test-1', 'label');
        $input = 'xxx';
        $typeConverter = $this->getAccessibleMock(
            CreatableFormElementPropertiesValidator::class,
            ['getConfigurationService'],
            [[], '', $validationDto]
        );
        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService->expects($this->any())
            ->method('getFormElementPredefinedDefaultValueFromFormEditorSetup')
            ->willReturn('default');
        $configurationService->expects($this->any())
            ->method('isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup')
            ->willReturn(true);
        $typeConverter->expects($this->any())->method('getConfigurationService')->willReturn($configurationService);

        $typeConverter($input, '');
    }

    /**
     * @test
     */
    public function validateFormElementPredefinedDefaultValueThrowsNoExceptionIfValueMatches()
    {
        $validationDto = new ValidationDto(null, null, 'test-1', 'label');
        $typeConverter = $this->getAccessibleMock(
            CreatableFormElementPropertiesValidator::class,
            ['getConfigurationService'],
            [[], '', $validationDto]
        );
        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService->expects($this->any())
            ->method('getFormElementPredefinedDefaultValueFromFormEditorSetup')
            ->willReturn('default');
        $configurationService->expects($this->any())
            ->method('isFormElementPropertyDefinedInPredefinedDefaultsInFormEditorSetup')
            ->willReturn(true);
        $typeConverter->expects($this->any())->method('getConfigurationService')->willReturn($configurationService);

        $failed = false;
        try {
            $typeConverter('', 'default');
        } catch (PropertyException $e) {
            $failed = true;
        }
        $this->assertFalse($failed);
    }

    public function validateFormElementValueThrowsExceptionIfValueDoesNotMatchDataProvider(): array
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
                'input' => 'foo',
                'allowedValues' => ['bar', 'baz'],
                'untranslatedAllowedValues' => ['bar', 'world'],
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
     * @dataProvider validateFormElementValueThrowsExceptionIfValueDoesNotMatchDataProvider
     */
    public function validateFormElementValueThrowsExceptionIfValueDoesNotMatch($input, array $allowedValues, array $untranslatedAllowedValues)
    {
        $this->expectException(PropertyException::class);

        $validationDto = new ValidationDto('standard', null, 'test-1', 'label');
        $validatorMock = $this->getAccessibleMock(
            CreatableFormElementPropertiesValidator::class,
            ['getConfigurationService'],
            [[], '', $validationDto]
        );

        $configurationServiceMock = $this->createMock(ConfigurationService::class);
        $configurationServiceMock->expects(self::any())->method(
            'getAllowedValuesForFormElementPropertyFromFormEditorSetup'
        )->willReturnMap([
            [$validationDto, true, $allowedValues],
            [$validationDto, false, $untranslatedAllowedValues],
        ]);

        $validatorMock->expects(self::any())->method('getConfigurationService')->willReturn($configurationServiceMock);

        $validatorMock->_call('validateFormElementValue', $input, $validationDto);
    }

    public function validateFormElementValueThrowsNoExceptionIfValueMatchesDataProvider(): array
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
     * @dataProvider validateFormElementValueThrowsNoExceptionIfValueMatchesDataProvider
     */
    public function validateFormElementValueThrowsNoExceptionIfValueMatches($input, array $allowedValues, array $untranslatedAllowedValues, array $allPossibleAllowedValuesTranslations)
    {
        $validationDto = new ValidationDto('standard', null, 'test-1', 'label');
        $validatorMock = $this->getAccessibleMock(
            CreatableFormElementPropertiesValidator::class,
            ['getConfigurationService'],
            [[], '', $validationDto]
        );

        $configurationServiceMock = $this->createMock(ConfigurationService::class);
        $configurationServiceMock->expects(self::any())->method(
            'getAllowedValuesForFormElementPropertyFromFormEditorSetup'
        )->willReturnMap([
            [$validationDto, true, $allowedValues],
            [$validationDto, false, $untranslatedAllowedValues],
        ]);
        $configurationServiceMock->expects(self::any())->method('getAllBackendTranslationsForTranslationKeys')->willReturn($allPossibleAllowedValuesTranslations);
        $validatorMock->expects(self::any())->method('getConfigurationService')->willReturn($configurationServiceMock);

        $failed = false;
        try {
            $validatorMock->_call('validateFormElementValue', $input, $validationDto);
        } catch (PropertyException $e) {
            $failed = true;
        }
        self::assertFalse($failed);
    }
}
