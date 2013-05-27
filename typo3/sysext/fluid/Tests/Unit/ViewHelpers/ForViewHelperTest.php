<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

include_once(__DIR__ . '/Fixtures/ConstraintSyntaxTreeNode.php');
require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for ForViewHelper
 */
class ForViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	public function setUp() {
		parent::setUp();
		$this->templateVariableContainer = new \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer(array());
		$this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);

		$this->arguments['reverse'] = NULL;
		$this->arguments['key'] = '';
		$this->arguments['iteration'] = NULL;
	}

	/**
	 * @test
	 */
	public function renderExecutesTheLoopCorrectly() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);
		$this->arguments['each'] = array(0, 1, 2, 3);
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
	 */
	public function renderPreservesKeys() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

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
	 */
	public function renderReturnsEmptyStringIfObjectIsNull() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$this->arguments['each'] = NULL;
		$this->arguments['as'] = 'foo';

		$this->injectDependenciesIntoViewHelper($viewHelper);

		$this->assertEquals('', $viewHelper->render($this->arguments['each'], $this->arguments['as']));
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyStringIfObjectIsEmptyArray() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$this->arguments['each'] = array();
		$this->arguments['as'] = 'foo';

		$this->injectDependenciesIntoViewHelper($viewHelper);

		$this->assertEquals('', $viewHelper->render($this->arguments['each'], $this->arguments['as']));
	}

	/**
	 * @test
	 */
	public function renderIteratesElementsInReverseOrderIfReverseIsTrue() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = array(0, 1, 2, 3);
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
	 */
	public function renderPreservesKeysIfReverseIsTrue() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

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
	 */
	public function keyContainsNumericalIndexIfTheGivenArrayDoesNotHaveAKey() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

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
	 */
	public function keyContainsNumericalIndexInAscendingOrderEvenIfReverseIsTrueIfTheGivenArrayDoesNotHaveAKey() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

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
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	public function renderThrowsExceptionWhenPassingObjectsToEachThatAreNotTraversable() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();
		$object = new \stdClass();

		$this->arguments['each'] = $object;
		$this->arguments['as'] = 'innerVariable';
		$this->arguments['key'] = 'someKey';
		$this->arguments['reverse'] = TRUE;

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);
	}


	/**
	 * @test
	 */
	public function renderIteratesThroughElementsOfTraversableObjects() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = new \ArrayObject(array('key1' => 'value1', 'key2' => 'value2'));
		$this->arguments['as'] = 'innerVariable';

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($viewHelperNode);
		$viewHelper->render($this->arguments['each'], $this->arguments['as']);

		$expectedCallProtocol = array(
			array('innerVariable' => 'value1'),
			array('innerVariable' => 'value2')
		);
		$this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
	}

	/**
	 * @test
	 */
	public function renderPreservesKeyWhenIteratingThroughElementsOfObjectsThatImplementIteratorInterface() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$this->arguments['each'] = new \ArrayIterator(array('key1' => 'value1', 'key2' => 'value2'));
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
	 */
	public function keyContainsTheNumericalIndexWhenIteratingThroughElementsOfObjectsOfTyeSplObjectStorage() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

		$splObjectStorageObject = new \SplObjectStorage();
		$object1 = new \stdClass();
		$splObjectStorageObject->attach($object1);
		$object2 = new \stdClass();
		$splObjectStorageObject->attach($object2, 'foo');
		$object3 = new \stdClass();
		$splObjectStorageObject->attach($object3, 'bar');

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
	 */
	public function iterationDataIsAddedToTemplateVariableContainerIfIterationArgumentIsSet() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

		$viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

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
					'isOdd' => TRUE
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