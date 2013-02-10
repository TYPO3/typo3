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
 * Test case for class \TYPO3\CMS\Beuser\Domain\Model\BackendUser
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class BackendUserTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Model\BackendUser
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Beuser\Domain\Model\BackendUser();
	}

	public function tearDown() {
		unset($this->fixture);
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

?>