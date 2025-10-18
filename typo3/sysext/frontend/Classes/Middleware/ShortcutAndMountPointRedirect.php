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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Checks mount points, shortcuts and redirects to the target.
 * Alternatively, checks if the current page is a redirect to an external page
 *
 * @internal this middleware might get removed in TYPO3 v10.x.
 */
class ShortcutAndMountPointRedirect implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageRecord = $pageInformation->getPageRecord();
        if ((int)$pageRecord['doktype'] === PageRepository::DOKTYPE_LINK) {
            $externalUrl = $this->prefixExternalPageUrl($pageRecord['url'], $request->getAttribute('normalizedParams')->getSiteUrl());
            $message = 'TYPO3 External URL' . ($exposeInformation ? ' at page with ID ' . $pageRecord['uid'] : '');
            if (!empty($externalUrl)) {
                return new RedirectResponse(
                    $externalUrl,
                    303,
                    ['X-Redirect-By' => $message]
                );
            }
            $this->logger->error(
                'Page of type "External URL" could not be resolved properly',
                [
                    'page' => $pageRecord,
                ]
            );
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page of type "External URL" could not be resolved properly',
                ['code' => PageAccessFailureReasons::INVALID_EXTERNAL_URL]
            );
        }

        return $handler->handle($request);
    }

    protected function getRedirectUri(ServerRequestInterface $request): ?string
    {
        $redirectToUri = $this->getRedirectUriForShortcut($request);
        if ($redirectToUri !== null) {
            return $redirectToUri;
        }
        return $this->getRedirectUriForMountPoint($request);
    }

    /**
     * Returns URI of target page, if the current page is a Shortcut.
     *
     * If the current page is of type shortcut and accessed directly via its URL,
     * the user will be redirected to shortcut target.
     */
    protected function getRedirectUriForShortcut(ServerRequestInterface $request): ?string
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $originalShortcutPageRecord = $pageInformation->getOriginalShortcutPageRecord();
        if (!empty($originalShortcutPageRecord)
            && $originalShortcutPageRecord['doktype'] == PageRepository::DOKTYPE_SHORTCUT
        ) {
            // Check if the shortcut page is actually on the current site, if not, this is a "page not found"
            // because the request was www.mydomain.com/?id=23 where page ID 23 (which is a shortcut) is on another domain/site.
            if ((int)($request->getQueryParams()['id'] ?? 0) > 0) {
                try {
                    $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
                    $targetSite = $siteFinder->getSiteByPageId($originalShortcutPageRecord['l10n_parent'] ?: $originalShortcutPageRecord['uid']);
                } catch (SiteNotFoundException) {
                    $targetSite = null;
                }
                $site = $request->getAttribute('site');
                if ($targetSite !== $site) {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'ID was outside the domain',
                        ['code' => PageAccessFailureReasons::ACCESS_DENIED_HOST_PAGE_MISMATCH]
                    );
                    throw new ImmediateResponseException($response, 1638022483);
                }
            }
            return $this->getUriToCurrentPageForRedirect($request);
        }
        return null;
    }

    /**
     * Returns URI of target page, if the current page is an overlaid mountpoint.
     *
     * If the current page is of type mountpoint and should be overlaid with the contents of the mountpoint page
     * and is accessed directly, the user will be redirected to the mountpoint context.
     */
    protected function getRedirectUriForMountPoint(ServerRequestInterface $request): ?string
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $originalMountPointPageRecord = $pageInformation->getOriginalMountPointPageRecord();
        if (!empty($originalMountPointPageRecord)
            && (int)$originalMountPointPageRecord['doktype'] === PageRepository::DOKTYPE_MOUNTPOINT
        ) {
            return $this->getUriToCurrentPageForRedirect($request);
        }
        return null;
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
            } elseif (!str_starts_with($redirectTo, '/')) {
                $redirectTo = $sitePrefix . $redirectTo;
            }
        }
        return $redirectTo;
    }

    protected function getUriToCurrentPageForRedirect(ServerRequestInterface $request): string
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageRecord = $pageInformation->getPageRecord();
        $parameter = $pageRecord['uid'];
        /** @var PageArguments $pageArguments */
        $pageArguments = $request->getAttribute('routing');
        $type = $pageArguments->getPageType();
        if ($type) {
            $parameter .= ',' . $type;
        }
        return GeneralUtility::makeInstance(ContentObjectRenderer::class)->createUrl([
            'parameter' => $parameter,
            'addQueryString' => 'untrusted',
            'addQueryString.' => ['exclude' => 'id,type'],
            'forceAbsoluteUrl' => true,
        ]);
    }
}
