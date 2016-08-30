<?php
namespace TYPO3\CMS\Taskcenter;

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

/**
 * Status of tasks
 */
class TaskStatus
{
    /**
     * Saves the section toggle state of tasks in the backend user's uc
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function saveCollapseState(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Remove 'el_' in the beginning which is needed for the saveSortingState()
        $item = isset($request->getParsedBody()['item']) ? $request->getParsedBody()['item'] : $request->getQueryParams()['item'];
        $item = substr(htmlspecialchars($item), 3);
        $state = (bool)(isset($request->getParsedBody()['state']) ? $request->getParsedBody()['state'] : $request->getQueryParams()['state']);

        $this->getBackendUserAuthentication()->uc['taskcenter']['states'][$item] = $state;
        $this->getBackendUserAuthentication()->writeUC();

        return $response;
    }

    /**
     * Saves the sorting order of tasks in the backend user's uc
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function saveSortingState(ServerRequestInterface $request, ResponseInterface $response)
    {
        $sort = [];
        $data = isset($request->getParsedBody()['data']) ? $request->getParsedBody()['data'] : $request->getQueryParams()['data'];

        $items = explode('&', $data);
        foreach ($items as $item) {
            $sort[] = substr($item, 12);
        }
        $this->getBackendUserAuthentication()->uc['taskcenter']['sorting'] = serialize($sort);
        $this->getBackendUserAuthentication()->writeUC();

        return $response;
    }

    /**
     * Returns BackendUserAuthentication
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
