<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
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

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * Testcase
 */
class GenericObjectValidatorTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function validatorShouldReturnErrorsIfTheValueIsNoObjectAndNotNull()
    {
        $this->assertTrue((new GenericObjectValidator())->validate('foo')->hasErrors());
    }

    /**
     * @test
     */
    public function validatorShouldReturnNoErrorsIfTheValueIsNull()
    {
        $this->assertFalse((new GenericObjectValidator())->validate(null)->hasErrors());
    }

    /**
     * @return array
     */
    public function dataProviderForValidator(): array
    {
        $error1 = new Error('error1', 1);
        $error2 = new Error('error2', 2);
        $emptyResult1 = new Result();
        $emptyResult2 = new Result();
        $resultWithError1 = new Result();
        $resultWithError1->addError($error1);
        $resultWithError2 = new Result();
        $resultWithError2->addError($error2);
        $objectWithPrivateProperties = new class() {
            protected $foo = 'foovalue';
            protected $bar = 'barvalue';
        };

        return [
            // If no errors happened, this is shown
            [$objectWithPrivateProperties, $emptyResult1, $emptyResult2, []],
            // If errors on two properties happened, they are merged together.
            [$objectWithPrivateProperties, $resultWithError1, $resultWithError2, ['foo' => [$error1], 'bar' => [$error2]]]
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForValidator
     *
     * @param mixed $objectToBeValidated
     * @param mixed $validationResultForFoo
     * @param mixed $validationResultForBar
     * @param mixed $errors
     */
    public function validateChecksAllPropertiesForWhichAPropertyValidatorExists($objectToBeValidated, $validationResultForFoo, $validationResultForBar, $errors)
    {
        $validator = new GenericObjectValidator();

        /** @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $validatorForFoo */
        $validatorForFoo = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validatorForFoo->expects($this->once())->method('validate')->with('foovalue')->will($this->returnValue($validationResultForFoo));

        /** @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $validatorForBar */
        $validatorForBar = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validatorForBar->expects($this->once())->method('validate')->with('barvalue')->will($this->returnValue($validationResultForBar));

        $validator->addPropertyValidator('foo', $validatorForFoo);
        $validator->addPropertyValidator('bar', $validatorForBar);

        $this->assertEquals($errors, $validator->validate($objectToBeValidated)->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function validateCanHandleRecursiveTargetsWithoutEndlessLooping()
    {
        $A = new class() {
            public $b;
        };

        $B = new class() {
            public $a;
        };

        $A->b = $B;
        $B->a = $A;

        $aValidator = new GenericObjectValidator();
        $bValidator = new GenericObjectValidator();

        $aValidator->addPropertyValidator('b', $bValidator);
        $bValidator->addPropertyValidator('a', $aValidator);

        $this->assertFalse($aValidator->validate($A)->hasErrors());
    }

    /**
     * @test
     */
    public function validateDetectsFailuresInRecursiveTargetsI()
    {
        $A = new class() {
            public $b;
        };

        $B = new class() {
            public $a;
            public $uuid = 0xF;
        };

        $A->b = $B;
        $B->a = $A;
        $aValidator = new GenericObjectValidator();
        $bValidator = new GenericObjectValidator();

        $aValidator->addPropertyValidator('b', $bValidator);
        $bValidator->addPropertyValidator('a', $aValidator);
        $error = new Error('error1', 123);
        $result = new Result();
        $result->addError($error);

        /** @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $mockUuidValidator */
        $mockUuidValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $mockUuidValidator->expects($this->any())->method('validate')->with(15)->will($this->returnValue($result));
        $bValidator->addPropertyValidator('uuid', $mockUuidValidator);

        $this->assertSame(['b.uuid' => [$error]], $aValidator->validate($A)->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function validateDetectsFailuresInRecursiveTargetsII()
    {
        $A = new class() {
            public $b;
            public $uuid = 0xF;
        };

        $B = new class() {
            public $a;
            public $uuid = 0xF;
        };

        $A->b = $B;
        $B->a = $A;
        $aValidator = new GenericObjectValidator();
        $bValidator = new GenericObjectValidator();

        $aValidator->addPropertyValidator('b', $bValidator);
        $bValidator->addPropertyValidator('a', $aValidator);
        $error1 = new Error('error1', 123);
        $result1 = new Result();
        $result1->addError($error1);

        /** @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $mockUuidValidator */
        $mockUuidValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $mockUuidValidator->expects($this->any())->method('validate')->with(15)->will($this->returnValue($result1));
        $aValidator->addPropertyValidator('uuid', $mockUuidValidator);
        $bValidator->addPropertyValidator('uuid', $mockUuidValidator);

        $this->assertSame(['b.uuid' => [$error1], 'uuid' => [$error1]], $aValidator->validate($A)->getFlattenedErrors());
    }
}
