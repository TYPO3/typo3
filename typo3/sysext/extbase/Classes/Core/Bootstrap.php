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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\RequestHandlersConfigurationFactory;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Extbase\Persistence\ClassesConfigurationFactory;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
     * Back reference to the parent content object
     * This has to be public as it is set directly from TYPO3
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver
     */
    protected $requestHandlerResolver;

    /**
     * @var \TYPO3\CMS\Extbase\Service\CacheService
     */
    protected $cacheService;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder
     */
    protected $extbaseRequestBuilder;

    public function __construct(
        ContainerInterface $container,
        ConfigurationManagerInterface $configurationManager,
        PersistenceManagerInterface $persistenceManager,
        RequestHandlerResolver $requestHandlerResolver,
        CacheService $cacheService,
        RequestBuilder $extbaseRequestBuilder
    ) {
        $this->container = $container;
        $this->configurationManager = $configurationManager;
        $this->persistenceManager = $persistenceManager;
        $this->requestHandlerResolver = $requestHandlerResolver;
        $this->cacheService = $cacheService;
        $this->extbaseRequestBuilder = $extbaseRequestBuilder;
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
        $this->initializePersistenceClassesConfiguration();
        $this->initializeRequestHandlersConfiguration();
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
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject */
        $contentObject = $this->cObj ?? $this->container->get(ContentObjectRenderer::class);
        $this->configurationManager->setContentObject($contentObject);
        $this->configurationManager->setConfiguration($configuration);
        // todo: Shouldn't the configuration manager object – which is a singleton – be stateless?
        // todo: At this point we give the configuration manager a state, while we could directly pass the
        // todo: configuration (i.e. controllerName, actionName and such), directly to the request
        // todo: handler, which then creates stateful request objects.
        // todo: Once this has changed, \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder::loadDefaultValues does not need
        // todo: to fetch this configuration from the configuration manager.
    }

    /**
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    private function initializePersistenceClassesConfiguration(): void
    {
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        GeneralUtility::makeInstance(ClassesConfigurationFactory::class, $cacheManager)
            ->createClassesConfiguration();
    }

    /**
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    private function initializeRequestHandlersConfiguration(): void
    {
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        GeneralUtility::makeInstance(RequestHandlersConfigurationFactory::class, $cacheManager)
            ->createRequestHandlersConfiguration();
    }

    /**
     * Runs the the Extbase Framework by resolving an appropriate Request Handler and passing control to it.
     * If the Framework is not initialized yet, it will be initialized.
     *
     * @param string $content The content. Not used
     * @param array $configuration The TS configuration array
     * @return string $content The processed content
     */
    public function run(string $content, array $configuration, ?ServerRequestInterface $request = null): string
    {
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'];
        $this->initialize($configuration);
        return $this->handleRequest($request);
    }

    /**
     * @return string
     */
    protected function handleRequest(ServerRequestInterface $request): string
    {
        $extbaseRequest = $this->extbaseRequestBuilder->build($request);
        $requestHandler = $this->requestHandlerResolver->resolveRequestHandler($extbaseRequest);
        $response = $requestHandler->handleRequest($extbaseRequest);
        // If response is NULL after handling the request we need to stop
        // This happens for instance, when a USER object was converted to a USER_INT
        // @see TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler::handleRequest()
        if ($response === null) {
            $content = '';
        } else {
            if (headers_sent() === false) {
                foreach ($response->getHeaders() as $name => $values) {
                    foreach ($values as $value) {
                        header(sprintf('%s: %s', $name, $value));
                    }
                }
            }

            $body = $response->getBody();
            $body->rewind();
            $content = $body->getContents();
            $this->resetSingletons();
            $this->cacheService->clearCachesOfRegisteredPageIds();
        }

        return $content;
    }

    /**
     * Entrypoint for backend modules, handling PSR-7 requests/responses
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
            'pluginName' => $route->getOption('moduleName')
        ];

        $this->initialize($configuration);

        $extbaseRequest = $this->extbaseRequestBuilder->build($request);
        $requestHandler = $this->requestHandlerResolver->resolveRequestHandler($extbaseRequest);
        $response = $requestHandler->handleRequest($extbaseRequest);

        $this->resetSingletons();
        $this->cacheService->clearCachesOfRegisteredPageIds();
        return $response;
    }

    /**
     * Resets global singletons for the next plugin
     */
    protected function resetSingletons(): void
    {
        $this->persistenceManager->persistAll();
    }
}
