<?php
namespace TYPO3\CMS\Backend\Console;

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
 * Entry point for the TYPO3 Command Line for Backend calls
 */
class Application implements ApplicationInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     *
     * @var string
     */
    protected $entryPointPath = 'typo3/';

    /**
     * All available request handlers that can deal with a CLI Request
     * @var array
     */
    protected $availableRequestHandlers = [
        \TYPO3\CMS\Backend\Console\CliRequestHandler::class
    ];

    /**
     * Constructor setting up legacy constants and register available Request Handlers
     *
     * @param \Composer\Autoload\ClassLoader $classLoader an instance of the class loader
     */
    public function __construct($classLoader)
    {
        $this->checkEnvironmentOrDie();

        $this->defineLegacyConstants();

        $this->bootstrap = Bootstrap::getInstance()
            ->initializeClassLoader($classLoader)
            ->baseSetup($this->entryPointPath);

        foreach ($this->availableRequestHandlers as $requestHandler) {
            $this->bootstrap->registerRequestHandlerImplementation($requestHandler);
        }

        $this->bootstrap->configure();
    }

    /**
     * Set up the application and shut it down afterwards
     *
     * @param callable $execute
     * @return void
     */
    public function run(callable $execute = null)
    {
        $this->bootstrap->handleRequest(new \Symfony\Component\Console\Input\ArgvInput());

        if ($execute !== null) {
            call_user_func($execute);
        }

        $this->bootstrap->shutdown();
    }

    /**
     * Define constants and variables
     */
    protected function defineLegacyConstants()
    {
        define('TYPO3_MODE', 'BE');
        define('TYPO3_cliMode', true);
    }

    /**
     * Check the script is called from a cli environment.
     *
     * @return void
     */
    protected function checkEnvironmentOrDie()
    {
        if (substr(php_sapi_name(), 0, 3) === 'cgi') {
            $this->initializeCgiCompatibilityLayerOrDie();
        } elseif (php_sapi_name() !== 'cli') {
            die('Not called from a command line interface (e.g. a shell or scheduler).' . LF);
        }
    }

    /**
     * Set up cgi sapi as de facto cli, but check no HTTP
     * environment variables are set.
     *
     * @return void
     */
    protected function initializeCgiCompatibilityLayerOrDie()
    {
        // Sanity check: Ensure we're running in a shell or cronjob (and NOT via HTTP)
        $checkEnvVars = ['HTTP_USER_AGENT', 'HTTP_HOST', 'SERVER_NAME', 'REMOTE_ADDR', 'REMOTE_PORT', 'SERVER_PROTOCOL'];
        foreach ($checkEnvVars as $var) {
            if (array_key_exists($var, $_SERVER)) {
                echo 'SECURITY CHECK FAILED! This script cannot be used within your browser!' . LF;
                echo 'If you are sure that we run in a shell or cronjob, please unset' . LF;
                echo 'environment variable ' . $var . ' (usually using \'unset ' . $var . '\')' . LF;
                echo 'before starting this script.' . LF;
                die;
            }
        }

        // Mimic CLI API in CGI API (you must use the -C/-no-chdir and the -q/--no-header switches!)
        ini_set('html_errors', 0);
        ini_set('implicit_flush', 1);
        ini_set('max_execution_time', 0);
        define('STDIN', fopen('php://stdin', 'r'));
        define('STDOUT', fopen('php://stdout', 'w'));
        define('STDERR', fopen('php://stderr', 'w'));
    }
}
