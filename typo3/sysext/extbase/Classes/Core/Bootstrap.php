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

namespace TYPO3\CMS\Extbase\Core;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Creates a request and dispatches it to the controller which was specified
 * by TS Setup and returns the content.
 *
 * This class is the main entry point for extbase extensions.
 *
 * @todo: Please note that this class will become internal in TYPO3 v13.0
 */
class Bootstrap
{
    /**
     * Set by UserContentObject (USER) via setContentObjectRenderer() in frontend
     */
    protected ?ContentObjectRenderer $cObj = null;

    protected ContainerInterface $container;
    protected ConfigurationManagerInterface $configurationManager;
    protected PersistenceManagerInterface $persistenceManager;
    protected CacheService $cacheService;
    protected Dispatcher $dispatcher;
    protected RequestBuilder $extbaseRequestBuilder;

    public function __construct(
        ContainerInterface $container,
        ConfigurationManagerInterface $configurationManager,
        PersistenceManagerInterface $persistenceManager,
        CacheService $cacheService,
        Dispatcher $dispatcher,
        RequestBuilder $extbaseRequestBuilder
    ) {
        $this->container = $container;
        $this->configurationManager = $configurationManager;
        $this->persistenceManager = $persistenceManager;
        $this->cacheService = $cacheService;
        $this->dispatcher = $dispatcher;
        $this->extbaseRequestBuilder = $extbaseRequestBuilder;
    }

    /**
     * Called for frontend plugins from UserContentObject via ContentObjectRenderer->callUserFunction().
     */
    public function setContentObjectRenderer(ContentObjectRenderer $cObj)
    {
        $this->cObj = $cObj;
    }

    /**
     * Explicitly initializes all necessary Extbase objects by invoking the various initialize* methods.
     *
     * Usually this method is only called from unit tests or other applications which need a more fine-grained control over
     * the initialization and request handling process. Most other applications just call the run() method.
     *
     * @param array $configuration The TS configuration array
     * @throws \RuntimeException
     * @see run()
     */
    public function initialize(array $configuration, ServerRequestInterface $request): ServerRequestInterface
    {
        if (!Environment::isCli()) {
            if (!isset($configuration['extensionName']) || $configuration['extensionName'] === '') {
                throw new \RuntimeException('Invalid configuration: "extensionName" is not set', 1290623020);
            }
            if (!isset($configuration['pluginName']) || $configuration['pluginName'] === '') {
                throw new \RuntimeException('Invalid configuration: "pluginName" is not set', 1290623027);
            }
        }
        return $this->initializeConfiguration($configuration, $request);
    }

    /**
     * Initializes the Object framework.
     *
     * @see initialize()
     * @internal
     */
    public function initializeConfiguration(array $configuration, ServerRequestInterface $request): ServerRequestInterface
    {
        if ($this->cObj === null) {
            $this->cObj = $this->container->get(ContentObjectRenderer::class);
            $this->cObj->setRequest($request);
        }
        // @deprecated since v12. Remove in v13.
        $this->configurationManager->setContentObject($this->cObj);
        if (method_exists($this->configurationManager, 'setRequest')) {
            // @todo: Avoid method_exists() when setRequest() has been added to interface.
            $this->configurationManager->setRequest($request);
        }
        $this->configurationManager->setConfiguration($configuration);
        return $request;
        // todo: Outdated todo, recheck in v13.
        //       Shouldn't the configuration manager object – which is a singleton – be stateless?
        //       At this point we give the configuration manager a state, while we could directly pass the
        //       configuration (i.e. controllerName, actionName and such), directly to the request
        //       handler, which then creates stateful request objects.
        //       Once this has changed, \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder::loadDefaultValues does not need
        //       to fetch this configuration from the configuration manager.
    }

    /**
     * Runs the Extbase Framework by resolving an appropriate Request Handler and passing control to it.
     * If the Framework is not initialized yet, it will be initialized.
     *
     * This is usually used in Frontend plugins.
     * This method will be marked as internal in the future, use EXTBASEPLUGIN in TypoScript to execute a Extbase plugin
     * instead.
     *
     * @param string $content The content. Not used
     * @param array $configuration The TS configuration array
     * @param ServerRequestInterface $request the incoming server request
     * @return string $content The processed content
     */
    public function run(string $content, array $configuration, ServerRequestInterface $request): string
    {
        $request = $this->initialize($configuration, $request);
        return $this->handleFrontendRequest($request);
    }

