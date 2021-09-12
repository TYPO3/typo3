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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Domain\Model\Entity;
use TYPO3\CMS\Extbase\Tests\Unit\Persistence\Fixture\Domain\Repository\EntityRepository;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RepositoryTest extends UnitTestCase
{
    /**
     * @var Repository|MockObject|AccessibleObjectInterface
     */
    protected $repository;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

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
        $this->mockBackend = $this->getAccessibleMock(Backend::class, ['dummy'], [$this->mockConfigurationManager], '', false);
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
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->repository = $this->getAccessibleMock(Repository::class, ['dummy'], [$this->mockObjectManager]);
        $this->repository->injectPersistenceManager($this->mockPersistenceManager);
    }

    /**
     * @test
     */
    public function abstractRepositoryImplementsRepositoryInterface(): void
    {
        self::assertInstanceOf(RepositoryInterface::class, $this->repository);
    }

    /**
     * @test
     */
    public function createQueryCallsPersistenceManagerWithExpectedClassName(): void
    {
        $mockPersistenceManager = $this->createMock(PersistenceManager::class);
        $mockPersistenceManager->expects(self::once())->method('createQueryForType')->with('ExpectedType');

        $this->repository->_set('objectType', 'ExpectedType');
        $this->repository->injectPersistenceManager($mockPersistenceManager);

        $this->repository->createQuery();
    }

    /**
     * @test
     */
    public function createQuerySetsDefaultOrderingIfDefined(): void
    {
        $orderings = ['foo' => QueryInterface::ORDER_ASCENDING];
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects(self::once())->method('setOrderings')->with($orderings);
        $mockPersistenceManager = $this->createMock(PersistenceManager::class);
        $mockPersistenceManager->expects(self::exactly(2))->method('createQueryForType')->with('ExpectedType')->willReturn($mockQuery);

        $this->repository->_set('objectType', 'ExpectedType');
        $this->repository->injectPersistenceManager($mockPersistenceManager);
        $this->repository->setDefaultOrderings($orderings);
        $this->repository->createQuery();

        $this->repository->setDefaultOrderings([]);
        $this->repository->createQuery();
    }

    /**
     * @test
     */
    public function findAllCreatesQueryAndReturnsResultOfExecuteCall(): void
    {
        $expectedResult = $this->createMock(QueryResultInterface::class);

        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects(self::once())->method('execute')->with()->willReturn($expectedResult);

        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $repository->expects(self::once())->method('createQuery')->willReturn($mockQuery);

        self::assertSame($expectedResult, $repository->findAll());
    }

    /**
     * @test
     */
    public function findByIdentifierReturnsResultOfGetObjectByIdentifierCallFromBackend(): void
    {
        $identifier = '42';
        $object = new \stdClass();

        $expectedResult = $this->createMock(QueryResultInterface::class);
        $expectedResult->expects(self::once())->method('getFirst')->willReturn($object);

        $this->mockQuery->method('getQuerySettings')->willReturn($this->mockQuerySettings);
        $this->mockQuery->expects(self::once())->method('matching')->willReturn($this->mockQuery);
        $this->mockQuery->expects(self::once())->method('execute')->willReturn($expectedResult);

        // skip backend, as we want to test the backend
        $this->mockSession->method('hasIdentifier')->willReturn(false);
        self::assertSame($object, $this->repository->findByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function addDelegatesToPersistenceManager(): void
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('add')->with($object);
        $this->repository->injectPersistenceManager($mockPersistenceManager);
        $this->repository->_set('objectType', get_class($object));
        $this->repository->add($object);
    }

    /**
     * @test
     */
    public function removeDelegatesToPersistenceManager(): void
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('remove')->with($object);
        $this->repository->injectPersistenceManager($mockPersistenceManager);
        $this->repository->_set('objectType', get_class($object));
        $this->repository->remove($object);
    }

    /**
     * @test
     */
    public function updateDelegatesToPersistenceManager(): void
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('update')->with($object);
        $this->repository->injectPersistenceManager($mockPersistenceManager);
        $this->repository->_set('objectType', get_class($object));
        $this->repository->update($object);
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
            ->setConstructorArgs([$this->mockObjectManager])
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
            ->setConstructorArgs([$this->mockObjectManager])
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
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $repository->expects(self::once())->method('createQuery')->willReturn($mockQuery);

        self::assertSame(2, $repository->countByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled(): void
    {
        $this->expectException(UnsupportedMethodException::class);
        $this->expectExceptionCode(1233180480);
        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $repository->__call('foo', []);
    }

    /**
     * @test
     */
    public function addChecksObjectType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1248363335);
        $this->repository->_set('objectType', 'ExpectedObjectType');
        $this->repository->add(new \stdClass());
    }

    /**
     * @test
     */
    public function removeChecksObjectType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1248363336);
        $this->repository->_set('objectType', 'ExpectedObjectType');
        $this->repository->remove(new \stdClass());
    }

    /**
     * @test
     */
    public function updateChecksObjectType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1249479625);
        $repository = $this->getAccessibleMock(Repository::class, ['dummy'], [$this->mockObjectManager]);
        $repository->_set('objectType', 'ExpectedObjectType');

        $repository->update(new \stdClass());
    }

    /**
     * @test
     */
    public function constructSetsObjectTypeFromClassName(): void
    {
        $repository = new EntityRepository($this->mockObjectManager);

        $reflectionClass = new \ReflectionClass($repository);
        $reflectionProperty = $reflectionClass->getProperty('objectType');
        $reflectionProperty->setAccessible(true);
        $objectType = $reflectionProperty->getValue($repository);

        self::assertEquals(Entity::class, $objectType);
    }

    /**
     * @test
     */
    public function createQueryReturnsQueryWithUnmodifiedDefaultQuerySettings(): void
    {
        $this->mockQuery = $this->createMock(Query::class);
        $mockDefaultQuerySettings = $this->createMock(QuerySettingsInterface::class);
        $this->repository->setDefaultQuerySettings($mockDefaultQuerySettings);
        $query = $this->repository->createQuery();
        $instanceQuerySettings = $query->getQuerySettings();
        self::assertEquals($mockDefaultQuerySettings, $instanceQuerySettings);
        self::assertNotSame($mockDefaultQuerySettings, $instanceQuerySettings);
    }

    /**
     * @test
     */
    public function findByUidReturnsResultOfGetObjectByIdentifierCall(): void
    {
        $fakeUid = '123';
        $object = new \stdClass();
        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['findByIdentifier'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $expectedResult = $object;
        $repository->expects(self::once())->method('findByIdentifier')->willReturn($object);
        $actualResult = $repository->findByUid($fakeUid);
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function updateRejectsObjectsOfWrongType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1249479625);
        $this->repository->_set('objectType', 'Foo');
        $this->repository->update(new \stdClass());
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
