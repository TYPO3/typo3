<?php
namespace TYPO3\CMS\Saltedpasswords\Tests\Unit\Evaluation;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Marcus Krause <marcus#exp2009@t3sec.info>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for SaltedPasswordsUtility
 *
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 */
class EvaluatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Saltedpasswords\Evaluation\Evaluator
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Saltedpasswords\\Evaluation\\Evaluator', array('dummy'));

		// Make sure SaltedPasswordsUtility::isUsageEnabled() returns TRUE
		unset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['saltedpasswords']);
		$GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'] = 'rsa';
	}

	/**
	 * @test
	 */
	public function passwordIsTurnedIntoSaltedString() {
		$isSet = NULL;
		$originalPassword = 'password';
		$saltedPassword = $this->fixture->evaluateFieldValue($originalPassword, '', $isSet);
		$this->assertTrue($isSet);
		$this->assertNotEquals($originalPassword, $saltedPassword);
		$this->assertTrue(\TYPO3\CMS\Core\Utility\GeneralUtility::inList('$1$,$2$,$2a,$P$', substr($saltedPassword, 0, 3)));
	}

	/**
	 * @test
	 */
	public function md5HashIsUpdatedToTemporarySaltedString() {
		$isSet = NULL;
		$originalPassword = '5f4dcc3b5aa765d61d8327deb882cf99';
		$saltedPassword = $this->fixture->evaluateFieldValue($originalPassword, '', $isSet);
		$this->assertTrue($isSet);
		$this->assertNotEquals($originalPassword, $saltedPassword);
		$this->assertTrue(\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($saltedPassword, 'M$'));
	}

	/**
	 * @test
	 */
	public function temporarySaltedStringIsNotTouched() {
		$isSet = NULL;
		$originalPassword = 'M$P$CibIRipvLfaPlaaeH8ifu9g21BrPjp.';
		$saltedPassword = $this->fixture->evaluateFieldValue($originalPassword, '', $isSet);
		$this->assertFalse($isSet);
		$this->assertSame($originalPassword, $saltedPassword);
	}
}


?>