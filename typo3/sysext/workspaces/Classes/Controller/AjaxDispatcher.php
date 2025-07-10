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

namespace TYPO3\CMS\Workspaces\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Controller\Remote\ActionHandler;
use TYPO3\CMS\Workspaces\Controller\Remote\MassActionHandler;
use TYPO3\CMS\Workspaces\Controller\Remote\RemoteServer;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceRepository;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceStageRepository;

/**
 * Implements the AJAX functionality for the various asynchronous calls.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class AjaxDispatcher
{
    protected array $classMap = [
        'RemoteServer' => RemoteServer::class,
        'MassActions' => MassActionHandler::class,
        'Actions' => ActionHandler::class,
    ];

    public function __construct(
        protected RemoteServer $remoteServer,
        protected WorkspaceRepository $workspaceRepository,
        protected WorkspaceStageRepository $workspaceStageRepository,
    ) {}

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $callStack = json_decode($request->getBody()->getContents());
        if (!is_array($callStack)) {
            $callStack = [$callStack];
        }
        $results = [];
        foreach ($callStack as $call) {
            $className = $this->classMap[$call->action];
            $method = $call->method;
            $parameters = $call->data;
            if (($parameters[1] ?? null) === null) {
                // Hack to have $request as second argument.
                unset($parameters[1]);
            }
            $parameters[] = $request;
            if ($method === 'getRowDetails') {
                $results[] = $this->buildResultFromResponse($this->getRowDetails($call->data[0]), $call);
                continue;
            }
            $instance = GeneralUtility::makeInstance($className);
            $results[] = $this->buildResultFromResponse($instance->$method(...$parameters), $call);
        }
        return new JsonResponse($results);
    }

    /**
     * @param mixed $responseFromMethod
     * @param \stdClass $call
     *
     * @return \stdClass
     */
    protected function buildResultFromResponse($responseFromMethod, $call)
    {
        $tmp = new \stdClass();
        $tmp->action = $call->action;
        $tmp->method = $call->method;
        $tmp->result = $responseFromMethod;
        return $tmp;
    }

    private function getRowDetails(\stdClass $parameters): array
    {
        $backendUser = $this->getBackendUser();
        $workspaceRecord = $this->workspaceRepository->findByUid($backendUser->workspace);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        return $this->remoteServer->getRowDetails($stages, $parameters);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
