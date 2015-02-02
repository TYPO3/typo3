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
class NumberRangeValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	protected $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator';

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsNoErrorForASimpleIntegerInRange() {
		$options = array('minimum' => 0, 'maximum' => 1000);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate(10.5)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsErrorForANumberOutOfRange() {
		$options = array('minimum' => 0, 'maximum' => 1000);
		$validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
		$this->assertTrue($validator->validate(1000.1)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsNoErrorForANumberInReversedRange() {
		$options = array('minimum' => 1000, 'maximum' => 0);
		$validator = $this->getMock($this->validatorClassName, array('dummy'), array($options));
		$this->assertFalse($validator->validate(100)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsErrorForAString() {
		$options = array('minimum' => 0, 'maximum' => 1000);
		$validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
		$this->assertTrue($validator->validate('not a number')->hasErrors());
	}
}
