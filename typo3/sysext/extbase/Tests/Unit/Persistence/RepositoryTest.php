<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Bastian Waidelich <bastian@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class RepositoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Repository|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $repository;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap
	 */
	protected $mockIdentityMap;

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
	protected $querySettings;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
	 */
	protected $mockQuerySettings;

	public function setUp() {
		$this->mockIdentityMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\IdentityMap');
		$this->mockQueryFactory = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactory');
		$this->mockQuery = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryInterface');
		$this->mockQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$this->mockQuery->expects($this->any())->method('getQuerySettings')->will($this->returnValue($this->mockQuerySettings));
		$this->mockQueryFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockQuery));
		$this->mockBackend = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\BackendInterface');
		$this->mockSession = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Session');
		$this->mockPersistenceManager = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface');
		$this->mockPersistenceManager->expects($this->any())->method('createQueryForType')->will($this->returnValue($this->mockQuery));
		$this->mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->repository = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Persistence\\Repository', array('dummy'), array($this->mockObjectManager));
		$this->repository->injectPersistenceManager($this->mockPersistenceManager);
	}

	/**
	 * @test
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$this->assertTrue($this->repository instanceof \TYPO3\CMS\Extbase\Persistence\RepositoryInterface);
	}

	/**
	 * @test
	 */
	public function createQueryCallsPersistenceManagerWithExpectedClassName() {
		$mockPersistenceManager = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
		$mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('ExpectedType');

		$repository = $this->getAccessibleMock('TYPO3\CMS\Extbase\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedType');
		$this->inject($repository, 'persistenceManager', $mockPersistenceManager);

		$repository->createQuery();
	}

	/**
	 * @test
	 */
	public function createQuerySetsDefaultOrderingIfDefined() {
		$orderings = array('foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING);
		$mockQuery = $this->getMock('TYPO3\CMS\Extbase\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('setOrderings')->with($orderings);
		$mockPersistenceManager = $this->getMock('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
		$mockPersistenceManager->expects($this->exactly(2))->method('createQueryForType')->with('ExpectedType')->will($this->returnValue($mockQuery));

		$repository = $this->getAccessibleMock('TYPO3\CMS\Extbase\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedType');
		$this->inject($repository, 'persistenceManager', $mockPersistenceManager);
		$repository->setDefaultOrderings($orderings);
		$repository->createQuery();

		$repository->setDefaultOrderings(array());
		$repository->createQuery();
	}

	/**
	 * @test
	 */
	public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
		$expectedResult = $this->getMock('TYPO3\CMS\Extbase\Persistence\QueryResultInterface');

		$mockQuery = $this->getMock('TYPO3\CMS\Extbase\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));

		$repository = $this->getMock('TYPO3\CMS\Extbase\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($expectedResult, $repository->findAll());
	}

	/**
	 * @test
	 */
	public function findByidentifierReturnsResultOfGetObjectByIdentifierCall() {
		$identifier = '42';
		$object = new \stdClass();

		$mockPersistenceManager = $this->getMock('TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier, 'stdClass')->will($this->returnValue($object));

		$repository = $this->getAccessibleMock('TYPO3\CMS\Extbase\Persistence\Repository', array('createQuery'));
		$this->inject($repository, 'persistenceManager', $mockPersistenceManager);
		$repository->_set('objectType', 'stdClass');

		$this->assertSame($object, $repository->findByIdentifier($identifier));
	}

	/**
	 * @test
	 */
	public function addDelegatesToPersistenceManager() {
		$object = new \stdClass();
		$mockPersistenceManager = $this->getMock('TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('add')->with($object);
		$repository = $this->getAccessibleMock('TYPO3\CMS\Extbase\Persistence\Repository', array('dummy'));
		$this->inject($repository, 'persistenceManager', $mockPersistenceManager);
		$repository->_set('objectType', get_class($object));
		$repository->add($object);
	}

	/**
	 * @test
	 */
	public function removeDelegatesToPersistenceManager() {
		$object = new \stdClass();
		$mockPersistenceManager = $this->getMock('TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('remove')->with($object);
		$repository = $this->getAccessibleMock('TYPO3\CMS\Extbase\Persistence\Repository', array('dummy'));
		$this->inject($repository, 'persistenceManager', $mockPersistenceManager);
		$repository->_set('objectType', get_class($object));
		$repository->remove($object);
	}

	/**
	 * @test
	 */
	public function updateDelegatesToPersistenceManager() {
		$object = new \stdClass();
		$mockPersistenceManager = $this->getMock('TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('update')->with($object);
		$repository = $this->getAccessibleMock('TYPO3\CMS\Extbase\Persistence\Repository', array('dummy'));
		$this->inject($repository, 'persistenceManager', $mockPersistenceManager);
		$repository->_set('objectType', get_class($object));
		$repository->update($object);
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('TYPO3\CMS\Extbase\Persistence\QueryResultInterface');
		$mockQuery = $this->getMock('TYPO3\CMS\Extbase\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($mockQueryResult));

		$repository = $this->getMock('TYPO3\CMS\Extbase\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($mockQueryResult, $repository->findByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$object = new \stdClass();
		$mockQueryResult = $this->getMock('TYPO3\CMS\Extbase\Persistence\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
		$mockQuery = $this->getMock('TYPO3\CMS\Extbase\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('setLimit')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$repository = $this->getMock('TYPO3\CMS\Extbase\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($object, $repository->findOneByFoo('bar'));
	}

	/**
	 * @test
	 */
	public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQuery = $this->getMock('TYPO3\CMS\Extbase\Persistence\QueryInterface');
		$mockQueryResult = $this->getMock('TYPO3\CMS\Extbase\Persistence\QueryResultInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));
		$mockQueryResult->expects($this->once())->method('count')->will($this->returnValue(2));

		$repository = $this->getMock('TYPO3\CMS\Extbase\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame(2, $repository->countByFoo('bar'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException
	 */
	public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled() {
		$repository = $this->getMock('TYPO3\CMS\Extbase\Persistence\Repository', array('createQuery'));
		$repository->__call('foo', array());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function addChecksObjectType() {
		$repository = $this->getAccessibleMock('TYPO3\CMS\Extbase\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedObjectType');

		$repository->add(new \stdClass());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function removeChecksObjectType() {
		$repository = $this->getAccessibleMock('TYPO3\CMS\Extbase\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedObjectType');

		$repository->remove(new \stdClass());
	}
	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function updateChecksObjectType() {
		$repository = $this->getAccessibleMock('TYPO3\CMS\Extbase\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedObjectType');

		$repository->update(new \stdClass());
	}

	/**
	 * dataProvider for createQueryCallsQueryFactoryWithExpectedType
	 *
	 * @return array
	 */
	public function modelAndRepositoryClassNames() {
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
	public function constructSetsObjectTypeFromClassName($repositoryClassName, $modelClassName) {
		$mockClassName = 'MockRepository' . uniqid();
		eval('class ' . $mockClassName . ' extends TYPO3\\CMS\\Extbase\\Persistence\\Repository {
			protected function getRepositoryClassName() {
				return \'' . $repositoryClassName . '\';
			}
			public function _getObjectType() {
				return $this->objectType;
			}
		}');
		$this->repository = new $mockClassName($this->mockObjectManager);
		$this->assertEquals($modelClassName, $this->repository->_getObjectType());
	}

	/**
	 * @test
	 */
	public function createQueryReturnsQueryWithUnmodifiedDefaultQuerySettings() {
		$this->mockQuery = new \TYPO3\CMS\Extbase\Persistence\Generic\Query('foo');
		$mockDefaultQuerySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$this->repository->setDefaultQuerySettings($mockDefaultQuerySettings);
		$query = $this->repository->createQuery();
		$instanceQuerySettings = $query->getQuerySettings();
		$this->assertEquals($mockDefaultQuerySettings, $instanceQuerySettings);
		$this->assertNotSame($mockDefaultQuerySettings, $instanceQuerySettings);
	}

	/**
	 * @test
	 */
	public function findByUidReturnsResultOfGetObjectByIdentifierCall() {
		$fakeUid = '123';
		$object = new \stdClass();
		$this->repository->_set('objectType', 'someObjectType');
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($fakeUid, 'someObjectType')->will($this->returnValue($object));
		$expectedResult = $object;
		$actualResult = $this->repository->findByUid($fakeUid);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 */
	public function updateRejectsObjectsOfWrongType() {
		$this->repository->_set('objectType', 'Foo');
		$this->repository->update(new \stdClass());
	}

	/**
	 * @test
	 */
	public function magicCallMethodReturnsFirstArrayKeyInFindOneBySomethingIfQueryReturnsRawResult() {
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
	public function magicCallMethodReturnsNullInFindOneBySomethingIfQueryReturnsEmptyRawResult() {
		$queryResultArray = array();
		$this->mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$this->mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('setLimit')->with(1)->will($this->returnValue($this->mockQuery));
		$this->mockQuery->expects($this->once())->method('execute')->will($this->returnValue($queryResultArray));
		$this->assertNull($this->repository->findOneByFoo('bar'));
	}

}

?>