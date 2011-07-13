<?php
/***************************************************************
*  Copyright notice
*  (c) 2010 Daniel Pötzinger
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

require_once(t3lib_extMgm::extPath('extbase') . 'Tests/Unit/Object/Container/Fixtures/Testclasses.php');

/**
 * Testcase for class t3lib_object_Container.
 *
 * @author Daniel Pötzinger
 * @author Bastian Waidelich <bastian@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class Tx_Extbase_Tests_Unit_Object_Container_ContainerTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	private $container;

	public function setUp() {
			//our mocked cache will allways indicate that he has nothing in the cache to force that we get the real classinfo
		$mockedCache = $this->getMock('Tx_Extbase_Object_Container_ClassInfoCache',array('has'));
		$mockedCache->expects($this->any())->method('has')->will($this->returnValue(false));

		$this->container = $this->getMock('Tx_Extbase_Object_Container_Container', array('log','getClassInfoCache'));
		$this->container->expects($this->any())->method('getClassInfoCache')->will($this->returnValue($mockedCache));
	}

	/**
	 * @test
	 */
	public function getInstanceReturnsInstanceOfSimpleClass() {
		$object = $this->container->getInstance('t3lib_object_tests_c');
		$this->assertInstanceOf('t3lib_object_tests_c', $object);
	}

	/**
	 * @test
	 */
	public function getInstanceReturnsInstanceOfAClassWithConstructorInjection() {
		$object = $this->container->getInstance('t3lib_object_tests_b');
		$this->assertInstanceOf('t3lib_object_tests_b', $object);
		$this->assertInstanceOf('t3lib_object_tests_c', $object->c);
	}

	/**
	 * @test
	 */
	public function getInstanceReturnsInstanceOfAClassWithTwoLevelDependency() {
		$object = $this->container->getInstance('t3lib_object_tests_a');
		$this->assertInstanceOf('t3lib_object_tests_a', $object);
		$this->assertInstanceOf('t3lib_object_tests_c', $object->b->c);
	}

	/**
	 * @test
	 */
	public function getInstanceReturnsInstanceOfAClassWithMixedSimpleTypeAndConstructorInjection() {
		$object = $this->container->getInstance('t3lib_object_tests_amixed_array');
		$this->assertInstanceOf('t3lib_object_tests_amixed_array', $object);
		$this->assertEquals(array('some' => 'default'), $object->myvalue);
	}

	/**
	 * @test
	 */
	public function getInstanceReturnsInstanceOfAClassWithMixedSimpleTypeAndConstructorInjectionWithNullDefaultValue() {
		$object = $this->container->getInstance('t3lib_object_tests_amixed_null');
		$this->assertInstanceOf('t3lib_object_tests_amixed_null', $object);
		$this->assertNull($object->myvalue);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Object_Exception
	 */
	public function getInstanceThrowsExceptionWhenTryingToInstanciateASingletonWithConstructorParameters() {
		$this->container->getInstance('t3lib_object_tests_amixed_array_singleton', array('somevalue'));
	}

	/**
	 * @test
	 */
	public function getInstanceReturnsInstanceOfAClassWithConstructorInjectionAndDefaultConstructorParameters() {
		$object = $this->container->getInstance('t3lib_object_tests_amixed_array');
		$this->assertInstanceOf('t3lib_object_tests_b', $object->b);
		$this->assertInstanceOf('t3lib_object_tests_c', $object->c);
		$this->assertEquals(array('some' => 'default'), $object->myvalue);
	}

	/**
	 * @test
	 */
	public function getInstancePassesGivenParameterToTheNewObject() {
		$mockObject = $this->getMock('t3lib_object_tests_c');

		$object = $this->container->getInstance('t3lib_object_tests_a', array($mockObject));
		$this->assertInstanceOf('t3lib_object_tests_a', $object);
		$this->assertSame($mockObject, $object->c);
	}

	/**
	 * @test
	 */
	public function getInstanceReturnsAFreshInstanceIfObjectIsNoSingleton() {
		$object1 = $this->container->getInstance('t3lib_object_tests_a');
		$object2 = $this->container->getInstance('t3lib_object_tests_a');

		$this->assertNotSame($object1, $object2);
	}

	/**
	 * @test
	 */
	public function getInstanceReturnsSameInstanceInstanceIfObjectIsSingleton() {
		$object1 = $this->container->getInstance('t3lib_object_tests_singleton');
		$object2 = $this->container->getInstance('t3lib_object_tests_singleton');

		$this->assertSame($object1, $object2);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Object_Exception_CannotBuildObject
	 */
	public function getInstanceThrowsExceptionIfPrototypeObjectsWiredViaConstructorInjectionContainCyclicDependencies() {
		$this->container->getInstance('t3lib_object_tests_cyclic1WithSetterDependency');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Object_Exception_CannotBuildObject
	 */
	public function getInstanceThrowsExceptionIfPrototypeObjectsWiredViaSetterInjectionContainCyclicDependencies() {
		$this->container->getInstance('t3lib_object_tests_cyclic1');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Object_Exception
	 */
	public function getInstanceThrowsExceptionIfClassWasNotFound() {
		$this->container->getInstance('nonextistingclass_bla');
	}

	/**
	 * @test
	 */
	public function getEmptyObjectReturnsInstanceOfSimpleClass() {
		$object = $this->container->getEmptyObject('t3lib_object_tests_c');
		$this->assertInstanceOf('t3lib_object_tests_c', $object);
	}

	/**
	 * @test
	 */
	public function test_canGetChildClass() {
		$object = $this->container->getInstance('t3lib_object_tests_b_child');
		$this->assertInstanceOf('t3lib_object_tests_b_child', $object);
	}

	/**
	 * @test
	 */
	public function test_canInjectInterfaceInClass() {
		$this->container->registerImplementation('t3lib_object_tests_someinterface', 't3lib_object_tests_someimplementation');
		$object = $this->container->getInstance('t3lib_object_tests_needsinterface');
		$this->assertInstanceOf('t3lib_object_tests_needsinterface', $object);

		$this->assertInstanceOf('t3lib_object_tests_someinterface', $object->dependency);
		$this->assertInstanceOf('t3lib_object_tests_someimplementation', $object->dependency);
	}

	/**
	 * @test
	 */
	public function test_canBuildCyclicDependenciesOfSingletonsWithSetter() {
		$object = $this->container->getInstance('t3lib_object_tests_resolveablecyclic1');
		$this->assertInstanceOf('t3lib_object_tests_resolveablecyclic1', $object);
		$this->assertInstanceOf('t3lib_object_tests_resolveablecyclic1', $object->o2->o3->o1);
	}

	/**
	 * @test
	 */
	public function singletonWhichRequiresPrototypeViaSetterInjectionWorksAndAddsDebugMessage() {
		$this->container->expects($this->once())->method('log')->with('The singleton "t3lib_object_singletonNeedsPrototype" needs a prototype in "injectDependency". This is often a bad code smell; often you rather want to inject a singleton.', 1);

		$object = $this->container->getInstance('t3lib_object_singletonNeedsPrototype');
		$this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
	}

	/**
	 * @test
	 */
	public function singletonWhichRequiresSingletonViaSetterInjectionWorks() {
		$this->container->expects($this->never())->method('log');

		$object = $this->container->getInstance('t3lib_object_singletonNeedsSingleton');
		$this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
	}

	/**
	 * @test
	 */
	public function prototypeWhichRequiresPrototypeViaSetterInjectionWorks() {
		$this->container->expects($this->never())->method('log');

		$object = $this->container->getInstance('t3lib_object_prototypeNeedsPrototype');
		$this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
	}

	/**
	 * @test
	 */
	public function prototypeWhichRequiresSingletonViaSetterInjectionWorks() {
		$this->container->expects($this->never())->method('log');

		$object = $this->container->getInstance('t3lib_object_prototypeNeedsSingleton');
		$this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
	}

	/**
	 * @test
	 */
	public function singletonWhichRequiresPrototypeViaConstructorInjectionWorksAndAddsDebugMessage() {
		$this->container->expects($this->once())->method('log')->with('The singleton "t3lib_object_singletonNeedsPrototypeInConstructor" needs a prototype in the constructor. This is often a bad code smell; often you rather want to inject a singleton.', 1);

		$object = $this->container->getInstance('t3lib_object_singletonNeedsPrototypeInConstructor');
		$this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
	}

	/**
	 * @test
	 */
	public function singletonWhichRequiresSingletonViaConstructorInjectionWorks() {
		$this->container->expects($this->never())->method('log');

		$object = $this->container->getInstance('t3lib_object_singletonNeedsSingletonInConstructor');
		$this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
	}

	/**
	 * @test
	 */
	public function prototypeWhichRequiresPrototypeViaConstructorInjectionWorks() {
		$this->container->expects($this->never())->method('log');

		$object = $this->container->getInstance('t3lib_object_prototypeNeedsPrototypeInConstructor');
		$this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
	}

	/**
	 * @test
	 */
	public function prototypeWhichRequiresSingletonViaConstructorInjectionWorks() {
		$this->container->expects($this->never())->method('log');

		$object = $this->container->getInstance('t3lib_object_prototypeNeedsSingletonInConstructor');
		$this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
	}
}
?>