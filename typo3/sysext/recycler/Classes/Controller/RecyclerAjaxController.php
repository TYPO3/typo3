<?php

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recycler\Domain\Model\DeletedRecords;
use TYPO3\CMS\Recycler\Domain\Model\Tables;
use TYPO3\CMS\Recycler\Utility\RecyclerUtility;

/**
 * Controller class for the 'recycler' extension. Handles the AJAX Requests
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class RecyclerAjaxController
{
    /**
     * The local configuration array
     *
     * @var array
     */
    protected $conf = [];

    /**
     * @var FrontendInterface
     */
    protected $runtimeCache;

    /**
     * @var DataHandler
     */
    protected $tce;

    public function __construct()
    {
        $this->runtimeCache = $this->getMemoryCache();
        $this->tce = GeneralUtility::makeInstance(DataHandler::class);
    }

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
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

        $extPath = ExtensionManagementUtility::extPath('recycler');
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setPartialRootPaths(['default' => $extPath . 'Resources/Private/Partials']);

        $content = null;
        // Determine the scripts to execute
        switch ($this->conf['action']) {
            case 'getTables':
                $this->setDataInSession(['depthSelection' => $this->conf['depth']]);

                $model = GeneralUtility::makeInstance(Tables::class);
                $content = $model->getTables($this->conf['startUid'], $this->conf['depth']);
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

                $view->setTemplatePathAndFilename($extPath . 'Resources/Private/Templates/Ajax/RecordsTable.html');
                $view->assign('showTableHeader', empty($this->conf['table']));
                $view->assign('showTableName', $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] && $this->getBackendUser()->isAdmin());
                $view->assign('allowDelete', $allowDelete);
                $view->assign('groupedRecords', $this->transform($deletedRowsArray));
                $content = [
                    'rows' => $view->render(),
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
                $affectedRecords = $model->undeleteData($this->conf['records'], $this->conf['recursive']);
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
                $success = $model->deleteData($this->conf['records'] ?? null);
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
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        foreach ($deletedRowsArray as $table => $rows) {
            $groupedRecords[$table]['information'] = [
                'table' => $table,
                'title' => $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title']),
            ];
            foreach ($rows as $row) {
                $pageTitle = $this->getPageTitle((int)$row['pid']);
                $backendUserName = $this->getBackendUserInformation((int)$row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']]);
                $userIdWhoDeleted = $this->getUserWhoDeleted($table, (int)$row['uid']);

                $groupedRecords[$table]['records'][] = [
                    'uid' => $row['uid'],
                    'pid' => $row['pid'],
                    'icon' => $iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render(),
                    'pageTitle' => $pageTitle,
                    'crdate' => BackendUtility::datetime($row[$GLOBALS['TCA'][$table]['ctrl']['crdate']]),
                    'tstamp' => BackendUtility::datetime($row[$GLOBALS['TCA'][$table]['ctrl']['tstamp']]),
                    'owner' => $backendUserName,
                    'owner_uid' => $row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']],
                    'title' => BackendUtility::getRecordTitle($table, $row),
                    'path' => RecyclerUtility::getRecordPath((int)$row['pid']),
                    'delete_user_uid' => $userIdWhoDeleted,
                    'delete_user' => $this->getBackendUserInformation($userIdWhoDeleted),
                    'isParentDeleted' => $table === 'pages' && RecyclerUtility::isParentPageDeleted((int)$row['pid']),
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
                $recordInfo = $this->tce->recordInfo('pages', $pageId, 'title');
                $pageTitle = $recordInfo['title'];
            }
            $this->runtimeCache->set($cacheId, $pageTitle);
        }
        return $pageTitle;
    }

    /**
     * Gets the username of a given backend user
     */
    protected function getBackendUserInformation(int $userId): string
    {
        if ($userId === 0) {
            return '';
        }
        $cacheId = 'recycler-user-' . $userId;
        $username = $this->runtimeCache->get($cacheId);
        if ($username === false) {
            $backendUser = BackendUtility::getRecord('be_users', $userId, 'username', '', false);
            if ($backendUser === null) {
                $username = sprintf(
                    '[%s]',
                    LocalizationUtility::translate('LLL:EXT:recycler/Resources/Private/Language/locallang.xlf:record.deleted')
                );
            } else {
                $username = $backendUser['username'];
            }
            $this->runtimeCache->set($cacheId, $username);
        }
        return $username;
    }

    /**
     * Get the user uid of the user who deleted the record
     */
    protected function getUserWhoDeleted(string $table, int $uid): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_history');
        $queryBuilder->select('userid')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'usertype',
                    $queryBuilder->createNamedParameter('BE', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'recuid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'actiontype',
                    $queryBuilder->createNamedParameter(RecordHistoryStore::ACTION_DELETE, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        return (int)$queryBuilder->executeQuery()->fetchOne();
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

    protected function getMemoryCache(): FrontendInterface
    {
        return $this->getCacheManager()->getCache('runtime');
    }

    protected function getCacheManager(): CacheManager
    {
        return GeneralUtility::makeInstance(CacheManager::class);
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
