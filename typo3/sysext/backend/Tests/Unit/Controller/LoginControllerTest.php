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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderResolver;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LoginControllerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function checkRedirectRedirectsIfLoginIsInProgressAndUserWasFound(): void
    {
        $GLOBALS['LANG'] = ($this->prophesize(LanguageService::class))->reveal();
        $authenticationProphecy = $this->prophesize(BackendUserAuthentication::class);
        $authenticationProphecy->getTSConfig()->willReturn([
            'auth.' => [
                'BE.' => [
                    'redirectToURL' => 'http://example.com',
                ],
            ],
        ]);
        $authenticationProphecy->writeUC()->willReturn();
        $authenticationProphecy->getSessionData('formProtectionSessionToken')->willReturn('foo');
        $GLOBALS['BE_USER'] = $authenticationProphecy->reveal();

        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createFromRequest')->willReturn($this->createMock(BackendFormProtection::class));
        $loginControllerMock = $this->getAccessibleMock(
            LoginController::class,
            ['isLoginInProgress', 'redirectToUrl'],
            [
                new Typo3Information(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(PageRenderer::class),
                $this->createMock(UriBuilder::class),
                new Features(),
                new Context(),
                $this->createMock(LoginProviderResolver::class),
                new ExtensionConfiguration(),
                new BackendEntryPointResolver(),
                $formProtectionFactory,
            ],
        );

        $GLOBALS['BE_USER']->user['uid'] = 1;
        $loginControllerMock->method('isLoginInProgress')->willReturn(true);
        $loginControllerMock->_set('loginRefresh', false);

        $loginControllerMock->expects(self::once())->method('redirectToUrl');
        $loginControllerMock->_call(
            'checkRedirect',
            $this->prophesize(ServerRequest::class)->reveal(),
            $this->prophesize(PageRenderer::class)->reveal()
        );
    }

    /**
     * @test
     */
    public function checkRedirectDoesNotRedirectIfNoUserIsFound(): void
    {
        $GLOBALS['BE_USER'] = $this->prophesize(BackendUserAuthentication::class)->reveal();
        $loginControllerMock = $this->getAccessibleMock(
            LoginController::class,
            ['redirectToUrl'],
            [],
            '',
            false
        );

        $GLOBALS['BE_USER']->user['uid'] = null;

        $loginControllerMock->expects(self::never())->method('redirectToUrl');
        $loginControllerMock->_call(
            'checkRedirect',
            $this->prophesize(ServerRequest::class)->reveal(),
            $this->prophesize(PageRenderer::class)->reveal()
        );
    }
}
