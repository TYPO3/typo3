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
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Utility\CanonicalizationUtility;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;

/**
 * Class to add the canonical tag to the page
 *
 * @internal this class is not part of TYPO3's Core API.
 */
class CanonicalGenerator
{
    protected TypoScriptFrontendController $typoScriptFrontendController;
    protected PageRepository $pageRepository;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(TypoScriptFrontendController $typoScriptFrontendController = null, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher ?? GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $this->typoScriptFrontendController = $typoScriptFrontendController ?? $this->getTypoScriptFrontendController();
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
    }

    public function generate(array $params): string
    {
        if ($this->typoScriptFrontendController->config['config']['disableCanonical'] ?? false) {
            return '';
        }

        $event = new ModifyUrlForCanonicalTagEvent('', $params['request'], new Page($params['page']));
        $event = $this->eventDispatcher->dispatch($event);
        $href = $event->getUrl();

        if (empty($href) && (int)$this->typoScriptFrontendController->page['no_index'] === 1) {
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
            $href = $this->checkDefaultCanonical();
        }

        if (!empty($href)) {
            $canonical = '<link ' . GeneralUtility::implodeAttributes([
                'rel' => 'canonical',
                'href' => $href,
            ], true) . '/>' . LF;
            $this->typoScriptFrontendController->additionalHeaderData[] = $canonical;
            return $canonical;
        }
        return '';
    }

    protected function checkForCanonicalLink(): string
    {
        if (!empty($this->typoScriptFrontendController->page['canonical_link'])) {
            return $this->typoScriptFrontendController->cObj->createUrl([
                'parameter' => $this->typoScriptFrontendController->page['canonical_link'],
                'forceAbsoluteUrl' => true,
            ]);
        }
        return '';
    }

    protected function checkContentFromPid(): string
    {
        if ($this->typoScriptFrontendController->contentPid !== $this->typoScriptFrontendController->id) {
            $parameter = $this->typoScriptFrontendController->contentPid;
            if ($parameter > 0) {
                $targetPage = $this->pageRepository->getPage($parameter, true);
                if (!empty($targetPage['canonical_link'])) {
                    $parameter = $targetPage['canonical_link'];
                }
                return $this->typoScriptFrontendController->cObj->createUrl([
                    'parameter' => $parameter,
                    'forceAbsoluteUrl' => true,
                ]);
            }
        }
        return '';
    }

    protected function checkDefaultCanonical(): string
    {
        // We should only create a canonical link to the target, if the target is within a valid site root
        $inSiteRoot = $this->isPageWithinSiteRoot($this->typoScriptFrontendController->id);
        if (!$inSiteRoot) {
            return '';
        }

        // Temporarily remove current mountpoint information as we want to have the
        // URL of the target page and not of the page within the mountpoint if the
        // current page is a mountpoint.
        $previousMp = $this->typoScriptFrontendController->MP;
        $this->typoScriptFrontendController->MP = '';

        $link = $this->typoScriptFrontendController->cObj->createUrl([
            'parameter' => $this->typoScriptFrontendController->id . ',' . $this->typoScriptFrontendController->getPageArguments()->getPageType(),
            'forceAbsoluteUrl' => true,
            'addQueryString' => true,
            'addQueryString.' => [
                'exclude' => implode(
                    ',',
                    CanonicalizationUtility::getParamsToExcludeForCanonicalizedUrl(
                        $this->typoScriptFrontendController->id,
                        (array)$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalCanonicalizedUrlParameters']
                    )
                ),
            ],
        ]);
        $this->typoScriptFrontendController->MP = $previousMp;
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
