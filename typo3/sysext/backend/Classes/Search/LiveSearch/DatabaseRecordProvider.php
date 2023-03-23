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

namespace TYPO3\CMS\Backend\Search\LiveSearch;

use Doctrine\DBAL\ArrayParameterType;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Search\Event\BeforeSearchInDatabaseRecordProviderEvent;
use TYPO3\CMS\Backend\Search\Event\ModifyQueryForLiveSearchEvent;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandProperty;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Search provider to query records from database
 *
 * @internal
 */
final class DatabaseRecordProvider implements SearchProviderInterface
{
    private const RECURSIVE_PAGE_LEVEL = 99;

    protected LanguageService $languageService;
    protected string $userPermissions;
    protected array $pageIdList = [];

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly IconFactory $iconFactory,
        protected readonly LanguageServiceFactory $languageServiceFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly QueryParser $queryParser,
    ) {
        $this->languageService = $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
        $this->userPermissions = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
    }

    public function getFilterLabel(): string
    {
        return $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:liveSearch.databaseRecordProvider.filterLabel');
    }

    public function count(SearchDemand $searchDemand): int
    {
        $count = 0;
        $event = $this->eventDispatcher->dispatch(
            new BeforeSearchInDatabaseRecordProviderEvent($this->getPageIdList(), $searchDemand)
        );
        $this->pageIdList = $event->getSearchPageIds();
        $searchDemand = $event->getSearchDemand();

        $accessibleTables = $this->getAccessibleTables($event);

        $parsedCommand = $this->parseCommand($searchDemand);
        $searchDemand = $parsedCommand['searchDemand'];
        if ($parsedCommand['table'] !== null && in_array($parsedCommand['table'], $accessibleTables)) {
            $accessibleTables = [$parsedCommand['table']];
        }

        foreach ($accessibleTables as $tableName) {
            $count += $this->countByTable($searchDemand, $tableName);
        }

        return $count;
    }

    /**
     * @return ResultItem[]
     */
    public function find(SearchDemand $searchDemand): array
    {
        $result = [];
        $remainingItems = $searchDemand->getLimit();
        $offset = $searchDemand->getOffset();
        if ($remainingItems < 1) {
            return [];
        }

        $event = $this->eventDispatcher->dispatch(
            new BeforeSearchInDatabaseRecordProviderEvent($this->getPageIdList(), $searchDemand)
        );
        $this->pageIdList = $event->getSearchPageIds();
        $searchDemand = $event->getSearchDemand();
        $accessibleTables = $this->getAccessibleTables($event);

        $parsedCommand = $this->parseCommand($searchDemand);
        $searchDemand = $parsedCommand['searchDemand'];
        if ($parsedCommand['table'] !== null && in_array($parsedCommand['table'], $accessibleTables)) {
            $accessibleTables = [$parsedCommand['table']];
        }

        foreach ($accessibleTables as $tableName) {
            if ($remainingItems < 1) {
                break;
            }

            // To have a reliable offset calculation across several database tables, we have to count the amount of
            // records and subtract the amount from the offset to be used, IF the amount is smaller than the requested
            // offset. At any point, the offset will be smaller than the amount of records, which will then be used in
            // ->findByTable().
            // If any subsequent ->findByTable() call returns a result, the offset becomes irrelevant and is then zeroed.
            if ($offset > 0) {
                $tableCount = $this->countByTable($searchDemand, $tableName);
                if ($tableCount <= $offset) {
                    $offset = max(0, $offset - $tableCount);
                    continue;
                }
            }

            $tableResult = $this->findByTable($searchDemand, $tableName, $remainingItems, $offset);
            if ($tableResult !== []) {
                $remainingItems -= count($tableResult);
                $offset = 0;
                $result[] = $tableResult;
            }
        }

        return array_merge([], ...$result);
    }

    protected function parseCommand(SearchDemand $searchDemand): array
    {
        $tableName = null;
        $commandQuery = null;
        $query = $searchDemand->getQuery();

        if ($this->queryParser->isValidCommand($query)) {
            $commandQuery = $query;
        } elseif ($this->queryParser->isValidPageJump($query)) {
            $commandQuery = $this->queryParser->getCommandForPageJump($query);
        }

        if ($commandQuery !== null) {
            $tableName = $this->queryParser->getTableNameFromCommand($query);
            $extractedQueryString = $this->queryParser->getSearchQueryValue($commandQuery);
            $searchDemand = new SearchDemand([
                new DemandProperty(DemandPropertyName::query, $extractedQueryString),
                ...array_filter(
                    $searchDemand->getProperties(),
                    static fn (DemandProperty $demandProperty) => $demandProperty->getName() !== DemandPropertyName::query
                ),
            ]);
        }

        return [
            'searchDemand' => $searchDemand,
            'table' => $tableName,
        ];
    }

    protected function getQueryBuilderForTable(SearchDemand $searchDemand, string $tableName): ?QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        $constraints = $this->buildConstraintsForTable($searchDemand->getQuery(), $queryBuilder, $tableName);
        if ($constraints === []) {
            return null;
        }

        $queryBuilder
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->or(...$constraints)
            );

        if ($this->pageIdList !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($this->pageIdList, ArrayParameterType::INTEGER)
                )
            );
        }

        /** @var ModifyQueryForLiveSearchEvent $event */
        $event = $this->eventDispatcher->dispatch(new ModifyQueryForLiveSearchEvent($queryBuilder, $tableName));

        return $event->getQueryBuilder();
    }

    protected function countByTable(SearchDemand $searchDemand, string $tableName): int
    {
        $queryBuilder = $this->getQueryBuilderForTable($searchDemand, $tableName);
        return (int)$queryBuilder?->count('*')->executeQuery()->fetchOne();
    }

    /**
     * @return ResultItem[]
     */
    protected function findByTable(SearchDemand $searchDemand, string $tableName, int $limit, int $offset): array
    {
        $queryBuilder = $this->getQueryBuilderForTable($searchDemand, $tableName);
        if ($queryBuilder === null) {
            return [];
        }

        $queryBuilder
            ->select('*')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $queryBuilder->addOrderBy('uid', 'DESC');

        $items = [];
        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL($tableName, $row);
            if (!is_array($row)) {
                continue;
            }

            $actions = [];
            $editLink = $this->getEditLink($tableName, $row);
            if ($editLink !== '') {
                $actions[] = (new ResultItemAction('edit_record'))
                    ->setLabel($this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:edit'))
                    ->setIcon($this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL))
                    ->setUrl($editLink);
            }

            $extraData = [
                'table' => $tableName,
                'uid' => $row['uid'],
            ];
            if (!($GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'] ?? false)) {
                $extraData['breadcrumb'] = BackendUtility::getRecordPath($row['pid'], 'AND ' . $this->userPermissions, 0);
            }

            $icon = $this->iconFactory->getIconForRecord($tableName, $row, Icon::SIZE_SMALL);
            $items[] = (new ResultItem(self::class))
                ->setItemTitle(BackendUtility::getRecordTitle($tableName, $row))
                ->setTypeLabel($this->languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']))
                ->setIcon($icon)
                ->setActions(...$actions)
                ->setExtraData($extraData)
                ->setInternalData([
                    'row' => $row,
                ])
            ;
        }

        return $items;
    }

    protected function canAccessTable(string $tableName): bool
    {
        if (($GLOBALS['TCA'][$tableName]['ctrl']['hideTable'] ?? false)
            || (
                !$this->getBackendUser()->check('tables_select', $tableName)
                && !$this->getBackendUser()->check('tables_modify', $tableName)
            )
        ) {
            return false;
        }

        return true;
    }

    protected function getAccessibleTables(BeforeSearchInDatabaseRecordProviderEvent $event): array
    {
        return array_filter(array_keys($GLOBALS['TCA']), function (string $tableName) use ($event) {
            return $this->canAccessTable($tableName) && !$event->isTableIgnored($tableName);
        });
    }

    /**
     * List of available page uids for user, empty array for admin users.
     *
     * @return int[]
     */
    protected function getPageIdList(): array
    {
        if ($this->getBackendUser()->isAdmin()) {
            return [];
        }
        $mounts = $this->getBackendUser()->returnWebmounts();
        $pageList = $mounts;
        $repository = GeneralUtility::makeInstance(PageTreeRepository::class);
        $repository->setAdditionalWhereClause($this->userPermissions);
        $pages = $repository->getFlattenedPages($mounts, self::RECURSIVE_PAGE_LEVEL);
        foreach ($pages as $page) {
            $pageList[] = (int)$page['uid'];
        }
        return $pageList;
    }

    /**
     * Get all fields from given table where we can search for.
     *
     * @return string[]
     */
    protected function extractSearchableFieldsFromTable(string $tableName): array
    {
        // Get the list of fields to search in from the TCA, if any
        if (isset($GLOBALS['TCA'][$tableName]['ctrl']['searchFields'])) {
            $fieldListArray = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'], true);
        } else {
            $fieldListArray = [];
        }
        // Add special fields
        if ($this->getBackendUser()->isAdmin()) {
            $fieldListArray[] = 'uid';
            $fieldListArray[] = 'pid';
        }
        return $fieldListArray;
    }

    /**
     * @return CompositeExpression[]
     */
    protected function buildConstraintsForTable(string $queryString, QueryBuilder $queryBuilder, string $tableName): array
    {
        $fieldsToSearchWithin = $this->extractSearchableFieldsFromTable($tableName);
        if ($fieldsToSearchWithin === []) {
            return [];
        }

        $constraints = [];

        // If the search string is a simple integer, assemble an equality comparison
        if (MathUtility::canBeInterpretedAsInteger($queryString)) {
            foreach ($fieldsToSearchWithin as $fieldName) {
                if ($fieldName !== 'uid'
                    && $fieldName !== 'pid'
                    && !isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])
                ) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] ?? [];
                $fieldType = $fieldConfig['type'] ?? '';

                // Assemble the search condition only if the field is an integer, or is uid or pid
                if ($fieldName === 'uid'
                    || $fieldName === 'pid'
                    || ($fieldType === 'number' && ($fieldConfig['format'] ?? 'integer') === 'integer')
                    || ($fieldType === 'datetime' && !in_array($fieldConfig['dbType'] ?? '', QueryHelper::getDateTimeTypes(), true))
                ) {
                    $constraints[] = $queryBuilder->expr()->eq(
                        $fieldName,
                        $queryBuilder->createNamedParameter($queryString, Connection::PARAM_INT)
                    );
                } elseif ($this->fieldTypeIsSearchable($fieldType)) {
                    // Otherwise and if the field makes sense to be searched, assemble a like condition
                    $constraints[] = $queryBuilder->expr()->like(
                        $fieldName,
                        $queryBuilder->createNamedParameter(
                            '%' . $queryBuilder->escapeLikeWildcards($queryString) . '%'
                        )
                    );
                }
            }
        } else {
            $like = '%' . $queryBuilder->escapeLikeWildcards($queryString) . '%';
            foreach ($fieldsToSearchWithin as $fieldName) {
                if (!isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] ?? [];
                $fieldType = $fieldConfig['type'] ?? '';

                // Check whether search should be case-sensitive or not
                $searchConstraint = $queryBuilder->expr()->and(
                    $queryBuilder->expr()->comparison(
                        'LOWER(' . $queryBuilder->quoteIdentifier($fieldName) . ')',
                        'LIKE',
                        $queryBuilder->createNamedParameter(mb_strtolower($like))
                    )
                );

                if (is_array($fieldConfig['search'] ?? false)) {
                    if (in_array('case', $fieldConfig['search'], true)) {
                        // Replace case insensitive default constraint
                        $searchConstraint = $queryBuilder->expr()->and(
                            $queryBuilder->expr()->like(
                                $fieldName,
                                $queryBuilder->createNamedParameter($like)
                            )
                        );
                    }
                    // Apply additional condition, if any
                    if ($fieldConfig['search']['andWhere'] ?? false) {
                        $searchConstraint = $searchConstraint->with(
                            QueryHelper::stripLogicalOperatorPrefix(QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), $fieldConfig['search']['andWhere']))
                        );
                    }
                }
                // Assemble the search condition only if the field makes sense to be searched
                if ($this->fieldTypeIsSearchable($fieldType) && $searchConstraint->count() !== 0) {
                    $constraints[] = $searchConstraint;
                }
            }
        }

        return $constraints;
    }

    protected function fieldTypeIsSearchable(string $fieldType): bool
    {
        $searchableFieldTypes = [
            'input',
            'text',
            'json',
            'flex',
            'email',
            'link',
            'color',
            'slug',
            'uuid',
        ];

        return in_array($fieldType, $searchableFieldTypes, true);
    }

    /**
     * Build a backend edit link based on given record.
     *
     * @param string $tableName Record table name
     * @param array $row Current record row from database.
     * @return string Link to open an edit window for record.
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess()
     */
    protected function getEditLink(string $tableName, array $row): string
    {
        $backendUser = $this->getBackendUser();
        $editLink = '';
        $calcPerms = new Permission($backendUser->calcPerms(BackendUtility::readPageAccess($row['pid'], $this->userPermissions) ?: []));
        $permsEdit = $calcPerms->editContentPermissionIsGranted();
        // "Edit" link - Only with proper edit permissions
        if (!($GLOBALS['TCA'][$tableName]['ctrl']['readOnly'] ?? false)
            && (
                $backendUser->isAdmin()
                || (
                    $permsEdit
                    && !($GLOBALS['TCA'][$tableName]['ctrl']['adminOnly'] ?? false)
                    && $backendUser->check('tables_modify', $tableName)
                    && $backendUser->recordEditAccessInternals($tableName, $row)
                )
            )
        ) {
            $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('web_list', ['id' => $row['pid']]);
            $editLink = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit[' . $tableName . '][' . $row['uid'] . ']' => 'edit',
                'returnUrl' => $returnUrl,
            ]);
        }
        return $editLink;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
