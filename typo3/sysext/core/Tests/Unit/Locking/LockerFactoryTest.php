<?php
namespace TYPO3\CMS\Core\Tests\Unit\Locking;

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
 * Abstract locker tests
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 */
class LockerFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Mock fixture.
	 *
	 * @var \TYPO3\CMS\Core\Locking\LockerFactory|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture;

	/**
	 * @var array
	 */
	protected $typeMap = array();

	/**
	 * Constructs test case.
	 *
	 * @param null   $name
	 * @param array  $data
	 * @param string $dataName
	 * @return \TYPO3\CMS\Core\Tests\Unit\Locking\LockerFactoryTest
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);

		$this->init();
	}

	/**
	 * Init tests.
	 *
	 * @return void
	 */
	protected function init() {
		$this->initTypeMap();
	}

	/**
	 * Initialize mocked typemap for testcase.
	 *
	 * @return void
	 */
	protected function initTypeMap() {
		$tmpFactory = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Locking\\LockerFactory', array('dummy'));
		$typeMap = $tmpFactory->_get('typeMap');
		if (is_array($typeMap)) {
			foreach ($typeMap as $name => $className) {
				$this->typeMap[$name] = $this->getMockClass($className);
			}

		}
	}

	/**
	 * Inits/setUp test class.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->fixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Locking\\LockerFactory', array('dummy'));
		$this->fixture->_set('typeMap', $this->typeMap);
	}

	/**
	 * ShutDowns test class.
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Dataprovider.
	 *
	 * @return array
	 */
	public function createReturnsInstanceOfTheSpecifiedLockerDataProvider() {
		$retArr = array();
		foreach ($this->typeMap as $name => $className) {
			$retArr[$name . ' locker'] = array($name, $className);
		}
		return $retArr;
	}

	/**
	 * Tests create method of locker factory.
	 *
	 * @param string $lockerType
	 * @param string $expectedClass
	 * @return void
	 * @dataProvider createReturnsInstanceOfTheSpecifiedLockerDataProvider
	 * @test
	 */
	public function createReturnsInstanceOfTheSpecifiedLocker($lockerType, $expectedClass) {
		$locker = $this->fixture->create($lockerType, 'foo', 'bar');
		$this->assertInstanceOf($expectedClass, $locker);
	}

}

?>