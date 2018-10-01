<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for logging a user out.
 * Does not display any content, just calls the logout-function for the current user and then makes a redirect.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class LogoutController
{
    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     * This will be split up in an abstract controller once proper routing/dispatcher is in place.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function logoutAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->processLogout();

        $redirectUrl = $request->getParsedBody()['redirect'] ?? $request->getQueryParams()['redirect'];
        $redirectUrl = GeneralUtility::sanitizeLocalUrl($redirectUrl);
        if (empty($redirectUrl)) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $redirectUrl = (string)$uriBuilder->buildUriFromRoute('login', [], $uriBuilder::ABSOLUTE_URL);
        }
        return new RedirectResponse(GeneralUtility::locationHeaderUrl($redirectUrl), 303);
    }

    /**
     * Performs the logout processing
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function logout()
    {
        trigger_error('LogoutController->logout() will be replaced by protected method processLogout() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->processLogout();
    }

    /**
     * Performs the logout processing
     */
    protected function processLogout(): void
    {
        if (empty($this->getBackendUser()->user['username'])) {
            return;
        }
        // Logout written to log
        $this->getBackendUser()->writelog(255, 2, 0, 1, 'User %s logged out from TYPO3 Backend', [$this->getBackendUser()->user['username']]);
        /** @var \TYPO3\CMS\Core\FormProtection\BackendFormProtection $backendFormProtection */
        $backendFormProtection = FormProtectionFactory::get();
        $backendFormProtection->removeSessionTokenFromRegistry();
        $this->getBackendUser()->logoff();
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
