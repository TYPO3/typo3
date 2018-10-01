<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Backend\Configuration\BackendUserConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A wrapper class to call BE_USER->uc
 * used for AJAX and TYPO3.Storage JS object
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class UserSettingsController
{
    /**
     * @var BackendUserConfiguration
     */
    protected $backendUserConfiguration;

    /**
     * Initializes the backendUserConfiguration
     */
    public function __construct()
    {
        $this->backendUserConfiguration = GeneralUtility::makeInstance(BackendUserConfiguration::class);
    }

    /**
     * Processes all AJAX calls and returns a JSON for the data
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function processAjaxRequest(ServerRequestInterface $request): ResponseInterface
    {
        // do the regular / main logic, depending on the action parameter
        $action = $request->getParsedBody()['action'] ?? $request->getQueryParams()['action'] ?? '';
        $key = $request->getParsedBody()['key'] ?? $request->getQueryParams()['key'] ?? '';
        $value = $request->getParsedBody()['value'] ?? $request->getQueryParams()['value'] ?? '';
        $data = $this->processRequest($action, $key, $value);

        return (new JsonResponse())->setPayload($data);
    }

    /**
     * Process data
     *
     * @param string $action
     * @param string $key
     * @param string $value
     * @return mixed
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function process($action, $key = '', $value = '')
    {
        trigger_error('UserSettingsController->process() will be replaced by protected method processRequest() in TYPO3 v10.0. Do not call from other extensions.', E_USER_DEPRECATED);
        return $this->processRequest($action, $key, $value);
    }

    /**
     * Process data
     *
     * @param string $action
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function processRequest(string $action, string $key = '', $value = '')
    {
        switch ($action) {
            case 'get':
                $content = $this->backendUserConfiguration->get($key);
                break;
            case 'getAll':
                $content = $this->backendUserConfiguration->getAll();
                break;
            case 'set':
                $this->backendUserConfiguration->set($key, $value);
                $content = $this->backendUserConfiguration->getAll();
                break;
            case 'addToList':
                $this->backendUserConfiguration->addToList($key, $value);
                $content = $this->backendUserConfiguration->getAll();
                break;
            case 'removeFromList':
                $this->backendUserConfiguration->removeFromList($key, $value);
                $content = $this->backendUserConfiguration->getAll();
                break;
            case 'unset':
                $this->backendUserConfiguration->unsetOption($key);
                $content = $this->backendUserConfiguration->getAll();
                break;
            case 'clear':
                $this->backendUserConfiguration->clear();
                $content = ['result' => true];
                break;
            default:
                $content = ['result' => false];
        }

        return $content;
    }
}
