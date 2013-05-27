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

require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for RenderViewHelper
 *
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_RenderViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_RenderViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->templateVariableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer();
		$this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);
		$this->viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_RenderViewHelper', array('dummy'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
	}

	/**
	 * @test
	 */
	public function loadSettingsIntoArgumentsSetsSettingsIfNoSettingsAreSpecified() {
		$arguments = array(
			'someArgument' => 'someValue'
		);
		$expected = array(
			'someArgument' => 'someValue',
			'settings' => 'theSettings'
		);
		$this->templateVariableContainer->add('settings', 'theSettings');

		$actual = $this->viewHelper->_call('loadSettingsIntoArguments', $arguments);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function loadSettingsIntoArgumentsDoesNotOverrideGivenSettings() {
		$arguments = array(
			'someArgument' => 'someValue',
			'settings' => 'specifiedSettings'
		);
		$expected = array(
			'someArgument' => 'someValue',
			'settings' => 'specifiedSettings'
		);
		$this->templateVariableContainer->add('settings', 'theSettings');

		$actual = $this->viewHelper->_call('loadSettingsIntoArguments', $arguments);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function loadSettingsIntoArgumentsDoesNotThrowExceptionIfSettingsAreNotInTemplateVariableContainer() {
		$arguments = array(
			'someArgument' => 'someValue'
		);
		$expected = array(
			'someArgument' => 'someValue'
		);

		$actual = $this->viewHelper->_call('loadSettingsIntoArguments', $arguments);
		$this->assertEquals($expected, $actual);
	}


}

?>