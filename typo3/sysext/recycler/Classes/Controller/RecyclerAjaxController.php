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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recycler\Domain\Model\DeletedRecords;
use TYPO3\CMS\Recycler\Domain\Model\Tables;

/**
 * Controller class for the 'recycler' extension. Handles the AJAX Requests
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
     * The constructor of this class
     */
    public function __construct()
    {
        // Configuration, variable assignment
        $this->conf['action'] = GeneralUtility::_GP('action');
        $this->conf['table'] = GeneralUtility::_GP('table') ? GeneralUtility::_GP('table') : '';
        $this->conf['limit'] = GeneralUtility::_GP('limit') ? (int)GeneralUtility::_GP('limit') : 25;
        $this->conf['start'] = GeneralUtility::_GP('start') ? (int)GeneralUtility::_GP('start') : 0;
        $this->conf['filterTxt'] = GeneralUtility::_GP('filterTxt') ? GeneralUtility::_GP('filterTxt') : '';
        $this->conf['startUid'] = GeneralUtility::_GP('startUid') ? (int)GeneralUtility::_GP('startUid') : 0;
        $this->conf['depth'] = GeneralUtility::_GP('depth') ? (int)GeneralUtility::_GP('depth') : 0;
        $this->conf['records'] = GeneralUtility::_GP('records') ? GeneralUtility::_GP('records') : null;
        $this->conf['recursive'] = GeneralUtility::_GP('recursive') ? (bool)(int)GeneralUtility::_GP('recursive') : false;
    }

    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $extPath = ExtensionManagementUtility::extPath('recycler');
        /* @var $view StandaloneView */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setPartialRootPaths(['default' => $extPath . 'Resources/Private/Partials']);

        $content = '';
        // Determine the scripts to execute
        switch ($this->conf['action']) {
            case 'getTables':
                $this->setDataInSession('depthSelection', $this->conf['depth']);

                /* @var $model Tables */
                $model = GeneralUtility::makeInstance(Tables::class);
                $content = $model->getTables($this->conf['startUid'], $this->conf['depth']);
                break;
            case 'getDeletedRecords':
                $this->setDataInSession('tableSelection', $this->conf['table']);
                $this->setDataInSession('depthSelection', $this->conf['depth']);
                $this->setDataInSession('resultLimit', $this->conf['limit']);

                /* @var $model DeletedRecords */
                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $model->loadData($this->conf['startUid'], $this->conf['table'], $this->conf['depth'], $this->conf['start'] . ',' . $this->conf['limit'], $this->conf['filterTxt']);
                $deletedRowsArray = $model->getDeletedRows();

                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $totalDeleted = $model->getTotalCount($this->conf['startUid'], $this->conf['table'], $this->conf['depth'], $this->conf['filterTxt']);

                /* @var $controller DeletedRecordsController */
                $controller = GeneralUtility::makeInstance(DeletedRecordsController::class);
                $recordsArray = $controller->transform($deletedRowsArray, $totalDeleted);

                $modTS = $this->getBackendUser()->getTSConfig('mod.recycler');
                $allowDelete = (bool)$this->getBackendUser()->user['admin'] ? true : (bool)$modTS['properties']['allowDelete'];

                $view->setTemplatePathAndFilename($extPath . 'Resources/Private/Templates/Ajax/RecordsTable.html');
                $view->assign('records', $recordsArray['rows']);
                $view->assign('allowDelete', $allowDelete);
                $view->assign('total', $recordsArray['total']);
                $content = [
                    'rows' => $view->render(),
                    'totalItems' => $recordsArray['total']
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

                /* @var $model DeletedRecords */
                $model = GeneralUtility::makeInstance(DeletedRecords::class);
                $success = $model->undeleteData($this->conf['records'], $this->conf['recursive']);
                $affectedRecords = count($this->conf['records']);
                $messageKey = 'flashmessage.undo.' . ($success ? 'success' : 'failure') . '.' . ($affectedRecords === 1 ? 'singular' : 'plural');
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

                /* @var $model DeletedRecords */
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
        $response->getBody()->write(json_encode($content));
        return $response;
    }

    /**
     * Sets data in the session of the current backend user.
     *
     * @param string $identifier The identifier to be used to set the data
     * @param string $data The data to be stored in the session
     * @return void
     */
    protected function setDataInSession($identifier, $data)
    {
        $beUser = $this->getBackendUser();
        $beUser->uc['tx_recycler'][$identifier] = $data;
        $beUser->writeUC();
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
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
