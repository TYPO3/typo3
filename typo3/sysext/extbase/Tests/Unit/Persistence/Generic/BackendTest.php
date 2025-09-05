<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BackendTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function insertRelationInRelationtableSetsMmMatchFieldsInRow(): void
    {
        $fixture = $this->getAccessibleMock(Backend::class, null, [], '', false);
        $dataMapFactory = $this->createMock(DataMapFactory::class);
        $dataMap = $this->createMock(DataMap::class);
        $columnMap = $this->createMock(ColumnMap::class);
        $storageBackend = $this->createMock(BackendInterface::class);
        $domainObject = $this->createMock(DomainObjectInterface::class);

        $mmMatchFields = [
            'identifier' => 'myTable:myField',
        ];

        $expectedRow = [
            'identifier' => 'myTable:myField',
            '' => 0,
        ];

        $columnMap
            ->expects($this->once())
            ->method('getRelationTableName')
            ->willReturn('myTable');
        $columnMap
            ->expects($this->once())
            ->method('getRelationTableMatchFields')
            ->willReturn($mmMatchFields);
        $columnMap
            ->method('getChildSortByFieldName')
            ->willReturn('');
        $dataMap
            ->method('getColumnMap')
            ->willReturn($columnMap);
        $dataMapFactory
            ->method('buildDataMap')
            ->willReturn($dataMap);
        $storageBackend
            ->expects($this->once())
            ->method('addRow')
            ->with('myTable', $expectedRow, true);

        $fixture->_set('dataMapFactory', $dataMapFactory);
        $fixture->_set('storageBackend', $storageBackend);
        $fixture->_call('insertRelationInRelationtable', $domainObject, $domainObject, '');
    }

    #[Test]
    public function getIdentifierByObjectWithStringInsteadOfObjectReturnsNull(): void
    {
        $session = $this->createMock(Session::class);
        $session->expects($this->never())->method('getIdentifierByObject');

        $backend = $this->getAccessibleMock(Backend::class, null, [$this->createMock(ConfigurationManagerInterface::class)], '', false);
        $backend->_set('session', $session);

        self::assertNull($backend->getIdentifierByObject('invalidObject'));
    }

    #[Test]
    public function getIdentifierByObjectReturnsIdentifierForNonLazyObject(): void
    {
        $fakeUuid = 'fakeUuid';
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $session = $this->createMock(Session::class);
        $object = new \stdClass();
        $session->expects($this->once())->method('getIdentifierByObject')->with($object)->willReturn($fakeUuid);
        $backend = $this->getAccessibleMock(Backend::class, null, [$configurationManager], '', false);
        $backend->_set('session', $session);
        self::assertEquals($backend->getIdentifierByObject($object), $fakeUuid);
    }

    #[Test]
    public function getIdentifierByObjectReturnsIdentifierForLazyObject(): void
    {
        $fakeUuid = 'fakeUuid';
        $configurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $proxy = $this->createMock(LazyLoadingProxy::class);
        $session = $this->createMock(Session::class);
        $object = new \stdClass();
        $proxy->expects($this->once())->method('_loadRealInstance')->willReturn($object);
        $session->expects($this->once())->method('getIdentifierByObject')->with($object)->willReturn($fakeUuid);
        $backend = $this->getAccessibleMock(Backend::class, null, [$configurationManager], '', false);
        $backend->_set('session', $session);
        self::assertEquals($backend->getIdentifierByObject($proxy), $fakeUuid);
    }
}
