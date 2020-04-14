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
    public function validatePropertyCollectionElementPredefinedDefaultValueThrowsExceptionIfValueDoesNotMatch()
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
        $configurationService->expects(self::any())->method(
            'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup'
        )->willReturn('default');
        $typeConverter->expects(self::any())->method('getConfigurationService')->willReturn($configurationService);

        $input = 'xxx';
        $typeConverter->_call('validatePropertyCollectionElementPredefinedDefaultValue', $input, $validationDto);
    }

    /**
     * @test
     */
    public function validatePropertyCollectionElementPredefinedDefaultValueThrowsNoExceptionIfValueMatches()
    {
        $validationDto = new ValidationDto(null, null, 'test-1', 'label', 'validators', 'StringLength');
        $typeConverter = $this->getAccessibleMock(
            CreatablePropertyCollectionElementPropertiesValidator::class,
            ['getConfigurationService'],
            [[], '', $validationDto]
        );
        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService->expects(self::any())->method(
            'getPropertyCollectionPredefinedDefaultValueFromFormEditorSetup'
        )->willReturn('default');
        $typeConverter->expects(self::any())->method('getConfigurationService')->willReturn($configurationService);

        $input = 'default';

        $failed = false;
        try {
            $typeConverter->_call('validatePropertyCollectionElementPredefinedDefaultValue', $input, $validationDto);
        } catch (PropertyException $e) {
            $failed = true;
        }
        self::assertFalse($failed);
    }
}
