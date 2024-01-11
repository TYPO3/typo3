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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\CMS\IndexedSearch\Event\EnableIndexingEvent;
use TYPO3\CMS\IndexedSearch\Indexer;

/**
 * PSR-14 Event Listener for \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController (TSFE),
 * which is called just before the content should be stored in the TYPO3 Cache.
 *
 * @internal this is a TYPO3-internal Event listener implementation and not part of TYPO3's Core API.
 */
class FrontendGenerationPageIndexingTrigger
{
    public function __construct(
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly TimeTracker $timeTracker,
        protected readonly PageTitleProviderManager $pageTitleProviderManager,
        protected readonly Indexer $indexer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Context $context,
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
        $tsfe = $event->getController();
        // Determine if page should be indexed, and if so, configure and initialize indexer
        if (!($tsfe->config['config']['index_enable'] ?? false)) {
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

        if ($tsfe->page['no_search'] ?? false) {
            $this->timeTracker->setTSlogMessage('Index page? No, The "No Search" flag has been set in the page properties!');
            return;
        }
        $languageAspect = $this->context->getAspect('language');
        if ($languageAspect->getId() !== $languageAspect->getContentId()) {
            $this->timeTracker->setTSlogMessage('Index page? No, languageId was different from contentId which indicates that the page contains fall-back content and that would be falsely indexed as localized content.');
            return;
        }
        // Init and start indexing
        $this->indexer->forceIndexing = $forceIndexing;
        $this->indexer->init($this->initializeIndexerConfiguration($event->getRequest(), $tsfe, $languageAspect));
        $this->indexer->indexTypo3PageContent();
        $this->timeTracker->pull();
    }

    /**
     * Setting up internal configuration from config array based on TypoScriptFrontendController
     * Information about page for which the indexing takes place
     */
    protected function initializeIndexerConfiguration(ServerRequestInterface $request, TypoScriptFrontendController $tsfe, LanguageAspect $languageAspect): array
    {
        /** @var PageArguments $pageArguments */
        $pageArguments = $request->getAttribute('routing');
        $pageInformation = $request->getAttribute('frontend.page.information');
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
            'crdate' => $pageInformation->getPageRecord()['crdate'],
            'rootline_uids' => [],
        ];

        // Root line uids
        foreach ($tsfe->config['rootLine'] as $rlkey => $rldat) {
            $configuration['rootline_uids'][$rlkey] = $rldat['uid'];
        }
        // Content of page
        // Content string (HTML of TYPO3 page)
        $configuration['content'] = $tsfe->content;

        // Alternative title for indexing
        // @see https://forge.typo3.org/issues/88041
        $configuration['indexedDocTitle'] = $this->pageTitleProviderManager->getTitle($request);

        // Most recent modification time (seconds) of the content on the page. Used to evaluate whether it should be re-indexed.
        $configuration['mtime'] = $tsfe->register['SYS_LASTCHANGED'] ?? $tsfe->page['SYS_LASTCHANGED'];
        // Configuration of behavior
        $configuration['index_externals'] = $tsfe->config['config']['index_externals'] ?? true;
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
}
