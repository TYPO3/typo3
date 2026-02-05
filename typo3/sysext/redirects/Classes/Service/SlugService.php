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
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
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
    final public const CORRELATION_ID_IDENTIFIER = '5d8e6e70';

    protected CorrelationId|string $correlationIdRedirectCreation = '';
    protected CorrelationId|string $correlationIdSlugUpdate = '';
    protected CorrelationId|string $correlationIdPageUpdate = '';
    protected bool $autoUpdateSlugs = false;
    protected bool $autoCreateRedirects = false;
    protected int $redirectTTL = 0;
    protected int $httpStatusCode = 307;

    public function __construct(
        private readonly Context $context,
        private readonly PageRepository $pageRepository,
        private readonly LinkService $linkService,
        private readonly RedirectCacheService $redirectCacheService,
        private readonly SlugRedirectChangeItemFactory $slugRedirectChangeItemFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ConnectionPool $connectionPool,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly TemporaryPermissionMutationService $temporaryPermissionMutationService
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

        $this->correlationIdPageUpdate = $correlationId;
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
            $record = array_replace(
                $this->getTableDefaultValues('sys_redirect'),
                [
                    'pid' => $storagePid,
                    'createdby' => $this->context->getPropertyFromAspect('backend.user', 'id', 0),
                    'endtime' => $this->redirectTTL > 0 ? $endtime->getTimestamp() : 0,
                    'source_host' => $source->getHost(),
                    'source_path' => $source->getPath(),
                    'target' => $targetLink,
                    'target_statuscode' => $this->httpStatusCode,
                ]
            );

            $record = $this->eventDispatcher->dispatch(
                new ModifyAutoCreateRedirectRecordBeforePersistingEvent(
                    slugRedirectChangeItem: $changeItem,
                    source: $source,
                    redirectRecord: $record,
                )
            )->getRedirectRecord();

            // Temporary add permissions to the user to perform the action.
            // Store if we need to revert those changes after the actions.
            $addedTableModify = $this->temporaryPermissionMutationService->addTableModify();
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $redirectNewId = StringUtility::getUniqueId('NEW');
            $data = [
                'sys_redirect' => [
                    $redirectNewId => $record,
                ],
            ];
            $dataHandler->start($data, []);
            $dataHandler->setCorrelationId($this->correlationIdRedirectCreation);
            $dataHandler->process_datamap();
            if ($addedTableModify) {
                // Revert temporary permissions
                $this->temporaryPermissionMutationService->removeTableModify();
            }
            $record['uid'] = $dataHandler->substNEWwithIDs[$redirectNewId] ?? null;

            if ($dataHandler->errorLog !== [] || $record['uid'] === null) {
                $this->logger->error(
                    'Could not create redirect record for source "{host}{path}"',
                    [
                        'host' => $source->getHost(),
                        'path' => $source->getPath(),
                        'persistedUid' => $record['uid'],
                        'errorLog' => $dataHandler->errorLog,
                    ]
                );
                continue;
            }

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

        $schema = $this->tcaSchemaFactory->get('pages');
        $slugHelper = GeneralUtility::makeInstance(SlugHelper::class, 'pages', 'slug', $schema->getField('slug')->getConfiguration());

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
                'correlationIdPageUpdate' => (string)$this->correlationIdPageUpdate,
                'correlationIdSlugUpdate' => (string)$this->correlationIdSlugUpdate,
                'correlationIdRedirectCreation' => (string)$this->correlationIdRedirectCreation,
            ],
            'autoUpdateSlugs' => (bool)$this->autoUpdateSlugs,
            'autoCreateRedirects' => (bool)$this->autoCreateRedirects,
        ];
        BackendUtility::setUpdateSignal('redirects:slugChanged', $data);
    }

    protected function getQueryBuilderForPages(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
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

    /**
     * Gather table default values from TCA and from the cached table schema information as fallback.
     *
     * @param string $tableName
     * @return array<non-empty-string, string|float|int|bool|null>
     * @todo Consider to provide this in Connection if use-full for different places.
     */
    private function getTableDefaultValues(string $tableName): array
    {
        $defaults = [];
        if ($this->tcaSchemaFactory->has($tableName)) {
            $tcaSchema = $this->tcaSchemaFactory->get($tableName);
            foreach ($tcaSchema->getFields() as $columnName => $column) {
                if ($column->hasDefaultValue()) {
                    $defaults[$columnName] = $column->getDefaultValue();
                }
            }
        }
        $connection = $this->connectionPool->getConnectionForTable($tableName);
        $tableColumnInfos = $connection->getSchemaInformation()->listTableColumnInfos($tableName);
        foreach ($tableColumnInfos as $columnName => $columnInfo) {
            if ($columnName === 'uid' || $columnInfo->autoincrement === true) {
                // Autoincrement fields and therefore the default TYPO3 `uid` column
                // should be not provided in a data array to ensure the behaviour
                // kicks correctly in.
                continue;
            }
            if (array_key_exists($columnName, $defaults)) {
                // Already having TCA default value, which weights higher.
                continue;
            }
            $columnDefaultValue = $columnInfo->default;
            if ($columnDefaultValue === null && $columnInfo->notNull === false) {
                // No need to set null as default value for a nullable column.
                continue;
            }
            $defaults[$columnName] = $columnDefaultValue;
        }
        return $defaults;
    }
}
