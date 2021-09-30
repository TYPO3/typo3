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
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Creates a request and dispatches it to the controller which was specified
 * by TS Setup, flexForm and returns the content.
 *
 * This class is the main entry point for extbase extensions.
 */
class Bootstrap
{
    /**
     * @var array
     */
    public static $persistenceClasses = [];

    /**
     * Set by UserContentObject (USER) via setContentObjectRenderer() in frontend
     *
     * @var ContentObjectRenderer|null
     */
    protected ?ContentObjectRenderer $cObj = null;

    protected ContainerInterface $container;
    protected ConfigurationManagerInterface $configurationManager;
    protected PersistenceManagerInterface $persistenceManager;
    protected RequestHandlerResolver $requestHandlerResolver;
    protected CacheService $cacheService;
    protected Dispatcher $dispatcher;
    protected RequestBuilder $extbaseRequestBuilder;

    public function __construct(
        ContainerInterface $container,
        ConfigurationManagerInterface $configurationManager,
        PersistenceManagerInterface $persistenceManager,
        RequestHandlerResolver $requestHandlerResolver,
        CacheService $cacheService,
        Dispatcher $dispatcher,
        RequestBuilder $extbaseRequestBuilder
    ) {
        $this->container = $container;
        $this->configurationManager = $configurationManager;
        $this->persistenceManager = $persistenceManager;
        $this->requestHandlerResolver = $requestHandlerResolver;
        $this->cacheService = $cacheService;
        $this->dispatcher = $dispatcher;
        $this->extbaseRequestBuilder = $extbaseRequestBuilder;
    }

    /**
     * Called for frontend plugins from UserContentObject via ContentObjectRenderer->callUserFunction().
     *
     * @param ContentObjectRenderer $cObj
     */
    public function setContentObjectRenderer(ContentObjectRenderer $cObj)
    {
        $this->cObj = $cObj;
    }

    /**
     * Explicitly initializes all necessary Extbase objects by invoking the various initialize* methods.
     *
     * Usually this method is only called from unit tests or other applications which need a more fine grained control over
     * the initialization and request handling process. Most other applications just call the run() method.
     *
     * @param array $configuration The TS configuration array
     * @throws \RuntimeException
     * @see run()
     */
    public function initialize(array $configuration): void
    {
        if (!Environment::isCli()) {
            if (!isset($configuration['extensionName']) || $configuration['extensionName'] === '') {
                throw new \RuntimeException('Invalid configuration: "extensionName" is not set', 1290623020);
            }
            if (!isset($configuration['pluginName']) || $configuration['pluginName'] === '') {
                throw new \RuntimeException('Invalid configuration: "pluginName" is not set', 1290623027);
            }
        }
        $this->initializeConfiguration($configuration);
    }

    /**
     * Initializes the Object framework.
     *
     * @param array $configuration
     * @see initialize()
     * @internal
     */
    public function initializeConfiguration(array $configuration): void
    {
        $this->cObj ??= $this->container->get(ContentObjectRenderer::class);
        $this->configurationManager->setContentObject($this->cObj);
        $this->configurationManager->setConfiguration($configuration);
        // todo: Shouldn't the configuration manager object – which is a singleton – be stateless?
        // todo: At this point we give the configuration manager a state, while we could directly pass the
        // todo: configuration (i.e. controllerName, actionName and such), directly to the request
        // todo: handler, which then creates stateful request objects.
        // todo: Once this has changed, \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder::loadDefaultValues does not need
        // todo: to fetch this configuration from the configuration manager.
    }

    /**
     * Runs the the Extbase Framework by resolving an appropriate Request Handler and passing control to it.
     * If the Framework is not initialized yet, it will be initialized.
     *
     * This is usually used in Frontend plugins.
     *
     * @param string $content The content. Not used
     * @param array $configuration The TS configuration array
     * @return string $content The processed content
     */
    public function run(string $content, array $configuration, ?ServerRequestInterface $request = null): string
    {
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'];
        $this->initialize($configuration);
        return $this->handleFrontendRequest($request);
    }

    protected function handleFrontendRequest(ServerRequestInterface $request): string
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
        $requestHandler = $this->requestHandlerResolver->resolveRequestHandler($extbaseRequest);
        $response = $requestHandler->handleRequest($extbaseRequest);
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

        // In case TSFE is available and this is a json response, we have
        // to take the TypoScript settings regarding charset into account.
        // @todo Since HTML5 only utf-8 is a valid charset, this settings should be deprecated
        if (($typoScriptFrontendController = ($GLOBALS['TSFE'] ?? null)) instanceof TypoScriptFrontendController
            && strpos($response->getHeaderLine('Content-Type'), 'application/json') === 0
        ) {
            // Unset the already defined Content-Type
            $response = $response->withoutHeader('Content-Type');
            if (empty($typoScriptFrontendController->config['config']['disableCharsetHeader'])) {
                // If the charset header is *not* disabled in configuration,
                // TypoScriptFrontendController will send the header later with the Content-Type which we set here.
                $typoScriptFrontendController->setContentType('application/json');
            } else {
                // Although the charset header is disabled in configuration, we *must* send a Content-Type header here.
                // Content-Type headers optionally carry charset information at the same time.
                // Since we have the information about the charset, there is no reason to not include the charset information although disabled in TypoScript.
                $response = $response->withHeader('Content-Type', 'application/json; charset=' . trim($typoScriptFrontendController->metaCharset));
            }
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
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @internal
     */
    public function handleBackendRequest(ServerRequestInterface $request): ResponseInterface
    {
        // build the configuration from the Server request / route
        /** @var Route $route */
        $route = $request->getAttribute('route');
        $moduleConfiguration = $route->getOption('moduleConfiguration');
        $configuration = [
            'extensionName' => $moduleConfiguration['extensionName'],
            'pluginName' => $route->getOption('moduleName'),
        ];

        $this->initialize($configuration);
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
