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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\FrontendLogin\Configuration\RedirectConfiguration;
use TYPO3\CMS\FrontendLogin\Validation\RedirectUrlValidator;

/**
 * Resolve felogin related redirects based on the current login type and the selected configuration (redirect mode)
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class RedirectHandler
{
    protected bool $userIsLoggedIn = false;

    public function __construct(
        protected RedirectModeHandler $redirectModeHandler,
        protected RedirectUrlValidator $redirectUrlValidator,
        Context $context
    ) {
        $this->userIsLoggedIn = (bool)$context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
    }

    /**
     * Process redirect modes. This method searches for a redirect url using all configured modes and returns it.
     */
    public function processRedirect(RequestInterface $request, string $loginType, RedirectConfiguration $configuration, string $redirectModeReferrer): string
    {
        if ($this->isUserLoginFailedAndLoginErrorActive($configuration->getModes(), $loginType)) {
            return $this->redirectModeHandler->redirectModeLoginError($request, $configuration->getPageOnLoginError());
        }

        $redirectUrlList = [];
        foreach ($configuration->getModes() as $redirectMode) {
            $redirectUrl = '';

            if ($loginType === LoginType::LOGIN) {
                $redirectUrl = $this->handleSuccessfulLogin($request, $redirectMode, $configuration->getPageOnLogin(), $configuration->getDomains(), $redirectModeReferrer);
            } elseif ($loginType === LoginType::LOGOUT) {
                $redirectUrl = $this->handleSuccessfulLogout($request, $redirectMode, $configuration->getPageOnLogout());
            }

            if ($redirectUrl !== '') {
                $redirectUrlList[] = $redirectUrl;
            }
        }

        return $this->fetchReturnUrlFromList($redirectUrlList, $configuration->getFirstMode());
    }

    /**
     * Get alternative logout form redirect url if logout and page not accessible
     */
    protected function getLogoutRedirectUrl(RequestInterface $request, array $redirectModes, int $redirectPageLogout = 0): string
    {
        if ($this->userIsLoggedIn && $this->isRedirectModeActive($redirectModes, RedirectMode::LOGOUT)) {
            return $this->redirectModeHandler->redirectModeLogout($request, $redirectPageLogout);
        }
        return $this->getGetpostRedirectUrl($request, $redirectModes);
    }

    /**
     * Is used for alternative redirect urls on redirect mode "getpost"
     */
    protected function getGetpostRedirectUrl(RequestInterface $request, array $redirectModes): string
    {
        return $this->isRedirectModeActive($redirectModes, RedirectMode::GETPOST)
            ? $this->getRedirectUrlRequestParam($request)
            : '';
    }

    /**
     * Handle redirect mode logout
     */
    protected function handleSuccessfulLogout(RequestInterface $request, string $redirectMode, int $redirectPageLogout): string
    {
        if ($redirectMode === RedirectMode::LOGOUT) {
            return $this->redirectModeHandler->redirectModeLogout($request, $redirectPageLogout);
        }
        return '';
    }

    /**
     * Base on setting redirectFirstMethod get first or last entry from redirect url list.
     */
    protected function fetchReturnUrlFromList(array $redirectUrlList, string $redirectFirstMethod): string
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
     */
    protected function handleSuccessfulLogin(RequestInterface $request, string $redirectMode, int $redirectPageLogin = 0, string $domains = '', string $redirectModeReferrer = ''): string
    {
        if (!$this->userIsLoggedIn) {
            return '';
        }

        // Logintype is needed because the login-page wouldn't be accessible anymore after a login (would always redirect)
        switch ($redirectMode) {
            case RedirectMode::GROUP_LOGIN:
                $redirectUrl = $this->redirectModeHandler->redirectModeGroupLogin($request);
                break;
            case RedirectMode::USER_LOGIN:
                $redirectUrl = $this->redirectModeHandler->redirectModeUserLogin($request);
                break;
            case RedirectMode::LOGIN:
                $redirectUrl = $this->redirectModeHandler->redirectModeLogin($request, $redirectPageLogin);
                break;
            case RedirectMode::GETPOST:
                $redirectUrl = $this->getRedirectUrlRequestParam($request);
                break;
            case RedirectMode::REFERRER:
                $redirectUrl = $this->redirectModeHandler->redirectModeReferrer($request, $redirectModeReferrer);
                break;
            case RedirectMode::REFERRER_DOMAINS:
                $redirectUrl = $this->redirectModeHandler->redirectModeReferrerDomains($request, $domains, $redirectModeReferrer);
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

    protected function isRedirectModeActive(array $redirectModes, string $mode): bool
    {
        return in_array($mode, $redirectModes, true);
    }

    /**
     * Returns the redirect Url that should be used in login form template for GET/POST redirect mode
     */
    public function getLoginFormRedirectUrl(
        RequestInterface $request,
        RedirectConfiguration $configuration,
        bool $redirectDisabled
    ): string {
        if (!$redirectDisabled) {
            return $this->getGetpostRedirectUrl($request, $configuration->getModes());
        }
        return '';
    }

    /**
     * Determines the `referer` variable used in the login form for loginMode=referer depending on the
     * following evaluation order:
     *
     * - HTTP POST parameter `referer`
     * - HTTP GET parameter `referer`
     * - HTTP_REFERER
     * - URL of initiating request in case plugin has been called via sub-request
     *
     * The evaluated `referer` is only returned, if it is considered valid.
     */
    public function getReferrerForLoginForm(RequestInterface $request, array $settings): string
    {
        // Early return, if redirectMode is not configured to respect the referrer
        if (!$this->isReferrerRedirectEnabled($settings)) {
            return '';
        }

        $referrer = (string)(
            $request->getParsedBody()['referer'] ??
            $request->getQueryParams()['referer'] ??
            $request->getServerParams()['HTTP_REFERER'] ??
            ''
        );

        // If the current request was initiated via sub-request, we use the URI of the original request as referrer
        if ($originalRequest = $request->getAttribute('originalRequest', false)) {
            $referrer = (string)$originalRequest->getUri();
        }

        if ($this->redirectUrlValidator->isValid($request, $referrer)) {
            return $referrer;
        }

        return '';
    }

    /**
     * Returns whether redirect based on the referrer is enabled
     */
    protected function isReferrerRedirectEnabled(array $settings): bool
    {
        $referrerRedirectModes = [RedirectMode::REFERRER, RedirectMode::REFERRER_DOMAINS];
        $configuredRedirectModes = GeneralUtility::trimExplode(',', $settings['redirectMode'] ?? '');
        return count(array_intersect($configuredRedirectModes, $referrerRedirectModes)) > 0;
    }

    /**
     * Returns the redirect Url that should be used in logout form
     */
    public function getLogoutFormRedirectUrl(
        RequestInterface $request,
        RedirectConfiguration $configuration,
        int $redirectPageLogout,
        bool $redirectDisabled
    ): string {
        if (!$redirectDisabled) {
            return $this->getLogoutRedirectUrl($request, $configuration->getModes(), $redirectPageLogout);
        }
        return $this->getRedirectUrlRequestParam($request);
    }

    /**
     * Returns validated redirect url contained in request param return_url or redirect_url
     */
    private function getRedirectUrlRequestParam(RequestInterface $request): string
    {
        // If config.typolinkLinkAccessRestrictedPages is set, the var is return_url
        $returnUrlFromRequest = (string)($request->getParsedBody()['return_url'] ?? $request->getQueryParams()['return_url'] ?? null);
        $redirectUrlFromRequest = (string)($request->getParsedBody()['redirect_url'] ?? $request->getQueryParams()['redirect_url'] ?? null);
        $redirectUrl = $returnUrlFromRequest ?: $redirectUrlFromRequest;

        return $this->redirectUrlValidator->isValid($request, $redirectUrl) ? $redirectUrl : '';
    }
}
