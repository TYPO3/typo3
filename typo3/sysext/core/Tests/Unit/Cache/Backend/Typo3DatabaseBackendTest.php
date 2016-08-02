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
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend::class, [], [], '', false);
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
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);
        $this->assertEquals('cf_Testing', $backend->getCacheTable());
    }

    /**
     * @test
     */
    public function setCacheCalculatesTagsTableName()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);
        $this->assertEquals('cf_Testing_tags', $backend->getTagsTable());
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function setThrowsExceptionIfFrontendWasNotSet()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $backend->set('identifier', 'data');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     */
    public function setThrowsExceptionIfDataIsNotAString()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);
        $data = ['Some data'];
        $entryIdentifier = 'BackendDbTest';
        $backend->set($entryIdentifier, $data);
    }

    /**
     * @test
     */
    public function setInsertsEntryInTable()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
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
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['remove'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);

        $backend->expects($this->once())->method('remove');
        $data = $this->getUniqueId('someData');
        $entryIdentifier = 'anIdentifier';
        $backend->set($entryIdentifier, $data, [], 500);
    }

    /**
     * @test
     */
    public function setReallySavesSpecifiedTags()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
        $GLOBALS['TYPO3_DB']
            ->expects($this->once())
            ->method('exec_INSERTmultipleRows')
            ->with(
                'cf_Testing_tags',
                $this->callback(function (array $data) {
                    if ($data[0] === 'identifier' && $data[1] === 'tag') {
                        return true;
                    }
                    return false;
                }),
                $this->callback(function (array $data) {
                    if ($data[0][0] !== 'anIdentifier' || $data[0][1] !== 'UnitTestTag%tag1') {
                        return false;
                    }
                    if ($data[1][0] !== 'anIdentifier' || $data[1][1] !== 'UnitTestTag%tag2') {
                        return false;
                    }
                    return true;
                })
            );
        $backend->set('anIdentifier', 'someData', ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
    }

    /**
     * @test
     */
    public function setSavesCompressedDataWithEnabledCompression()
    {
        $backendOptions = [
            'compression' => true
        ];
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(
            \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
            ['dummy'],
            ['Testing', $backendOptions]
        );
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
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
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
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

        $backend->set('aIdentifier', 'someData', [], 0);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function getThrowsExceptionIfFrontendWasNotSet()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $backend->get('identifier');
    }

    /**
     * @test
     */
    public function getReturnsContentOfTheCorrectCacheEntry()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
        $GLOBALS['TYPO3_DB']
            ->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->with('content', 'cf_Testing', $this->anything())
            ->will($this->returnValue(['content' => 'someData']));

        $loadedData = $backend->get('aIdentifier');
        $this->assertEquals('someData', $loadedData);
    }

    /**
     * @test
     */
    public function getSetsExceededLifetimeQueryPart()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
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
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function hasThrowsExceptionIfFrontendWasNotSet()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $backend->has('identifier');
    }

    /**
     * @test
     */
    public function hasReturnsTrueForExistingEntry()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
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
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
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
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function removeThrowsExceptionIfFrontendWasNotSet()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $backend->remove('identifier');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function collectGarbageThrowsExceptionIfFrontendWasNotSet()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $backend->collectGarbage();
    }

    /**
     * @test
     */
    public function collectGarbageDeletesTagsFromExpiredEntries()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(1))
            ->method('sql_fetch_assoc')
            ->will($this->returnValue(['identifier' => 'aIdentifier']));
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
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function findIdentifiersByTagThrowsExceptionIfFrontendWasNotSet()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $backend->findIdentifiersByTag('identifier');
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
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
            ->will($this->returnValue([['identifier' => 'aIdentifier']]));
        $this->assertSame(['aIdentifier' => 'aIdentifier'], $backend->findIdentifiersByTag('aTag'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function flushThrowsExceptionIfFrontendWasNotSet()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $backend->flush();
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(0))
            ->method('exec_TRUNCATEquery')
            ->with('cf_Testing');
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(1))
            ->method('exec_TRUNCATEquery')
            ->with('cf_Testing_tags');

        $backend->flush();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Cache\Exception
     */
    public function flushByTagThrowsExceptionIfFrontendWasNotSet()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $backend->flushByTag([]);
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        /** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
        $backend = $this->getMock(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class, ['dummy'], ['Testing']);
        $this->setUpMockFrontendOfBackend($backend);

        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
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
            ->will($this->returnValue(['identifier' => 'BackendDbTest1']));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(3))
            ->method('fullQuoteStr')
            ->with('BackendDbTest1', 'cf_Testing')
            ->will($this->returnValue('BackendDbTest1'));
        $GLOBALS['TYPO3_DB']
            ->expects($this->at(4))
            ->method('sql_fetch_assoc')
            ->will($this->returnValue(['identifier' => 'BackendDbTest2']));
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
