<?php
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
use TYPO3\CMS\Extbase\Mvc\Cli\Response as CliResponse;
use TYPO3\CMS\Extbase\Mvc\Web\Response as WebResponse;

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
    public function initialize($configuration)
    {
        if (!Environment::isCli()) {
            if (!isset($configuration['vendorName']) || $configuration['vendorName'] === '') {
                throw new \RuntimeException('Invalid configuration: "vendorName" is not set', 1526629315);
            }
            if (!isset($configuration['extensionName']) || $configuration['extensionName'] === '') {
                throw new \RuntimeException('Invalid configuration: "extensionName" is not set', 1290623020);
            }
            if (!isset($configuration['pluginName']) || $configuration['pluginName'] === '') {
                throw new \RuntimeException('Invalid configuration: "pluginName" is not set', 1290623027);
            }
        }
        $this->initializeObjectManager();
        $this->initializeConfiguration($configuration);
        $this->configureObjectManager(true);
        $this->initializePersistence();
    }

    /**
     * Initializes the Object framework.
     *
     * @see initialize()
     */
    protected function initializeObjectManager()
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
    public function initializeConfiguration($configuration)
    {
        $this->configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject */
        $contentObject = $this->cObj ?? \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $this->configurationManager->setContentObject($contentObject);
        $this->configurationManager->setConfiguration($configuration);
    }

    /**
     * Configures the object manager object configuration from
     * config.tx_extbase.objects and plugin.tx_foo.objects
     *
     * @param bool $isInternalCall Set to true by Boostrap, not by extensions
     * @see initialize()
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function configureObjectManager($isInternalCall = false)
    {
        if (!$isInternalCall) {
            trigger_error(self::class . '::configureObjectManager() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        }
        $frameworkSetup = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (!isset($frameworkSetup['objects']) || !is_array($frameworkSetup['objects'])) {
            return;
        }
        trigger_error('Overriding object implementations via TypoScript settings config.tx_extbase.objects and plugin.tx_%plugin%.objects will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $objectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
        foreach ($frameworkSetup['objects'] as $classNameWithDot => $classConfiguration) {
            if (isset($classConfiguration['className'])) {
                $originalClassName = rtrim($classNameWithDot, '.');
                $objectContainer->registerImplementation($originalClassName, $classConfiguration['className']);
            }
        }
    }

    /**
     * Initializes the persistence framework
     *
     * @see initialize()
     * @internal
     */
    public function initializePersistence()
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
    public function run($content, $configuration)
    {
        $this->initialize($configuration);
        return $this->handleRequest();
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\CommandException Is thrown if the response object defined an exit code > 0
     * @return string
     */
    protected function handleRequest()
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
            if ($response instanceof CliResponse && $response->getExitCode()) {
                throw new \TYPO3\CMS\Extbase\Mvc\Exception\CommandException('The request has been terminated as the response defined an exit code.', $response->getExitCode());
            }
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
        if (isset($moduleConfiguration['vendorName'])) {
            $configuration['vendorName'] = $moduleConfiguration['vendorName'];
        }

        $this->initialize($configuration);

        /** @var \TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver $requestHandlerResolver */
        $requestHandlerResolver = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver::class);
        $requestHandler = $requestHandlerResolver->resolveRequestHandler();
        /** @var WebResponse $extbaseResponse */
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
     * @param WebResponse $extbaseResponse
     * @return ResponseInterface
     */
    protected function convertExtbaseResponseToPsr7Response(WebResponse $extbaseResponse): ResponseInterface
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
    protected function resetSingletons()
    {
        $this->persistenceManager->persistAll();
    }
}
