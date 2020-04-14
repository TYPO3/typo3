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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LoginControllerTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var LoginController|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $loginControllerMock;

    /**
     * @var bool
     * @see prophesizeFormProtection
     */
    protected static $alreadySetUp = false;

    /**
     * @throws \InvalidArgumentException
     */
    protected function setUp(): void
    {
        $this->loginControllerMock = $this->getAccessibleMock(LoginController::class, ['dummy'], [], '', false);
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingProviderConfiguration()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433417281);
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders']);
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsNonArrayProviderConfiguration()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433417281);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = 'foo';
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsIfNoProviderIsRegistered()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433417281);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingConfigurationForProvider()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433416043);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [],
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsWrongProvider()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1460977275);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => \stdClass::class,
            ],
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingLabel()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433416044);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'sorting' => 30,
                'icon-class' => 'foo',
            ],
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingIconClass()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433416045);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'sorting' => 30,
                'label' => 'foo',
            ],
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingSorting()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433416046);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'label' => 'foo',
                'icon-class' => 'foo',
            ],
        ];
        $this->loginControllerMock->_call('validateAndSortLoginProviders');
    }

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
                    'redirectToURL' => 'http://example.com'
                ]
            ]
        ]);
        $authenticationProphecy->writeUC()->willReturn();
        $authenticationProphecy->getSessionData('formProtectionSessionToken')->willReturn('foo');
        $GLOBALS['BE_USER'] = $authenticationProphecy->reveal();
        $this->prophesizeFormProtection();

        $this->loginControllerMock = $this->getAccessibleMock(
            LoginController::class,
            ['isLoginInProgress', 'redirectToUrl'],
            [],
            '',
            false
        );

        $GLOBALS['BE_USER']->user['uid'] = 1;
        $this->loginControllerMock->method('isLoginInProgress')->willReturn(true);
        $this->loginControllerMock->_set('loginRefresh', false);

        $this->loginControllerMock->expects(self::once())->method('redirectToUrl');
        $this->loginControllerMock->_call(
            'checkRedirect',
            $this->prophesize(ServerRequest::class)->reveal(),
            $this->prophesize(PageRenderer::class)->reveal()
        );
    }

    /**
     * @test
     */
    public function checkRedirectAddsJavaScriptForCaseLoginRefresh(): void
    {
        $GLOBALS['LANG'] = $this->prophesize(LanguageService::class)->reveal();
        $authenticationProphecy = $this->prophesize(BackendUserAuthentication::class);
        $authenticationProphecy->getTSConfig()->willReturn([
            'auth.' => [
                'BE.' => [
                    'redirectToURL' => 'http://example.com'
                ]
            ]
        ]);
        $authenticationProphecy->writeUC()->willReturn();
        $this->prophesizeFormProtection();
        $GLOBALS['BE_USER'] = $authenticationProphecy->reveal();

        $this->loginControllerMock = $this->getAccessibleMock(
            LoginController::class,
            ['isLoginInProgress', 'redirectToUrl'],
            [],
            '',
            false
        );

        $GLOBALS['BE_USER']->user['uid'] = 1;
        $this->loginControllerMock->method('isLoginInProgress')->willReturn(false);
        $this->loginControllerMock->_set('loginRefresh', true);
        /** @var ObjectProphecy|PageRenderer $pageRendererProphecy */
        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        /** @var MethodProphecy $inlineCodeProphecy */
        $inlineCodeProphecy = $pageRendererProphecy->addJsInlineCode('loginRefresh', Argument::cetera());
        $this->loginControllerMock->_set('pageRenderer', $pageRendererProphecy->reveal());

        $this->loginControllerMock->_call(
            'checkRedirect',
            $this->prophesize(ServerRequest::class)->reveal()
        );

        $inlineCodeProphecy->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function checkRedirectAddsJavaScriptForCaseLoginRefreshWhileLoginIsInProgress(): void
    {
        $GLOBALS['LANG'] = $this->prophesize(LanguageService::class)->reveal();
        $authenticationProphecy = $this->prophesize(BackendUserAuthentication::class);
        $authenticationProphecy->getTSConfig()->willReturn([
            'auth.' => [
                'BE.' => [
                    'redirectToURL' => 'http://example.com'
                ]
            ]
        ]);
        $authenticationProphecy->writeUC()->willReturn();
        $GLOBALS['BE_USER'] = $authenticationProphecy->reveal();
        $this->prophesizeFormProtection();

        $this->loginControllerMock = $this->getAccessibleMock(
            LoginController::class,
            ['isLoginInProgress', 'redirectToUrl'],
            [],
            '',
            false
        );

        $GLOBALS['BE_USER']->user['uid'] = 1;
        $this->loginControllerMock->method('isLoginInProgress')->willReturn(true);
        $this->loginControllerMock->_set('loginRefresh', true);
        /** @var ObjectProphecy|PageRenderer $pageRendererProphecy */
        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        /** @var MethodProphecy $inlineCodeProphecy */
        $inlineCodeProphecy = $pageRendererProphecy->addJsInlineCode('loginRefresh', Argument::cetera());
        $this->loginControllerMock->_set('pageRenderer', $pageRendererProphecy->reveal());

        $this->loginControllerMock->_call(
            'checkRedirect',
            $this->prophesize(ServerRequest::class)->reveal()
        );

        $inlineCodeProphecy->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function checkRedirectDoesNotRedirectIfNoUserIsFound(): void
    {
        $GLOBALS['BE_USER'] = $this->prophesize(BackendUserAuthentication::class)->reveal();
        $this->loginControllerMock = $this->getAccessibleMock(
            LoginController::class,
            ['redirectToUrl'],
            [],
            '',
            false
        );

        $GLOBALS['BE_USER']->user['uid'] = null;

        $this->loginControllerMock->expects(self::never())->method('redirectToUrl');
        $this->loginControllerMock->_call(
            'checkRedirect',
            $this->prophesize(ServerRequest::class)->reveal(),
            $this->prophesize(PageRenderer::class)->reveal()
        );
    }

    /**
     * FormProtectionFactory has an internal static instance cache we need to work around here
     */
    protected function prophesizeFormProtection(): void
    {
        if (!self::$alreadySetUp) {
            $formProtectionProphecy = $this->prophesize(BackendFormProtection::class);
            GeneralUtility::addInstance(BackendFormProtection::class, $formProtectionProphecy->reveal());
            self::$alreadySetUp = true;
        }
    }
}
