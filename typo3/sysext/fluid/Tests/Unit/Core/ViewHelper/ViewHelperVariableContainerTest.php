<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\ViewHelper;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/../Fixtures/TestViewHelper.php');

/**
 * Testcase for AbstractViewHelper
 */
class ViewHelperVariableContainerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer
	 */
	protected $viewHelperVariableContainer;

	protected function setUp() {
		$this->viewHelperVariableContainer = new \TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer();
	}

	/**
	 * @test
	 */
	public function storedDataCanBeReadOutAgain() {
		$variable = 'Hello world';
		$this->assertFalse($this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\ViewHelpers\\TestViewHelper', 'test'));
		$this->viewHelperVariableContainer->add('TYPO3\\CMS\\Fluid\\ViewHelpers\\TestViewHelper', 'test', $variable);
		$this->assertTrue($this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\ViewHelpers\\TestViewHelper', 'test'));

		$this->assertEquals($variable, $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\TestViewHelper', 'test'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
	 */
	public function gettingNonNonExistentValueThrowsException() {
		$this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
	 */
	public function settingKeyWhichIsAlreadyStoredThrowsException() {
		$this->viewHelperVariableContainer->add('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->add('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey', 'value2');
	}

	/**
	 * @test
	 */
	public function addOrUpdateWorks() {
		$this->viewHelperVariableContainer->add('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->addOrUpdate('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey', 'value2');
		$this->assertEquals($this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey'), 'value2');
	}

	/**
	 * @test
	 */
	public function aSetValueCanBeRemovedAgain() {
		$this->viewHelperVariableContainer->add('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey', 'value1');
		$this->viewHelperVariableContainer->remove('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey');
		$this->assertFalse($this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
	 */
	public function removingNonExistentKeyThrowsException() {
		$this->viewHelperVariableContainer->remove('TYPO3\\CMS\\Fluid\\ViewHelper\\NonExistent', 'nonExistentKey');
	}

	/**
	 * @test
	 */
	public function viewCanBeReadOutAgain() {
		$view = $this->getMock('TYPO3\\CMS\\Fluid\\View\\AbstractTemplateView', array('getTemplateSource', 'getLayoutSource', 'getPartialSource', 'hasTemplate', 'canRender', 'getTemplateIdentifier', 'getLayoutIdentifier', 'getPartialIdentifier'));
		$this->viewHelperVariableContainer->setView($view);
		$this->assertSame($view, $this->viewHelperVariableContainer->getView());
	}
}

?>