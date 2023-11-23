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

namespace TYPO3\CMS\Redirects\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Redirects\Event\AfterAutoCreateRedirectHasBeenPersistedEvent;
use TYPO3\CMS\Redirects\Event\ModifyAutoCreateRedirectRecordBeforePersistingEvent;
use TYPO3\CMS\Redirects\Hooks\DataHandlerSlugUpdateHook;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItemFactory;

/**
 * @internal Due to some possible refactorings in TYPO3 v10+
 */
class SlugService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * `dechex(1569615472)` (similar to timestamps used with exceptions, but in hex)
     */
    public const CORRELATION_ID_IDENTIFIER = '5d8e6e70';

    /**
     * @var SiteInterface
     */
    protected $site;

    /**
     * @var CorrelationId|string
     */
    protected $correlationIdRedirectCreation = '';

    /**
     * @var CorrelationId|string
     */
    protected $correlationIdSlugUpdate = '';

    /**
     * @var bool
     */
    protected $autoUpdateSlugs;

    /**
     * @var bool
     */
    protected $autoCreateRedirects;

    /**
     * @var int
     */
    protected $redirectTTL;

    /**
     * @var int
     */
    protected $httpStatusCode;

    public function __construct(
        protected readonly Context $context,
        protected readonly SiteFinder $siteFinder,
        protected readonly PageRepository $pageRepository,
        protected readonly LinkService $linkService,
        protected readonly RedirectCacheService $redirectCacheService,
        protected readonly SlugRedirectChangeItemFactory $slugRedirectChangeItemFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function rebuildSlugsForSlugChange(int $pageId, SlugRedirectChangeItem $changeItem, CorrelationId $correlationId): void
    {
        $this->initializeSettings($changeItem->getSite());
        if ($this->autoUpdateSlugs || $this->autoCreateRedirects) {
            $sourceHosts = [];
            $this->createCorrelationIds($pageId, $correlationId);
            if ($this->autoCreateRedirects) {
                $sourceHosts = $this->createRedirects(
                    $changeItem,
                    $changeItem->getDefaultLanguagePageId(),
                    (int)$changeItem->getChanged()['sys_language_uid']
                );
            }
            if ($this->autoUpdateSlugs) {
                $sourceHosts += $this->checkSubPages($changeItem->getChanged(), $changeItem);
            }
            $this->sendNotification();
            // rebuild caches only for matched source hosts
            if ($sourceHosts !== []) {
                foreach (array_unique($sourceHosts) as $sourceHost) {
                    $this->redirectCacheService->rebuildForHost($sourceHost);
                }
            }
        }
    }

    protected function initializeSettings(Site $site): void
    {
        $settings = $site->getSettings();
        $this->autoUpdateSlugs = (bool)$settings->get('redirects.autoUpdateSlugs', true);
        $this->autoCreateRedirects = (bool)$settings->get('redirects.autoCreateRedirects', true);
        if (!$this->context->getPropertyFromAspect('workspace', 'isLive')) {
            $this->autoCreateRedirects = false;
        }
        $this->redirectTTL = (int)$settings->get('redirects.redirectTTL', 0);
        $this->httpStatusCode = (int)$settings->get('redirects.httpStatusCode', 307);
    }

    protected function createCorrelationIds(int $pageId, CorrelationId $correlationId): void
    {
        if ($correlationId->getSubject() === null) {
            $subject = md5('pages:' . $pageId);
            $correlationId = $correlationId->withSubject($subject);
        }

        $this->correlationIdRedirectCreation = $correlationId->withAspects(self::CORRELATION_ID_IDENTIFIER, 'redirect');
        $this->correlationIdSlugUpdate = $correlationId->withAspects(self::CORRELATION_ID_IDENTIFIER, 'slug');
    }

    /**
     * @return string[] All unique source hosts for created redirects.
     */
    protected function createRedirects(SlugRedirectChangeItem $changeItem, int $pageId, int $languageId): array
    {
        $sourceHosts = [];
        $storagePid = $changeItem->getSite()->getRootPageId();
        foreach ($changeItem->getSourcesCollection()->all() as $source) {
            /** @var DateTimeAspect $date */
            $date = $this->context->getAspect('date');
            $endtime = $date->getDateTime()->modify('+' . $this->redirectTTL . ' days');
            $targetLinkParameters = array_replace(['_language' => $languageId], $source->getTargetLinkParameters());
            $targetLink = $this->linkService->asString([
                'type' => 'page',
                'pageuid' => $pageId,
                'parameters' => HttpUtility::buildQueryString($targetLinkParameters),
            ]);
            $record = [
                'pid' => $storagePid,
                'updatedon' => $date->get('timestamp'),
                'createdon' => $date->get('timestamp'),
                'deleted' => 0,
                'disabled' => 0,
                'starttime' => 0,
                'endtime' => $this->redirectTTL > 0 ? $endtime->getTimestamp() : 0,
                'source_host' => $source->getHost(),
                'source_path' => $source->getPath(),
                'is_regexp' => 0,
                'force_https' => 0,
                'respect_query_parameters' => 0,
                'target' => $targetLink,
                'target_statuscode' => $this->httpStatusCode,
                'hitcount' => 0,
                'lasthiton' => 0,
                'disable_hitcount' => 0,
                'creation_type' => 0,
            ];

            $record = $this->eventDispatcher->dispatch(
                new ModifyAutoCreateRedirectRecordBeforePersistingEvent(
                    slugRedirectChangeItem: $changeItem,
                    source: $source,
                    redirectRecord: $record,
                )
            )->getRedirectRecord();
            // @todo Use dataHandler to create records
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_redirect');
            $connection->insert('sys_redirect', $record);
            $id = (int)$connection->lastInsertId('sys_redirect');
            $record['uid'] = $id;
            $this->getRecordHistoryStore()->addRecord('sys_redirect', $id, $record, $this->correlationIdRedirectCreation);
            $this->eventDispatcher->dispatch(
                new AfterAutoCreateRedirectHasBeenPersistedEvent(
                    slugRedirectChangeItem: $changeItem,
                    source: $source,
                    redirectRecord: $record,
                )
            );
            if (!in_array($source->getHost(), $sourceHosts)) {
                $sourceHosts[] = $source->getHost();
            }
        }
        return $sourceHosts;
    }

    /**
     * @return string[] All unique source hosts for created redirects.
     */
    protected function checkSubPages(array $currentPageRecord, SlugRedirectChangeItem $parentChangeItem): array
    {
        $sourceHosts = [];
        $languageUid = (int)$currentPageRecord['sys_language_uid'];
        // resolveSubPages needs the page id of the default language
        $pageId = $languageUid === 0 ? (int)$currentPageRecord['uid'] : (int)$currentPageRecord['l10n_parent'];
        $subPageRecords = $this->resolveSubPages($pageId, $languageUid);
        foreach ($subPageRecords as $subPageRecord) {
            $changeItem = $this->slugRedirectChangeItemFactory->create(
                pageId: (int)$subPageRecord['uid'],
                original: $subPageRecord
            );
            if ($changeItem === null) {
                continue;
            }
            $updatedPageRecord = $this->updateSlug($subPageRecord, $parentChangeItem);
            if ($updatedPageRecord !== null && $this->autoCreateRedirects) {
                $subPageId = (int)$subPageRecord['sys_language_uid'] === 0 ? (int)$subPageRecord['uid'] : (int)$subPageRecord['l10n_parent'];
                $changeItem = $changeItem->withChanged($updatedPageRecord);
                $sourceHosts += array_values($this->createRedirects($changeItem, $subPageId, $languageUid));
            }
        }
        return $sourceHosts;
    }

    protected function resolveSubPages(int $id, int $languageUid): array
    {
        // First resolve all sub-pages in default language
        $queryBuilder = $this->getQueryBuilderForPages();
        $subPages = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        // if the language is not the default language, resolve the language related records.
        if ($languageUid > 0) {
            $queryBuilder = $this->getQueryBuilderForPages();
            $subPages = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in('l10n_parent', $queryBuilder->createNamedParameter(array_column($subPages, 'uid'), Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT))
                )
                ->orderBy('uid', 'ASC')
                ->executeQuery()
                ->fetchAllAssociative();
        }
        $results = [];
        if (!empty($subPages)) {
            $subPages = $this->pageRepository->getPagesOverlay($subPages, $languageUid);
            foreach ($subPages as $subPage) {
                $results[] = $subPage;
                // resolveSubPages needs the page id of the default language
                $pageId = $languageUid === 0 ? (int)$subPage['uid'] : (int)$subPage['l10n_parent'];
                foreach ($this->resolveSubPages($pageId, $languageUid) as $page) {
                    $results[] = $page;
                }
            }
        }
        return $results;
    }

    /**
     * Update a slug by given record, old parent page slug and new parent page slug.
     * In case no update is required, the method returns null else the new slug.
     */
    protected function updateSlug(array $subPageRecord, SlugRedirectChangeItem $changeItem): ?array
    {
        if ($changeItem->getChanged() === null
            || !str_starts_with($subPageRecord['slug'], $changeItem->getOriginal()['slug'])
        ) {
            return null;
        }
        $oldSlugOfParentPage = $changeItem->getOriginal()['slug'];
        $newSlugOfParentPage = $changeItem->getChanged()['slug'];
        $newSlug = rtrim($newSlugOfParentPage, '/') . '/'
            . substr($subPageRecord['slug'], strlen(rtrim($oldSlugOfParentPage, '/') . '/'));
        $state = RecordStateFactory::forName('pages')
            ->fromArray($subPageRecord, $subPageRecord['pid'], $subPageRecord['uid']);
        $fieldConfig = $GLOBALS['TCA']['pages']['columns']['slug']['config'] ?? [];
        $slugHelper = GeneralUtility::makeInstance(SlugHelper::class, 'pages', 'slug', $fieldConfig);

        if (!$slugHelper->isUniqueInSite($newSlug, $state)) {
            $newSlug = $slugHelper->buildSlugForUniqueInSite($newSlug, $state);
        }

        $this->persistNewSlug((int)$subPageRecord['uid'], $newSlug);
        return BackendUtility::getRecord('pages', (int)$subPageRecord['uid']);
    }

    protected function persistNewSlug(int $uid, string $newSlug): void
    {
        $this->disableHook();
        $data = [];
        $data['pages'][$uid]['slug'] = $newSlug;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->setCorrelationId($this->correlationIdSlugUpdate);
        $dataHandler->process_datamap();
        $this->enabledHook();
    }

    protected function sendNotification(): void
    {
        $data = [
            'componentName' => 'redirects',
            'eventName' => 'slugChanged',
            'correlations' => [
                'correlationIdSlugUpdate' => (string)$this->correlationIdSlugUpdate,
                'correlationIdRedirectCreation' => (string)$this->correlationIdRedirectCreation,
            ],
            'autoUpdateSlugs' => (bool)$this->autoUpdateSlugs,
            'autoCreateRedirects' => (bool)$this->autoCreateRedirects,
        ];
        BackendUtility::setUpdateSignal('redirects:slugChanged', $data);
    }

    protected function getRecordHistoryStore(): RecordHistoryStore
    {
        $backendUser = $this->getBackendUser();
        return GeneralUtility::makeInstance(
            RecordHistoryStore::class,
            RecordHistoryStore::USER_BACKEND,
            (int)$backendUser->user['uid'],
            (int)$backendUser->getOriginalUserIdWhenInSwitchUserMode(),
            $this->context->getPropertyFromAspect('date', 'timestamp'),
            $backendUser->workspace
        );
    }

    protected function getQueryBuilderForPages(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        /** @noinspection PhpStrictTypeCheckingInspection */
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->context->getPropertyFromAspect('workspace', 'id')));
        return $queryBuilder;
    }

    protected function enabledHook(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['redirects'] =
            DataHandlerSlugUpdateHook::class;
    }

    protected function disableHook(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['redirects']);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
