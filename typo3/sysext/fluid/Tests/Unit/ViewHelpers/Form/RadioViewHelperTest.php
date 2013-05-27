<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Radio" Form view helper
 */
class RadioViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\RadioViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\RadioViewHelper', array('setErrorClassAttribute', 'getName', 'getValue', 'isObjectAccessorMode', 'getPropertyValue', 'registerFieldNameForFormTokenGeneration'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 */
	public function renderCorrectlySetsTagNameAndDefaultAttributes() {
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('input');
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'radio');
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
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'radio');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
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
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'radio');
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
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'radio');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
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
	public function renderDoesNotAppendSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray() {
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'radio');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
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
	public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeString() {
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'radio');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
		$mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
		$mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

		$this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$this->viewHelper->expects($this->any())->method('getValue')->will($this->returnValue('bar'));
		$this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(TRUE));
		$this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue('bar'));
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