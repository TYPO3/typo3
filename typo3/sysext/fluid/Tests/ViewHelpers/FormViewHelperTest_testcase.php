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

/**
 */

require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');

/**
 * @version $Id:$
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_FormViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUUID() {
		$object = new stdClass();

		$mockBackend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$mockBackend->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('123'));

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($mockBackend));

		$expectedResult = '<input type="hidden" name="theName[uid]" value="123" />';

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('dummy'), array(), '', FALSE);
		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('name' => 'theName')));
		$viewHelper->_set('persistenceManager', $mockPersistenceManager);

		$actualResult = $viewHelper->_call('renderHiddenIdentityField', $object);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderAddsObjectToTemplateVariableContainer() {
		$formObject = new stdClass();

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);


		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject', $formObject);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject');
		$viewHelper->render(NULL, array(), NULL, NULL, NULL, NULL, array(), $formObject);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderAddsFormNameToTemplateVariableContainer() {
		$formName = 'someFormName';

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('name' => $formName)));

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName', $formName);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName');
		$viewHelper->render('', array(), NULL, NULL, NULL, NULL);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderCallsRenderHiddenReferrerFields() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenReferrerFields'), array(), '', FALSE);
		$viewHelper->expects($this->once())->method('renderHiddenReferrerFields');
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->render('', array(), NULL, NULL, NULL, NULL);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Request');
		$this->controllerContext->expects($this->atLeastOnce())->method('getRequest')->will($this->returnValue($mockRequest));

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$mockRequest->expects($this->atLeastOnce())->method('getControllerExtensionName')->will($this->returnValue('extensionName'));
		$mockRequest->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue('controllerName'));
		$mockRequest->expects($this->atLeastOnce())->method('getControllerActionName')->will($this->returnValue('controllerActionName'));

		$hiddenFields = $viewHelper->_call('renderHiddenReferrerFields');
		$expectedResult = PHP_EOL . '<input type="hidden" name="__referrer[extensionName]" value="extensionName" />' . PHP_EOL .
			'<input type="hidden" name="__referrer[controllerName]" value="controllerName" />' . PHP_EOL .
			'<input type="hidden" name="__referrer[actionName]" value="controllerActionName" />';
		$this->assertEquals($expectedResult, $hiddenFields);
	}
}
?>