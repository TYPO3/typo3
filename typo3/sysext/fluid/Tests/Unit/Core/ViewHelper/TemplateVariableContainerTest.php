<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for TemplateVariableContainer
 *
 */
class Tx_Fluid_Tests_Unit_Core_ViewHelper_TemplateVariableContainerTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 */
	public function setUp() {
		$this->variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer();
	}

	/**
	 */
	public function tearDown() {
		unset($this->variableContainer);
	}

	/**
	 * @test
	 */
	public function addedObjectsCanBeRetrievedAgain() {
		$object = "StringObject";
		$this->variableContainer->add("variable", $object);
		$this->assertSame($this->variableContainer->get('variable'), $object, 'The retrieved object from the context is not the same as the stored object.');
	}

	/**
	 * @test
	 */
	public function addedObjectsCanBeRetrievedAgainUsingArrayAccess() {
		$object = "StringObject";
		$this->variableContainer['variable'] = $object;
		$this->assertSame($this->variableContainer->get('variable'), $object);
		$this->assertSame($this->variableContainer['variable'], $object);
	}

	/**
	 * @test
	 */
	public function addedObjectsExistInArray() {
		$object = "StringObject";
		$this->variableContainer->add("variable", $object);
		$this->assertTrue($this->variableContainer->exists('variable'));
		$this->assertTrue(isset($this->variableContainer['variable']));
	}

	/**
	 * @test
	 */
	public function addedObjectsExistInAllIdentifiers() {
		$object = "StringObject";
		$this->variableContainer->add("variable", $object);
		$this->assertEquals($this->variableContainer->getAllIdentifiers(), array('variable'), 'Added key is not visible in getAllIdentifiers');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function duplicateIdentifiersThrowException() {
		$this->variableContainer->add('variable', 'string1');
		$this->variableContainer['variable'] = 'string2';
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function addingReservedIdentifiersThrowException() {
		$this->variableContainer->add('TrUe', 'someValue');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function gettingNonexistentValueThrowsException() {
		$this->variableContainer->get('nonexistent');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function deletingNonexistentValueThrowsException() {
		$this->variableContainer->remove('nonexistent');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function removeReallyRemovesVariables() {
		$this->variableContainer->add('variable', 'string1');
		$this->variableContainer->remove('variable');
		$this->variableContainer->get('variable');
	}

	/**
	 * @test
	 */
	public function whenVariablesAreEmpty_getAll_shouldReturnEmptyArray() {
		$this->assertSame(array(), $this->variableContainer->get('_all'));
	}

	/**
	 * @test
	 */
	public function getAllShouldReturnAllVariables() {
		$this->variableContainer->add('name', 'Simon');
		$this->assertSame(array('name' => 'Simon'), $this->variableContainer->get('_all'));
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception_InvalidVariableException
	 */
	public function addingVariableNamedAllShouldThrowException() {
		$this->variableContainer->add('_all', 'foo');
	}
}

?>