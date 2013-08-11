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
 * Abstract file locker tests
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 */
class AbstractFileLockerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Enable backup of global and system variables.
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Mock fixture.
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker\AbstractFileLocker|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture;

	/**
	 * Accessible mock fixture.
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker\AbstractFileLocker|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $accessibleFixture;

	/**
	 * Contains accessable class name.
	 *
	 * @var string
	 */
	protected $accessibleClassName;

	/**
	 * Holds an array auf default constructor args.
	 *  First arg -> context: dummy
	 *  Second arg -> id: dummy
	 *
	 * @var array
	 */
	protected $dummyConstructorArgs = array('dummy', 'dummy');

	/**
	 * Holds array of default options.
	 *
	 * @var array
	 */
	protected $defaultOptions = array(
		'logging' => TRUE,
		'retries' => 150,
		'retryInterval' => 200,
		'respectExecutionTime' => TRUE,
		'autoReleaseOnPHPShutdown' => TRUE,
		'maxLockAge' => 120,
	);

	/**
	 * Constructs test class.
	 *
	 * @param null   $name
	 * @param array  $data
	 * @param string $dataName
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);

		// build accessable proxy class and save classname
		$this->accessibleClassName = $this->buildAccessibleProxy('TYPO3\\CMS\\Core\\Locking\\Locker\\AbstractFileLocker');
	}

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
	 * @param array $constructorArgs
	 * @param array $mockMethods
	 * @return void
	 */
	protected function createFixture(array $constructorArgs = array(), array $mockMethods = array()) {
		$this->fixture = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Locking\\Locker\\AbstractFileLocker', $constructorArgs, '', (count($constructorArgs) >= 2 ? TRUE : FALSE), TRUE, TRUE, $mockMethods);
	}

	/**
	 * Create new accessable mock for abstract locker.
	 *
	 * @param array $constructorArgs
	 * @param array $mockMethods
	 * @return void
	 */
	protected function createAccessableFixture(array $constructorArgs = array(), array $mockMethods = array()) {
		$this->accessibleFixture = $this->getMockForAbstractClass($this->accessibleClassName, $constructorArgs, '', (count($constructorArgs) >= 2 ? TRUE : FALSE), TRUE, TRUE, $mockMethods);
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

}
