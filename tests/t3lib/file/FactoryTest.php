<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


require_once 'vfsStream/vfsStream.php';

/**
 * Testcase for the factory of VFS
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_file_FactoryTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_file_Factory
	 */
	private $fixture;

	/**
	 * @var bool
	 */
	private $objectCreated = FALSE;

	public function setUp() {
		$this->fixture = new t3lib_file_Factory();
	}

	/**********************************
	 * Storage Collections
	 **********************************/

	/**
	 * @test
	 */
	public function createStorageCollectionObjectCreatesCollectionWithCorrectArguments() {
		$mockedMount = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		$path = uniqid();
		$name = uniqid();

		$storageCollection = $this->fixture->createFolderObject($mockedMount, $path, $name, 0);

		$this->assertSame($mockedMount, $storageCollection->getStorage());
		$this->assertEquals($path . '/', $storageCollection->getIdentifier());
		$this->assertEquals($name, $storageCollection->getName());
	}

	/**********************************
	 * Drivers
	 **********************************/

	/**
	 * @test
	 */
	public function getDriverObjectAcceptsDriverClassName() {
		$mockedDriver = $this->getMock('t3lib_file_Driver_AbstractDriver', array(), array(), '', FALSE);
		$driverFixtureClass = get_class($mockedDriver);
		t3lib_div::addInstance($driverFixtureClass, $mockedDriver);
		$mockedMount = $this->getMock('t3lib_file_Storage', array(), array(), '', FALSE);
		$mockedRegistry = $this->getMock('t3lib_file_Driver_DriverRegistry');
		$mockedRegistry->expects($this->once())->method('getDriverClass')->with($this->equalTo($driverFixtureClass))
			->will($this->returnValue($driverFixtureClass));
		t3lib_div::setSingletonInstance('t3lib_file_Driver_DriverRegistry', $mockedRegistry);

		$obj = $this->fixture->getDriverObject($driverFixtureClass, array());
		$this->assertInstanceOf('t3lib_file_Driver_AbstractDriver', $obj);
	}
}
?>