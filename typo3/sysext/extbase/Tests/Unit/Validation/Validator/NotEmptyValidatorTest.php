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

/**
 * Testcase for the not empty validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NotEmptyValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator::class;

	public function setup() {
		$this->validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorReturnsNoErrorForASimpleString() {
		$this->assertFalse($this->validator->validate('a not empty string')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorReturnsErrorForAnEmptyString() {
		$this->assertTrue($this->validator->validate('')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorReturnsErrorForANullValue() {
		$this->assertTrue($this->validator->validate(NULL)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorForAnEmptySubject() {
		$this->assertEquals(1, count($this->validator->validate('')->getErrors()));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function notEmptyValidatorCreatesTheCorrectErrorForANullValue() {
		$this->assertEquals(1, count($this->validator->validate(NULL)->getErrors()));
	}

	/**
	 * @test
	 * @author Ingo Pfennigstorf <i.pfennigstorf@gmail.com>
	 */
	public function notEmptyValidatorWorksForEmptyArrays() {
		$this->assertTrue($this->validator->validate(array())->hasErrors());
		$this->assertFalse($this->validator->validate(array(1 => 2))->hasErrors());
	}

	/**
	 * @test
	 * @author Ingo Pfennigstorf <i.pfennigstorf@gmail.com>
	 */
	public function notEmptyValidatorWorksForEmptyCountableObjects() {
		$this->assertTrue($this->validator->validate(new \SplObjectStorage())->hasErrors());
	}

	/**
	 * @test
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 */
	public function notEmptyValidatorWorksForNotEmptyCountableObjects() {
		$countableObject = new \SplObjectStorage();
		$countableObject->attach(new \StdClass());
		$this->assertFalse($this->validator->validate($countableObject)->hasErrors());
	}

}
