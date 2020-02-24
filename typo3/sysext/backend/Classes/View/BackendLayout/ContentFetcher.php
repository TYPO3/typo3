<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\View\BackendLayout;

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

use Doctrine\DBAL\Driver\Statement;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class responsible for fetching the content data related to a BackendLayout
 *
 * - Reads content records
 * - Performs workspace overlay on records
 * - Capable of returning all records in active language as flat array
 * - Capable of returning records for a given column in a given (optional) language
 * - Capable of returning translation data (brief info about translation consistenty)
 */
class ContentFetcher
{
    /**
     * @var BackendLayout
     */
    protected $backendLayout;

    /**
     * @var array
     */
    protected $fetchedContentRecords = [];

    /**
     * Stores whether a certain language has translations in it
     *
     * @var array
     */
    protected $languageHasTranslationsCache = [];

    public function __construct(BackendLayout $backendLayout)
    {
        $this->backendLayout = $backendLayout;
    }

    public function setBackendLayout(BackendLayout $backendLayout): void
    {
        $this->backendLayout = $backendLayout;
    }

    /**
     * Gets content records per column.
     * This is required for correct workspace overlays.
     *
     * @param int|null $columnNumber
     * @param int|null $languageId
     * @return array Associative array for each column (colPos) or for all columns if $columnNumber is null
     */
    public function getContentRecordsPerColumn(?int $columnNumber = null, ?int $languageId = null): iterable
    {
        if (empty($this->fetchedContentRecords)) {
            $queryBuilder = $this->getQueryBuilder();
            $records = $this->getResult($queryBuilder->execute());
            foreach ($records as $record) {
                $recordLanguage = (int)$record['sys_language_uid'];
                $recordColumnNumber = (int)$record['colPos'];
                $this->fetchedContentRecords[$recordLanguage][$recordColumnNumber][] = $record;
            }
        }

        $languageId = $languageId ?? $this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer();

        $contentByLanguage = &$this->fetchedContentRecords[$languageId];

        if ($columnNumber === null) {
            return $contentByLanguage ?? [];
        }

        return $contentByLanguage[$columnNumber] ?? [];
    }

    public function getFlatContentRecords(): iterable
    {
        $languageId = $this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer();
        $contentRecords = $this->getContentRecordsPerColumn(null, $languageId);
        return empty($contentRecords) ? [] : array_merge(...$contentRecords);
    }

    public function getUnusedRecords(): iterable
    {
        $unrendered = [];
        $knownColumnPositionNumbers = $this->backendLayout->getColumnPositionNumbers();
        $rememberer = $this->backendLayout->getRecordRememberer();
        foreach ($this->fetchedContentRecords[$this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer()] ?? [] as $contentRecordsInColumn) {
            foreach ($contentRecordsInColumn as $contentRecord) {
                if (!$rememberer->isRemembered((int)$contentRecord['uid']) && !in_array($contentRecord['colPos'], $knownColumnPositionNumbers)) {
                    $unrendered[] = $contentRecord;
                }
            }
        }
        return $unrendered;
    }

    public function getTranslationData(iterable $contentElements, int $language): array
    {
        $configuration = $this->backendLayout->getDrawingConfiguration();

        if ($language === 0) {
            return [];
        }

        if (!isset($this->languageHasTranslationsCache[$language])) {
            foreach ($contentElements as $contentElement) {
                if ((int)$contentElement['l18n_parent'] === 0) {
                    $this->languageHasTranslationsCache[$language]['hasStandAloneContent'] = true;
                    $this->languageHasTranslationsCache[$language]['mode'] = 'free';
                }
                if ((int)$contentElement['l18n_parent'] > 0) {
                    $this->languageHasTranslationsCache[$language]['hasTranslations'] = true;
                    $this->languageHasTranslationsCache[$language]['mode'] = 'connected';
                }
            }
            if (!isset($this->languageHasTranslationsCache[$language])) {
                $this->languageHasTranslationsCache[$language]['hasTranslations'] = false;
            }

            // Check for inconsistent translations, force "mixed" mode and dispatch a FlashMessage to user if such a case is encountered.
            if (isset($this->languageHasTranslationsCache[$language]['hasStandAloneContent'])
                && $this->languageHasTranslationsCache[$language]['hasTranslations']
            ) {
                $this->languageHasTranslationsCache[$language]['mode'] = 'mixed';
                $siteLanguage = $configuration->getSiteLanguage($language);
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf($this->getLanguageService()->getLL('staleTranslationWarning'), $siteLanguage->getTitle()),
                    sprintf($this->getLanguageService()->getLL('staleTranslationWarningTitle'), $siteLanguage->getTitle()),
                    FlashMessage::WARNING
                );
                $service = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $service->getMessageQueueByIdentifier();
                $queue->addMessage($message);
            }
        }
        return $this->languageHasTranslationsCache[$language];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $fields = ['*'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder
            ->select(...$fields)
            ->from('tt_content');

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'tt_content.pid',
                $queryBuilder->createNamedParameter($this->backendLayout->getDrawingConfiguration()->getPageId(), \PDO::PARAM_INT)
            )
        );

        $additionalConstraints = [];
        $parameters = [
            'table' => 'tt_content',
            'fields' => $fields,
            'groupBy' => null,
            'orderBy' => null
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][PageLayoutView::class]['modifyQuery'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (method_exists($hookObject, 'modifyQuery')) {
                $hookObject->modifyQuery(
                    $parameters,
                    'tt_content',
                    $this->backendLayout->getDrawingConfiguration()->getPageId(),
                    $additionalConstraints,
                    $fields,
                    $queryBuilder
                );
            }
        }

        return $queryBuilder;
    }

    protected function getResult(Statement $result): array
    {
        $output = [];
        while ($row = $result->fetch()) {
            BackendUtility::workspaceOL('tt_content', $row, -99, true);
            if ($row) {
                $output[] = $row;
            }
        }
        return $output;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
