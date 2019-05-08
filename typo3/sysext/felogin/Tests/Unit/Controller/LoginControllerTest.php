<?php
declare(strict_types=1);

namespace TYPO3\CMS\Felogin\Tests\Unit\Controller;

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
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Felogin\Controller\LoginController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LoginControllerTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|ViewInterface
     */
    protected $view;

    /**
     * @var LoginController
     */
    protected $subject;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|ServerRequestInterface
     */
    protected $request;

    protected function setUp(): void
    {
        $this->view = $this->prophesize(ViewInterface::class);
        $this->configurationManager = $this->prophesize(ConfigurationManagerInterface::class);
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->subject = new LoginController();

        $GLOBALS['TSFE'] = $this->prophesize(TypoScriptFrontendController::class)->reveal();
        $GLOBALS['TYPO3_REQUEST'] = $this->request->reveal();

        $this->subject->injectConfigurationManager($this->configurationManager->reveal());
        $this->inject($this->subject, 'view', $this->view->reveal());

        parent::setUp();
    }

    /**
     * @test
     */
    public function loginActionShouldAssingStoragePidToView(): void
    {
        $this->setUserLoggedIn(false);

        $this->configurationManager
            ->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
            )
            ->willReturn(
                [
                    'persistence' => [
                        'storagePid' => '1,2,3'
                    ]
                ]
            );

        $this->view
            ->assignMultiple(
                Argument::withEntry(
                    'storagePid', '1,2,3'
                )
            )
            ->shouldBeCalled();

        $this->subject->loginAction();
    }

    /**
     * @test
     */
    public function loginActionShouldAssingDefaultMessageKey(): void
    {
        $this->assertMessageKey(LoginController::MESSAGEKEY_DEFAULT);

        $this->subject->loginAction();
    }

    /**
     * @test
     */
    public function loginActionShouldAssingLoginFailedToViewOnNotLoggedInUser(): void
    {
        $this->setLoginType();
        $this->setUserLoggedIn(false);

        $this->assertMessageKey(LoginController::MESSAGEKEY_ERROR);

        $this->subject->loginAction();
    }

    /**
     * @test
     */
    public function loginActionShouldAssingLogoutMessageKey(): void
    {
        $this->setLoginType(LoginType::LOGOUT);

        $this->assertMessageKey(LoginController::MESSAGEKEY_LOGOUT);

        $this->subject->loginAction();
    }

    /**
     * @test
     */
    public function loginActionShouldRedirectToOverviewOnSuccessfulLoginAndShowLogoutFormAfterLoginDisabled(): void
    {
        $this->setLoginType();
        $this->setUserLoggedIn(true);
        $this->inject($this->subject, 'settings', ['showLogoutFormAfterLogin' => false]);

        $webRequest = $this->injectRequestForControllerAction('overview');
        $webRequest
            ->setArguments(['showLoginMessage' => true])
            ->shouldBeCalled();

        $this->expectException(StopActionException::class);
        $this->expectExceptionMessage('forward');

        $this->subject->loginAction();
    }

    /**
     * @test
     */
    public function loginActionShouldRedirectToLogoutOnSuccessfulLoginAndShowLogoutFormAfterLoginEnabled(): void
    {
        $this->setLoginType();
        $this->setUserLoggedIn(true);

        $this->inject($this->subject, 'settings', ['showLogoutFormAfterLogin' => true]);
        $this->injectRequestForControllerAction('logout');

        $this->expectException(StopActionException::class);

        $this->subject->loginAction();
    }

    /**
     * @test
     */
    public function loginActionShouldRedirectToLogoutForAlreadyLoggedInUser(): void
    {
        $this->setUserLoggedIn(true);

        $this->injectRequestForControllerAction('logout');

        $this->expectException(StopActionException::class);

        $this->subject->loginAction();
    }

    /**
     * @test
     * @dataProvider permaloginStatusDataProvider
     */
    public function permaloginStatusShouldAddCorrectPermaloginStatus(int $expected, int $conf, int $settings, int $lifetime): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'] = $conf;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['lifetime'] = $lifetime;

        $this->inject($this->subject, 'settings', ['showPermaLogin' => $settings]);

        $this->view
            ->assignMultiple(
                Argument::withEntry(
                    'permaloginStatus', $expected
                )
            )
            ->shouldBeCalled();

        $this->subject->loginAction();
    }

    public function permaloginStatusDataProvider(): \Generator
    {
        yield '-1 => lifetime = 0' => [-1, 1, 1, 0];
        yield '-1 => setting = 0' => [-1, 1, 0, 60*60];
        yield '-1 => TYPO3_CONF_VARS -1, settings = 0' => [-1, -1, 1, 60*60];
        yield '0 => TYPO3_CONF_VARS 0, setting = 1' => [0, 0, 1, 60*60];
        yield '1 => TYPO3_CONF_VARS 1, setting = 1' => [1, 1, 1, 60*60];
        yield '-1 => TYPO3_CONF_VARS 2, setting = 1' => [-1, 2, 1, 60*60];
    }

    /**
     * @test
     */
    public function overviewActionShouldAssingUserAndLoginMessageForLoggedInUser(): void
    {
        $this->setUserLoggedIn(true);

        $feUser = $this->prophesize(FrontendUserAuthentication::class);

        $GLOBALS['TSFE']->fe_user = $feUser->reveal();
        $GLOBALS['TSFE']->fe_user->user = ['username' => 'foo'];

        $this->view->assignMultiple(
            [
                'user'         => ['username' => 'foo'],
                'loginMessage' => true
            ]
        );

        $this->subject->overviewAction(true);
    }

    /**
     * @test
     */
    public function overviewActionShouldRedirectToLoginOnNotLoggedInUser(): void
    {
        $this->setUserLoggedIn(false);

        $this->injectRequestForControllerAction('login');

        $this->expectException(StopActionException::class);
        $this->expectExceptionMessage('forward');

        $this->subject->overviewAction();
    }

    protected function setLoginType(string $loginType = LoginType::LOGIN): void
    {
        $this->request
            ->getParsedBody()
            ->willReturn(
                [
                    'logintype' => $loginType
                ]
            );
    }

    protected function setUserLoggedIn(bool $userLoggedIn): void
    {
        $userAspect = $this->prophesize(UserAspect::class);
        $userAspect
            ->get('isLoggedIn')
            ->willReturn($userLoggedIn);
        GeneralUtility::makeInstance(Context::class)->setAspect('frontend.user', $userAspect->reveal());
    }

    /**
     * @param string $messageKey
     */
    protected function assertMessageKey(string $messageKey): void
    {
        $this->view
            ->assignMultiple(
                Argument::withEntry(
                    'messageKey', $messageKey
                )
            )
            ->shouldBeCalled();
    }

    protected function injectRequestForControllerAction(string $controllerActionName): ObjectProphecy
    {
        $webRequest = $this->prophesize(Request::class);
        $webRequest
            ->setDispatched(false)
            ->shouldBeCalled();
        $webRequest
            ->setControllerActionName($controllerActionName)
            ->shouldBeCalled();

        $this->inject($this->subject, 'request', $webRequest->reveal());

        return $webRequest;
    }
}
