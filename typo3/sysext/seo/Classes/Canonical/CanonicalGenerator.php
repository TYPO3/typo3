<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Seo\Canonical;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Utility\CanonicalizationUtility;

/**
 * Class to add the canonical tag to the page
 *
 * @internal this class is not part of TYPO3's Core API.
 */
class CanonicalGenerator
{
    /**
     * @var TypoScriptFrontendController
     */
    protected $typoScriptFrontendController;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * CanonicalGenerator constructor
     *
     * @param TypoScriptFrontendController $typoScriptFrontendController
     * @param Dispatcher $signalSlotDispatcher
     */
    public function __construct(TypoScriptFrontendController $typoScriptFrontendController = null, Dispatcher $signalSlotDispatcher = null)
    {
        if ($typoScriptFrontendController === null) {
            $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        }
        if ($signalSlotDispatcher === null) {
            $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        }
        $this->typoScriptFrontendController = $typoScriptFrontendController;
        $this->signalSlotDispatcher = $signalSlotDispatcher;
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
    }

    /**
     * @return string
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public function generate(): string
    {
        $href = '';
        $this->signalSlotDispatcher->dispatch(self::class, 'beforeGeneratingCanonical', [&$href]);

        if (empty($href) && (int)$this->typoScriptFrontendController->page['no_index'] === 1) {
            return '';
        }

        if (empty($href)) {
            // 1) Check if page show content from other page
            $href = $this->checkContentFromPid();
        }
        if (empty($href)) {
            // 2) Check if page has canonical URL set
            $href = $this->checkForCanonicalLink();
        }
        if (empty($href)) {
            // 3) Fallback, create canonical URL
            $href = $this->checkDefaultCanonical();
        }

        if (!empty($href)) {
            $canonical = '<link ' . GeneralUtility::implodeAttributes([
                    'rel' => 'canonical',
                    'href' => $href
                ], true) . '/>' . LF;
            $this->typoScriptFrontendController->additionalHeaderData[] = $canonical;
            return $canonical;
        }
        return '';
    }

    /**
     * @return string
     */
    protected function checkForCanonicalLink(): string
    {
        if (!empty($this->typoScriptFrontendController->page['canonical_link'])) {
            return $this->typoScriptFrontendController->cObj->typoLink_URL([
                'parameter' => $this->typoScriptFrontendController->page['canonical_link'],
                'forceAbsoluteUrl' => true,
            ]);
        }
        return '';
    }

    /**
     * @return string
     */
    protected function checkContentFromPid(): string
    {
        if (!empty($this->typoScriptFrontendController->page['content_from_pid'])) {
            $parameter = (int)$this->typoScriptFrontendController->page['content_from_pid'];
            if ($parameter > 0) {
                $targetPage = $this->pageRepository->getPage($parameter, true);
                if (!empty($targetPage['canonical_link'])) {
                    $parameter = $targetPage['canonical_link'];
                }
                return $this->typoScriptFrontendController->cObj->typoLink_URL([
                    'parameter' => $parameter,
                    'forceAbsoluteUrl' => true,
                ]);
            }
        }
        return '';
    }

    /**
     * @return string
     */
    protected function checkDefaultCanonical(): string
    {
        return $this->typoScriptFrontendController->cObj->typoLink_URL([
            'parameter' => $this->typoScriptFrontendController->id . ',' . $this->typoScriptFrontendController->type,
            'forceAbsoluteUrl' => true,
            'addQueryString' => true,
            'addQueryString.' => [
                'method' => 'GET',
                'exclude' => implode(
                    ',',
                    CanonicalizationUtility::getParamsToExcludeForCanonicalizedUrl(
                        (int)$this->typoScriptFrontendController->id,
                        (array)$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalCanonicalizedUrlParameters']
                    )
                )
            ]
        ]);
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
