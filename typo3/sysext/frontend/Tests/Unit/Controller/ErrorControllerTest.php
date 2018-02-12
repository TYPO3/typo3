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

use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Testcase for \TYPO3\CMS\Frontend\Controller\ErrorController
 */
class ErrorControllerTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * Tests concerning pageNotFound handling
     */

    /**
     * @test
     */
    public function pageNotFoundHandlingThrowsExceptionIfNotConfigured()
    {
        $this->expectExceptionMessage('This test page was not found!');
        $this->expectExceptionCode(1518472189);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = false;
        $subject = new ErrorController();
        $subject->pageNotFoundAction('This test page was not found!');
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
                        'Content-Type' => ['text/html; charset=utf-8']
                    ]
                ]
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
                        'Content-Type' => ['text/html; charset=utf-8']
                    ]
                ]
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
                        'Content-Type' => ['text/html; charset=utf-8']
                    ]
                ]
            ],
            '404 with a readfile functionality' => [
                'handler' => 'READFILE:LICENSE.txt',
                'header' => 'HTTP/1.0 404 Not Found',
                'message' => 'Custom message',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 404,
                    'reasonPhrase' => 'Not Found',
                    'content' => 'GNU GENERAL PUBLIC LICENSE',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8']
                    ]
                ]
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
                    ]
                ]
            ],
            'Custom path, no prefix' => [
                'handler' => '/404/',
                'header' => 'HTTP/1.0 404 Not Found
X-TYPO3-Additional-Header: Banana Stand',
                'message' => 'Custom message',
                'response' => [
                    'type' => RedirectResponse::class,
                    'statusCode' => 404,
                    'reasonPhrase' => 'Not Found',
                    'headers' => [
                        'location' => ['https://localhost/404/'],
                        'X-TYPO3-Additional-Header' => ['Banana Stand'],
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider errorPageHandlingDataProvider
     */
    public function pageNotFoundHandlingReturnsConfiguredResponseObject($handler, $header, $message, $expectedResponseDetails, $expectedExceptionCode = null)
    {
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
        $subject = new ErrorController();
        $response = $subject->pageNotFoundAction($message);
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
     * Tests concerning accessDenied handling
     */

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
                        'Content-Type' => ['text/html; charset=utf-8']
                    ]
                ]
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
        $subject = new ErrorController();
        $response = $subject->accessDeniedAction($message);
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
     * Tests concerning unavailable handling
     */

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
        $subject->unavailableAction('All your system are belong to us!');
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
        $subject->unavailableAction('All your system are belong to us!');
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
                    'reasonPhrase' => 'Not Found',
                    'content' => 'Reason: Custom message',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8']
                    ]
                ]
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
                        'Content-Type' => ['text/html; charset=utf-8']
                    ]
                ]
            ],
            '503 with custom userfunction' => [
                'handler' => 'USER_FUNCTION:' . ErrorControllerTest::class . '->mockedUserFunctionCall',
                'header' => 'HTTP/1.0 503 Service Temporarily Unavailable',
                'message' => 'Custom message',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 503,
                    'reasonPhrase' => 'Not Found',
                    'content' => 'It\'s magic, Michael: Custom message',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8']
                    ]
                ]
            ],
            '503 with a readfile functionality' => [
                'handler' => 'READFILE:LICENSE.txt',
                'header' => 'HTTP/1.0 503 Service Temporarily Unavailable',
                'message' => 'Custom message',
                'response' => [
                    'type' => HtmlResponse::class,
                    'statusCode' => 503,
                    'reasonPhrase' => 'Not Found',
                    'content' => 'GNU GENERAL PUBLIC LICENSE',
                    'headers' => [
                        'Content-Type' => ['text/html; charset=utf-8']
                    ]
                ]
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
                    'reasonPhrase' => 'Not Found',
                    'headers' => [
                        'location' => ['www.typo3.org'],
                        'X-TYPO3-Additional-Header' => ['Banana Stand'],
                    ]
                ]
            ],
            'Custom path, no prefix' => [
                'handler' => '/fail/',
                'header' => 'HTTP/1.0 503 Service Temporarily Unavailable
X-TYPO3-Additional-Header: Banana Stand',
                'message' => 'Custom message',
                'response' => [
                    'type' => RedirectResponse::class,
                    'statusCode' => 503,
                    'reasonPhrase' => 'Not Found',
                    'headers' => [
                        'location' => ['https://localhost/fail/'],
                        'X-TYPO3-Additional-Header' => ['Banana Stand'],
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider errorPageHandlingDataProvider
     */
    public function pageUnavailableHandlingReturnsConfiguredResponseObject($handler, $header, $message, $expectedResponseDetails, $expectedExceptionCode = null)
    {
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
        $subject = new ErrorController();
        $response = $subject->unavailableAction($message);
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
     * Callback function when testing "USER_FUNCTION:" prefix
     */
    public function mockedUserFunctionCall($params)
    {
        return '<p>It\'s magic, Michael: ' . $params['reasonText'] . '</p>';
    }
}
