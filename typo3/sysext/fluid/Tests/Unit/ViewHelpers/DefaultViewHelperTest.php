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

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for DefaultViewHelper
 */
class DefaultViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\CaseViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getMock('TYPO3\CMS\Fluid\ViewHelpers\DefaultViewHelper', array('renderChildren'));
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
	public function renderReturnsChildNodesIfSwitchExpressionIsSetInViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(TRUE));

		$renderedChildNodes = 'ChildNodes';
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue($renderedChildNodes));

		$this->assertSame($renderedChildNodes, $this->viewHelper->render('someValue'));
	}

}

?>