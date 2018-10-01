<?php
namespace TYPO3\CMS\Extbase\Mvc\Web;

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

/**
 * A request handler which can handle web requests invoked by the frontend.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class FrontendRequestHandler extends AbstractRequestHandler
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $extensionService;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\CacheHashEnforcer
     */
    protected $cacheHashEnforcer;

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
     */
    public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Web\CacheHashEnforcer $cacheHashEnforcer
     */
    public function injectCacheHashEnforcer(\TYPO3\CMS\Extbase\Mvc\Web\CacheHashEnforcer $cacheHashEnforcer)
    {
        $this->cacheHashEnforcer = $cacheHashEnforcer;
    }

    /**
     * Handles the web request. The response will automatically be sent to the client.
     *
     * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface|null
     */
    public function handleRequest()
    {
        $request = $this->requestBuilder->build();
        if ($this->extensionService->isActionCacheable(null, null, $request->getControllerName(), $request->getControllerActionName())) {
            $request->setIsCached(true);
        } else {
            $contentObject = $this->configurationManager->getContentObject();
            if ($contentObject->getUserObjectType() === \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER) {
                $contentObject->convertToUserIntObject();
                // \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::convertToUserIntObject() will recreate the object, so we have to stop the request here
                return null;
            }
            $request->setIsCached(false);
        }

        if ($this->configurationManager->isFeatureEnabled('requireCHashArgumentForActionArguments')) {
            $pluginNamespace = $this->extensionService->getPluginNamespace(
                $request->getControllerExtensionName(),
                $request->getPluginName()
            );
            $this->cacheHashEnforcer->enforceForRequest($request, $pluginNamespace);
        }

        /** @var \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response */
        $response = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Response::class);
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
}
