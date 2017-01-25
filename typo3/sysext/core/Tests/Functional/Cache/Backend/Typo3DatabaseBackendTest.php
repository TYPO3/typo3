<?php
namespace TYPO3\CMS\Core\Tests\Functional\Cache\Backend;

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

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;

/**
 * Test case
 */
class Typo3DatabaseBackendTest extends FunctionalTestCase
{

    /**
     * @test
     */
    public function getReturnsPreviouslySetEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('myIdentifier', 'myData');
        $this->assertSame('myData', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsPreviouslySetEntryWithNewContentIfSetWasCalledMultipleTimes()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('myIdentifier', 'myData');
        $subject->set('myIdentifier', 'myNewData');
        $this->assertSame('myNewData', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function setInsertsDataWithTagsIntoCacheTable()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('myIdentifier', 'myData', ['aTag', 'anotherTag']);

        $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="myIdentifier"');
        $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="myIdentifier" AND tag="aTag"');
        $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="myIdentifier" AND tag="anotherTag"');
    }

    /**
     * @test
     */
    public function setStoresCompressedContent()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Have backend with compression enabled
        $subject = new Typo3DatabaseBackend('Testing', ['compression' => true]);
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('myIdentifier', 'myCachedContent');

        $row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'content',
            'cf_cache_pages',
            'identifier="myIdentifier"'
        );

