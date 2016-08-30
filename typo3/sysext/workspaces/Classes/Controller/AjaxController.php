<?php
namespace TYPO3\CMS\Workspaces\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Implements the AJAX functionality for the various asynchronous calls
 */
class AjaxController
{
    /**
     * Sets the TYPO3 Backend context to a certain workspace,
     * called by the Backend toolbar menu
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function switchWorkspaceAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $workspaceId = (int)(isset($parsedBody['workspaceId']) ? $parsedBody['workspaceId'] : $queryParams['workspaceId']);
        $pageId = (int)(isset($parsedBody['pageId']) ? $parsedBody['pageId'] : $queryParams['pageId']);
        $finalPageUid = 0;
        $originalPageId = $pageId;

        $this->getBackendUser()->setWorkspace($workspaceId);

        while ($pageId) {
            $page = BackendUtility::getRecordWSOL('pages', $pageId, '*',
                ' AND pages.t3ver_wsid IN (0, ' . $workspaceId . ')');
            if ($page) {
                if ($this->getBackendUser()->doesUserHaveAccess($page, 1)) {
                    break;
                }
            } else {
                $page = BackendUtility::getRecord('pages', $pageId);
            }
            $pageId = $page['pid'];
        }

        if (isset($page['uid'])) {
            $finalPageUid = (int)$page['uid'];
        }

        $ajaxResponse = [
            'title'       => \TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($workspaceId),
            'workspaceId' => $workspaceId,
            'pageId'      => ($finalPageUid && $originalPageId == $finalPageUid) ? null : $finalPageUid
        ];
        $response->getBody()->write(json_encode($ajaxResponse));
        return $response;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
