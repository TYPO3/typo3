<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

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

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DisjunctionValidatorTest extends UnitTestCase
{

    /**
     * @test
     */
    public function validateReturnsNoErrorsIfOneValidatorReturnsNoError()
    {
        $validatorDisjunction = new \TYPO3\CMS\Extbase\Validation\Validator\DisjunctionValidator([]);
        $validatorObject = $this->getMockBuilder(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validatorObject->expects(self::any())->method('validate')->willReturn(new \TYPO3\CMS\Extbase\Error\Result());
        $errors = new \TYPO3\CMS\Extbase\Error\Result();
        $errors->addError(new \TYPO3\CMS\Extbase\Error\Error('Error', 123));
        $secondValidatorObject = $this->getMockBuilder(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $secondValidatorObject->expects(self::any())->method('validate')->willReturn($errors);
        $validatorDisjunction->addValidator($validatorObject);
        $validatorDisjunction->addValidator($secondValidatorObject);
        self::assertFalse($validatorDisjunction->validate('some subject')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsAllErrorsIfAllValidatorsReturnErrors()
    {
        $validatorDisjunction = new \TYPO3\CMS\Extbase\Validation\Validator\DisjunctionValidator([]);
        $error1 = new \TYPO3\CMS\Extbase\Error\Error('Error', 123);
        $error2 = new \TYPO3\CMS\Extbase\Error\Error('Error2', 123);
        $errors1 = new \TYPO3\CMS\Extbase\Error\Result();
        $errors1->addError($error1);
        $validatorObject = $this->getMockBuilder(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validatorObject->expects(self::any())->method('validate')->willReturn($errors1);
        $errors2 = new \TYPO3\CMS\Extbase\Error\Result();
        $errors2->addError($error2);
        $secondValidatorObject = $this->getMockBuilder(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $secondValidatorObject->expects(self::any())->method('validate')->willReturn($errors2);
        $validatorDisjunction->addValidator($validatorObject);
        $validatorDisjunction->addValidator($secondValidatorObject);
        self::assertEquals([$error1, $error2], $validatorDisjunction->validate('some subject')->getErrors());
    }
}
