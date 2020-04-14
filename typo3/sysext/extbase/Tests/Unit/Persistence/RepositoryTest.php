<?php

declare(strict_types=1);
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

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

use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RepositoryTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Repository|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $repository;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory
     */
    protected $mockQueryFactory;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface
     */
    protected $mockBackend;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
     */
    protected $mockSession;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\QueryInterface
     */
    protected $mockQuery;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
    */
    protected $mockQuerySettings;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
     */
    protected $mockConfigurationManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockQueryFactory = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory::class);
        $this->mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $this->mockQuerySettings = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $this->mockQuery->expects(self::any())->method('getQuerySettings')->willReturn($this->mockQuerySettings);
        $this->mockQueryFactory->expects(self::any())->method('create')->willReturn($this->mockQuery);
        $this->mockSession = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\Session::class);
        $this->mockConfigurationManager = $this->createMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class);
        $this->mockBackend = $this->getAccessibleMock(Backend::class, ['dummy'], [$this->mockConfigurationManager], '', false);
        $this->mockBackend->_set('session', $this->mockSession);
        $this->mockPersistenceManager = $this->getAccessibleMock(
            \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class,
            ['createQueryForType'],
            [
                $this->mockQueryFactory,
                $this->mockBackend,
                $this->mockSession
            ]
        );
        $this->mockBackend->setPersistenceManager($this->mockPersistenceManager);
        $this->mockPersistenceManager->expects(self::any())->method('createQueryForType')->willReturn($this->mockQuery);
        $this->mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->repository = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Repository::class, ['dummy'], [$this->mockObjectManager]);
        $this->repository->injectPersistenceManager($this->mockPersistenceManager);
    }

    /**
     * @test
     */
    public function abstractRepositoryImplementsRepositoryInterface()
    {
        self::assertTrue($this->repository instanceof \TYPO3\CMS\Extbase\Persistence\RepositoryInterface);
    }

    /**
     * @test
     */
    public function createQueryCallsPersistenceManagerWithExpectedClassName()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        $mockPersistenceManager->expects(self::once())->method('createQueryForType')->with('ExpectedType');

        $this->repository->_set('objectType', 'ExpectedType');
        $this->repository->injectPersistenceManager($mockPersistenceManager);

        $this->repository->createQuery();
    }

    /**
     * @test
     */
    public function createQuerySetsDefaultOrderingIfDefined()
    {
        $orderings = ['foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING];
        $mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQuery->expects(self::once())->method('setOrderings')->with($orderings);
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
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
    public function findAllCreatesQueryAndReturnsResultOfExecuteCall()
    {
        $expectedResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);

        $mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQuery->expects(self::once())->method('execute')->with()->willReturn($expectedResult);

        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(['createQuery'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $repository->expects(self::once())->method('createQuery')->willReturn($mockQuery);

        self::assertSame($expectedResult, $repository->findAll());
    }

    /**
     * @test
     */
    public function findByIdentifierReturnsResultOfGetObjectByIdentifierCallFromBackend()
    {
        $identifier = '42';
        $object = new \stdClass();

        $expectedResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);
        $expectedResult->expects(self::once())->method('getFirst')->willReturn($object);

        $this->mockQuery->expects(self::any())->method('getQuerySettings')->willReturn($this->mockQuerySettings);
        $this->mockQuery->expects(self::once())->method('matching')->willReturn($this->mockQuery);
        $this->mockQuery->expects(self::once())->method('execute')->willReturn($expectedResult);

        // skip backend, as we want to test the backend
        $this->mockSession->expects(self::any())->method('hasIdentifier')->willReturn(false);
        self::assertSame($object, $this->repository->findByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function addDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('add')->with($object);
        $this->repository->injectPersistenceManager($mockPersistenceManager);
        $this->repository->_set('objectType', get_class($object));
        $this->repository->add($object);
    }

    /**
     * @test
     */
    public function removeDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('remove')->with($object);
        $this->repository->injectPersistenceManager($mockPersistenceManager);
        $this->repository->_set('objectType', get_class($object));
        $this->repository->remove($object);
    }

    /**
     * @test
     */
    public function updateDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('update')->with($object);
        $this->repository->injectPersistenceManager($mockPersistenceManager);
        $this->repository->_set('objectType', get_class($object));
        $this->repository->update($object);
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQueryResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);
        $mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->willReturn($mockQuery);
        $mockQuery->expects(self::once())->method('execute')->with()->willReturn($mockQueryResult);

        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(['createQuery'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $repository->expects(self::once())->method('createQuery')->willReturn($mockQuery);

        self::assertSame($mockQueryResult, $repository->findByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $object = new \stdClass();
        $mockQueryResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);
        $mockQueryResult->expects(self::once())->method('getFirst')->willReturn($object);
        $mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->willReturn($mockQuery);
        $mockQuery->expects(self::once())->method('setLimit')->willReturn($mockQuery);
        $mockQuery->expects(self::once())->method('execute')->willReturn($mockQueryResult);

        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(['createQuery'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $repository->expects(self::once())->method('createQuery')->willReturn($mockQuery);

        self::assertSame($object, $repository->findOneByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQueryResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);
        $mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->willReturn($mockQuery);
        $mockQuery->expects(self::once())->method('execute')->willReturn($mockQueryResult);
        $mockQueryResult->expects(self::once())->method('count')->willReturn(2);

        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(['createQuery'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $repository->expects(self::once())->method('createQuery')->willReturn($mockQuery);

        self::assertSame(2, $repository->countByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled()
    {
        $this->expectException(UnsupportedMethodException::class);
        $this->expectExceptionCode(1233180480);
        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(['createQuery'])
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $repository->__call('foo', []);
    }

    /**
     * @test
     */
    public function addChecksObjectType()
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1248363335);
        $this->repository->_set('objectType', 'ExpectedObjectType');
        $this->repository->add(new \stdClass());
    }

    /**
     * @test
     */
    public function removeChecksObjectType()
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1248363336);
        $this->repository->_set('objectType', 'ExpectedObjectType');
        $this->repository->remove(new \stdClass());
    }

    /**
     * @test
     */
    public function updateChecksObjectType()
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1249479625);
        $repository = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Repository::class, ['dummy'], [$this->mockObjectManager]);
        $repository->_set('objectType', 'ExpectedObjectType');

        $repository->update(new \stdClass());
    }

    /**
     * @test
     */
    public function constructSetsObjectTypeFromClassName()
    {
        $repository = new Fixture\Domain\Repository\EntityRepository($this->mockObjectManager);

        $reflectionClass = new \ReflectionClass($repository);
        $reflectionProperty = $reflectionClass->getProperty('objectType');
        $reflectionProperty->setAccessible(true);
        $objectType = $reflectionProperty->getValue($repository);

        self::assertEquals(Fixture\Domain\Model\Entity::class, $objectType);
    }

    /**
     * @test
     */
    public function createQueryReturnsQueryWithUnmodifiedDefaultQuerySettings()
    {
        $this->mockQuery = new \TYPO3\CMS\Extbase\Persistence\Generic\Query('foo');
        $mockDefaultQuerySettings = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $this->repository->setDefaultQuerySettings($mockDefaultQuerySettings);
        $query = $this->repository->createQuery();
        $instanceQuerySettings = $query->getQuerySettings();
        self::assertEquals($mockDefaultQuerySettings, $instanceQuerySettings);
        self::assertNotSame($mockDefaultQuerySettings, $instanceQuerySettings);
    }

    /**
     * @test
     */
    public function findByUidReturnsResultOfGetObjectByIdentifierCall()
    {
        $fakeUid = '123';
        $object = new \stdClass();
        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(['findByIdentifier'])
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
    public function updateRejectsObjectsOfWrongType()
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1249479625);
        $this->repository->_set('objectType', 'Foo');
        $this->repository->update(new \stdClass());
    }

    /**
     * @test
     */
    public function magicCallMethodReturnsFirstArrayKeyInFindOneBySomethingIfQueryReturnsRawResult()
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
    public function magicCallMethodReturnsNullInFindOneBySomethingIfQueryReturnsEmptyRawResult()
    {
        $queryResultArray = [];
        $this->mockQuery->expects(self::once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $this->mockQuery->expects(self::once())->method('matching')->with('matchCriteria')->willReturn($this->mockQuery);
        $this->mockQuery->expects(self::once())->method('setLimit')->with(1)->willReturn($this->mockQuery);
        $this->mockQuery->expects(self::once())->method('execute')->willReturn($queryResultArray);
        self::assertNull($this->repository->findOneByFoo('bar'));
    }
}
