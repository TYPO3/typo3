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
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\NumberFieldType;
use TYPO3\CMS\Core\Schema\SearchableSchemaFieldsCollector;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Search provider to query pages from database
 *
 * @internal
 */
final class PageRecordProvider implements SearchProviderInterface
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
        private readonly SiteFinder $siteFinder,
        private readonly SearchableSchemaFieldsCollector $searchableSchemaFieldsCollector,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
        $this->languageService = $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
        $this->userPermissions = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
    }

    public function getFilterLabel(): string
    {
        return $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:liveSearch.pageRecordProvider.filterLabel');
    }

    public function count(SearchDemand $searchDemand): int
    {
        $searchDemand = $this->parseCommand($searchDemand);
        $queryBuilder = $this->getQueryBuilderForTable($searchDemand);
        return (int)$queryBuilder?->count('*')->executeQuery()->fetchOne();
    }

    /**
     * @return ResultItem[]
     */
    public function find(SearchDemand $searchDemand): array
    {
        $this->pageIdList = $this->getPageIdList();
        $result = [];

        $remainingItems = $searchDemand->getLimit();
        $searchDemand = $this->parseCommand($searchDemand);
        $tableResult = $this->findByTable($searchDemand, $remainingItems);

        $result[] = $tableResult;

        return array_merge([], ...$result);
    }

    private function parseCommand(SearchDemand $searchDemand): SearchDemand
    {
        $commandQuery = null;
        $query = $searchDemand->getQuery();

        if ($this->queryParser->isValidCommand($query)) {
            $commandQuery = $query;
        } elseif ($this->queryParser->isValidPageJump($query)) {
            $commandQuery = $this->queryParser->getCommandForPageJump($query);
        }

        if ($commandQuery !== null) {
            $tableName = $this->queryParser->getTableNameFromCommand($query);
            if ($tableName === 'pages') {
                $extractedQueryString = $this->queryParser->getSearchQueryValue($commandQuery);
                $searchDemand = new SearchDemand([
                    new DemandProperty(DemandPropertyName::query, $extractedQueryString),
                    ...array_filter(
                        $searchDemand->getProperties(),
                        static fn(DemandProperty $demandProperty): bool => $demandProperty->getName() !== DemandPropertyName::query
                    ),
                ]);
            }
        }

        return $searchDemand;
    }

    private function getQueryBuilderForTable(SearchDemand $searchDemand): ?QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->add(new WorkspaceRestriction($this->getBackendUser()->workspace))
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        $constraints = $this->buildConstraintsForTable($searchDemand->getQuery(), $queryBuilder);
        $event = $this->eventDispatcher->dispatch(new ModifyConstraintsForLiveSearchEvent($constraints, 'pages', $searchDemand));
        $constraints = $event->getConstraints();
        if ($constraints === []) {
            return null;
        }

        $queryBuilder
            ->from('pages')
            ->where(
                $queryBuilder->expr()->or(...$constraints)
            );

        if ($this->userPermissions) {
            $queryBuilder->andWhere($this->userPermissions);
        }

        if ($this->pageIdList !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($this->pageIdList, ArrayParameterType::INTEGER)
                )
            );
        }

        $event = $this->eventDispatcher->dispatch(new ModifyQueryForLiveSearchEvent($queryBuilder, 'pages'));

        return $event->getQueryBuilder();
    }

    /**
     * @return ResultItem[]
     */
    private function findByTable(SearchDemand $searchDemand, int $limit): array
    {
        $queryBuilder = $this->getQueryBuilderForTable($searchDemand);
        if ($queryBuilder === null) {
            return [];
        }

        $queryBuilder
            ->select('*')
            ->setFirstResult($searchDemand->getOffset())
            ->setMaxResults($limit)
            ->addOrderBy('uid', 'DESC');

        $queryBuilder->addOrderBy('uid', 'DESC');

        $items = [];
        $result = $queryBuilder->executeQuery();
        $schema = $this->tcaSchemaFactory->get('pages');
        $hasWorkspaceCapability = $schema->hasCapability(TcaSchemaCapability::Workspace);

        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('pages', $row);
            if (!is_array($row)) {
                continue;
            }

            $flagIconData = [];
            try {
                $site = $this->siteFinder->getSiteByPageId($row['l10n_source'] > 0 ? $row['l10n_source'] : $row['uid']);
                $siteLanguage = $site->getLanguageById($row['sys_language_uid']);
                $flagIconData = [
                    'identifier' => $siteLanguage->getFlagIdentifier(),
                    'title' => $siteLanguage->getTitle(),
                ];
            } catch (SiteNotFoundException|\InvalidArgumentException) {
                // intended fall-thru, perhaps broken data in database or pages without (=deleted) site config
            }

            $actions = [];

            $editActionLink = $this->getEditActionLink($row);
            if ($editActionLink !== '') {
                $actions[DatabaseRecordActionType::EDIT->value] = (new ResultItemAction(DatabaseRecordActionType::EDIT->value))
                    ->setLabel($this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.edit'))
                    ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
                    ->setUrl($editActionLink);
            }

            $layoutActionLink = $this->getLayoutActionLink($row);
            if ($layoutActionLink !== '') {
                $actions[DatabaseRecordActionType::LAYOUT->value] = (new ResultItemAction(DatabaseRecordActionType::LAYOUT->value))
                    ->setLabel($this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.layout'))
                    ->setIcon($this->iconFactory->getIcon('actions-viewmode-layout', IconSize::SMALL))
                    ->setUrl($layoutActionLink);
            }

            $listActionLink = $this->getRecordsActionLink($row);
            if ($listActionLink !== '') {
                $actions[DatabaseRecordActionType::LIST->value] = (new ResultItemAction(DatabaseRecordActionType::LIST->value))
                    ->setLabel($this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showList'))
                    ->setIcon($this->iconFactory->getIcon('actions-list', IconSize::SMALL))
                    ->setUrl($listActionLink);
            }

            $previewActionLink = $this->getPreviewActionLink($row);
            if ($previewActionLink !== '') {
                $actions[DatabaseRecordActionType::PREVIEW->value] = (new ResultItemAction(DatabaseRecordActionType::PREVIEW->value))
                    ->setLabel($this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-file-view', IconSize::SMALL))
                    ->setUrl($previewActionLink);
            }

            // Find the default action
            $defaultActionIdentifier = DatabaseRecordActionType::fromUserForTable($this->getBackendUser(), 'pages');
            $defaultAction = $actions[$defaultActionIdentifier->value] ?? null;

            $icon = $this->iconFactory->getIconForRecord('pages', $row, IconSize::SMALL);
            $items[] = (new ResultItem(self::class))
                ->setItemTitle(BackendUtility::getRecordTitle('pages', $row))
                ->setTypeLabel($schema->getTitle($this->languageService->sL(...)))
                ->setIcon($icon)
                ->setActions(...array_values($actions))
                ->setDefaultAction($defaultAction)
                ->setExtraData([
                    'breadcrumb' => BackendUtility::getRecordPath($row['pid'], 'AND ' . $this->userPermissions, 0),
                    'flagIcon' => $flagIconData,
                    'inWorkspace' => $hasWorkspaceCapability && $row['t3ver_wsid'] > 0,
                ])
                ->setInternalData([
                    'row' => $row,
                ])
            ;
        }

        return $items;
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
    private function buildConstraintsForTable(string $queryString, QueryBuilder $queryBuilder): array
    {
        $platform = $queryBuilder->getConnection()->getDatabasePlatform();
        $isPostgres = $platform instanceof DoctrinePostgreSQLPlatform;
        $fieldsToSearchWithin = $this->searchableSchemaFieldsCollector->getFields('pages');
        [$subSchemaDivisorFieldName, $fieldsSubSchemaTypes] = $this->searchableSchemaFieldsCollector->getSchemaFieldSubSchemaTypes('pages');
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
                $fieldConfig = $field->getConfiguration();

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
     * Build a backend edit link based on given page.
     *
     * @param array $row Current page row from database.
     * @return string Link to open an edit window for page.
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess()
     */
    private function getEditActionLink(array $row): string
    {
        $backendUser = $this->getBackendUser();
        $editLink = '';
        $permissionSet = new Permission($backendUser->calcPerms(BackendUtility::readPageAccess($row['uid'], $this->userPermissions) ?: []));
        $schema = $this->tcaSchemaFactory->get('pages');
        if (!$schema->hasCapability(TcaSchemaCapability::AccessReadOnly)
            && (
                $backendUser->isAdmin()
                || (
                    $permissionSet->editContentPermissionIsGranted()
                    && !$schema->hasCapability(TcaSchemaCapability::AccessAdminOnly)
                    && $backendUser->check('tables_modify', 'pages')
                    && $backendUser->recordEditAccessInternals('pages', $row)
                )
            )
        ) {
            $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $row['uid']]);
            $editLink = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit[pages][' . $row['uid'] . ']' => 'edit',
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
    private function getLayoutActionLink(array $row): string
    {
        $showLink = '';
        if ($this->hasPagesAccess($row)) {
            $parameter = [
                'id' => $row['sys_language_uid'] === 0 ? $row['uid'] : $row['l10n_parent'],
                'languages' => [$row['sys_language_uid']],
            ];
            $showLink = (string)$this->uriBuilder->buildUriFromRoute('web_layout', $parameter);
        }
        return $showLink;
    }

    /**
     * Build a link to the record list based on given record.
     *
     * @param array $row Current record row from database.
     * @return string Link to open an edit window for record.
     */
    private function getRecordsActionLink(array $row): string
    {
        $showLink = '';
        if ($this->hasPagesAccess($row)) {
            $parameter = [
                'id' => $row['sys_language_uid'] === 0 ? $row['uid'] : $row['l10n_parent'],
                'languages' => [$row['sys_language_uid']],
            ];
            $showLink = ((string)$this->uriBuilder->buildUriFromRoute('records', $parameter)) . '#t3-table-pages';
        }
        return $showLink;
    }

    /**
     * Build a preview link to display the record in the frontend.
     *
     * @param array $row Current record row from database.
     * @return string Link to open an edit window for record.
     */
    private function getPreviewActionLink(array $row): string
    {
        $previewLink = '';
        if ($this->hasPagesAccess($row)) {
            $previewUriBuilder = PreviewUriBuilder::create($row);
            if ($previewUriBuilder->isPreviewable()) {
                $previewLink = (string)$previewUriBuilder->buildUri();
            }
        }

        return $previewLink;
    }

    private function hasPagesAccess(array $row): bool
    {
        $backendUser = $this->getBackendUser();
        $permissionSet = new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $row['uid']) ?? []));
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
