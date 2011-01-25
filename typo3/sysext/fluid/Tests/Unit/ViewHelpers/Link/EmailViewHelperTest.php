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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Link_EmailViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Link_EmailViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var tslib_cObj
	 */
	protected $cObjBackup;

	public function setUp() {
		parent::setUp();

		$this->cObjBackup = $GLOBALS['TSFE']->cObj;
		$GLOBALS['TSFE']->cObj = $this->getMock('tslib_cObj', array(), array(), '', FALSE);

		$this->viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Link_EmailViewHelper'), array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	public function tearDown() {
		$GLOBALS['TSFE']->cObj = $this->cObjBackup;
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCorrectlySetsTagNameAndAttributesAndContent() {
		//$GLOBALS['TSFE']->cObj->expects($this->once())->method('getMailTo')->with('some@email.tld', 'some@email.tld')->will($this->returnValue(array('mailto:some@email.tld', 'some@email.tld')));

		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('a');
		$mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'mailto:some@email.tld');
		$mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
		$this->viewHelper->_set('tag', $mockTagBuilder);

		$this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('some content'));

		$this->viewHelper->initialize();
		$this->viewHelper->render('some@email.tld');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderSetsTagContentToEmailIfRenderChildrenReturnNull() {
		//$GLOBALS['TSFE']->cObj->expects($this->once())->method('getMailTo')->with('some@email.tld', 'some@email.tld')->will($this->returnValue(array('mailto:some@email.tld', 'some@email.tld')));

		$mockTagBuilder = $this->getMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
		$mockTagBuilder->expects($this->once())->method('setContent')->with('some@email.tld');
		$this->viewHelper->_set('tag', $mockTagBuilder);

		$this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue(NULL));

		$this->viewHelper->initialize();
		$this->viewHelper->render('some@email.tld');
	}
}

?>