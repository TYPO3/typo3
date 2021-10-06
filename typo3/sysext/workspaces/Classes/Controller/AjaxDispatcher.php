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

namespace TYPO3\CMS\Workspaces\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Controller\Remote\ActionHandler;
use TYPO3\CMS\Workspaces\Controller\Remote\MassActionHandler;
use TYPO3\CMS\Workspaces\Controller\Remote\RemoteServer;

/**
 * Implements the AJAX functionality for the various asynchronous calls
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class AjaxDispatcher
{
    /**
     * @var array
     */
    protected $classMap = [
        'RemoteServer' => RemoteServer::class,
        'MassActions' => MassActionHandler::class,
        'Actions' => ActionHandler::class,
    ];

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
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
        $tmp->tid = $call->tid;
        $tmp->type = $call->type;
        return $tmp;
    }
}
