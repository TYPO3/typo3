<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder;
use TYPO3\CMS\Fluid\Core\Widget\WidgetContext;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequest;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequestBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class WidgetRequestBuilderTest extends UnitTestCase
{
    /**
     * @var WidgetRequestBuilder
     */
    protected $widgetRequestBuilder;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var WidgetRequest
     */
    protected $mockWidgetRequest;

    /**
     * @var AjaxWidgetContextHolder
     */
    protected $mockAjaxWidgetContextHolder;

    /**
     * @var WidgetContext
     */
    protected $mockWidgetContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->widgetRequestBuilder = $this->getAccessibleMock(WidgetRequestBuilder::class, ['setArgumentsFromRawRequestData']);
        $this->mockWidgetRequest = $this->createMock(WidgetRequest::class);
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->expects(self::any())->method('get')->with(WidgetRequest::class)->willReturn($this->mockWidgetRequest);
        $this->widgetRequestBuilder->_set('objectManager', $this->mockObjectManager);
        $this->mockWidgetContext = $this->createMock(WidgetContext::class);
        $this->mockAjaxWidgetContextHolder = $this->createMock(AjaxWidgetContextHolder::class);
        $this->widgetRequestBuilder->injectAjaxWidgetContextHolder($this->mockAjaxWidgetContextHolder);
        $environmentServiceMock = $this->createMock(EnvironmentService::class);
        $environmentServiceMock->expects(self::any())->method('isEnvironmentInFrontendMode')->willReturn(true);
        $environmentServiceMock->expects(self::any())->method('isEnvironmentInBackendMode')->willReturn(false);
        $this->widgetRequestBuilder->injectEnvironmentService($environmentServiceMock);
    }

    /**
     * @test
     */
    public function buildThrowsIfNoFluidWidgetIdWasSet()
    {
        $_SERVER = [
            'REMOTE_ADDR' => 'foo',
            'SSL_SESSION_ID' => 'foo',
            'REQUEST_URI' => 'foo',
            'ORIG_SCRIPT_NAME' => 'foo',
            'REQUEST_METHOD' => 'foo'
        ];
        $_GET = [
            'not-the-fluid-widget-id' => 'foo'
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1521190675);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestUri()
    {
        $_SERVER = [
            'REMOTE_ADDR' => 'foo',
            'SSL_SESSION_ID' => 'foo',
            'REQUEST_URI' => 'foo',
            'ORIG_SCRIPT_NAME' => 'foo',
            'REQUEST_METHOD' => 'foo'
        ];
        $_GET = [
            'fluid-widget-id' => 'foo'
        ];
        $requestUri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        $this->mockAjaxWidgetContextHolder->expects(self::once())->method('get')->willReturn($this->mockWidgetContext);
        $this->mockWidgetRequest->expects(self::once())->method('setRequestURI')->with($requestUri);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsBaseUri()
    {
        $_SERVER = [
            'REMOTE_ADDR' => 'foo',
            'SSL_SESSION_ID' => 'foo',
            'REQUEST_URI' => 'foo',
            'ORIG_SCRIPT_NAME' => 'foo',
            'REQUEST_METHOD' => 'foo'
        ];
        $_GET = [
            'fluid-widget-id' => 'foo'
        ];
        $baseUri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $this->mockAjaxWidgetContextHolder->expects(self::once())->method('get')->willReturn($this->mockWidgetContext);
        $this->mockWidgetRequest->expects(self::once())->method('setBaseURI')->with($baseUri);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsRequestMethod()
    {
        $_SERVER = [
            'REMOTE_ADDR' => 'foo',
            'SSL_SESSION_ID' => 'foo',
            'REQUEST_URI' => 'foo',
            'ORIG_SCRIPT_NAME' => 'foo',
            'REQUEST_METHOD' => 'POST'
        ];
        $_GET = [
            'fluid-widget-id' => 'foo'
        ];
        $this->mockAjaxWidgetContextHolder->expects(self::once())->method('get')->willReturn($this->mockWidgetContext);
        $this->mockWidgetRequest->expects(self::once())->method('setMethod')->with('POST');
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsPostArgumentsFromRequest()
    {
        $_SERVER = [
            'REMOTE_ADDR' => 'foo',
            'SSL_SESSION_ID' => 'foo',
            'REQUEST_URI' => 'foo',
            'ORIG_SCRIPT_NAME' => 'foo',
            'REQUEST_METHOD' => 'POST'
        ];
        $_GET = [
            'get' => 'foo',
            'fluid-widget-id' => 'foo'
        ];
        $_POST = [
            'post' => 'bar'
        ];
        $this->mockAjaxWidgetContextHolder->expects(self::once())->method('get')->willReturn($this->mockWidgetContext);
        $this->mockWidgetRequest->expects(self::once())->method('setArguments')->with($_POST);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsGetArgumentsFromRequest()
    {
        $_SERVER = [
            'REMOTE_ADDR' => 'foo',
            'SSL_SESSION_ID' => 'foo',
            'REQUEST_URI' => 'foo',
            'ORIG_SCRIPT_NAME' => 'foo',
            'REQUEST_METHOD' => 'GET'
        ];
        $_GET = [
            'get' => 'foo',
            'fluid-widget-id' => 'foo'
        ];
        $_POST = [
            'post' => 'bar'
        ];
        $this->mockAjaxWidgetContextHolder->expects(self::once())->method('get')->willReturn($this->mockWidgetContext);
        $this->mockWidgetRequest->expects(self::once())->method('setArguments')->with($_GET);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsControllerActionNameFromGetArguments()
    {
        $_SERVER = [
            'REMOTE_ADDR' => 'foo',
            'SSL_SESSION_ID' => 'foo',
            'REQUEST_URI' => 'foo',
            'ORIG_SCRIPT_NAME' => 'foo',
            'REQUEST_METHOD' => 'foo'
        ];
        $_GET = [
            'action' => 'myAction',
            'fluid-widget-id' => 'foo'
        ];
        $this->mockAjaxWidgetContextHolder->expects(self::once())->method('get')->willReturn($this->mockWidgetContext);
        $this->mockWidgetRequest->expects(self::once())->method('setControllerActionName')->with('myAction');
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildSetsWidgetContext()
    {
        $_SERVER = [
            'REMOTE_ADDR' => 'foo',
            'SSL_SESSION_ID' => 'foo',
            'REQUEST_URI' => 'foo',
            'ORIG_SCRIPT_NAME' => 'foo',
            'REQUEST_METHOD' => 'foo'
        ];
        $_GET = [
            'fluid-widget-id' => '123'
        ];
        $this->mockAjaxWidgetContextHolder->expects(self::once())->method('get')->willReturn($this->mockWidgetContext);
        $this->mockAjaxWidgetContextHolder->expects(self::once())->method('get')->with('123')->willReturn($this->mockWidgetContext);
        $this->mockWidgetRequest->expects(self::once())->method('setWidgetContext')->with($this->mockWidgetContext);
        $this->widgetRequestBuilder->build();
    }

    /**
     * @test
     */
    public function buildReturnsRequest()
    {
        $_SERVER = [
            'REMOTE_ADDR' => 'foo',
            'SSL_SESSION_ID' => 'foo',
            'REQUEST_URI' => 'foo',
            'ORIG_SCRIPT_NAME' => 'foo',
            'REQUEST_METHOD' => 'foo'
        ];
        $_GET = [
            'fluid-widget-id' => 'foo'
        ];
        $this->mockAjaxWidgetContextHolder->expects(self::once())->method('get')->willReturn($this->mockWidgetContext);
        $expected = $this->mockWidgetRequest;
        $actual = $this->widgetRequestBuilder->build();
        self::assertSame($expected, $actual);
    }
}
