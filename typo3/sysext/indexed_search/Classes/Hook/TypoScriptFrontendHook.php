<?php

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

namespace TYPO3\CMS\IndexedSearch\Hook;

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Charset\UnknownCharsetException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\IndexedSearch\Indexer;

/**
 * Hooks for \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController (TSFE).
 * @internal this is a TYPO3-internal hook implementation and not part of TYPO3's Core API.
 */
class TypoScriptFrontendHook
{
    /**
     * Trigger indexing of content, after evaluating if this page could / should be indexed.
     *
     * @param array $parameters
     * @param TypoScriptFrontendController $tsfe
     */
    public function indexPageContent(array $parameters, TypoScriptFrontendController $tsfe)
    {
        // Determine if page should be indexed, and if so, configure and initialize indexer
        if (!($tsfe->config['config']['index_enable'] ?? false)) {
            return;
        }

        // Indexer configuration from Extension Manager interface:
        $disableFrontendIndexing = (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('indexed_search', 'disableFrontendIndexing');
        $forceIndexing = $tsfe->applicationData['forceIndexing'] ?? false;

        $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
        $timeTracker->push('Index page');
        if ($disableFrontendIndexing && !$forceIndexing) {
            $timeTracker->setTSlogMessage('Index page? No, Ordinary Frontend indexing during rendering is disabled.');
            return;
        }

        if ($tsfe->page['no_search']) {
            $timeTracker->setTSlogMessage('Index page? No, The "No Search" flag has been set in the page properties!');
            return;
        }
        /** @var LanguageAspect $languageAspect */
        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
        if ($languageAspect->getId() !== $languageAspect->getContentId()) {
            $timeTracker->setTSlogMessage('Index page? No, languageId was different from contentId which indicates that the page contains fall-back content and that would be falsely indexed as localized content.');
            return;
        }
        // Init and start indexing
        $indexer = GeneralUtility::makeInstance(Indexer::class);
        $indexer->forceIndexing = $forceIndexing;
        $indexer->init($this->initializeIndexerConfiguration($tsfe, $languageAspect));
        $indexer->indexTypo3PageContent();
        $timeTracker->pull();
    }

    /**
     * Setting up internal configuration from config array based on TypoScriptFrontendController
     * Information about page for which the indexing takes place
     *
     * @param TypoScriptFrontendController $tsfe
     * @param LanguageAspect $languageAspect
     * @return array
     */
    protected function initializeIndexerConfiguration(TypoScriptFrontendController $tsfe, LanguageAspect $languageAspect): array
    {
        $pageArguments = $tsfe->getPageArguments();
        $configuration = [
            // Page id
            'id' => $tsfe->id,
            // Page type
            'type'=> $tsfe->type,
            // sys_language UID of the language of the indexing.
            'sys_language_uid' => $languageAspect->getId(),
            // MP variable, if any (Mount Points)
            'MP' => $tsfe->MP,
            // Group list
            'gr_list' => implode(',', GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1])),
            // page arguments array
            'staticPageArguments' => $pageArguments->getStaticArguments(),
            // The creation date of the TYPO3 page
            'crdate' => $tsfe->page['crdate'],
            'rootline_uids' => [],
        ];

        // Root line uids
        foreach ($tsfe->config['rootLine'] as $rlkey => $rldat) {
            $configuration['rootline_uids'][$rlkey] = $rldat['uid'];
        }
        // Content of page
        $configuration['content'] = $this->convOutputCharset($tsfe->content, $tsfe->metaCharset);
        // Content string (HTML of TYPO3 page)
        $configuration['indexedDocTitle'] = $this->convOutputCharset($tsfe->indexedDocTitle, $tsfe->metaCharset);
        // Alternative title for indexing
        $configuration['metaCharset'] = $tsfe->metaCharset;
        // Character set of content (will be converted to utf-8 during indexing)
        $configuration['mtime'] = $tsfe->register['SYS_LASTCHANGED'] ?? $tsfe->page['SYS_LASTCHANGED'];
        // Most recent modification time (seconds) of the content on the page. Used to evaluate whether it should be re-indexed.
        // Configuration of behavior
        $configuration['index_externals'] = $tsfe->config['config']['index_externals'];
        // Whether to index external documents like PDF, DOC etc. (if possible)
        $configuration['index_descrLgd'] = $tsfe->config['config']['index_descrLgd'] ?? 0;
        // Length of description text (max 250, default 200)
        $configuration['index_metatags'] = $tsfe->config['config']['index_metatags'] ?? true;
        // Set to zero
        $configuration['recordUid'] = 0;
        $configuration['freeIndexUid'] = 0;
        $configuration['freeIndexSetId'] = 0;
        return $configuration;
    }

    /**
     * Converts input string from utf-8 to metaCharset IF the two charsets are different.
     *
     * @param string $content Content to be converted.
     * @param string $metaCharset
     * @return string Converted content string.
     */
    protected function convOutputCharset(string $content, string $metaCharset): string
    {
        if ($metaCharset !== 'utf-8') {
            $charsetConverter = GeneralUtility::makeInstance(CharsetConverter::class);
            try {
                $content = $charsetConverter->conv($content, 'utf-8', $metaCharset);
            } catch (UnknownCharsetException $e) {
                throw new \RuntimeException('Invalid config.metaCharset: ' . $e->getMessage(), 1508916285);
            }
        }
        return $content;
    }
}
