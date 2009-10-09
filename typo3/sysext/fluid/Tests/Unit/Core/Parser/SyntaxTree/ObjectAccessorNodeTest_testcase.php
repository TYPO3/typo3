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

require_once(dirname(__FILE__) . '/../Fixtures/SomeEmptyClass.php');

/**
 * Testcase for ObjectAccessor
 *
 * @version $Id: ObjectAccessorNodeTest.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNodeTest_testcase extends Tx_Extbase_Base_testcase {

	protected $mockTemplateVariableContainer;

	protected $renderingContext;

	protected $renderingConfiguration;

	public function setUp() {
		$this->mockTemplateVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer');
		$this->renderingContext = new Tx_Fluid_Core_Rendering_RenderingContext();
		$this->renderingContext->setTemplateVariableContainer($this->mockTemplateVariableContainer);
		$this->renderingConfiguration = $this->getMock('Tx_Fluid_Core_Rendering_RenderingConfiguration');
		$this->renderingContext->setRenderingConfiguration($this->renderingConfiguration);
	}
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function objectAccessorWorksWithStrings() {
		$objectAccessorNode = new Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode('exampleObject');
		$objectAccessorNode->setRenderingContext($this->renderingContext);

		$this->mockTemplateVariableContainer->expects($this->at(0))->method('get')->with('exampleObject')->will($this->returnValue('ExpectedString'));

		$actualResult = $objectAccessorNode->evaluate();
		$this->assertEquals('ExpectedString', $actualResult, 'ObjectAccessorNode did not work for string input.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function objectAccessorWorksWithNestedObjects() {
		$exampleObject = new Tx_Fluid_Core_Parser_Fixtures_SomeEmptyClass('Foo');

		$objectAccessorNode = new Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode('exampleObject.subproperty');
		$objectAccessorNode->setRenderingContext($this->renderingContext);

		$this->mockTemplateVariableContainer->expects($this->at(0))->method('get')->with('exampleObject')->will($this->returnValue($exampleObject));

		$actualResult = $objectAccessorNode->evaluate();
		$this->assertEquals('Foo', $actualResult, 'ObjectAccessorNode did not work for calling getters.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function objectAccessorWorksWithDirectProperties() {
		$expectedResult = 'This is a test';
		$exampleObject = new Tx_Fluid_Core_Parser_Fixtures_SomeEmptyClass('');
		$exampleObject->publicVariable = $expectedResult;

		$objectAccessorNode = new Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode('exampleObject.publicVariable');
		$objectAccessorNode->setRenderingContext($this->renderingContext);

		$this->mockTemplateVariableContainer->expects($this->at(0))->method('get')->with('exampleObject')->will($this->returnValue($exampleObject));

		$actualResult = $objectAccessorNode->evaluate();
		$this->assertEquals($expectedResult, $actualResult, 'ObjectAccessorNode did not work for direct properties.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function objectAccessorWorksOnAssociativeArrays() {
		$expectedResult = 'My value';
		$exampleArray = array('key' => array('key2' => $expectedResult));

		$objectAccessorNode = new Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode('variable.key.key2');
		$objectAccessorNode->setRenderingContext($this->renderingContext);

		$this->mockTemplateVariableContainer->expects($this->at(0))->method('get')->with('variable')->will($this->returnValue($exampleArray));

		$actualResult = $objectAccessorNode->evaluate();
		$this->assertEquals($expectedResult, $actualResult, 'ObjectAccessorNode did not traverse associative arrays.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 */
	public function objectAccessorThrowsExceptionIfKeyInAssociativeArrayDoesNotExist() {
		$this->markTestIncomplete('Objects accessors fail silently so far. We need some context dependencies here.');
		$expected = 'My value';
		$exampleArray = array('key' => array('key2' => $expected));
		$objectAccessorNode = new Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode('variable.key.key3');
		$context = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array('variable' => $exampleArray));

		$actual = $objectAccessorNode->evaluate();
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_RuntimeException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function objectAccessorThrowsErrorIfPropertyDoesNotExist() {
		$this->markTestIncomplete('Objects accessors fail silently so far. We need some context dependencies here.');

		$expected = 'This is a test';
		$exampleObject = new Tx_Fluid_SomeEmptyClass("Hallo");
		$exampleObject->publicVariable = $expected;
		$objectAccessorNode = new Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode("exampleObject.publicVariableNotExisting");
		$context = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array('exampleObject' => $exampleObject));

		$actual = $objectAccessorNode->evaluate($context);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function objectAccessorPostProcessorIsCalled() {
		$objectAccessorNode = new Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode('variable');
		$objectAccessorNode->setRenderingContext($this->renderingContext);

		$this->mockTemplateVariableContainer->expects($this->at(0))->method('get')->with('variable')->will($this->returnValue('hallo'));

		$this->renderingContext->setObjectAccessorPostProcessorEnabled(TRUE);

		$objectAccessorPostProcessor = $this->getMock('Tx_Fluid_Core_Rendering_ObjectAccessorPostProcessorInterface');
		$this->renderingConfiguration->expects($this->once())->method('getObjectAccessorPostProcessor')->will($this->returnValue($objectAccessorPostProcessor));
		$objectAccessorPostProcessor->expects($this->once())->method('process')->with('hallo', TRUE)->will($this->returnValue('PostProcessed'));
		$this->assertEquals('PostProcessed', $objectAccessorNode->evaluate());
	}

}

?>