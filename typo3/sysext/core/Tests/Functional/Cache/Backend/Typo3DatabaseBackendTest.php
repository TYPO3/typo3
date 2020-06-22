<?php

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

namespace TYPO3\CMS\Core\Tests\Functional\Cache\Backend;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

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
        $frontendProphecy->getIdentifier()->willReturn('pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('myIdentifier', 'myData');
        self::assertSame('myData', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsPreviouslySetEntryWithNewContentIfSetWasCalledMultipleTimes()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('myIdentifier', 'myData');
        $subject->set('myIdentifier', 'myNewData');
        self::assertSame('myNewData', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function setInsertsDataWithTagsIntoCacheTable()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('myIdentifier', 'myData', ['aTag', 'anotherTag']);

        $cacheTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages');
        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');
        self::assertSame(1, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'myIdentifier']));
        self::assertSame(1, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'myIdentifier', 'tag' => 'aTag']));
        self::assertSame(1, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'myIdentifier', 'tag' => 'anotherTag']));
    }

    /**
     * @test
     */
    public function setStoresCompressedContent()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Have backend with compression enabled
        $subject = new Typo3DatabaseBackend('Testing', ['compression' => true]);
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('myIdentifier', 'myCachedContent');

        $row = (new ConnectionPool())
            ->getConnectionForTable('cache_pages')
            ->select(
                ['content'],
                'cache_pages',
                ['identifier' => 'myIdentifier']
            )
            ->fetch();

        // Content comes back uncompressed
        self::assertSame('myCachedContent', gzuncompress($row['content']));
    }

    /**
     * @test
     */
    public function getReturnsFalseIfNoCacheEntryExists()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        self::assertFalse($subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsFalseForExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Push an expired row into db
        (new ConnectionPool())->getConnectionForTable('cache_pages')->insert(
            'cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] - 60,
                'content' => 'myCachedContent',
            ],
            [
                'content' => Connection::PARAM_LOB
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        self::assertFalse($subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsNotExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Push a row into db
        (new ConnectionPool())->getConnectionForTable('cache_pages')->insert(
            'cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => 'myCachedContent',
            ],
            [
                'content' => Connection::PARAM_LOB
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        self::assertSame('myCachedContent', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsUnzipsNotExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Push a compressed row into db
        (new ConnectionPool())->getConnectionForTable('cache_pages')->insert(
            'cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => gzcompress('myCachedContent'),
            ],
            [
                'content' => Connection::PARAM_LOB
            ]
        );

        // Have backend with compression enabled
        $subject = new Typo3DatabaseBackend('Testing', ['compression' => true]);
        $subject->setCache($frontendProphecy->reveal());

        // Content comes back uncompressed
        self::assertSame('myCachedContent', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function getReturnsEmptyStringUnzipped()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Push a compressed row into db
        (new ConnectionPool())->getConnectionForTable('cache_pages')->insert(
            'cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => gzcompress(''),
            ],
            [
                'content' => Connection::PARAM_LOB
            ]
        );

        // Have backend with compression enabled
        $subject = new Typo3DatabaseBackend('Testing', ['compression' => true]);
        $subject->setCache($frontendProphecy->reveal());

        // Content comes back uncompressed
        self::assertSame('', $subject->get('myIdentifier'));
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfNoCacheEntryExists()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        self::assertFalse($subject->has('myIdentifier'));
    }

    /**
     * @test
     */
    public function hasReturnsFalseForExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Push an expired row into db
        (new ConnectionPool())->getConnectionForTable('cache_pages')->insert(
            'cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] - 60,
                'content' => 'myCachedContent',
            ],
            [
                'content' => Connection::PARAM_LOB
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        self::assertFalse($subject->has('myIdentifier'));
    }

    /**
     * @test
     */
    public function hasReturnsNotExpiredCacheEntry()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Push a row into db
        (new ConnectionPool())->getConnectionForTable('cache_pages')->insert(
            'cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => 'myCachedContent',
            ],
            [
                'content' => Connection::PARAM_LOB
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        self::assertTrue($subject->has('myIdentifier'));
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfNoEntryHasBeenRemoved()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        self::assertFalse($subject->remove('myIdentifier'));
    }

    /**
     * @test
     */
    public function removeReturnsTrueIfAnEntryHasBeenRemoved()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Push a row into db
        (new ConnectionPool())->getConnectionForTable('cache_pages')->insert(
            'cache_pages',
            [
                'identifier' => 'myIdentifier',
                'expires' => $GLOBALS['EXEC_TIME'] + 60,
                'content' => 'myCachedContent',
            ],
            [
                'content' => Connection::PARAM_LOB
            ]
        );

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        self::assertTrue($subject->remove('myIdentifier'));
    }

    /**
     * @test
     */
    public function removeRemovesCorrectEntriesFromDatabase()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Add one cache row to remove and another one that shouldn't be removed
        $cacheTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages');
        $cacheTableConnection->bulkInsert(
            'cache_pages',
            [
                ['myIdentifier', $GLOBALS['EXEC_TIME'] + 60, 'myCachedContent'],
                ['otherIdentifier', $GLOBALS['EXEC_TIME'] + 60, 'otherCachedContent'],
            ],
            ['identifier', 'expires', 'content'],
            [
                'content' => Connection::PARAM_LOB
            ]
        );
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        // Add a couple of tags
        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');
        $tagsTableConnection->bulkInsert(
            'cache_pages_tags',
            [
                ['myIdentifier', 'aTag'],
                ['myIdentifier', 'otherTag'],
                ['otherIdentifier', 'aTag'],
                ['otherIdentifier', 'otherTag'],
            ],
            ['identifier', 'tag']
        );

        $subject->remove('myIdentifier');

        // cache row with removed identifier has been removed, other one exists
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'myIdentifier']));
        self::assertSame(1, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'otherIdentifier']));

        // tags of myIdentifier should have been removed, others exist
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'myIdentifier']));
        self::assertSame(2, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'otherIdentifier']));
    }

    /**
     * @test
     */
    public function findIdentifiersByTagReturnsIdentifierTaggedWithGivenTag()
    {
        $subject = $this->getSubjectObject();

        self::assertEquals(['idA' => 'idA'], $subject->findIdentifiersByTag('tagA'));
        self::assertEquals(['idA' => 'idA', 'idB' => 'idB'], $subject->findIdentifiersByTag('tagB'));
        self::assertEquals(['idB' => 'idB', 'idC' => 'idC'], $subject->findIdentifiersByTag('tagC'));
    }

    /**
     * @test
     *
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function flushByTagWorksWithEmptyCacheTablesWithMysql()
    {
        $subject = $this->getSubjectObject(true);
        $subject->flushByTag('tagB');
    }

    /**
     * @test
     *
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function flushByTagsWorksWithEmptyCacheTablesWithMysql()
    {
        $subject = $this->getSubjectObject(true);
        $subject->flushByTags(['tagB']);
    }

    /**
     * @test
     *
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function flushByTagRemovesCorrectRowsFromDatabaseWithMysql()
    {
        $subject = $this->getSubjectObject(true);
        $subject->flushByTag('tagB');

        $cacheTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages');
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idA']));
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idB']));
        self::assertSame(1, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idC']));
        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idA']));
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idB']));
        self::assertSame(2, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idC']));
    }

    /**
     * @test
     *
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function flushByTagsRemovesCorrectRowsFromDatabaseWithMysql()
    {
        $subject = $this->getSubjectObject(true);
        $subject->flushByTags(['tagC', 'tagD']);

        $cacheTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages');
        self::assertSame(1, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idA']));
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idB']));
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idC']));
        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');
        self::assertSame(2, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idA']));
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idB']));
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idC']));
    }

    /**
     * @test
     */
    public function flushByTagWorksWithEmptyCacheTablesWithNonMysql()
    {
        $subject = $this->getSubjectObject(true, false);
        $subject->flushByTag('tagB');
    }

    /**
     * @test
     */
    public function flushByTagsWorksWithEmptyCacheTablesWithNonMysql()
    {
        $subject = $this->getSubjectObject(true, false);
        $subject->flushByTags(['tagB', 'tagC']);
    }

    /**
     * @test
     */
    public function flushByTagRemovesCorrectRowsFromDatabaseWithNonMysql()
    {
        $subject = $this->getSubjectObject(true, false);
        $subject->flushByTag('tagB');

        $cacheTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages');
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idA']));
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idB']));
        self::assertSame(1, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idC']));
        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idA']));
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idB']));
        self::assertSame(2, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idC']));
    }

    /**
     * @test
     */
    public function flushByTagsRemovesCorrectRowsFromDatabaseWithNonMysql()
    {
        $subject = $this->getSubjectObject(true, false);
        $subject->flushByTags(['tagC', 'tagD']);

        $cacheTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages');
        self::assertSame(1, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idA']));
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idB']));
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idC']));
        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');
        self::assertSame(2, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idA']));
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idB']));
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idC']));
    }

    /**
     * @test
     *
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function collectGarbageWorksWithEmptyTableWithMysql()
    {
        $subject = $this->getSubjectObject(true);
        $subject->collectGarbage();
    }

    /**
     * @test
     *
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function collectGarbageRemovesCacheEntryWithExpiredLifetimeWithMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects(self::once())->method('isConnectionMysql')->willReturn(true);
        $subject->setCache($frontendProphecy->reveal());

        // idA should be expired after EXEC_TIME manipulation, idB should stay
        $subject->set('idA', 'dataA', [], 60);
        $subject->set('idB', 'dataB', [], 240);

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $cacheTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages');
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idA']));
        self::assertSame(1, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idB']));
    }

    /**
     * @test
     *
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function collectGarbageRemovesTagEntriesForCacheEntriesWithExpiredLifetimeWithMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects(self::once())->method('isConnectionMysql')->willReturn(true);
        $subject->setCache($frontendProphecy->reveal());

        // tag rows tagA and tagB should be removed by garbage collector after EXEC_TIME manipulation
        $subject->set('idA', 'dataA', ['tagA', 'tagB'], 60);
        $subject->set('idB', 'dataB', ['tagB', 'tagC'], 240);

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idA']));
        self::assertSame(2, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idB']));
    }

    /**
     * @test
     *
     * @group not-postgres
     * @group not-mssql
     * @group not-sqlite
     */
    public function collectGarbageRemovesOrphanedTagEntriesFromTagsTableWithMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects(self::once())->method('isConnectionMysql')->willReturn(true);
        $subject->setCache($frontendProphecy->reveal());

        // tag rows tagA and tagB should be removed by garbage collector after EXEC_TIME manipulation
        $subject->set('idA', 'dataA', ['tagA', 'tagB'], 60);
        $subject->set('idB', 'dataB', ['tagB', 'tagC'], 240);

        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');

        // Push two orphaned tag row into db - tags that have no related cache record anymore for whatever reason
        $tagsTableConnection->insert(
            'cache_pages_tags',
            [
                'identifier' => 'idC',
                'tag' => 'tagC'
            ]
        );
        $tagsTableConnection->insert(
            'cache_pages_tags',
            [
                'identifier' => 'idC',
                'tag' => 'tagD'
            ]
        );

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idA']));
        self::assertSame(2, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idB']));
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idC']));
    }

    /**
     * @test
     */
    public function collectGarbageWorksWithEmptyTableWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects(self::once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        $subject->collectGarbage();
    }

    /**
     * @test
     */
    public function collectGarbageRemovesCacheEntryWithExpiredLifetimeWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects(self::once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        // idA should be expired after EXEC_TIME manipulation, idB should stay
        $subject->set('idA', 'dataA', [], 60);
        $subject->set('idB', 'dataB', [], 240);

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $cacheTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages');
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idA']));
        self::assertSame(1, $cacheTableConnection->count('*', 'cache_pages', ['identifier' => 'idB']));
    }

    /**
     * @test
     */
    public function collectGarbageRemovesTagEntriesForCacheEntriesWithExpiredLifetimeWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects(self::once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        // tag rows tagA and tagB should be removed by garbage collector after EXEC_TIME manipulation
        $subject->set('idA', 'dataA', ['tagA', 'tagB'], 60);
        $subject->set('idB', 'dataB', ['tagB', 'tagC'], 240);

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idA']));
        self::assertSame(2, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idB']));
    }

    /**
     * @test
     */
    public function collectGarbageRemovesOrphanedTagEntriesFromTagsTableWithNonMysql()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        // Must be mocked here to test for "mysql" version implementation
        $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
            ->setMethods(['isConnectionMysql'])
            ->setConstructorArgs(['Testing'])
            ->getMock();
        $subject->expects(self::once())->method('isConnectionMysql')->willReturn(false);
        $subject->setCache($frontendProphecy->reveal());

        // tag rows tagA and tagB should be removed by garbage collector after EXEC_TIME manipulation
        $subject->set('idA', 'dataA', ['tagA', 'tagB'], 60);
        $subject->set('idB', 'dataB', ['tagB', 'tagC'], 240);

        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');

        // Push two orphaned tag row into db - tags that have no related cache record anymore for whatever reason
        $tagsTableConnection->insert(
            'cache_pages_tags',
            [
                'identifier' => 'idC',
                'tag' => 'tagC'
            ]
        );
        $tagsTableConnection->insert(
            'cache_pages_tags',
            [
                'identifier' => 'idC',
                'tag' => 'tagD'
            ]
        );

        $GLOBALS['EXEC_TIME'] = $GLOBALS['EXEC_TIME'] + 120;

        $subject->collectGarbage();

        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idA']));
        self::assertSame(2, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idB']));
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', ['identifier' => 'idC']));
    }

    /**
     * @test
     */
    public function flushLeavesCacheAndTagsTableEmpty()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('idA', 'dataA', ['tagA', 'tagB']);

        $subject->flush();

        $cacheTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages');
        $tagsTableConnection = (new ConnectionPool())->getConnectionForTable('cache_pages_tags');
        self::assertSame(0, $cacheTableConnection->count('*', 'cache_pages', []));
        self::assertSame(0, $tagsTableConnection->count('*', 'cache_pages_tags', []));
    }

    /**
     * @param bool $returnMockObject
     * @param bool $isConnectionMysql
     *
     * @return Typo3DatabaseBackend
     */
    protected function getSubjectObject($returnMockObject = false, $isConnectionMysql = true)
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('pages');

        if (!$returnMockObject) {
            $subject = new Typo3DatabaseBackend('Testing');
        } else {
            $subject = $this->getMockBuilder(Typo3DatabaseBackend::class)
                ->setMethods(['isConnectionMysql'])
                ->setConstructorArgs(['Testing'])
                ->getMock();
            $subject->expects(self::once())->method('isConnectionMysql')->willReturn($isConnectionMysql);
        }
        $subject->setCache($frontendProphecy->reveal());

        $subject->set('idA', 'dataA', ['tagA', 'tagB']);
        $subject->set('idB', 'dataB', ['tagB', 'tagC']);
        $subject->set('idC', 'dataC', ['tagC', 'tagD']);

        return $subject;
    }
}
