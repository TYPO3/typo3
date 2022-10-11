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

use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\FrontendLogin\Configuration\RedirectConfiguration;
use TYPO3\CMS\FrontendLogin\Redirect\RedirectHandler;
use TYPO3\CMS\FrontendLogin\Redirect\RedirectModeHandler;
use TYPO3\CMS\FrontendLogin\Redirect\ServerRequestHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RedirectHandlerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @var RedirectHandler
     */
    protected $subject;

    /**
     * @var ServerRequestInterface
     */
    protected $typo3Request;

    protected MockObject&ServerRequestHandler $serverRequestHandler;
    protected MockObject&RedirectModeHandler $redirectModeHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverRequestHandler = $this->getMockBuilder(ServerRequestHandler::class)->disableOriginalConstructor()->getMock();
        $this->redirectModeHandler = $this->getMockBuilder(RedirectModeHandler::class)->disableOriginalConstructor()->getMock();

        $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();

        $this->subject = new RedirectHandler(
            $this->serverRequestHandler,
            $this->redirectModeHandler,
            new Context()
        );
    }

    /**
     * @test
     * @dataProvider loginTypeLogoutDataProvider
     */
    public function processShouldReturnStringForLoginTypeLogout(string $expect, string $redirectMode): void
    {
        $this->redirectModeHandler->method('redirectModeLogout')->with(0)->willReturn('');

        self::assertEquals($expect, $this->subject->processRedirect('logout', new RedirectConfiguration($redirectMode, '', 0, '', 0, 0), ''));
    }

    public function loginTypeLogoutDataProvider(): Generator
    {
        yield 'empty string on empty redirect mode' => ['', ''];
        yield 'empty string on redirect mode logout' => ['', 'logout'];
    }

    /**
     * @test
     * @dataProvider getLogoutRedirectUrlDataProvider
     */
    public function getLogoutRedirectUrlShouldReturnAlternativeRedirectUrl(
        string $expected,
        array $redirectModes,
        array $body,
        bool $userLoggedIn
    ): void {
        $this->subject = new RedirectHandler(
            $this->serverRequestHandler,
            $this->redirectModeHandler,
            $this->getContextMockWithUserLoggedIn($userLoggedIn)
        );

        $this->serverRequestHandler->method('getRedirectUrlRequestParam')->willReturn($body['return_url'] ?? '');

        $configuration = RedirectConfiguration::fromSettings(['redirectMode' => $redirectModes]);
        self::assertEquals($expected, $this->subject->getLogoutFormRedirectUrl($configuration, 13, false));
    }

    public function getLogoutRedirectUrlDataProvider(): Generator
    {
        yield 'empty redirect mode should return empty returnUrl' => ['', [], [], false];
        yield 'redirect mode getpost should return param return_url' => [
            'https://dummy.url',
            ['getpost'],
            ['return_url' => 'https://dummy.url'],
            false,
        ];
        yield 'redirect mode getpost,logout should return param return_url on not logged in user' => [
            'https://dummy.url/3',
            ['getpost', 'logout'],
            ['return_url' => 'https://dummy.url/3'],
            false,
        ];
    }

    /**
     * @test
     */
    public function getLogoutRedirectUrlShouldReturnAlternativeRedirectUrlForLoggedInUserAndRedirectPageLogoutSet(): void
    {
        $this->subject = new RedirectHandler(
            $this->serverRequestHandler,
            $this->redirectModeHandler,
            $this->getContextMockWithUserLoggedIn()
        );

        $this->serverRequestHandler->method('getRedirectUrlRequestParam')->willReturn('');
        $this->redirectModeHandler->method('redirectModeLogout')->with(3)->willReturn('https://logout.url');

        $configuration = RedirectConfiguration::fromSettings(['redirectMode' => ['logout']]);
        self::assertEquals('https://logout.url', $this->subject->getLogoutFormRedirectUrl($configuration, 3, false));
    }

    protected function getContextMockWithUserLoggedIn(bool $userLoggedIn = true): Context
    {
        $mockUserAuthentication = $this->getMockBuilder(FrontendUserAuthentication::class)
            ->disableOriginalConstructor()->getMock();
        $mockUserAuthentication->user['uid'] = $userLoggedIn ? 1 : 0;
        return new Context(['frontend.user' => new UserAspect($mockUserAuthentication)]);
    }

    public function getLoginFormRedirectUrlDataProvider(): array
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
            'redirect enabled, GET/POST redirect mode configured' => [
                'https://redirect.url',
                'login,getpost',
                false,
                'https://redirect.url',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getLoginFormRedirectUrlDataProvider
     */
    public function getLoginFormRedirectUrlReturnsExpectedValue(
        string $redirectUrl,
        string $redirectMode,
        bool $redirectDisabled,
        string $expected
    ) {
        $this->subject = new RedirectHandler(
            $this->serverRequestHandler,
            $this->redirectModeHandler,
            new Context()
        );

        $this->serverRequestHandler->method('getRedirectUrlRequestParam')->willReturn($redirectUrl);

        $configuration = RedirectConfiguration::fromSettings(['redirectMode' => $redirectMode]);
        self::assertEquals($expected, $this->subject->getLoginFormRedirectUrl($configuration, $redirectDisabled));
    }
}
