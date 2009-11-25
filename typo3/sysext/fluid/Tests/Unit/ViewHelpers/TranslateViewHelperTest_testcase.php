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

/**
 * @package Fluid
 * @subpackage Tests
 * @version $Id: TranslateViewHelperTest_testcase.php 1734 2009-11-25 21:53:57Z stucki $
 */
/**
 * Testcase for TranslateViewHelper
 *
 * @package Fluid
 * @subpackage Tests
 * @version $Id: TranslateViewHelperTest_testcase.php 1734 2009-11-25 21:53:57Z stucki $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once('ViewHelperBaseTestcase.php');
class Tx_Fluid_ViewHelpers_TranslateViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @var tslib_fe
	 */
	protected $tsfeBackup;

	/**
	 * @var language
	 */
	protected $langBackup;

	public function setUp() {
		parent::setUp();
		$this->tsfeBackup = $GLOBALS['TSFE'];
		$this->langBackup = $GLOBALS['LANG'];
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array(), array(), '', FALSE);
		$GLOBALS['LANG'] = $this->getMock('language', array(), array(), '', FALSE);
	}

	public function tearDown() {
		$GLOBALS['TSFE'] = $this->tsfeBackup;
		$GLOBALS['LANG'] = $this->langBackup;
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAcceptsLllFileReference() {
		$GLOBALS['LANG']->expects($this->once())->method('sL')->with('LLL:someExtension/locallang.xml')->will($this->returnValue('some translation'));

		$viewHelper = new Tx_Fluid_ViewHelpers_TranslateViewHelper();
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->request->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('fluid'));

		$actualResult = $viewHelper->render('LLL:someExtension/locallang.xml');
		$this->assertEquals('some translation', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderHtmlEscapesLllTranslationsByDefault() {
		$GLOBALS['LANG']->expects($this->once())->method('sL')->with('LLL:someExtension/locallang.xml')->will($this->returnValue('some translation with <strong>HTML tags</strong> and special chäracterß.'));

		$viewHelper = new Tx_Fluid_ViewHelpers_TranslateViewHelper();
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->request->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('fluid'));

		$actualResult = $viewHelper->render('LLL:someExtension/locallang.xml');
		$this->assertEquals('some translation with &lt;strong&gt;HTML tags&lt;/strong&gt; and special chäracterß.', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function htmlEscapingCanBeDisabledForLllTranslations() {
		$GLOBALS['LANG']->expects($this->once())->method('sL')->with('LLL:someExtension/locallang.xml')->will($this->returnValue('some translation with <strong>HTML tags</strong> and special chäracterß.'));

		$viewHelper = new Tx_Fluid_ViewHelpers_TranslateViewHelper();
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->request->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('fluid'));

		$actualResult = $viewHelper->render('LLL:someExtension/locallang.xml', FALSE);
		$this->assertEquals('some translation with <strong>HTML tags</strong> and special chäracterß.', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function defaultValuesAreNotHtmlEscaped() {
		$this->markTestIncomplete("Error - needs to be fixed");
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_TranslateViewHelper', array('translate', 'renderChildren'));
		$viewHelper->expects($this->once())->method('translate')->with('nonexistingKey')->will($this->returnValue(NULL));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some translation with <strong>HTML tags</strong> and special chäracterß.'));

		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->request->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('fluid'));


		$actualResult = $viewHelper->render('nonexistingKey');
		$this->assertEquals('some translation with <strong>HTML tags</strong> and special chäracterß.', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function stillNeedsALotMoreTests() {
		$this->markTestIncomplete('This still needs a lot more tests');
	}
}

?>
