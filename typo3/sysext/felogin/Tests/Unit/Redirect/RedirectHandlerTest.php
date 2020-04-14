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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
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
    /**
     * If set to true, tearDown() will purge singleton instances created by the test.
     *
     * @var bool
     */
    protected $resetSingletonInstances = true;

    /**
     * @var RedirectHandler
     */
    protected $subject;

    /**
     * @var ServerRequestInterface
     */
    protected $typo3Request;

    /**
     * @var ServerRequestHandler
     */
    protected $serverRequestHandler;

    /**
     * @var RedirectModeHandler
     */
    protected $redirectModeHandler;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|Context
     */
    protected $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverRequestHandler = $this->prophesize(ServerRequestHandler::class);
        $this->redirectModeHandler = $this->prophesize(RedirectModeHandler::class);
        $this->context = $this->prophesize(Context::class);

        $GLOBALS['TSFE'] = $this->prophesize(TypoScriptFrontendController::class)->reveal();

        $this->subject = new RedirectHandler(
            $this->serverRequestHandler->reveal(),
            $this->redirectModeHandler->reveal(),
            $this->context->reveal()
        );
    }

    /**
     * @test
     * @dataProvider loginTypeLogoutDataProvider
     * @param string $expect
     * @param array $settings
     */
    public function processShouldReturnStringForLoginTypeLogout(string $expect, string $redirectMode): void
    {
        $this->redirectModeHandler->redirectModeLogout(0)->willReturn('');

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
     * @param string $expected
     * @param array $redirectModes
     * @param array $body
     * @param bool $userLoggedIn
     */
    public function getLogoutRedirectUrlShouldReturnAlternativeRedirectUrl(
        string $expected,
        array $redirectModes,
        array $body,
        bool $userLoggedIn
    ): void {
        $this->setUserLoggedIn($userLoggedIn);

        $this->serverRequestHandler
            ->getRedirectUrlRequestParam()
            ->willReturn($body['return_url'] ?? '');

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
            false
        ];
        yield 'redirect mode getpost,logout should return param return_url on not logged in user' => [
            'https://dummy.url/3',
            ['getpost', 'logout'],
            ['return_url' => 'https://dummy.url/3'],
            false
        ];
    }

    /**
     * @test
     */
    public function getLogoutRedirectUrlShouldReturnAlternativeRedirectUrlForLoggedInUserAndRedirectPageLogoutSet(
    ): void {
        $this->setUserLoggedIn(true);

        $this->subject = new RedirectHandler(
            $this->serverRequestHandler->reveal(),
            $this->redirectModeHandler->reveal(),
            $this->context->reveal()
        );

        $this->serverRequestHandler
            ->getRedirectUrlRequestParam()
            ->willReturn([]);

        $this->redirectModeHandler
            ->redirectModeLogout(3)
            ->willReturn('https://logout.url');

        $configuration = RedirectConfiguration::fromSettings(['redirectMode' => ['logout']]);
        self::assertEquals('https://logout.url', $this->subject->getLogoutFormRedirectUrl($configuration, 3, false));
    }

    protected function setUserLoggedIn(bool $userLoggedIn): void
    {
        $this->context
            ->getPropertyFromAspect('frontend.user', 'isLoggedIn')
            ->willReturn($userLoggedIn);
    }
}
