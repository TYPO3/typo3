<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class Tx_Extbase_MVC_Controller_ActionController_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestSticksToSpecifiedSequence() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('setDispatched')->with(TRUE);

		$mockResponse = $this->getMock('Tx_Extbase_MVC_Web_Response', array(), array(), '', FALSE);

		$mockView = $this->getMock('Tx_Extbase_MVC_View_ViewInterface');

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array(
			'initializeFooAction', 'initializeAction', 'resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'mapRequestArgumentsToControllerArguments', 'resolveView', 'initializeView', 'callActionMethod', 'checkRequestHash'),
			array(), '', FALSE);
		$mockController->_set('objectFactory', $mockObjectFactory);
		$mockController->expects($this->at(0))->method('resolveActionMethodName')->will($this->returnValue('fooAction'));
		$mockController->expects($this->at(1))->method('initializeActionMethodArguments');
		$mockController->expects($this->at(2))->method('initializeActionMethodValidators');
		$mockController->expects($this->at(3))->method('initializeAction');
		$mockController->expects($this->at(4))->method('initializeFooAction');
		$mockController->expects($this->at(5))->method('mapRequestArgumentsToControllerArguments');
                $mockController->expects($this->at(6))->method('checkRequestHash');
		$mockController->expects($this->at(7))->method('resolveView')->will($this->returnValue($mockView));
		$mockController->expects($this->at(8))->method('initializeView');
		$mockController->expects($this->at(9))->method('callActionMethod');

		$mockController->processRequest($mockRequest, $mockResponse);
		$this->assertSame($mockRequest, $mockController->_get('request'));
		$this->assertSame($mockResponse, $mockController->_get('response'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionMethodAppendsStringsReturnedByActionMethodToTheResponseObject() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('Tx_Extbase_MVC_ResponseInterface', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the returned string');

		$mockArguments = new ArrayObject;

		$mockArgumentMappingResults = $this->getMock('Tx_Extbase_Property_MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(FALSE));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->will($this->returnValue('the returned string'));
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('argumentsMappingResults', $mockArgumentMappingResults);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionMethodRendersTheViewAutomaticallyIfTheActionReturnedNullAndAViewExists() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('Tx_Extbase_MVC_ResponseInterface', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the view output');

		$mockView = $this->getMock('Tx_Extbase_MVC_View_ViewInterface');
		$mockView->expects($this->once())->method('render')->will($this->returnValue('the view output'));

		$mockArguments = new ArrayObject;

		$mockArgumentMappingResults = $this->getMock('Tx_Extbase_Property_MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(FALSE));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction');
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('argumentsMappingResults', $mockArgumentMappingResults);
		$mockController->_set('view', $mockView);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionMethodCallsTheErrorActionIfTheMappingResultsHaveErrors() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('Tx_Extbase_MVC_ResponseInterface', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the returned string');

		$mockArguments = new ArrayObject;

		$mockArgumentMappingResults = $this->getMock('Tx_Extbase_Property_MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(TRUE));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('barAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('barAction')->will($this->returnValue('the returned string'));
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('errorMethodName', 'barAction');
		$mockController->_set('argumentsMappingResults', $mockArgumentMappingResults);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function callActionMethodPassesDefaultValuesAsArguments() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('Tx_Extbase_MVC_ResponseInterface', array(), array(), '', FALSE);

		$arguments = new ArrayObject();
		$optionalArgument = new Tx_Extbase_MVC_Controller_Argument('name1', 'Text');
		$optionalArgument->setDefaultValue('Default value');
		$arguments[] = $optionalArgument;

		$mockArgumentMappingResults = $this->getMock('Tx_Extbase_Property_MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(FALSE));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->with('Default value');
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('argumentsMappingResults', $mockArgumentMappingResults);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function resolveViewUsesFluidTemplateViewIfTemplateIsAvailable() {
		$mockSession = $this->getMock('Tx_Extbase_Session_SessionInterface');
		$mockControllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array(), array(), '', FALSE);

		$mockFluidTemplateView = $this->getMock('Tx_Extbase_MVC_View_ViewInterface', array('setControllerContext', 'getViewHelper', 'assign', 'assignMultiple', 'render', 'hasTemplate', 'initializeView'));
		$mockFluidTemplateView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);
		$mockFluidTemplateView->expects($this->once())->method('hasTemplate')->will($this->returnValue(TRUE));

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('Tx_Fluid_View_TemplateView')->will($this->returnValue($mockFluidTemplateView));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('buildControllerContext'), array(), '', FALSE);
		$mockController->expects($this->once())->method('buildControllerContext')->will($this->returnValue($mockControllerContext));
		$mockController->_set('session', $mockSession);
		$mockController->_set('objectManager', $mockObjectManager);

		$this->assertSame($mockFluidTemplateView, $mockController->_call('resolveView'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveViewObjectNameUsesViewObjectNamePatternToResolveViewObjectName() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('MyPackage'));
		$mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('MyController'));
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('MyAction'));
		$mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('MyFormat'));

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface', array(), array(), '', FALSE);
		
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('objectManager', $mockObjectManager);
		$mockController->_set('viewObjectNamePattern', 'RandomViewObjectPattern_@package_@controller_@action_@format');

		$mockController->_call('resolveViewObjectName');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveActionMethodNameReturnsTheCurrentActionMethodNameFromTheRequest() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('fooBar'));

		$mockController = $this->getAccessibleMock('Tx_Extbase_MVC_Controller_ActionController', array('fooBarAction'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);

		$this->assertEquals('fooBarAction', $mockController->_call('resolveActionMethodName'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_NoSuchAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveActionMethodNameThrowsAnExceptionIfTheActionDefinedInTheRequestDoesNotExist() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('fooBar'));

		$mockController = $this->getAccessibleMock('Tx_Extbase_MVC_Controller_ActionController', array('otherBarAction'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);

		$mockController->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeActionMethodArgumentsRegistersArgumentsFoundInTheSignatureOfTheCurrentActionMethod() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);

		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array('addNewArgument', 'removeAll'), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('addNewArgument')->with('stringArgument', 'string', TRUE);
		$mockArguments->expects($this->at(1))->method('addNewArgument')->with('integerArgument', 'integer', TRUE);
		$mockArguments->expects($this->at(2))->method('addNewArgument')->with('objectArgument', 'F3_Foo_Bar', TRUE);

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction', 'evaluateDontValidateAnnotations'), array(), '', FALSE);

		$methodParameters = array(
			'stringArgument' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'type' => 'string'
			),
			'integerArgument' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'type' => 'integer'
			),
			'objectArgument' => array(
				'position' => 2,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'type' => 'F3_Foo_Bar'
			)
		);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));

		$mockController->injectReflectionService($mockReflectionService);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodArguments');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeActionMethodArgumentsRegistersOptionalArgumentsAsSuch() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);

		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('addNewArgument')->with('arg1', 'string', TRUE);
		$mockArguments->expects($this->at(1))->method('addNewArgument')->with('arg2', 'array', FALSE, array(21));
		$mockArguments->expects($this->at(2))->method('addNewArgument')->with('arg3', 'string', FALSE, 42);

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction', 'evaluateDontValidateAnnotations'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'type' => 'string'
			),
			'arg2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => TRUE,
				'optional' => TRUE,
				'defaultValue' => array(21),
				'allowsNull' => FALSE
			),
			'arg3' => array(
				'position' => 2,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => TRUE,
				'defaultValue' => 42,
				'allowsNull' => FALSE,
				'type' => 'string'
			)
		);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));

		$mockController->injectReflectionService($mockReflectionService);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodArguments');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sbastian@typo3.org>
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidArgumentType
	 */
	public function initializeActionMethodArgumentsThrowsExceptionIfDataTypeWasNotSpecified() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);

		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array(), array(), '', FALSE);

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
			)
		);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));

		$mockController->injectReflectionService($mockReflectionService);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodArguments');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sbastian@typo3.org>
	 */
	public function initializeActionMethodValidatorsCorrectlyRegistersValidatorsBasedOnDataType() {
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction'), array(), '', FALSE);

		$argument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName'), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('arg1'));

		$arguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array('dummy'), array(), '', FALSE);
		$arguments->addArgument($argument);

		$methodTagsValues = array(

		);

		$methodArgumentsValidatorConjunctions = array();
		$methodArgumentsValidatorConjunctions['arg1'] = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockValidatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('buildMethodArgumentsValidatorConjunctions')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodArgumentsValidatorConjunctions));

		$mockController->injectReflectionService($mockReflectionService);
		$mockController->injectValidatorResolver($mockValidatorResolver);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodValidators');

		$this->assertEquals($methodArgumentsValidatorConjunctions['arg1'], $arguments['arg1']->getValidator());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sbastian@typo3.org>
	 */
	public function initializeActionMethodValidatorsRegistersModelBasedValidators() {
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction'), array(), '', FALSE);

		$argument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName', 'getDataType'), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('arg1'));
		$argument->expects($this->any())->method('getDataType')->will($this->returnValue('F3_Foo_Quux'));

		$arguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array('dummy'), array(), '', FALSE);
		$arguments->addArgument($argument);

		$methodTagsValues = array(

		);

		$quuxBaseValidatorConjunction = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);

		$methodArgumentsValidatorConjunctions = array();
		$methodArgumentsValidatorConjunctions['arg1'] = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$methodArgumentsValidatorConjunctions['arg1']->expects($this->once())->method('addValidator')->with($quuxBaseValidatorConjunction);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockValidatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('buildMethodArgumentsValidatorConjunctions')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodArgumentsValidatorConjunctions));
		$mockValidatorResolver->expects($this->once())->method('getBaseValidatorConjunction')->with('F3_Foo_Quux')->will($this->returnValue($quuxBaseValidatorConjunction));

		$mockController->injectReflectionService($mockReflectionService);
		$mockController->injectValidatorResolver($mockValidatorResolver);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodValidators');

		$this->assertEquals($methodArgumentsValidatorConjunctions['arg1'], $arguments['arg1']->getValidator());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sbastian@typo3.org>
	 */
	public function initializeActionMethodValidatorsDoesNotRegisterModelBasedValidatorsIfDontValidateAnnotationIsSet() {
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction'), array(), '', FALSE);

		$argument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getName', 'getDataType'), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('arg1'));
		$argument->expects($this->any())->method('getDataType')->will($this->returnValue('F3_Foo_Quux'));

		$arguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array('dummy'), array(), '', FALSE);
		$arguments->addArgument($argument);

		$methodTagsValues = array(
			'dontvalidate' => array(
				'$arg1'
			)
		);

		$methodArgumentsValidatorConjunctions = array();
		$methodArgumentsValidatorConjunctions['arg1'] = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockValidatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('buildMethodArgumentsValidatorConjunctions')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodArgumentsValidatorConjunctions));
		$mockValidatorResolver->expects($this->any())->method('getBaseValidatorConjunction')->will($this->throwException(new Exception("This should not be called because the dontvalidate annotation is set.")));

		$mockController->injectReflectionService($mockReflectionService);
		$mockController->injectValidatorResolver($mockValidatorResolver);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodValidators');

		$this->assertEquals($methodArgumentsValidatorConjunctions['arg1'], $arguments['arg1']->getValidator());
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function defaultErrorActionSetsArgumentMappingResultsErrorsInRequest() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);
		$mockFlashMessages = $this->getMock('Tx_Extbase_MVC_Controller_FlashMessages', array(), array(), '', FALSE);

		$mockError = $this->getMock('Tx_Extbase_Error_Error', array('getMessage'), array(), '', FALSE);
		$mockArgumentsMappingResults = $this->getMock('Tx_Extbase_Property_MappingResults', array('getErrors', 'getWarnings'), array(), '', FALSE);
		$mockArgumentsMappingResults->expects($this->atLeastOnce())->method('getErrors')->will($this->returnValue(array($mockError)));
		$mockArgumentsMappingResults->expects($this->any())->method('getWarnings')->will($this->returnValue(array()));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('pushFlashMessage'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('flashMessages', $mockFlashMessages);
		$mockController->_set('argumentsMappingResults', $mockArgumentsMappingResults);

		$mockRequest->expects($this->once())->method('setErrors')->with(array($mockError));

		$mockController->_call('errorAction');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function defaultErrorActionCallsGetErrorFlashMessageAndPutsFlashMessage() {
		$this->markTestIncomplete('To be implemented');
	}

        /**
	 * Data Provider for checkRequestHashDoesNotThrowExceptionInNormalOperations
	 */
	public function checkRequestHashInNormalOperation() {
		return array(
			// HMAC is verified
			array(TRUE),
			// HMAC not verified, but objects are directly fetched from persistence layer
			array(FALSE, FALSE, Tx_Extbase_MVC_Controller_Argument::ORIGIN_PERSISTENCE, Tx_Extbase_MVC_Controller_Argument::ORIGIN_PERSISTENCE),
			// HMAC not verified, objects new and modified, but dontverifyrequesthash-annotation set
			array(FALSE, TRUE, Tx_Extbase_MVC_Controller_Argument::ORIGIN_PERSISTENCE, Tx_Extbase_MVC_Controller_Argument::ORIGIN_PERSISTENCE_AND_MODIFIED, array('dontverifyrequesthash' => ''))
		);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @dataProvider checkRequestHashInNormalOperation
	 */
	public function checkRequestHashDoesNotThrowExceptionInNormalOperations($hmacVerified, $reflectionServiceNeedsInitialization = FALSE, $argument1Origin = 3, $argument2Origin = 3, $methodTagsValues = array()) {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array('isHmacVerified'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('isHmacVerified')->will($this->returnValue($hmacVerified));

		$argument1 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getOrigin'), array(), '', FALSE);
		$argument1->expects($this->any())->method('getOrigin')->will($this->returnValue($argument1Origin));
		$argument2 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getOrigin'), array(), '', FALSE);
		$argument2->expects($this->any())->method('getOrigin')->will($this->returnValue($argument2Origin));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('dummy'), array(), '', FALSE);

		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array('getMethodTagsValues'), array(), '', FALSE);
		if ($reflectionServiceNeedsInitialization) {
			// Somehow this is needed, else I get "Mocked method does not exist."
			$mockReflectionService->expects($this->any())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));
		}
		$mockController->_set('arguments', array($argument1, $argument2));
		$mockController->_set('request', $mockRequest);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->injectReflectionService($mockReflectionService);

		$mockController->_call('checkRequestHash');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidOrNoRequestHash
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function checkRequestHashThrowsExceptionIfNeeded() {
		// $this->markTestIncomplete('To be implemented');
		$hmacVerified = FALSE;
		$argument1Origin = Tx_Extbase_MVC_Controller_Argument::ORIGIN_PERSISTENCE_AND_MODIFIED;
		$argument2Origin = Tx_Extbase_MVC_Controller_Argument::ORIGIN_PERSISTENCE;
		$methodTagsValues = array();
		
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array('isHmacVerified'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('isHmacVerified')->will($this->returnValue($hmacVerified));
		
		$argument1 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getOrigin'), array(), '', FALSE);
		$argument1->expects($this->any())->method('getOrigin')->will($this->returnValue($argument1Origin));
		$argument2 = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('getOrigin'), array(), '', FALSE);
		$argument2->expects($this->any())->method('getOrigin')->will($this->returnValue($argument2Origin));
		
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('dummy'), array(), '', FALSE);
		
		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array('getMethodTagsValues'), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));
		
		$mockController->_set('arguments', array($argument1, $argument2));
		$mockController->_set('request', $mockRequest);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->injectReflectionService($mockReflectionService);
		
		$mockController->_call('checkRequestHash');
	}

}
?>
