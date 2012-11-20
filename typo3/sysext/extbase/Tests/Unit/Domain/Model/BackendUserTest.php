<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Felix Kopp <felix-source@phorax.com>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for \TYPO3\CMS\Extbase\Domain\Model\BackendUser.
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @api
 */
class BackendUserTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUser
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Extbase\Domain\Model\BackendUser();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function getUserNameInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getUserName());
	}

	/**
	 * @test
	 */
	public function setUserNameSetsUserName() {
		$userName = 'don.juan';
		$this->fixture->setUserName($userName);
		$this->assertSame($userName, $this->fixture->getUserName());
	}

	/**
	 * @test
	 */
	public function getIsAdministratorInitiallyReturnsFalse() {
		$this->assertFalse($this->fixture->getIsAdministrator());
	}

	/**
	 * @test
	 */
	public function setIsAdministratorCanSetIsAdministratorToTrue() {
		$this->fixture->setIsAdministrator(TRUE);
		$this->assertTrue($this->fixture->getIsAdministrator());
	}

	/**
	 * @test
	 */
	public function getIsDisabledInitiallyReturnsFalse() {
		$this->assertFalse($this->fixture->getIsDisabled());
	}

	/**
	 * @test
	 */
	public function setIsDisabledCanSetIsDisabledToTrue() {
		$this->fixture->setIsDisabled(TRUE);
		$this->assertTrue($this->fixture->getIsDisabled());
	}

	/**
	 * @test
	 */
	public function getStartDateAndTimeInitiallyReturnsNull() {
		$this->assertNull($this->fixture->getStartDateAndTime());
	}

	/**
	 * @test
	 */
	public function setStartDateAndTimeSetsStartDateAndTime() {
		$date = new \DateTime();
		$this->fixture->setStartDateAndTime($date);
		$this->assertSame($date, $this->fixture->getStartDateAndTime());
	}

	/**
	 * @test
	 */
	public function getEndDateAndTimeInitiallyReturnsNull() {
		$this->assertNull($this->fixture->getEndDateAndTime());
	}

	/**
	 * @test
	 */
	public function setEndDateAndTimeSetsEndDateAndTime() {
		$date = new \DateTime();
		$this->fixture->setEndDateAndTime($date);
		$this->assertSame($date, $this->fixture->getEndDateAndTime());
	}

	/**
	 * @test
	 */
	public function isActivatedInitiallyReturnsTrue() {
		$this->assertTrue($this->fixture->isActivated());
	}

	/**
	 * @test
	 */
	public function isActivatedForDisabledReturnsFalse() {
		$this->fixture->setIsDisabled(TRUE);
		$this->assertFalse($this->fixture->isActivated());
	}

	/**
	 * @test
	 */
	public function isActivatedForStartDateAndTimeInFutureReturnsFalse() {
		$tomorrow = new \DateTime('tomorrow');
		$this->fixture->setStartDateAndTime($tomorrow);
		$this->assertFalse($this->fixture->isActivated());
	}

	/**
	 * @test
	 */
	public function isActivatedForStartDateAndTimeInPastReturnsTrue() {
		$yesterday = new \DateTime('yesterday');
		$this->fixture->setStartDateAndTime($yesterday);
		$this->assertTrue($this->fixture->isActivated());
	}

	/**
	 * @test
	 */
	public function isActivatedForEndDateAndTimeInFutureReturnsTrue() {
		$tomorrow = new \DateTime('tomorrow');
		$this->fixture->setEndDateAndTime($tomorrow);
		$this->assertTrue($this->fixture->isActivated());
	}

	/**
	 * @test
	 */
	public function isActivatedForEndDateAndTimeInPastReturnsFalse() {
		$yesterday = new \DateTime('yesterday');
		$this->fixture->setEndDateAndTime($yesterday);
		$this->assertFalse($this->fixture->isActivated());
	}

	/**
	 * @test
	 */
	public function isActivatedForStartDateAndTimeInPastEndDateAndTimeInFutureReturnsTrue() {
		$yesterday = new \DateTime('yesterday');
		$this->fixture->setStartDateAndTime($yesterday);
		$tomorrow = new \DateTime('tomorrow');
		$this->fixture->setEndDateAndTime($tomorrow);
		$this->assertTrue($this->fixture->isActivated());
	}

	/**
	 * @test
	 */
	public function isActivatedForStartDateAndTimeInPastEndDateAndTimeInPastReturnsFalse() {
		$yesterday = new \DateTime('yesterday');
		$this->fixture->setStartDateAndTime($yesterday);
		$this->fixture->setEndDateAndTime($yesterday);
		$this->assertFalse($this->fixture->isActivated());
	}

	/**
	 * @test
	 */
	public function isActivatedForStartDateAndTimeInFutureEndDateAndTimeInFutureReturnsFalse() {
		$tomorrow = new \DateTime('tomorrow');
		$this->fixture->setStartDateAndTime($tomorrow);
		$this->fixture->setEndDateAndTime($tomorrow);
		$this->assertFalse($this->fixture->isActivated());
	}

	/**
	 * @test
	 */
	public function getEmailInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getEmail());
	}

	/**
	 * @test
	 */
	public function setEmailSetsEmail() {
		$email = 'don.juan@example.com';
		$this->fixture->setEmail($email);
		$this->assertSame($email, $this->fixture->getEmail());
	}

	/**
	 * @test
	 */
	public function getRealNameInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getRealName());
	}

	/**
	 * @test
	 */
	public function setRealNameSetsRealName() {
		$realName = 'Don Juan';
		$this->fixture->setRealName($realName);
		$this->assertSame($realName, $this->fixture->getRealName());
	}

	/**
	 * @test
	 */
	public function getIpLockIsDisabledInitiallyReturnsFalse() {
		$this->assertFalse($this->fixture->getIpLockIsDisabled());
	}

	/**
	 * @test
	 */
	public function setIpLockIsDisabledCanSetIpLockIsDisabledToTrue() {
		$this->fixture->setIpLockIsDisabled(TRUE);
		$this->assertTrue($this->fixture->getIpLockIsDisabled());
	}

	/**
	 * @test
	 */
	public function getLastLoginDateAndTimeInitiallyReturnsNull() {
		$this->assertNull($this->fixture->getLastLoginDateAndTime());
	}

	/**
	 * @test
	 */
	public function setLastLoginDateAndTimeSetsLastLoginDateAndTime() {
		$date = new \DateTime();
		$this->fixture->setLastLoginDateAndTime($date);
		$this->assertSame($date, $this->fixture->getLastLoginDateAndTime());
	}
}

?>