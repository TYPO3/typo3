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

use Prophecy\Argument;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

    protected function setUp(): void
    {
        parent::setUp();

        $mockWidgetContext = $this->createMock(WidgetContext::class);
        $mockWidgetContext->method('getControllerObjectName')->willReturn('TYPO3\\CMS\\Core\\Controller\\FooController');

        $ajaxWidgetContextHolderProphecy = $this->prophesize(AjaxWidgetContextHolder::class);
        $ajaxWidgetContextHolderProphecy->get(Argument::cetera())->willReturn($mockWidgetContext);

        $environmentServiceMock = $this->createMock(EnvironmentService::class);
        $environmentServiceMock->expects(self::any())->method('isEnvironmentInFrontendMode')->willReturn(true);
        $environmentServiceMock->expects(self::any())->method('isEnvironmentInBackendMode')->willReturn(false);

        $this->widgetRequestBuilder = new WidgetRequestBuilder();
        $this->widgetRequestBuilder->injectAjaxWidgetContextHolder($ajaxWidgetContextHolderProphecy->reveal());
        $this->widgetRequestBuilder->injectEnvironmentService($environmentServiceMock);
    }

    /**
     * @test
     */
    public function buildThrowsIfNoFluidWidgetIdWasSet()
    {
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
            'REQUEST_METHOD' => 'GET'
        ];
        $_GET = [
            'fluid-widget-id' => 'foo'
        ];
        $requestUri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        /** @var WidgetRequest $request */
        $request = $this->widgetRequestBuilder->build();

        self::assertInstanceOf(WidgetRequest::class, $request);
        self::assertSame($requestUri, $request->getRequestUri());
    }

    /**
     * @test
     */
    public function buildSetsBaseUri()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET'
        ];
        $_GET = [
            'fluid-widget-id' => 'foo'
        ];
        $baseUri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        /** @var WidgetRequest $request */
        $request = $this->widgetRequestBuilder->build();

        self::assertInstanceOf(WidgetRequest::class, $request);
        self::assertSame($baseUri, $request->getBaseUri());
    }

    /**
     * @test
     */
    public function buildSetsRequestMethod()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST'
        ];
        $_GET = [
            'fluid-widget-id' => 'foo'
        ];
        /** @var WidgetRequest $request */
        $request = $this->widgetRequestBuilder->build();

        self::assertInstanceOf(WidgetRequest::class, $request);
        self::assertSame('POST', $request->getMethod());
    }

    /**
     * @test
     */
    public function buildSetsPostArgumentsFromRequest()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST'
        ];
        $_GET = [
            'get' => 'foo',
            'fluid-widget-id' => 'foo'
        ];
        $_POST = [
            'post' => 'bar'
        ];
        /** @var WidgetRequest $request */
        $request = $this->widgetRequestBuilder->build();

        self::assertInstanceOf(WidgetRequest::class, $request);
        self::assertSame($_POST, $request->getArguments());
    }

    /**
     * @test
     */
    public function buildSetsGetArgumentsFromRequest()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET'
        ];
        $_GET = [
            'get' => 'foo',
            'fluid-widget-id' => 'foo'
        ];
        $_POST = [
            'post' => 'bar'
        ];
        /** @var WidgetRequest $request */
        $request = $this->widgetRequestBuilder->build();

        self::assertInstanceOf(WidgetRequest::class, $request);
        self::assertSame($_GET, $request->getArguments());
    }

    /**
     * @test
     */
    public function buildSetsControllerActionNameFromGetArguments()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET'
        ];
        $_GET = [
            'action' => 'myAction',
            'fluid-widget-id' => 'foo'
        ];
        /** @var WidgetRequest $request */
        $request = $this->widgetRequestBuilder->build();

        self::assertInstanceOf(WidgetRequest::class, $request);
        self::assertSame('myAction', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function buildSetsWidgetContext()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET'
        ];
        $_GET = [
            'fluid-widget-id' => '123'
        ];
        /** @var WidgetRequest $request */
        $request = $this->widgetRequestBuilder->build();

        self::assertInstanceOf(WidgetRequest::class, $request);
        self::assertSame('TYPO3\\CMS\\Core\\Controller\\FooController', $request->getControllerObjectName());
        self::assertSame('[]', $request->getArgumentPrefix());
    }
}
