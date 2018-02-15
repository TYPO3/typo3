<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Middleware;

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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $redirectToUri = $this->checkLockedBackend();
        if (!empty($redirectToUri)) {
            return new RedirectResponse($redirectToUri, 302);
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
     * @throws \RuntimeException
     * @return string|null
     */
    protected function checkLockedBackend()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] < 0) {
            throw new \RuntimeException('TYPO3 Backend locked: Backend and Install Tool are locked for maintenance. [BE][adminOnly] is set to "' . (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] . '".', 1517949794);
        }
        if (@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $fileContent = file_get_contents(PATH_typo3conf . 'LOCK_BACKEND');
            if ($fileContent) {
                return $fileContent;
            }
            throw new \RuntimeException('TYPO3 Backend locked: Browser backend is locked for maintenance. Remove lock by removing the file "typo3conf/LOCK_BACKEND" or use CLI-scripts.', 1517949793);
        }
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
            throw new \RuntimeException('TYPO3 Backend access denied: The IP address of your client does not match the list of allowed IP addresses.', 1517949792);
        }
    }
}
