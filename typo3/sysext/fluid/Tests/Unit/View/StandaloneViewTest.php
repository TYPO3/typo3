<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\View;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
 */
class StandaloneViewTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \TYPO3\CMS\Fluid\View\StandaloneView
	 */
	protected $view;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface
	 */
	protected $mockRenderingContext;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer
	 */
	protected $mockViewHelperVariableContainer;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 */
	protected $mockControllerContext;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Parser\TemplateParser
	 */
	protected $mockTemplateParser;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Request
	 */
	protected $mockRequest;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 */
	protected $mockUriBuilder;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface
	 */
	protected $mockParsedTemplate;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $mockConfigurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer
	 */
	protected $mockFlashMessageContainer;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $mockContentObject;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler
	 */
	protected $mockTemplateCompiler;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 */
	public function setUp() {
		$this->view = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', array('dummy'), array(), '', FALSE);
		$this->mockTemplateParser = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser');
		$this->mockParsedTemplate = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsedTemplateInterface');
		$this->mockTemplateParser->expects($this->any())->method('parse')->will($this->returnValue($this->mockParsedTemplate));
		$this->mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$this->mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback(array($this, 'objectManagerCallback')));
		$this->mockRequest = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request');
		$this->mockUriBuilder = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
		$this->mockFlashMessageContainer = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\FlashMessageContainer');
		$this->mockContentObject = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$this->mockControllerContext = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext');
		$this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockRequest));
		$this->mockViewHelperVariableContainer = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\ViewHelperVariableContainer');
		$this->mockRenderingContext = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContext');
		$this->mockRenderingContext->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));
		$this->mockRenderingContext->expects($this->any())->method('getViewHelperVariableContainer')->will($this->returnValue($this->mockViewHelperVariableContainer));
		$this->view->injectTemplateParser($this->mockTemplateParser);
		$this->view->injectObjectManager($this->mockObjectManager);
		$this->view->setRenderingContext($this->mockRenderingContext);
		$this->mockTemplateCompiler = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Compiler\\TemplateCompiler');
		$this->view->_set('templateCompiler', $this->mockTemplateCompiler);
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager', $this->mockObjectManager);
		\TYPO3\CMS\Core\Utility\GeneralUtility::addInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer', $this->mockContentObject);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * @param string $className
	 * @return object
	 */
	public function objectManagerCallback($className) {
		switch ($className) {
			case 'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface':
				return $this->mockConfigurationManager;
			case 'TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser':
				return $this->mockTemplateParser;
			case 'TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContext':
				return $this->mockRenderingContext;
			case 'TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request':
				return $this->mockRequest;
			case 'TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder':
				return $this->mockUriBuilder;
			case 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext':
				return $this->mockControllerContext;
			case 'TYPO3\\CMS\\Extbase\\Mvc\\Controller\\FlashMessageContainer':
				return $this->mockFlashMessageContainer;
			case 'TYPO3\\CMS\\Fluid\\Core\\Compiler\\TemplateCompiler':
				return $this->mockTemplateCompiler;
		}
	}

	/**
	 * @test
	 */
	public function constructorSetsSpecifiedContentObject() {
		$mockContentObject = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		// FIXME should be compared with identicalTo() - but that does not seem to work
		$this->mockConfigurationManager->expects($this->once())->method('setContentObject')->with($this->equalTo($this->mockContentObject));
		new \TYPO3\CMS\Fluid\View\StandaloneView($mockContentObject);
	}

	/**
	 * @test
	 */
	public function constructorCreatesContentObjectIfItIsNotSpecified() {
		// FIXME should be compared with identicalTo() - but that does not seem to work
		$this->mockConfigurationManager->expects($this->once())->method('setContentObject')->with($this->equalTo($this->mockContentObject));
		new \TYPO3\CMS\Fluid\View\StandaloneView();
	}

	/**
	 * @test
	 */
	public function constructorSetsRequestUri() {
		$expectedRequestUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
		$this->mockRequest->expects($this->once())->method('setRequestURI')->with($expectedRequestUri);
		new \TYPO3\CMS\Fluid\View\StandaloneView();
	}

	/**
	 * @test
	 */
	public function constructorSetsBaseUri() {
		$expectedBaseUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		$this->mockRequest->expects($this->once())->method('setBaseURI')->with($expectedBaseUri);
		new \TYPO3\CMS\Fluid\View\StandaloneView();
	}

	/**
	 * @test
	 */
	public function constructorInjectsRequestToUriBuilder() {
		$this->mockUriBuilder->expects($this->once())->method('setRequest')->with($this->mockRequest);
		new \TYPO3\CMS\Fluid\View\StandaloneView();
	}

	/**
	 * @test
	 */
	public function constructorInjectsRequestToControllerContext() {
		$this->mockControllerContext->expects($this->once())->method('setRequest')->with($this->mockRequest);
		new \TYPO3\CMS\Fluid\View\StandaloneView();
	}

	/**
	 * @test
	 */
	public function constructorInjectsUriBuilderToControllerContext() {
		$this->mockControllerContext->expects($this->once())->method('setUriBuilder')->with($this->mockUriBuilder);
		new \TYPO3\CMS\Fluid\View\StandaloneView();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	public function renderThrowsExceptionIfTemplateIsNotSpecified() {
		$this->view->render();
	}

	/**
	 * @test
	 */
	public function renderPassesSpecifiedTemplateSourceToTemplateParser() {
		$this->view->setTemplateSource('The Template Source');
		$this->mockTemplateParser->expects($this->once())->method('parse')->with('The Template Source');
		$this->view->render();
	}

	/**
	 * @test
	 */
	public function renderLoadsSpecifiedTemplateFileAndPassesSourceToTemplateParser() {
		$templatePathAndFilename = __DIR__ . '/Fixtures/StandaloneViewFixture.html';
		$expectedResult = file_get_contents($templatePathAndFilename);
		$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		$this->mockTemplateParser->expects($this->once())->method('parse')->with($expectedResult);
		$this->view->render();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	public function renderThrowsExceptionIfSpecifiedTemplateFileDoesNotExist() {
		$this->view->setTemplatePathAndFilename('NonExistingTemplatePath');
		@$this->view->render();
	}

	/**
	 * @test
	 */
	public function setFormatSetsRequestFormat() {
		$this->mockRequest->expects($this->once())->method('setFormat')->with('xml');
		$this->view->setFormat('xml');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	public function getLayoutRootPathThrowsExceptionIfLayoutRootPathAndTemplatePathAreNotSpecified() {
		$this->view->getLayoutRootPath();
	}

	/**
	 * @test
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
	 * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	public function getLayoutSourceThrowsExceptionIfLayoutRootPathDoesNotExist() {
		$this->view->setLayoutRootPath('some/non/existing/Path');
		$this->view->_call('getLayoutSource');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	public function getLayoutSourceThrowsExceptionIfLayoutFileDoesNotExist() {
		$layoutRootPath = __DIR__ . '/Fixtures';
		$this->view->setLayoutRootPath($layoutRootPath);
		$this->view->_call('getLayoutSource', 'NonExistingLayout');
	}

	/**
	 * @test
	 */
	public function getLayoutSourceReturnsContentOfLayoutFileForTheDefaultFormat() {
		$layoutRootPath = __DIR__ . '/Fixtures';
		$this->view->setLayoutRootPath($layoutRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('html'));
		$expectedResult = file_get_contents($layoutRootPath . '/LayoutFixture.html');
		$actualResult = $this->view->_call('getLayoutSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getLayoutSourceReturnsContentOfLayoutFileForTheSpecifiedFormat() {
		$layoutRootPath = __DIR__ . '/Fixtures';
		$this->view->setLayoutRootPath($layoutRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('xml'));
		$expectedResult = file_get_contents($layoutRootPath . '/LayoutFixture.xml');
		$actualResult = $this->view->_call('getLayoutSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getLayoutSourceReturnsContentOfDefaultLayoutFileIfNoLayoutExistsForTheSpecifiedFormat() {
		$layoutRootPath = __DIR__ . '/Fixtures';
		$this->view->setLayoutRootPath($layoutRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('foo'));
		$expectedResult = file_get_contents($layoutRootPath . '/LayoutFixture');
		$actualResult = $this->view->_call('getLayoutSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	public function getPartialRootPathThrowsExceptionIfPartialRootPathAndTemplatePathAreNotSpecified() {
		$this->view->getPartialRootPath();
	}

	/**
	 * @test
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
	 * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	public function getPartialSourceThrowsExceptionIfPartialRootPathDoesNotExist() {
		$this->view->setPartialRootPath('some/non/existing/Path');
		$this->view->_call('getPartialSource', 'SomePartial');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	public function getPartialSourceThrowsExceptionIfPartialFileDoesNotExist() {
		$partialRootPath = __DIR__ . '/Fixtures';
		$this->view->setPartialRootPath($partialRootPath);
		$this->view->_call('getPartialSource', 'NonExistingPartial');
	}

	/**
	 * @test
	 */
	public function getPartialSourceReturnsContentOfPartialFileForTheDefaultFormat() {
		$partialRootPath = __DIR__ . '/Fixtures';
		$this->view->setPartialRootPath($partialRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('html'));
		$expectedResult = file_get_contents($partialRootPath . '/LayoutFixture.html');
		$actualResult = $this->view->_call('getPartialSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getPartialSourceReturnsContentOfPartialFileForTheSpecifiedFormat() {
		$partialRootPath = __DIR__ . '/Fixtures';
		$this->view->setPartialRootPath($partialRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('xml'));
		$expectedResult = file_get_contents($partialRootPath . '/LayoutFixture.xml');
		$actualResult = $this->view->_call('getPartialSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getPartialSourceReturnsContentOfDefaultPartialFileIfNoPartialExistsForTheSpecifiedFormat() {
		$partialRootPath = __DIR__ . '/Fixtures';
		$this->view->setPartialRootPath($partialRootPath);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('foo'));
		$expectedResult = file_get_contents($partialRootPath . '/LayoutFixture');
		$actualResult = $this->view->_call('getPartialSource', 'LayoutFixture');
		$this->assertEquals($expectedResult, $actualResult);
	}
}

?>
