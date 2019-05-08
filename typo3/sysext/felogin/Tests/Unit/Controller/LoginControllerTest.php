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
            ->assign('storagePid', '1,2,3')
            ->shouldBeCalled();

        $this->subject->loginAction();
    }

    /**
     * @test
     */
    public function loginActionShouldAssingLoginFailedToViewOnNotLoggedInUser(): void
    {
        $this->setLoginTypeLogin();
        $this->setUserLoggedIn(false);

        $this->view
            ->assign('loginFailed', true)
            ->shouldBeCalled();

        $this->view
            ->assign('storagePid', '')
            ->shouldBeCalled();

        $this->subject->loginAction();
    }

    /**
     * @test
     */
    public function loginActionShouldRedirectToOverviewOnSuccessfulLogin(): void
    {
        $this->setLoginTypeLogin();
        $this->setUserLoggedIn(true);

        $webRequest = $this->prophesize(Request::class);

        $webRequest
            ->setDispatched(false)
            ->shouldBeCalled();
        $webRequest
            ->setControllerActionName('overview')
            ->shouldBeCalled();
        $webRequest
            ->setArguments(['loginMessage' => true])
            ->shouldBeCalled();

        $this->inject($this->subject, 'request', $webRequest->reveal());

        $this->expectException(StopActionException::class);
        $this->expectExceptionMessage('forward');

        $this->subject->loginAction();
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

        $webRequest = $this->prophesize(Request::class);

        $webRequest
            ->setDispatched(false)
            ->shouldBeCalled();
        $webRequest
            ->setControllerActionName('login')
            ->shouldBeCalled();

        $this->inject($this->subject, 'request', $webRequest->reveal());

        $this->expectException(StopActionException::class);
        $this->expectExceptionMessage('forward');

        $this->subject->overviewAction();
    }

    private function setLoginTypeLogin(): void
    {
        $this->request
            ->getParsedBody()
            ->willReturn(
                [
                    'logintype' => LoginType::LOGIN
                ]
            );
    }

    private function setUserLoggedIn(bool $userLoggedIn): void
    {
        $userAspect = $this->prophesize(UserAspect::class);
        $userAspect
            ->get('isLoggedIn')
            ->willReturn($userLoggedIn);
        GeneralUtility::makeInstance(Context::class)->setAspect('frontend.user', $userAspect->reveal());
    }
}
