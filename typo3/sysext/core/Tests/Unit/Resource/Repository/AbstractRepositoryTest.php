<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Repository;

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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Test case
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class AbstractRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Resource\AbstractRepository
	 */
	protected $subject;

	protected $mockedDb;

	protected function createDatabaseMock() {
		$this->mockedDb = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
		$GLOBALS['TYPO3_DB'] = $this->mockedDb;
	}

	public function setUp() {
		$this->subject = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\AbstractRepository', array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function findByUidFailsIfUidIsString() {
		$this->setExpectedException('InvalidArgumentException', '', 1316779798);
		$this->subject->findByUid('asdf');
	}

	/**
	 * @test
	 */
	public function findByUidAcceptsNumericUidInString() {
		$this->createDatabaseMock();
		$this->mockedDb->expects($this->once())->method('exec_SELECTgetSingleRow')->with($this->anything(), $this->anything(), $this->stringContains('uid=' . 123))->will($this->returnValue(array('uid' => 123)));
		$this->subject->findByUid('123');
	}

	/**
	 * test runs on a concrete implementation of AbstractRepository
	 * to ease the pain of testing a protected method. Feel free to improve.
	 *
	 * @test
	 */
	public function getWhereClauseForEnabledFieldsIncludesDeletedCheckInBackend() {
		$GLOBALS['TCA'] = array(
			'sys_file_storage' => array(
				'ctrl' => array(
					'delete' => 'deleted',
				),
			),
		);
		/** @var \TYPO3\CMS\Core\Resource\StorageRepository|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $storageRepositoryMock */
		$storageRepositoryMock = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Resource\\StorageRepository',
			array('dummy'),
			array(),
			'',
			FALSE
		);
		$result = $storageRepositoryMock->_call('getWhereClauseForEnabledFields');
		$this->assertContains('sys_file_storage.deleted=0', $result);
	}

	/**
	 * test runs on a concrete implementation of AbstractRepository
	 * to ease the pain of testing a protected method. Feel free to improve.
	 *
	 * @test
	 */
	public function getWhereClauseForEnabledFieldsCallsSysPageForDeletedFlagInFrontend() {
		$GLOBALS['TSFE'] = new \stdClass();
		$sysPageMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$GLOBALS['TSFE']->sys_page = $sysPageMock;
		$sysPageMock
			->expects($this->once())
			->method('deleteClause')
			->with('sys_file_storage');
		$storageRepositoryMock = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\Resource\\StorageRepository',
			array('getEnvironmentMode'),
			array(),
			'',
			FALSE
		);
		$storageRepositoryMock->expects($this->any())->method('getEnvironmentMode')->will($this->returnValue('FE'));
		$storageRepositoryMock->_call('getWhereClauseForEnabledFields');
	}

}
