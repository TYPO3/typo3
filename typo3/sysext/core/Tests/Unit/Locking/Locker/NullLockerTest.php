<?php
namespace TYPO3\CMS\Core\Tests\Unit\Locking\Locker;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Daniel Hürtgen <huertgen@rheinschafe.de>
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
 * Null locker tests
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 */
class NullLockerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Enable backup of global and system variables.
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Mock fixture.
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker\NullLocker|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture;

	/**
	 * Accessible mock fixture.
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker\NullLocker|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $accessibleFixture;

	/**
	 * Inits/setUp test class.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->createFixture();
		$this->createAccessableFixture();
	}

	/**
	 * Create new basic mock for abstract locker.
	 *
	 * @param array $mockMethods
	 * @param array $constructorArgs
	 * @return void
	 */
	protected function createFixture(array $mockMethods = array('log'), array $constructorArgs = array()) {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Locking\\Locker\\NullLocker', $mockMethods, $constructorArgs, '', (count($constructorArgs) >= 2 ? TRUE : FALSE));
	}

	/**
	 * Create new accessable mock for null locker.
	 *
	 * @param array $mockMethods
	 * @param array $constructorArgs
	 * @return void
	 */
	protected function createAccessableFixture(array $mockMethods = array('log'), array $constructorArgs = array()) {
		$this->accessibleFixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Locking\\Locker\\NullLocker', $mockMethods, $constructorArgs, '', (count($constructorArgs) >= 2 ? TRUE : FALSE));
	}

	/**
	 * ShutDowns test class.
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->fixture);
		unset($this->accessibleFixture);
	}

	/**
	 * Tests that get type on a null-locker instance will return 'disabled'.
	 *
	 * @return void
	 * @test
	 */
	public function getLockerTypeOnNullLockerWillReturnDisabled() {
		$this->assertSame('NullLocker', $this->fixture->getType());
	}

	/**
	 * Tests that acquiring lock on a null-locker instance will always return true.
	 *
	 * @return void
	 * @test
	 */
	public function acquireLockingWillAlwaysReturnTrue() {
		$this->assertTrue($this->fixture->acquire());
	}

	/**
	 * Tests that release lock on a null-locker instance will always return true.
	 *
	 * @return void
	 * @test
	 */
	public function releaseLockingWillAlwaysReturnTrue() {
		$this->assertTrue($this->fixture->release());
	}

}

?>