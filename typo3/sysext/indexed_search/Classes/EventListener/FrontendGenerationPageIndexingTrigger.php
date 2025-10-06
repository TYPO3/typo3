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

namespace TYPO3\CMS\IndexedSearch\EventListener;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\CMS\IndexedSearch\Event\EnableIndexingEvent;
use TYPO3\CMS\IndexedSearch\Indexer;

/**
 * Listen on AfterCacheableContentIsGeneratedEvent, which is called just before the content
 * should be stored in the TYPO3 Cache.
 *
 * @internal this is a TYPO3-internal Event listener implementation and not part of TYPO3's Core API.
 */
final readonly class FrontendGenerationPageIndexingTrigger
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private TimeTracker $timeTracker,
        private PageTitleProviderManager $pageTitleProviderManager,
        private Indexer $indexer,
        private EventDispatcherInterface $eventDispatcher,
        private Context $context,
    ) {}

    /**
     * Trigger indexing of content, after evaluating if this page could / should be indexed.
     * This is triggered for all page content that can be cached.
     */
    #[AsEventListener('indexed-search')]
    public function indexPageContent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        if (!$event->isCachingEnabled()) {
            return;
        }
        $request = $event->getRequest();
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        $pageArguments = $request->getAttribute('routing');
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageParts = $request->getAttribute('frontend.page.parts');
        $pageRecord = $pageInformation->getPageRecord();

        // Determine if page should be indexed, and if so, configure and initialize indexer
        if (!($typoScriptConfigArray['index_enable'] ?? false)) {
            return;
        }

        // Indexer configuration from Extension Manager interface:
        $disableFrontendIndexing = (bool)$this->extensionConfiguration->get('indexed_search', 'disableFrontendIndexing');
        $forceIndexing = $this->eventDispatcher->dispatch(new EnableIndexingEvent($event->getRequest()))->isIndexingEnabled();

        $this->timeTracker->push('Index page');
        if ($disableFrontendIndexing && !$forceIndexing) {
            $this->timeTracker->setTSlogMessage('Index page? No, Ordinary Frontend indexing during rendering is disabled.');
            return;
        }

        if ($pageRecord['no_search'] ?? false) {
            $this->timeTracker->setTSlogMessage('Index page? No, The "No Search" flag has been set in the page properties!');
            return;
        }
        $languageAspect = $this->context->getAspect('language');
        if ($languageAspect->getId() !== $languageAspect->getContentId()) {
            $this->timeTracker->setTSlogMessage(
                'Index page? No, languageId was different from contentId which indicates that the page contains'
                . ' fall-back content and that would be falsely indexed as localized content.'
            );
            return;
        }

        $this->indexer->forceIndexing = $forceIndexing;

        $configuration = [
            // Page id
            'id' => $pageInformation->getId(),
            // Page type
            'type' => $pageArguments->getPageType(),
            // site language id of the language of the indexing.
            'sys_language_uid' => $languageAspect->getId(),
            // MP variable, if any (Mount Points)
            'MP' => $pageInformation->getMountPoint(),
            // Group list
            'gr_list' => implode(',', $this->context->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1])),
            // page arguments array
            'staticPageArguments' => $pageArguments->getStaticArguments(),
            // The creation date of the TYPO3 page
            'crdate' => $pageRecord['crdate'],
            'rootline_uids' => [],
            'content' => $event->getContent(),
            // Alternative title for indexing
            'indexedDocTitle' => $this->pageTitleProviderManager->getTitle($request),
            // Most recent modification time (seconds) of the content on the page. Used to evaluate whether it should be re-indexed.
            'mtime' => $pageParts->getLastChanged(),
            // Whether to index external documents like PDF, DOC etc.
            'index_externals' => $typoScriptConfigArray['index_externals'] ?? true,
            // Length of description text (max 250, default 200)
            'index_descrLgd' => $typoScriptConfigArray['index_descrLgd'] ?? 0,
            'index_metatags' => $typoScriptConfigArray['index_metatags'] ?? true,
            // Set to zero (@todo: why is this needed?)
            'recordUid' => 0,
            'freeIndexUid' => 0,
            'freeIndexSetId' => 0,
        ];
        $localRootLine = $pageInformation->getLocalRootLine();
        foreach ($localRootLine as $rlkey => $rldat) {
            $configuration['rootline_uids'][$rlkey] = $rldat['uid'];
        }

        $this->indexer->init($configuration);
        $this->indexer->indexTypo3PageContent();
        $this->timeTracker->pull();
    }
}
