<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
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

class Tx_Extbase_Tests_Unit_DomainObject_AbstractEntityTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithSimpleProperties() {
		$domainObjectName = uniqid('DomainObject_');
		eval('class ' . $domainObjectName . ' extends Tx_Extbase_DomainObject_AbstractEntity {
			public $foo;
			public $bar;
		}');
		$domainObject = new $domainObjectName();
		$domainObject->foo = 'Test';
		$domainObject->bar = 'It is raining outside';
		$domainObject->_memorizeCleanState();

		$this->assertFalse($domainObject->_isDirty());
	}

	/**
	 * @test
	 */
	public function objectIsDirtyAfterCallingMemorizeCleanStateWithSimplePropertiesAndModifyingThePropertiesAfterwards() {
		$domainObjectName = uniqid('DomainObject_');
		eval('class ' . $domainObjectName . ' extends Tx_Extbase_DomainObject_AbstractEntity {
			public $foo;
			public $bar;
		}');
		$domainObject = new $domainObjectName();
		$domainObject->foo = 'Test';
		$domainObject->bar = 'It is raining outside';

		$domainObject->_memorizeCleanState();
		$domainObject->bar = 'Now it is sunny.';

		$this->assertTrue($domainObject->_isDirty());
	}

	/**
	 * @test
	 */
	public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithObjectProperties() {
		$domainObjectName = uniqid('DomainObject_');
		eval('class ' . $domainObjectName . ' extends Tx_Extbase_DomainObject_AbstractEntity {
			public $foo;
			public $bar;
		}');
		$domainObject = new $domainObjectName();
		$domainObject->foo = new DateTime();
		$domainObject->bar = 'It is raining outside';
		$domainObject->_memorizeCleanState();

		$this->assertEquals($domainObject->_isDirty(), FALSE);
	}

	/**
	 * @test
	 */
	public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithOtherDomainObjectsAsProperties() {
		$domainObjectName = uniqid('DomainObject_');
		eval('class ' . $domainObjectName . ' extends Tx_Extbase_DomainObject_AbstractEntity {
			public $foo;
			public $bar;
		}');

		$secondDomainObjectName = uniqid('DomainObject_');
		eval('class ' . $secondDomainObjectName . ' extends Tx_Extbase_DomainObject_AbstractEntity {
			public $foo;
			public $bar;
		}');
		$secondDomainObject = new $secondDomainObjectName;
		$secondDomainObject->_memorizeCleanState();


		$domainObject = new $domainObjectName();
		$domainObject->foo = $secondDomainObject;
		$domainObject->bar = 'It is raining outside';
		$domainObject->_memorizeCleanState();

		$this->assertEquals($domainObject->_isDirty(), FALSE);
	}

	/**
	 * dataProvider for aChangedValueCanBeDetected
	 */
	public function previousAndCurrentValue() {
		$className1 = uniqid('Class_');
		eval('class ' . $className1 . ' extends Tx_Extbase_DomainObject_AbstractEntity {
			public $bar = 42;
		}');

		// An entity with a simple property
		$entity1 = new $className1;
		$entity1->_setProperty('uid', 123);

		// A clone of the entity above
		$clonedEntity1 = clone $entity1;

		$className2 = uniqid('Class_');
		eval('class ' . $className2 . ' extends Tx_Extbase_DomainObject_AbstractEntity {
			public $bar = 99;
		}');
		
		// A different entity
		$entity2 = new $className2;
		$entity2->_setProperty('uid', 321);

		// An entity similar to the second one but with a different uid
		$entity3 = new $className2;
		$entity3->_setProperty('uid', 222);

		// An entity identical to the second one but pointing to a different memory space (identical in sense of Domain-Driven Design)
		$entity4 = new $className2;
		$entity4->_setProperty('uid', 321);

		// An entity similar to the first entity (same properties and uid, but different class)
		$entity5 = new $className2;
		$entity5->bar = 42;
		$entity5->_setProperty('uid', 123);

		$className3 = uniqid('Class_');
		eval('class ' . $className3 . ' extends Tx_Extbase_DomainObject_AbstractValueObject {
			public $bar = 49;
			public $baz = "The quick brown...";
		}');

		// A simple value object
		$valueObject1 = new $className3;
		$valueObject1->_setProperty('uid', 321);

		// A clone of the value object
		$clonedValueObject1 = clone $valueObject1;

		// A value object with the same property values than the first one but with a different uid (identical to the first value object)
		$valueObject2 = new $className3;
		$valueObject2->_setProperty('uid', 111);

		// A value with different property value but the same uid than the first one (not identical to the first value object)
		$valueObject3 = new $className3;
		$valueObject->bar = 456;
		$valueObject3->_setProperty('uid', 111);

		// An empty Object Storage and its clone
		$emptyObjectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		$clonedObjectStorage = clone $emptyObjectStorage;

		// An Object Storage in a clean state holding an object, and its clone
		$objectStorage1 = clone $emptyObjectStorage;
		$objectStorage1->attach(new stdClass);
		$objectStorage1->_memorizeCleanState();
		$clonedObjectStorage1 = clone $objectStorage1;

		// An Object Storage in a dirty state holding an object
		$objectStorage2 = clone $emptyObjectStorage;
		$clonedObjectStorage2 = clone $objectStorage2;
		$objectStorage2->attach(new stdClass);
		
		return array(
			'Same integer values' => array(42, 42, FALSE),
			'Different integer values' => array(42, 666, TRUE),
			'Same number but different types' => array(42, '42', TRUE),
			'Change from NULL to 0 value' => array(NULL, 0, TRUE),
			'Change from 0 to NULL value' => array(0, NULL, TRUE),
			'Two different standard class instances' => array(new stdClass, new stdClass, FALSE),
			'Change from NULL to standard class instance' => array(NULL, new stdClass, TRUE),
			'Change from standard class instance to NULL' => array(new stdClass, NULL, TRUE),
			'Two equal entities (same memory pointer)' => array($entity1, $entity1, FALSE),
			'Entities with different class, uid, and properties' => array($entity1, $entity2, TRUE),
			'Entities with different class but the same uid and properties' => array($entity1, $entity5, TRUE),
			'Entities of same class and property values but different uid' => array($entity2, $entity3, TRUE),
			'Same entity (same class, uid, and property values)' => array($entity2, $entity4, FALSE),
			'An entity and its clone' => array($entity1, $clonedEntity1, FALSE),
			'Value objects with the same property values but a different uid' => array($valueObject1, $valueObject2, FALSE),
			'Value objects with the same uid but different property values' => array($valueObject2, $valueObject3, FALSE),
			'A Value object and its clone' => array($valueObject1, $clonedValueObject1, FALSE),
			'An empty ObjectStorage and its clone' => array($clonedObjectStorage, $emptyObjectStorage, FALSE),
			'An ObjectStorage and its clone' => array($clonedObjectStorage1, $objectStorage1, FALSE),
			'Modified ObjectStorage' => array($clonedObjectStorage2, $objectStorage2, TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider previousAndCurrentValue
	 */
	public function aChangedValueCanBeDetected($previousValue, $currentValue, $expectedResult) {
		$className = uniqid('Class_');
		eval('class ' . $className . ' extends Tx_Extbase_DomainObject_AbstractEntity {}');
		$mockObject = $this->getMock($this->buildAccessibleProxy($className), array('dummy'), array(), '', FALSE);
		
		$this->assertEquals($expectedResult, $mockObject->_call('_propertyValueHasChanged', $previousValue, $currentValue));
	}

}
?>