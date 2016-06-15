<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\View;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\Core\Parser\PreProcessor\XmlnsNamespaceTemplatePreProcessor;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Core\Variables\CmsVariableProvider;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\TemplateParser;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Test case
 */
class StandaloneViewTest extends UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = array();

    /**
     * @var StandaloneView|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $view;

    /**
     * @var \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface|\PHPUnit_Framework_MockObject_MockObject
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
     * @var ConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConfigurationManager;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockContentObject;

    /**
     * @var TemplatePaths|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockTemplatePaths;

    /**
     * @var CmsVariableProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockVariableProvider;

    /**
     * @var CacheManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockCacheManager;

    /**
     * Sets up this test case
     *
     * @return void
     */
    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->view = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\View\StandaloneView::class,
            array('testFileExistence', 'buildParserConfiguration', 'getOrParseAndStoreTemplate'), array(), '', false
        );
        $this->mockConfigurationManager = $this->createMock(ConfigurationManagerInterface::class);
        $this->mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback(array($this, 'objectManagerCallback')));
        $this->mockRequest = $this->createMock(Request::class);
        $this->mockUriBuilder = $this->createMock(UriBuilder::class);
        $this->mockContentObject = $this->createMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->mockControllerContext = $this->createMock(ControllerContext::class);
        $this->mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->mockRequest));
        $this->mockTemplatePaths = $this->createMock(TemplatePaths::class);
        $this->mockViewHelperVariableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $this->mockRenderingContext = $this->createMock(\TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture::class);
        $this->mockRenderingContext->expects($this->any())->method('getControllerContext')->will($this->returnValue($this->mockControllerContext));
        $this->mockRenderingContext->expects($this->any())->method('getViewHelperVariableContainer')->will($this->returnValue($this->mockViewHelperVariableContainer));
        $this->mockRenderingContext->expects($this->any())->method('getVariableProvider')->willReturn($this->mockVariableProvider);
        $this->mockRenderingContext->expects($this->any())->method('getTemplatePaths')->willReturn($this->mockTemplatePaths);
        $this->view->_set('objectManager', $this->mockObjectManager);
        $this->view->_set('baseRenderingContext', $this->mockRenderingContext);
        $this->view->_set('controllerContext', $this->mockControllerContext);
        $this->view->expects($this->any())->method('getOrParseAndStoreTemplate')->willReturn($this->mockParsedTemplate);
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class, $this->mockObjectManager);
        GeneralUtility::addInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class, $this->mockContentObject);

        $this->mockCacheManager = $this->createMock(\TYPO3\CMS\Core\Cache\CacheManager::class);
        $mockCache = $this->createMock(\TYPO3Fluid\Fluid\Core\Cache\FluidCacheInterface::class);
        $this->mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $this->mockCacheManager);
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
            case TemplatePaths::class:
                return $this->mockTemplatePaths;
            case CacheManager::class:
                return $this->mockCacheManager;
            case XmlnsNamespaceTemplatePreProcessor::class:
                return $this->mockTemplateProcessor;
        }
        throw new \InvalidArgumentException('objectManagerCallback cannot handle class "' . $className . '". Looks like incomplete mocking in the tests.', 1417105493);
    }

    /**
     * @test
     */
    public function constructorSetsSpecifiedContentObject()
    {
        $mockContentObject = $this->createMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
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
     */
    public function setFormatSetsRequestFormat()
    {
        $this->mockRequest->expects($this->once())->method('setFormat')->with('xml');
        $this->view->setFormat('xml');
    }
}
