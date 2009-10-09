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

require_once(dirname(__FILE__) . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Submit" Form view helper
 *
 * @version $Id: SubmitViewHelperTest.php 2914 2009-07-28 18:26:38Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_Form_SubmitViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Form_ubmitViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = new Tx_Fluid_ViewHelpers_Form_SubmitViewHelper();
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCorrectlySetsTagNameAndDefaultAttributes() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute'));
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('input');
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'submit');

		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}
}

?>