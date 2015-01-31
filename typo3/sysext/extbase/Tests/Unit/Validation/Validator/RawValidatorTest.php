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
 * Testcase for the raw validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RawValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\RawValidator::class;

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRawValidatorAlwaysReturnsNoErrors() {
		$rawValidator = new \TYPO3\CMS\Extbase\Validation\Validator\RawValidator(array());
		$this->assertFalse($rawValidator->validate('simple1expression')->hasErrors());
		$this->assertFalse($rawValidator->validate('')->hasErrors());
		$this->assertFalse($rawValidator->validate(NULL)->hasErrors());
		$this->assertFalse($rawValidator->validate(FALSE)->hasErrors());
		$this->assertFalse($rawValidator->validate(new \ArrayObject())->hasErrors());
	}

}
