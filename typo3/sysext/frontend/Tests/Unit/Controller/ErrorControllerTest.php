<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\Tests\Unit\Controller;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ErrorControllerTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function pageNotFoundHandlingThrowsExceptionIfNotConfigured()
    {
        $typo3InformationProphecy = $this->prophesize(Typo3Information::class);
        $typo3InformationProphecy->getCopyrightYear()->willReturn('1999-20XX');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationProphecy->reveal());
        $GLOBALS['TYPO3_REQUEST'] = [];
        $subject = new ErrorController();
        $response = $subject->pageNotFoundAction(new ServerRequest(), 'This test page was not found!');
        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('This test page was not found!', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function unavailableHandlingThrowsExceptionIfNotConfigured()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->expectExceptionMessage('All your system are belong to us!');
        $this->expectExceptionCode(1518472181);
        $subject = new ErrorController();
        $subject->unavailableAction(new ServerRequest(), 'All your system are belong to us!');
    }

    /**
     * @test
     */
    public function unavailableHandlingDoesNotTriggerDueToDevIpMask()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->expectExceptionMessage('All your system are belong to us!');
        $this->expectExceptionCode(1518472181);
        $subject = new ErrorController();
        $subject->unavailableAction(new ServerRequest(), 'All your system are belong to us!');
    }

    /**
     * @test
     */
    public function defaultErrorHandlerWithHtmlResponseIsChosenWhenNoSiteConfiguredForPageNotFoundAction()
    {
        $typo3InformationProphecy = $this->prophesize(Typo3Information::class);
        $typo3InformationProphecy->getCopyrightYear()->willReturn('1999-20XX');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationProphecy->reveal());
        $subject = new ErrorController();
        $response = $subject->pageNotFoundAction(new ServerRequest(), 'Error handler is not configured.');
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Error handler is not configured.', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function defaultErrorHandlerWithJsonResponseIsChosenWhenNoSiteConfiguredForPageNotFoundAction()
    {
        $subject = new ErrorController();
        $response = $subject->pageNotFoundAction((new ServerRequest())->withAddedHeader('Accept', 'application/json'), 'Error handler is not configured.');
        $responseContent = \json_decode($response->getBody()->getContents(), true);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertEquals(['reason' => 'Error handler is not configured.'], $responseContent);
    }

    /**
     * @test
     */
    public function defaultErrorHandlerWithHtmlResponseIsChosenWhenNoSiteConfiguredForUnavailableAction()
    {
        $typo3InformationProphecy = $this->prophesize(Typo3Information::class);
        $typo3InformationProphecy->getCopyrightYear()->willReturn('1999-20XX');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationProphecy->reveal());
        $subject = new ErrorController();
        $response = $subject->unavailableAction(new ServerRequest(), 'Error handler is not configured.');
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Error handler is not configured.', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function defaultErrorHandlerWithJsonResponseIsChosenWhenNoSiteConfiguredForUnavailableAction()
    {
        $subject = new ErrorController();
        $response = $subject->unavailableAction((new ServerRequest())->withAddedHeader('Accept', 'application/json'), 'Error handler is not configured.');
        $responseContent = \json_decode($response->getBody()->getContents(), true);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertEquals(['reason' => 'Error handler is not configured.'], $responseContent);
    }

    /**
     * @test
     */
    public function defaultErrorHandlerWithHtmlResponseIsChosenWhenNoSiteConfiguredForAccessDeniedAction()
    {
        $typo3InformationProphecy = $this->prophesize(Typo3Information::class);
        $typo3InformationProphecy->getCopyrightYear()->willReturn('1999-20XX');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationProphecy->reveal());
        $subject = new ErrorController();
        $response = $subject->accessDeniedAction(new ServerRequest(), 'Error handler is not configured.');
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Error handler is not configured.', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function defaultErrorHandlerWithJsonResponseIsChosenWhenNoSiteConfiguredForAccessDeniedAction()
    {
        $subject = new ErrorController();
        $response = $subject->accessDeniedAction((new ServerRequest())->withAddedHeader('Accept', 'application/json'), 'Error handler is not configured.');
        $responseContent = \json_decode($response->getBody()->getContents(), true);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertEquals(['reason' => 'Error handler is not configured.'], $responseContent);
    }
}
