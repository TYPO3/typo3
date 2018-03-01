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
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Process the ID, type and other parameters.
 * After this point we have an array, TSFE->page, which is the page-record of the current page, $TSFE->id.
 *
 * Now, if there is a backend user logged in and he has NO access to this page,
 * then re-evaluate the id shown!
 */
class PageResolver implements MiddlewareInterface
{
    /**
     * Resolve the page ID
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $GLOBALS['TSFE']->siteScript = $request->getAttribute('normalizedParams')->getSiteScript();
        $this->checkAlternativeIdMethods($GLOBALS['TSFE']);
        $GLOBALS['TSFE']->clear_preview();
        $GLOBALS['TSFE']->determineId();

        // No access? Then remove user & Re-evaluate the page-id
        if ($GLOBALS['TSFE']->isBackendUserLoggedIn() && !$GLOBALS['BE_USER']->doesUserHaveAccess($GLOBALS['TSFE']->page, Permission::PAGE_SHOW)) {
            unset($GLOBALS['BE_USER']);
            $GLOBALS['TSFE']->beUserLogin = false;
            $this->checkAlternativeIdMethods($GLOBALS['TSFE']);
            $GLOBALS['TSFE']->clear_preview();
            $GLOBALS['TSFE']->determineId();
        }

        // Evaluate the cache hash parameter
        $GLOBALS['TSFE']->makeCacheHash();

        return $handler->handle($request);
    }

    /**
     * Provides ways to bypass the '?id=[xxx]&type=[xx]' format, using either PATH_INFO or Server Rewrites
     *
     * Two options:
     * 1) Use PATH_INFO (also Apache) to extract id and type from that var. Does not require any special modules compiled with apache. (less typical)
     * 2) Using hook which enables features like those provided from "realurl" extension (AKA "Speaking URLs")
     */
    protected function checkAlternativeIdMethods(TypoScriptFrontendController $tsfe)
    {
        // Call post processing function for custom URL methods.
        $_params = ['pObj' => &$tsfe];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $tsfe);
        }
    }
}
