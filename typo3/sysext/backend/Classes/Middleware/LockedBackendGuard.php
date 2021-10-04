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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Exception\BackendAccessDeniedException;
use TYPO3\CMS\Backend\Exception\BackendLockedException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Checks various security options for accessing the TYPO3 backend before proceeding
 *
 * Depends on the NormalizedParams middleware to identify the
 * Site URL and if the page is not running via HTTPS yet.
 *
 * @internal
 */
class LockedBackendGuard implements MiddlewareInterface
{
    /**
     * Checks the client's IP address and if typo3conf/LOCK_BACKEND is available
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $redirectToUri = $this->checkLockedBackend();
            if (!empty($redirectToUri)) {
                return new RedirectResponse($redirectToUri, 302);
            }
        } catch (BackendLockedException $e) {
            // Looks like an AJAX request that can handle JSON, (usually from the timeout functionality)
            // So, let's form a request that fits
            if (str_contains($request->getHeaderLine('Accept'), 'application/json')) {
                $session = [
                    'timed_out' => false,
                    'will_time_out' => false,
                    'locked' => true,
                    'message' => $e->getMessage(),
                ];
                return new JsonResponse(['login' => $session]);
            }
            throw $e;
        }
        $this->validateVisitorsIpAgainstIpMaskList(
            $request->getAttribute('normalizedParams')->getRemoteAddress(),
            trim((string)$GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])
        );

        return $handler->handle($request);
    }

    /**
     * Check adminOnly configuration variable and redirects to an URL in file typo3conf/LOCK_BACKEND
     *
     * @return string|null
     * @throws BackendLockedException
     */
    protected function checkLockedBackend()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] < 0) {
            throw new BackendLockedException(
                HttpUtility::HTTP_STATUS_403,
                'Backend and Install Tool are locked for maintenance. [BE][adminOnly] is set to "' . (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] . '".',
                'TYPO3 Backend locked',
                1517949794
            );
        }
        if (@is_file(Environment::getLegacyConfigPath() . '/LOCK_BACKEND')) {
            $fileContent = file_get_contents(Environment::getLegacyConfigPath() . '/LOCK_BACKEND');
            if ($fileContent) {
                return $fileContent;
            }
            throw new BackendLockedException(
                HttpUtility::HTTP_STATUS_403,
                'Backend access by browser is locked for maintenance. Remove lock by removing the file "typo3conf/LOCK_BACKEND" or use CLI-scripts.',
                'TYPO3 Backend locked',
                1517949793
            );
        }

        return null;
    }

    /**
     * Compare client IP with IPmaskList and throw an exception
     *
     * @param string $ipAddress
     * @param string $ipMaskList
     * @throws \RuntimeException
     */
    protected function validateVisitorsIpAgainstIpMaskList(string $ipAddress, string $ipMaskList = '')
    {
        if ($ipMaskList !== '' && !GeneralUtility::cmpIP($ipAddress, $ipMaskList)) {
            throw new BackendAccessDeniedException(
                HttpUtility::HTTP_STATUS_403,
                'The IP address of your client does not match the list of allowed IP addresses.',
                'TYPO3 Backend access denied',
                1517949792
            );
        }
    }
}
