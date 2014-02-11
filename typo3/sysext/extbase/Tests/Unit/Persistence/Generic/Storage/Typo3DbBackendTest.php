<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Storage;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 */
class Typo3DbBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function uidOfAlreadyPersistedValueObjectIsDeterminedCorrectly() {
		$mockValueObject = $this->getMock('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractValueObject', array('_getProperties'), array(), '', FALSE);
		$mockValueObject->expects($this->once())->method('_getProperties')->will($this->returnValue(array('propertyName' => 'propertyValue')));
		$mockColumnMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMap', array('isPersistableProperty', 'getColumnName'), array(), '', FALSE);
		$mockColumnMap->expects($this->any())->method('getColumnName')->will($this->returnValue('column_name'));
		$tableName = 'tx_foo_table';
		$mockDataMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMap', array('isPersistableProperty', 'getColumnMap', 'getTableName'), array(), '', FALSE);
		$mockDataMap->expects($this->any())->method('isPersistableProperty')->will($this->returnValue(TRUE));
		$mockDataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($mockColumnMap));
		$mockDataMap->expects($this->any())->method('getTableName')->will($this->returnValue($tableName));
		$mockDataMapper = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper', array('getDataMap'), array(), '', FALSE);
		$mockDataMapper->expects($this->once())->method('getDataMap')->will($this->returnValue($mockDataMap));
		$expectedStatement = 'SELECT * FROM tx_foo_table WHERE column_name=?';
		$expectedParameters = array('plainPropertyValue');
		$expectedUid = 52;
		$mockDataBaseHandle = $this->getMock('TYPO3\CMS\Core\Database\DatabaseConnection', array('sql_query', 'sql_fetch_assoc'), array(), '', FALSE);
		$mockDataBaseHandle->expects($this->once())->method('sql_query')->will($this->returnValue('resource'));
		$mockDataBaseHandle->expects($this->any())->method('sql_fetch_assoc')->with('resource')->will($this->returnValue(array('uid' => $expectedUid)));
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('getPlainValue', 'checkSqlErrors', 'replacePlaceholders', 'addVisibilityConstraintStatement'), array(), '', FALSE);
		$mockTypo3DbBackend->expects($this->once())->method('getPlainValue')->will($this->returnValue('plainPropertyValue'));
		$mockTypo3DbBackend->expects($this->once())->method('addVisibilityConstraintStatement')->with($this->isInstanceOf('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface'), $tableName, $this->isType('array'));
		$mockTypo3DbBackend->expects($this->once())->method('replacePlaceholders')->with($expectedStatement, $expectedParameters);
		$mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
		$mockTypo3DbBackend->_set('databaseHandle', $mockDataBaseHandle);
		$result = $mockTypo3DbBackend->_callRef('getUidOfAlreadyPersistedValueObject', $mockValueObject);
		$this->assertSame($expectedUid, $result);
	}

	/**
	 * @test
	 */
	public function doLanguageAndWorkspaceOverlayChangesUidIfInPreview() {
		$comparisonRow = array(
			'uid' => '43',
			'pid' => '42',
			'_ORIG_pid' => '-1',
			'_ORIG_uid' => '43'
		);
		$row = array(
			'uid' => '42',
			'pid' => '42'
		);
		$workspaceVersion = array(
			'uid' => '43',
			'pid' => '-1'
		);
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings|\PHPUnit_Framework_MockObject_MockObject $querySettings */
		$mockQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings', array('dummy'), array(), '', FALSE);

		$workspaceUid = 2;
		$sourceMock = new \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Selector('tx_foo', 'Tx_Foo');
		/** @var $pageRepositoryMock \TYPO3\CMS\Frontend\Page\PageRepository|\PHPUnit_Framework_MockObject_MockObject */
		$pageRepositoryMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository', array('movePlhOL', 'getWorkspaceVersionOfRecord'));
		$pageRepositoryMock->versioningPreview = TRUE;
		$pageRepositoryMock->expects($this->once())->method('getWorkspaceVersionOfRecord')->with($workspaceUid, 'tx_foo', '42')->will($this->returnValue($workspaceVersion));
		$mockTypo3DbBackend = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbBackend', array('dummy'), array(), '', FALSE);
		$mockTypo3DbBackend->_set('pageRepository', $pageRepositoryMock);
		$this->assertSame(array($comparisonRow), $mockTypo3DbBackend->_call('doLanguageAndWorkspaceOverlay', $sourceMock, array($row), $mockQuerySettings, $workspaceUid));
	}
}
