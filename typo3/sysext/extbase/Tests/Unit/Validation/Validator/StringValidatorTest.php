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
 * Testcase for the string length validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StringValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\StringValidator::class;

	public function setup() {
		$this->validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function stringValidatorShouldValidateString() {
		$this->assertFalse($this->validator->validate('Hello World')->hasErrors());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function stringValidatorShouldReturnErrorIfNumberIsGiven() {
		$this->assertTrue($this->validator->validate(42)->hasErrors());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function stringValidatorShouldReturnErrorIfObjectWithToStringMethodStringIsGiven() {
		$className = $this->getUniqueId('TestClass');
		eval('
			class ' . $className . ' {
				public function __toString() {
					return "ASDF";
				}
			}
		');
		$object = new $className();
		$this->assertTrue($this->validator->validate($object)->hasErrors());
	}

}
