<?php
namespace TYPO3\CMS\Extbase\Mvc;

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

use TYPO3\CMS\Extbase\Configuration\RequestHandlersConfigurationFactory;

/**
 * Analyzes the raw request and delivers a request handler which can handle it.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class RequestHandlerResolver
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\RequestHandlersConfiguration
     */
    private $requestHandlersConfiguration;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param RequestHandlersConfigurationFactory $requestHandlersConfigurationFactory
     */
    public function __construct(RequestHandlersConfigurationFactory $requestHandlersConfigurationFactory)
    {
        $this->requestHandlersConfiguration = $requestHandlersConfigurationFactory->createRequestHandlersConfiguration();
    }

    /**
     * Analyzes the raw request and tries to find a request handler which can handle
     * it. If none is found, an exception is thrown.
     *
     * @return \TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface A request handler
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception
     */
    public function resolveRequestHandler()
    {
        $suitableRequestHandlers = [];
        foreach ($this->requestHandlersConfiguration->getRegisteredRequestHandlers() as $requestHandlerClassName) {
            /** @var RequestHandlerInterface $requestHandler */
            $requestHandler = $this->objectManager->get($requestHandlerClassName);
            if ($requestHandler->canHandleRequest()) {
                $priority = $requestHandler->getPriority();
                if (isset($suitableRequestHandlers[$priority])) {
                    throw new \TYPO3\CMS\Extbase\Mvc\Exception('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
                }
                $suitableRequestHandlers[$priority] = $requestHandler;
            }
        }
        if (empty($suitableRequestHandlers)) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception('No suitable request handler found.', 1205414233);
        }
        ksort($suitableRequestHandlers);
        return array_pop($suitableRequestHandlers);
    }
}
