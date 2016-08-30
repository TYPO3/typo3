<?php
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

/**
 * Testcase for the Generic Object Validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class GenericObjectValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator::class;

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function validatorShouldReturnErrorsIfTheValueIsNoObjectAndNotNull()
    {
        $this->assertTrue($this->validator->validate('foo')->hasErrors());
    }

    /**
     * @test
     */
    public function validatorShouldReturnNoErrorsIfTheValueIsNull()
    {
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @return array
     */
    public function dataProviderForValidator()
    {
        $error1 = new \TYPO3\CMS\Extbase\Error\Error('error1', 1);
        $error2 = new \TYPO3\CMS\Extbase\Error\Error('error2', 2);
        $emptyResult1 = new \TYPO3\CMS\Extbase\Error\Result();
        $emptyResult2 = new \TYPO3\CMS\Extbase\Error\Result();
        $resultWithError1 = new \TYPO3\CMS\Extbase\Error\Result();
        $resultWithError1->addError($error1);
        $resultWithError2 = new \TYPO3\CMS\Extbase\Error\Result();
        $resultWithError2->addError($error2);
        $classNameForObjectWithPrivateProperties = $this->getUniqueId('B');
        eval('class ' . $classNameForObjectWithPrivateProperties . '{ protected $foo = \'foovalue\'; protected $bar = \'barvalue\'; }');
        $objectWithPrivateProperties = new $classNameForObjectWithPrivateProperties();
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
     * @param mixed $mockObject
     * @param mixed $validationResultForFoo
     * @param mixed $validationResultForBar
     * @param mixed $errors
     */
    public function validateChecksAllPropertiesForWhichAPropertyValidatorExists($mockObject, $validationResultForFoo, $validationResultForBar, $errors)
    {
        $validatorForFoo = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $validatorForFoo->expects($this->once())->method('validate')->with('foovalue')->will($this->returnValue($validationResultForFoo));
        $validatorForBar = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $validatorForBar->expects($this->once())->method('validate')->with('barvalue')->will($this->returnValue($validationResultForBar));
        $this->validator->addPropertyValidator('foo', $validatorForFoo);
        $this->validator->addPropertyValidator('bar', $validatorForBar);
        $this->assertEquals($errors, $this->validator->validate($mockObject)->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function validateCanHandleRecursiveTargetsWithoutEndlessLooping()
    {
        $classNameA = $this->getUniqueId('B');
        eval('class ' . $classNameA . '{ public $b; }');
        $classNameB = $this->getUniqueId('B');
        eval('class ' . $classNameB . '{ public $a; }');
        $A = new $classNameA();
        $B = new $classNameB();
        $A->b = $B;
        $B->a = $A;

        $aValidator = new \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator([]);
        $bValidator = new \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator([]);

        $aValidator->addPropertyValidator('b', $bValidator);
        $bValidator->addPropertyValidator('a', $aValidator);
        $this->assertFalse($aValidator->validate($A)->hasErrors());
    }

    /**
     * @test
     */
    public function validateDetectsFailuresInRecursiveTargetsI()
    {
        $classNameA = $this->getUniqueId('A');
        eval('class ' . $classNameA . '{ public $b; }');
        $classNameB = $this->getUniqueId('B');
        eval('class ' . $classNameB . '{ public $a; public $uuid = 0xF; }');
        $A = new $classNameA();
        $B = new $classNameB();
        $A->b = $B;
        $B->a = $A;
        $aValidator = $this->getValidator();
        $bValidator = $this->getValidator();

        $aValidator->addPropertyValidator('b', $bValidator);
        $bValidator->addPropertyValidator('a', $aValidator);
        $error = new \TYPO3\CMS\Extbase\Error\Error('error1', 123);
        $result = new \TYPO3\CMS\Extbase\Error\Result();
        $result->addError($error);
        $mockUuidValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $mockUuidValidator->expects($this->any())->method('validate')->with(15)->will($this->returnValue($result));
        $bValidator->addPropertyValidator('uuid', $mockUuidValidator);
        $this->assertSame(['b.uuid' => [$error]], $aValidator->validate($A)->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function validateDetectsFailuresInRecursiveTargetsII()
    {
        $classNameA = $this->getUniqueId('A');
        eval('class ' . $classNameA . '{ public $b; public $uuid = 0xF; }');
        $classNameB = $this->getUniqueId('B');
        eval('class ' . $classNameB . '{ public $a; public $uuid = 0xF; }');
        $A = new $classNameA();
        $B = new $classNameB();
        $A->b = $B;
        $B->a = $A;
        $aValidator = $this->getValidator();
        $bValidator = $this->getValidator();

        $aValidator->addPropertyValidator('b', $bValidator);
        $bValidator->addPropertyValidator('a', $aValidator);
        $error1 = new \TYPO3\CMS\Extbase\Error\Error('error1', 123);
        $result1 = new \TYPO3\CMS\Extbase\Error\Result();
        $result1->addError($error1);
        $mockUuidValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, ['validate', 'getOptions']);
        $mockUuidValidator->expects($this->any())->method('validate')->with(15)->will($this->returnValue($result1));
        $aValidator->addPropertyValidator('uuid', $mockUuidValidator);
        $bValidator->addPropertyValidator('uuid', $mockUuidValidator);
        $this->assertSame(['b.uuid' => [$error1], 'uuid' => [$error1]], $aValidator->validate($A)->getFlattenedErrors());
    }
}
