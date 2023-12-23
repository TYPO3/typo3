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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
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
        $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        if ($typoScriptFrontendController->config['config']['disableCanonical'] ?? false) {
            return '';
        }

        /** @var ServerRequestInterface $request */
        $request = $params['request'];
        $event = new ModifyUrlForCanonicalTagEvent($request, new Page($params['page']));
        $event = $this->eventDispatcher->dispatch($event);
        $href = $event->getUrl();

        if (empty($href) && (int)$typoScriptFrontendController->page['no_index'] === 1) {
            return '';
        }

        if (empty($href)) {
            // 1) Check if page has canonical URL set
            $href = $this->checkForCanonicalLink();
        }
        if (empty($href)) {
            // 2) Check if page show content from other page
            $href = $this->checkContentFromPid();
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
            $typoScriptFrontendController->additionalHeaderData[] = $canonical;
            return $canonical;
        }
        return '';
    }

    protected function checkForCanonicalLink(): string
    {
        $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        if (!empty($typoScriptFrontendController->page['canonical_link'])) {
            return $typoScriptFrontendController->cObj->createUrl([
                'parameter' => $typoScriptFrontendController->page['canonical_link'],
                'forceAbsoluteUrl' => true,
            ]);
        }
        return '';
    }

    protected function checkContentFromPid(): string
    {
        $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        if ($typoScriptFrontendController->contentPid !== $typoScriptFrontendController->id) {
            $parameter = $typoScriptFrontendController->contentPid;
            if ($parameter > 0) {
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $targetPage = $pageRepository->getPage($parameter, true);
                if (!empty($targetPage['canonical_link'])) {
                    $parameter = $targetPage['canonical_link'];
                }
                return $typoScriptFrontendController->cObj->createUrl([
                    'parameter' => $parameter,
                    'forceAbsoluteUrl' => true,
                ]);
            }
        }
        return '';
    }

    protected function checkDefaultCanonical(ServerRequestInterface $request): string
    {
        $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        // We should only create a canonical link to the target, if the target is within a valid site root
        $inSiteRoot = $this->isPageWithinSiteRoot($typoScriptFrontendController->id);
        if (!$inSiteRoot) {
            return '';
        }

        // Temporarily remove current mountpoint information as we want to have the
        // URL of the target page and not of the page within the mountpoint if the
        // current page is a mountpoint.
        $pageInformation = clone $request->getAttribute('frontend.page.information');
        $pageInformation->setMountPoint('');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $typoScriptFrontendController);
        $cObj->setRequest($request);
        $cObj->start($pageInformation->getPageRecord(), 'pages');
        $link = $cObj->createUrl([
            'parameter' => $typoScriptFrontendController->id . ',' . $request->getAttribute('routing')->getPageType(),
            'forceAbsoluteUrl' => true,
            'addQueryString' => true,
            'addQueryString.' => [
                'exclude' => implode(
                    ',',
                    CanonicalizationUtility::getParamsToExcludeForCanonicalizedUrl(
                        $typoScriptFrontendController->id,
                        (array)$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalCanonicalizedUrlParameters']
                    )
                ),
            ],
        ]);
        return $link;
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

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
