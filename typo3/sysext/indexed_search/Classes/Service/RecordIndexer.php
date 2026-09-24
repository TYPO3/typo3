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

namespace TYPO3\CMS\IndexedSearch\Service;

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\IndexedSearch\Domain\Repository\AdministrationRepository;
use TYPO3\CMS\IndexedSearch\Indexer;

/**
 * Indexes database records, as configured by indexing configurations
 * of type "Database records" (index_config.type = 1).
 *
 * @internal This class is not part of TYPO3's Core API.
 */
final readonly class RecordIndexer
{
    private const INDEXING_CONFIGURATION_TYPE_RECORDS = 1;

    public function __construct(
        private ConnectionPool $connectionPool,
        private RecordFactory $recordFactory,
        private AdministrationRepository $administrationRepository,
        #[AutowireLocator([Indexer::class])]
        private ServiceLocator $indexers,
    ) {}

    /**
     * Find all indexing configurations of type "Database records" that ask for
     * a record to be indexed as soon as it is changed. A configuration applies when
     * it is placed on the same page as the record, or points to the record's page
     * as alternative source page.
     *
     * @return list<RecordInterface>
     */
    public function findIndexOnChangeConfigurations(RecordInterface $record): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_config');
        $expr = $queryBuilder->expr();
        $rows = $queryBuilder->select('*')
            ->from('index_config')
            ->where(
                $expr->eq('set_id', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $expr->eq('type', $queryBuilder->createNamedParameter(self::INDEXING_CONFIGURATION_TYPE_RECORDS, Connection::PARAM_INT)),
                $expr->eq('table2index', $queryBuilder->createNamedParameter($record->getMainType())),
                $expr->eq('records_indexonchange', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                $expr->or(
                    $expr->and(
                        $expr->eq('alternative_source_pid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                        $expr->eq('pid', $queryBuilder->createNamedParameter($record->getPid(), Connection::PARAM_INT))
                    ),
                    $expr->eq('alternative_source_pid', $queryBuilder->createNamedParameter($record->getPid(), Connection::PARAM_INT))
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(fn(array $row): RecordInterface => $this->recordFactory->createFromDatabaseRow('index_config', $row), $rows);
    }

    /**
     * Index a single record according to the given indexing configuration. The record
     * is indexed as if it was a page, located on the page of the indexing configuration.
     */
    public function indexRecord(Record $record, RecordInterface $configuration): void
    {
        $fieldNames = GeneralUtility::trimExplode(',', (string)$configuration->get('fieldlist'), true);
        $title = $this->getFieldValue($record, (string)array_shift($fieldNames));
        $content = '';
        foreach ($fieldNames as $fieldName) {
            $content .= $this->getFieldValue($record, $fieldName) . ' ';
        }

        parse_str(str_replace('###UID###', (string)$record->getUid(), (string)$configuration->get('get_params')), $queryArguments);
        $systemProperties = $record->getSystemProperties();
        $mtime = $systemProperties?->getLastUpdatedAt()?->getTimestamp() ?? 0;
        $crdate = $systemProperties?->getCreatedAt()?->getTimestamp() ?? 0;

        $indexer = $this->indexers->get(Indexer::class);
        $indexer->forceIndexing = true;
        $indexer->init([
            'id' => $configuration->getPid(),
            'type' => 0,
            'sys_language_uid' => $record->getLanguageId() ?? 0,
            'MP' => '',
            'gr_list' => '0,-1',
            'staticPageArguments' => $queryArguments,
            'freeIndexUid' => $configuration->getUid(),
            'freeIndexSetId' => (int)$configuration->get('set_id'),
            'rootline_uids' => $this->getLocalRootLineUids($configuration->getPid()),
            'index_externals' => false,
            'index_descrLgd' => 200,
            'index_metatags' => true,
            'mtime' => $mtime,
            'crdate' => $crdate,
            'recordUid' => $record->getUid(),
            'metaCharset' => 'utf-8',
            'indexedDocTitle' => '',
            'content' => '<html><head><title>' . htmlspecialchars($title) . '</title></head><body>' . htmlspecialchars($content) . '</body></html>',
        ]);
        $indexer->indexTypo3PageContent();
    }

    /**
     * Remove everything that has been indexed for a record by the given indexing configuration.
     */
    public function removeRecord(RecordInterface $record, RecordInterface $configuration): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('index_phash');
        $phashes = $queryBuilder->select('phash')
            ->from('index_phash')
            ->where(
                $queryBuilder->expr()->eq('recordUid', $queryBuilder->createNamedParameter($record->getUid(), Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('freeIndexUid', $queryBuilder->createNamedParameter($configuration->getUid(), Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchFirstColumn();
        if ($phashes !== []) {
            $this->administrationRepository->removeIndexedPhashRow(implode(',', $phashes), 0);
        }
    }

    /**
     * Remove everything that has been indexed for a page.
     */
    public function removePage(int $pageId): void
    {
        $this->administrationRepository->removeIndexedPhashRow('ALL', $pageId, 0);
    }

    private function getFieldValue(Record $record, string $fieldName): string
    {
        if ($fieldName === '' || !$record->has($fieldName)) {
            return '';
        }
        $value = $record->get($fieldName);
        if (!is_scalar($value)) {
            return '';
        }
        return strip_tags(str_replace('<', ' <', (string)$value));
    }

    /**
     * Uids of the pages from the closest site root down to the given page.
     *
     * @return list<int>
     */
    private function getLocalRootLineUids(int $pageId): array
    {
        try {
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
        } catch (RootLineException) {
            return [];
        }
        $uids = [];
        foreach (array_reverse($rootLine) as $page) {
            if (!empty($page['is_siteroot'])) {
                $uids = [];
            }
            $uids[] = (int)$page['uid'];
        }
        return $uids;
    }
}
