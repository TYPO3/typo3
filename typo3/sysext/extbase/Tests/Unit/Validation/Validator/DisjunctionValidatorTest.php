<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test case
 */
class DisjunctionValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function allValidatorsInTheDisjunctionAreCalledEvenIfOneReturnsNoError()
    {
        $this->markTestSkipped('Needs a bugfix of Flow first.');
        $validatorDisjunction = new \TYPO3\CMS\Extbase\Validation\Validator\DisjunctionValidator([]);
        $validatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $validatorObject->expects($this->once())->method('validate')->will($this->returnValue(new \TYPO3\CMS\Extbase\Error\Result()));
        $errors = new \TYPO3\CMS\Extbase\Error\Result();
        $errors->addError(new \TYPO3\CMS\Extbase\Error\Error('Error', 123));
        $secondValidatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $secondValidatorObject->expects($this->exactly(1))->method('validate')->will($this->returnValue($errors));
        $validatorDisjunction->addValidator($validatorObject);
        $validatorDisjunction->addValidator($secondValidatorObject);
        $validatorDisjunction->validate('some subject');
    }

    /**
     * @test
     */
    public function validateReturnsNoErrorsIfOneValidatorReturnsNoError()
    {
        $validatorDisjunction = new \TYPO3\CMS\Extbase\Validation\Validator\DisjunctionValidator([]);
        $validatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $validatorObject->expects($this->any())->method('validate')->will($this->returnValue(new \TYPO3\CMS\Extbase\Error\Result()));
        $errors = new \TYPO3\CMS\Extbase\Error\Result();
        $errors->addError(new \TYPO3\CMS\Extbase\Error\Error('Error', 123));
        $secondValidatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors));
        $validatorDisjunction->addValidator($validatorObject);
        $validatorDisjunction->addValidator($secondValidatorObject);
        $this->assertFalse($validatorDisjunction->validate('some subject')->hasErrors());
    }

    /**
     * @test
     */
    public function validateReturnsAllErrorsIfAllValidatorsReturnErrrors()
    {
        $validatorDisjunction = new \TYPO3\CMS\Extbase\Validation\Validator\DisjunctionValidator([]);
        $error1 = new \TYPO3\CMS\Extbase\Error\Error('Error', 123);
        $error2 = new \TYPO3\CMS\Extbase\Error\Error('Error2', 123);
        $errors1 = new \TYPO3\CMS\Extbase\Error\Result();
        $errors1->addError($error1);
        $validatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $validatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors1));
        $errors2 = new \TYPO3\CMS\Extbase\Error\Result();
        $errors2->addError($error2);
        $secondValidatorObject = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $secondValidatorObject->expects($this->any())->method('validate')->will($this->returnValue($errors2));
        $validatorDisjunction->addValidator($validatorObject);
        $validatorDisjunction->addValidator($secondValidatorObject);
        $this->assertEquals([$error1, $error2], $validatorDisjunction->validate('some subject')->getErrors());
    }
}
