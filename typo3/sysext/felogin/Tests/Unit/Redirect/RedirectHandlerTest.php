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

namespace TYPO3\CMS\FrontendLogin\Tests\Unit\Redirect;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\FrontendLogin\Configuration\RedirectConfiguration;
use TYPO3\CMS\FrontendLogin\Redirect\RedirectHandler;
use TYPO3\CMS\FrontendLogin\Redirect\RedirectMode;
use TYPO3\CMS\FrontendLogin\Redirect\RedirectModeHandler;
use TYPO3\CMS\FrontendLogin\Validation\RedirectUrlValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RedirectHandlerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected RedirectHandler $subject;
    protected ServerRequestInterface $typo3Request;
    protected MockObject&RedirectModeHandler $redirectModeHandler;
    protected MockObject&RedirectUrlValidator $redirectUrlValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redirectModeHandler = $this->createMock(RedirectModeHandler::class);
        $this->redirectUrlValidator = $this->createMock(RedirectUrlValidator::class);

        $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();

        $this->subject = new RedirectHandler(
            $this->redirectModeHandler,
            $this->redirectUrlValidator,
            new Context()
        );
    }

    public static function loginTypeLogoutDataProvider(): \Generator
    {
        yield 'empty string on empty redirect mode' => ['', ''];
        yield 'empty string on redirect mode logout' => ['', 'logout'];
    }

    #[DataProvider('loginTypeLogoutDataProvider')]
    #[Test]
    public function processShouldReturnStringForLoginTypeLogout(string $expect, string $redirectMode): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);

        $this->redirectModeHandler->method('redirectModeLogout')->with($request, 0)->willReturn('');

        $result = $this->subject->processRedirect($request, 'logout', new RedirectConfiguration($redirectMode, '', 0, '', 0, 0), '');
        self::assertEquals($expect, $result);
    }

    protected function getContextMockWithUserLoggedIn(bool $userLoggedIn = true): Context
    {
        $mockUserAuthentication = $this->getMockBuilder(FrontendUserAuthentication::class)->disableOriginalConstructor()->getMock();
        $mockUserAuthentication->user['uid'] = $userLoggedIn ? 1 : 0;
        $context = new Context();
        $context->setAspect('frontend.user', new UserAspect($mockUserAuthentication));
        return $context;
    }

    public static function getLoginFormRedirectUrlDataProvider(): array
    {
        return [
            'redirect disabled' => [
                'no url',
                'getpost',
                true,
                '',
            ],
            'redirect enabled, GET/POST redirect mode not configured' => [
                'https://redirect.url',
                'login',
                false,
                '',
            ],
            'redirect enabled, GET/POST redirect mode configured, invalid URL' => [
                'https://invalid-redirect.url',
                'login,getpost',
                false,
                '',
            ],
            'redirect enabled, GET/POST redirect mode configured, valid URL' => [
                'https://redirect.url',
                'login,getpost',
                false,
                'https://redirect.url',
            ],
        ];
    }

    #[DataProvider('getLoginFormRedirectUrlDataProvider')]
    #[Test]
    public function getLoginFormRedirectUrlReturnsExpectedValue(
        string $redirectUrl,
        string $redirectMode,
        bool $redirectDisabled,
        string $expected
    ): void {
        $this->subject = new RedirectHandler(
            $this->redirectModeHandler,
            $this->redirectUrlValidator,
            new Context()
        );

        $serverRequest = (new ServerRequest())->withQueryParams(['redirect_url' => $redirectUrl])->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);

        if ($redirectUrl === $expected) {
            $this->redirectUrlValidator->expects(self::once())->method('isValid')->with($request, $redirectUrl)->willReturn(true);
        }

        $configuration = RedirectConfiguration::fromSettings(['redirectMode' => $redirectMode]);
        self::assertEquals($expected, $this->subject->getLoginFormRedirectUrl($request, $configuration, $redirectDisabled));
    }

    #[Test]
    public function getReferrerForLoginFormReturnsEmptyStringIfRedirectModeReferrerDisabled(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);
        $settings = ['redirectMode' => RedirectMode::LOGIN];
        self::assertEquals('', $this->subject->getReferrerForLoginForm($request, $settings));
    }

    #[Test]
    public function getReferrerForLoginFormReturnsReferrerGetParameter(): void
    {
        $expectedReferrer = 'https://example.com/page-referrer';
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withQueryParams(['referer' => $expectedReferrer]);
        $request = new Request($serverRequest);
        $this->redirectUrlValidator->expects(self::once())->method('isValid')->with($request, $expectedReferrer)->willReturn(true);
        $settings = ['redirectMode' => RedirectMode::REFERRER];
        self::assertEquals($expectedReferrer, $this->subject->getReferrerForLoginForm($request, $settings));
    }

    #[Test]
    public function getReferrerForLoginFormReturnsReferrerPostParameter(): void
    {
        $expectedReferrer = 'https://example.com/page-referrer';
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withParsedBody(['referer' => $expectedReferrer]);
        $request = new Request($serverRequest);
        $this->redirectUrlValidator->expects(self::once())->method('isValid')->with($request, $expectedReferrer)->willReturn(true);
        $settings = ['redirectMode' => RedirectMode::REFERRER];
        self::assertEquals($expectedReferrer, $this->subject->getReferrerForLoginForm($request, $settings));
    }

    #[Test]
    public function getReferrerForLoginFormReturnsHttpReferrerParameter(): void
    {
        $expectedReferrer = 'https://example.com/page-referrer';
        $serverRequest = (new ServerRequest('/login', 'GET', 'php://input', [], ['HTTP_REFERER' => $expectedReferrer]))
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);
        $this->redirectUrlValidator->expects(self::once())->method('isValid')->with($request, $expectedReferrer)->willReturn(true);
        $settings = ['redirectMode' => RedirectMode::REFERRER];
        self::assertEquals($expectedReferrer, $this->subject->getReferrerForLoginForm($request, $settings));
    }

    #[Test]
    public function getReferrerForLoginFormReturnsOriginalRequestUrlIfCalledBySubRequest(): void
    {
        $expectedReferrer = 'https://example.com/original-page';
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('originalRequest', new ServerRequest($expectedReferrer));
        $request = new Request($serverRequest);
        $this->redirectUrlValidator->expects(self::once())->method('isValid')->with($request, $expectedReferrer)->willReturn(true);
        $settings = ['redirectMode' => RedirectMode::REFERRER];
        self::assertEquals($expectedReferrer, $this->subject->getReferrerForLoginForm($request, $settings));
    }

    #[Test]
    public function getReferrerForLoginFormReturnsNoUrlIfRedirectReferrerIsOff(): void
    {
        $serverRequest = (new ServerRequest(
            '/login',
            'GET',
            'php://input',
            [],
            ['HTTP_REFERER' => 'https://example.com/page-referrer']
        ))->withQueryParams(['tx_felogin_login' => ['redirectReferrer' => 'off']])
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);
        $this->redirectUrlValidator->expects(self::never())->method('isValid');
        $settings = ['redirectMode' => RedirectMode::REFERRER];
        self::assertEquals('', $this->subject->getReferrerForLoginForm($request, $settings));
    }
}
