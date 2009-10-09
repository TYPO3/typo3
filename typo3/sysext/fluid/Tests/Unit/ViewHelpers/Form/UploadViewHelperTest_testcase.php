<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

include_once(dirname(__FILE__) . '/Fixtures/EmptySyntaxTreeNode.php');
include_once(dirname(__FILE__) . '/Fixtures/Fixture_UserDomainClass.php');
require_once(dirname(__FILE__) . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Upload" Form view helper
 *
 * @version $Id: UploadViewHelperTest.php 2914 2009-07-28 18:26:38Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_Form_UploadViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Form_UploadViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_UploadViewHelper'), array('setErrorClassAttribute'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCorrectlySetsTagName() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName'), array(), '', FALSE);
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('input');
		$this->viewHelper->injectTagBuilder($mockTagBuilder);
		$this->viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array()));

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCorrectlySetsTypeNameAndValueAttributes() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('addAttribute', 'setContent', 'render'), array(), '', FALSE);
		$mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('type', 'file');
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('name', 'someName');
		$mockTagBuilder->expects($this->once())->method('render');
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$arguments = new Tx_Fluid_Core_ViewHelper_Arguments(array(
			'name' => 'someName',
		));

		$this->viewHelper->setArguments($arguments);
		$this->viewHelper->setViewHelperNode(new Tx_Fluid_ViewHelpers_Fixtures_EmptySyntaxTreeNode());
		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCallsSetErrorClassAttribute() {
		$this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
		$this->viewHelper->render();
	}
}

?>