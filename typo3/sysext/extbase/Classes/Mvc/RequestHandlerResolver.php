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

namespace TYPO3\CMS\Extbase\Mvc;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\RequestHandlersConfiguration;

/**
 * Analyzes the raw request and delivers a request handler which can handle it.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class RequestHandlerResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RequestHandlersConfiguration
     */
    private $requestHandlersConfiguration;

    public function __construct(ContainerInterface $container, RequestHandlersConfiguration $requestHandlersConfiguration)
    {
        $this->container = $container;
        $this->requestHandlersConfiguration = $requestHandlersConfiguration;
    }

    /**
     * Analyzes the raw request and tries to find a request handler which can handle
     * it. If none is found, an exception is thrown.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request A request
     * @return \TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface A request handler
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception
     */
    public function resolveRequestHandler(RequestInterface $request)
    {
        $suitableRequestHandlers = [];
        foreach ($this->requestHandlersConfiguration->getRegisteredRequestHandlers() as $requestHandlerClassName) {
            /** @var RequestHandlerInterface $requestHandler */
            $requestHandler = $this->container->has($requestHandlerClassName)
                ? $this->container->get($requestHandlerClassName)
                : GeneralUtility::makeInstance($requestHandlerClassName)
            ;
            if ($requestHandler->canHandleRequest($request)) {
                $priority = $requestHandler->getPriority();
                if (isset($suitableRequestHandlers[$priority])) {
                    throw new Exception('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
                }
                $suitableRequestHandlers[$priority] = $requestHandler;
            }
        }
        if (empty($suitableRequestHandlers)) {
            throw new Exception('No suitable request handler found.', 1205414233);
        }
        ksort($suitableRequestHandlers);
        return array_pop($suitableRequestHandlers);
    }
}