    /**
     * Used for any Extbase Plugin in the Frontend, be sure to run $this->initialize() before.
     *
     * @internal
     */
    public function handleFrontendRequest(ServerRequestInterface $request): string
    {
        $extbaseRequest = $this->extbaseRequestBuilder->build($request);
        if (!$this->isExtbaseRequestCacheable($extbaseRequest)) {
            if ($this->cObj->getUserObjectType() === ContentObjectRenderer::OBJECTTYPE_USER) {
                // ContentObjectRenderer::convertToUserIntObject() will recreate the object,
                // so we have to stop the request here before the action is actually called
                $this->cObj->convertToUserIntObject();
                return '';
            }
        }

        // Dispatch the extbase request
        $response = $this->dispatcher->dispatch($extbaseRequest);
        if ($response->getStatusCode() >= 300) {
            // Avoid caching the plugin when we issue a redirect or error response
            // This means that even when an action is configured as cachable
            // we avoid the plugin to be cached, but keep the page cache untouched
            if ($this->cObj->getUserObjectType() === ContentObjectRenderer::OBJECTTYPE_USER) {
                $this->cObj->convertToUserIntObject();
            }
        }
        // Usually coming from an error action, ensure all caches are cleared
        if ($response->getStatusCode() === 400) {
            $this->clearCacheOnError();
        }

        // In case TSFE is available and this is a json response, we have to let TSFE know we have a specific Content-Type
        if (($typoScriptFrontendController = ($GLOBALS['TSFE'] ?? null)) instanceof TypoScriptFrontendController
            && $response->hasHeader('Content-Type')
        ) {
            $typoScriptFrontendController->setContentType($response->getHeaderLine('Content-Type'));
            // Do not send the header directly (see below)
            $response = $response->withoutHeader('Content-Type');
        }

        if (headers_sent() === false) {
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value));
                }
            }

            // Set status code from extbase response
            // @todo: Remove when ContentObjectRenderer is response aware
            if ($response->getStatusCode() >= 300) {
                header('HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());
            }
        }
        $body = $response->getBody();
        $body->rewind();
        $content = $body->getContents();
        $this->resetSingletons();
        $this->cacheService->clearCachesOfRegisteredPageIds();
        return $content;
    }

    /**
     * Entrypoint for backend modules, handling PSR-7 requests/responses.
     *
     * Creates an Extbase Request, dispatches it and then returns the Response
     *
     * @internal
     */
    public function handleBackendRequest(ServerRequestInterface $request): ResponseInterface
    {
        // build the configuration from the module, included in the current request
        $module = $request->getAttribute('module');
        $configuration = [
            'extensionName' => $module?->getExtensionName(),
            'pluginName' => $module?->getIdentifier(),
        ];

        $request = $this->initialize($configuration, $request);
        $extbaseRequest = $this->extbaseRequestBuilder->build($request);
        $response = $this->dispatcher->dispatch($extbaseRequest);
        $this->resetSingletons();
        $this->cacheService->clearCachesOfRegisteredPageIds();
        return $response;
    }

    /**
     * Clear cache of current page on error. Needed because we want a re-evaluation of the data.
     */
    protected function clearCacheOnError(): void
    {
        $extbaseSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (isset($extbaseSettings['persistence']['enableAutomaticCacheClearing']) && $extbaseSettings['persistence']['enableAutomaticCacheClearing'] === '1') {
            if (isset($GLOBALS['TSFE'])) {
                $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);
            }
        }
    }

    /**
     * Resets global singletons for the next plugin
     */
    protected function resetSingletons(): void
    {
        $this->persistenceManager->persistAll();
    }

    protected function isExtbaseRequestCacheable(RequestInterface $extbaseRequest): bool
    {
        $controllerClassName = $extbaseRequest->getControllerObjectName();
        $actionName = $extbaseRequest->getControllerActionName();
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $nonCacheableActions = $frameworkConfiguration['controllerConfiguration'][$controllerClassName]['nonCacheableActions'] ?? null;
        if (!is_array($nonCacheableActions)) {
            return true;
        }
        return !in_array($actionName, $nonCacheableActions, true);
    }
}
