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

class Tx_Extbase_MVC_Controller_ActionController_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 */
	public function processRequestSticksToSpecifiedSequence() {
		 // TODO mock URIBuilder after object factory was implemented
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('setDispatched')->with(TRUE);
	
		$mockResponse = $this->getMock('Tx_Extbase_MVC_Response', array(), array(), '', FALSE);

		$mockView = $this->getMock('Tx_Extbase_MVC_View_ViewInterface');
	
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array(
			'initializeFooAction', 'initializeAction', 'resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'mapRequestArgumentsToControllerArguments', 'initializeControllerArgumentsBaseValidators', 'resolveView', 'initializeView', 'callActionMethod'));
		$mockController->expects($this->at(0))->method('resolveActionMethodName')->will($this->returnValue('fooAction'));
		$mockController->expects($this->at(1))->method('initializeActionMethodArguments');
		$mockController->expects($this->at(2))->method('initializeActionMethodValidators');
		$mockController->expects($this->at(3))->method('initializeAction');
		$mockController->expects($this->at(4))->method('initializeFooAction');
		$mockController->expects($this->at(5))->method('initializeControllerArgumentsBaseValidators');
		$mockController->expects($this->at(6))->method('mapRequestArgumentsToControllerArguments');
		$mockController->expects($this->at(7))->method('resolveView')->will($this->returnValue($mockView));
		$mockController->expects($this->at(8))->method('initializeView');
		$mockController->expects($this->at(9))->method('callActionMethod');
	
		$mockController->processRequest($mockRequest, $mockResponse);
		$this->assertSame($mockRequest, $mockController->_get('request'));
		$this->assertSame($mockResponse, $mockController->_get('response'));
	}
	
	/**
	 * @test
	 */
	public function callActionMethodAppendsStringsReturnedByActionMethodToTheResponseObject() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
	
		$mockResponse = $this->getMock('Tx_Extbase_MVC_Response', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the returned string');
	
		$mockArguments = $this->getMock('Tx_Extbase_Controller_Arguments', array('areValid'), array(), '', FALSE);
		$mockArguments->expects($this->any())->method('areValid')->will($this->returnValue(TRUE));
		
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
	 */
	public function callActionMethodRendersTheViewAutomaticallyIfTheActionReturnedNullAndAViewExists() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
	
		$mockResponse = $this->getMock('Tx_Extbase_MVC_Response', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the view output');
	
		$mockView = $this->getMock('Tx_Extbase_MVC_View_ViewInterface');
		$mockView->expects($this->once())->method('render')->will($this->returnValue('the view output'));
	
		$mockArguments = $this->getMock('Tx_Extbase_Controller_Arguments', array('areValid'), array(), '', FALSE);
		$mockArguments->expects($this->any())->method('areValid')->will($this->returnValue(TRUE));

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
	 */
	public function callActionMethodCallsTheErrorActionIfTheMappingResultsHaveErrors() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
	
		$mockResponse = $this->getMock('Tx_Extbase_MVC_Response', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the returned string');
	
		$mockArguments = $this->getMock('Tx_Extbase_Controller_Arguments', array('areValid'), array(), '', FALSE);
		$mockArguments->expects($this->any())->method('areValid')->will($this->returnValue(TRUE));
		
		$mockArgumentMappingResults = $this->getMock('Tx_Extbase_Property_MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(FALSE));
			
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->will($this->returnValue('the returned string'));
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
	 */
	public function callActionMethodPassesDefaultValuesAsArguments() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
	
		$mockResponse = $this->getMock('Tx_Extbase_MVC_Response', array(), array(), '', FALSE);
	
		$mockArguments = $this->getMock('ArrayObject', array('areValid'), array(), '', FALSE);
		$mockArguments->expects($this->any())->method('areValid')->will($this->returnValue(TRUE));
		$optionalArgument = new Tx_Extbase_MVC_Controller_Argument('name1', 'Text');
		$optionalArgument->setDefaultValue('Default value');
		$mockArguments[] = $optionalArgument;

		$mockArgumentMappingResults = $this->getMock('Tx_Extbase_Property_MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(FALSE));
				
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->with('Default value');
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('argumentsMappingResults', $mockArgumentMappingResults);
		$mockController->_call('callActionMethod');
	}
	
	/**
	 * @test
	 */
	public function resolveViewUsesFluidTemplateViewIfTemplateIsAvailable() {
		$mockControllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array(), array(), '', FALSE);

		$mockFluidTemplateView = $this->getMock('Tx_Fluid_View_TemplateView', array('setControllerContext', 'getViewHelper', 'assign', 'render', 'hasTemplate'));
		$mockFluidTemplateView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);
		$mockFluidTemplateView->expects($this->once())->method('hasTemplate')->will($this->returnValue(TRUE));

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('Tx_Fluid_View_TemplateView')->will($this->returnValue($mockFluidTemplateView));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('buildControllerContext', 'resolveViewObjectName'), array(), '', FALSE);
		$mockController->expects($this->once())->method('buildControllerContext')->will($this->returnValue($mockControllerContext));
		$mockController->_set('objectManager', $mockObjectManager);

		$this->assertSame($mockFluidTemplateView, $mockController->_call('resolveView'));
	}
	
	/**
	 * @test
	 */
	public function resolveViewPreparesTheViewSpecifiedInTheRequestObjectAndUsesTheEmptyViewIfNoneCouldBeFound() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerExtensionName')->will($this->returnValue('Foo'));
		$mockRequest->expects($this->at(1))->method('getControllerSubextensionName')->will($this->returnValue(''));
		$mockRequest->expects($this->at(1))->method('getControllerName')->will($this->returnValue('Test'));
		$mockRequest->expects($this->at(2))->method('getControllerActionName')->will($this->returnValue('list'));
		$mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('html'));

		$mockControllerContext = $this->getMock('Tx_extbase_MVC_Controller_ControllerContext', array('getRequest'), array(), '', FALSE);
		$mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$mockFluidTemplateView = $this->getMock('Tx_Extbase_MVC_View_ViewInterface', array('setControllerContext', 'getViewHelper', 'assign', 'assignMultiple', 'render', 'hasTemplate'));
		$mockFluidTemplateView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);
		$mockFluidTemplateView->expects($this->once())->method('hasTemplate')->will($this->returnValue(FALSE));
		$mockView = $this->getMock('Tx_Extbase_MVC_View_ViewInterface');
		$mockView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('Tx_Fluid_View_TemplateView')->will($this->returnValue($mockFluidTemplateView));
		$mockObjectManager->expects($this->at(1))->method('getObject')->with('Tx_Extbase_MVC_View_EmptyView')->will($this->returnValue($mockView));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('buildControllerContext'), array(), '', FALSE);
		$mockController->expects($this->once())->method('buildControllerContext')->will($this->returnValue($mockControllerContext));
		$mockController->_set('request', $mockRequest);
		$mockController->_set('objectManager', $mockObjectManager);

		$this->assertSame($mockView, $mockController->_call('resolveView'));
		}

	/**
	 * @test
	 */
	public function resolveViewObjectNameUsesViewObjectNamePatternToResolveViewObjectName() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('MyExtension'));
		$mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('MyController'));
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('myAction'));

		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('viewObjectNamePattern', 'RandomViewObjectPattern_@extension_View_@controller_@action');
	
		eval('class RandomViewObjectPattern_MyExtension_View_MyController_MyAction {}');
	
		$this->assertEquals('RandomViewObjectPattern_MyExtension_View_MyController_MyAction', $mockController->_call('resolveViewObjectName'));
	}
	
	/**
	 * @test
	 */
	public function resolveViewObjectNameReturnsDefaultViewObjectNameIfNoCustomViewCanBeFound() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
		
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('defaultViewObjectName', 'MyDefaultViewObjectName');
		
		eval('class MyDefaultViewObjectName {}');
	
		$this->assertEquals('MyDefaultViewObjectName', $mockController->_call('resolveViewObjectName'));
	}
	
	
	/**
	 * @test
	 */
	public function initializeActionMethodArgumentsRegistersArgumentsFoundInTheSignatureOfTheCurrentActionMethod() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
	
		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('addNewArgument')->with('stringArgument', 'string', TRUE);
		$mockArguments->expects($this->at(1))->method('addNewArgument')->with('integerArgument', 'integer', TRUE);
		$mockArguments->expects($this->at(2))->method('addNewArgument')->with('objectArgument', 'Tx_Foo_Bar', TRUE);
	
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction'), array(), '', FALSE);
	
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
				'type' => 'Tx_Foo_Bar'
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
	 */
	public function initializeActionMethodArgumentsRegistersOptionalArgumentsAsSuch() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request', array(), array(), '', FALSE);
	
		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('addNewArgument')->with('arg1', 'Text', TRUE);
		$mockArguments->expects($this->at(1))->method('addNewArgument')->with('arg2', 'array', FALSE, array(21));
		$mockArguments->expects($this->at(2))->method('addNewArgument')->with('arg3', 'Text', FALSE, 42);
	
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction'), array(), '', FALSE);
	
		$methodParameters = array(
			'arg1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE
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
				'allowsNull' => FALSE
			)
		);
	
		$methodTagsValues = array(
			'param' => array(
				'string $arg1',
				'array $arg2',
				'string $arg3'
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
	 */
	public function initializeActionMethodValidatorsDetectsValidateAnnotationsAndRegistersNewValidatorsForEachArgument() {
		$mockController = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_ActionController'), array('fooAction'), array(), '', FALSE);
	
		$conjunction1 = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
		$conjunction2 = $this->getMock('Tx_Extbase_Validation_Validator_ConjunctionValidator', array(), array(), '', FALSE);
	
		$validatorConjunctions = array(
			'arg1' => $conjunction1,
			'arg2' => $conjunction2
		);
	
		$mockValidatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('buildMethodArgumentsValidatorConjunctions')->with(get_class($mockController), 'fooAction')->will($this->returnValue($validatorConjunctions));
	
		$mockArgument = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array('setValidator'), array(), '', FALSE);
		$mockArgument->expects($this->at(0))->method('setValidator')->with($conjunction1);
		$mockArgument->expects($this->at(1))->method('setValidator')->with($conjunction2);
		
		$mockArguments = $this->getMock('Tx_Extbase_MVC_Controller_Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('offsetExists')->with('arg1')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->at(1))->method('offsetGet')->with('arg1')->will($this->returnValue($mockArgument));
		$mockArguments->expects($this->at(2))->method('offsetExists')->with('arg2')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->at(3))->method('offsetGet')->with('arg2')->will($this->returnValue($mockArgument));
		
		$mockReflectionService = $this->getMock('Tx_Extbase_Reflection_Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue(array('tag' => 'lorem ipsum')));
		
		$mockController->_set('validatorResolver', $mockValidatorResolver);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('reflectionService', $mockReflectionService);
		$mockController->_call('initializeActionMethodValidators');
	}

}
?>