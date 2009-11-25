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
 * @version $Id: FormViewHelperTest_testcase.php 1734 2009-11-25 21:53:57Z stucki $
 */
class Tx_Fluid_ViewHelpers_FormViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUID() {
		$object = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractDomainObject'), array('_isNew'));
		$object->_set('uid', '123');
		$object->expects($this->once())->method('_isNew')->will($this->returnValue(FALSE));

		$expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('prefixFieldName'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->expects($this->any())->method('prefixFieldName')->with('theName')->will($this->returnValue('prefix[theName]'));

		$actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderHiddenIdentityFieldReturnsAHiddenInputFieldIfObjectIsNewButAClone() {
		$object = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractDomainObject'), array('_isNew', '_isClone'));
		$object->_set('uid', '123');
		$object->expects($this->once())->method('_isNew')->will($this->returnValue(TRUE));
		$object->expects($this->once())->method('_isClone')->will($this->returnValue(TRUE));

		$expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('prefixFieldName'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->expects($this->any())->method('prefixFieldName')->with('theName')->will($this->returnValue('prefix[theName]'));

		$actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderHiddenIdentityFieldReturnsACommentIfTheObjectIsWithoutIdentity() {
		$object = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractDomainObject'), array('_isNew'));
		$object->expects($this->once())->method('_isNew')->will($this->returnValue(FALSE));

		$expectedResult = chr(10) . '<!-- Object of type ' . get_class($object) . ' is without identity -->' . chr(10);

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('prefixFieldName'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderAddsObjectToViewHelperVariableContainer() {
		$formObject = new stdClass();

		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormNameToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormNameFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'));
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
	 */
	public function renderAddsFormNameToViewHelperVariableContainer() {
		$formName = 'someFormName';

		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_FormViewHelper', array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'));
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(new Tx_Fluid_Core_ViewHelper_Arguments(array('name' => $formName)));

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName', $formName);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName');
		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderCallsRenderHiddenReferrerFields() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenReferrerFields', 'renderRequestHashField'));
		$viewHelper->expects($this->once())->method('renderHiddenReferrerFields');
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$this->request->expects($this->atLeastOnce())->method('getControllerExtensionName')->will($this->returnValue('extensionName'));
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

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
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
	public function renderAddsExtensionPrefixToTemplateVariableContainerIfNoPrefixIsSpecified() {
		$expectedPrefix = 'tx_someextension_someplugin';

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(
			new Tx_Fluid_Core_ViewHelper_Arguments(
				array(
					'extensionName' => 'someExtension',
					'pluginName' => 'somePlugin',
				)
			)
		);

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix', $expectedPrefix);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix');
		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderUsesCurrentExtensionNameForExtensionPrefixIfNoPrefixAndNoExtensionNameIsSpecified() {
		$expectedPrefix = 'tx_currentextensionname_someplugin';

		$this->request->expects($this->atLeastOnce())->method('getControllerExtensionName')->will($this->returnValue('currentExtensionName'));

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(
			new Tx_Fluid_Core_ViewHelper_Arguments(
				array(
					'pluginName' => 'somePlugin',
				)
			)
		);

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix', $expectedPrefix);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix');
		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderUsesCurrentPluginNameForExtensionPrefixIfNoPrefixAndNoPluginNameIsSpecified() {
		$expectedPrefix = 'tx_someextension_currentpluginname';

		$this->request->expects($this->atLeastOnce())->method('getPluginName')->will($this->returnValue('currentPluginName'));

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(
			new Tx_Fluid_Core_ViewHelper_Arguments(
				array(
					'extensionName' => 'someExtension',
				)
			)
		);

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix', $expectedPrefix);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix');
		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderUsesCurrentExtensionNameAndCurrentPluginNameForExtensionPrefixIfNoPrefixAndNoExtensionNameAndNoPluginNameIsSpecified() {
		$expectedPrefix = 'tx_currentextensionname_currentpluginname';

		$this->request->expects($this->atLeastOnce())->method('getControllerExtensionName')->will($this->returnValue('currentExtensionName'));
		$this->request->expects($this->atLeastOnce())->method('getPluginName')->will($this->returnValue('currentPluginName'));

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'renderRequestHashField', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$this->viewHelperVariableContainer->expects($this->once())->method('add')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix', $expectedPrefix);
		$this->viewHelperVariableContainer->expects($this->once())->method('remove')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix');
		$viewHelper->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setFormActionUriUsesUriBuilderToSetActionAttribute() {
		$this->uriBuilder->expects($this->once())->method('reset')->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setTargetPageUid')->with(123)->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('setTargetPageType')->with(2)->will($this->returnValue($this->uriBuilder));
		$this->uriBuilder->expects($this->once())->method('uriFor')->with('someAction', array('foo' => 'bar'), 'someController', 'someExtensionName', 'somePluginName')->will($this->returnValue('someUri'));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('action', 'someUri');

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(
			new Tx_Fluid_Core_ViewHelper_Arguments(
				array(
					'pageUid' => 123,
					'pageType' => 2,
					'action' => 'someAction',
					'arguments' => array('foo' => 'bar'),
					'controller' => 'someController',
					'extensionName' => 'someExtensionName',
					'pluginName' => 'somePluginName',
				)
			)
		);

		$viewHelper->_call('setFormActionUri');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setFormActionUriRespectsActionUriArgument() {
		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('action', 'someOverwrittenUri');

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$viewHelper->setArguments(
			new Tx_Fluid_Core_ViewHelper_Arguments(
				array(
					'pageUid' => 123,
					'pageType' => 2,
					'action' => 'someAction',
					'arguments' => array('foo' => 'bar'),
					'controller' => 'someController',
					'extensionName' => 'someExtensionName',
					'pluginName' => 'somePluginName',
					'actionUri' => 'someOverwrittenUri',
				)
			)
		);

		$viewHelper->_call('setFormActionUri');
	}
}
?>