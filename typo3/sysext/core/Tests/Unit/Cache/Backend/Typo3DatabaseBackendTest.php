<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

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
use Prophecy\Argument;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 *
 */
class Typo3DatabaseBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Helper method to inject a mock frontend to backend instance
     *
     * @param \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend $backend Current backend instance
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface Mock frontend
     */
    protected function setUpMockFrontendOfBackend(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend $backend)
    {
        $mockCache = $this->createMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class);
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('Testing'));
        $backend->setCache($mockCache);
        return $mockCache;
    }

    /**
     * @test
     */
    public function setCacheCalculatesCacheTableName()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);
        $this->assertEquals('cf_Testing', $backend->getCacheTable());
    }

    /**
     * @test
     */
    public function setCacheCalculatesTagsTableName()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);
        $this->assertEquals('cf_Testing_tags', $backend->getTagsTable());
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfFrontendWasNotSet()
    {
        $this->expectException(\TYPO3\CMS\Core\Cache\Exception::class);
        $this->expectExceptionCode(1236518288);

        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $backend->set('identifier', 'data');
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfDataIsNotAString()
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1236518298);

        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);
        $data = array('Some data');
        $entryIdentifier = 'BackendDbTest';
        $backend->set($entryIdentifier, $data);
    }

    /**
     * @test
     */
    public function setInsertsEntryInTable()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);
        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->once())
            ->method('exec_INSERTquery')
            ->with('cf_Testing', $this->callback(function (array $data) {
                if ($data['content'] !== 'someData') {
                    return false;
                }
                if ($data['identifier'] !== 'anIdentifier') {
                    return false;
                }
                return true;
            }));
        $backend->set('anIdentifier', 'someData');
    }

    /**
     * @test
     */
    public function setRemovesAnAlreadyExistingCacheEntryForTheSameIdentifier()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('remove'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);
        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);

        $backend->expects($this->once())->method('remove');
        $data = $this->getUniqueId('someData');
        $entryIdentifier = 'anIdentifier';
        $backend->set($entryIdentifier, $data, array(), 500);
    }

    /**
     * @test
     */
    public function setReallySavesSpecifiedTags()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);
        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connection = $this->createMock(Connection::class);
        $connectionPool->expects($this->once())->method('getConnectionForTable')->willReturn($connection);
        $connection->expects($this->once())->method('bulkInsert')->with(
            'cf_Testing_tags',
            $this->callback(function (array $data) {
                if ($data[0][0] !== 'anIdentifier' || $data[0][1] !== 'UnitTestTag%tag1') {
                    return false;
                }
                if ($data[1][0] !== 'anIdentifier' || $data[1][1] !== 'UnitTestTag%tag2') {
                    return false;
                }
                return true;
            }),
            $this->callback(function (array $data) {
                if ($data[0] === 'identifier' && $data[1] === 'tag') {
                    return true;
                }
                return false;
            })
        );
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool);
        $backend->set('anIdentifier', 'someData', array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
    }

    /**
     * @test
     */
    public function setSavesCompressedDataWithEnabledCompression()
    {
        $backendOptions = array(
            'compression' => true
        );
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing', $backendOptions))
            ->getMock();

        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->once())
            ->method('exec_INSERTquery')
            ->with(
                'cf_Testing',
                $this->callback(function (array $data) {
                    if (@gzuncompress($data['content']) === 'someData') {
                        return true;
                    }
                    return false;
                }
                ));

        $backend->set('anIdentifier', 'someData');
    }

    /**
     * @test
     */
    public function setWithUnlimitedLifetimeWritesCorrectEntry()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->once())
            ->method('exec_INSERTquery')
            ->with(
                'cf_Testing',
                $this->callback(function (array $data) {
                    $lifetime = $data['expires'];
                    if ($lifetime > 2000000000) {
                        return true;
                    }
                    return false;
                }
                ));

        $backend->set('aIdentifier', 'someData', array(), 0);
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfFrontendWasNotSet()
    {
        $this->expectException(\TYPO3\CMS\Core\Cache\Exception::class);
        $this->expectExceptionCode(1236518288);

        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $backend->get('identifier');
    }

    /**
     * @test
     */
    public function getReturnsContentOfTheCorrectCacheEntry()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->with('content', 'cf_Testing', $this->anything())
            ->will($this->returnValue(array('content' => 'someData')));

        $loadedData = $backend->get('aIdentifier');
        $this->assertEquals('someData', $loadedData);
    }

    /**
     * @test
     */
    public function getSetsExceededLifetimeQueryPart()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->with(
                'content',
                'cf_Testing',
                $this->stringContains('identifier =  AND cf_Testing.expires >=')
            );

        $backend->get('aIdentifier');
    }

    /**
     * @test
     */
    public function hasThrowsExceptionIfFrontendWasNotSet()
    {
        $this->expectException(\TYPO3\CMS\Core\Cache\Exception::class);
        $this->expectExceptionCode(1236518288);

        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $backend->has('identifier');
    }

    /**
     * @test
     */
    public function hasReturnsTrueForExistingEntry()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->once())
            ->method('exec_SELECTcountRows')
            ->with('*', 'cf_Testing', $this->anything())
            ->will($this->returnValue(1));

        $this->assertTrue($backend->has('aIdentifier'));
    }

    /**
     * @test
     */
    public function hasSetsExceededLifetimeQueryPart()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->once())
            ->method('exec_SELECTcountRows')
            ->with(
                '*',
                'cf_Testing',
                $this->stringContains('identifier =  AND cf_Testing.expires >='))
            ->will($this->returnValue(1));

        $this->assertTrue($backend->has('aIdentifier'));
    }

    /**
     * @test
     */
    public function removeThrowsExceptionIfFrontendWasNotSet()
    {
        $this->expectException(\TYPO3\CMS\Core\Cache\Exception::class);
        $this->expectExceptionCode(1236518288);

        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $backend->remove('identifier');
    }

    /**
     * @test
     */
    public function removeReallyRemovesACacheEntry()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(0))
            ->method('fullQuoteStr')
            ->will($this->returnValue('aIdentifier'));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(1))
            ->method('exec_DELETEquery')
            ->with('cf_Testing', 'identifier = aIdentifier');
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(2))
            ->method('fullQuoteStr')
            ->will($this->returnValue('aIdentifier'));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(3))
            ->method('exec_DELETEquery')
            ->with('cf_Testing_tags', 'identifier = aIdentifier');

        $backend->remove('aIdentifier');
    }

    /**
     * @test
     */
    public function collectGarbageThrowsExceptionIfFrontendWasNotSet()
    {
        $this->expectException(\TYPO3\CMS\Core\Cache\Exception::class);
        $this->expectExceptionCode(1236518288);

        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $backend->collectGarbage();
    }

    /**
     * @test
     */
    public function collectGarbageDeletesTagsFromExpiredEntries()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(1))
            ->method('sql_fetch_assoc')
            ->will($this->returnValue(array('identifier' => 'aIdentifier')));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(2))
            ->method('fullQuoteStr')
            ->will($this->returnValue('aIdentifier'));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(3))
            ->method('sql_fetch_assoc')
            ->will($this->returnValue(false));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(5))
            ->method('exec_DELETEquery')
            ->with('cf_Testing_tags', 'identifier IN (aIdentifier)');

        $backend->collectGarbage();
    }

    /**
     * @test
     */
    public function collectGarbageDeletesExpiredEntry()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(1))
            ->method('sql_fetch_assoc')
            ->will($this->returnValue(false));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(3))
            ->method('exec_DELETEquery')
            ->with('cf_Testing', $this->stringContains('cf_Testing.expires < '));

        $backend->collectGarbage();
    }

    /**
     * @test
     */
    public function findIdentifiersByTagThrowsExceptionIfFrontendWasNotSet()
    {
        $this->expectException(\TYPO3\CMS\Core\Cache\Exception::class);
        $this->expectExceptionCode(1236518288);

        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $backend->findIdentifiersByTag('identifier');
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(0))
            ->method('fullQuoteStr')
            ->will($this->returnValue('cf_Testing_tags'));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(1))
            ->method('exec_SELECTgetRows')
            ->with(
                'cf_Testing.identifier',
                'cf_Testing, cf_Testing_tags',
                $this->stringContains('cf_Testing_tags.tag = cf_Testing_tags AND cf_Testing.identifier = cf_Testing_tags.identifier AND cf_Testing.expires >= '),
                'cf_Testing.identifier'
            )
            ->will($this->returnValue(array(array('identifier' => 'aIdentifier'))));
        $this->assertSame(array('aIdentifier' => 'aIdentifier'), $backend->findIdentifiersByTag('aTag'));
    }

    /**
     * @test
     */
    public function flushThrowsExceptionIfFrontendWasNotSet()
    {
        $this->expectException(\TYPO3\CMS\Core\Cache\Exception::class);
        $this->expectExceptionCode(1236518288);

        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $backend->flush();
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->truncate('cf_Testing')->shouldBeCalled()->willReturn(0);
        $connectionProphet->truncate('cf_Testing_tags')->shouldBeCalled()->willReturn(0);

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getConnectionForTable(Argument::cetera())->willReturn($connectionProphet->reveal());

        // Two instances are required as there are different tables being cleared
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $backend->flush();
    }

    /**
     * @test
     */
    public function flushByTagThrowsExceptionIfFrontendWasNotSet()
    {
        $this->expectException(\TYPO3\CMS\Core\Cache\Exception::class);
        $this->expectExceptionCode(1236518288);

        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $backend->flushByTag(array());
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMockBuilder(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class)
            ->setMethods(array('dummy'))
            ->setConstructorArgs(array('Testing'))
            ->getMock();
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(0))
            ->method('fullQuoteStr')
            ->will($this->returnValue('UnitTestTag%special'));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(1))
            ->method('exec_SELECTquery')
            ->with(
                'DISTINCT identifier',
                'cf_Testing_tags',
                'cf_Testing_tags.tag = UnitTestTag%special'
            );
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(2))
            ->method('sql_fetch_assoc')
            ->will($this->returnValue(array('identifier' => 'BackendDbTest1')));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(3))
            ->method('fullQuoteStr')
            ->with('BackendDbTest1', 'cf_Testing')
            ->will($this->returnValue('BackendDbTest1'));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(4))
            ->method('sql_fetch_assoc')
            ->will($this->returnValue(array('identifier' => 'BackendDbTest2')));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(5))
            ->method('fullQuoteStr')
            ->with('BackendDbTest2', 'cf_Testing')
            ->will($this->returnValue('BackendDbTest2'));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(6))
            ->method('sql_fetch_assoc')
            ->will($this->returnValue(false));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(7))
            ->method('sql_free_result')
            ->will($this->returnValue(true));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(8))
            ->method('exec_DELETEquery')
            ->with('cf_Testing', 'identifier IN (BackendDbTest1, BackendDbTest2)');
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(9))
            ->method('exec_DELETEquery')
            ->with('cf_Testing_tags', 'identifier IN (BackendDbTest1, BackendDbTest2)');

        $backend->flushByTag('UnitTestTag%special');
    }
}
