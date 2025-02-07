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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for logging a user out.
 * Does not display any content, just calls the logout-function for the current user and then makes a redirect.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
readonly class LogoutController
{
    public function __construct(
        protected UriBuilder $uriBuilder,
        protected FormProtectionFactory $formProtectionFactory
    ) {}

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
        $this->processLogout($request);

        $redirectUrl = $request->getParsedBody()['redirect'] ?? $request->getQueryParams()['redirect'] ?? '';
        $redirectUrl = GeneralUtility::sanitizeLocalUrl($redirectUrl);
        if (empty($redirectUrl)) {
            $redirectUrl = (string)$this->uriBuilder->buildUriFromRoute('login', [], UriBuilder::ABSOLUTE_URL);
        }
        return new RedirectResponse(GeneralUtility::locationHeaderUrl($redirectUrl), 303);
    }

    /**
     * Performs the logout processing
     */
    protected function processLogout(ServerRequestInterface $request): void
    {
        if (empty($this->getBackendUser()->user['username'])) {
            return;
        }
        // Logout written to log
        $this->getBackendUser()->writelog(SystemLogType::LOGIN, SystemLogLoginAction::LOGOUT, SystemLogErrorClassification::MESSAGE, null, 'User %s logged out from TYPO3 Backend', [$this->getBackendUser()->user['username']]);
        /** @var BackendFormProtection $backendFormProtection */
        $backendFormProtection = $this->formProtectionFactory->createFromRequest($request);
        $backendFormProtection->removeSessionTokenFromRegistry();
        $this->getBackendUser()->logoff();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
