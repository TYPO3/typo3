<?php
namespace TYPO3\CMS\Backend\Http;

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
 * Entry point for the TYPO3 Backend (HTTP requests)
 */
class Application implements ApplicationInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Number of subdirectories where the entry script is located, relative to PATH_site
     * Usually this is equal to PATH_site = 0
     * @var int
     */
    protected $entryPointLevel = 1;

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * All available request handlers that can handle backend requests (non-CLI)
     * @var array
     */
    protected $availableRequestHandlers = [
        \TYPO3\CMS\Backend\Http\RequestHandler::class,
        \TYPO3\CMS\Backend\Http\BackendModuleRequestHandler::class,
        \TYPO3\CMS\Backend\Http\AjaxRequestHandler::class
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
            ->setRequestType(TYPO3_REQUESTTYPE_BE | (!empty($_GET['ajaxID']) ? TYPO3_REQUESTTYPE_AJAX : 0))
            ->baseSetup($this->entryPointLevel);

        // Redirect to install tool if base configuration is not found
        if (!$this->bootstrap->checkIfEssentialConfigurationExists()) {
            $this->bootstrap->redirectToInstallTool($this->entryPointLevel);
        }

        foreach ($this->availableRequestHandlers as $requestHandler) {
            $this->bootstrap->registerRequestHandlerImplementation($requestHandler);
        }

        $this->bootstrap->configure();
    }

    /**
     * Set up the application and shut it down afterwards
     *
     * @param callable $execute
     */
    public function run(callable $execute = null)
    {
        $this->request = \TYPO3\CMS\Core\Http\ServerRequestFactory::fromGlobals();
        // see below when this option is set and Bootstrap::defineTypo3RequestTypes() for more details
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
            $this->request = $this->request->withAttribute('isAjaxRequest', true);
        } elseif (isset($this->request->getQueryParams()['M'])) {
            $this->request = $this->request->withAttribute('isModuleRequest', true);
        }

        $this->bootstrap->handleRequest($this->request);

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
    }
}
