<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');
/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: BaseViewHelperTest.php 2523 2009-06-02 10:35:40Z k-fish $
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_BaseViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_renderTakesBaseURIFromControllerContext() {
		$baseURI = 'http://typo3.org/';

		$request = $this->getMock('Tx_Fluid_MVC_Web_Request');
		$request->expects($this->any())->method('getBaseURI')->will($this->returnValue($baseURI));

		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($request));

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_BaseViewHelper'), array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$expected = '<base href="http://typo3.org/"></base>';
		$actual = $viewHelper->render();
		$this->assertSame($expected, $actual);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_renderAddsFormNameToTemplateVariableContainer() {
		$formName = 'someFormName';

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('name' => $formName)));

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName', $formName);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName');
		$viewHelper->render('', array(), NULL, NULL, NULL, NULL);
	}
}
?>