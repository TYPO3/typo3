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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserGroupRepository;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\FrontendLogin\Service\UserService;
use TYPO3\CMS\FrontendLogin\Validation\RedirectUrlValidator;

/**
 * Do felogin related redirects
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class RedirectModeHandler
{
    public function __construct(
        protected UriBuilder $uriBuilder,
        protected RedirectUrlValidator $redirectUrlValidator,
        private UserService $userService,
        private FrontendUserRepository $frontendUserRepository,
        private FrontendUserGroupRepository $frontendUserGroupRepository
    ) {}

    /**
     * Handle redirect mode groupLogin
     */
    public function redirectModeGroupLogin(RequestInterface $request): string
    {
        $groups = $this->userService->getFeUserGroupData();

        if (empty($groups)) {
            return '';
        }
        $groupUids = array_keys($groups);

        // take the first group with a redirect page
        foreach ($groupUids as $groupUid) {
            $redirectPageId = (int)$this->frontendUserGroupRepository
                ->findRedirectPageIdByGroupId($groupUid);
            if ($redirectPageId > 0) {
                return $this->buildUriForPageUid($request, $redirectPageId);
            }
        }
        return '';
    }

    /**
     * Handle redirect mode userLogin
     */
    public function redirectModeUserLogin(RequestInterface $request): string
    {
        $redirectPageId = $this->frontendUserRepository->findRedirectIdPageByUserId(
            $this->userService->getFeUserData()['uid']
        );

        if ($redirectPageId === null) {
            return '';
        }

        return $this->buildUriForPageUid($request, $redirectPageId);
    }

    /**
     * Handle redirect mode login
     */
    public function redirectModeLogin(RequestInterface $request, int $redirectPageLogin): string
    {
        $redirectUrl = '';
        if ($redirectPageLogin !== 0) {
            $redirectUrl = $this->buildUriForPageUid($request, $redirectPageLogin);
        }

        return $redirectUrl;
    }

    /**
     * Handle redirect mode referrer
     */
    public function redirectModeReferrer(RequestInterface $request, string $redirectReferrer): string
    {
        $redirectUrl = '';
        if ($redirectReferrer !== 'off') {
            // Avoid forced logout, when trying to login immediately after a logout
            $redirectUrl = preg_replace('/[&?]logintype=[a-z]+/', '', $this->getReferrer($request));
        }

        return $redirectUrl ?? '';
    }

    /**
     * Handle redirect mode refererDomains
     */
    public function redirectModeReferrerDomains(RequestInterface $request, string $domains, string $redirectReferrer): string
    {
        $redirectUrl = '';
        if ($redirectReferrer !== '') {
            return '';
        }

        // Auto redirect.
        // Feature to redirect to the page where the user came from (HTTP_REFERER).
        // Allowed domains to redirect to, can be configured with plugin.tx_felogin_login.domains
        // also avoid redirect when logging in after changing password
        if ($domains) {
            $url = $this->getReferrer($request);
            // Is referring url allowed to redirect?
            $match = [];
            if (preg_match('#^https?://([[:alnum:].-]+)/#', $url, $match)) {
                $redirectDomain = $match[1];
                $found = false;
                foreach (GeneralUtility::trimExplode(',', $domains, true) as $domain) {
                    if (preg_match('/(?:^|\\.)' . preg_quote($domain, '/') . '$/', $redirectDomain)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $url = '';
                }
            }
            // Avoid forced logout, when trying to login immediately after a logout
            if ($url) {
                $redirectUrl = preg_replace('/[&?]logintype=[a-z]+/', '', $url);
            }
        }

        return $redirectUrl ?? '';
    }

    /**
     * Handle redirect mode loginError after login-error
     */
    public function redirectModeLoginError(RequestInterface $request, int $redirectPageLoginError = 0): string
    {
        $redirectUrl = '';
        if ($redirectPageLoginError > 0) {
            $redirectUrl = $this->buildUriForPageUid($request, $redirectPageLoginError);
        }

        return $redirectUrl;
    }

    /**
     * Handle redirect mode logout
     */
    public function redirectModeLogout(RequestInterface $request, int $redirectPageLogout): string
    {
        $redirectUrl = '';
        if ($redirectPageLogout > 0) {
            $redirectUrl = $this->buildUriForPageUid($request, $redirectPageLogout);
        }

        return $redirectUrl;
    }

    protected function buildUriForPageUid(RequestInterface $request, int $pageUid): string
    {
        $this->uriBuilder->reset();
        $this->uriBuilder->setRequest($request);
        $this->uriBuilder->setTargetPageUid($pageUid);

        return $this->uriBuilder->build();
    }

    protected function getReferrer(RequestInterface $request): string
    {
        $referrer = '';
        $requestReferrer = (string)($request->getParsedBody()['referer'] ?? $request->getQueryParams()['referer'] ?? '');

        if ($this->redirectUrlValidator->isValid($request, $requestReferrer)) {
            $referrer = $requestReferrer;
        }

        return $referrer;
    }
}
