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

namespace TYPO3\CMS\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Checks mount points, shortcuts and redirects to the target.
 * Alternatively, checks if the current page is a redirect to an external page
 *
 * @internal this middleware might get removed in TYPO3 v10.x.
 */
class ShortcutAndMountPointRedirect implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $exposeInformation = $GLOBALS['TYPO3_CONF_VARS']['FE']['exposeRedirectInformation'] ?? false;

        // Check for shortcut page and mount point redirect
        try {
            $redirectToUri = $this->getRedirectUri($request);
        } catch (ImmediateResponseException $e) {
            return $e->getResponse();
        }
        if ($redirectToUri !== null && $redirectToUri !== (string)$request->getUri()) {
            /** @var PageArguments $pageArguments */
            $pageArguments = $request->getAttribute('routing', null);
            $message = 'TYPO3 Shortcut/Mountpoint' . ($exposeInformation ? ' at page with ID ' . $pageArguments->getPageId() : '');
            return new RedirectResponse(
                $redirectToUri,
                307,
                ['X-Redirect-By' => $message]
            );
        }

        // See if the current page is of doktype "External URL", if so, do a redirect as well.
        /** @var TypoScriptFrontendController */
        $controller = $request->getAttribute('frontend.controller');
        if (empty($controller->config['config']['disablePageExternalUrl'] ?? null)
            && (int)$controller->page['doktype'] === PageRepository::DOKTYPE_LINK) {
            $externalUrl = $this->prefixExternalPageUrl(
                $controller->page['url'],
                $request->getAttribute('normalizedParams')->getSiteUrl()
            );
            if (!empty($externalUrl)) {
                $message = 'TYPO3 External URL' . ($exposeInformation ? ' at page with ID ' . $controller->page['uid'] : '');
                return new RedirectResponse(
                    $externalUrl,
                    303,
                    ['X-Redirect-By' => $message]
                );
            }
        }

        return $handler->handle($request);
    }

    protected function getRedirectUri(ServerRequestInterface $request): ?string
    {
        /** @var TypoScriptFrontendController */
        $controller = $request->getAttribute('frontend.controller');
        $redirectToUri = $controller->getRedirectUriForShortcut($request);
        if ($redirectToUri !== null) {
            return $redirectToUri;
        }
        return $controller->getRedirectUriForMountPoint($request);
    }

    /**
     * Returns the redirect URL for the input page row IF the doktype is set to 3.
     *
     * @param string $redirectTo The page row to return URL type for
     * @param string $sitePrefix if no protocol or relative path given, the site prefix is added
     * @return string The URL from based on the external page URL given with a prefix.
     */
    protected function prefixExternalPageUrl(string $redirectTo, string $sitePrefix): string
    {
        $uI = parse_url($redirectTo);
        // If relative path, prefix Site URL
        // If it's a valid email without protocol, add "mailto:"
        if (!($uI['scheme'] ?? false)) {
            if (GeneralUtility::validEmail($redirectTo)) {
                $redirectTo = 'mailto:' . $redirectTo;
            } elseif ($redirectTo[0] !== '/') {
                $redirectTo = $sitePrefix . $redirectTo;
            }
        }
        return $redirectTo;
    }
}
