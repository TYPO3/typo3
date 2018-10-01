<?php
namespace TYPO3\CMS\Recycler\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recycler\Domain\Model\DeletedRecords;
use TYPO3\CMS\Recycler\Domain\Model\Tables;

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
        /* @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setPartialRootPaths(['default' => $extPath . 'Resources/Private/Partials']);

        $content = '';
        // Determine the scripts to execute
        switch ($this->conf['action']) {
            case 'getTables':
                $this->setDataInSession(['depthSelection' => $this->conf['depth']]);

                /* @var Tables $model */
                $model = GeneralUtility::makeInstance(Tables::class);
                $content = $model->getTables($this->conf['startUid'], $this->conf['depth']);
                break;
            case 'getDeletedRecords':
                $this->setDataInSession([
                    'tableSelection' => $this->conf['table'],
                    'depthSelection' => $this->conf['depth'],
                    'resultLimit' => $this->conf['limit'],
                ]);

                /* @var DeletedRecords $model */
                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $model->loadData($this->conf['startUid'], $this->conf['table'], $this->conf['depth'], $this->conf['start'] . ',' . $this->conf['limit'], $this->conf['filterTxt']);
                $deletedRowsArray = $model->getDeletedRows();

                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $totalDeleted = $model->getTotalCount($this->conf['startUid'], $this->conf['table'], $this->conf['depth'], $this->conf['filterTxt']);

                /* @var DeletedRecordsController $controller */
                $controller = GeneralUtility::makeInstance(DeletedRecordsController::class);
                $recordsArray = $controller->transform($deletedRowsArray);

                $allowDelete = $this->getBackendUser()->isAdmin()
                    ?: (bool)($this->getBackendUser()->getTSConfig()['mod.']['recycler.']['allowDelete'] ?? false);

                $view->setTemplatePathAndFilename($extPath . 'Resources/Private/Templates/Ajax/RecordsTable.html');
                $view->assign('records', $recordsArray['rows']);
                $view->assign('allowDelete', $allowDelete);
                $content = [
                    'rows' => $view->render(),
                    'totalItems' => $totalDeleted
                ];
                break;
            case 'undoRecords':
                if (empty($this->conf['records']) || !is_array($this->conf['records'])) {
                    $content = [
                        'success' => false,
                        'message' => LocalizationUtility::translate('flashmessage.delete.norecordsselected', 'recycler')
                    ];
                    break;
                }

                /* @var DeletedRecords $model */
                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $affectedRecords = $model->undeleteData($this->conf['records'], $this->conf['recursive']);
                $messageKey = 'flashmessage.undo.' . ($affectedRecords !== false ? 'success' : 'failure') . '.' . ((int)$affectedRecords === 1 ? 'singular' : 'plural');
                $content = [
                    'success' => true,
                    'message' => sprintf(LocalizationUtility::translate($messageKey, 'recycler'), $affectedRecords)
                ];
                break;
            case 'deleteRecords':
                if (empty($this->conf['records']) || !is_array($this->conf['records'])) {
                    $content = [
                        'success' => false,
                        'message' => LocalizationUtility::translate('flashmessage.delete.norecordsselected', 'recycler')
                    ];
                    break;
                }

                /* @var DeletedRecords $model */
                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $success = $model->deleteData($this->conf['records']);
                $affectedRecords = count($this->conf['records']);
                $messageKey = 'flashmessage.delete.' . ($success ? 'success' : 'failure') . '.' . ($affectedRecords === 1 ? 'singular' : 'plural');
                $content = [
                    'success' => true,
                    'message' => sprintf(LocalizationUtility::translate($messageKey, 'recycler'), $affectedRecords)
                ];
                break;
        }
        return (new JsonResponse())->setPayload($content);
    }

    /**
     * Sets data in the session of the current backend user.
     *
     * @param array $data The data to be stored in the session
     */
    protected function setDataInSession(array $data)
    {
        $beUser = $this->getBackendUser();
        $recyclerUC = $beUser->uc['tx_recycler'] ?? [];
        if (!empty(array_diff_assoc($data, $recyclerUC))) {
            $beUser->uc['tx_recycler'] = array_merge($recyclerUC, $data);
            $beUser->writeUC();
        }
    }

    /**
     * Returns the BackendUser
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
