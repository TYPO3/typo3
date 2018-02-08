<?php
namespace TYPO3\CMS\Install\Http;

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
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\AbstractApplication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Entry point for the TYPO3 Install Tool
 */
class Application extends AbstractApplication
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Number of subdirectories where the entry script is located, relative to PATH_site
     * @var int
     */
    protected $entryPointLevel = 1;

    /**
     * All available request handlers that can handle an install tool request
     * @var array
     */
    protected $availableRequestHandlers = [
        \TYPO3\CMS\Install\Http\RequestHandler::class,
        \TYPO3\CMS\Install\Http\InstallerRequestHandler::class
    ];

    /**
     * Constructor setting up legacy constant and register available Request Handlers
     *
     * @param \Composer\Autoload\ClassLoader $classLoader an instance of the class loader
     */
    public function __construct($classLoader)
    {
        $this->defineLegacyConstants();

        $this->bootstrap = Bootstrap::getInstance()
            ->initializeClassLoader($classLoader)
            ->setRequestType(TYPO3_REQUESTTYPE_INSTALL)
            ->baseSetup($this->entryPointLevel);

        $this->bootstrap
            ->startOutputBuffering()
            ->loadConfigurationAndInitialize(false, \TYPO3\CMS\Core\Package\FailsafePackageManager::class);

        $this->disableCachingFramework();
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->availableRequestHandlers as $requestHandler) {
            $handler = GeneralUtility::makeInstance($requestHandler, $this->bootstrap);
            if ($handler->canHandleRequest($request)) {
                return $handler->handleRequest($request);
            }
        }

        throw new \TYPO3\CMS\Core\Exception('No suitable request handler found.', 1518448686);
    }

    /**
     * Set caching to NullBackend, install tool must not cache anything
     */
    protected function disableCachingFramework()
    {
        $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];

        $cacheConfigurationsWithCachesSetToNullBackend = [];
        foreach ($cacheConfigurations as $cacheName => $cacheConfiguration) {
            // cache_core is handled in bootstrap already
            if (is_array($cacheConfiguration) && $cacheName !== 'cache_core') {
                $cacheConfiguration['backend'] = NullBackend::class;
                $cacheConfiguration['options'] = [];
            }
            $cacheConfigurationsWithCachesSetToNullBackend[$cacheName] = $cacheConfiguration;
        }
        /** @var $cacheManager \TYPO3\CMS\Core\Cache\CacheManager */
        $cacheManager = $this->bootstrap->getEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        $cacheManager->setCacheConfigurations($cacheConfigurationsWithCachesSetToNullBackend);
    }

    /**
     * Define constants
     */
    protected function defineLegacyConstants()
    {
        define('TYPO3_MODE', 'BE');
    }
}
