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

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for AliasViewHelper
 */
class AliasViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @test
	 */
	public function renderAddsSingleValueToTemplateVariableContainerAndRemovesItAfterRendering() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\AliasViewHelper();

		$mockViewHelperNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode', array('evaluateChildNodes'), array(), '', FALSE);
		$mockViewHelperNode->expects($this->once())->method('evaluateChildNodes')->will($this->returnValue('foo'));

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('someAlias', 'someValue');
		$this->templateVariableContainer->expects($this->at(1))->method('remove')->with('someAlias');

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($mockViewHelperNode);
		$viewHelper->render(array('someAlias' => 'someValue'));
	}

	/**
	 * @test
	 */
	public function renderAddsMultipleValuesToTemplateVariableContainerAndRemovesThemAfterRendering() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\AliasViewHelper();

		$mockViewHelperNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode', array('evaluateChildNodes'), array(), '', FALSE);
		$mockViewHelperNode->expects($this->once())->method('evaluateChildNodes')->will($this->returnValue('foo'));

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('someAlias', 'someValue');
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('someOtherAlias', 'someOtherValue');
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('someAlias');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('someOtherAlias');

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($mockViewHelperNode);
		$viewHelper->render(array('someAlias' => 'someValue', 'someOtherAlias' => 'someOtherValue'));
	}

	/**
	 * @test
	 */
	public function renderDoesNotTouchTemplateVariableContainerAndReturnsChildNodesIfMapIsEmpty() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\AliasViewHelper();

		$mockViewHelperNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode', array('evaluateChildNodes'), array(), '', FALSE);
		$mockViewHelperNode->expects($this->once())->method('evaluateChildNodes')->will($this->returnValue('foo'));

		$this->templateVariableContainer->expects($this->never())->method('add');
		$this->templateVariableContainer->expects($this->never())->method('remove');

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setViewHelperNode($mockViewHelperNode);

		$this->assertEquals('foo', $viewHelper->render(array()));
	}
}

?>