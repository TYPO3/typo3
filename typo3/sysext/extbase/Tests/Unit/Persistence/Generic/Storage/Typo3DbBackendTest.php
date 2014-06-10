<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Storage;

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
			'uid' => '42',
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
