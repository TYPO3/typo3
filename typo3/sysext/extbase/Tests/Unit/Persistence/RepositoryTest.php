<?php
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
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException;

/**
 * Test case
 */
class RepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Repository|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
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

    protected function setUp()
    {
        $this->mockQueryFactory = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory::class);
        $this->mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $this->mockQuerySettings = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $this->mockQuery->expects($this->any())->method('getQuerySettings')->will($this->returnValue($this->mockQuerySettings));
        $this->mockQueryFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockQuery));
        $this->mockSession = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\Session::class);
        $this->mockConfigurationManager = $this->createMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class);
        $this->mockBackend = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Backend::class, array('dummy'), array($this->mockConfigurationManager));
        $this->inject($this->mockBackend, 'session', $this->mockSession);
        $this->mockPersistenceManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class, array('createQueryForType'));
        $this->inject($this->mockBackend, 'persistenceManager', $this->mockPersistenceManager);
        $this->inject($this->mockPersistenceManager, 'persistenceSession', $this->mockSession);
        $this->inject($this->mockPersistenceManager, 'backend', $this->mockBackend);
        $this->mockPersistenceManager->expects($this->any())->method('createQueryForType')->will($this->returnValue($this->mockQuery));
        $this->mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->repository = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Repository::class, array('dummy'), array($this->mockObjectManager));
        $this->repository->_set('persistenceManager', $this->mockPersistenceManager);
    }

    /**
     * @test
     */
    public function abstractRepositoryImplementsRepositoryInterface()
    {
        $this->assertTrue($this->repository instanceof \TYPO3\CMS\Extbase\Persistence\RepositoryInterface);
    }

    /**
     * @test
     */
    public function createQueryCallsPersistenceManagerWithExpectedClassName()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        $mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('ExpectedType');

        $this->repository->_set('objectType', 'ExpectedType');
        $this->inject($this->repository, 'persistenceManager', $mockPersistenceManager);

        $this->repository->createQuery();
    }

    /**
     * @test
     */
    public function createQuerySetsDefaultOrderingIfDefined()
    {
        $orderings = array('foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING);
        $mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('setOrderings')->with($orderings);
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        $mockPersistenceManager->expects($this->exactly(2))->method('createQueryForType')->with('ExpectedType')->will($this->returnValue($mockQuery));

        $this->repository->_set('objectType', 'ExpectedType');
        $this->inject($this->repository, 'persistenceManager', $mockPersistenceManager);
        $this->repository->setDefaultOrderings($orderings);
        $this->repository->createQuery();

        $this->repository->setDefaultOrderings(array());
        $this->repository->createQuery();
    }

    /**
     * @test
     */
    public function findAllCreatesQueryAndReturnsResultOfExecuteCall()
    {
        $expectedResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);

        $mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));

        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(array('createQuery'))
            ->setConstructorArgs(array($this->mockObjectManager))
            ->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame($expectedResult, $repository->findAll());
    }

    /**
     * @test
     */
    public function findByidentifierReturnsResultOfGetObjectByIdentifierCallFromBackend()
    {
        $identifier = '42';
        $object = new \stdClass();

        $expectedResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);
        $expectedResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));

        $this->mockQuery->expects($this->any())->method('getQuerySettings')->will($this->returnValue($this->mockQuerySettings));
        $this->mockQuery->expects($this->once())->method('matching')->will($this->returnValue($this->mockQuery));
        $this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($expectedResult));

        // skip backend, as we want to test the backend
        $this->mockSession->expects($this->any())->method('hasIdentifier')->will($this->returnValue(false));
        $this->assertSame($object, $this->repository->findByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function addDelegatesToPersistenceManager()
    {
        $object = new \stdClass();
        $mockPersistenceManager = $this->createMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('add')->with($object);
        $this->inject($this->repository, 'persistenceManager', $mockPersistenceManager);
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
        $mockPersistenceManager->expects($this->once())->method('remove')->with($object);
        $this->inject($this->repository, 'persistenceManager', $mockPersistenceManager);
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
        $mockPersistenceManager->expects($this->once())->method('update')->with($object);
        $this->inject($this->repository, 'persistenceManager', $mockPersistenceManager);
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
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($mockQueryResult));

        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(array('createQuery'))
            ->setConstructorArgs(array($this->mockObjectManager))
            ->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame($mockQueryResult, $repository->findByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $object = new \stdClass();
        $mockQueryResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);
        $mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
        $mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('setLimit')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(array('createQuery'))
            ->setConstructorArgs(array($this->mockObjectManager))
            ->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame($object, $repository->findOneByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria()
    {
        $mockQuery = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class);
        $mockQueryResult = $this->createMock(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class);
        $mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
        $mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));
        $mockQueryResult->expects($this->once())->method('count')->will($this->returnValue(2));

        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(array('createQuery'))
            ->setConstructorArgs(array($this->mockObjectManager))
            ->getMock();
        $repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

        $this->assertSame(2, $repository->countByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled()
    {
        $this->expectException(UnsupportedMethodException::class);
        $this->expectExceptionCode(1233180480);
        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(array('createQuery'))
            ->setConstructorArgs(array($this->mockObjectManager))
            ->getMock();
        $repository->__call('foo', array());
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
        $repository = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Repository::class, array('dummy'), array($this->mockObjectManager));
        $repository->_set('objectType', 'ExpectedObjectType');

        $repository->update(new \stdClass());
    }

    /**
     * dataProvider for createQueryCallsQueryFactoryWithExpectedType
     *
     * @return array
     */
    public function modelAndRepositoryClassNames()
    {
        return array(
            array('Tx_BlogExample_Domain_Repository_BlogRepository', 'Tx_BlogExample_Domain_Model_Blog'),
            array('﻿_Domain_Repository_Content_PageRepository', '﻿_Domain_Model_Content_Page'),
            array('Tx_RepositoryExample_Domain_Repository_SomeModelRepository', 'Tx_RepositoryExample_Domain_Model_SomeModel'),
            array('Tx_RepositoryExample_Domain_Repository_RepositoryRepository', 'Tx_RepositoryExample_Domain_Model_Repository'),
            array('Tx_Repository_Domain_Repository_RepositoryRepository', 'Tx_Repository_Domain_Model_Repository')
        );
    }

    /**
     * @test
     * @dataProvider modelAndRepositoryClassNames
     * @param string $repositoryClassName
     * @param string $modelClassName
     */
    public function constructSetsObjectTypeFromClassName($repositoryClassName, $modelClassName)
    {
        $repositoryClassNameWithNS = __NAMESPACE__ . '\\' . $repositoryClassName;
        eval('namespace ' . __NAMESPACE__ . '; class ' . $repositoryClassName . ' extends \\TYPO3\\CMS\\Extbase\\Persistence\\Repository {
			protected function getRepositoryClassName() {
				return \'' . $repositoryClassName . '\';
			}
			public function _getObjectType() {
				return $this->objectType;
			}
		}');
        $this->repository = new $repositoryClassNameWithNS($this->mockObjectManager);
        $this->assertEquals($modelClassName, $this->repository->_getObjectType());
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
        $this->assertEquals($mockDefaultQuerySettings, $instanceQuerySettings);
        $this->assertNotSame($mockDefaultQuerySettings, $instanceQuerySettings);
    }

    /**
     * @test
     */
    public function findByUidReturnsResultOfGetObjectByIdentifierCall()
    {
        $fakeUid = '123';
        $object = new \stdClass();
        $repository = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Repository::class)
            ->setMethods(array('findByIdentifier'))
            ->setConstructorArgs(array($this->mockObjectManager))
            ->getMock();
        $expectedResult = $object;
        $repository->expects($this->once())->method('findByIdentifier')->will($this->returnValue($object));
        $actualResult = $repository->findByUid($fakeUid);
        $this->assertSame($expectedResult, $actualResult);
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
        $queryResultArray = array(
            0 => array(
                'foo' => 'bar',
            ),
        );
        $this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
        $this->mockQuery->expects($this->once())->method('setLimit')->with(1)->will($this->returnValue($this->mockQuery));
        $this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($queryResultArray));
        $this->assertSame(array('foo' => 'bar'), $this->repository->findOneByFoo('bar'));
    }

    /**
     * @test
     */
    public function magicCallMethodReturnsNullInFindOneBySomethingIfQueryReturnsEmptyRawResult()
    {
        $queryResultArray = array();
        $this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
        $this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
        $this->mockQuery->expects($this->once())->method('setLimit')->with(1)->will($this->returnValue($this->mockQuery));
        $this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($queryResultArray));
        $this->assertNull($this->repository->findOneByFoo('bar'));
    }
}
