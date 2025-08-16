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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Validator;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConjunctionValidatorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function addingValidatorsToAJunctionValidatorWorks(): void
    {
        $conjunctionValidator = new ConjunctionValidator();
        $conjunctionValidator->setOptions([]);
        $mockValidator = $this->createMock(ValidatorInterface::class);
        $conjunctionValidator->addValidator($mockValidator);
        self::assertTrue($conjunctionValidator->getValidators()->offsetExists($mockValidator));
    }

    #[Test]
    public function allValidatorsInTheConjunctionAreCalledEvenIfOneReturnsError(): void
    {
        $validatorConjunction = new ConjunctionValidator();
        $validatorConjunction->setOptions([]);
        $validatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $validatorObject->expects(self::once())->method('validate')->willReturn(new Result());
        $errors = new Result();
        $errors->addError(new Error('Error', 123));
        $secondValidatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $secondValidatorObject->expects(self::once())->method('validate')->willReturn($errors);
        $thirdValidatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $thirdValidatorObject->expects(self::once())->method('validate')->willReturn(new Result());
        $validatorConjunction->addValidator($validatorObject);
        $validatorConjunction->addValidator($secondValidatorObject);
        $validatorConjunction->addValidator($thirdValidatorObject);
        $validatorConjunction->validate('some subject');
    }

    #[Test]
    public function validatorConjunctionReturnsNoErrorsIfAllJunctionedValidatorsReturnNoErrors(): void
    {
        $validatorConjunction = new ConjunctionValidator();
        $validatorConjunction->setOptions([]);
        $validatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $validatorObject->method('validate')->willReturn(new Result());
        $secondValidatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $secondValidatorObject->method('validate')->willReturn(new Result());
        $validatorConjunction->addValidator($validatorObject);
        $validatorConjunction->addValidator($secondValidatorObject);
        self::assertFalse($validatorConjunction->validate('some subject')->hasErrors());
    }

    #[Test]
    public function validatorConjunctionReturnsErrorsIfOneValidatorReturnsErrors(): void
    {
        $validatorConjunction = new ConjunctionValidator();
        $validatorConjunction->setOptions([]);
        $validatorObject = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $errors = new Result();
        $errors->addError(new Error('Error', 123));
        $validatorObject->method('validate')->willReturn($errors);
        $validatorConjunction->addValidator($validatorObject);
        self::assertTrue($validatorConjunction->validate('some subject')->hasErrors());
    }

    #[Test]
    public function removingAValidatorOfTheValidatorConjunctionWorks(): void
    {
        $validatorConjunction = new ConjunctionValidator();
        $validatorConjunction->setOptions([]);
        $validator1 = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $validator2 = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $validatorConjunction->addValidator($validator1);
        $validatorConjunction->addValidator($validator2);
        $validatorConjunction->removeValidator($validator1);
        self::assertFalse($validatorConjunction->getValidators()->offsetExists($validator1));
        self::assertTrue($validatorConjunction->getValidators()->offsetExists($validator2));
    }

    #[Test]
    public function removingANotExistingValidatorIndexThrowsException(): void
    {
        $this->expectException(NoSuchValidatorException::class);
        $this->expectExceptionCode(1207020177);
        $validatorConjunction = new ConjunctionValidator();
        $validatorConjunction->setOptions([]);
        $validator = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $validatorConjunction->removeValidator($validator);
    }

    #[Test]
    public function countReturnsTheNumberOfValidatorsContainedInTheConjunction(): void
    {
        $validatorConjunction = new ConjunctionValidator();
        $validatorConjunction->setOptions([]);
        $validator1 = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        $validator2 = $this->getMockBuilder(ValidatorInterface::class)
            ->onlyMethods(['validate', 'getOptions', 'setOptions'])
            ->getMock();
        self::assertCount(0, $validatorConjunction);
        $validatorConjunction->addValidator($validator1);
        $validatorConjunction->addValidator($validator2);
        self::assertCount(2, $validatorConjunction);
    }
}
