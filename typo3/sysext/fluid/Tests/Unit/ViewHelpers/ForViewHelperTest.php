<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

include_once(dirname(__FILE__) . '/Fixtures/ConstraintSyntaxTreeNode.php');
require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for ForViewHelper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_ForViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderExecutesTheLoopCorrectly() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$viewHelper->render(array(0,1,2,3), 'innerVariable');

		$expectedCallProtocol = array(
			array('innerVariable' => 0),
			array('innerVariable' => 1),
			array('innerVariable' => 2),
			array('innerVariable' => 3)
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderPreservesKeys() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$viewHelper->render(array('key1' => 'value1', 'key2' => 'value2'), 'innerVariable', 'someKey');

		$expectedCallProtocol = array(
			array(
				'innerVariable' => 'value1',
				'someKey' => 'key1'
			),
			array(
				'innerVariable' => 'value2',
				'someKey' => 'key2'
			)
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringIfObjectIsNull() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$this->assertEquals('', $viewHelper->render(NULL, 'foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringIfObjectIsEmptyArray() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$this->assertEquals('', $viewHelper->render(array(), 'foo'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAddsCurrentValueToTemplateVariableContainerAndRemovesItAfterRendering() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$mockViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('evaluateChildNodes'), array(), '', FALSE);
		$mockViewHelperNode->expects($this->any())->method('evaluateChildNodes')->will($this->returnValue('foo'));

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
		$this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'Fluid');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');

		$viewHelper->setTemplateVariableContainer($this->templateVariableContainer);
		$viewHelper->setViewHelperNode($mockViewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$viewHelper->render(array('foo' => 'bar', 'FLOW3' => 'Fluid'), 'innerVariable');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAddsCurrentKeyToTemplateVariableContainerAndRemovesItAfterRendering() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$mockViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('evaluateChildNodes'), array(), '', FALSE);
		$mockViewHelperNode->expects($this->any())->method('evaluateChildNodes')->will($this->returnValue('foo'));

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('someKey', 'foo');
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('someKey');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'Fluid');
		$this->templateVariableContainer->expects($this->at(5))->method('add')->with('someKey', 'FLOW3');
		$this->templateVariableContainer->expects($this->at(6))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(7))->method('remove')->with('someKey');

		$viewHelper->setTemplateVariableContainer($this->templateVariableContainer);
		$viewHelper->setRenderingContext($this->renderingContext);
		$viewHelper->setViewHelperNode($mockViewHelperNode);
		$viewHelper->render(array('foo' => 'bar', 'FLOW3' => 'Fluid'), 'innerVariable', 'someKey');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderIteratesElementsInReverseOrderIfReverseIsTrue() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$viewHelper->render(array(0,1,2,3), 'innerVariable', '', TRUE);

		$expectedCallProtocol = array(
			array('innerVariable' => 3),
			array('innerVariable' => 2),
			array('innerVariable' => 1),
			array('innerVariable' => 0)
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderPreservesKeysIfReverseIsTrue() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$viewHelper->render(array('key1' => 'value1', 'key2' => 'value2'), 'innerVariable', 'someKey', TRUE);

		$expectedCallProtocol = array(
			array(
				'innerVariable' => 'value2',
				'someKey' => 'key2'
			),
			array(
				'innerVariable' => 'value1',
				'someKey' => 'key1'
			)
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function keyContainsNumericalIndexIfTheGivenArrayDoesNotHaveAKey() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$viewHelper->render(array('foo', 'bar', 'baz'), 'innerVariable', 'someKey');

		$expectedCallProtocol = array(
			array(
				'innerVariable' => 'foo',
				'someKey' => 0
			),
			array(
				'innerVariable' => 'bar',
				'someKey' => 1
			),
			array(
				'innerVariable' => 'baz',
				'someKey' => 2
			)
		);
		$this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function keyContainsNumericalIndexInAscendingOrderEvenIfReverseIsTrueIfTheGivenArrayDoesNotHaveAKey() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$viewHelper->render(array('foo', 'bar', 'baz'), 'innerVariable', 'someKey', TRUE);

		$expectedCallProtocol = array(
			array(
				'innerVariable' => 'baz',
				'someKey' => 0
			),
			array(
				'innerVariable' => 'bar',
				'someKey' => 1
			),
			array(
				'innerVariable' => 'foo',
				'someKey' => 2
			)
		);
		$this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThrowsExceptionWhenPassingObjectsToEachThatAreNotTraversable() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();
		$object = new stdClass();

		$viewHelper->render($object, 'innerVariable', 'someKey');
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderIteratesThroughElementsOfTraversableObjects() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$traversableObject = new ArrayObject(array('key1' => 'value1', 'key2' => 'value2'));
		$viewHelper->render($traversableObject, 'innerVariable');

		$expectedCallProtocol = array(
			array('innerVariable' => 'value1'),
			array('innerVariable' => 'value2'),
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderPreservesKeyWhenIteratingThroughElementsOfObjectsThatImplementIteratorInterface() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$iteratorObject = new ArrayIterator(array('key1' => 'value1', 'key2' => 'value2'));
		$viewHelper->render($iteratorObject, 'innerVariable', 'someKey');

		$expectedCallProtocol = array(
			array(
				'innerVariable' => 'value1',
				'someKey' => 'key1'
			),
			array(
				'innerVariable' => 'value2',
				'someKey' => 'key2'
			)
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function keyContainsTheNumericalIndexWhenIteratingThroughElementsOfObjectsOfTyeSplObjectStorage() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($variableContainer);
		$viewHelper->setTemplateVariableContainer($variableContainer);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->setRenderingContext($this->renderingContext);
		$splObjectStorageObject = new SplObjectStorage();
		$object1 = new stdClass();
		$splObjectStorageObject->attach($object1);
		$object2 = new stdClass();
		$splObjectStorageObject->attach($object2);
		$object3 = new stdClass();
		$splObjectStorageObject->attach($object3);
		$viewHelper->render($splObjectStorageObject, 'innerVariable', 'someKey');

		$expectedCallProtocol = array(
			array(
				'innerVariable' => $object1,
				'someKey' => 0
			),
			array(
				'innerVariable' => $object2,
				'someKey' => 1
			),
			array(
				'innerVariable' => $object3,
				'someKey' => 2
			)
		);
		$this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function iterationDataIsAddedToTemplateVariableContainerIfIterationArgumentIsSet() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$mockViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array('evaluateChildNodes'), array(), '', FALSE);
		$mockViewHelperNode->expects($this->any())->method('evaluateChildNodes')->will($this->returnValue('foo'));

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('iteration', array('index' => 0, 'cycle' => 1, 'total' => 3, 'isFirst' => TRUE, 'isLast' => FALSE, 'isEven' => FALSE, 'isOdd' => TRUE));
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('iteration');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'Fluid');
		$this->templateVariableContainer->expects($this->at(5))->method('add')->with('iteration', array('index' => 1, 'cycle' => 2, 'total' => 3, 'isFirst' => FALSE, 'isLast' => FALSE, 'isEven' => TRUE, 'isOdd' => FALSE));
		$this->templateVariableContainer->expects($this->at(6))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(7))->method('remove')->with('iteration');
		$this->templateVariableContainer->expects($this->at(8))->method('add')->with('innerVariable', 'rocks');
		$this->templateVariableContainer->expects($this->at(9))->method('add')->with('iteration', array('index' => 2, 'cycle' => 3, 'total' => 3, 'isFirst' => FALSE, 'isLast' => TRUE, 'isEven' => FALSE, 'isOdd' => TRUE));
		$this->templateVariableContainer->expects($this->at(10))->method('remove')->with('innerVariable');
		$this->templateVariableContainer->expects($this->at(11))->method('remove')->with('iteration');

		$viewHelper->setTemplateVariableContainer($this->templateVariableContainer);
		$viewHelper->setRenderingContext($this->renderingContext);
		$viewHelper->setViewHelperNode($mockViewHelperNode);
		$viewHelper->render(array('foo' => 'bar', 'FLOW3' => 'Fluid', 'TYPO3' => 'rocks'), 'innerVariable', '', FALSE, 'iteration');
	}
}

?>