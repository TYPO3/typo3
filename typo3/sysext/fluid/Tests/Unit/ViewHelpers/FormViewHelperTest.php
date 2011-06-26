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
 * Test for the Form view helper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_FormViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @var Tx_Extbase_Service_ExtensionService
	 */
	protected $mockExtensionService;

	public function setUp() {
		parent::setUp();
		$this->mockExtensionService = $this->getMock('Tx_Extbase_Service_ExtensionService');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderAddsObjectToViewHelperVariableContainer() {
		$formObject = new stdClass();

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormObjectNameToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectNameFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('object' => $formObject)));
		$this->viewHelperVariableContainer->expects($this->at(0))->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject', $formObject);
		$this->viewHelperVariableContainer->expects($this->at(1))->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'additionalIdentityProperties', array());
		$this->viewHelperVariableContainer->expects($this->at(2))->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject');
		$this->viewHelperVariableContainer->expects($this->at(3))->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'additionalIdentityProperties');
		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAddsObjectNameToTemplateVariableContainer() {
		$objectName = 'someObjectName';

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('name' => $objectName)));

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObjectName', $objectName);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObjectName');
		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function formObjectNameArgumentOverrulesNameArgument() {
		$objectName = 'someObjectName';

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('name' => 'formName', 'objectName' => $objectName)));

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObjectName', $objectName);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObjectName');
		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderCallsRenderHiddenReferrerFields() {
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderRequestHashField', 'renderHiddenReferrerFields'), array(), '', FALSE);
		$viewHelper->expects($this->once())->method('renderHiddenReferrerFields');
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCallsRenderHiddenIdentityField() {
		$object = new stdClass();
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderRequestHashField', 'renderHiddenIdentityField', 'getFormObjectName'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('object' => $object)));
		$viewHelper->expects($this->atLeastOnce())->method('getFormObjectName')->will($this->returnValue('MyName'));
		$viewHelper->expects($this->once())->method('renderHiddenIdentityField')->with($object, 'MyName');

		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderCallsRenderAdditionalIdentityFields() {
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderRequestHashField', 'renderAdditionalIdentityFields'), array(), '', FALSE);
		$viewHelper->expects($this->once())->method('renderAdditionalIdentityFields');
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderWrapsHiddenFieldsWithDivForXhtmlCompatibility() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderRequestHashField'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->expects($this->once())->method('renderHiddenIdentityField')->will($this->returnValue('hiddenIdentityField'));
		$viewHelper->expects($this->once())->method('renderAdditionalIdentityFields')->will($this->returnValue('additionalIdentityFields'));
		$viewHelper->expects($this->once())->method('renderHiddenReferrerFields')->will($this->returnValue('hiddenReferrerFields'));
		$viewHelper->expects($this->once())->method('renderRequestHashField')->will($this->returnValue('requestHashField'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('formContent'));

		$expectedResult = chr(10) . '<div style="display: none">' . 'hiddenIdentityFieldadditionalIdentityFieldshiddenReferrerFieldsrequestHashField' . chr(10) . '</div>' . chr(10) . 'formContent';
		$this->tagBuilder->expects($this->once())->method('setContent')->with($expectedResult);

		$viewHelper->render();
	}


	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderAdditionalIdentityFieldsFetchesTheFieldsFromViewHelperVariableContainerAndBuildsHiddenFieldsForThem() {
		$identityProperties = array(
			'object1[object2]' => '<input type="hidden" name="object1[object2][__identity]" value="42" />',
			'object1[object2][subobject]' => '<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />'
		);
		$this->viewHelperVariableContainer->expects($this->once())->method('exists')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'additionalIdentityProperties')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'additionalIdentityProperties')->will($this->returnValue($identityProperties));
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$expected = chr(10) . '<input type="hidden" name="object1[object2][__identity]" value="42" />' . chr(10) .
			'<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />';
		$actual = $viewHelper->_call('renderAdditionalIdentityFields');
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields() {
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$this->request->expects($this->atLeastOnce())->method('getControllerExtensionName')->will($this->returnValue('extensionName'));
		$this->request->expects($this->never())->method('getControllerSubextensionName');
		$this->request->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue('controllerName'));
		$this->request->expects($this->atLeastOnce())->method('getControllerActionName')->will($this->returnValue('controllerActionName'));

		$hiddenFields = $viewHelper->_call('renderHiddenReferrerFields');
		$expectedResult = chr(10) . '<input type="hidden" name="__referrer[extensionName]" value="extensionName" />' . chr(10) .
			'<input type="hidden" name="__referrer[controllerName]" value="controllerName" />' . chr(10) .
			'<input type="hidden" name="__referrer[actionName]" value="controllerActionName" />' . chr(10);
		$this->assertEquals($expectedResult, $hiddenFields);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAddsSpecifiedPrefixToTemplateVariableContainer() {
		$prefix = 'somePrefix';

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('fieldNamePrefix' => $prefix)));

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix', $prefix);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix');
		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderAddsDefaultFieldNamePrefixToTemplateVariableContainerIfNoPrefixIsSpecified() {
		$expectedPrefix = 'tx_someextension_someplugin';

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
		$this->mockExtensionService->expects($this->once())->method('getPluginNamespace')->with('SomeExtension', 'SomePlugin')->will($this->returnValue('tx_someextension_someplugin'));
		$viewHelper->injectExtensionService($this->mockExtensionService);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('extensionName' => 'SomeExtension', 'pluginName' => 'SomePlugin')));

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix', $expectedPrefix);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix');
		$viewHelper->render();
	}

	/**
	 * Data Provider for postProcessUriArgumentsForRequestHashWorks
	 */
	public function argumentsForPostProcessUriArgumentsForRequestHash() {
		return array(
			// simple values
			array(
				array(
					'bla' => 'X',
					'blubb' => 'Y'
				),
				array(
					'bla',
					'blubb'
				)
			),
			// Arrays
			array(
				array(
					'bla' => array(
						'test1' => 'X',
						'test2' => 'Y'
					),
					'blubb' => 'Y'
				),
				array(
					'bla[test1]',
					'bla[test2]',
					'blubb'
				)
			)
		);
	}
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @dataProvider argumentsForPostProcessUriArgumentsForRequestHash
	 */
	public function postProcessUriArgumentsForRequestHashWorks($arguments, $expectedResults) {
		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('dummy'), array(), '', FALSE);
		$results = array();
		$viewHelper->_callRef('postProcessUriArgumentsForRequestHash', $arguments, $results);
		$this->assertEquals($expectedResults, $results);
	}
}
?>