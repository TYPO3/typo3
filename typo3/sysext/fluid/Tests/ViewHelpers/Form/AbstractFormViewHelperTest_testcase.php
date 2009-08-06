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
 * Test for the Abstract Form view helper
 *
 * @version $Id: AbstractFormViewHelperTest.php 2914 2009-07-28 18:26:38Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function ifAnAttributeValueIsAnObjectMaintainedByThePersistenceManagerItIsConvertedToAUUID() {
		$mockPersistenceBackend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$mockPersistenceBackend->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66'));

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($mockPersistenceBackend));

		$className = 'Object' . uniqid();
		$fullClassName = 'Tx_Fluid_ViewHelpers_Form_' . $className;
		eval('class ' . $className . '  {
			public function FLOW3_Persistence_isNew() { return FALSE; }
			public function FLOW3_Persistence_isDirty($propertyName) {}
			public function FLOW3_Persistence_memorizeCleanState($propertyName = NULL) {}
			public function FLOW3_AOP_Proxy_getProperty($name) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() {}
			public function __clone() {}
		}');
		$object = $this->getMock($fullClassName);
		$object->expects($this->any())->method('FLOW3_Persistence_isNew')->will($this->returnValue(FALSE));

		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('dummy'), array(), '', FALSE);
		$formViewHelper->injectPersistenceManager($mockPersistenceManager);

		// TODO mock arguments
		$arguments = new Tx_Fluid_Core_ViewHelper_Arguments(array('name' => 'foo', 'value' => $object, 'property' => NULL));
		$formViewHelper->_set('arguments', $arguments);
		$formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(FALSE));

		$this->assertSame('foo[uid]', $formViewHelper->_call('getName'));
		$this->assertSame('6f487e40-4483-11de-8a39-0800200c9a66', $formViewHelper->_call('getValue'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getNameBuildsNameFromPropertyAndFormNameIfInObjectAccessorMode() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('isObjectAccessorMode'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);

		$formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName')->will($this->returnValue('myFormName'));

		$formViewHelper->_set('arguments', array('name' => NULL, 'value' => NULL, 'property' => 'bla'));
		$expected = 'myFormName[bla]';
		$actual = $formViewHelper->_call('getName');
		$this->assertSame($expected, $actual);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValueBuildsValueFromPropertyAndFormObjectIfInObjectAccessorMode() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('isObjectAccessorMode'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);

		$className = 'test_' . uniqid();
		$mockObject = eval('
			class ' . $className . ' {
				public function getSomething() {
					return "MyString";
				}
			}
			return new ' . $className . ';
		');

		$formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject')->will($this->returnValue($mockObject));
		$this->viewHelperVariableContainer->expects($this->once())->method('exists')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject')->will($this->returnValue(TRUE));

		// TODO mock arguments
		$arguments = new Tx_Fluid_Core_ViewHelper_Arguments(array('name' => NULL, 'value' => NULL, 'property' => 'something'));
		$formViewHelper->_set('arguments', $arguments);
		$expected = 'MyString';
		$actual = $formViewHelper->_call('getValue');
		$this->assertSame($expected, $actual);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValueReturnsNullIfNotInObjectAccessorModeAndValueArgumentIsNoSet() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('isObjectAccessorMode'), array(), '', FALSE);
		$formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(FALSE));

		$mockArguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->any())->method('hasArgument')->with('value')->will($this->returnValue(FALSE));
		$formViewHelper->_set('arguments', $mockArguments);

		$this->assertNull($formViewHelper->_call('getValue'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValueReturnsValueArgumentIfSpecified() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('isObjectAccessorMode'), array(), '', FALSE);
		$formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(FALSE));

		$mockArguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->any())->method('hasArgument')->with('value')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->any())->method('offsetGet')->with('value')->will($this->returnValue('someValue'));
		$formViewHelper->_set('arguments', $mockArguments);

		$this->assertEquals('someValue', $formViewHelper->_call('getValue'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function isObjectAccessorModeReturnsTrueIfPropertyIsSetAndFormObjectIsGiven() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);

		$this->viewHelperVariableContainer->expects($this->once())->method('exists')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName')->will($this->returnValue(TRUE));

		$formViewHelper->_set('arguments', array('name' => NULL, 'value' => NULL, 'property' => 'bla'));
		$this->assertTrue($formViewHelper->_call('isObjectAccessorMode'));

		$formViewHelper->_set('arguments', array('name' => NULL, 'value' => NULL, 'property' => NULL));
		$this->assertFalse($formViewHelper->_call('isObjectAccessorMode'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getErrorsForPropertyReturnsErrorsFromRequestIfPropertyIsSet() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Request');

		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('hasArgument'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);
		$mockArguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->once())->method('hasArgument')->with('property')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->once())->method('offsetGet')->with('property')->will($this->returnValue('bar'));
		$formViewHelper->_set('arguments', $mockArguments);
		$this->viewHelperVariableContainer->expects($this->any())->method('get')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName')->will($this->returnValue('foo'));

		$this->controllerContext->expects($this->once())->method('getRequest')->will($this->returnValue($mockRequest));
		$mockArgumentError = $this->getMock('Tx_Extbase_MVC_Controller_ArgumentError', array(), array('foo'));
		$mockArgumentError->expects($this->once())->method('getPropertyName')->will($this->returnValue('foo'));
		$mockPropertyError = $this->getMock('Tx_Extbase_Validation_PropertyError', array(), array('bar'));
		$mockPropertyError->expects($this->once())->method('getPropertyName')->will($this->returnValue('bar'));
		$mockError = $this->getMock('Tx_Extbase_Error_Error', array(), array(), '', FALSE);
		$mockPropertyError->expects($this->once())->method('getErrors')->will($this->returnValue(array($mockError)));
		$mockArgumentError->expects($this->once())->method('getErrors')->will($this->returnValue(array($mockPropertyError)));
		$mockRequest->expects($this->once())->method('getErrors')->will($this->returnValue(array($mockArgumentError)));

		$errors = $formViewHelper->_call('getErrorsForProperty');
		$this->assertEquals(array($mockError), $errors);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getErrorsForPropertyReturnsEmptyArrayIfPropertyIsNotSet() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('hasArgument'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);
		$mockArguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->once())->method('hasArgument')->with('property')->will($this->returnValue(FALSE));
		$formViewHelper->_set('arguments', $mockArguments);

		$errors = $formViewHelper->_call('getErrorsForProperty');
		$this->assertEquals(array(), $errors);
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setErrorClassAttributeDoesNotSetClassAttributeIfNoErrorOccured() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('hasArgument', 'getErrorsForProperty'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);
		$mockArguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->once())->method('hasArgument')->with('class')->will($this->returnValue(FALSE));
		$formViewHelper->_set('arguments', $mockArguments);

		$this->tagBuilder->expects($this->never())->method('addAttribute');

		$formViewHelper->_call('setErrorClassAttribute');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setErrorClassAttributeSetsErrorClassIfAnErrorOccured() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('hasArgument', 'getErrorsForProperty'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);
		$mockArguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(FALSE));
		$mockArguments->expects($this->at(1))->method('hasArgument')->with('errorClass')->will($this->returnValue(FALSE));
		$formViewHelper->_set('arguments', $mockArguments);

		$mockError = $this->getMock('Tx_Extbase_Error_Error', array(), array(), '', FALSE);
		$formViewHelper->expects($this->once())->method('getErrorsForProperty')->will($this->returnValue(array($mockError)));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('class', 'f3-form-error');

		$formViewHelper->_call('setErrorClassAttribute');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setErrorClassAttributeAppendsErrorClassToExistingClassesIfAnErrorOccured() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('hasArgument', 'getErrorsForProperty'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);
		$mockArguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->at(1))->method('offsetGet')->with('class')->will($this->returnValue('default classes'));
		$mockArguments->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(FALSE));
		$formViewHelper->_set('arguments', $mockArguments);

		$mockError = $this->getMock('Tx_Extbase_Error_Error', array(), array(), '', FALSE);
		$formViewHelper->expects($this->once())->method('getErrorsForProperty')->will($this->returnValue(array($mockError)));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('class', 'default classes f3-form-error');

		$formViewHelper->_call('setErrorClassAttribute');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setErrorClassAttributeSetsCustomErrorClassIfAnErrorOccured() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('hasArgument', 'getErrorsForProperty'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);
		$mockArguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(FALSE));
		$mockArguments->expects($this->at(1))->method('hasArgument')->with('errorClass')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->at(2))->method('offsetGet')->with('errorClass')->will($this->returnValue('custom-error-class'));
		$formViewHelper->_set('arguments', $mockArguments);

		$mockError = $this->getMock('Tx_Extbase_Error_Error', array(), array(), '', FALSE);
		$formViewHelper->expects($this->once())->method('getErrorsForProperty')->will($this->returnValue(array($mockError)));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('class', 'custom-error-class');

		$formViewHelper->_call('setErrorClassAttribute');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setErrorClassAttributeAppendsCustomErrorClassIfAnErrorOccured() {
		$formViewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('hasArgument', 'getErrorsForProperty'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($formViewHelper);
		$mockArguments = $this->getMock('Tx_Fluid_Core_ViewHelper_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->at(1))->method('offsetGet')->with('class')->will($this->returnValue('default classes'));
		$mockArguments->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->at(3))->method('offsetGet')->with('errorClass')->will($this->returnValue('custom-error-class'));
		$formViewHelper->_set('arguments', $mockArguments);

		$mockError = $this->getMock('Tx_Extbase_Error_Error', array(), array(), '', FALSE);
		$formViewHelper->expects($this->once())->method('getErrorsForProperty')->will($this->returnValue(array($mockError)));

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('class', 'default classes custom-error-class');

		$formViewHelper->_call('setErrorClassAttribute');
	}
}

?>