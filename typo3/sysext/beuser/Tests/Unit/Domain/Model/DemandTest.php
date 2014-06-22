<?php
namespace TYPO3\CMS\Beuser\Tests\Unit\Domain\Model;

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
 * Test case for class \TYPO3\CMS\Beuser\Domain\Model\Demand
 */
class DemandTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Model\Demand
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Beuser\Domain\Model\Demand();
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
