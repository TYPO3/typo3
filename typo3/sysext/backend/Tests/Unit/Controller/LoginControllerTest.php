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

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LoginControllerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var LoginController|MockObject|AccessibleObjectInterface
     */
    protected $loginControllerMock;

    /**
     * @var bool
     * @see prophesizeFormProtection
     */
    protected static bool $alreadySetUp = false;

    /**
     * @throws \InvalidArgumentException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loginControllerMock = $this->getAccessibleMock(LoginController::class, ['dummy'], [], '', false);
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
                    'redirectToURL' => 'http://example.com',
                ],
            ],
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
