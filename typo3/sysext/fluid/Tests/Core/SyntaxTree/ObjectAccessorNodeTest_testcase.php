<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage Tests
 * @version $Id$
 */

require_once(dirname(__FILE__) . '/../Fixtures/SomeEmptyClass.php');

/**
 * Testcase for ObjectAccessor
 *
 * @package
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_SyntaxTree_ObjectAccessorNodeTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_objectAccessorWorksWithStrings() {
		$mockVariableContainer = $this->getMock('Tx_Fluid_Core_VariableContainer');
		$mockVariableContainer->expects($this->at(0))->method('get')->with('exampleObject')->will($this->returnValue('ExpectedString'));

		$objectAccessorNode = new Tx_Fluid_Core_SyntaxTree_ObjectAccessorNode('exampleObject');
		$objectAccessorNode->setVariableContainer($mockVariableContainer);

		$actualResult = $objectAccessorNode->evaluate();
		$this->assertEquals('ExpectedString', $actualResult, 'ObjectAccessorNode did not work for string input.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_objectAccessorWorksWithNestedObjects() {
		$exampleObject = new Tx_Fluid_SomeEmptyClass('Foo');

		$mockVariableContainer = $this->getMock('Tx_Fluid_Core_VariableContainer');
		$mockVariableContainer->expects($this->at(0))->method('get')->with('exampleObject')->will($this->returnValue($exampleObject));

		$objectAccessorNode = new Tx_Fluid_Core_SyntaxTree_ObjectAccessorNode('exampleObject.subproperty');
		$objectAccessorNode->setVariableContainer($mockVariableContainer);

		$actualResult = $objectAccessorNode->evaluate();
		$this->assertEquals('Foo', $actualResult, 'ObjectAccessorNode did not work for calling getters.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_objectAccessorWorksWithDirectProperties() {
		$expectedResult = 'This is a test';
		$exampleObject = new Tx_Fluid_SomeEmptyClass('');
		$exampleObject->publicVariable = $expectedResult;

		$mockVariableContainer = $this->getMock('Tx_Fluid_Core_VariableContainer');
		$mockVariableContainer->expects($this->at(0))->method('get')->with('exampleObject')->will($this->returnValue($exampleObject));

		$objectAccessorNode = new Tx_Fluid_Core_SyntaxTree_ObjectAccessorNode('exampleObject.publicVariable');
		$objectAccessorNode->setVariableContainer($mockVariableContainer);

		$actualResult = $objectAccessorNode->evaluate();
		$this->assertEquals($expectedResult, $actualResult, 'ObjectAccessorNode did not work for direct properties.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_objectAccessorWorksOnAssociativeArrays() {
		$expectedResult = 'My value';
		$exampleArray = array('key' => array('key2' => $expectedResult));

		$mockVariableContainer = $this->getMock('Tx_Fluid_Core_VariableContainer');
		$mockVariableContainer->expects($this->at(0))->method('get')->with('variable')->will($this->returnValue($exampleArray));

		$objectAccessorNode = new Tx_Fluid_Core_SyntaxTree_ObjectAccessorNode('variable.key.key2');
		$objectAccessorNode->setVariableContainer($mockVariableContainer);

		$actualResult = $objectAccessorNode->evaluate();
		$this->assertEquals($expectedResult, $actualResult, 'ObjectAccessorNode did not traverse associative arrays.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 */
	public function test_objectAccessorThrowsExceptionIfKeyInAssociativeArrayDoesNotExist() {
		$this->markTestIncomplete('Objects accessors fail silently so far. We need some context dependencies here.');
		$expected = 'My value';
		$exampleArray = array('key' => array('key2' => $expected));
		$objectAccessorNode = new Tx_Fluid_Core_SyntaxTree_ObjectAccessorNode('variable.key.key3');
		$context = new Tx_Fluid_Core_VariableContainer(array('variable' => $exampleArray));

		$actual = $objectAccessorNode->evaluate();
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_objectAccessorThrowsErrorIfPropertyDoesNotExist() {
		$this->markTestIncomplete('Objects accessors fail silently so far. We need some context dependencies here.');

		$expected = 'This is a test';
		$exampleObject = new Tx_Fluid_SomeEmptyClass("Hallo");
		$exampleObject->publicVariable = $expected;
		$objectAccessorNode = new Tx_Fluid_Core_SyntaxTree_ObjectAccessorNode("exampleObject.publicVariableNotExisting");
		$context = new Tx_Fluid_Core_VariableContainer(array('exampleObject' => $exampleObject));

		$actual = $objectAccessorNode->evaluate($context);
	}


}



?>
