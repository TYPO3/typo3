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

require_once(dirname(__FILE__) . '/../ViewHelperBaseTestcase.php');

/**
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Link_ExternalViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Link_EmailViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_Link_ExternalViewHelper', array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 */
	public function renderCorrectlySetsTagNameAndAttributesAndContent() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('a');
		$mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'http://www.some-domain.tld');
		$mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
		$this->viewHelper->_set('tag', $mockTagBuilder);

		$this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('some content'));

		$this->viewHelper->initialize();
		$this->viewHelper->render('http://www.some-domain.tld');
	}

	/**
	 * @test
	 */
	public function renderAddsHttpPrefixIfSpecifiedUriDoesNotContainScheme() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('a');
		$mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'http://www.some-domain.tld');
		$mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
		$this->viewHelper->_set('tag', $mockTagBuilder);

		$this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('some content'));

		$this->viewHelper->initialize();
		$this->viewHelper->render('www.some-domain.tld');
	}

	/**
	 * @test
	 */
	public function renderAddsSpecifiedSchemeIfUriDoesNotContainScheme() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('a');
		$mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'ftp://some-domain.tld');
		$mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
		$this->viewHelper->_set('tag', $mockTagBuilder);

		$this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('some content'));

		$this->viewHelper->initialize();
		$this->viewHelper->render('some-domain.tld', 'ftp');
	}

	/**
	 * @test
	 */
	public function renderDoesNotAddEmptyScheme() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('a');
		$mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'some-domain.tld');
		$mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
		$this->viewHelper->_set('tag', $mockTagBuilder);

		$this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('some content'));

		$this->viewHelper->initialize();
		$this->viewHelper->render('some-domain.tld', '');
	}
}

?>