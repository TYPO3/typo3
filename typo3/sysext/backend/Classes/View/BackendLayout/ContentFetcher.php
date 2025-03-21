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

namespace TYPO3\CMS\Backend\View\BackendLayout;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\Event\IsContentUsedOnPageLayoutEvent;
use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForContentEvent;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Class responsible for fetching the content data related to a BackendLayout
 *
 * - Reads content records
 * - Performs workspace overlay on records
 * - Capable of returning all records in active language as flat array
 * - Capable of returning records for a given column in a given (optional) language
 * - Capable of returning translation data (brief info about translation consistency)
 *
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class ContentFetcher
{
    protected PageLayoutContext $context;

    protected array $fetchedContentRecords = [];

    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(PageLayoutContext $pageLayoutContext)
    {
        $this->context = $pageLayoutContext;
        $this->fetchedContentRecords = $this->getRuntimeCache()->get('ContentFetcher_fetchedContentRecords') ?: [];
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }

    /**
     * Gets content records per column.
     * This is required for correct workspace overlays.
     *
     * @return array Associative array for each column (colPos) or for all columns if $columnNumber is null
     */
    public function getContentRecordsPerColumn(?int $columnNumber = null, ?int $languageId = null): array
    {
        $languageId = $languageId ?? $this->context->getSiteLanguage()->getLanguageId();

        if (empty($this->fetchedContentRecords)) {
            $isLanguageComparisonMode = $this->context->getDrawingConfiguration()->isLanguageComparisonMode();
            $queryBuilder = $this->getQueryBuilder();
            $result = $queryBuilder->executeQuery();
            $records = $this->getResult($result);
            foreach ($records as $record) {
                $recordLanguage = (int)$record['sys_language_uid'];
                $recordColumnNumber = (int)$record['colPos'];
                if ($recordLanguage === -1) {
                    // Record is set to "all languages", place it according to view mode.
                    if ($isLanguageComparisonMode) {
                        // Force the record to only be shown in default language in "Languages" view mode.
                        $recordLanguage = 0;
                    } else {
                        // Force the record to be shown in the currently active language in "Columns" view mode.
                        $recordLanguage = $languageId;
                    }
                }
                $this->fetchedContentRecords[$recordLanguage][$recordColumnNumber][] = $record;
            }
            $this->getRuntimeCache()->set('ContentFetcher_fetchedContentRecords', $this->fetchedContentRecords);
        }

        $contentByLanguage = &$this->fetchedContentRecords[$languageId];

        if ($columnNumber === null) {
            return $contentByLanguage ?? [];
        }

        return $contentByLanguage[$columnNumber] ?? [];
    }

    public function getFlatContentRecords(int $languageId): iterable
    {
        $contentRecords = $this->getContentRecordsPerColumn(null, $languageId);
        return empty($contentRecords) ? [] : array_merge(...$contentRecords);
    }

    /**
     * Allows to decide via an Event whether a custom type has children which were rendered or should not be rendered.
     */
    public function getUnusedRecords(): iterable
    {
        $unrendered = [];
        $recordIdentityMap = $this->context->getRecordIdentityMap();
        $languageId = $this->context->getDrawingConfiguration()->getSelectedLanguageId();
        // @todo consider to invoke the identity-map much earlier (to avoid fetching database records again)
        foreach ($this->getContentRecordsPerColumn(null, $languageId) as $contentRecordsInColumn) {
            foreach ($contentRecordsInColumn as $contentRecord) {
                $used = $recordIdentityMap->hasIdentifier('tt_content', (int)$contentRecord['uid']);
                // A hook mentioned that this record is used somewhere, so this is in fact "rendered" already
                $event = new IsContentUsedOnPageLayoutEvent($contentRecord, $used, $this->context);
                $event = $this->eventDispatcher->dispatch($event);
                if (!$event->isRecordUsed()) {
                    $unrendered[] = $contentRecord;
                }
            }
        }
        return $unrendered;
    }

    public function getTranslationData(iterable $contentElements, int $language): array
    {
        if ($language === 0) {
            return [];
        }

        $languageTranslationInfo = $this->getRuntimeCache()->get('ContentFetcher_TranslationInfo_' . $language) ?: [];
        if (empty($languageTranslationInfo)) {
            $contentRecordsInDefaultLanguage = $this->getContentRecordsPerColumn(null, 0);
            if (!empty($contentRecordsInDefaultLanguage)) {
                $contentRecordsInDefaultLanguage = array_merge(...$contentRecordsInDefaultLanguage);
            }
            $untranslatedRecordUids = array_flip(
                array_column(
                    // Eliminate records with "-1" as sys_language_uid since they can not be translated
                    array_filter($contentRecordsInDefaultLanguage, static function (array $record): bool {
                        return (int)($record['sys_language_uid'] ?? 0) !== -1;
                    }),
                    'uid'
                )
            );

            foreach ($contentElements as $contentElement) {
                if ((int)$contentElement['sys_language_uid'] === -1) {
                    continue;
                }
                if ((int)$contentElement['l18n_parent'] === 0) {
                    $languageTranslationInfo['hasStandAloneContent'] = true;
                    $languageTranslationInfo['mode'] = 'free';
                }
                if ((int)$contentElement['l18n_parent'] > 0) {
                    $languageTranslationInfo['hasTranslations'] = true;
                    $languageTranslationInfo['mode'] = 'connected';
                }
                if ((int)$contentElement['l10n_source'] > 0) {
                    unset($untranslatedRecordUids[(int)$contentElement['l10n_source']]);
                }
            }
            if (!isset($languageTranslationInfo['hasTranslations'])) {
                $languageTranslationInfo['hasTranslations'] = false;
            }

            $untranslatedRecordUidsWithoutWorkspaceDeletedRecords = $this->removeWorkspaceDeletedPlaceholdersUidsFromUntranslatedRecordUids(array_keys($untranslatedRecordUids), $language);
            $languageTranslationInfo['untranslatedRecordUids'] = $untranslatedRecordUidsWithoutWorkspaceDeletedRecords;
            if (array_keys($untranslatedRecordUids) !== $untranslatedRecordUidsWithoutWorkspaceDeletedRecords) {
                $languageTranslationInfo['hasElementsWithWorkspaceDeletePlaceholders'] = true;
            }

            // Check for inconsistent translations, force "mixed" mode and dispatch a FlashMessage to user if such a case is encountered.
            if (isset($languageTranslationInfo['hasStandAloneContent'])
                && $languageTranslationInfo['hasTranslations']
            ) {
                $languageTranslationInfo['mode'] = 'mixed';

                // We do not want to show the staleTranslationWarning if allowInconsistentLanguageHandling is enabled
                if (!$this->context->getDrawingConfiguration()->getAllowInconsistentLanguageHandling()) {
                    $siteLanguage = $this->context->getSiteLanguage($language);
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:staleTranslationWarning'),
                        sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:staleTranslationWarningTitle'), $siteLanguage->getTitle()),
                        ContextualFeedbackSeverity::WARNING
                    );
                    $service = GeneralUtility::makeInstance(FlashMessageService::class);
                    $queue = $service->getMessageQueueByIdentifier();
                    $queue->addMessage($message);
                }
            }

            $this->getRuntimeCache()->set('ContentFetcher_TranslationInfo_' . $language, $languageTranslationInfo);
        }
        return $languageTranslationInfo;
    }

    protected function removeWorkspaceDeletedPlaceholdersUidsFromUntranslatedRecordUids(array $untranslatedRecordUids, int $language): array
    {
        if ($this->getBackendUser()->workspace <= 0) {
            // Early return if we're not in a workspace to suppress some queries.
            return $untranslatedRecordUids;
        }
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->andWhere(
            $queryBuilder->expr()->and(
                $queryBuilder->expr()->in(
                    'l18n_parent',
                    $queryBuilder->createNamedParameter($untranslatedRecordUids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                )
            )
        );
        $result = $queryBuilder->executeQuery();
        $uidsToRemoveFromUntranslatedRecordUids = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('tt_content', $row, -99, true);
            if ($row && VersionState::tryFrom($row['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER) {
                $uidsToRemoveFromUntranslatedRecordUids[] = $row['l18n_parent'];
            }
        }
        return array_diff($untranslatedRecordUids, $uidsToRemoveFromUntranslatedRecordUids);
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        $queryBuilder
            ->select('*')
            ->from('tt_content');

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'tt_content.pid',
                $queryBuilder->createNamedParameter($this->context->getPageId(), Connection::PARAM_INT)
            )
        );

        $schema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('tt_content');
        $sortBy = $schema->hasCapability(TcaSchemaCapability::SortByField) ? (string)$schema->getCapability(TcaSchemaCapability::SortByField) : '';
        if ($sortBy === '' && $schema->hasCapability(TcaSchemaCapability::DefaultSorting)) {
            $sortBy = (string)$schema->getCapability(TcaSchemaCapability::DefaultSorting)->getValue();
        }
        foreach (QueryHelper::parseOrderBy($sortBy) as $orderBy) {
            $queryBuilder->addOrderBy($orderBy[0], $orderBy[1]);
        }

        $event = new ModifyDatabaseQueryForContentEvent($queryBuilder, 'tt_content', $this->context->getPageId());
        $event = $this->eventDispatcher->dispatch($event);
        return $event->getQueryBuilder();
    }

    protected function getResult($result): array
    {
        $output = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('tt_content', $row, -99, true);
            if ($row && VersionState::tryFrom($row['t3ver_state'] ?? 0) !== VersionState::DELETE_PLACEHOLDER) {
                $output[] = $row;
            }
        }
        return $output;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getRuntimeCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }

    public function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
