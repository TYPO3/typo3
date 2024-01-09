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

namespace TYPO3\CMS\Seo\Canonical;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Utility\CanonicalizationUtility;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;

/**
 * Class to add the canonical tag to the page
 *
 * @internal this class is not part of TYPO3's Core API.
 */
readonly class CanonicalGenerator
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function generate(array $params): string
    {
        /** @var ServerRequestInterface $request */
        $request = $params['request'];
        $pageRecord = $request->getAttribute('frontend.page.information')->getPageRecord();
        if ($request->getAttribute('frontend.controller')->config['config']['disableCanonical'] ?? false) {
            return '';
        }

        $event = new ModifyUrlForCanonicalTagEvent($request, new Page($pageRecord));
        $event = $this->eventDispatcher->dispatch($event);
        $href = $event->getUrl();

        $pageInformation = $request->getAttribute('frontend.page.information');
        if (empty($href) && (int)$pageInformation->getPageRecord()['no_index'] === 1) {
            return '';
        }

        if (empty($href)) {
            // 1) Check if page has canonical URL set
            $href = $this->checkForCanonicalLink($request);
        }
        if (empty($href)) {
            // 2) Check if page show content from other page
            $href = $this->checkContentFromPid($request);
        }
        if (empty($href)) {
            // 3) Fallback, create canonical URL
            $href = $this->checkDefaultCanonical($request);
        }

        if (!empty($href)) {
            $canonical = '<link ' . GeneralUtility::implodeAttributes([
                'rel' => 'canonical',
                'href' => $href,
            ], true) . '/>' . LF;
            $request->getAttribute('frontend.controller')->additionalHeaderData[] = $canonical;
            return $canonical;
        }
        return '';
    }

    protected function checkForCanonicalLink(ServerRequestInterface $request): string
    {
        $typoScriptFrontendController = $request->getAttribute('frontend.controller');
        $pageRecord = $request->getAttribute('frontend.page.information')->getPageRecord();
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $typoScriptFrontendController);
        $cObj->setRequest($request);
        $cObj->start($pageRecord, 'pages');
        if (!empty($pageRecord['canonical_link'])) {
            return $cObj->createUrl([
                'parameter' => $pageRecord['canonical_link'],
                'forceAbsoluteUrl' => true,
            ]);
        }
        return '';
    }

    protected function checkContentFromPid(ServerRequestInterface $request): string
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $id = $pageInformation->getId();
        $contentPid = $pageInformation->getContentFromPid();
        if ($id !== $contentPid) {
            $targetPid = $contentPid;
            if ($targetPid > 0) {
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $targetPageRecord = $pageRepository->getPage($contentPid, true);
                if (!empty($targetPageRecord['canonical_link'])) {
                    $targetPid = $targetPageRecord['canonical_link'];
                }
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $request->getAttribute('frontend.controller'));
                $cObj->setRequest($request);
                $cObj->start($request->getAttribute('frontend.page.information')->getPageRecord(), 'pages');
                return $cObj->createUrl([
                    'parameter' => $targetPid,
                    'forceAbsoluteUrl' => true,
                ]);
            }
        }
        return '';
    }

    protected function checkDefaultCanonical(ServerRequestInterface $request): string
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $id = $pageInformation->getId();
        // We should only create a canonical link to the target, if the target is within a valid site root
        $inSiteRoot = $this->isPageWithinSiteRoot($id);
        if (!$inSiteRoot) {
            return '';
        }

        // Temporarily remove current mount point information as we want to have the
        // URL of the target page and not of the page within the mount point if the
        // current page is a mount point.
        $pageInformation = clone $request->getAttribute('frontend.page.information');
        $pageInformation->setMountPoint('');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $request->getAttribute('frontend.controller'));
        $cObj->setRequest($request);
        $cObj->start($pageInformation->getPageRecord(), 'pages');
        return $cObj->createUrl([
            'parameter' => $id . ',' . $request->getAttribute('routing')->getPageType(),
            'forceAbsoluteUrl' => true,
            'addQueryString' => true,
            'addQueryString.' => [
                'exclude' => implode(
                    ',',
                    CanonicalizationUtility::getParamsToExcludeForCanonicalizedUrl(
                        $id,
                        (array)$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalCanonicalizedUrlParameters']
                    )
                ),
            ],
        ]);
    }

    protected function isPageWithinSiteRoot(int $id): bool
    {
        $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $id)->get();
        foreach ($rootline as $page) {
            if ($page['is_siteroot']) {
                return true;
            }
        }
        return false;
    }
}
