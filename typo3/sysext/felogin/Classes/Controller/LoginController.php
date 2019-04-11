<?php
declare(strict_types=1);

namespace TYPO3\CMS\Felogin\Controller;

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

use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Used for plugin login
 */
class LoginController extends ActionController
{
    /**
     * show login form
     *
     * @return void
     */
    public function loginAction(): void
    {
        $this->handleLogin($this->isUserLoggedIn(), (string)$this->getPropertyFromGetAndPost('logintype'));

        $this->view->assign('storagePid', $this->getStoragePid());
    }

    /**
     * user overview for logged in users
     *
     * @param bool $showLoginMessage
     */
    public function overviewAction(bool $showLoginMessage = false): void
    {
        if (!$this->isUserLoggedIn()) {
            $this->forward('login');
        }

        $this->view->assignMultiple(
            [
                'user'             => $GLOBALS['TSFE']->fe_user->user ?? [],
                'showLoginMessage' => $showLoginMessage
            ]
        );
    }

    protected function getStoragePid(): string
    {
        return (string)($this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
            )['persistence']['storagePid'] ?? '');
    }

    /**
     * returns a property that exists in post or get context
     *
     * @param string $propertyName
     * @return mixed|null
     */
    protected function getPropertyFromGetAndPost(string $propertyName)
    {
        // todo: refactor when extbase handles PSR-15 requests
        $request = $GLOBALS['TYPO3_REQUEST'];

        return $request->getParsedBody()[$propertyName] ?? $request->getQueryParams()[$propertyName] ?? null;
    }

    /**
     * redirect on current login state
     *
     * @param bool $userLoggedIn
     * @param string $loginType
     */
    protected function handleLogin(bool $userLoggedIn, string $loginType): void
    {
        $loginInProgress = $loginType === LoginType::LOGIN;
        if ($userLoggedIn) {
            $this->forward('overview', null, null, ['showLoginMessage' => $loginInProgress]);
        }

        if ($loginInProgress) {
            $this->view->assign('loginFailed', true);
        }
    }

    /**
     * check if the user is logged in
     *
     * @return bool
     */
    protected function isUserLoggedIn(): bool
    {
        return (bool)GeneralUtility::makeInstance(Context::class)
            ->getPropertyFromAspect('frontend.user', 'isLoggedIn');
    }
}
