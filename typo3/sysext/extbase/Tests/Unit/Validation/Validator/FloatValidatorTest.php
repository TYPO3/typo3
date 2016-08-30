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
 * Testcase for the float validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FloatValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\FloatValidator::class;

    public function setup()
    {
        $this->validator = $this->getMock($this->validatorClassName, ['translateErrorMessage']);
    }

    /**
     * Data provider with valid floats
     *
     * @return array
     */
    public function validFloats()
    {
        return [
            [1029437.234726],
            ['123.45'],
            ['+123.45'],
            ['-123.45'],
            ['123.45e3'],
            [123450.0]
        ];
    }

    /**
     * @test
     * @dataProvider validFloats
     * @param mixed $float
     */
    public function floatValidatorReturnsNoErrorsForAValidFloat($float)
    {
        $this->assertFalse($this->validator->validate($float)->hasErrors());
    }

    /**
     * Data provider with invalid floats
     *
     * @return array
     */
    public function invalidFloats()
    {
        return [
            [1029437],
            ['1029437'],
            ['not a number']
        ];
    }

    /**
     * @test
     * @dataProvider invalidFloats
     * @param mixed $float
     */
    public function floatValidatorReturnsErrorForAnInvalidFloat($float)
    {
        $this->assertTrue($this->validator->validate($float)->hasErrors());
    }

    /**
     * test
     */
    public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject()
    {
        $this->assertEquals(1, count($this->validator->validate(123456)->getErrors()));
    }
}
