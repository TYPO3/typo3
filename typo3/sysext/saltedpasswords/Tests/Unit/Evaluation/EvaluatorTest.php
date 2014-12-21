<?php
namespace TYPO3\CMS\Saltedpasswords\Tests\Unit\Evaluation;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Evaluation\Evaluator;

/**
 * Testcase for SaltedPasswordsUtility
 *
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 */
class EvaluatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var Evaluator
	 */
	protected $subject;

	/**
	 * Set up the a test
	 */
	public function setUp() {
		$this->subject = $this->getMock('TYPO3\\CMS\\Saltedpasswords\\Evaluation\\Evaluator', array('dummy'));

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
		$saltedPassword = $this->subject->evaluateFieldValue($originalPassword, '', $isSet);
		$this->assertTrue($isSet);
		$this->assertNotEquals($originalPassword, $saltedPassword);
		$this->assertTrue(GeneralUtility::inList('$1$,$2$,$2a,$P$', substr($saltedPassword, 0, 3)));
	}

	/**
	 * @test
	 */
	public function md5HashIsUpdatedToTemporarySaltedString() {
		$isSet = NULL;
		$originalPassword = '5f4dcc3b5aa765d61d8327deb882cf99';
		$saltedPassword = $this->subject->evaluateFieldValue($originalPassword, '', $isSet);
		$this->assertTrue($isSet);
		$this->assertNotEquals($originalPassword, $saltedPassword);
		$this->assertTrue(GeneralUtility::isFirstPartOfStr($saltedPassword, 'M$'));
	}

	/**
	 * @test
	 */
	public function temporarySaltedStringIsNotTouched() {
		$isSet = NULL;
		$originalPassword = 'M$P$CibIRipvLfaPlaaeH8ifu9g21BrPjp.';
		$saltedPassword = $this->subject->evaluateFieldValue($originalPassword, '', $isSet);
		$this->assertSame(NULL, $isSet);
		$this->assertSame($originalPassword, $saltedPassword);
	}
}
