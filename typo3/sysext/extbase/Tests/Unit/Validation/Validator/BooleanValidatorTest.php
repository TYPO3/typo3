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
 * Testcase for the number range validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class BooleanValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase
{
    protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\BooleanValidator::class;

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForAFalseStringExpectation()
    {
        $options = array('is' => 'false');
        $validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
        $this->assertFalse($validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForATrueStringExpectation()
    {
        $options = array('is' => 'true');
        $validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
        $this->assertFalse($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForATrueExpectation()
    {
        $options = array('is' => true);
        $validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
        $this->assertFalse($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsNoErrorForAFalseExpectation()
    {
        $options = array('is' => false);
        $validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
        $this->assertFalse($validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsErrorForTrueWhenFalseExpected()
    {
        $options = array('is' => false);
        $validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
        $this->assertTrue($validator->validate(true)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsErrorForFalseWhenTrueExpected()
    {
        $options = array('is' => true);
        $validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
        $this->assertTrue($validator->validate(false)->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsErrorForAString()
    {
        $options = array('is' => true);
        $validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
        $this->assertTrue($validator->validate('a string')->hasErrors());
    }

    /**
     * @test
     */
    public function booleanValidatorReturnsTrueIfNoParameterIsGiven()
    {
        $options = array();
        $validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
        $this->assertFalse($validator->validate(true)->hasErrors());
    }
}
