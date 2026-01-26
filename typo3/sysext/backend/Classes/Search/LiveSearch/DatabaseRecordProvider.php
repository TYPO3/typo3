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
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Search\Event\BeforeSearchInDatabaseRecordProviderEvent;
use TYPO3\CMS\Backend\Search\Event\ModifyConstraintsForLiveSearchEvent;
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
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\NumberFieldType;
use TYPO3\CMS\Core\Schema\SearchableSchemaFieldsCollector;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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

    private LanguageService $languageService;
    private string $userPermissions;
    private array $pageIdList = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly IconFactory $iconFactory,
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly QueryParser $queryParser,
        private readonly SearchableSchemaFieldsCollector $searchableSchemaFieldsCollector,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly ConnectionPool $connectionPool,
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

    private function parseCommand(SearchDemand $searchDemand): array
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
                    static fn(DemandProperty $demandProperty): bool => $demandProperty->getName() !== DemandPropertyName::query
                ),
            ]);
        }

        return [
            'searchDemand' => $searchDemand,
            'table' => $tableName,
        ];
    }

    private function getQueryBuilderForTable(SearchDemand $searchDemand, string $tableName): ?QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace, true));

        $constraints = $this->buildConstraintsForTable($searchDemand->getQuery(), $queryBuilder, $tableName);
        $event = $this->eventDispatcher->dispatch(new ModifyConstraintsForLiveSearchEvent($constraints, $tableName, $searchDemand));
        $constraints = $event->getConstraints();
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

    private function countByTable(SearchDemand $searchDemand, string $tableName): int
    {
        $queryBuilder = $this->getQueryBuilderForTable($searchDemand, $tableName);
        return (int)$queryBuilder?->count('*')->executeQuery()->fetchOne();
    }

    /**
     * @return ResultItem[]
     */
    private function findByTable(SearchDemand $searchDemand, string $tableName, int $limit, int $offset): array
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
        $schema = $this->tcaSchemaFactory->get($tableName);
        $rootLevelCapability = $schema->getCapability(TcaSchemaCapability::RestrictionRootLevel);
        $hasWorkspaceCapability = $schema->hasCapability(TcaSchemaCapability::Workspace);

        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL($tableName, $row);
            if (!is_array($row)) {
                continue;
            }

            $actions = [];

            $editActionLink = $this->getEditActionLink($tableName, $row);
            if ($editActionLink !== '') {
                $actions[DatabaseRecordActionType::EDIT->value] = (new ResultItemAction(DatabaseRecordActionType::EDIT->value))
                    ->setLabel($this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.edit'))
                    ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
                    ->setUrl($editActionLink);
            }

            $layoutActionLink = $this->getLayoutActionLink($tableName, $row);
            if ($layoutActionLink !== '') {
                $actions[DatabaseRecordActionType::LAYOUT->value] = (new ResultItemAction(DatabaseRecordActionType::LAYOUT->value))
                    ->setLabel($this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.layout'))
                    ->setIcon($this->iconFactory->getIcon('actions-viewmode-layout', IconSize::SMALL))
                    ->setUrl($layoutActionLink);
            }

            $listActionLink = $this->getRecordsActionLink($tableName, $row);
            if ($listActionLink !== '') {
                $actions[DatabaseRecordActionType::LIST->value] = (new ResultItemAction(DatabaseRecordActionType::LIST->value))
                    ->setLabel($this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showList'))
                    ->setIcon($this->iconFactory->getIcon('actions-list', IconSize::SMALL))
                    ->setUrl($listActionLink);
            }

            $previewActionLink = $this->getPreviewActionLink($tableName, $row);
            if ($previewActionLink !== '') {
                $actions[DatabaseRecordActionType::PREVIEW->value] = (new ResultItemAction(DatabaseRecordActionType::PREVIEW->value))
                    ->setLabel($this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-file-view', IconSize::SMALL))
                    ->setUrl($previewActionLink);
            }

            // Find the default action
            $defaultActionIdentifier = DatabaseRecordActionType::fromUserForTable($this->getBackendUser(), $tableName);
            $defaultAction = $actions[$defaultActionIdentifier->value] ?? null;

            $extraData = [
                'table' => $tableName,
                'uid' => $row['uid'],
                'inWorkspace' => $hasWorkspaceCapability && $row['t3ver_wsid'] > 0,
            ];
            if ($rootLevelCapability->canExistOnPages()) {
                $extraData['breadcrumb'] = BackendUtility::getRecordPath($row['pid'], 'AND ' . $this->userPermissions, 0);
            }

            $icon = $this->iconFactory->getIconForRecord($tableName, $row, IconSize::SMALL);
            $items[] = (new ResultItem(self::class))
                ->setItemTitle(BackendUtility::getRecordTitle($tableName, $row))
                ->setTypeLabel($schema->getTitle($this->languageService->sL(...)) ?: $tableName)
                ->setIcon($icon)
                ->setActions(...array_values($actions))
                ->setDefaultAction($defaultAction)
                ->setExtraData($extraData)
                ->setInternalData([
                    'row' => $row,
                ])
            ;
        }

        return $items;
    }

    private function canAccessTable(string $tableName): bool
    {
        if (!$this->tcaSchemaFactory->has($tableName)) {
            return true;
        }
        $schema = $this->tcaSchemaFactory->get($tableName);
        if ($schema->hasCapability(TcaSchemaCapability::HideInUi)) {
            return false;
        }
        if (!$this->getBackendUser()->check('tables_select', $tableName)
            && !$this->getBackendUser()->check('tables_modify', $tableName)) {
            return false;
        }

        return true;
    }

    private function getAccessibleTables(BeforeSearchInDatabaseRecordProviderEvent $event): array
    {
        return array_filter($this->tcaSchemaFactory->all()->getNames(), function (string $tableName) use ($event): bool {
            return $this->canAccessTable($tableName) && !$event->isTableIgnored($tableName);
        });
    }

    /**
     * List of available page uids for user, empty array for admin users.
     *
     * @return int[]
     */
    private function getPageIdList(): array
    {
        if ($this->getBackendUser()->isAdmin()) {
            return [];
        }
        $mounts = $this->getBackendUser()->getWebmounts();
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
     * @return CompositeExpression[]
     */
    private function buildConstraintsForTable(string $queryString, QueryBuilder $queryBuilder, string $tableName): array
    {
        $platform = $queryBuilder->getConnection()->getDatabasePlatform();
        $isPostgres = $platform instanceof DoctrinePostgreSQLPlatform;
        $fieldsToSearchWithin = $this->searchableSchemaFieldsCollector->getFields($tableName);
        [$subSchemaDivisorFieldName, $fieldsSubSchemaTypes] = $this->searchableSchemaFieldsCollector->getSchemaFieldSubSchemaTypes($tableName);
        $constraints = [];
        // If the search string is a simple integer, assemble an equality comparison
        if (MathUtility::canBeInterpretedAsInteger($queryString)) {
            // Add uid and pid constraint
            $constraints[] = $queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($queryString, Connection::PARAM_INT)
            );
            $constraints[] = $queryBuilder->expr()->eq(
                'pid',
                $queryBuilder->createNamedParameter($queryString, Connection::PARAM_INT)
            );
            foreach ($fieldsToSearchWithin as $fieldName => $field) {
                // Assemble the search condition only if the field is an integer
                if ($field instanceof NumberFieldType || $field instanceof DateTimeFieldType) {
                    $searchConstraint = $queryBuilder->expr()->eq(
                        $fieldName,
                        $queryBuilder->createNamedParameter($queryString, Connection::PARAM_INT)
                    );
                } else {
                    // Otherwise assemble a like condition
                    $searchConstraint = $queryBuilder->expr()->like(
                        $fieldName,
                        $queryBuilder->createNamedParameter(
                            '%' . $queryBuilder->escapeLikeWildcards($queryString) . '%'
                        )
                    );
                }

                // If this table has subtypes (e.g. tt_content.CType), we want to ensure that only CType that contain
                // e.g. "bodytext" in their list of fields, to search through them. This is important when a field
                // is filled but its type has been changed.
                if ($subSchemaDivisorFieldName !== ''
                    && isset($fieldsSubSchemaTypes[$fieldName])
                    && $fieldsSubSchemaTypes[$fieldName] !== []
                ) {
                    // Using `IN()` with a string-value quoted list is fine for all database systems, even when
                    // used on integer-typed fields and no additional work required here to mitigate something.
                    $searchConstraint = $queryBuilder->expr()->and(
                        $searchConstraint,
                        $queryBuilder->expr()->in(
                            $subSchemaDivisorFieldName,
                            $queryBuilder->quoteArrayBasedValueListToStringList($fieldsSubSchemaTypes[$fieldName])
                        ),
                    );
                }

                $constraints[] = $searchConstraint;
            }
        } else {
            $like = '%' . $queryBuilder->escapeLikeWildcards($queryString) . '%';
            foreach ($fieldsToSearchWithin as $fieldName => $field) {
                // Enforce case-insensitive comparison by lower-casing field and value, unrelated to charset/collation
                // on MySQL/MariaDB, for example if column collation is `utf8mb4_bin` - which would be case-sensitive.
                $preparedFieldName = $isPostgres
                    ? $queryBuilder->castFieldToTextType($fieldName)
                    : $queryBuilder->quoteIdentifier($fieldName);
                $searchConstraint = $queryBuilder->expr()->comparison(
                    'LOWER(' . $preparedFieldName . ')',
                    'LIKE',
                    $queryBuilder->createNamedParameter(mb_strtolower($like))
                );

                // If this table has subtypes (e.g. tt_content.CType), we want to ensure that only CType that contain
                // e.g. "bodytext" in their list of fields, to search through them. This is important when a field
                // is filled but its type has been changed.
                if ($subSchemaDivisorFieldName !== ''
                    && isset($fieldsSubSchemaTypes[$fieldName])
                    && $fieldsSubSchemaTypes[$fieldName] !== []
                ) {
                    // Using `IN()` with a string-value quoted list is fine for all database systems, even when
                    // used on integer-typed fields and no additional work required here to mitigate something.
                    $searchConstraint = $queryBuilder->expr()->and(
                        $searchConstraint,
                        $queryBuilder->expr()->in(
                            $subSchemaDivisorFieldName,
                            $queryBuilder->quoteArrayBasedValueListToStringList($fieldsSubSchemaTypes[$fieldName])
                        ),
                    );
                }

                $constraints[] = $searchConstraint;
            }
        }

        return $constraints;
    }

    /**
     * Build a backend edit link based on given record.
     *
     * @param string $tableName Record table name
     * @param array $row Current record row from database.
     * @return string Link to open an edit window for record.
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess()
     */
    private function getEditActionLink(string $tableName, array $row): string
    {
        $backendUser = $this->getBackendUser();
        $editLink = '';
        $permissionSet = new Permission($backendUser->calcPerms(BackendUtility::readPageAccess($row['pid'], $this->userPermissions) ?: []));
        $schema = $this->tcaSchemaFactory->get($tableName);
        if (!$schema->hasCapability(TcaSchemaCapability::AccessReadOnly)
            && (
                $backendUser->isAdmin()
                || (
                    $permissionSet->editContentPermissionIsGranted()
                    && !$schema->hasCapability(TcaSchemaCapability::AccessAdminOnly)
                    && $backendUser->check('tables_modify', $tableName)
                    && $backendUser->checkRecordEditAccess($tableName, $row)->isAllowed
                )
            )
        ) {
            // @todo pass module context to live search and pass module context to edit link and use for return url
            $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('records', ['id' => $row['pid']]);
            $editLink = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit[' . $tableName . '][' . $row['uid'] . ']' => 'edit',
                'returnUrl' => $returnUrl,
            ]);
        }
        return $editLink;
    }

    /**
     * Build a link to the page layout for the given record.
     *
     * @param array $row Current record row from database.
     * @return string Link to open an edit window for record.
     */
    private function getLayoutActionLink(string $tableName, array $row): string
    {
        $showLink = '';
        if ($tableName !== 'tt_content') {
            return $showLink;
        }
        if ($this->hasPagesAccess($row)) {
            $parameter = [
                'id' => $row['pid'],
                'languages' => [$row['sys_language_uid']],
            ];
            $showLink = ((string)$this->uriBuilder->buildUriFromRoute('web_layout', $parameter)) . '#element-' . $tableName . '-' . $row['uid'];
        }
        return $showLink;
    }

    /**
     * Build a link to the record list based on given record.
     *
     * @param array $row Current record row from database.
     * @return string Link to open an edit window for record.
     */
    private function getRecordsActionLink(string $table, array $row): string
    {
        return $this->hasPagesAccess($row) ? (((string)$this->uriBuilder->buildUriFromRoute('records', ['id' => $row['pid']])) . '#t3-table-' . $table) : '';
    }

    /**
     * Build a preview link to display the record in the frontend.
     *
     * @param array $row Current record row from database.
     * @return string Link to open an edit window for record.
     */
    private function getPreviewActionLink(string $table, array $row): string
    {
        $previewLink = '';
        if ($this->hasPagesAccess($row)) {
            $previewUriBuilder = PreviewUriBuilder::createForRecordPreview($table, $row, (int)($row['pid'] ?? 0));
            if ($previewUriBuilder->isPreviewable()) {
                $previewLink = (string)$previewUriBuilder->buildUri();
            }
        }
        return $previewLink;
    }

    private function hasPagesAccess(array $row): bool
    {
        $backendUser = $this->getBackendUser();
        $permissionSet = new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $row['pid']) ?? []));
        $pagesSchema = $this->tcaSchemaFactory->get('pages');
        return $backendUser->isAdmin()
            || (
                $permissionSet->showPagePermissionIsGranted()
                && !$pagesSchema->hasCapability(TcaSchemaCapability::AccessAdminOnly)
                && $backendUser->check('tables_select', 'pages')
            );
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
