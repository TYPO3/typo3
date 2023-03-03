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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Persistence;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RepositoryTest extends UnitTestCase
{
    /**
     * @var Repository|MockObject|AccessibleObjectInterface
     */
    protected $repository;

    /**
     * @var QueryFactory
     */
    protected $mockQueryFactory;

    /**
     * @var BackendInterface
     */
    protected $mockBackend;

    /**
     * @var Session
     */
    protected $mockSession;

    /**
     * @var PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * @var QueryInterface
     */
    protected $mockQuery;

    /**
     * @var QuerySettingsInterface
     */
    protected $mockQuerySettings;

    /**
     * @var ConfigurationManager
     */
    protected $mockConfigurationManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockQueryFactory = $this->createMock(QueryFactory::class);
        $this->mockQuery = $this->createMock(QueryInterface::class);
        $this->mockQuerySettings = $this->createMock(QuerySettingsInterface::class);
        $this->mockQuery->method('getQuerySettings')->willReturn($this->mockQuerySettings);
        $this->mockQueryFactory->method('create')->willReturn($this->mockQuery);
        $this->mockSession = $this->createMock(Session::class);
        $this->mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $this->mockBackend = $this->getAccessibleMock(Backend::class, null, [$this->mockConfigurationManager], '', false);
        $this->mockBackend->_set('session', $this->mockSession);
        $this->mockPersistenceManager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['createQueryForType'],
            [
                $this->mockQueryFactory,
                $this->mockBackend,
                $this->mockSession,
            ]
        );
        $this->mockBackend->setPersistenceManager($this->mockPersistenceManager);
        $this->mockPersistenceManager->method('createQueryForType')->willReturn($this->mockQuery);
        $this->repository = $this->getAccessibleMock(Repository::class, null);
        $this->repository->injectPersistenceManager($this->mockPersistenceManager);
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria(): void
    {
        $mockQueryResult = $this->createMock(QueryResultInterface::class);
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->willReturn($mockQuery);
        $mockQuery->expects(self::once())->method('execute')->with()->willReturn($mockQueryResult);

        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->getMock();
        $repository->expects(self::once())->method('createQuery')->willReturn($mockQuery);

        self::assertSame($mockQueryResult, $repository->findByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria(): void
    {
        $object = new \stdClass();
        $mockQueryResult = $this->createMock(QueryResultInterface::class);
        $mockQueryResult->expects(self::once())->method('getFirst')->willReturn($object);
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->willReturn($mockQuery);
        $mockQuery->expects(self::once())->method('setLimit')->willReturn($mockQuery);
        $mockQuery->expects(self::once())->method('execute')->willReturn($mockQueryResult);

        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->getMock();
        $repository->expects(self::once())->method('createQuery')->willReturn($mockQuery);

        self::assertSame($object, $repository->findOneByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria(): void
    {
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQueryResult = $this->createMock(QueryResultInterface::class);
        $mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->willReturn($mockQuery);
        $mockQuery->expects(self::once())->method('execute')->willReturn($mockQueryResult);
        $mockQueryResult->expects(self::once())->method('count')->willReturn(2);

        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->getMock();
        $repository->expects(self::once())->method('createQuery')->willReturn($mockQuery);

        self::assertSame(2, $repository->countByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodReturnsFirstArrayKeyInFindOneBySomethingIfQueryReturnsRawResult(): void
    {
        $queryResultArray = [
            0 => [
                'foo' => 'bar',
            ],
        ];
        $this->mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $this->mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->willReturn($this->mockQuery);
        $this->mockQuery->expects(self::once())->method('setLimit')->with(1)->willReturn($this->mockQuery);
        $this->mockQuery->expects(self::once())->method('execute')->willReturn($queryResultArray);
        self::assertSame(['foo' => 'bar'], $this->repository->findOneByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodReturnsNullInFindOneBySomethingIfQueryReturnsEmptyRawResult(): void
    {
        $queryResultArray = [];
        $this->mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $this->mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->willReturn($this->mockQuery);
        $this->mockQuery->expects(self::once())->method('setLimit')->with(1)->willReturn($this->mockQuery);
        $this->mockQuery->expects(self::once())->method('execute')->willReturn($queryResultArray);
        self::assertNull($this->repository->findOneByFoo('bar'));
    }
}
