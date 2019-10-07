<?php
declare(strict_types = 1);

namespace TYPO3\CMS\FrontendLogin\Controller;

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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\FrontendLogin\Configuration\RedirectConfiguration;
use TYPO3\CMS\FrontendLogin\Event\LoginConfirmedEvent;
use TYPO3\CMS\FrontendLogin\Event\ModifyLoginFormViewEvent;
use TYPO3\CMS\FrontendLogin\Helper\TreeUidListProvider;
use TYPO3\CMS\FrontendLogin\Redirect\RedirectHandler;
use TYPO3\CMS\FrontendLogin\Redirect\ServerRequestHandler;
use TYPO3\CMS\FrontendLogin\Service\UserService;

/**
 * Used for plugin login
 */
class LoginController extends ActionController
{
    /**
     * @var string
     */
    public const MESSAGEKEY_DEFAULT = 'welcome';

    /**
     * @var string
     */
    public const MESSAGEKEY_ERROR = 'error';

    /**
     * @var string
     */
    public const MESSAGEKEY_LOGOUT = 'logout';

    /**
     * @var RedirectHandler
     */
    protected $redirectHandler;

    /**
     * @var string
     */
    protected $loginType = '';

    /**
     * @var TreeUidListProvider
     */
    protected $treeUidListProvider;

    /**
     * @var ServerRequestHandler
     */
    protected $requestHandler;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var RedirectConfiguration
     */
    protected $configuration;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        RedirectHandler $redirectHandler,
        TreeUidListProvider $treeUidListProvider,
        ServerRequestHandler $requestHandler,
        UserService $userService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->redirectHandler = $redirectHandler;
        $this->treeUidListProvider = $treeUidListProvider;
        $this->requestHandler = $requestHandler;
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Initialize redirects
     */
    public function initializeAction(): void
    {
        $this->loginType = (string)$this->requestHandler->getPropertyFromGetAndPost('logintype');

        $this->configuration = new RedirectConfiguration(
            (string)($this->settings['redirectMode'] ?? ''),
            (string)($this->settings['redirectFirstMethod'] ?? ''),
            (int)($this->settings['redirectPageLogin'] ?? 0),
            (string)($this->settings['domains'] ?? ''),
            (int)($this->settings['redirectPageLoginError'] ?? 0),
            (int)($this->settings['redirectPageLogout'] ?? 0)
        );

        if ($this->isLoginOrLogoutInProgress() && !$this->isRedirectDisabled()) {
            if ($this->userService->cookieWarningRequired()) {
                $this->view->assign('cookieWarning', true);
                return;
            }

            $redirectUrl = $this->redirectHandler->processRedirect(
                $this->loginType,
                $this->configuration,
                $this->request->hasArgument('redirectReferrer') ? $this->request->getArgument('redirectReferrer') : ''
            );
            if ($redirectUrl !== '') {
                $this->redirectToUri($redirectUrl);
            }
        }
    }

    /**
     * Show login form
     */
    public function loginAction(): void
    {
        $this->handleLoginForwards();

        $this->eventDispatcher->dispatch(new ModifyLoginFormViewEvent($this->view));

        $this->view->assignMultiple(
            [
                'messageKey' => $this->getStatusMessageKey(),
                'storagePid' => $this->getStoragePid(),
                'permaloginStatus' => $this->getPermaloginStatus(),
                'redirectURL' => $this->redirectHandler->getLoginFormRedirectUrl($this->configuration->getModes(), $this->configuration->getPageOnLogin(), $this->isRedirectDisabled()),
                'redirectReferrer' => $this->request->hasArgument('redirectReferrer') ? (string)$this->request->getArgument('redirectReferrer'): '',
                'referer' => $this->requestHandler->getPropertyFromGetAndPost('referer'),
                'noRedirect' => $this->isRedirectDisabled(),
            ]
        );
    }

    /**
     * User overview for logged in users
     *
     * @param bool $showLoginMessage
     * @throws StopActionException
     */
    public function overviewAction(bool $showLoginMessage = false): void
    {
        if (!$this->userService->isUserLoggedIn()) {
            $this->forward('login');
        }

        $this->eventDispatcher->dispatch(new LoginConfirmedEvent($this, $this->view));

        $this->view->assignMultiple(
            [
                'user' => $this->userService->getFeUserData(),
                'showLoginMessage' => $showLoginMessage,
            ]
        );
    }

    /**
     * Show logout form
     */
    public function logoutAction(int $redirectPageLogout = 0): void
    {
        $this->view->assignMultiple(
            [
                'user' => $this->userService->getFeUserData(),
                'storagePid' => $this->getStoragePid(),
                'noRedirect' => $this->isRedirectDisabled(),
                'actionUri' => $this->redirectHandler->getLogoutFormRedirectUrl($this->configuration->getModes(), $redirectPageLogout, $this->isRedirectDisabled()),
            ]
        );
    }

    /**
     * Returns the parsed storagePid list including recursions
     *
     * @return string
     */
    protected function getStoragePid(): string
    {
        return $this->treeUidListProvider->getListForIdList(
            (string)$this->settings['pages'],
            (int)$this->settings['recursive']
        );
    }

    /**
     * Handle forwards to overview and logout actions from login action
     */
    protected function handleLoginForwards(): void
    {
        if ($this->shouldRedirectToOverview()) {
            $this->forward('overview', null, null, ['showLoginMessage' => true]);
        }

        if ($this->userService->isUserLoggedIn()) {
            $this->forward('logout');
        }
    }

    /**
     * The permanent login checkbox should only be shown if permalogin is not deactivated (-1),
     * not forced to be always active (2) and lifetime is greater than 0
     *
     * @return int
     */
    protected function getPermaloginStatus(): int
    {
        $permaLogin = (int)$GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'];

        return $this->isPermaloginDisabled($permaLogin) ? -1 : $permaLogin;
    }

    protected function isPermaloginDisabled(int $permaLogin): bool
    {
        return $permaLogin > 1
               || (int)($this->settings['showPermaLogin'] ?? 0) === 0
               || $GLOBALS['TYPO3_CONF_VARS']['FE']['lifetime'] === 0;
    }

    /**
     * Redirect to overview on login successful and setting showLogoutFormAfterLogin disabled
     *
     * @return bool
     */
    protected function shouldRedirectToOverview(): bool
    {
        return $this->userService->isUserLoggedIn()
               && ($this->loginType === LoginType::LOGIN)
               && !($this->settings['showLogoutFormAfterLogin'] ?? 0);
    }

    /**
     * Return message key based on user login status
     *
     * @return string
     */
    protected function getStatusMessageKey(): string
    {
        $messageKey = self::MESSAGEKEY_DEFAULT;
        if ($this->loginType === LoginType::LOGIN && !$this->userService->isUserLoggedIn()) {
            $messageKey = self::MESSAGEKEY_ERROR;
        } elseif ($this->loginType === LoginType::LOGOUT) {
            $messageKey = self::MESSAGEKEY_LOGOUT;
        }

        return $messageKey;
    }

    protected function isLoginOrLogoutInProgress(): bool
    {
        return $this->loginType === LoginType::LOGIN || $this->loginType === LoginType::LOGOUT;
    }

    /**
     * Is redirect disabled by setting or noredirect parameter
     *
     * @return bool
     */
    public function isRedirectDisabled(): bool
    {
        return
            $this->request->hasArgument('noredirect')
            || ($this->settings['noredirect'] ?? false)
            || ($this->settings['redirectDisable'] ?? false);
    }
}
