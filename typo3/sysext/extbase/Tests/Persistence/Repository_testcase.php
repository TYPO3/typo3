<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
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

class Tx_Extbase_Persistence_Repository_testcase extends Tx_Extbase_Base_testcase {
	
	public function setUp() {
		$this->aggregateRootClassName = uniqid('Tx_Aggregate_Root_Class_');
		eval('class ' . $this->aggregateRootClassName . ' implements Tx_Extbase_DomainObject_DomainObjectInterface {
			public function _memorizeCleanState() {}
			public function _isNew() {}
			public function _isDirty() {}
			public function _setProperty($propertyName, $propertyValue) {}
			public function _getProperty($propertyName) {}
			public function _getProperties() {}
			public function _getDirtyProperties() {}
			public function getUid() { return 123; }
		}');
	}
	
	/**
	 * @test
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$mockRepository = $this->getMock('Tx_Extbase_Persistence_Repository', array('dummy'), array(), '', FALSE);
		$this->assertTrue($mockRepository instanceof Tx_Extbase_Persistence_RepositoryInterface);
	}
	
	/**
	 * @test
	 */
	public function addRegistersAnObjectAtThePersistenceSessionAsAdded() {
		$aggregateRootObject = new $this->aggregateRootClassName;
		
		$mockPersistenceSession = $this->getMock('Tx_Extbase_Persistence_Session', array(), array(), '', FALSE);
		$mockPersistenceSession->expects($this->once())->method('registerAddedObject')->with($aggregateRootObject);

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));

		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('dummy'), array(), '', FALSE);
		$mockRepository->_set('persistenceManager', $mockPersistenceManager);
		
		$mockRepository->add($aggregateRootObject);
	}

	/**
	 * @test
	 */
	public function removeRegistersAnObjectAtThePersistenceSessionAsRemoved() {
		$aggregateRootObject = new $this->aggregateRootClassName;

		$mockPersistenceSession = $this->getMock('Tx_Extbase_Persistence_Session', array(), array(), '', FALSE);
		$mockPersistenceSession->expects($this->once())->method('registerRemovedObject')->with($aggregateRootObject);

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));

		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('dummy'), array(), '', FALSE);
		$mockRepository->_set('persistenceManager', $mockPersistenceManager);

		$mockRepository->remove($aggregateRootObject);
	}
	
	/**
	 * @test
	 */
	public function createQueryCallsQueryFactoryWithExpectedType() {
		$fakeRepositoryClassName = $this->aggregateRootClassName . 'Repository';
		
		$mockQueryFactory = $this->getMock('Tx_Extbase_Persistence_QueryFactoryInterface');
		$mockQueryFactory->expects($this->once())->method('create')->with($this->aggregateRootClassName);
	
		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('getRepositoryClassName'), array(), $fakeRepositoryClassName, FALSE);
		$mockRepository->_set('objectType', $this->aggregateRootClassName);
		$mockRepository->_set('queryFactory', $mockQueryFactory);
		
		$mockRepository->createQuery();
	}
	
	/**
	 * @test
	 */
	public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
		$expectedResult = array('one', 'two');

		$mockPersistenceSession = $this->getMock('Tx_Extbase_Persistence_Session', array(), array(), '', FALSE);
		$mockPersistenceSession->expects($this->once())->method('registerReconstitutedObjects')->with($expectedResult);

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));
	
		$mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($expectedResult));
	
		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('createQuery'), array(), '', FALSE);
		$mockRepository->_set('persistenceManager', $mockPersistenceManager);
		$mockRepository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));
	
		$this->assertSame($expectedResult, $mockRepository->findAll());
	}
	
	/**
	 * @test
	 */
	public function findByUidCreatesQueryAndReturnsResultOfExecuteCall() {
		$fakeUid = 123;

		$mockPersistenceSession = $this->getMock('Tx_Extbase_Persistence_Session');

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));

		$mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$mockQuery->expects($this->once())->method('withUid')->with($fakeUid)->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array('one', 'two')));
	
		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('createQuery'), array(), '', FALSE);
		$mockRepository->_set('persistenceManager', $mockPersistenceManager);
		$mockRepository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));
	
		$this->assertSame('one', $mockRepository->findByUid($fakeUid));
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function findByUidResultsInAnExceptionForInvalidUidArgument() {
		$mockRepository = $this->getMock('Tx_Extbase_Persistence_Repository', array('createQuery'), array(), '', FALSE);
		$mockRepository->findByUid(-123);
	}
	
	/**
	 * Replacing a reconstituted object (which has a uid) by a new object
	 * will ask the persistence backend to replace them accordingly in the
	 * identity map.
	 *
	 * @test
	 * @return void
	 */
	public function replaceReconstitutedObjectByNewObject() {
		$existingObject = new $this->aggregateRootClassName;
		$newObject = new $this->aggregateRootClassName;
	
		$mockPersistenceBackend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$mockPersistenceBackend->expects($this->once())->method('getUidByObject')->with($existingObject)->will($this->returnValue(123));
		$mockPersistenceBackend->expects($this->once())->method('replaceObject')->with($existingObject, $newObject);
	
		$mockPersistenceSession = $this->getMock('Tx_Extbase_Persistence_Session', array(), array(), '', FALSE);
		$mockPersistenceSession->expects($this->once())->method('unregisterReconstitutedObject')->with($existingObject);
		$mockPersistenceSession->expects($this->once())->method('registerReconstitutedObject')->with($newObject);

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));
		$mockPersistenceManager->expects($this->once())->method('getBackend')->will($this->returnValue($mockPersistenceBackend));

		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('dummy'), array(), '', FALSE);
		$mockRepository->_set('persistenceManager', $mockPersistenceManager);
		$mockRepository->replace($existingObject, $newObject);
	}
	
	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_UnknownObject
	 */
	public function tryingToReplaceAnUnknownObjectResultsInAnException() {
		$existingObject = new $this->aggregateRootClassName;
		$newObject = new $this->aggregateRootClassName;
	
		$mockPersistenceBackend = $this->getMock('Tx_Extbase_Persistence_Backend', array('getUidByObject'), array(), '', FALSE);
		$mockPersistenceBackend->expects($this->once())->method('getUidByObject')->with($existingObject)->will($this->returnValue(NULL));

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getBackend')->will($this->returnValue($mockPersistenceBackend));

		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('dummy'), array(), '', FALSE);
		$mockRepository->_set('persistenceManager', $mockPersistenceManager);
		$mockRepository->replace($existingObject, $newObject);
	}
	
	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockPersistenceSession = $this->getMock('Tx_Extbase_Persistence_Session', array(), array(), '', FALSE);
		
		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));

		$mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('fooBaz', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array('baz', 'quux')));
	
		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('createQuery'), array(), '', FALSE);
		$mockRepository->_set('persistenceManager', $mockPersistenceManager);
		$mockRepository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));
	
		$this->assertSame(array('baz', 'quux'), $mockRepository->findByFooBaz('bar'));
	}
	
	/**
	 * @test
	 */
	public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockPersistenceSession = $this->getMock('Tx_Extbase_Persistence_Session', array(), array(), '', FALSE);

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getSession')->will($this->returnValue($mockPersistenceSession));

		$mockQuery = $this->getMock('Tx_Extbase_Persistence_QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('setLimit')->with(1)->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue(array('baz', 'foo')));
	
		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('createQuery'), array(), '', FALSE);
		$mockRepository->_set('persistenceManager', $mockPersistenceManager);
		$mockRepository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));
	
		$this->assertSame('baz', $mockRepository->findOneByFoo('bar'));
	}
	
	/**
	 * @test
	 * @expectedException Tx_Extbase_Persistence_Exception_UnsupportedMethod
	 */
	public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled() {
		$mockRepository = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_Persistence_Repository'), array('dummy'), array(), '', FALSE);
		$mockRepository->__call('foo', array());
	}
	
}
?>
