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

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Event\AddCacheTagEvent;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\Selector;
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
 * @todo: These tests smell as if they should be turned into functional tests
 */
final class RepositoryTest extends UnitTestCase
{
    private Repository&MockObject&AccessibleObjectInterface $subject;
    private MockObject&Session $mockSession;
    private MockObject&QueryInterface $mockQuery;
    private MockObject&QuerySettingsInterface $mockQuerySettings;

    protected function setUp(): void
    {
        parent::setUp();
        $mockQueryFactory = $this->createMock(QueryFactory::class);
        $this->mockQuery = $this->createMock(QueryInterface::class);
        $this->mockQuerySettings = $this->createMock(QuerySettingsInterface::class);
        $this->mockQuerySettings->method('getLanguageAspect')->willReturn(new LanguageAspect());
        $this->mockQuery->method('getQuerySettings')->willReturn($this->mockQuerySettings);
        $mockQueryFactory->method('create')->willReturn($this->mockQuery);
        $this->mockSession = $this->createMock(Session::class);
        $mockBackend = $this->getAccessibleMock(Backend::class, null, [$this->createMock(ConfigurationManager::class)], '', false);
        $mockBackend->_set('session', $this->mockSession);
        $mockPersistenceManager = $this->getAccessibleMock(
            PersistenceManager::class,
            ['createQueryForType'],
            [
                $mockQueryFactory,
                $mockBackend,
                $this->mockSession,
            ]
        );
        $mockBackend->setPersistenceManager($mockPersistenceManager);
        $mockPersistenceManager->method('createQueryForType')->willReturn($this->mockQuery);
        $this->subject = $this->getAccessibleMock(Repository::class, null);
        $this->subject->injectPersistenceManager($mockPersistenceManager);
    }

    #[Test]
    public function abstractRepositoryImplementsRepositoryInterface(): void
    {
        self::assertInstanceOf(RepositoryInterface::class, $this->subject);
    }

    #[Test]
    public function createQueryCallsPersistenceManagerWithExpectedClassName(): void
    {
        $mockPersistenceManager = $this->createMock(PersistenceManager::class);
        $mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('ExpectedType');

        $this->subject->_set('objectType', 'ExpectedType');
        $this->subject->injectPersistenceManager($mockPersistenceManager);

        $this->subject->createQuery();
    }

    #[Test]
    public function createQuerySetsDefaultOrderingIfDefined(): void
    {
        $orderings = ['foo' => QueryInterface::ORDER_ASCENDING];
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects($this->once())->method('setOrderings')->with($orderings);
        $mockPersistenceManager = $this->createMock(PersistenceManager::class);
        $mockPersistenceManager->expects($this->exactly(2))->method('createQueryForType')->with('ExpectedType')->willReturn($mockQuery);

        $this->subject->_set('objectType', 'ExpectedType');
        $this->subject->injectPersistenceManager($mockPersistenceManager);
        $this->subject->setDefaultOrderings($orderings);
        $this->subject->createQuery();

        $this->subject->setDefaultOrderings([]);
        $this->subject->createQuery();
    }

    #[Test]
    public function findAllCreatesQueryAndReturnsResultOfExecuteCall(): void
    {
        $expectedResult = $this->createMock(QueryResultInterface::class);
        $source = new Selector('tx_myext_domain_model_foo', 'not_relevant');

        $this->mockQuery->method('getSource')->willReturn($source);
        $this->mockQuery->expects($this->once())->method('execute')->with()->willReturn($expectedResult);
        $this->mockQuerySettings->method('getStoragePageIds')->willReturn([]);
        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->getMock();
        $repository->expects($this->exactly(1))->method('createQuery')->willReturn($this->mockQuery);
        $features = $this->createMock(Features::class);
        $features->method('isFeatureEnabled')->willReturn(false);
        $repository->injectFeatures($features);

        self::assertSame($expectedResult, $repository->findAll());
    }

