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

namespace TYPO3\CMS\Extbase\Mvc\Web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * A request handler which can handle web requests invoked by the frontend.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class FrontendRequestHandler implements RequestHandlerInterface
{
    protected Dispatcher $dispatcher;
    protected ConfigurationManagerInterface $configurationManager;

    public function __construct(
        Dispatcher $dispatcher,
        ConfigurationManagerInterface $configurationManager
    ) {
        $this->dispatcher = $dispatcher;
        $this->configurationManager = $configurationManager;
    }

    /**
     * Handles the web request. The response will automatically be sent to the client.
     *
     * @param RequestInterface $request
     * @return ResponseInterface|null
     * @throws InfiniteLoopException
     */
    public function handleRequest(RequestInterface $request)
    {
        if ($this->isActionCacheable($request->getControllerObjectName(), $request->getControllerActionName())) {
            $request->setIsCached(true);
        } else {
            $contentObject = $this->configurationManager->getContentObject();
            if ($contentObject->getUserObjectType() === ContentObjectRenderer::OBJECTTYPE_USER) {
                $contentObject->convertToUserIntObject();
                // \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::convertToUserIntObject() will recreate the object, so we have to stop the request here
                return null; // todo: Instead of returning null, throw an Exception instead and harden the interface.
            }
            $request->setIsCached(false);
        }

        return $this->dispatcher->dispatch($request);
    }

    /**
     * This request handler can handle any web request.
     *
     * @param RequestInterface $request
     * @return bool If the request is a web request, TRUE otherwise FALSE
     */
    public function canHandleRequest(RequestInterface $request)
    {
        // @todo: Use $request when extbase hands over PSR-7 compatible requests
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
    }

    protected function isActionCacheable(string $controllerClassName, string $actionName): bool
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        $nonCacheableActions = $frameworkConfiguration['controllerConfiguration'][$controllerClassName]['nonCacheableActions'] ?? null;

        if (!is_array($nonCacheableActions)) {
            return true;
        }

        return !in_array($actionName, $frameworkConfiguration['controllerConfiguration'][$controllerClassName]['nonCacheableActions'], true);
    }

    public function getPriority(): int
    {
        return 100;
    }
}
