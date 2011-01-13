<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the StandaloneView
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_View_StandaloneViewTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @var Tx_Fluid_View_StandaloneView
	 */
	protected $view;

	/**
	 * @var Tx_Fluid_Core_Rendering_RenderingContextInterface
	 */
	protected $mockRenderingContext;

	/**
	 * @var Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer
	 */
	protected $mockViewHelperVariableContainer;

	/**
	 * @var Tx_Extbase_MVC_Controller_ControllerContext
	 */
	protected $mockControllerContext;

	/**
	 * @var Tx_Fluid_Core_Parser_TemplateParser
	 */
	protected $mockTemplateParser;

	/**
	 * @var Tx_Extbase_Object_ObjectManager
	 */
	protected $mockObjectManager;

	/**
	 * @var Tx_Extbase_MVC_Web_Request
	 */
	protected $mockRequest;

	/**
	 * @var Tx_Extbase_MVC_Web_Routing_UriBuilder
	 */
	protected $mockUriBuilder;

	/**
	 * @var Tx_Fluid_Core_Parser_ParsedTemplateInterface
	 */
	protected $mockParsedTemplate;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $mockConfigurationManager;

	/**
	 * @var Tx_Extbase_MVC_Controller_FlashMessages
	 */
	protected $mockFlashMessages;

	/**
	 * @var tslib_cObj
	 */
	protected $mockContentObject;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 */
	public function setUp() {
		$this->view = $this->getAccessibleMock('Tx_Fluid_View_StandaloneView', array('dummy'), array(), '', FALSE);

		$this->mockTemplateParser = $this->getMock('Tx_Fluid_Core_Parser_TemplateParser');
		$this->mockParsedTemplate = $this->getMock('Tx_Fluid_Core_Parser_ParsedTemplateInterface');
		$this->mockTemplateParser->expects($this->any())->method('parse')->will($this->returnValue($this->mockParsedTemplate));

		$this->mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');

		$this->mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManager');
		$this->mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallback')));

		$this->mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$this->mockUriBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_UriBuilder');
		$this->mockFlashMessages = $this->getMock('Tx_Extbase_MVC_Controller_FlashMessages');
		$this->mockContentObject = $this->getMock('tslib_cObj');

		$this->mockControllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext');
		$this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockRequest));

		$this->mockViewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');

		$this->mockRenderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContextInterface');
		$this->mockRenderingContext->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));
		$this->mockRenderingContext->expects($this->any())->method('getViewHelperVariableContainer')->will($this->returnValue($this->mockViewHelperVariableContainer));

		$this->view->injectTemplateParser($this->mockTemplateParser);
		$this->view->injectObjectManager($this->mockObjectManager);
		$this->view->setRenderingContext($this->mockRenderingContext);

		t3lib_div::setSingletonInstance('Tx_Extbase_Object_ObjectManager', $this->mockObjectManager);
		t3lib_div::addInstance('tslib_cObj', $this->mockContentObject);
	}

	/**
	 * @param string $className
	 * @return object
	 */
	public function objectManagerCallback($className) {
		switch($className) {
			case 'Tx_Extbase_Configuration_ConfigurationManagerInterface':
				return $this->mockConfigurationManager;
			case 'Tx_Fluid_Core_Parser_TemplateParser':
				return $this->mockTemplateParser;
			case 'Tx_Fluid_Core_Rendering_RenderingContext':
				return $this->mockRenderingContext;
			case 'Tx_Extbase_MVC_Web_Request':
				return $this->mockRequest;
			case 'Tx_Extbase_MVC_Web_Routing_UriBuilder':
				return $this->mockUriBuilder;
			case 'Tx_Extbase_MVC_Controller_ControllerContext':
				return $this->mockControllerContext;
			case 'Tx_Extbase_MVC_Controller_FlashMessages':
				return $this->mockFlashMessages;
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsSpecifiedContentObject() {
		$mockContentObject = $this->getMock('tslib_cObj');
		// FIXME should be compared with identicalTo() - but that does not seem to work
		$this->mockConfigurationManager->expects($this->once())->method('setContentObject')->with($this->equalTo($this->mockContentObject));

		new Tx_Fluid_View_StandaloneView($mockContentObject);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorCreatesContentObjectIfItIsNotSpecified() {
		// FIXME should be compared with identicalTo() - but that does not seem to work
		$this->mockConfigurationManager->expects($this->once())->method('setContentObject')->with($this->equalTo($this->mockContentObject));

		new Tx_Fluid_View_StandaloneView();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsRequestUri() {
		$expectedRequestUri = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
		$this->mockRequest->expects($this->once())->method('setRequestURI')->with($expectedRequestUri);
		new Tx_Fluid_View_StandaloneView();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsBaseUri() {
		$expectedBaseUri = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$this->mockRequest->expects($this->once())->method('setBaseURI')->with($expectedBaseUri);
		new Tx_Fluid_View_StandaloneView();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorInjectsRequestToUriBuilder() {
		$this->mockUriBuilder->expects($this->once())->method('setRequest')->with($this->mockRequest);
		new Tx_Fluid_View_StandaloneView();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorInjectsRequestToControllerContext() {
		$this->mockControllerContext->expects($this->once())->method('setRequest')->with($this->mockRequest);
		new Tx_Fluid_View_StandaloneView();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorInjectsUriBuilderToControllerContext() {
		$this->mockControllerContext->expects($this->once())->method('setUriBuilder')->with($this->mockUriBuilder);
		new Tx_Fluid_View_StandaloneView();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorInjectsFlashMessageContainerToControllerContext() {
		$this->mockControllerContext->expects($this->once())->method('setFlashMessageContainer')->with($this->mockFlashMessages);
		new Tx_Fluid_View_StandaloneView();
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThrowsExceptionIfTemplateIsNotSpecified() {
		$this->view->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderPassesSpecifiedTemplateSourceToTemplateParser() {
		$this->view->setTemplateSource('The Template Source');
		$this->mockTemplateParser->expects($this->once())->method('parse')->with('The Template Source');
		$this->view->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderLoadsSpecifiedTemplateFileAndPassesSourceToTemplateParser() {
		$templatePathAndFilename = dirname(__FILE__) . '/Fixtures/StandaloneViewFixture.html';
		$expectedResult = file_get_contents($templatePathAndFilename);
		$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		$this->mockTemplateParser->expects($this->once())->method('parse')->with($expectedResult);
		$this->view->render();
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThrowsExceptionIfSpecifiedTemplateFileDoesNotExist() {
		$this->view->setTemplatePathAndFilename('NonExistingTemplatePath');
		$this->view->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setFormatSetsRequestFormat() {
		$this->mockRequest->expects($this->once())->method('setFormat')->with('xml');
		$this->view->setFormat('xml');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getLayoutRootPathThrowsExceptionIfLayoutRootPathAndTemplatePathAreNotSpecified() {
		$this->view->getLayoutRootPath();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getLayoutRootPathReturnsSpecifiedLayoutRootPathByDefault() {
		$templatePathAndFilename = 'some/template/RootPath/SomeTemplate.html';
		$layoutRootPath = 'some/layout/RootPath';
		$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		$this->view->setLayoutRootPath($layoutRootPath);
		$actualResult = $this->view->getLayoutRootPath();
	    $this->assertEquals($layoutRootPath, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getLayoutRootPathReturnsDefaultPathIfNoLayoutRootPathIsSpecified() {
		$templatePathAndFilename = 'some/template/RootPath/SomeTemplate.html';
		$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		$expectedResult = 'some/template/RootPath/Layouts';
		$actualResult = $this->view->getLayoutRootPath();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getLayoutSourceThrowsExceptionIfLayoutRootPathDoesNotExist() {
		$this->view->setLayoutRootPath('some/non/existing/Path');
		$this->view->_call('getLayoutSource');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getLayoutSourceThrowsExceptionIfLayoutFileDoesNotExist() {
		$layoutRootPath = dirname(__FILE__) . '/Fixtures';
		$this->view->setLayoutRootPath($layoutRootPath);
		$this->view->_call('getLayoutSource', 'NonExistingLayout');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getLayoutSourceReturnsContentOfLayoutFileForTheDefaultFormat() {
		$layoutRootPath = dirname(__FILE__) . '/Fixtures';
		$this->view->setLayoutRootPath($layoutRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('html'));
		$expectedResult = file_get_contents($layoutRootPath . '/LayoutFixture.html');
		$actualResult = $this->view->_call('getLayoutSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getLayoutSourceReturnsContentOfLayoutFileForTheSpecifiedFormat() {
		$layoutRootPath = dirname(__FILE__) . '/Fixtures';
		$this->view->setLayoutRootPath($layoutRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('xml'));
		$expectedResult = file_get_contents($layoutRootPath . '/LayoutFixture.xml');
		$actualResult = $this->view->_call('getLayoutSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getLayoutSourceReturnsContentOfDefaultLayoutFileIfNoLayoutExistsForTheSpecifiedFormat() {
		$layoutRootPath = dirname(__FILE__) . '/Fixtures';
		$this->view->setLayoutRootPath($layoutRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('foo'));
		$expectedResult = file_get_contents($layoutRootPath . '/LayoutFixture');
		$actualResult = $this->view->_call('getLayoutSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPartialRootPathThrowsExceptionIfPartialRootPathAndTemplatePathAreNotSpecified() {
		$this->view->getPartialRootPath();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPartialRootPathReturnsSpecifiedPartialRootPathByDefault() {
		$templatePathAndFilename = 'some/template/RootPath/SomeTemplate.html';
		$partialRootPath = 'some/partial/RootPath';
		$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		$this->view->setPartialRootPath($partialRootPath);
		$actualResult = $this->view->getPartialRootPath();
	    $this->assertEquals($partialRootPath, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPartialRootPathReturnsDefaultPathIfNoPartialRootPathIsSpecified() {
		$templatePathAndFilename = 'some/template/RootPath/SomeTemplate.html';
		$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		$expectedResult = 'some/template/RootPath/Partials';
		$actualResult = $this->view->getPartialRootPath();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPartialSourceThrowsExceptionIfPartialRootPathDoesNotExist() {
		$this->view->setPartialRootPath('some/non/existing/Path');
		$this->view->_call('getPartialSource');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPartialSourceThrowsExceptionIfPartialFileDoesNotExist() {
		$partialRootPath = dirname(__FILE__) . '/Fixtures';
		$this->view->setPartialRootPath($partialRootPath);
		$this->view->_call('getPartialSource', 'NonExistingPartial');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPartialSourceReturnsContentOfPartialFileForTheDefaultFormat() {
		$partialRootPath = dirname(__FILE__) . '/Fixtures';
		$this->view->setPartialRootPath($partialRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('html'));
		$expectedResult = file_get_contents($partialRootPath . '/LayoutFixture.html');
		$actualResult = $this->view->_call('getPartialSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPartialSourceReturnsContentOfPartialFileForTheSpecifiedFormat() {
		$partialRootPath = dirname(__FILE__) . '/Fixtures';
		$this->view->setPartialRootPath($partialRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('xml'));
		$expectedResult = file_get_contents($partialRootPath . '/LayoutFixture.xml');
		$actualResult = $this->view->_call('getPartialSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPartialSourceReturnsContentOfDefaultPartialFileIfNoPartialExistsForTheSpecifiedFormat() {
		$partialRootPath = dirname(__FILE__) . '/Fixtures';
		$this->view->setPartialRootPath($partialRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('foo'));
		$expectedResult = file_get_contents($partialRootPath . '/LayoutFixture');
		$actualResult = $this->view->_call('getPartialSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>