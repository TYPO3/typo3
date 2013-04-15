<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

/***********************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Kai Vogel <kai.vogel@speedprogs.de>, Speedprogs.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 **********************************************************************/

/**
 * Test case
 */
class BackendTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function insertRelationInRelationtableSetsMmMatchFieldsInRow() {
		/* \TYPO3\CMS\Extbase\Persistence\Generic\Backend|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', array('dummy'), array(), '', FALSE);
		/* \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper|\PHPUnit_Framework_MockObject_MockObject */
		$dataMapper = $this->getMock('TYPO3\\CMS\Extbase\\Persistence\\Generic\\Mapper\\DataMapper');
		/* \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap|\PHPUnit_Framework_MockObject_MockObject */
		$dataMap = $this->getMock('TYPO3\\CMS\Extbase\\Persistence\\Generic\\Mapper\\DataMap', array(), array(), '', FALSE);
		/* \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap|\PHPUnit_Framework_MockObject_MockObject */
		$columnMap = $this->getMock('TYPO3\\CMS\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', array(), array(), '', FALSE);
		/* \TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface|\PHPUnit_Framework_MockObject_MockObject */
		$storageBackend = $this->getMock('TYPO3\\CMS\Extbase\\Persistence\\Generic\\Storage\\BackendInterface');
		/* \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$domainObject = $this->getMock('TYPO3\\CMS\Extbase\\DomainObject\\DomainObjectInterface');

		$mmMatchFields = array(
			'identifier' => 'myTable:myField',
		);

		$expectedRow = array(
			'identifier' => 'myTable:myField',
			'' => 0
		);

		$columnMap
			->expects($this->once())
			->method('getRelationTableMatchFields')
			->will($this->returnValue($mmMatchFields));
		$columnMap
			->expects($this->any())
			->method('getChildSortByFieldName')
			->will($this->returnValue(''));
		$dataMap
			->expects($this->any())
			->method('getColumnMap')
			->will($this->returnValue($columnMap));
		$dataMapper
			->expects($this->any())
			->method('getDataMap')
			->will($this->returnValue($dataMap));
		$storageBackend
			->expects($this->once())
			->method('addRow')
			->with(NULL, $expectedRow, TRUE);

		$fixture->_set('dataMapper', $dataMapper);
		$fixture->_set('storageBackend', $storageBackend);
		$fixture->_call('insertRelationInRelationtable', $domainObject, $domainObject, '');
	}

	/**
	 * @test
	 */
	public function getPlainValueReturnsCorrectDateTimeFormat() {
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Backend', array('dummy'), array(), '', FALSE);
		$columnMap = new \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap('column_name', 'propertyName');
		$columnMap->setDateTimeStorageFormat('datetime');
		$datetimeAsString = '2013-04-15 09:30:00';
		$input = new \DateTime($datetimeAsString);
		$this->assertEquals('2013-04-15 09:30:00', $fixture->_call('getPlainValue', $input, $columnMap));
		$columnMap->setDateTimeStorageFormat('date');
		$this->assertEquals('2013-04-15', $fixture->_call('getPlainValue', $input, $columnMap));
	}

}
?>