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
use TYPO3\CMS\Core\Core\ApplicationInterface;
use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * Entry point for the TYPO3 Install Tool
 */
class Application implements ApplicationInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var string
     */
    protected $entryPointPath = 'typo3/sysext/install/Start/';

    /**
     * All available request handlers that can handle an install tool request
     * @var array
     */
    protected $availableRequestHandlers = [
        \TYPO3\CMS\Install\Http\RequestHandler::class
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
            ->baseSetup($this->entryPointPath);

        foreach ($this->availableRequestHandlers as $requestHandler) {
            $this->bootstrap->registerRequestHandlerImplementation($requestHandler);
        }

        $this->bootstrap
            ->startOutputBuffering()
            ->loadConfigurationAndInitialize(false, \TYPO3\CMS\Core\Package\FailsafePackageManager::class);
    }

    /**
     * Set up the application and shut it down afterwards
     * Failsafe minimal setup mode for the install tool
     * Does not call "run()" therefore
     *
     * @param callable $execute
     * @return void
     */
    public function run(callable $execute = null)
    {
        $this->bootstrap->handleRequest(\TYPO3\CMS\Core\Http\ServerRequestFactory::fromGlobals());

        if ($execute !== null) {
            call_user_func($execute);
        }

        $this->bootstrap->shutdown();
    }

    /**
     * Define constants
     */
    protected function defineLegacyConstants()
    {
        define('TYPO3_MODE', 'BE');
        define('TYPO3_enterInstallScript', '1');
    }
}