        // Content comes back uncompressed
        $this->assertSame('myCachedContent', gzuncompress($row['content']));
    }

    /**
     * @test
     */
    public function getReturnsFalseIfNoCacheEntryExists()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->assertFalse($subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsFalseForExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Push an expired row into db
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] - 60,
                'content' => 'myCachedContent',
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->assertFalse($subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsNotExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Push a row into db
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => 'myCachedContent',
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->assertSame('myCachedContent', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsUnzipsNotExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Push a compressed row into db
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => gzcompress('myCachedContent'),
            ]
        );

        // Have backend with compression enabled
        $subject = new Typo3DatabaseBackend('Testing', ['compression' => true]);
        $subject->setCache($frontendProphecy->reveal());

        // Content comes back uncompressed
        $this->assertSame('myCachedContent', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsEmptyStringUnzipped()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Push a compressed row into db
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => gzcompress(''),
            ]
        );

        // Have backend with compression enabled
        $subject = new Typo3DatabaseBackend('Testing', ['compression' => true]);
        $subject->setCache($frontendProphecy->reveal());

        // Content comes back uncompressed
        $this->assertSame('', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfNoCacheEntryExists()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->assertFalse($subject->has('myIdentifier'));
    }

    /**
     * @test
     */
    public function hasReturnsFalseForExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Push an expired row into db
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] - 60,
                'content' => 'myCachedContent',
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->assertFalse($subject->has('myIdentifier'));
    }

    /**
     * @test
     */
    public function hasReturnsNotExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Push a row into db
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => 'myCachedContent',
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->assertTrue($subject->has('myIdentifier'));
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfNoEntryHasBeenRemoved()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->assertFalse($subject->remove('myIdentifier'));
    }

    /**
     * @test
     */
    public function removeReturnsTrueIfAnEntryHasBeenRemoved()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Push a row into db
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => 'myCachedContent',
            ]
        );
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());
        $this->assertTrue($subject->remove('myIdentifier'));
    }

    /**
     * @test
     */
    public function removeRemovesCorrectEntriesFromDatabase()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Add one cache row to remove and another one that shouldn't be removed
        $GLOBALS['TYPO3_DB']->INSERTmultipleRows(
            'cf_cache_pages',
            ['identifier', 'expires', 'content'],
            [
                ['myIdentifier', $GLOBALS['EXEC_TIME'] + 60, 'myCachedContent'],
                ['otherIdentifier', $GLOBALS['EXEC_TIME'] + 60, 'otherCachedContent'],
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        // Add a couple of tags
        $GLOBALS['TYPO3_DB']->INSERTmultipleRows(
            'cf_cache_pages',
            ['identifier', 'tag'],
            [
                ['myIdentifier', 'aTag'],
                ['myIdentifier', 'otherTag'],
                ['otherIdentifier', 'aTag'],
                ['otherIdentifier', 'otherTag'],
            ]
        );

        $subject->remove('myIdentifier');

        // cache row with removed identifier has been removed, other one exists
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="myIdentifier"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="otherIdentifier"'));

        // tags of myIdentifier should have been removed, others exist
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="myIdentifier"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="otherIdentifier"'));
    }

    /**
     * @test
     */
    public function findIdentifiersByTagReturnsIdentifierTaggedWithGivenTag()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('idA', 'dataA', ['tagA', 'tagB']);
        $subject->set('idB', 'dataB', ['tagB', 'tagC']);

        $this->assertSame(['idA' => 'idA'], $subject->findIdentifiersByTag('tagA'));
        $this->assertSame(['idA' => 'idA', 'idB' => 'idB'], $subject->findIdentifiersByTag('tagB'));
        $this->assertSame(['idB' => 'idB'], $subject->findIdentifiersByTag('tagC'));
    }

    /**
     * @test
     */
    public function flushByTagWorksWithEmptyCacheTablesWithMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(true);
        $subject->setCache($frontendProphecy->reveal());

        $subject->flushByTag('tagB');
    }

    /**
     * @test
     */
    public function flushByTagRemovesCorrectRowsFromDatabaseWithMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(true);
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('idA', 'dataA', ['tagA', 'tagB']);
        $subject->set('idB', 'dataB', ['tagB', 'tagC']);
        $subject->set('idC', 'dataC', ['tagC', 'tagD']);
        $subject->flushByTag('tagB');

        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idA"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idB"'));
        $this->assertSame(1, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idC"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idA"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idB"'));
        $this->assertSame(2, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idC"'));
    }

    /**
     * @test
     */
    public function flushByTagWorksWithEmptyCacheTablesWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        $subject->flushByTag('tagB');
    }

    /**
     * @test
     */
    public function flushByTagRemovesCorrectRowsFromDatabaseWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('idA', 'dataA', ['tagA', 'tagB']);
        $subject->set('idB', 'dataB', ['tagB', 'tagC']);
        $subject->set('idC', 'dataC', ['tagC', 'tagD']);
        $subject->flushByTag('tagB');

        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idA"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idB"'));
        $this->assertSame(1, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idC"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idA"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idB"'));
        $this->assertSame(2, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idC"'));
    }

    /**
     * @test
     */
    public function collectGarbageWorksWithEmptyTableWithMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(true);
        $subject->setCache($frontendProphecy->reveal());

        $subject->collectGarbage();
    }

    /**
     * @test
     */
    public function collectGarbageRemovesCacheEntryWithExpiredLifetimeWithMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(true);
        $subject->setCache($frontendProphecy->reveal());

        // idA should be expired after EXEC_TIME manipulation, idB should stay
        $subject->set('idA', 'dataA', [], 60);
        $subject->set('idB', 'dataB', [], 240);

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idA"'));
        $this->assertSame(1, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idB"'));
    }

    /**
     * @test
     */
    public function collectGarbageRemovesTagEntriesForCacheEntriesWithExpiredLifetimeWithMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(true);
        $subject->setCache($frontendProphecy->reveal());

        // tag rows tagA and tagB should be removed by garbage collector after EXEC_TIME manipulation
        $subject->set('idA', 'dataA', ['tagA', 'tagB'], 60);
        $subject->set('idB', 'dataB', ['tagB', 'tagC'], 240);

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idA"'));
        $this->assertSame(2, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idB"'));
    }

    /**
     * @test
     */
    public function collectGarbageRemovesOrphanedTagEntriesFromTagsTableWithMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(true);
        $subject->setCache($frontendProphecy->reveal());

        // tag rows tagA and tagB should be removed by garbage collector after EXEC_TIME manipulation
        $subject->set('idA', 'dataA', ['tagA', 'tagB'], 60);
        $subject->set('idB', 'dataB', ['tagB', 'tagC'], 240);

        // Push two orphaned tag row into db - tags that have no related cache record anymore for whatever reason
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages_tags',
            [
                'identifier' => 'idC',
                'tag' => 'tagC'
            ]
        );
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages_tags',
            [
                'identifier' => 'idC',
                'tag' => 'tagD'
            ]
        );

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idA"'));
        $this->assertSame(2, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idB"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idC"'));
    }

    /**
     * @test
     */
    public function collectGarbageWorksWithEmptyTableWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        $subject->collectGarbage();
    }

    /**
     * @test
     */
    public function collectGarbageRemovesCacheEntryWithExpiredLifetimeWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        // idA should be expired after EXEC_TIME manipulation, idB should stay
        $subject->set('idA', 'dataA', [], 60);
        $subject->set('idB', 'dataB', [], 240);

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idA"'));
        $this->assertSame(1, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages', 'identifier="idB"'));
    }

    /**
     * @test
     */
    public function collectGarbageRemovesTagEntriesForCacheEntriesWithExpiredLifetimeWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        // tag rows tagA and tagB should be removed by garbage collector after EXEC_TIME manipulation
        $subject->set('idA', 'dataA', ['tagA', 'tagB'], 60);
        $subject->set('idB', 'dataB', ['tagB', 'tagC'], 240);

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idA"'));
        $this->assertSame(2, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idB"'));
    }

    /**
     * @test
     */
    public function collectGarbageRemovesOrphanedTagEntriesFromTagsTableWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects($this->once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        // tag rows tagA and tagB should be removed by garbage collector after EXEC_TIME manipulation
        $subject->set('idA', 'dataA', ['tagA', 'tagB'], 60);
        $subject->set('idB', 'dataB', ['tagB', 'tagC'], 240);

        // Push two orphaned tag row into db - tags that have no related cache record anymore for whatever reason
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages_tags',
            [
                'identifier' => 'idC',
                'tag' => 'tagC'
            ]
        );
        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
            'cf_cache_pages_tags',
            [
                'identifier' => 'idC',
                'tag' => 'tagD'
            ]
        );

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idA"'));
        $this->assertSame(2, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idB"'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags', 'identifier="idC"'));
    }

    /**
     * @test
     */
    public function flushLeavesCacheAndTagsTableEmpty()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('idA', 'dataA', ['tagA', 'tagB']);

        $subject->flush();

        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages'));
        $this->assertSame(0, $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cf_cache_pages_tags'));
    }
}
