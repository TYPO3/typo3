<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
	 * @test
	 */
	public function indexFileUpdatesFileProperties() {
		/** @var $fixture \TYPO3\CMS\Core\Resource\Service\IndexerService|\PHPUnit_Framework_MockObject_MockObject */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', array('gatherFileInformation', 'getFileIndexRepository'));

		$fileInfo = array(
			'mount' => 1,
			'identifier' => '/some/filepath/filename.jpg',
			'size' => 1234,
			'uid' => rand(1, 100),
			'sha1' => '123',
		);

		$fixture->expects($this->any())->method('gatherFileInformation')->will($this->returnValue($fileInfo));

		$repositoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
		$repositoryMock->expects($this->any())->method('findByContentHash')->will($this->returnValue(array()));
		$fixture->expects($this->any())->method('getFileIndexRepository')->will($this->returnValue($repositoryMock));

		$mockedFile = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);
		$mockedFile->expects($this->once())->method('updateProperties');

		$fixture->indexFile($mockedFile);
	}

	/**
	 * @test
	 */
	public function indexFileSetsCreationdateAndTimestampPropertiesOfRecordToCurrentExecutionTime() {
		$fileInfo = array();
		/** @var $fixture \TYPO3\CMS\Core\Resource\Service\IndexerService|\PHPUnit_Framework_MockObject_MockObject */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', array('gatherFileInformation', 'getFileIndexRepository'));

		$fixture->expects($this->any())->method('gatherFileInformation')->will($this->returnValue($fileInfo));

		$repositoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
		$repositoryMock->expects($this->any())->method('findByContentHash')->will($this->returnValue(array()));
		$repositoryMock->expects($this->once())->method('add');
		$fixture->expects($this->any())->method('getFileIndexRepository')->will($this->returnValue($repositoryMock));

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);

		$arrayConstraint = $this->logicalAnd(
			$this->arrayHasKey('crdate'),
			$this->arrayHasKey('tstamp'),
			$this->contains($GLOBALS['EXEC_TIME'])
		);

		$mockedFile = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);

		$fixture->indexFile($mockedFile);
	}

}
