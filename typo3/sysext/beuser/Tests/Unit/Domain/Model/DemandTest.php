<?php
namespace TYPO3\CMS\Beuser\Tests\Unit\Domain\Model;

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
 * Test case for class \TYPO3\CMS\Beuser\Domain\Model\Demand
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class DemandTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Model\Demand
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Beuser\Domain\Model\Demand();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function setUserTypeAllExpectedValueForInt() {
		$userType = \TYPO3\CMS\Beuser\Domain\Model\Demand::ALL;
		$this->fixture->setUserType($userType);
		$this->assertSame($this->fixture->getUserType(), $userType);
	}

	/**
	 * @test
	 */
	public function setUserTypeAdminOnlyExpectedValueForInt() {
		$userType = \TYPO3\CMS\Beuser\Domain\Model\Demand::USERTYPE_ADMINONLY;
		$this->fixture->setUserType($userType);
		$this->assertSame($this->fixture->getUserType(), $userType);
	}

	/**
	 * @test
	 */
	public function setUserTypeUserOnlyExpectedValueForInt() {
		$userType = \TYPO3\CMS\Beuser\Domain\Model\Demand::USERTYPE_USERONLY;
		$this->fixture->setUserType($userType);
		$this->assertSame($this->fixture->getUserType(), $userType);
	}

	/**
	 * @test
	 */
	public function setStatusAllExpectedValueForInt() {
		$status = \TYPO3\CMS\Beuser\Domain\Model\Demand::ALL;
		$this->fixture->setStatus($status);
		$this->assertSame($this->fixture->getStatus(), $status);
	}

	/**
	 * @test
	 */
	public function setStatusActiveExpectedValueForInt() {
		$status = \TYPO3\CMS\Beuser\Domain\Model\Demand::STATUS_ACTIVE;
		$this->fixture->setStatus($status);
		$this->assertSame($this->fixture->getStatus(), $status);
	}

	/**
	 * @test
	 */
	public function setStatusInactiveExpectedValueForInt() {
		$status = \TYPO3\CMS\Beuser\Domain\Model\Demand::STATUS_INACTIVE;
		$this->fixture->setStatus($status);
		$this->assertSame($this->fixture->getStatus(), $status);
	}

	/**
	 * @test
	 */
	public function setLoginAllExpectedValueForInt() {
		$login = \TYPO3\CMS\Beuser\Domain\Model\Demand::ALL;
		$this->fixture->setLogins($login);
		$this->assertSame($this->fixture->getLogins(), $login);
	}

	/**
	 * @test
	 */
	public function setLoginNoneExpectedValueForInt() {
		$login = \TYPO3\CMS\Beuser\Domain\Model\Demand::LOGIN_NONE;
		$this->fixture->setLogins($login);
		$this->assertSame($this->fixture->getLogins(), $login);
	}

	/**
	 * @test
	 */
	public function setLoginxSameExpectedValueForInt() {
		$login = \TYPO3\CMS\Beuser\Domain\Model\Demand::LOGIN_SOME;
		$this->fixture->setLogins($login);
		$this->assertSame($this->fixture->getLogins(), $login);
	}

	/**
	 * @test
	 */
	public function getUserNameInitialValueForString() {
		$this->assertSame($this->fixture->getUserName(), '', 'UserName must be empty string.');
	}

	/**
	 * @test
	 */
	public function setUserNameReturnExpectedValueForString() {
		$newUserName = 'User#ää*%^name';
		$this->fixture->setUserName($newUserName);
		$this->assertSame($this->fixture->getUserName(), $newUserName, 'UserName is not as set before.');
	}

}

?>