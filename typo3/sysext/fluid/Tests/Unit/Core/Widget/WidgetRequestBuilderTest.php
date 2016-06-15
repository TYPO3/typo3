<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

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

/**
 * Test case
 */
class WidgetRequestBuilderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetRequestBuilder
     */
    protected $widgetRequestBuilder;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetRequest
     */
    protected $mockWidgetRequest;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
     */
    protected $mockAjaxWidgetContextHolder;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
     */
    protected $mockWidgetContext;

    protected function setUp()
    {
        $this->widgetRequestBuilder = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequestBuilder::class, array('setArgumentsFromRawRequestData'));
        $this->mockWidgetRequest = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class);
        $this->mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\Core\Widget\WidgetRequest::class)->will($this->returnValue($this->mockWidgetRequest));
        $this->widgetRequestBuilder->_set('objectManager', $this->mockObjectManager);
        $this->mockWidgetContext = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext::class);
        $this->mockAjaxWidgetContextHolder = $this->createMock(\TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder::class);
        $this->widgetRequestBuilder->injectAjaxWidgetContextHolder($this->mockAjaxWidgetContextHolder);
        $this->mockAjaxWidgetContextHolder->expects($this->once())->method('get')->will($this->returnValue($this->mockWidgetContext));
    }

    /**
     * @test
     */
    public function buildSetsRequestUri()
    {
        $requestUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        $this->mockWidgetRequest->expects($this->once())->method('setRequestURI')->with($requestUri);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsBaseUri()
    {
        $baseUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $this->mockWidgetRequest->expects($this->once())->method('setBaseURI')->with($baseUri);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->mockWidgetRequest->expects($this->once())->method('setMethod')->with('POST');
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsPostArgumentsFromRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = array('get' => 'foo');
        $_POST = array('post' => 'bar');
        $this->mockWidgetRequest->expects($this->once())->method('setArguments')->with($_POST);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsGetArgumentsFromRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = array('get' => 'foo');
        $_POST = array('post' => 'bar');
        $this->mockWidgetRequest->expects($this->once())->method('setArguments')->with($_GET);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsControllerActionNameFromGetArguments()
    {
        $_GET = array('action' => 'myAction');
        $this->mockWidgetRequest->expects($this->once())->method('setControllerActionName')->with('myAction');
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsWidgetContext()
    {
        $_GET = array('fluid-widget-id' => '123');
        $this->mockAjaxWidgetContextHolder->expects($this->once())->method('get')->with('123')->will($this->returnValue($this->mockWidgetContext));
        $this->mockWidgetRequest->expects($this->once())->method('setWidgetContext')->with($this->mockWidgetContext);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildReturnsRequest()
    {
        $expected = $this->mockWidgetRequest;
        $actual = $this->widgetRequestBuilder->build();
        $this->assertSame($expected, $actual);
    }
}
