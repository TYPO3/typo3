<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Unit\Controller;

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

use Prophecy\Argument;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ErrorControllerTest extends UnitTestCase
{
    /**
     * Purge possibly left over instances
     */
    public function tearDown()
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function pageNotFoundHandlingThrowsExceptionIfNotConfigured()
    {
        $this->expectExceptionMessage('This test page was not found!');
        $this->expectExceptionCode(1518472189);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = false;
        $GLOBALS['TYPO3_REQUEST'] = [];
        $subject = new ErrorController();
        $subject->pageNotFoundAction(new ServerRequest(), 'This test page was not found!');
    }

    /**
     * Data Provider for 404
     *
     * @return array
     */
    public function errorPageHandlingDataProvider()
    {
        return [
            '404 with default errorpage' => [
                'handler' => true,
                'header' => 'HTTP/1.0 404 Not Found',
                'message' => 'Custom message',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 404,
                    'reasonPhrase' => 'Not Found',
                    'content' => 'Reason: Custom message',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8'],
                    ],
                ],
            ],
            '404 with default errorpage setting the handler to legacy value' => [
                'handler' => '1',
                'header' => 'HTTP/1.0 404 This is a dead end',
                'message' => 'Come back tomorrow',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 404,
                    'reasonPhrase' => 'This is a dead end',
                    'content' => 'Reason: Come back tomorrow',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8'],
                    ],
                ],
            ],
            '404 with custom userfunction' => [
                'handler' => 'USER_FUNCTION:' . ErrorControllerTest::class . '->mockedUserFunctionCall',
                'header' => 'HTTP/1.0 404 Not Found',
                'message' => 'Custom message',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 404,
                    'reasonPhrase' => 'Not Found',
                    'content' => 'It\'s magic, Michael: Custom message',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8'],
                    ],
                ],
            ],
            '404 with a readfile functionality' => [
                'handler' => 'READFILE:typo3/sysext/frontend/Tests/Unit/Controller/Fixtures/error.txt',
                'header' => 'HTTP/1.0 404 Not Found',
                'message' => 'Custom message',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 404,
                    'reasonPhrase' => 'Not Found',
                    'content' => 'rama-lama-ding-dong',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8'],
                    ],
                ],
            ],
            '404 with a readfile functionality with an invalid file' => [
                'handler' => 'READFILE:does_not_exist.php6',
                'header' => 'HTTP/1.0 404 Not Found',
                'message' => 'Custom message',
                'response' => null,
                'exceptionCode' => 1518472245,
            ],
            '404 with a redirect - never do that in production - it is bad for SEO. But with custom headers as well...' => [
                'handler' => 'REDIRECT:www.typo3.org',
                'header' => 'HTTP/1.0 404 Not Found
X-TYPO3-Additional-Header: Banana Stand',
                'message' => 'Custom message',
                'response' => [
                    'type' => RedirectResponse::class,
                    'statusCode' => 404,
                    'reasonPhrase' => 'Not Found',
                    'headers' => [
                        'location' => ['www.typo3.org'],
                        'X-TYPO3-Additional-Header' => ['Banana Stand'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider errorPageHandlingDataProvider
     */
    public function pageNotFoundHandlingReturnsConfiguredResponseObject(
        $handler,
        $header,
        $message,
        $expectedResponseDetails,
        $expectedExceptionCode = null
    ) {
        if ($expectedExceptionCode !== null) {
            $this->expectExceptionCode($expectedExceptionCode);
        }
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = $handler;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_statheader'] = $header;
        // faking getIndpEnv() variables
        $_SERVER['REQUEST_URI'] = '/unit-test/';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SSL_SESSION_ID'] = true;
        $GLOBALS['TYPO3_REQUEST'] = [];

        $this->prophesizeErrorPageController();

        $subject = new ErrorController();
        $response = $subject->pageNotFoundAction(new ServerRequest(), $message);
        if (is_array($expectedResponseDetails)) {
            $this->assertInstanceOf($expectedResponseDetails['type'], $response);
            $this->assertEquals($expectedResponseDetails['statusCode'], $response->getStatusCode());
            $this->assertEquals($expectedResponseDetails['reasonPhrase'], $response->getReasonPhrase());
            if (isset($expectedResponseDetails['content'])) {
                $this->assertContains($expectedResponseDetails['content'], $response->getBody()->getContents());
            }
            $this->assertEquals($expectedResponseDetails['headers'], $response->getHeaders());
        }
    }

    /**
     * @test
     */
    public function pageNotFoundHandlingReturnsResponseFromPrefix()
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = '/404/';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_statheader'] = 'HTTP/1.0 404 Not Found
X-TYPO3-Additional-Header: Banana Stand';
        // faking getIndpEnv() variables
        $_SERVER['REQUEST_URI'] = '/unit-test/';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SSL_SESSION_ID'] = true;
        $GLOBALS['TYPO3_REQUEST'] = [];
        $this->prophesizeErrorPageController();
        $subject = new ErrorController();

        $this->prophesizeGetUrl();
        $response = $subject->pageNotFoundAction(new ServerRequest(), 'Custom message');

        $expectedResponseDetails = [
            'type' => HtmlResponse::class,
            'statusCode' => 404,
            'reasonPhrase' => 'Not Found',
            'headers' => [
                'Content-Type' => ['text/html; charset=utf-8'],
                'X-TYPO3-Additional-Header' => ['Banana Stand'],
            ],
        ];
        $this->assertInstanceOf($expectedResponseDetails['type'], $response);
        $this->assertEquals($expectedResponseDetails['statusCode'], $response->getStatusCode());
        $this->assertEquals($expectedResponseDetails['reasonPhrase'], $response->getReasonPhrase());
        if (isset($expectedResponseDetails['content'])) {
            $this->assertContains($expectedResponseDetails['content'], $response->getBody()->getContents());
        }
        $this->assertEquals($expectedResponseDetails['headers'], $response->getHeaders());
    }

    /**
     * Data Provider for 403
     *
     * @return array
     */
    public function accessDeniedDataProvider()
    {
        return [
            '403 with default errorpage' => [
                'handler' => true,
                'header' => 'HTTP/1.0 403 Who are you',
                'message' => 'Be nice, do good',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 403,
                    'reasonPhrase' => 'Who are you',
                    'content' => 'Reason: Be nice, do good',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider accessDeniedDataProvider
     */
    public function accessDeniedReturnsProperHeaders($handler, $header, $message, $expectedResponseDetails)
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = $handler;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_accessdeniedheader'] = $header;
        // faking getIndpEnv() variables
        $_SERVER['REQUEST_URI'] = '/unit-test/';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SSL_SESSION_ID'] = true;
        $GLOBALS['TYPO3_REQUEST'] = [];
        $subject = new ErrorController();
        $response = $subject->accessDeniedAction(new ServerRequest(), $message);
        if (is_array($expectedResponseDetails)) {
            $this->assertInstanceOf($expectedResponseDetails['type'], $response);
            $this->assertEquals($expectedResponseDetails['statusCode'], $response->getStatusCode());
            $this->assertEquals($expectedResponseDetails['reasonPhrase'], $response->getReasonPhrase());
            if (isset($expectedResponseDetails['content'])) {
                $this->assertContains($expectedResponseDetails['content'], $response->getBody()->getContents());
            }
            $this->assertEquals($expectedResponseDetails['headers'], $response->getHeaders());
        }
    }

    /**
     * @test
     */
    public function unavailableHandlingThrowsExceptionIfNotConfigured()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'] = true;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->expectExceptionMessage('All your system are belong to us!');
        $this->expectExceptionCode(1518472181);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'] = false;
        $subject = new ErrorController();
        $subject->unavailableAction(new ServerRequest(), 'All your system are belong to us!');
    }

    /**
     * @test
     */
    public function unavailableHandlingDoesNotTriggerDueToDevIpMask()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'] = true;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->expectExceptionMessage('All your system are belong to us!');
        $this->expectExceptionCode(1518472181);
        $subject = new ErrorController();
        $subject->unavailableAction(new ServerRequest(), 'All your system are belong to us!');
    }

    /**
     * Data Provider for 503
     *
     * @return array
     */
    public function unavailableHandlingDataProvider()
    {
        return [
            '503 with default errorpage' => [
                'handler' => true,
                'header' => 'HTTP/1.0 503 Service Temporarily Unavailable',
                'message' => 'Custom message',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 503,
                    'reasonPhrase' => 'Service Temporarily Unavailable',
                    'content' => 'Reason: Custom message',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8'],
                    ],
                ],
            ],
            '503 with default errorpage setting the handler to legacy value' => [
                'handler' => '1',
                'header' => 'HTTP/1.0 503 This is a dead end',
                'message' => 'Come back tomorrow',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 503,
                    'reasonPhrase' => 'This is a dead end',
                    'content' => 'Reason: Come back tomorrow',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8'],
                    ],
                ],
            ],
            '503 with custom userfunction' => [
                'handler' => 'USER_FUNCTION:' . ErrorControllerTest::class . '->mockedUserFunctionCall',
                'header' => 'HTTP/1.0 503 Service Temporarily Unavailable',
                'message' => 'Custom message',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 503,
                    'reasonPhrase' => 'Service Temporarily Unavailable',
                    'content' => 'It\'s magic, Michael: Custom message',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8'],
                    ],
                ],
            ],
            '503 with a readfile functionality' => [
                'handler' => 'READFILE:typo3/sysext/frontend/Tests/Unit/Controller/Fixtures/error.txt',
                'header' => 'HTTP/1.0 503 Service Temporarily Unavailable',
                'message' => 'Custom message',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 503,
                    'reasonPhrase' => 'Service Temporarily Unavailable',
                    'content' => 'Let it snow',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8'],
                    ],
                ],
            ],
            '503 with a readfile functionality with an invalid file' => [
                'handler' => 'READFILE:does_not_exist.php6',
                'header' => 'HTTP/1.0 503 Service Temporarily Unavailable',
                'message' => 'Custom message',
                'response' => null,
                'exceptionCode' => 1518472245,
            ],
            '503 with a redirect - never do that in production - it is bad for SEO. But with custom headers as well...' => [
                'handler' => 'REDIRECT:www.typo3.org',
                'header' => 'HTTP/1.0 503 Service Temporarily Unavailable
X-TYPO3-Additional-Header: Banana Stand',
                'message' => 'Custom message',
                'response' => [
                    'type' => RedirectResponse::class,
                    'statusCode' => 503,
                    'reasonPhrase' => 'Service Temporarily Unavailable',
                    'headers' => [
                        'location' => ['www.typo3.org'],
                        'X-TYPO3-Additional-Header' => ['Banana Stand'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unavailableHandlingDataProvider
     */
    public function pageUnavailableHandlingReturnsConfiguredResponseObject(
        $handler,
        $header,
        $message,
        $expectedResponseDetails,
        $expectedExceptionCode = null
    ) {
        if ($expectedExceptionCode !== null) {
            $this->expectExceptionCode($expectedExceptionCode);
        }
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '-1';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'] = $handler;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling_statheader'] = $header;
        // faking getIndpEnv() variables
        $_SERVER['REQUEST_URI'] = '/unit-test/';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SSL_SESSION_ID'] = true;
        $GLOBALS['TYPO3_REQUEST'] = [];
        $this->prophesizeGetUrl();
        $this->prophesizeErrorPageController();
        $subject = new ErrorController();
        $response = $subject->unavailableAction(new ServerRequest(), $message);
        if (is_array($expectedResponseDetails)) {
            $this->assertInstanceOf($expectedResponseDetails['type'], $response);
            $this->assertEquals($expectedResponseDetails['statusCode'], $response->getStatusCode());
            $this->assertEquals($expectedResponseDetails['reasonPhrase'], $response->getReasonPhrase());
            if (isset($expectedResponseDetails['content'])) {
                $this->assertContains($expectedResponseDetails['content'], $response->getBody()->getContents());
            }
            $this->assertEquals($expectedResponseDetails['headers'], $response->getHeaders());
        }
    }

    /**
     * @test
     */
    public function pageUnavailableHandlingReturnsResponseOfPrefix()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '-1';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'] = '/fail/';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling_statheader'] = 'HTTP/1.0 503 Service Temporarily Unavailable
X-TYPO3-Additional-Header: Banana Stand';
        // faking getIndpEnv() variables
        $_SERVER['REQUEST_URI'] = '/unit-test/';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SSL_SESSION_ID'] = true;
        $GLOBALS['TYPO3_REQUEST'] = [];
        $this->prophesizeErrorPageController();
        $this->prophesizeGetUrl();
        $subject = new ErrorController();
        $response = $subject->unavailableAction(new ServerRequest(), 'custom message');

        $expectedResponseDetails = [
            'type' => HtmlResponse::class,
            'statusCode' => 503,
            'reasonPhrase' => 'Service Temporarily Unavailable',
            'headers' => [
                'Content-Type' => ['text/html; charset=utf-8'],
                'X-TYPO3-Additional-Header' => ['Banana Stand'],
            ],
        ];
        $this->assertInstanceOf($expectedResponseDetails['type'], $response);
        $this->assertEquals($expectedResponseDetails['statusCode'], $response->getStatusCode());
        $this->assertEquals($expectedResponseDetails['reasonPhrase'], $response->getReasonPhrase());
        if (isset($expectedResponseDetails['content'])) {
            $this->assertContains($expectedResponseDetails['content'], $response->getBody()->getContents());
        }
        $this->assertEquals($expectedResponseDetails['headers'], $response->getHeaders());
    }

    /**
     * Callback function when testing "USER_FUNCTION:" prefix
     */
    public function mockedUserFunctionCall($params)
    {
        return '<p>It\'s magic, Michael: ' . $params['reasonText'] . '</p>';
    }

    private function prophesizeErrorPageController(): void
    {
        $errorPageControllerProphecy = $this->prophesize(ErrorPageController::class);
        $errorPageControllerProphecy->errorAction(Argument::cetera())
            ->will(
                function ($args) {
                    return 'Reason: ' . $args[1];
                }
            );
        GeneralUtility::addInstance(ErrorPageController::class, $errorPageControllerProphecy->reveal());
    }

    private function prophesizeGetUrl(): void
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $prefixPageResponseProphecy = $this->prophesize(Response::class);
        $prefixPageResponseProphecy->getHeaders()->willReturn([]);
        $prefixPageResponseProphecy->getBody()->willReturn($streamProphecy);
        $prefixPageResponseProphecy->getStatusCode()->willReturn(200);
        $prefixPageResponseProphecy->getHeaderLine('Content-Type')->willReturn('text/html; charset=utf-8');
        $requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        $requestFactoryProphecy->request(Argument::cetera())->willReturn($prefixPageResponseProphecy->reveal());
        GeneralUtility::addInstance(RequestFactory::class, $requestFactoryProphecy->reveal());
    }
}
