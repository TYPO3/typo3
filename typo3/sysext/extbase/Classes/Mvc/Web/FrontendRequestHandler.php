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

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * A request handler which can handle web requests invoked by the frontend.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class FrontendRequestHandler extends AbstractRequestHandler
{
    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Handles the web request. The response will automatically be sent to the client.
     *
     * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface|null
     */
    public function handleRequest()
    {
        $request = $this->requestBuilder->build();
        if ($this->isActionCacheable($request->getControllerObjectName(), $request->getControllerActionName())) {
            $request->setIsCached(true);
        } else {
            $contentObject = $this->configurationManager->getContentObject();
            if ($contentObject->getUserObjectType() === ContentObjectRenderer::OBJECTTYPE_USER) {
                $contentObject->convertToUserIntObject();
                // \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::convertToUserIntObject() will recreate the object, so we have to stop the request here
                return null;
            }
            $request->setIsCached(false);
        }

        if ($this->configurationManager->isFeatureEnabled('requireCHashArgumentForActionArguments')) {
            trigger_error('The option requireCHashArgumentForActionArguments is removed', E_USER_DEPRECATED);
        }

        /** @var \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response */
        $response = $this->objectManager->get(Response::class);
        $this->dispatcher->dispatch($request, $response);
        return $response;
    }

    /**
     * This request handler can handle any web request.
     *
     * @return bool If the request is a web request, TRUE otherwise FALSE
     */
    public function canHandleRequest()
    {
        return $this->environmentService->isEnvironmentInFrontendMode();
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
}
