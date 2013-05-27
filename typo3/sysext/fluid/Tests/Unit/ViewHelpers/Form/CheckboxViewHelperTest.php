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

require_once(dirname(__FILE__) . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Checkbox" Form view helper
 *
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Form_CheckboxViewHelperTest extends Tx_Fluid_Tests_Unit_ViewHelpers_Form_FormFieldViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Form_CheckboxViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_Form_CheckboxViewHelper', array('setErrorClassAttribute', 'getName', 'getValue', 'isObjectAccessorMode', 'getPropertyValue', 'registerFieldNameForFormTokenGeneration'));
		$this->arguments['property'] = '';
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 */
	public function renderCorrectlySetsTagNameAndDefaultAttributes() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('input');
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
		$mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

		$this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$this->viewHelper->expects($this->any())->method('getValue')->will($this->returnValue('bar'));
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function renderSetsCheckedAttributeIfSpecified() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
		$mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
		$mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

		$this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$this->viewHelper->expects($this->any())->method('getValue')->will($this->returnValue('bar'));
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
		$this->viewHelper->render(TRUE);
	}

	/**
	 * @test
	 */
	public function renderIgnoresBoundPropertyIfCheckedIsSet() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
		$mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

		$this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$this->viewHelper->expects($this->any())->method('getValue')->will($this->returnValue('bar'));
		$this->viewHelper->expects($this->never())->method('isObjectAccessorMode')->will($this->returnValue(TRUE));
		$this->viewHelper->expects($this->never())->method('getPropertyValue')->will($this->returnValue(TRUE));
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
		$this->viewHelper->render(TRUE);
		$this->viewHelper->render(FALSE);
	}

	/**
	 * @test
	 */
	public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
		$mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
		$mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

		$this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$this->viewHelper->expects($this->any())->method('getValue')->will($this->returnValue('bar'));
		$this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(TRUE));
		$this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(TRUE));
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function renderAppendsSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo[]');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo[]');
		$mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

		$this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$this->viewHelper->expects($this->any())->method('getValue')->will($this->returnValue('bar'));
		$this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(TRUE));
		$this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(array()));
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArray() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo[]');
		$mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
		$mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

		$this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$this->viewHelper->expects($this->any())->method('getValue')->will($this->returnValue('bar'));
		$this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(TRUE));
		$this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(array('foo', 'bar', 'baz')));
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 */
	public function bindingObjectsToACheckboxThatAreNotOfTypeBooleanOrArrayThrowsException() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute'));

		$this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(TRUE));
		$this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(new stdClass()));
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function renderCallsSetErrorClassAttribute() {
		$this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
		$this->viewHelper->render();
	}
}

?>