<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Middleware;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calls a hook before processing a request for the TYPO3 Frontend.
 *
 * @internal
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
 */
class PreprocessRequestHook implements MiddlewareInterface
{

    /**
     * Hook to preprocess the current request
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Legacy functionality to check if any hook modified global GET/POST
        // This is a safety net, see RequestHandler for how this is validated.
        // This information is just a compat layer which will be removed in TYPO3 v10.0.
        $request = $request->withAttribute('_originalGetParameters', $_GET);
        if ($request->getMethod() === 'POST') {
            $request = $request->withAttribute('_originalPostParameters', $_POST);
        }
        // Set original parameters
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'])) {
            trigger_error('The "preprocessRequest" hook will be removed in TYPO3 v10.0 in favor of PSR-15. Use a middleware instead.', E_USER_DEPRECATED);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'] as $hookFunction) {
                $hookParameters = [];
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $hookParameters);
            }
        }
        return $handler->handle($request);
    }
}