    #[Test]
    public function findByIdentifierReturnsResultOfGetObjectByIdentifierCallFromBackend(): void
    {
        $identifier = '42';
        $object = new \stdClass();

        $expectedResult = $this->createMock(QueryResultInterface::class);
        $expectedResult->expects($this->once())->method('getFirst')->willReturn($object);

        $this->mockQuery->method('getQuerySettings')->willReturn($this->mockQuerySettings);
        $this->mockQuery->expects($this->once())->method('matching')->willReturn($this->mockQuery);
        $this->mockQuery->expects($this->once())->method('execute')->willReturn($expectedResult);

        // skip backend, as we want to test the backend
        $this->mockSession->method('hasIdentifier')->willReturn(false);
        self::assertNotNull($this->subject->findByIdentifier($identifier));
    }

    #[Test]
    public function addDelegatesToPersistenceManager(): void
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('add')->with($object);
        $this->subject->injectPersistenceManager($mockPersistenceManager);
        $this->subject->_set('objectType', get_class($object));
        $this->subject->add($object);
    }

    #[Test]
    public function removeDelegatesToPersistenceManager(): void
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('remove')->with($object);
        $this->subject->injectPersistenceManager($mockPersistenceManager);
        $this->subject->_set('objectType', get_class($object));
        $this->subject->remove($object);
    }

    #[Test]
    public function updateDelegatesToPersistenceManager(): void
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('update')->with($object);
        $this->subject->injectPersistenceManager($mockPersistenceManager);
        $this->subject->_set('objectType', get_class($object));
        $this->subject->update($object);
    }

    #[Test]
    public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled(): void
    {
        $this->expectException(UnsupportedMethodException::class);
        $this->expectExceptionCode(1233180480);
        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->getMock();
        $repository->__call('foo', []);
    }

    #[Test]
    public function addChecksObjectType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1248363335);
        $this->subject->_set('objectType', 'ExpectedObjectType');
        $this->subject->add(new \stdClass());
    }

    #[Test]
    public function removeChecksObjectType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1248363336);
        $this->subject->_set('objectType', 'ExpectedObjectType');
        $this->subject->remove(new \stdClass());
    }

    #[Test]
    public function updateChecksObjectType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1249479625);
        $repository = $this->getAccessibleMock(Repository::class, null);
        $repository->_set('objectType', 'ExpectedObjectType');

        $repository->update(new \stdClass());
    }

    #[Test]
    public function constructSetsObjectTypeFromClassName(): void
    {
        $repository = new EntityRepository();
        $reflectionClass = new \ReflectionClass($repository);
        $reflectionProperty = $reflectionClass->getProperty('objectType');
        $objectType = $reflectionProperty->getValue($repository);

        self::assertEquals(Entity::class, $objectType);
    }

    #[Test]
    public function createQueryReturnsQueryWithUnmodifiedDefaultQuerySettings(): void
    {
        $this->mockQuery = $this->createMock(Query::class);
        $mockDefaultQuerySettings = $this->createMock(QuerySettingsInterface::class);
        $this->subject->setDefaultQuerySettings($mockDefaultQuerySettings);
        $query = $this->subject->createQuery();
        $instanceQuerySettings = $query->getQuerySettings();
        self::assertEquals($mockDefaultQuerySettings, $instanceQuerySettings);
        self::assertNotSame($mockDefaultQuerySettings, $instanceQuerySettings);
    }

    #[Test]
    public function findByUidReturnsResultOfGetObjectByIdentifierCall(): void
    {
        $fakeUid = '123';
        $object = new \stdClass();
        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['findByIdentifier'])
            ->getMock();
        $expectedResult = $object;
        $repository->expects($this->once())->method('findByIdentifier')->willReturn($object);
        $actualResult = $repository->findByUid($fakeUid);
        self::assertNotNull($actualResult);
    }

    #[Test]
    public function updateRejectsObjectsOfWrongType(): void
    {
        $this->expectException(IllegalObjectTypeException::class);
        $this->expectExceptionCode(1249479625);
        $this->subject->_set('objectType', 'Foo');
        $this->subject->update(new \stdClass());
    }

    #[Test]
    #[IgnoreDeprecations]
    public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria(): void
    {
        $mockQueryResult = $this->createMock(QueryResultInterface::class);
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->willReturn($mockQuery);
        $mockQuery->expects($this->once())->method('execute')->with()->willReturn($mockQueryResult);

        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->getMock();
        $repository->expects($this->once())->method('createQuery')->willReturn($mockQuery);
        $features = $this->createMock(Features::class);
        $features->method('isFeatureEnabled')->willReturn(false);
        $repository->injectFeatures($features);

        /** @phpstan-ignore-next-line */
        self::assertSame($mockQueryResult, $repository->findByFoo('bar'));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria(): void
    {
        $object = new \stdClass();
        $mockQueryResult = $this->createMock(QueryResultInterface::class);
        $mockQueryResult->expects($this->once())->method('getFirst')->willReturn($object);
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->willReturn($mockQuery);
        $mockQuery->expects($this->once())->method('setLimit')->willReturn($mockQuery);
        $mockQuery->expects($this->once())->method('execute')->willReturn($mockQueryResult);

        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->getMock();
        $repository->expects($this->once())->method('createQuery')->willReturn($mockQuery);
        $features = $this->createMock(Features::class);
        $features->method('isFeatureEnabled')->willReturn(false);
        $repository->injectFeatures($features);

        /** @phpstan-ignore-next-line */
        self::assertSame($object, $repository->findOneByFoo('bar'));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria(): void
    {
        $mockQuery = $this->createMock(QueryInterface::class);
        $mockQueryResult = $this->createMock(QueryResultInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->willReturn($mockQuery);
        $mockQuery->expects($this->once())->method('execute')->willReturn($mockQueryResult);
        $mockQueryResult->expects($this->once())->method('count')->willReturn(2);

        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->getMock();
        $repository->expects($this->once())->method('createQuery')->willReturn($mockQuery);
        $features = $this->createMock(Features::class);
        $features->method('isFeatureEnabled')->willReturn(false);
        $repository->injectFeatures($features);

        /** @phpstan-ignore-next-line */
        self::assertSame(2, $repository->countByFoo('bar'));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function magicCallMethodReturnsFirstArrayKeyInFindOneBySomethingIfQueryReturnsRawResult(): void
    {
        $queryResultArray = [
            0 => [
                'foo' => 'bar',
            ],
        ];
        $this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->willReturn($this->mockQuery);
        $this->mockQuery->expects($this->once())->method('setLimit')->with(1)->willReturn($this->mockQuery);
        $this->mockQuery->expects($this->once())->method('execute')->willReturn($queryResultArray);
        $features = $this->createMock(Features::class);
        $features->method('isFeatureEnabled')->willReturn(false);
        $this->subject->injectFeatures($features);
        /** @phpstan-ignore-next-line */
        self::assertSame(['foo' => 'bar'], $this->subject->findOneByFoo('bar'));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function magicCallMethodReturnsNullInFindOneBySomethingIfQueryReturnsEmptyRawResult(): void
    {
        $queryResultArray = [];
        $this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->willReturn('matchCriteria');
        $this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->willReturn($this->mockQuery);
        $this->mockQuery->expects($this->once())->method('setLimit')->with(1)->willReturn($this->mockQuery);
        $this->mockQuery->expects($this->once())->method('execute')->willReturn($queryResultArray);
        $features = $this->createMock(Features::class);
        $features->method('isFeatureEnabled')->willReturn(false);
        $this->subject->injectFeatures($features);
        /** @phpstan-ignore-next-line */
        self::assertNull($this->subject->findOneByFoo('bar'));
    }

    #[Test]
    public function findAllCreatesTableCacheTagForEmptyStoragePid(): void
    {
        $mockQuery = $this->createMock(QueryResultInterface::class);
        $source = new Selector('tx_myext_domain_model_foo', 'not_relevant');

        $this->mockQuerySettings->method('getStoragePageIds')->willReturn([]);
        $this->mockQuery->method('execute')->willReturn($mockQuery);
        $this->mockQuery->method('getSource')->willReturn($source);

        $mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $mockEventDispatcher->expects($this->once())->method('dispatch')->with(
            self::isInstanceOf(AddCacheTagEvent::class),
        )->willReturnCallback(
            function (AddCacheTagEvent $event) {
                self::assertSame('tx_myext_domain_model_foo', $event->cacheTag->name);
            }
        );

        $repository = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['createQuery'])
            ->getMock();
        $repository->expects($this->exactly(1))->method('createQuery')->willReturn($this->mockQuery);
        $repository->injectEventDispatcher($mockEventDispatcher);
        $features = $this->createMock(Features::class);
        $features->method('isFeatureEnabled')->willReturn(true);
        $repository->injectFeatures($features);

        self::assertSame($mockQuery, $repository->findAll());
    }
}
