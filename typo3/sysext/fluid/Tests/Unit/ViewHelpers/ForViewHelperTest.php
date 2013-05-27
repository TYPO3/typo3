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

	public function setUp() {
		parent::setUp();
		$this->templateVariableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());
		$this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);

		$this->arguments['reverse'] = NULL;
		$this->arguments['key'] = '';
		$this->arguments['iteration'] = NULL;
	}
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderExecutesTheLoopCorrectly() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);
		$this->arguments['each'] = array(0,1,2,3);
		$this->arguments['as'] = 'innerVariable';

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as']);

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

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = array('key1' => 'value1', 'key2' => 'value2');
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['key'] = 'someKey';

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

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

		$this->arguments['each'] = NULL;
		$this->arguments['as'] = 'foo';

		$this->injectDependenciesIntoViewHelper($viewHelper);

		$this->assertEquals('', $viewHelper->render($this->arguments['each'], $this->arguments['as']));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringIfObjectIsEmptyArray() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$this->arguments['each'] = array();
		$this->arguments['as'] = 'foo';

		$this->injectDependenciesIntoViewHelper($viewHelper);

		$this->assertEquals('', $viewHelper->render($this->arguments['each'], $this->arguments['as']));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderIteratesElementsInReverseOrderIfReverseIsTrue() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = array(0,1,2,3);
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['reverse'] = TRUE;

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse']);

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

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = array('key1' => 'value1', 'key2' => 'value2');
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['key'] = 'someKey';
		$this->arguments['reverse'] = TRUE;

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse']);

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

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = array('foo', 'bar', 'baz');
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['key'] = 'someKey';

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

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

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = array('foo', 'bar', 'baz');
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['key'] = 'someKey';
		$this->arguments['reverse'] = TRUE;

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse']);

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

		$this->arguments['each'] = $object;
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['key'] = 'someKey';
		$this->arguments['reverse'] = TRUE;

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderIteratesThroughElementsOfTraversableObjects() {
		$viewHelper = new Tx_Fluid_ViewHelpers_ForViewHelper();

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = new ArrayObject(array('key1' => 'value1', 'key2' => 'value2'));
		$this->arguments['as'] = 'innerVariable';

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as']);

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

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = new ArrayIterator(array('key1' => 'value1', 'key2' => 'value2'));
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['key'] = 'someKey';

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

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

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$splObjectStorageObject = new SplObjectStorage();
		$object1 = new stdClass();
		$splObjectStorageObject->attach($object1);
		$object2 = new stdClass();
		$splObjectStorageObject->attach($object2);
		$object3 = new stdClass();
		$splObjectStorageObject->attach($object3);

		$this->arguments['each'] = $splObjectStorageObject;
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['key'] = 'someKey';

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

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

		$viewHelperNode = new Tx_Fluid_ViewHelpers_Fixtures_ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = array('foo' => 'bar', 'FLOW3' => 'Fluid', 'TYPO3' => 'rocks');
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['iteration'] = 'iteration';

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse'], $this->arguments['iteration']);

		$expectedCallProtocol = array(
			array(
				'innerVariable' => 'bar',
				'iteration' => array(
					'index' => 0,
					'cycle' => 1,
					'total' => 3,
					'isFirst' => TRUE,
					'isLast' => FALSE,
					'isEven' => FALSE,
					'isOdd' => TRUE,
				)
			),
			array(
				'innerVariable' => 'Fluid',
				'iteration' => array(
					'index' => 1,
					'cycle' => 2,
					'total' => 3,
					'isFirst' => FALSE,
					'isLast' => FALSE,
					'isEven' => TRUE,
					'isOdd' => FALSE
				)
			),
			array(
				'innerVariable' => 'rocks',
				'iteration' => array(
					'index' => 2,
					'cycle' => 3,
					'total' => 3,
					'isFirst' => FALSE,
					'isLast' => TRUE,
					'isEven' => FALSE,
					'isOdd' => TRUE
				)
			)
		);
		$this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}
}

?>
