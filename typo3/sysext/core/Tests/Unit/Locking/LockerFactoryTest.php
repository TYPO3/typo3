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
	 * Inits/setUp test class.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Locking\LockerFactory();
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
	 * @test
	 */
	public function createReturnsSpecifiedLockerInstance() {
		$mockClassName = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Locking\\Locker\\AbstractLocker', array(), '', FALSE));

		$testLocker = $this->fixture->create($mockClassName, 'dummyContext', 'dummyId');

		$this->assertInstanceOf($mockClassName, $testLocker);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @test
	 */
	public function validateThatCreateWillThrowAnExceptionIfClassNotImpelementsLockerInterface() {
		$this->fixture->create('stdClass', 'dummyContext', 'dummyId');
	}

}

?>
