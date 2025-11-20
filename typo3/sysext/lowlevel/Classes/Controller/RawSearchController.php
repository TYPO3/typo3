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

namespace TYPO3\CMS\Lowlevel\Controller;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Types\Types;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * "Database > Raw search" module.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
class RawSearchController
{
    protected array $MOD_MENU = [];
    protected array $MOD_SETTINGS = [];

    public function __construct(
        protected IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly ComponentFactory $componentFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->MOD_MENU = [
            'sword' => '',
        ];
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $parsedBody['SET'] ?? $queryParams['SET'] ?? [], 'system_database_raw', 'ses');

        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->makeDocHeaderModuleMenu();
        $moduleTemplate->getDocHeaderComponent()->setShortcutContext(
            routeIdentifier: 'system_database_raw',
            displayName: $this->getLanguageService()->translate('title', 'lowlevel.modules.database_raw')
        );

        $title = $this->getLanguageService()->translate('title', 'lowlevel.modules.database_integrity');
        $moduleTemplate->setTitle($title, $this->getLanguageService()->translate('title', 'lowlevel.modules.database_raw'));
        $moduleTemplate->assign('sword', (string)($this->MOD_SETTINGS['sword'] ?? ''));
        $moduleTemplate->assign('results', $this->search($request));
        $moduleTemplate->assign('isSearching', $request->getMethod() === 'POST');
        return $moduleTemplate->renderResponse('SearchRaw');
    }

    protected function search(ServerRequestInterface $request): string
    {
        $swords = $this->MOD_SETTINGS['sword'] ?? '';
        if ($swords === '') {
            return '';
        }
        $out = '';
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            // Avoid querying tables with no columns
            if ($schema->getFields()->count() === 0) {
                continue;
            }
            // Get fields list
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
            $tableColumnInfos = $connection->getSchemaInformation()->listTableColumnInfos($table);
            $normalizedTableColumns = [];
            $fields = [];
            foreach ($tableColumnInfos as $column) {
                if (!$schema->hasField($column->name)) {
                    continue;
                }
                $fields[] = $column->name;
                $normalizedTableColumns[$column->name] = $column;
            }
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->count('*')->from($table);
            $likes = [];
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($swords) . '%';
            foreach ($fields as $field) {
                $quotedField = $queryBuilder->quoteIdentifier($field);
                $column = $normalizedTableColumns[$field] ?? $normalizedTableColumns[$quotedField] ?? null;
                if ($column !== null
                    && $connection->getDatabasePlatform() instanceof DoctrinePostgreSQLPlatform
                    && !in_array($column->typeName, [Types::STRING, Types::ASCII_STRING], true)
                ) {
                    if ($column->typeName === Types::SMALLINT) {
                        // we need to cast smallint to int first, otherwise text case below won't work
                        $quotedField .= '::int';
                    }
                    $quotedField .= '::text';
                }
                $likes[] = $queryBuilder->expr()->comparison(
                    $quotedField,
                    'LIKE',
                    $queryBuilder->createNamedParameter($escapedLikeString)
                );
            }
            $queryBuilder->orWhere(...$likes);
            $count = $queryBuilder->executeQuery()->fetchOne();

            if ($count > 0) {
                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $queryBuilder
                    ->select('uid')
                    ->from($table)
                    ->setMaxResults(200);
                if ($schema->hasCapability(TcaSchemaCapability::Label) && $schema->getCapability(TcaSchemaCapability::Label)->hasPrimaryField()) {
                    $queryBuilder->addSelect($schema->getCapability(TcaSchemaCapability::Label)->getPrimaryFieldName());
                }
                $likes = [];
                foreach ($fields as $field) {
                    $quotedField = $queryBuilder->quoteIdentifier($field);
                    $column = $normalizedTableColumns[$field] ?? $normalizedTableColumns[$quotedField] ?? null;
                    if ($column !== null
                        && $connection->getDatabasePlatform() instanceof DoctrinePostgreSQLPlatform
                        && !in_array($column->typeName, [Types::STRING, Types::ASCII_STRING], true)
                    ) {
                        if ($column->typeName === Types::SMALLINT) {
                            // we need to cast smallint to int first, otherwise text case below won't work
                            $quotedField .= '::int';
                        }
                        $quotedField .= '::text';
                    }
                    $likes[] = $queryBuilder->expr()->comparison(
                        $quotedField,
                        'LIKE',
                        $queryBuilder->createNamedParameter($escapedLikeString)
                    );
                }
                $statement = $queryBuilder->orWhere(...$likes)->executeQuery();
                $lastRow = null;
                $rowArr = [];
                while ($row = $statement->fetchAssociative()) {
                    $rowArr[] = $this->resultRowDisplay($row, $table, $request);
                    $lastRow = $row;
                }
                $markup = [];
                $markup[] = '<div class="panel panel-default">';
                $markup[] = '  <div class="panel-heading">';
                // TODO: why 3 dots in the sL function here?
                $markup[] = htmlspecialchars($schema->getTitle($this->getLanguageService()->sL(...))) . ' (' . $count . ')';
                $markup[] = '  </div>';
                $markup[] = '  <div class="table-fit">';
                $markup[] = '    <table class="table table-striped table-hover">';
                $markup[] = $this->resultRowTitles((array)$lastRow, $table);
                $markup[] = implode(LF, $rowArr);
                $markup[] = '    </table>';
                $markup[] = '  </div>';
                $markup[] = '</div>';

                $out .= implode(LF, $markup);
            }
        }
        return $out;
    }

    protected function resultRowDisplay(array $row, string $table, ServerRequestInterface $request): string
    {
        $languageService = $this->getLanguageService();
        $out = '<tr>';
        foreach ($row as $fieldName => $fieldValue) {
            if ($fieldName !== 'pid' && $fieldName !== 'deleted') {
                $out .= '<td>' . htmlspecialchars((string)$fieldValue) . '</td>';
            }
        }
        $out .= '<td class="col-control">';

        if (!($row['deleted'] ?? false)) {
            // "Edit"
            $editActionUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit',
                    ],
                ],
                'module' => 'system_database',
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()
                    . HttpUtility::buildQueryString(['SET' => $request->getParsedBody()['SET'] ?? []], '&'),
            ]);
            $editAction = '<a class="btn btn-default" href="' . htmlspecialchars($editActionUrl) . '"'
                . ' title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:edit')) . '">'
                . $this->iconFactory->getIcon('actions-open', IconSize::SMALL)->render()
                . '</a>';

            // "Info"
            $infoActionTitle = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:showInfo'));
            $infoAction = sprintf(
                '<a class="btn btn-default" href="#" title="' . $infoActionTitle . '" data-dispatch-action="%s" data-dispatch-args-list="%s">%s</a>',
                'TYPO3.InfoWindow.showItem',
                htmlspecialchars($table . ',' . $row['uid']),
                $this->iconFactory->getIcon('actions-document-info', IconSize::SMALL)->render()
            );

            $out .= '<div class="btn-group" role="group">' . $editAction . $infoAction . '</div>';
        } else {
            $undeleteActionUrl = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                'cmd' => [
                    $table => [
                        $row['uid'] => [
                            'undelete' => 1,
                        ],
                    ],
                ],
                'redirect' => (string)$this->uriBuilder->buildUriFromRoute('system_database_raw'),
            ]);
            $undeleteAction = '<a class="btn btn-default" href="' . htmlspecialchars($undeleteActionUrl) . '"'
                . ' title="' . htmlspecialchars($languageService->translate('undelete_only', 'lowlevel.modules.database_integrity')) . '">'
                . $this->iconFactory->getIcon('actions-edit-restore', IconSize::SMALL)->render()
                . '</a>';
            $out .= '<div class="btn-group" role="group">' . $undeleteAction . '</div>';
        }
        $out .= '</td></tr>';

        return $out;
    }

    /**
     * @param array|null $row Table columns
     */
    protected function resultRowTitles(?array $row, string $table): string
    {
        $languageService = $this->getLanguageService();
        $tableHeader = [];
        // Start header row
        $tableHeader[] = '<thead><tr>';
        // Iterate over given columns
        $schema = $this->tcaSchemaFactory->get($table);
        foreach ($row ?? [] as $fieldName => $fieldValue) {
            if ($schema->hasField($fieldName)) {
                $title = $schema->getField($fieldName)->getLabel();
                $title = $languageService->sL($title);
            } else {
                $title = $languageService->sL($fieldName);
            }
            $tableHeader[] = '<th>' . htmlspecialchars($title) . '</th>';
        }
        // Add empty icon column
        $tableHeader[] = '<th></th>';
        // Close header row
        $tableHeader[] = '</tr></thead>';

        return implode(LF, $tableHeader);
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
