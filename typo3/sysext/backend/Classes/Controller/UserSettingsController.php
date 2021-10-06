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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\BackendUserConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A wrapper class to call BE_USER->uc
 * used for AJAX and Storage/Persistent JS object
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class UserSettingsController
{
    private const ALLOWED_ACTIONS = [
        'GET' => ['get', 'getAll'],
        'POST' => ['set', 'addToList', 'removeFromList', 'unset', 'clear'],
    ];

    /**
     * Processes all AJAX calls and returns a JSON for the data
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function processAjaxRequest(ServerRequestInterface $request): ResponseInterface
    {
        // do the regular / main logic, depending on the action parameter
        $action = $this->getValidActionFromRequest($request);

        $key = $request->getParsedBody()['key'] ?? $request->getQueryParams()['key'] ?? '';
        $value = $request->getParsedBody()['value'] ?? $request->getQueryParams()['value'] ?? '';
        $backendUserConfiguration = GeneralUtility::makeInstance(BackendUserConfiguration::class);
        switch ($action) {
            case 'get':
                $content = $backendUserConfiguration->get($key);
                break;
            case 'getAll':
                $content = $backendUserConfiguration->getAll();
                break;
            case 'set':
                $backendUserConfiguration->set($key, $value);
                $content = $backendUserConfiguration->getAll();
                break;
            case 'addToList':
                $backendUserConfiguration->addToList($key, $value);
                $content = $backendUserConfiguration->getAll();
                break;
            case 'removeFromList':
                $backendUserConfiguration->removeFromList($key, $value);
                $content = $backendUserConfiguration->getAll();
                break;
            case 'unset':
                $backendUserConfiguration->unsetOption($key);
                $content = $backendUserConfiguration->getAll();
                break;
            case 'clear':
                $backendUserConfiguration->clear();
                $content = ['result' => true];
                break;
            default:
                $content = ['result' => false];
        }
        return new JsonResponse($content);
    }

    protected function getValidActionFromRequest(ServerRequestInterface $request): string
    {
        $action = $request->getParsedBody()['action'] ?? $request->getQueryParams()['action'] ?? '';
        return in_array($action, (self::ALLOWED_ACTIONS[$request->getMethod()] ?? []), true) ? $action : '';
    }
}
