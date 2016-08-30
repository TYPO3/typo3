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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3\CMS\Fluid\Core\Parser\TemplateParser;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Test case
 */
class StandaloneViewTest extends UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var StandaloneView|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $view;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRenderingContext;

    /**
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockViewHelperVariableContainer;

    /**
     * @var ControllerContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockControllerContext;

    /**
     * @var TemplateParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockTemplateParser;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRequest;

    /**
     * @var UriBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockUriBuilder;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockParsedTemplate;

    /**
     * @var ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConfigurationManager;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockContentObject;

    /**
     * @var TemplateCompiler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockTemplateCompiler;

    /**
     * Sets up this test case
     *
     * @return void
     */
    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->view = $this->getAccessibleMock(\TYPO3\CMS\Fluid\View\StandaloneView::class, ['testFileExistence', 'buildParserConfiguration'], [], '', false);
        $this->mockTemplateParser = $this->getMock(TemplateParser::class);
        $this->mockParsedTemplate = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface::class);
        $this->mockTemplateParser->expects($this->any())->method('parse')->will($this->returnValue($this->mockParsedTemplate));
        $this->mockConfigurationManager = $this->getMock(ConfigurationManagerInterface::class);
        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback([$this, 'objectManagerCallback']));
        $this->mockRequest = $this->getMock(Request::class);
        $this->mockUriBuilder = $this->getMock(UriBuilder::class);
        $this->mockContentObject = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->mockControllerContext = $this->getMock(ControllerContext::class);
        $this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockRequest));
        $this->mockViewHelperVariableContainer = $this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer::class);
        $this->mockRenderingContext = $this->getMock(RenderingContext::class);
        $this->mockRenderingContext->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));
        $this->mockRenderingContext->expects($this->any())->method('getViewHelperVariableContainer')->will($this->returnValue($this->mockViewHelperVariableContainer));
        $this->view->_set('templateParser', $this->mockTemplateParser);
        $this->view->_set('objectManager', $this->mockObjectManager);
        $this->view->setRenderingContext($this->mockRenderingContext);
        $this->mockTemplateCompiler = $this->getMock(TemplateCompiler::class);
        $this->view->_set('templateCompiler', $this->mockTemplateCompiler);
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class, $this->mockObjectManager);
        GeneralUtility::addInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class, $this->mockContentObject);

        $mockCacheManager = $this->getMock(\TYPO3\CMS\Core\Cache\CacheManager::class, [], [], '', false);
        $mockCache = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class, [], [], '', false);
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $mockCacheManager);
    }

    /**
     * @return void
     */
    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @param string $className
     * @return ConfigurationManagerInterface|TemplateParser|RenderingContext|Request|UriBuilder|ControllerContext|TemplateCompiler
     */
    public function objectManagerCallback($className)
    {
        switch ($className) {
            case ConfigurationManagerInterface::class:
                return $this->mockConfigurationManager;
            case TemplateParser::class:
                return $this->mockTemplateParser;
            case RenderingContext::class:
                return $this->mockRenderingContext;
            case Request::class:
                return $this->mockRequest;
            case UriBuilder::class:
                return $this->mockUriBuilder;
            case ControllerContext::class:
                return $this->mockControllerContext;
            case TemplateCompiler::class:
                return $this->mockTemplateCompiler;
        }
        throw new \InvalidArgumentException('objectManagerCallback cannot handle class "' . $className . '". Looks like incomplete mocking in the tests.', 1417105493);
    }

    /**
     * @test
     */
    public function constructorSetsSpecifiedContentObject()
    {
        $mockContentObject = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->mockConfigurationManager->expects($this->once())->method('setContentObject')->with($this->identicalTo($mockContentObject));
        new StandaloneView($mockContentObject);
    }

    /**
     * @test
     */
    public function constructorCreatesContentObjectIfItIsNotSpecified()
    {
        $this->mockConfigurationManager->expects($this->once())->method('setContentObject')->with($this->identicalTo($this->mockContentObject));
        new StandaloneView();
    }

    /**
     * @test
     */
    public function constructorSetsRequestUri()
    {
        $expectedRequestUri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        $this->mockRequest->expects($this->once())->method('setRequestURI')->with($expectedRequestUri);
        new StandaloneView();
    }

    /**
     * @test
     */
    public function constructorSetsBaseUri()
    {
        $expectedBaseUri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $this->mockRequest->expects($this->once())->method('setBaseURI')->with($expectedBaseUri);
        new StandaloneView();
    }

    /**
     * @test
     */
    public function constructorInjectsRequestToUriBuilder()
    {
        $this->mockUriBuilder->expects($this->once())->method('setRequest')->with($this->mockRequest);
        new StandaloneView();
    }

    /**
     * @test
     */
    public function constructorInjectsRequestToControllerContext()
    {
        $this->mockControllerContext->expects($this->once())->method('setRequest')->with($this->mockRequest);
        new StandaloneView();
    }

    /**
     * @test
     */
    public function constructorInjectsUriBuilderToControllerContext()
    {
        $this->mockControllerContext->expects($this->once())->method('setUriBuilder')->with($this->mockUriBuilder);
        new StandaloneView();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function renderThrowsExceptionIfTemplateIsNotSpecified()
    {
        $this->view->render();
    }

    /**
     * @test
     */
    public function renderPassesSpecifiedTemplateSourceToTemplateParser()
    {
        $this->view->setTemplateSource('The Template Source');
        $this->mockTemplateParser->expects($this->once())->method('parse')->with('The Template Source');
        $this->view->render();
    }

    /**
     * @test
     */
    public function renderLoadsSpecifiedTemplateFileAndPassesSourceToTemplateParser()
    {
        $templatePathAndFilename = GeneralUtility::fixWindowsFilePath(__DIR__) . '/Fixtures/StandaloneViewFixture.html';
        $expectedResult = file_get_contents($templatePathAndFilename);
        $this->view->setTemplatePathAndFilename($templatePathAndFilename);
        $this->view->expects($this->once())->method('testFileExistence')->with($templatePathAndFilename)->will($this->returnValue(true));
        $this->mockTemplateParser->expects($this->once())->method('parse')->with($expectedResult);
        $this->view->render();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function renderThrowsExceptionIfSpecifiedTemplateFileDoesNotExist()
    {
        $this->view->setTemplatePathAndFilename('NonExistingTemplatePath');
        @$this->view->render();
    }

    /**
     * @test
     */
    public function setFormatSetsRequestFormat()
    {
        $this->mockRequest->expects($this->once())->method('setFormat')->with('xml');
        $this->view->setFormat('xml');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutRootPathThrowsExceptionIfLayoutRootPathAndTemplatePathAreNotSpecified()
    {
        $this->view->getLayoutRootPath();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutRootPathsThrowsExceptionIfLayoutRootPathAndTemplatePathAreNotSpecified()
    {
        $this->view->getLayoutRootPaths();
    }

    /**
     * @test
     */
    public function getLayoutRootPathReturnsSpecifiedLayoutRootPathByDefault()
    {
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
    public function getLayoutRootPathsReturnsSpecifiedLayoutRootPathByDefault()
    {
        $templatePathAndFilename = 'some/template/RootPath/SomeTemplate.html';
        $layoutRootPaths = [
            'some/layout/RootPath'
        ];
        $this->view->setTemplatePathAndFilename($templatePathAndFilename);
        $this->view->setLayoutRootPaths($layoutRootPaths);
        $actualResult = $this->view->getLayoutRootPaths();
        $this->assertEquals($layoutRootPaths, $actualResult);
    }

    /**
     * @test
     */
    public function getLayoutRootPathReturnsDefaultPathIfNoLayoutRootPathIsSpecified()
    {
        $templatePathAndFilename = 'some/template/RootPath/SomeTemplate.html';
        $this->view->setTemplatePathAndFilename($templatePathAndFilename);
        $expectedResult = 'some/template/RootPath/Layouts';
        $actualResult = $this->view->getLayoutRootPath();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getLayoutRootPathsReturnsDefaultPathIfNoLayoutRootPathIsSpecified()
    {
        $templatePathAndFilename = 'some/template/RootPath/SomeTemplate.html';
        $this->view->setTemplatePathAndFilename($templatePathAndFilename);
        $expectedResult = ['some/template/RootPath/Layouts'];
        $actualResult = $this->view->getLayoutRootPaths();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutSourceThrowsExceptionIfLayoutRootPathDoesNotExist()
    {
        $this->view->setLayoutRootPath('some/non/existing/Path');
        $this->view->_call('getLayoutSource');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutSourceThrowsExceptionIfLayoutRootPathsDoesNotExist()
    {
        $this->view->setLayoutRootPaths(['some/non/existing/Path']);
        $this->view->_call('getLayoutSource');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutSourceThrowsExceptionIfLayoutFileDoesNotExist()
    {
        $layoutRootPath = __DIR__ . '/Fixtures';
        $this->view->setLayoutRootPaths([$layoutRootPath]);
        $this->view->_call('getLayoutSource', 'NonExistingLayout');
    }

    /**
     * @test
     */
    public function getLayoutSourceReturnsContentOfLayoutFileForTheDefaultFormat()
    {
        $layoutRootPath = GeneralUtility::fixWindowsFilePath(__DIR__) . '/Fixtures';
        $this->view->setLayoutRootPath($layoutRootPath);
        $this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->once())->method('testFileExistence')->with($layoutRootPath . '/LayoutFixture.html')->will($this->returnValue(true));
        $expectedResult = file_get_contents($layoutRootPath . '/LayoutFixture.html');
        $actualResult = $this->view->_call('getLayoutSource', 'LayoutFixture');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getLayoutSourceReturnsContentOfLayoutFileForTheSpecifiedFormat()
    {
        $layoutRootPath = GeneralUtility::fixWindowsFilePath(__DIR__) . '/Fixtures';
        $this->view->setLayoutRootPath($layoutRootPath);
        $this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('xml'));
        $this->view->expects($this->once())->method('testFileExistence')->with($layoutRootPath . '/LayoutFixture.xml')->will($this->returnValue(true));
        $expectedResult = file_get_contents($layoutRootPath . '/LayoutFixture.xml');
        $actualResult = $this->view->_call('getLayoutSource', 'LayoutFixture');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getLayoutSourceReturnsContentOfDefaultLayoutFileIfNoLayoutExistsForTheSpecifiedFormat()
    {
        $layoutRootPath = GeneralUtility::fixWindowsFilePath(__DIR__) . '/Fixtures';
        $this->view->setLayoutRootPath($layoutRootPath);
        $this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('foo'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with($layoutRootPath . '/LayoutFixture.foo')->will($this->returnValue(false));
        $this->view->expects($this->at(1))->method('testFileExistence')->with($layoutRootPath . '/LayoutFixture')->will($this->returnValue(true));
        $expectedResult = file_get_contents($layoutRootPath . '/LayoutFixture');
        $actualResult = $this->view->_call('getLayoutSource', 'LayoutFixture');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialRootPathThrowsExceptionIfPartialRootPathAndTemplatePathAreNotSpecified()
    {
        $this->view->getPartialRootPath();
    }

    /**
     * @test
     */
    public function getPartialRootPathReturnsSpecifiedPartialRootPathByDefault()
    {
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
    public function getPartialRootPathReturnsDefaultPathIfNoPartialRootPathIsSpecified()
    {
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
    public function getPartialSourceThrowsExceptionIfPartialRootPathDoesNotExist()
    {
        $this->view->setPartialRootPath('some/non/existing/Path');
        $this->view->_call('getPartialSource', 'SomePartial');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialSourceThrowsExceptionIfPartialFileDoesNotExist()
    {
        $partialRootPath = __DIR__ . '/Fixtures';
        $this->view->setPartialRootPath($partialRootPath);
        $this->view->_call('getPartialSource', 'NonExistingPartial');
    }

    /**
     * @test
     */
    public function getPartialSourceReturnsContentOfPartialFileForTheDefaultFormat()
    {
        $partialRootPath = __DIR__ . '/Fixtures';
        $this->view->setPartialRootPath($partialRootPath);
        $this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->once())->method('testFileExistence')->will($this->returnValue(true));
        $expectedResult = file_get_contents($partialRootPath . '/LayoutFixture.html');
        $actualResult = $this->view->_call('getPartialSource', 'LayoutFixture');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPartialSourceReturnsContentOfPartialFileForTheSpecifiedFormat()
    {
        $partialRootPath = __DIR__ . '/Fixtures';
        $this->view->setPartialRootPath($partialRootPath);
        $this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('xml'));
        $this->view->expects($this->once())->method('testFileExistence')->will($this->returnValue(true));
        $expectedResult = file_get_contents($partialRootPath . '/LayoutFixture.xml');
        $actualResult = $this->view->_call('getPartialSource', 'LayoutFixture');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getPartialSourceReturnsContentOfDefaultPartialFileIfNoPartialExistsForTheSpecifiedFormat()
    {
        $partialRootPath = __DIR__ . '/Fixtures';
        $this->view->setPartialRootPath($partialRootPath);
        $this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('foo'));
        $this->view->expects($this->at(1))->method('testFileExistence')->will($this->returnValue(true));
        $expectedResult = file_get_contents($partialRootPath . '/LayoutFixture');
        $actualResult = $this->view->_call('getPartialSource', 'LayoutFixture');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function setPartialRootPathsOverridesValueSetBySetPartialRootPath()
    {
        $this->view->setPartialRootPath('/foo/bar');
        $this->view->setPartialRootPaths(['/overruled/path']);
        $expected = ['/overruled/path'];
        $actual = $this->view->_call('getPartialRootPaths');
        $this->assertEquals($expected, $actual, 'A set partial root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function setLayoutRootPathsOverridesValuesSetBySetLayoutRootPath()
    {
        $this->view->setLayoutRootPath('/foo/bar');
        $this->view->setLayoutRootPaths(['/overruled/path']);
        $expected = ['/overruled/path'];
        $actual = $this->view->_call('getLayoutRootPaths');
        $this->assertEquals($expected, $actual, 'A set layout root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function getLayoutPathAndFilenameRespectsCasingOfLayoutName()
    {
        $this->view->setLayoutRootPaths(['some/Default/Directory']);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/LayoutName.html')->willReturn(false);
        $this->view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/LayoutName')->willReturn(false);
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/layoutName.html')->willReturn(true);
        $this->assertSame(PATH_site . 'some/Default/Directory/layoutName.html', $this->view->_call('getLayoutPathAndFilename', 'layoutName'));
    }

    /**
     * @test
     */
    public function getLayoutPathAndFilenameFindsUpperCasedLayoutName()
    {
        $this->view->setLayoutRootPaths(['some/Default/Directory']);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/LayoutName.html')->willReturn(true);
        $this->assertSame(PATH_site . 'some/Default/Directory/LayoutName.html', $this->view->_call('getLayoutPathAndFilename', 'layoutName'));
    }

    /**
     * @test
     */
    public function getLayoutPathAndFilenameResolvesTheSpecificFile()
    {
        $this->view->setLayoutRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Layouts',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->once())->method('testFileExistence')->with(PATH_site . 'specific/Layouts/Default.html')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'specific/Layouts/Default.html', $this->view->_call('getLayoutPathAndFilename'));
    }

    /**
     * @test
     */
    public function getLayoutPathAndFilenameResolvesTheDefaultFile()
    {
        $this->view->setLayoutRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Layouts',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Default.html')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'some/Default/Directory/Default.html', $this->view->_call('getLayoutPathAndFilename'));
    }

    /**
     * @test
     */
    public function getLayoutPathAndFilenameResolvesTheSpecificFileWithNumericIndices()
    {
        $this->view->setLayoutRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Layouts',
            '17' => 'specific/Layouts',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'specific/Layouts/Default.html')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'specific/Layouts/Default.html', $this->view->_call('getLayoutPathAndFilename'));
    }

    /**
     * @test
     */
    public function getLayoutPathAndFilenameResolvesTheDefaultFileWithNumericIndices()
    {
        $this->view->setLayoutRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Layouts',
            '17' => 'specific/Layouts',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(4))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Default.html')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'some/Default/Directory/Default.html', $this->view->_call('getLayoutPathAndFilename'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     * @expectedExceptionCode 1288092555
     */
    public function getLayoutPathAndFilenameThrowsExceptionIfNoFileWasFound()
    {
        $this->view->setLayoutRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Layouts',
            '17' => 'specific/Layouts',
        ]);
        $this->view->expects($this->any())->method('testFileExistence')->will($this->returnValue(false));
        $this->view->_call('getLayoutPathAndFilename');
    }

    /**
     * @test
     */
    public function getPartialPathAndFilenameRespectsCasingOfPartialName()
    {
        $this->view->setPartialRootPaths(['some/Default/Directory']);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/PartialName.html')->willReturn(false);
        $this->view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/PartialName')->willReturn(false);
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/partialName.html')->willReturn(true);
        $this->assertSame(PATH_site . 'some/Default/Directory/partialName.html', $this->view->_call('getPartialPathAndFilename', 'partialName'));
    }

    /**
     * @test
     */
    public function getPartialPathAndFilenameFindsUpperCasedPartialName()
    {
        $this->view->setPartialRootPaths(['some/Default/Directory']);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/PartialName.html')->willReturn(true);
        $this->assertSame(PATH_site . 'some/Default/Directory/PartialName.html', $this->view->_call('getPartialPathAndFilename', 'partialName'));
    }

    /**
     * @test
     */
    public function getPartialPathAndFilenameResolvesTheSpecificFile()
    {
        $this->view->setPartialRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Partials',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->once())->method('testFileExistence')->with(PATH_site . 'specific/Partials/Partial.html')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'specific/Partials/Partial.html', $this->view->_call('getPartialPathAndFilename', 'Partial'));
    }

    /**
     * @test
     */
    public function getPartialPathAndFilenameResolvesTheDefaultFile()
    {
        $this->view->setPartialRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Partials',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Partial.html')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'some/Default/Directory/Partial.html', $this->view->_call('getPartialPathAndFilename', 'Partial'));
    }

    /**
     * @test
     */
    public function getPartialPathAndFilenameResolvesTheSpecificFileWithNumericIndices()
    {
        $this->view->setPartialRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Partials',
            '17' => 'specific/Partials',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'specific/Partials/Partial.html')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'specific/Partials/Partial.html', $this->view->_call('getPartialPathAndFilename', 'Partial'));
    }

    /**
     * @test
     */
    public function getPartialPathAndFilenameResolvesTheDefaultFileWithNumericIndices()
    {
        $this->view->setPartialRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Partials',
            '17' => 'specific/Partials',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(4))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Partial.html')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'some/Default/Directory/Partial.html', $this->view->_call('getPartialPathAndFilename', 'Partial'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     * @expectedExceptionCode 1288092556
     */
    public function getPartialPathAndFilenameThrowsExceptionIfNoFileWasFound()
    {
        $this->view->setPartialRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Partials',
            '17' => 'specific/Partials',
        ]);
        $this->view->expects($this->any())->method('testFileExistence')->will($this->returnValue(false));
        $this->view->_call('getPartialPathAndFilename', 'Partial');
    }

    /**
     * @test
     */
    public function getPartialPathAndFilenameWalksNumericalIndicesInDescendingOrder()
    {
        $this->view->setPartialRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Partials',
            '17' => 'specific/Partials',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Partials/Partial.html')->will($this->returnValue(false));
        $this->view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Partials/Partial')->will($this->returnValue(false));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'specific/Partials/Partial.html')->will($this->returnValue(false));
        $this->view->expects($this->at(3))->method('testFileExistence')->with(PATH_site . 'specific/Partials/Partial')->will($this->returnValue(false));
        $this->view->expects($this->at(4))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Partial.html')->will($this->returnValue(false));
        $this->view->expects($this->at(5))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Partial')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'some/Default/Directory/Partial', $this->view->_call('getPartialPathAndFilename', 'Partial'));
    }

    /**
     * @test
     */
    public function getLayoutPathAndFilenameWalksNumericalIndicesInDescendingOrder()
    {
        $this->view->setLayoutRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Layouts',
            '17' => 'specific/Layouts',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Layouts/Default.html')->will($this->returnValue(false));
        $this->view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Layouts/Default')->will($this->returnValue(false));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'specific/Layouts/Default.html')->will($this->returnValue(false));
        $this->view->expects($this->at(3))->method('testFileExistence')->with(PATH_site . 'specific/Layouts/Default')->will($this->returnValue(false));
        $this->view->expects($this->at(4))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Default.html')->will($this->returnValue(false));
        $this->view->expects($this->at(5))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Default')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'some/Default/Directory/Default', $this->view->_call('getLayoutPathAndFilename'));
    }

    /**
     * @test
     */
    public function getPartialPathAndFilenameWalksStringKeysInReversedOrder()
    {
        $this->view->setPartialRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Partials',
            'verySpecific' => 'evenMore/Specific/Partials',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Partials/Partial.html')->will($this->returnValue(false));
        $this->view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Partials/Partial')->will($this->returnValue(false));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'specific/Partials/Partial.html')->will($this->returnValue(false));
        $this->view->expects($this->at(3))->method('testFileExistence')->with(PATH_site . 'specific/Partials/Partial')->will($this->returnValue(false));
        $this->view->expects($this->at(4))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Partial.html')->will($this->returnValue(false));
        $this->view->expects($this->at(5))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Partial')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'some/Default/Directory/Partial', $this->view->_call('getPartialPathAndFilename', 'Partial'));
    }

    /**
     * @test
     */
    public function getLayoutPathAndFilenameWalksStringKeysInReversedOrder()
    {
        $this->view->setLayoutRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Layout',
            'verySpecific' => 'evenMore/Specific/Layout',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Layout/Default.html')->will($this->returnValue(false));
        $this->view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Layout/Default')->will($this->returnValue(false));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'specific/Layout/Default.html')->will($this->returnValue(false));
        $this->view->expects($this->at(3))->method('testFileExistence')->with(PATH_site . 'specific/Layout/Default')->will($this->returnValue(false));
        $this->view->expects($this->at(4))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Default.html')->will($this->returnValue(false));
        $this->view->expects($this->at(5))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Default')->will($this->returnValue(true));
        $this->assertEquals(PATH_site . 'some/Default/Directory/Default', $this->view->_call('getLayoutPathAndFilename'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function setTemplateThrowsExceptionIfNoTemplateRootPathsAreSet()
    {
        $this->view->setTemplate('TemplateName');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function setTemplateThrowsExceptionIfSpecifiedTemplateNameDoesNotExist()
    {
        $this->view->setTemplateRootPaths([
            'Some/Template/Path'
        ]);
        $this->view->setTemplate('NonExistingTemplateName');
    }

    /**
     * @test
     */
    public function setTemplateRespectsCasingOfTemplateName()
    {
        $this->view->setTemplateRootPaths(['some/Default/Directory']);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/TemplateName.html')->willReturn(false);
        $this->view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/TemplateName')->willReturn(false);
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/templateName.html')->willReturn(true);
        $this->view->setTemplate('templateName');

        $this->assertSame(PATH_site . 'some/Default/Directory/templateName.html', $this->view->getTemplatePathAndFilename());
    }

    /**
     * @test
     */
    public function setTemplateSetsUpperCasedTemplateName()
    {
        $this->view->setTemplateRootPaths(['some/Default/Directory']);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/TemplateName.html')->willReturn(true);
        $this->view->setTemplate('templateName');
        $this->assertSame(PATH_site . 'some/Default/Directory/TemplateName.html', $this->view->getTemplatePathAndFilename());
    }

    /**
     * @test
     */
    public function setTemplateResolvesTheSpecificTemplateFile()
    {
        $this->view->setTemplateRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Templates',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'specific/Templates/Template.html')->will($this->returnValue(true));
        $this->view->setTemplate('Template');
        $this->assertEquals(PATH_site . 'specific/Templates/Template.html', $this->view->getTemplatePathAndFilename());
    }

    /**
     * @test
     */
    public function setTemplateResolvesTheDefaultTemplateFile()
    {
        $this->view->setTemplateRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Templates',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Template.html')->will($this->returnValue(true));
        $this->view->setTemplate('Template');

        $this->assertEquals(PATH_site . 'some/Default/Directory/Template.html', $this->view->getTemplatePathAndFilename());
    }

    /**
     * @test
     */
    public function setTemplateResolvesTemplateNameWithPath()
    {
        $this->view->setTemplateRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Templates',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'specific/Templates/Email/Template.html')->will($this->returnValue(true));
        $this->view->setTemplate('Email/Template');
        $this->assertEquals(PATH_site . 'specific/Templates/Email/Template.html', $this->view->getTemplatePathAndFilename());
    }

    /**
     * @test
     */
    public function setTemplateResolvesTheSpecificFileWithNumericIndices()
    {
        $this->view->setTemplateRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Templates',
            '17' => 'specific/Templates',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'specific/Templates/Template.html')->will($this->returnValue(true));
        $this->view->setTemplate('Template');
        $this->assertEquals(PATH_site . 'specific/Templates/Template.html', $this->view->getTemplatePathAndFilename());
    }

    /**
     * @test
     */
    public function setTemplateResolvesTheDefaultFileWithNumericIndices()
    {
        $this->view->setTemplateRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Templates',
            '17' => 'specific/Templates',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(4))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Template.html')->will($this->returnValue(true));
        $this->view->setTemplate('Template');
        $this->assertEquals(PATH_site . 'some/Default/Directory/Template.html', $this->view->getTemplatePathAndFilename());
    }

    /**
     * @test
     */
    public function setTemplateWalksNumericalIndicesInDescendingOrder()
    {
        $this->view->setTemplateRootPaths([
            '10' => 'some/Default/Directory',
            '25' => 'evenMore/Specific/Templates',
            '17' => 'specific/Templates',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Templates/Template.html')->will($this->returnValue(false));
        $this->view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Templates/Template')->will($this->returnValue(false));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'specific/Templates/Template.html')->will($this->returnValue(false));
        $this->view->expects($this->at(3))->method('testFileExistence')->with(PATH_site . 'specific/Templates/Template')->will($this->returnValue(false));
        $this->view->expects($this->at(4))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Template.html')->will($this->returnValue(false));
        $this->view->expects($this->at(5))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Template')->will($this->returnValue(true));
        $this->view->setTemplate('Template');
        $this->assertEquals(PATH_site . 'some/Default/Directory/Template', $this->view->getTemplatePathAndFilename());
    }

    /**
     * @test
     */
    public function setTemplateWalksStringKeysInReversedOrder()
    {
        $this->view->setTemplateRootPaths([
            'default' => 'some/Default/Directory',
            'specific' => 'specific/Templates',
            'verySpecific' => 'evenMore/Specific/Templates',
        ]);
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $this->view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Templates/Template.html')->will($this->returnValue(false));
        $this->view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'evenMore/Specific/Templates/Template')->will($this->returnValue(false));
        $this->view->expects($this->at(2))->method('testFileExistence')->with(PATH_site . 'specific/Templates/Template.html')->will($this->returnValue(false));
        $this->view->expects($this->at(3))->method('testFileExistence')->with(PATH_site . 'specific/Templates/Template')->will($this->returnValue(false));
        $this->view->expects($this->at(4))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Template.html')->will($this->returnValue(false));
        $this->view->expects($this->at(5))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/Template')->will($this->returnValue(true));
        $this->view->setTemplate('Template');
        $this->assertEquals(PATH_site . 'some/Default/Directory/Template', $this->view->getTemplatePathAndFilename());
    }
}
