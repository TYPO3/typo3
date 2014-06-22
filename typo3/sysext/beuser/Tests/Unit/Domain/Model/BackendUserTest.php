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
 * Test case
 */
class BackendUserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Model\BackendUser
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Beuser\Domain\Model\BackendUser();
	}

	/**
	 * @test
	 */
	public function getUidReturnsInitialValueForInt() {
		$this->assertTrue($this->fixture->getUid() === NULL, 'Not uid set after initialization.');
	}

	/**
	 * @test
	 */
	public function getUserNameReturnsInitialValueForString() {
		$this->assertTrue($this->fixture->getUserName() === '', 'Username not empty');
	}

	/**
	 * @test
	 */
	public function setUserNameForStringSetsUserName() {
		$newUserName = 'DonJuan';
		$this->fixture->setUserName($newUserName);
		$this->assertSame($this->fixture->getUserName(), $newUserName);
	}

	/**
	 * @test
	 */
	public function getRealNameReturnInitialValueForString() {
		$this->assertTrue($this->fixture->getRealName() === '', 'Real name not empty');
	}

	/**
	 * @test
	 */
	public function setRealNameForStringSetsName() {
		$realName = 'Conceived at T3CON2018';
		$this->fixture->setRealName($realName);
		$this->assertSame($this->fixture->getRealName(), $realName);
	}

	/**
	 * @test
	 */
	public function getAdminReturnInitialValueForBoolean() {
		$this->assertTrue($this->fixture->getIsAdministrator() === FALSE, 'Admin status is correct.');
	}

	/**
	 * @test
	 */
	public function setAdminToTrueSetsAdmin() {
		$this->fixture->setIsAdministrator(TRUE);
		$this->assertTrue($this->fixture->getIsAdministrator(), 'Admin status is not true, after setting to true.');
	}

	/**
	 * @test
	 */
	public function setAdminToFalseSetsAdmin() {
		$this->fixture->setIsAdministrator(FALSE);
		$this->assertFalse($this->fixture->getIsAdministrator(), 'Admin status is not false, after setting to false.');
	}

}
