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
 * File locker tests
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 */
class FileLockerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Enable backup of global and system variables.
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Mock fixture.
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker\FileLocker|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture;

	/**
	 * Accessible mock fixture.
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker\FileLocker|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
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
	protected function createFixture(array $mockMethods = array('log'), array $constructorArgs = array('dummyContext', 'dummyId')) {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Locking\\Locker\\FileLocker', $mockMethods, $constructorArgs, '', (count($constructorArgs) >= 2 ? TRUE : FALSE));
	}

	/**
	 * Create new accessable mock for null locker.
	 *
	 * @param array $mockMethods
	 * @param array $constructorArgs
	 * @return void
	 */
	protected function createAccessableFixture(array $mockMethods = array('log'), array $constructorArgs = array('dummyContext', 'dummyId')) {
		$this->accessibleFixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Locking\\Locker\\FileLocker', $mockMethods, $constructorArgs, '', (count($constructorArgs) >= 2 ? TRUE : FALSE));
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
	 * Tests that get type on a file-locker instance will return 'simple'.
	 *
	 * @return void
	 * @test
	 */
	public function getLockerTypeOnFileLockerWillReturnSimple() {
		$this->assertSame('simple', $this->fixture->getType());
	}

}

?>
