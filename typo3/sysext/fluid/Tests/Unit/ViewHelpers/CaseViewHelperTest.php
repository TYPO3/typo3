<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for CaseViewHelper
 */
class CaseViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\CaseViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getMock('TYPO3\CMS\Fluid\ViewHelpers\CaseViewHelper', array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	public function renderThrowsExceptionIfSwitchExpressionIsNotSetInViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(FALSE));
		$this->viewHelper->render('foo');
	}

	/**
	 * @test
	 */
	public function renderReturnsChildNodesIfTheSpecifiedValueIsEqualToTheSwitchExpression() {
		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue('someValue'));

		$renderedChildNodes = 'ChildNodes';
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue($renderedChildNodes));

		$this->assertSame($renderedChildNodes, $this->viewHelper->render('someValue'));
	}

	/**
	 * @test
	 */
	public function renderSetsBreakStateInViewHelperVariableContainerIfTheSpecifiedValueIsEqualToTheSwitchExpression() {
		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue('someValue'));

		$this->viewHelperVariableContainer->expects($this->once())->method('addOrUpdate')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'break', TRUE);

		$this->viewHelper->render('someValue');
	}

	/**
	 * @test
	 */
	public function renderWeaklyComparesSpecifiedValueWithSwitchExpression() {
		$numericValue = 123;
		$stringValue = '123';

		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue($numericValue));

		$this->viewHelperVariableContainer->expects($this->once())->method('addOrUpdate')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'break', TRUE);

		$this->viewHelper->render($stringValue);
	}


	/**
	 * @test
	 */
	public function renderReturnsAnEmptyStringIfTheSpecifiedValueIsNotEqualToTheSwitchExpression() {
		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue('someValue'));
		$this->assertSame('', $this->viewHelper->render('someOtherValue'));
	}

	/**
	 * @test
	 */
	public function renderReturnsChildNodesIfDefaultIsTrue() {
		$this->viewHelperVariableContainer->expects(
			$this->atLeastOnce())
			->method('exists')
			->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')
			->will($this->returnValue(TRUE)
			);
		$this->viewHelperVariableContainer->expects(
			$this->atLeastOnce())->method('get')
			->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')
			->will($this->returnValue(NULL)
			);

		$renderedChildNodes = 'ChildNodes';
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue($renderedChildNodes));

		$this->assertSame($renderedChildNodes, $this->viewHelper->render(NULL, TRUE));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	public function renderThrowsExceptionIfNeighterValueNorDefaultAreGiven() {
		$this->viewHelperVariableContainer->expects(
			$this->atLeastOnce())
			->method('exists')
			->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')
			->will($this->returnValue(TRUE)
			);
		$this->viewHelperVariableContainer->expects($this->never())->method('get');

		$this->viewHelper->render(NULL, FALSE);
	}

	/**
	 * @test
	 */
	public function renderReturnsChildNodesEvenIfValueIsFalseButDefaultIsTrue() {
		$this->viewHelperVariableContainer->expects(
			$this->atLeastOnce())
			->method('exists')
			->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')
			->will($this->returnValue(TRUE)
			);
		$this->viewHelperVariableContainer->expects(
			$this->atLeastOnce())->method('get')
			->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')
			->will($this->returnValue('someValue')
			);

		$renderedChildNodes = 'ChildNodes';
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue($renderedChildNodes));

		$this->assertSame($renderedChildNodes, $this->viewHelper->render('someOtherValue', TRUE));
	}

}
