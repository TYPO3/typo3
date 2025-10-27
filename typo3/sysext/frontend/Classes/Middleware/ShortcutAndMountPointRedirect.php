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
use TYPO3\CMS\Core\LinkHandling\PageTypeLinkResolver;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Redirects pages of type mount points, shortcuts and link to their destination.
 *
 * @internal
 */
class ShortcutAndMountPointRedirect implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected readonly PageTypeLinkResolver $pageTypeLinkResolver,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $exposeInformation = $GLOBALS['TYPO3_CONF_VARS']['FE']['exposeRedirectInformation'] ?? false;

        // Check for shortcut page and mount point redirect
        try {
            $redirectToUri = $this->getRedirectUri($request);
        } catch (ImmediateResponseException $e) {
            return $e->getResponse();
        }
        /** @var PageArguments $pageArguments */
        $pageArguments = $request->getAttribute('routing', null);
        if ($redirectToUri !== null && $redirectToUri !== (string)$request->getUri()) {
            $message = 'TYPO3 Shortcut/Mountpoint' . ($exposeInformation ? ' at page with ID ' . $pageArguments->getPageId() : '');
            return new RedirectResponse(
                $redirectToUri,
                307,
                ['X-Redirect-By' => $message]
            );
        }

        // See if the current page is of doktype "Link", if so, do a redirect as well.
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageRecord = $pageInformation->getPageRecord();
        if ((int)$pageRecord['doktype'] === PageRepository::DOKTYPE_LINK) {
            $url = $this->pageTypeLinkResolver->resolvePageLinkUrl($pageRecord, $request);
            $message =  'TYPO3 Link' . ($exposeInformation ? ' at page with ID ' . $pageArguments->getPageId() : '');
            $status =  $this->pageTypeLinkResolver->getRedirectStatus($pageRecord);

            if ($status !== null) {
                return new RedirectResponse(
                    $url,
                    $status,
                    ['X-Redirect-By' => $message]
                );
            }

            $this->logger->error(
                'Page of type "Link" could not be resolved properly',
                [
                    'page' => $pageRecord,
                ]
            );
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page of type "Link" could not be resolved properly',
                ['code' => PageAccessFailureReasons::INVALID_LINK_PAGE]
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
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        return $contentObjectRenderer->createUrl([
            'parameter' => $parameter,
            'addQueryString' => 'untrusted',
            'addQueryString.' => ['exclude' => 'id,type'],
            'forceAbsoluteUrl' => true,
        ]);
    }
}
