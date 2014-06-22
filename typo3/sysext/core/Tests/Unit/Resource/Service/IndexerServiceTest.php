<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Service;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case
 *
 */
class IndexerServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function indexFileUpdatesFileProperties() {
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);

		/** @var $subject \TYPO3\CMS\Core\Resource\Service\IndexerService|\PHPUnit_Framework_MockObject_MockObject */
		$subject = $this->getMock(
			'TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService',
			array('gatherFileInformation', 'getFileIndexRepository', 'emitPreFileIndexSignal', 'emitPostFileIndexSignal')
		);

		$fileInfo = array(
			'mount' => 1,
			'identifier' => '/some/filepath/filename.jpg',
			'size' => 1234,
			'uid' => rand(1, 100),
			'sha1' => '123',
		);

		$subject->expects($this->any())->method('gatherFileInformation')->will($this->returnValue($fileInfo));

		$repositoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
		$repositoryMock->expects($this->any())->method('findByContentHash')->will($this->returnValue(array()));
		$subject->expects($this->any())->method('getFileIndexRepository')->will($this->returnValue($repositoryMock));

		$mockedFile = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);
		$mockedFile->expects($this->once())->method('updateProperties');

		$subject->indexFile($mockedFile);
	}

	/**
	 * @test
	 */
	public function indexFileSetsCreationdateAndTimestampPropertiesOfRecordToCurrentExecutionTime() {
		$fileInfo = array();
		/** @var $subject \TYPO3\CMS\Core\Resource\Service\IndexerService|\PHPUnit_Framework_MockObject_MockObject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', array('gatherFileInformation', 'getFileIndexRepository', 'emitPreFileIndexSignal', 'emitPostFileIndexSignal'));

		$subject->expects($this->any())->method('gatherFileInformation')->will($this->returnValue($fileInfo));

		$repositoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
		$repositoryMock->expects($this->any())->method('findByContentHash')->will($this->returnValue(array()));
		$repositoryMock->expects($this->once())->method('add');
		$subject->expects($this->any())->method('getFileIndexRepository')->will($this->returnValue($repositoryMock));

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);

		$mockedFile = $this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);

		$subject->indexFile($mockedFile);
	}
}