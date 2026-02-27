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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Recycler\Domain\Model\DeletedRecords;
use TYPO3\CMS\Recycler\Service\RecyclerService;

/**
 * Controller class for the 'recycler' extension. Handles the AJAX requests.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class RecyclerAjaxController
{
    public function __construct(
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly RecyclerService $recyclerService,
    ) {}

    public function getTablesAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $startUid = (int)($queryParams['startUid'] ?? 0);
        $depth = (int)($queryParams['depth'] ?? 0);

        return new JsonResponse($this->recyclerService->getAvailableTables($startUid, $depth));
    }

    public function getDeletedRecordsAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $table = (string)($queryParams['table'] ?? '');
        $itemsPerPage = MathUtility::forceIntegerInRange(
            (int)($this->getBackendUser()->getTSConfig()['mod.']['recycler.']['recordsPageLimit'] ?? 25),
            1
        );
        $start = (int)($queryParams['start'] ?? 0);
        $currentPage = (int)floor($start / $itemsPerPage) + 1;
        $filterTxt = (string)($queryParams['filterTxt'] ?? '');
        $startUid = (int)($queryParams['startUid'] ?? 0);
        $depth = (int)($queryParams['depth'] ?? 0);

        $result = $this->recyclerService->getDeletedRecords($startUid, $table, $depth, $filterTxt, $currentPage, $itemsPerPage);

        $view = $this->backendViewFactory->create($request);
        $view->assign('showTableHeader', empty($table));
        $view->assign('showTableName', $this->getBackendUser()->shallDisplayDebugInformation());
        $view->assign('allowDelete', $this->isDeleteAllowed());
        $view->assign('groupedRecords', $result['groupedRecords']);

        return new JsonResponse([
            'rows' => $view->render('Ajax/RecordsTable'),
            'totalItems' => $result['totalItems'],
        ]);
    }

    public function undoRecordsAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $records = $parsedBody['records'] ?? null;
        $recursive = (bool)($parsedBody['recursive'] ?? false);

        if (empty($records) || !is_array($records)) {
            return new JsonResponse([
                'success' => false,
                'message' => LocalizationUtility::translate('flashmessage.delete.norecordsselected', 'recycler'),
            ]);
        }

        $model = GeneralUtility::makeInstance(DeletedRecords::class);
        $affectedRecords = $model->undeleteData($records, $recursive);
        $messageKey = 'flashmessage.undo.' . ($affectedRecords !== false ? 'success' : 'failure') . '.' . ((int)$affectedRecords === 1 ? 'singular' : 'plural');

        return new JsonResponse([
            'success' => true,
            'message' => sprintf((string)LocalizationUtility::translate($messageKey, 'recycler'), $affectedRecords),
        ]);
    }

    public function deleteRecordsAction(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isDeleteAllowed()) {
            return new JsonResponse([
                'success' => false,
                'message' => LocalizationUtility::translate('flashmessage.delete.unauthorized', 'recycler'),
            ]);
        }

        $parsedBody = $request->getParsedBody();
        $records = $parsedBody['records'] ?? null;

        if (empty($records) || !is_array($records)) {
            return new JsonResponse([
                'success' => false,
                'message' => LocalizationUtility::translate('flashmessage.delete.norecordsselected', 'recycler'),
            ]);
        }

        $model = GeneralUtility::makeInstance(DeletedRecords::class);
        $affectedRecords = $model->deleteData($records);
        $success = $affectedRecords > 0;
        $messageKey = 'flashmessage.delete.' . ($success ? 'success' : 'failure') . '.' . ($affectedRecords === 1 ? 'singular' : 'plural');

        return new JsonResponse([
            'success' => $success,
            'message' => sprintf((string)LocalizationUtility::translate($messageKey, 'recycler'), $affectedRecords),
        ]);
    }

    protected function isDeleteAllowed(): bool
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return true;
        }

        return (bool)($backendUser->getTSConfig()['mod.']['recycler.']['allowDelete'] ?? false);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
