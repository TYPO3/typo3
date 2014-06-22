<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/**
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
 * Test case
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class RawValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function theRawValidatorAlwaysReturnsTRUE() {
		$rawValidator = $this->getMock('TYPO3\CMS\Extbase\Validation\Validator\RawValidator', array('addError'), array(), '', FALSE);
		$rawValidator->expects($this->never())->method('addError');
		$rawValidator->isValid('simple1expression');
		$rawValidator->isValid('');
		$rawValidator->isValid(NULL);
		$rawValidator->isValid(FALSE);
		$rawValidator->isValid(new \ArrayObject());
	}
}
