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

namespace TYPO3\CMS\Recycler\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Recycler\Domain\Model\DeletedRecords;

/**
 * Controller class for the 'recycler' extension. Handles the AJAX requests.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class RecyclerAjaxController
{
    /**
     * The local configuration array
     */
    protected array $conf = [];

    public function __construct(
        protected readonly BackendViewFactory $backendViewFactory,
        #[Autowire(service: 'cache.runtime')]
        protected readonly FrontendInterface $runtimeCache,
        protected readonly IconFactory $iconFactory,
        protected readonly ConnectionPool $connectionPool,
        protected readonly RecordHistory $recordHistory,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->conf['action'] = $parsedBody['action'] ?? $queryParams['action'] ?? null;
        $this->conf['table'] = $parsedBody['table'] ?? $queryParams['table'] ?? '';
        $this->conf['limit'] = MathUtility::forceIntegerInRange(
            (int)($this->getBackendUser()->getTSConfig()['mod.']['recycler.']['recordsPageLimit'] ?? 25),
            1
        );
        $this->conf['start'] = (int)($parsedBody['start'] ?? $queryParams['start'] ?? 0);
        $this->conf['filterTxt'] = $parsedBody['filterTxt'] ?? $queryParams['filterTxt'] ?? '';
        $this->conf['startUid'] = (int)($parsedBody['startUid'] ?? $queryParams['startUid'] ?? 0);
        $this->conf['depth'] = (int)($parsedBody['depth'] ?? $queryParams['depth'] ?? 0);
        $this->conf['records'] = $parsedBody['records'] ?? $queryParams['records'] ?? null;
        $this->conf['recursive'] = (bool)($parsedBody['recursive'] ?? $queryParams['recursive'] ?? false);

        $content = null;
        // Determine the scripts to execute
        switch ($this->conf['action']) {
            case 'getTables':
                $this->setDataInSession(['depthSelection' => $this->conf['depth']]);

                $content = $this->getTables($this->conf['startUid'], $this->conf['depth']);
                break;
            case 'getDeletedRecords':
                $this->setDataInSession([
                    'tableSelection' => $this->conf['table'],
                    'depthSelection' => $this->conf['depth'],
                    'resultLimit' => $this->conf['limit'],
                ]);

                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $model->loadData($this->conf['startUid'], $this->conf['table'], $this->conf['depth'], $this->conf['start'] . ',' . $this->conf['limit'], $this->conf['filterTxt']);
                $deletedRowsArray = $model->getDeletedRows();

                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $totalDeleted = $model->getTotalCount($this->conf['startUid'], $this->conf['table'], $this->conf['depth'], $this->conf['filterTxt']);

                $allowDelete = $this->getBackendUser()->isAdmin()
                    ?: (bool)($this->getBackendUser()->getTSConfig()['mod.']['recycler.']['allowDelete'] ?? false);

                $view = $this->backendViewFactory->create($request);
                $view->assign('showTableHeader', empty($this->conf['table']));
                $view->assign('showTableName', $this->getBackendUser()->shallDisplayDebugInformation());
                $view->assign('allowDelete', $allowDelete);
                $view->assign('groupedRecords', $this->transform($deletedRowsArray));
                $content = [
                    'rows' => $view->render('Ajax/RecordsTable'),
                    'totalItems' => $totalDeleted,
                ];
                break;
            case 'undoRecords':
                if (empty($this->conf['records']) || !is_array($this->conf['records'])) {
                    $content = [
                        'success' => false,
                        'message' => LocalizationUtility::translate('flashmessage.delete.norecordsselected', 'recycler'),
                    ];
                    break;
                }

                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $affectedRecords = $model->undeleteData($this->conf['records'], (bool)$this->conf['recursive']);
                $messageKey = 'flashmessage.undo.' . ($affectedRecords !== false ? 'success' : 'failure') . '.' . ((int)$affectedRecords === 1 ? 'singular' : 'plural');
                $content = [
                    'success' => true,
                    'message' => sprintf((string)LocalizationUtility::translate($messageKey, 'recycler'), $affectedRecords),
                ];
                break;
            case 'deleteRecords':
                if (empty($this->conf['records']) || !is_array($this->conf['records'])) {
                    $content = [
                        'success' => false,
                        'message' => LocalizationUtility::translate('flashmessage.delete.norecordsselected', 'recycler'),
                    ];
                    break;
                }

                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $success = $model->deleteData($this->conf['records']);
                $affectedRecords = count($this->conf['records']);
                $messageKey = 'flashmessage.delete.' . ($success ? 'success' : 'failure') . '.' . ($affectedRecords === 1 ? 'singular' : 'plural');
                $content = [
                    'success' => true,
                    'message' => sprintf((string)LocalizationUtility::translate($messageKey, 'recycler'), $affectedRecords),
                ];
                break;
        }
        return new JsonResponse($content);
    }

    /**
     * Transforms the rows for the deleted records by grouping them
     * by their corresponding table and processing the raw record data.
     *
     * @param array<string, array> $deletedRowsArray
     */
    protected function transform(array $deletedRowsArray): array
    {
        $groupedRecords = [];
        $lang = $this->getLanguageService();

        foreach ($deletedRowsArray as $table => $rows) {
            $schema = $this->tcaSchemaFactory->get($table);
            $titleString = $schema->getRawConfiguration()['title'] ?? null;
            $groupedRecords[$table]['information'] = [
                'table' => $table,
                'title' => $titleString !== null ? $lang->sL($titleString) : BackendUtility::getNoRecordTitle(),
            ];
            foreach ($rows as $row) {
                $pageTitle = $this->getPageTitle((int)$row['pid']);
                $ownerInformation = $this->recordHistory->getCreationInformationForRecord($table, $row);
                $ownerUid = (int)(is_array($ownerInformation) && $ownerInformation['usertype'] === 'BE' ? $ownerInformation['userid'] : 0);
                $deleteUserUid = $this->recordHistory->getUserIdFromDeleteActionForRecord($table, (int)$row['uid']);

                $creationDate = '';
                if ($schema->hasCapability(TcaSchemaCapability::CreatedAt)) {
                    $creationDate = BackendUtility::datetime($row[$schema->getCapability(TcaSchemaCapability::CreatedAt)->getFieldName()]);
                }
                $lastUpdateDate = '';
                if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
                    $lastUpdateDate = BackendUtility::datetime($row[$schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName()]);
                }
                $groupedRecords[$table]['records'][] = [
                    'uid' => $row['uid'],
                    'pid' => $row['pid'],
                    'icon' => $this->iconFactory->getIconForRecord($table, $row, IconSize::SMALL)->render(),
                    'pageTitle' => $pageTitle,
                    'crdate' => $creationDate,
                    'tstamp' => $lastUpdateDate,
                    'backendUserUid' => $ownerUid,
                    'backendUser' => $this->getBackendUserInformation($ownerUid),
                    'title' => BackendUtility::getRecordTitle($table, $row),
                    'path' => $this->getRecordPath((int)$row['pid']),
                    'deletedBackendUserUid' => $deleteUserUid,
                    'deletedBackendUser' => $this->getBackendUserInformation($deleteUserUid),
                    'isParentDeleted' => $table === 'pages' && $this->isParentPageDeleted((int)$row['pid']),
                ];
            }
        }

        return $groupedRecords;
    }

    /**
     * Gets the page title of the given page id
     */
    protected function getPageTitle(int $pageId): string
    {
        $cacheId = 'recycler-pagetitle-' . $pageId;
        $pageTitle = $this->runtimeCache->get($cacheId);
        if ($pageTitle === false) {
            if ($pageId === 0) {
                $pageTitle = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
            } else {
                $recordInfo = BackendUtility::getRecord('pages', (string)$pageId, '*', '', false);
                $pageTitle = $recordInfo['title'] ?? '';
            }
            $this->runtimeCache->set($cacheId, $pageTitle);
        }
        return $pageTitle;
    }

    /**
     * Gets the username and real name of a given backend user
     */
    protected function getBackendUserInformation(int $userId): array
    {
        if ($userId === 0) {
            return [];
        }
        $cacheId = 'recycler-user-' . $userId;
        $username = $this->runtimeCache->get($cacheId);
        $userData = [];
        if ($username === false) {
            $backendUser = BackendUtility::getRecord('be_users', $userId, 'username, realName', '', false);
            if ($backendUser !== null) {
                $userData[] = [
                    'username' => $backendUser['username'] ?? '',
                    'realName' => $backendUser['realName'] ?? '',
                ];
            }
            $this->runtimeCache->set($cacheId, $username);
        }
        return $userData;
    }

    /**
     * Sets data in the session of the current backend user.
     *
     * @param array $data The data to be stored in the session
     */
    protected function setDataInSession(array $data): void
    {
        $beUser = $this->getBackendUser();
        $recyclerUC = $beUser->uc['tx_recycler'] ?? [];
        if (!empty(array_diff_assoc($data, $recyclerUC))) {
            $beUser->uc['tx_recycler'] = array_merge($recyclerUC, $data);
            $beUser->writeUC();
        }
    }

    /**
     * Returns the path (visually) of a page $uid, fx. "/First page/Second page/Another subpage"
     * Each part of the path will be limited to $titleLimit characters
     * Deleted pages are filtered out.
     *
     * @param int $uid Page uid for which to create record path
     * @return string Path of record (string) OR array with short/long title if $fullTitleLimit is set.
     */
    protected function getRecordPath(int $uid): string
    {
        $output = '/';
        if ($uid === 0) {
            return $output;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $loopCheck = 100;
        while ($loopCheck > 0) {
            $loopCheck--;

            $queryBuilder
                ->select('uid', 'pid', 'title', 'deleted', 't3ver_oid', 't3ver_wsid', 't3ver_state')
                ->from('pages')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)));
            $row = $queryBuilder->executeQuery()->fetchAssociative();
            if ($row !== false) {
                BackendUtility::workspaceOL('pages', $row);
                if (is_array($row)) {
                    $uid = (int)$row['pid'];
                    $output = '/' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], 1000)) . $output;
                    if ($row['deleted']) {
                        $output = '<span class="text-danger">' . $output . '</span>';
                    }
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        return $output;
    }

    /**
     * Check if parent record is deleted
     */
    protected function isParentPageDeleted(int $pid): bool
    {
        if ($pid === 0) {
            return false;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $deleted = $queryBuilder
            ->select('deleted')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        return (bool)$deleted;
    }

    /**
     * @param int $startUid UID from selected page
     * @param int $depth How many levels recursive
     * @return array The tables to be displayed
     */
    protected function getTables(int $startUid, int $depth): array
    {
        $deletedRecordsTotal = 0;
        $lang = $this->getLanguageService();
        $tables = [];

        foreach ($this->getRelevantSchemata() as $tableName => $schema) {
            $deletedField = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
            // Determine whether the table has deleted records
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();

            $deletedCount = $queryBuilder->count('uid')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->neq(
                        $deletedField,
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchOne();

            if (!$deletedCount) {
                continue;
            }
            /* @var DeletedRecords $deletedDataObject */
            $deletedDataObject = GeneralUtility::makeInstance(DeletedRecords::class);
            $deletedData = $deletedDataObject->loadData($startUid, $tableName, $depth)->getDeletedRows();
            if (isset($deletedData[$tableName]) && $deletedRecordsInTable = count($deletedData[$tableName])) {
                $deletedRecordsTotal += $deletedRecordsInTable;
                $tables[] = [
                    $tableName,
                    $deletedRecordsInTable,
                    $lang->sL($schema->getRawConfiguration()['title'] ?? $tableName),
                ];
            }
        }

        array_unshift($tables, [
            '',
            $deletedRecordsTotal,
            $lang->sL('LLL:EXT:recycler/Resources/Private/Language/locallang.xlf:label_allrecordtypes'),
        ]);
        return $tables;
    }

    /**
     * Returns the modifiable tables of the current user, which have a SoftDelete field.
     *
     * @return TcaSchema[]
     */
    protected function getRelevantSchemata(): array
    {
        $schemata = [];
        $tables = explode(',', $this->getBackendUser()->groupData['tables_modify']);
        foreach ($this->tcaSchemaFactory->all() as $name => $schema) {
            if (!$schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                continue;
            }
            if ($this->getBackendUser()->isAdmin()) {
                $schemata[$name] = $schema;
                continue;
            }
            if (in_array($name, $tables, true)) {
                $schemata[$name] = $schema;
            }
        }
        return $schemata;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
