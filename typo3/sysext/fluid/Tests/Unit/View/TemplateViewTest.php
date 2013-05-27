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

include_once(dirname(__FILE__) . '/Fixtures/TransparentSyntaxTreeNode.php');
include_once(dirname(__FILE__) . '/Fixtures/TemplateViewFixture.php');

/**
 * Testcase for the TemplateView
 *
 * @version $Id: TemplateViewTest.php 4334 2010-05-28 10:06:01Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_View_TemplateViewTest extends Tx_Extbase_BaseTestCase {

	public function initializeViewSetsParserConfiguration() {
		$this->markTestSkipped('incomplete, needs to be written!');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function expandGenericPathPatternWorksWithBubblingDisabledAndFormatNotOptional() {
		$mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', NULL, 'My', 'html');

		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('getTemplateRootPath', 'getPartialRootPath', 'getLayoutRootPath'), array(), '', FALSE);
		$templateView->_set('controllerContext', $mockControllerContext);
		$templateView->expects($this->any())->method('getTemplateRootPath')->will($this->returnValue('Resources/Private/'));

		$expected = array('Resources/Private/Templates/My/@action.html');
		$actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', FALSE, FALSE);
		$this->assertEquals($expected, $actual);
	}


	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function expandGenericPathPatternWorksWithSubpackageAndBubblingDisabledAndFormatNotOptional() { $this->markTestIncomplete("Not implemented in v4");
		$mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'MySubPackage', 'My', 'html');

		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('getTemplateRootPath', 'getPartialRootPath', 'getLayoutRootPath'), array(), '', FALSE);
		$templateView->_set('controllerContext', $mockControllerContext);
		$templateView->expects($this->any())->method('getTemplateRootPath')->will($this->returnValue('Resources/Private/'));
		$actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', FALSE, FALSE);

		$expected = array(
			'Resources/Private/Templates/MySubPackage/My/@action.html'
		);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function expandGenericPathPatternWorksWithSubpackageAndBubblingDisabledAndFormatOptional() { $this->markTestIncomplete("Not implemented in v4");
		$mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'MySubPackage', 'My', 'html');

		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('getTemplateRootPath', 'getPartialRootPath', 'getLayoutRootPath'), array(), '', FALSE);
		$templateView->_set('controllerContext', $mockControllerContext);
		$templateView->expects($this->any())->method('getTemplateRootPath')->will($this->returnValue('Resources/Private/'));
		$actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', FALSE, TRUE);

		$expected = array(
			'Resources/Private/Templates/MySubPackage/My/@action.html',
			'Resources/Private/Templates/MySubPackage/My/@action'
		);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function expandGenericPathPatternWorksWithSubpackageAndBubblingEnabledAndFormatOptional() { $this->markTestIncomplete("Not implemented in v4");
		$mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'MySubPackage', 'My', 'html');

		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('getTemplateRootPath', 'getPartialRootPath', 'getLayoutRootPath'), array(), '', FALSE);
		$templateView->_set('controllerContext', $mockControllerContext);
		$templateView->expects($this->any())->method('getTemplateRootPath')->will($this->returnValue('Resources/Private/'));
		$actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', TRUE, TRUE);

		$expected = array(
			'Resources/Private/Templates/MySubPackage/My/@action.html',
			'Resources/Private/Templates/MySubPackage/My/@action',
			'Resources/Private/Templates/MySubPackage/@action.html',
			'Resources/Private/Templates/MySubPackage/@action',
			'Resources/Private/Templates/@action.html',
			'Resources/Private/Templates/@action',
		);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Helper to build mock controller context needed to test expandGenericPathPattern.
	 *
	 * @param $packageKey
	 * @param $subPackageKey
	 * @param $controllerClassName
	 * @param $format
	 *
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setupMockControllerContextForPathResolving($packageKey, $subPackageKey, $controllerName, $format) {
     	$controllerObjectName = 'Tx_' . $packageKey . '_' . ($subPackageKey !== '' ? '_' . $subPackageKey . '_' : '') . 'Controller_' . $controllerName . 'Controller';

		$mockRequest = $this->getMock('Tx_Extbase_MVC_Request');
		$mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue($packageKey));
		$mockRequest->expects($this->any())->method('getControllerSubPackageKey')->will($this->returnValue($subPackageKey));
		$mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue($controllerName));
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
		$mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue($format));

		$mockControllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array('getRequest'), array(), '', FALSE);
		$mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		return $mockControllerContext;
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTemplateRootPathReturnsUserSpecifiedTemplatePath() {
		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('dummy'), array(), '', FALSE);
		$templateView->setTemplateRootPath('/foo/bar');
		$expected = '/foo/bar';
		$actual = $templateView->_call('getTemplateRootPath');
		$this->assertEquals($expected, $actual, 'A set template root path was not returned correctly.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPartialRootPathReturnsUserSpecifiedPartialPath() {
		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('dummy'), array(), '', FALSE);
		$templateView->setPartialRootPath('/foo/bar');
		$expected = '/foo/bar';
		$actual = $templateView->_call('getPartialRootPath');
		$this->assertEquals($expected, $actual, 'A set partial root path was not returned correctly.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getLayoutRootPathReturnsUserSpecifiedPartialPath() {
		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('dummy'), array(), '', FALSE);
		$templateView->setLayoutRootPath('/foo/bar');
		$expected = '/foo/bar';
		$actual = $templateView->_call('getLayoutRootPath');
		$this->assertEquals($expected, $actual, 'A set partial root path was not returned correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderCallsRenderOnParsedTemplateInterface() {
		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('parseTemplate', 'resolveTemplatePathAndFilename', 'buildParserConfiguration'), array(), '', FALSE);
		$parsedTemplate = $this->getMock('Tx_Fluid_Core_Parser_ParsedTemplateInterface');
		$objectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$controllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array(), array(), '', FALSE);

		$variableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer');
		$renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContext', array(), array(), '', FALSE);

		$viewHelperVariableContainer = $this->getMock('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$objectManager->expects($this->exactly(3))->method('create')->will($this->onConsecutiveCalls($variableContainer, $renderingContext, $viewHelperVariableContainer));

		$templateView->_set('objectManager', $objectManager);
		$templateView->injectTemplateParser($this->getMock('Tx_Fluid_Core_Parser_TemplateParser'));
		$templateView->setControllerContext($controllerContext);

		$templateView->expects($this->once())->method('parseTemplate')->will($this->returnValue($parsedTemplate));

			// Real expectations
		$parsedTemplate->expects($this->once())->method('render')->with($renderingContext)->will($this->returnValue('Hello World'));

		$this->assertEquals('Hello World', $templateView->render(), 'The output of the ParsedTemplates render Method is not returned by the TemplateView');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseTemplateReadsTheGivenTemplateAndReturnsTheParsedResult() {
		$mockTemplateParser = $this->getMock('Tx_Fluid_Core_Parser_TemplateParser', array('parse'));
		$mockTemplateParser->expects($this->once())->method('parse')->with('Unparsed Template')->will($this->returnValue('Parsed Template'));

		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('dummy'), array(), '', FALSE);
		$templateView->injectTemplateParser($mockTemplateParser);

		$parsedTemplate = $templateView->_call('parseTemplate', dirname(__FILE__) . '/Fixtures/UnparsedTemplateFixture.html');
		$this->assertSame('Parsed Template', $parsedTemplate);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseTemplateThrowsAnExceptionIfTheSpecifiedTemplateResourceDoesNotExist() {
		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('dummy'), array(), '', FALSE);
		$templateView->_call('parseTemplate', 'foo');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function pathToPartialIsResolvedCorrectly() {
		$this->markTestSkipped('Needs proper implementation.');
		$mockRequest = $this->getMock('Tx_Fluid_MVC_Request', array('getControllerPackageKey', ''));
		$mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('DummyPackageKey'));
		$mockControllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array('getRequest'));
		$mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$mockPackage = $this->getMock('Tx_Fluid_Package_PackageInterface', array('getPackagePath'));
		$mockPackage->expects($this->any())->method('getPackagePath')->will($this->returnValue('/ExamplePackagePath/'));
		$mockPackageManager = $this->getMock('Tx_Fluid_Package_PackageManagerInterface', array('getPackage'));
		$mockPackageManager->expects($this->any())->method('getPackage')->with('DummyPackageKey')->will($this->returnValue($mockPackage));

		vfsStreamWrapper::register();
		$mockRootDirectory = vfsStreamDirectory::create('ExamplePackagePath/Resources/Private/Partials');
		$mockRootDirectory->getChild('Resources/Private/Partials')->addChild('Partials');
		vfsStreamWrapper::setRoot($mockRootDirectory);

		$this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array(''), array(), '', FALSE);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function viewIsPlacedInVariableContainer() {
		$this->markTestSkipped('view will be placed in ViewHelperContext soon');
		$packageManager = t3lib_div::makeInstance('Tx_Fluid_Package_PackageManagerInterface');
		$resourceManager = t3lib_div::makeInstance('Tx_Fluid_Resource_ResourceManager');

		$syntaxTreeNode = new Tx_Fluid_View_Fixture_TransparentSyntaxTreeNode();

		$parsingState = new Tx_Fluid_Core_Parser_ParsingState();
		$parsingState->setRootNode($syntaxTreeNode);

		$templateParserMock = $this->getMock('Tx_Fluid_Core_Parser_TemplateParser', array('parse'));
		$templateParserMock->expects($this->any())->method('parse')->will($this->returnValue($parsingState));

		$mockRequest = $this->getMock('Tx_Extbase_MVC_Request');
		$mockRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('Tx_Fluid_Foo_Bar_Controller_BazController'));
		$mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('Fluid'));
		$mockControllerContext = $this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array('getRequest'), array(), '', FALSE);
		$mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateView = new Tx_Fluid_View_Fixture_TemplateViewFixture(new Tx_Fluid_Compatibility_ObjectManager(), $packageManager, $resourceManager, new Tx_Fluid_Compatibility_ObjectManager());
		$templateView->injectTemplateParser($templateParserMock);
		$templateView->setTemplatePathAndFilename(dirname(__FILE__) . '/Fixtures/TemplateViewSectionFixture.html');
		$templateView->setLayoutPathAndFilename(dirname(__FILE__) . '/Fixtures/LayoutFixture.html');
		$templateView->setControllerContext($mockControllerContext);
		$templateView->initializeObject();
		$templateView->addVariable('name', 'value');
		$templateView->render();

		$this->assertSame($templateView, $syntaxTreeNode->variableContainer->get('view'), 'The view has not been placed in the variable container.');
		$this->assertEquals('value', $syntaxTreeNode->variableContainer->get('name'), 'Context variable has been set.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function renderSingleSectionWorks() {
		$this->markTestSkipped('needs refactoring - this is a functional test with too many side effects');
		$templateView = new Tx_Fluid_View_TemplateView();
		$templateView->setTemplatePathAndFilename(dirname(__FILE__) . '/Fixtures/TemplateViewSectionFixture.html');
		$this->assertEquals($templateView->renderSection('mySection'), 'Output', 'Specific section was not rendered correctly!');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function layoutEngineMergesTemplateAndLayout() {
		$this->markTestSkipped('needs refactoring - this is a functional test with too many side effects');
		$templateView = new Tx_Fluid_View_TemplateView();
		$templateView->setTemplatePathAndFilename(dirname(__FILE__) . '/Fixtures/TemplateViewSectionFixture.html');
		$templateView->setLayoutPathAndFilename(dirname(__FILE__) . '/Fixtures/LayoutFixture.html');
		$this->assertEquals($templateView->renderWithLayout('LayoutFixture'), '<div>Output</div>', 'Specific section was not rendered correctly!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveTemplatePathAndFilenameChecksDifferentPathPatternsAndReturnsTheFirstPathWhichExists() {
		$this->markTestSkipped('vfs not yet supported in v4');
		vfsStreamWrapper::register();
		mkdir('vfs://MyTemplates');
		file_put_contents('vfs://MyTemplates/MyCoolAction.html', '');

		$paths = array(
			 'vfs://NonExistantDir/UnknowFile.html',
			 'vfs://MyTemplates/@action.html'
		);

		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('expandGenericPathPattern'), array(), '', FALSE);
		$templateView->expects($this->once())->method('expandGenericPathPattern')->with('@templateRoot/@subpackage/@controller/@action.@format', FALSE, FALSE)->will($this->returnValue($paths));

		$templateView->setTemplateRootPath('MyTemplates');
		$templateView->setPartialRootPath('MyPartials');
		$templateView->setLayoutRootPath('MyLayouts');

		$this->assertSame('vfs://MyTemplates/MyCoolAction.html', $templateView->_call('resolveTemplatePathAndFilename', 'myCoolAction'));
		
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveTemplatePathAndFilenameReturnsTheExplicitlyConfiguredTemplatePathAndFilename() {
		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('dummy'), array(), '', FALSE);
		$templateView->_set('templatePathAndFilename', 'Foo/Bar/Baz.html');

		$this->assertSame('Foo/Bar/Baz.html', $templateView->_call('resolveTemplatePathAndFilename'));
  	}
}

?>