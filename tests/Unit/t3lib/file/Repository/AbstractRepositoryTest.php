<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Testcase for the abstract repository base class
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_file_Repository_AbstractRepositoryTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_file_Repository_AbstractRepository
	 */
	private $fixture;

	private $mockedDb;

	private $Typo3DbBackup;

	protected function createDatabaseMock() {
		$this->mockedDb = $this->getMock('t3lib_DB');
		$this->Typo3DbBackup = $GLOBALS['TYPO3_DB'];
		$GLOBALS['TYPO3_DB'] = $this->mockedDb;
	}

	public function setUp() {
		$this->fixture = $this->getMockForAbstractClass('t3lib_file_Repository_AbstractRepository', array(), '', FALSE);
	}

	public function tearDown() {
		if ($this->mockedDb) {
			$GLOBALS['TYPO3_DB'] = $this->Typo3DbBackup;
		}
	}

	/**
	 * @test
	 */
	public function findByUidFailsIfUidIsString() {
		$this->setExpectedException('InvalidArgumentException', '', 1316779798);

		$this->fixture->findByUid('asdf');
	}

	/**
	 * @test
	 */
	public function findByUidAcceptsNumericUidInString() {
		$this->createDatabaseMock();
		$this->mockedDb->expects($this->once())->method('exec_SELECTgetSingleRow')
		  ->with($this->anything(), $this->anything(), $this->stringContains('uid=' . 123))
		  ->will($this->returnValue(array('uid' => 123)));

		$this->fixture->findByUid("123");
	}
}
?>