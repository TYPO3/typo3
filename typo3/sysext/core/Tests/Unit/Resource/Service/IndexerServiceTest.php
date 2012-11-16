<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Service;

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
 * Testcase for the file indexing service
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class IndexerServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var boolean Backup all $GLOBALS
	 */
	protected $backupGlobals = TRUE;

	/**
	 * @var Store backup of TYPO3_DB
	 */
	protected $dbBackup;

	/**
	 * Set up this test case
	 */
	public function setUp() {
			// Store TYPO3_DB in local var, it is substituted with a mock in this test
		$this->dbBackup = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Tear down this test case
	 */
	public function tearDown() {
			// Re constitute TYPO3_DB from backup
		$GLOBALS['TYPO3_DB'] = $this->dbBackup;
	}

	/**
	 * @test
	 */
	public function indexFileUpdatesFileProperties() {
		/** @var $fixture \TYPO3\CMS\Core\Resource\Service\IndexerService|\PHPUnit_Framework_MockObject_MockObject */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', array('gatherFileInformation', 'getRepository'));

		$fileInfo = array(
			'mount' => 1,
			'identifier' => '/some/filepath/filename.jpg',
			'size' => 1234,
			'uid' => rand(1, 100),
			'sha1' => '123',
		);

		$fixture->expects($this->any())->method('gatherFileInformation')->will($this->returnValue($fileInfo));

		$repositoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository', array('findBySha1Hash'));
		$repositoryMock->expects($this->any())->method('findBySha1Hash')->will($this->returnValue(array()));
		$fixture->expects($this->any())->method('getRepository')->will($this->returnValue($repositoryMock));

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']->expects($this->atLeastOnce())->method('sql_insert_id')->will($this->returnValue($fileInfo['uid']));

		$mockedFile = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);
		$mockedFile->expects($this->once())->method('updateProperties')->with($this->equalTo($fileInfo));

		$fixture->indexFile($mockedFile);
	}

	/**
	 * @test
	 */
	public function indexFileSetsCreationdateAndTimestampPropertiesOfRecordToCurrentExecutionTime() {
		$fileInfo = array();
		/** @var $fixture \TYPO3\CMS\Core\Resource\Service\IndexerService|\PHPUnit_Framework_MockObject_MockObject */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', array('gatherFileInformation', 'getRepository'));

		$fixture->expects($this->any())->method('gatherFileInformation')->will($this->returnValue($fileInfo));

		$repositoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository', array('findBySha1Hash'));
		$repositoryMock->expects($this->any())->method('findBySha1Hash')->will($this->returnValue(array()));
		$fixture->expects($this->any())->method('getRepository')->will($this->returnValue($repositoryMock));

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']->expects($this->once())->method('exec_INSERTquery')->with($this->anything(), $this->equalTo(array(
			'crdate' => $GLOBALS['EXEC_TIME'],
			'tstamp' => $GLOBALS['EXEC_TIME']
		)));

		$mockedFile = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);

		$fixture->indexFile($mockedFile);
	}

}

?>