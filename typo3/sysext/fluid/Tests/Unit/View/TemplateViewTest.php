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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_View_TemplateViewTest extends Tx_Extbase_BaseTestCase {

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
	public function pathToPartialIsResolvedCorrectly() {
		$this->markTestSkipped('Needs to be finished');
		vfsStreamWrapper::register();
		mkdir('vfs://MyTemplates');
		file_put_contents('vfs://MyTemplates/MyCoolAction.html', 'contentsOfMyCoolAction');
		$mockRootDirectory = vfsStreamDirectory::create('ExamplePackagePath/Resources/Private/Partials');
		$mockRootDirectory->getChild('Resources/Private/Partials')->addChild('Partials');
		vfsStreamWrapper::setRoot($mockRootDirectory);

		$this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array(''), array(), '', FALSE);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveTemplatePathAndFilenameChecksDifferentPathPatternsAndReturnsTheFirstPathWhichExists() { $this->markTestIncomplete("Not implemented in v4");
		vfsStreamWrapper::register();
		mkdir('vfs://MyTemplates');
		file_put_contents('vfs://MyTemplates/MyCoolAction.html', 'contentsOfMyCoolAction');

		$paths = array(
			 'vfs://NonExistantDir/UnknowFile.html',
			 'vfs://MyTemplates/@action.html'
		);

		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('expandGenericPathPattern'), array(), '', FALSE);
		$templateView->expects($this->once())->method('expandGenericPathPattern')->with('@templateRoot/@subpackage/@controller/@action.@format', FALSE, FALSE)->will($this->returnValue($paths));

		$templateView->setTemplateRootPath('MyTemplates');
		$templateView->setPartialRootPath('MyPartials');
		$templateView->setLayoutRootPath('MyLayouts');

		$this->assertSame('contentsOfMyCoolAction', $templateView->_call('getTemplateSource', 'myCoolAction'));

	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveTemplatePathAndFilenameReturnsTheExplicitlyConfiguredTemplatePathAndFilename() { $this->markTestIncomplete("Not implemented in v4");
		vfsStreamWrapper::register();
		mkdir('vfs://MyTemplates');
		file_put_contents('vfs://MyTemplates/MyCoolAction.html', 'contentsOfMyCoolAction');

		$templateView = $this->getAccessibleMock('Tx_Fluid_View_TemplateView', array('dummy'), array(), '', FALSE);
		$templateView->_set('templatePathAndFilename', 'vfs://MyTemplates/MyCoolAction.html');

		$this->assertSame('contentsOfMyCoolAction', $templateView->_call('getTemplateSource'));
  	}
}

?>