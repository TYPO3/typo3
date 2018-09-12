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
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Checks mount points, shortcuts and redirects to the target.
 * Alternatively, checks if the current page is an redirect to an external page
 *
 * @internal this middleware might get removed in TYPO3 v10.0.
 */
class ShortcutAndMountPointRedirect implements MiddlewareInterface
{
    /**
     * @var TypoScriptFrontendController
     */
    private $controller;

    public function __construct(TypoScriptFrontendController $controller = null)
    {
        $this->controller = $controller ?: $GLOBALS['TSFE'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check for shortcut page and mount point redirect
        $redirectToUri = $this->getRedirectUri($request);
        if ($redirectToUri !== null && $redirectToUri !== (string)$request->getUri()) {
            return new RedirectResponse($redirectToUri, 307);
        }

        // See if the current page is of doktype "External URL", if so, do a redirect as well.
        if (empty($this->controller->config['config']['disablePageExternalUrl'] ?? null)
            && PageRepository::DOKTYPE_LINK === (int)$this->controller->page['doktype']) {
            $externalUrl = $this->prefixExternalPageUrl(
                $this->controller->page['url'],
                $request->getAttribute('normalizedParams')->getSiteUrl()
            );
            if (!empty($externalUrl)) {
                return new RedirectResponse($externalUrl, 303);
            }
        }

        return $handler->handle($request);
    }

    protected function getRedirectUri(ServerRequestInterface $request): ?string
    {
        $redirectToUri = $this->controller->getRedirectUriForShortcut($request);
        if ($redirectToUri !== null) {
            return $redirectToUri;
        }
        return $this->controller->getRedirectUriForMountPoint($request);
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
