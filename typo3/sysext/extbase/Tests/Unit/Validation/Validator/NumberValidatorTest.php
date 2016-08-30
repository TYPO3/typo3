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
 * Test case
 */
class NumberValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\NumberValidator::class;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface
     */
    protected $validator;

    public function setup()
    {
        $this->validator = $this->getMock($this->validatorClassName, ['translateErrorMessage']);
    }

    /**
     * @test
     */
    public function numberValidatorReturnsTrueForASimpleInteger()
    {
        $this->assertFalse($this->validator->validate(1029437)->hasErrors());
    }

    /**
     * @test
     */
    public function numberValidatorReturnsFalseForAString()
    {
        $expectedResult = new \TYPO3\CMS\Extbase\Error\Result();
        // we only test for the error code, after the message translation method is mocked
        $expectedResult->addError(new \TYPO3\CMS\Extbase\Validation\Error(null, 1221563685));
        $this->assertEquals($expectedResult, $this->validator->validate('not a number'));
    }
}
