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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
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
    /**
     * @var PageLayoutContext
     */
    protected $context;

    /**
     * @var array
     */
    protected $fetchedContentRecords = [];

    public function __construct(PageLayoutContext $pageLayoutContext)
    {
        $this->context = $pageLayoutContext;
        $this->fetchedContentRecords = $this->getRuntimeCache()->get('ContentFetcher_fetchedContentRecords') ?: [];
    }

    /**
     * Gets content records per column.
     * This is required for correct workspace overlays.
     *
     * @param int|null $columnNumber
     * @param int|null $languageId
     * @return array Associative array for each column (colPos) or for all columns if $columnNumber is null
     */
    public function getContentRecordsPerColumn(?int $columnNumber = null, ?int $languageId = null): array
    {
        $languageId = $languageId ?? $this->context->getSiteLanguage()->getLanguageId();

        if (empty($this->fetchedContentRecords)) {
            $isLanguageMode = $this->context->getDrawingConfiguration()->getLanguageMode();
            $queryBuilder = $this->getQueryBuilder();
            $result = $queryBuilder->executeQuery();
            $records = $this->getResult($result);
            foreach ($records as $record) {
                $recordLanguage = (int)$record['sys_language_uid'];
                $recordColumnNumber = (int)$record['colPos'];
                if ($recordLanguage === -1) {
                    // Record is set to "all languages", place it according to view mode.
                    if ($isLanguageMode) {
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
     * A hook allows to decide whether a custom type has children which were rendered or should not be rendered.
     *
     * @return iterable
     */
    public function getUnusedRecords(): iterable
    {
        $unrendered = [];
        $hookArray = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['record_is_used'] ?? [];
        $pageLayoutView = PageLayoutView::createFromPageLayoutContext($this->context);

        $knownColumnPositionNumbers = $this->context->getBackendLayout()->getColumnPositionNumbers();
        $rememberer = GeneralUtility::makeInstance(RecordRememberer::class);
        $languageId = $this->context->getDrawingConfiguration()->getSelectedLanguageId();
        foreach ($this->getContentRecordsPerColumn(null, $languageId) as $contentRecordsInColumn) {
            foreach ($contentRecordsInColumn as $contentRecord) {
                $used = $rememberer->isRemembered((int)$contentRecord['uid']);
                // A hook mentioned that this record is used somewhere, so this is in fact "rendered" already
                foreach ($hookArray as $hookFunction) {
                    $_params = ['columns' => $knownColumnPositionNumbers, 'record' => $contentRecord, 'used' => $used];
                    $used = GeneralUtility::callUserFunction($hookFunction, $_params, $pageLayoutView);
                }
                if (!$used) {
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
            $languageTranslationInfo['untranslatedRecordUids'] = array_keys($untranslatedRecordUids);

            // Check for inconsistent translations, force "mixed" mode and dispatch a FlashMessage to user if such a case is encountered.
            if (isset($languageTranslationInfo['hasStandAloneContent'])
                && $languageTranslationInfo['hasTranslations']
            ) {
                $languageTranslationInfo['mode'] = 'mixed';
                $siteLanguage = $this->context->getSiteLanguage($language);

                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $this->getLanguageService()->getLL('staleTranslationWarning'),
                    sprintf($this->getLanguageService()->getLL('staleTranslationWarningTitle'), $siteLanguage->getTitle()),
                    FlashMessage::WARNING
                );
                $service = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $service->getMessageQueueByIdentifier();
                $queue->addMessage($message);
            }

            $this->getRuntimeCache()->set('ContentFetcher_TranslationInfo_' . $language, $languageTranslationInfo);
        }
        return $languageTranslationInfo;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $fields = ['*'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$GLOBALS['BE_USER']->workspace));
        $queryBuilder
            ->select(...$fields)
            ->from('tt_content');

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'tt_content.pid',
                $queryBuilder->createNamedParameter($this->context->getPageId(), \PDO::PARAM_INT)
            )
        );

        $additionalConstraints = [];
        $parameters = [
            'table' => 'tt_content',
            'fields' => $fields,
            'groupBy' => null,
            'orderBy' => null,
        ];

        $sortBy = (string)($GLOBALS['TCA']['tt_content']['ctrl']['sortby'] ?: $GLOBALS['TCA']['tt_content']['ctrl']['default_sortby']);
        foreach (QueryHelper::parseOrderBy($sortBy) as $orderBy) {
            $queryBuilder->addOrderBy($orderBy[0], $orderBy[1]);
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][PageLayoutView::class]['modifyQuery'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (method_exists($hookObject, 'modifyQuery')) {
                $hookObject->modifyQuery(
                    $parameters,
                    'tt_content',
                    $this->context->getPageId(),
                    $additionalConstraints,
                    $fields,
                    $queryBuilder
                );
            }
        }

        return $queryBuilder;
    }

    protected function getResult($result): array
    {
        $output = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('tt_content', $row, -99, true);
            if ($row && !VersionState::cast($row['t3ver_state'] ?? 0)->equals(VersionState::DELETE_PLACEHOLDER)) {
                $output[] = $row;
            }
        }
        return $output;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getRuntimeCache(): VariableFrontend
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }
}
