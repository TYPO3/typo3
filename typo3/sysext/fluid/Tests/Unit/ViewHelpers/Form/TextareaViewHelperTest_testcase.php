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
 * Test for the "Textarea" Form view helper
 *
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Fluid_ViewHelpers_Form_TextareaViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Form_TextareaViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_TextareaViewHelper'), array('setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCorrectlySetsTagName() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName'), array(), '', FALSE);
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('textarea');
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCorrectlySetsNameAttributeAndContent() {
		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('addAttribute', 'setContent', 'render'), array(), '', FALSE);
		$mockTagBuilder->expects($this->once())->method('addAttribute')->with('name', 'NameOfTextarea');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextarea');
		$mockTagBuilder->expects($this->once())->method('setContent')->with('Current value');
		$mockTagBuilder->expects($this->once())->method('render');
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$arguments = new Tx_Fluid_Core_ViewHelper_Arguments(array(
			'name' => 'NameOfTextarea',
			'value' => 'Current value'
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
