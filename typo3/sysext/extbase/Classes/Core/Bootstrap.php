<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Core;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Mvc\Web\Response as ExtbaseResponse;

/**
 * Creates a request an dispatches it to the controller which was specified
 * by TS Setup, flexForm and returns the content.
 *
 * This class is the main entry point for extbase extensions.
 */
class Bootstrap implements \TYPO3\CMS\Extbase\Core\BootstrapInterface
{
    /**
     * Back reference to the parent content object
     * This has to be public as it is set directly from TYPO3
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

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
        $this->initializeObjectManager();
        $this->initializeConfiguration($configuration);
        $this->initializePersistence();
    }

    /**
     * Initializes the Object framework.
     *
     * @see initialize()
     */
    protected function initializeObjectManager(): void
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
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
        $this->configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject */
        $contentObject = $this->cObj ?? \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
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
     * Initializes the persistence framework
     *
     * @see initialize()
     * @internal
     */
    public function initializePersistence(): void
    {
        $this->persistenceManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
    }

    /**
     * Runs the the Extbase Framework by resolving an appropriate Request Handler and passing control to it.
     * If the Framework is not initialized yet, it will be initialized.
     *
     * @param string $content The content. Not used
     * @param array $configuration The TS configuration array
     * @return string $content The processed content
     */
    public function run(string $content, array $configuration): string
    {
        $this->initialize($configuration);
        return $this->handleRequest();
    }

    /**
     * @return string
     */
    protected function handleRequest(): string
    {
        /** @var \TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver $requestHandlerResolver */
        $requestHandlerResolver = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver::class);
        $requestHandler = $requestHandlerResolver->resolveRequestHandler();

        $response = $requestHandler->handleRequest();
        // If response is NULL after handling the request we need to stop
        // This happens for instance, when a USER object was converted to a USER_INT
        // @see TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler::handleRequest()
        if ($response === null) {
            $content = '';
        } else {
            $content = $response->shutdown();
            $this->resetSingletons();
            $this->objectManager->get(\TYPO3\CMS\Extbase\Service\CacheService::class)->clearCachesOfRegisteredPageIds();
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

        /** @var \TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver $requestHandlerResolver */
        $requestHandlerResolver = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver::class);
        $requestHandler = $requestHandlerResolver->resolveRequestHandler();
        /** @var ExtbaseResponse $extbaseResponse */
        $extbaseResponse = $requestHandler->handleRequest();

        // Convert to PSR-7 response and hand it back to TYPO3 Core
        $response = $this->convertExtbaseResponseToPsr7Response($extbaseResponse);
        $this->resetSingletons();
        $this->objectManager->get(\TYPO3\CMS\Extbase\Service\CacheService::class)->clearCachesOfRegisteredPageIds();
        return $response;
    }

    /**
     * Converts a Extbase response object into a PSR-7 Response
     *
     * @param ExtbaseResponse $extbaseResponse
     * @return ResponseInterface
     */
    protected function convertExtbaseResponseToPsr7Response(ExtbaseResponse $extbaseResponse): ResponseInterface
    {
        $response = new \TYPO3\CMS\Core\Http\Response(
            'php://temp',
            $extbaseResponse->getStatusCode(),
            $extbaseResponse->getUnpreparedHeaders()
        );
        $content = $extbaseResponse->getContent();
        if ($content !== null) {
            $response->getBody()->write($content);
        }
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
