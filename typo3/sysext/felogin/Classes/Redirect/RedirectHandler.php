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

namespace TYPO3\CMS\FrontendLogin\Redirect;

use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\FrontendLogin\Configuration\RedirectConfiguration;

/**
 * Resolve felogin related redirects based on the current login type and the selected configuration (redirect mode)
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class RedirectHandler
{
    /**
     * @var bool
     */
    protected $userIsLoggedIn = false;

    /**
     * @var ServerRequestHandler
     */
    protected $requestHandler;

    /**
     * @var RedirectModeHandler
     */
    protected $redirectModeHandler;

    public function __construct(
        ServerRequestHandler $requestHandler,
        RedirectModeHandler $redirectModeHandler,
        Context $context
    ) {
        $this->requestHandler = $requestHandler;
        $this->redirectModeHandler = $redirectModeHandler;
        $this->userIsLoggedIn = (bool)$context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
    }

    /**
     * Process redirect modes. The function searches for a redirect url using all configured modes.
     *
     * @param string $loginType
     * @param RedirectConfiguration $configuration
     * @param string $redirectModeReferrer
     * @return string Redirect URL
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function processRedirect(string $loginType, RedirectConfiguration $configuration, string $redirectModeReferrer): string
    {
        if ($this->isUserLoginFailedAndLoginErrorActive($configuration->getModes(), $loginType)) {
            return $this->redirectModeHandler->redirectModeLoginError($configuration->getPageOnLoginError());
        }

        $redirectUrlList = [];
        foreach ($configuration->getModes() as $redirectMode) {
            $redirectUrl = '';

            if ($loginType === LoginType::LOGIN) {
                $redirectUrl = $this->handleSuccessfulLogin($redirectMode, $configuration->getPageOnLogin(), $configuration->getDomains(), $redirectModeReferrer);
            } elseif ($loginType === LoginType::LOGOUT) {
                $redirectUrl = $this->handleSuccessfulLogout($redirectMode, $configuration->getPageOnLogout());
            }

            if ($redirectUrl !== '') {
                $redirectUrlList[] = $redirectUrl;
            }
        }

        return $this->fetchReturnUrlFromList($redirectUrlList, $configuration->getFirstMode());
    }

    /**
     * Get alternative logout form redirect url if logout and page not accessible
     *
     * @param array $redirectModes
     * @param int $redirectPageLogout
     * @return string
     */
    protected function getLogoutRedirectUrl(array $redirectModes, int $redirectPageLogout = 0): string
    {
        if ($this->userIsLoggedIn && $this->isRedirectModeActive($redirectModes, RedirectMode::LOGOUT)) {
            return $this->redirectModeHandler->redirectModeLogout($redirectPageLogout);
        }
        return $this->getGetpostRedirectUrl($redirectModes);
    }

    /**
     * Is used for alternative redirect urls on redirect mode "getpost"
     *
     * @param array $redirectModes
     * @return string
     */
    protected function getGetpostRedirectUrl(array $redirectModes): string
    {
        return $this->isRedirectModeActive($redirectModes, RedirectMode::GETPOST)
            ? $this->requestHandler->getRedirectUrlRequestParam()
            : '';
    }

    /**
     * Handle redirect mode logout
     *
     * @param string $redirectMode
     * @param int $redirectPageLogout
     * @return string
     */
    protected function handleSuccessfulLogout(string $redirectMode, int $redirectPageLogout): string
    {
        if ($redirectMode === RedirectMode::LOGOUT) {
            return $this->redirectModeHandler->redirectModeLogout($redirectPageLogout);
        }
        return '';
    }

    /**
     * Base on setting redirectFirstMethod get first or last entry from redirect url list.
     *
     * @param array $redirectUrlList
     * @param string $redirectFirstMethod
     * @return string
     */
    protected function fetchReturnUrlFromList(array $redirectUrlList, $redirectFirstMethod): string
    {
        if (count($redirectUrlList) === 0) {
            return '';
        }

        // Remove empty values, but keep "0" as value (that's why "strlen" is used as second parameter)
        $redirectUrlList = array_filter($redirectUrlList, static function (string $value): bool {
            return strlen($value) > 0;
        });

        return $redirectFirstMethod
            ? array_shift($redirectUrlList)
            : array_pop($redirectUrlList);
    }

    /**
     * Generate redirect_url for case that the user was successfully logged in
     *
     * @param string $redirectMode
     * @param int $redirectPageLogin
     * @param string $domains
     * @param string $redirectModeReferrer
     * @return string
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function handleSuccessfulLogin(string $redirectMode, int $redirectPageLogin = 0, string $domains = '', string $redirectModeReferrer = ''): string
    {
        if (!$this->userIsLoggedIn) {
            return '';
        }

        // Logintype is needed because the login-page wouldn't be accessible anymore after a login (would always redirect)
        switch ($redirectMode) {
            case RedirectMode::GROUP_LOGIN:
                $redirectUrl = $this->redirectModeHandler->redirectModeGroupLogin();
                break;
            case RedirectMode::USER_LOGIN:
                $redirectUrl = $this->redirectModeHandler->redirectModeUserLogin();
                break;
            case RedirectMode::LOGIN:
                $redirectUrl = $this->redirectModeHandler->redirectModeLogin($redirectPageLogin);
                break;
            case RedirectMode::GETPOST:
                $redirectUrl = $this->requestHandler->getRedirectUrlRequestParam();
                break;
            case RedirectMode::REFERER:
                $redirectUrl = $this->redirectModeHandler->redirectModeReferrer($redirectModeReferrer);
                break;
            case RedirectMode::REFERER_DOMAINS:
                $redirectUrl = $this->redirectModeHandler->redirectModeRefererDomains($domains, $redirectModeReferrer);
                break;
            default:
                $redirectUrl = '';
        }

        return $redirectUrl;
    }

    protected function isUserLoginFailedAndLoginErrorActive(array $redirectModes, string $loginType): bool
    {
        return $loginType === LoginType::LOGIN
            && !$this->userIsLoggedIn
            && $this->isRedirectModeActive($redirectModes, RedirectMode::LOGIN_ERROR);
    }

    /**
     * Checks if the give mode is active or not
     *
     * @param string $mode
     * @param array $redirectModes
     * @return bool
     */
    protected function isRedirectModeActive(array $redirectModes, string $mode): bool
    {
        return in_array($mode, $redirectModes, true);
    }

    /**
     * Returns the redirect Url that should be used in login form template for GET/POST redirect mode
     *
     * @param RedirectConfiguration $configuration
     * @param bool $redirectDisabled
     * @return string
     */
    public function getLoginFormRedirectUrl(RedirectConfiguration $configuration, bool $redirectDisabled): string
    {
        if (!$redirectDisabled) {
            return $this->getGetpostRedirectUrl($configuration->getModes());
        }
        return '';
    }

    /**
     * Returns the redirect Url that should be used in logout form
     *
     * @param RedirectConfiguration $configuration
     * @param int $redirectPageLogout
     * @param bool $redirectDisabled
     * @return string
     */
    public function getLogoutFormRedirectUrl(RedirectConfiguration $configuration, int $redirectPageLogout, bool $redirectDisabled): string
    {
        if (!$redirectDisabled) {
            return $this->getLogoutRedirectUrl($configuration->getModes(), $redirectPageLogout);
        }
        return $this->requestHandler->getRedirectUrlRequestParam();
    }
}
