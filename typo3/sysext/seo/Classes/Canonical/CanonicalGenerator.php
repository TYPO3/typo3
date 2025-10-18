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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Utility\CanonicalizationUtility;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;
use TYPO3\CMS\Seo\Exception\CanonicalGenerationDisabledException;

/**
 * Class to add the canonical tag to the page
 *
 * @internal this class is not part of TYPO3's Core API.
 */
#[Autoconfigure(public: true)]
readonly class CanonicalGenerator
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private PageRenderer $pageRenderer,
    ) {}

    public function generate(array $params): string
    {
        /** @var ServerRequestInterface $request */
        $request = $params['request'];
        $pageRecord = $request->getAttribute('frontend.page.information')->getPageRecord();
        $canonicalGenerationDisabledException = null;

        $href = '';
        try {
            $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
            if ($typoScriptConfigArray['disableCanonical'] ?? false) {
                throw new CanonicalGenerationDisabledException('Generation of the canonical tag is disabled via TypoScript "disableCanonical"', 1706104146);
            }
            if ((int)$pageRecord['no_index'] === 1) {
                throw new CanonicalGenerationDisabledException('Generation of the canonical is disabled due to "no_index" being set active in the page properties', 1706104147);
            }

            // 1) Check if page has canonical URL set
            $href = $this->checkForCanonicalLink($request);
            if ($href === '') {
                // 2) Check if page show content from other page
                $href = $this->checkContentFromPid($request);
            }
            if ($href === '') {
                // 3) Fallback, create canonical URL
                $href = $this->checkDefaultCanonical($request);
            }
        } catch (CanonicalGenerationDisabledException $canonicalGenerationDisabledException) {
        } finally {
            $event = $this->eventDispatcher->dispatch(
                new ModifyUrlForCanonicalTagEvent($request, new Page($pageRecord), $href, $canonicalGenerationDisabledException)
            );
            $href = $event->getUrl();
        }

        if ($href !== '') {
            $canonical = '<link ' . GeneralUtility::implodeAttributes([
                'rel' => 'canonical',
                'href' => $href,
            ], true) . ($this->pageRenderer->getDocType()->isXmlCompliant() ? '/' : '') . '>' . LF;
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->addHeaderData($canonical);
            return $canonical;
        }
        return '';
    }

    protected function checkForCanonicalLink(ServerRequestInterface $request): string
    {
        $pageRecord = $request->getAttribute('frontend.page.information')->getPageRecord();
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
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
                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
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
        $pageInformation = clone $pageInformation;
        $pageInformation->setMountPoint('');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
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
