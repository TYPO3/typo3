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

namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ConjunctionValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addingValidatorsToAJunctionValidatorWorks()
    {
        $conjunctionValidator = new ConjunctionValidator();
        $mockValidator = $this->createMock(ValidatorInterface::class);
        $conjunctionValidator->addValidator($mockValidator);
        self::assertTrue($conjunctionValidator->getValidators()->contains($mockValidator));
    }

    /**
     * @test
     */
    public function allValidatorsInTheConjunctionAreCalledEvenIfOneReturnsError()
    {
        $validatorConjunction = new ConjunctionValidator();
        $validatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validatorObject->expects(self::once())->method('validate')->willReturn(new Result());
        $errors = new Result();
        $errors->addError(new Error('Error', 123));
        $secondValidatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $secondValidatorObject->expects(self::once())->method('validate')->willReturn($errors);
        $thirdValidatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $thirdValidatorObject->expects(self::once())->method('validate')->willReturn(new Result());
        $validatorConjunction->addValidator($validatorObject);
        $validatorConjunction->addValidator($secondValidatorObject);
        $validatorConjunction->addValidator($thirdValidatorObject);
        $validatorConjunction->validate('some subject');
    }

    /**
     * @test
     */
    public function validatorConjunctionReturnsNoErrorsIfAllJunctionedValidatorsReturnNoErrors()
    {
        $validatorConjunction = new ConjunctionValidator([]);
        $validatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validatorObject->expects(self::any())->method('validate')->willReturn(new Result());
        $secondValidatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $secondValidatorObject->expects(self::any())->method('validate')->willReturn(new Result());
        $validatorConjunction->addValidator($validatorObject);
        $validatorConjunction->addValidator($secondValidatorObject);
        self::assertFalse($validatorConjunction->validate('some subject')->hasErrors());
    }

    /**
     * @test
     */
    public function validatorConjunctionReturnsErrorsIfOneValidatorReturnsErrors()
    {
        $validatorConjunction = new ConjunctionValidator([]);
        $validatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $errors = new Result();
        $errors->addError(new Error('Error', 123));
        $validatorObject->expects(self::any())->method('validate')->willReturn($errors);
        $validatorConjunction->addValidator($validatorObject);
        self::assertTrue($validatorConjunction->validate('some subject')->hasErrors());
    }

    /**
     * @test
     */
    public function removingAValidatorOfTheValidatorConjunctionWorks()
    {
        $validatorConjunction = new ConjunctionValidator();
        $validator1 = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validator2 = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validatorConjunction->addValidator($validator1);
        $validatorConjunction->addValidator($validator2);
        $validatorConjunction->removeValidator($validator1);
        self::assertFalse($validatorConjunction->getValidators()->contains($validator1));
        self::assertTrue($validatorConjunction->getValidators()->contains($validator2));
    }

    /**
     * @test
     */
    public function removingANotExistingValidatorIndexThrowsException()
    {
        $this->expectException(NoSuchValidatorException::class);
        $this->expectExceptionCode(1207020177);
        $validatorConjunction = new ConjunctionValidator();
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validatorConjunction->removeValidator($validator);
    }

    /**
     * @test
     */
    public function countReturnsTheNumberOfValidatorsContainedInTheConjunction()
    {
        $validatorConjunction = new ConjunctionValidator();
        $validator1 = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validator2 = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        self::assertSame(0, count($validatorConjunction));
        $validatorConjunction->addValidator($validator1);
        $validatorConjunction->addValidator($validator2);
        self::assertSame(2, count($validatorConjunction));
    }
}
