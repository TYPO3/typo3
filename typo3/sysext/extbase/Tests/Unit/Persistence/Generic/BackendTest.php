<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

/*
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
class BackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function insertRelationInRelationtableSetsMmMatchFieldsInRow()
    {
        /* \TYPO3\CMS\Extbase\Persistence\Generic\Backend|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $fixture = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Backend::class, ['dummy'], [], '', false);
        /* \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper|\PHPUnit_Framework_MockObject_MockObject */
        $dataMapper = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        /* \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap|\PHPUnit_Framework_MockObject_MockObject */
        $dataMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, [], [], '', false);
        /* \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap|\PHPUnit_Framework_MockObject_MockObject */
        $columnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, [], [], '', false);
        /* \TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface|\PHPUnit_Framework_MockObject_MockObject */
        $storageBackend = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface::class);
        /* \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $domainObject = $this->getMock(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::class);

        $mmMatchFields = [
            'identifier' => 'myTable:myField',
        ];

        $expectedRow = [
            'identifier' => 'myTable:myField',
            '' => 0
        ];

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
            ->with(null, $expectedRow, true);

        $fixture->_set('dataMapper', $dataMapper);
        $fixture->_set('storageBackend', $storageBackend);
        $fixture->_call('insertRelationInRelationtable', $domainObject, $domainObject, '');
    }

    /**
     * @test
     */
    public function getIdentifierByObjectReturnsIdentifierForNonlazyObject()
    {
        $fakeUuid = 'fakeUuid';
        $configurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $session = $this->getMock('stdClass', ['getIdentifierByObject'], [], '', false);
        $object = new \stdClass();

        $session->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue($fakeUuid));

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Backend $backend */
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Backend::class, ['dummy'], [$configurationManager]);
        $backend->_set('session', $session);

        $this->assertEquals($backend->getIdentifierByObject($object), $fakeUuid);
    }

    /**
     * @test
     */
    public function getIdentifierByObjectReturnsIdentifierForLazyObject()
    {
        $fakeUuid = 'fakeUuid';
        $configurationManager = $this->getMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $parentObject = new \stdClass();
        $proxy = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy::class, ['_loadRealInstance'], [$parentObject, 'y', 'z'], '', false);
        $session = $this->getMock('stdClass', ['getIdentifierByObject'], [], '', false);
        $object = new \stdClass();

        $proxy->expects($this->once())->method('_loadRealInstance')->will($this->returnValue($object));
        $session->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue($fakeUuid));

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Backend $backend */
        $backend = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Backend::class, ['dummy'], [$configurationManager]);
        $backend->_set('session', $session);

        $this->assertEquals($backend->getIdentifierByObject($proxy), $fakeUuid);
    }
}
