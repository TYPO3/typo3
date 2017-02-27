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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\Dispatcher;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Handles the request for backend modules and wizards
 * Juggles with $GLOBALS['TBE_MODULES']
 */
class BackendModuleRequestHandler implements RequestHandlerInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var array
     */
    protected $moduleRegistry = [];

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUserAuthentication;

    /**
     * Instance of the current Http Request
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Constructor handing over the bootstrap and the original request
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Handles the request, evaluating the configuration and executes the module accordingly
     *
     * @param ServerRequestInterface $request
     * @return NULL|\Psr\Http\Message\ResponseInterface
     * @throws Exception
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
        $this->boot();

        $this->moduleRegistry = $GLOBALS['TBE_MODULES'];

        if (!$this->isValidModuleRequest()) {
            throw new Exception('The CSRF protection token for the requested module is missing or invalid', 1417988921);
        }

        // Set to empty as it is not needed / always coming from typo3/index.php
        $GLOBALS['BACK_PATH'] = '';

        $this->backendUserAuthentication = $GLOBALS['BE_USER'];

        $moduleName = (string)$this->request->getQueryParams()['M'];
        if ($this->isDispatchedModule($moduleName)) {
            return $this->dispatchModule($moduleName);
        } else {
            // @deprecated: This else path is deprecated and throws deprecations logs at registration time. Can be removed with TYPO3 CMS 8.
            $isDispatched = $this->callTraditionalModule($moduleName);
            if (!$isDispatched) {
                throw new Exception('No module "' . $moduleName . '" could be found.', 1294585070);
            }
        }
        return null;
    }

    /**
     * Execute TYPO3 bootstrap
     *
     * @return void
     */
    protected function boot()
    {
        // Evaluate the constant for skipping the BE user check for the bootstrap, will be done without the constant at a later point
        if (defined('TYPO3_PROCEED_IF_NO_USER') && TYPO3_PROCEED_IF_NO_USER) {
            $proceedIfNoUserIsLoggedIn = true;
        } else {
            $proceedIfNoUserIsLoggedIn = false;
        }

        $this->bootstrap->checkLockedBackendAndRedirectOrDie()
            ->checkBackendIpOrDie()
            ->checkSslBackendAndRedirectIfNeeded()
            ->initializeBackendRouter()
            ->loadExtensionTables(true)
            ->initializeSpriteManager()
            ->initializeBackendUser()
            ->initializeBackendAuthentication($proceedIfNoUserIsLoggedIn)
            ->initializeLanguageObject()
            ->initializeBackendTemplate()
            ->endOutputBufferingAndCleanPreviousOutput()
            ->initializeOutputCompression()
            ->sendHttpHeaders();
    }

    /**
     * This request handler can handle any backend request coming from index.php
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function canHandleRequest(ServerRequestInterface $request)
    {
        return $request->getAttribute('isModuleRequest', false);
    }

    /**
     * Checks if all parameters are met.
     *
     * @return bool
     */
    protected function isValidModuleRequest()
    {
        return $this->getFormProtection() instanceof BackendFormProtection
            && $this->getFormProtection()->validateToken((string)$this->request->getQueryParams()['moduleToken'], 'moduleCall', (string)$this->request->getQueryParams()['M']);
    }

    /**
     * A dispatched module is used, when no PATH is given.
     * Traditional modules have a module path set.
     *
     * @param string $moduleName
     * @return bool
     */
    protected function isDispatchedModule($moduleName)
    {
        return empty($this->moduleRegistry['_PATHS'][$moduleName]);
    }

    /**
     * Executes the modules configured via Extbase
     *
     * @param string $moduleName
     * @return Response A PSR-7 response object
     * @throws \RuntimeException
     */
    protected function dispatchModule($moduleName)
    {
        $moduleConfiguration = $this->getModuleConfiguration($moduleName);

        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        // Check permissions and exit if the user has no permission for entry
        $this->backendUserAuthentication->modAccess($moduleConfiguration, true);
        $id = isset($this->request->getQueryParams()['id']) ? $this->request->getQueryParams()['id'] : $this->request->getParsedBody()['id'];
        if ($id && MathUtility::canBeInterpretedAsInteger($id)) {
            $permClause = $this->backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
            // Check page access
            $access = is_array(BackendUtility::readPageAccess((int)$id, $permClause));
            if (!$access) {
                // Check if page has been deleted
                $deleteField = $GLOBALS['TCA']['pages']['ctrl']['delete'];
                $pageInfo = BackendUtility::getRecord('pages', (int)$id, $deleteField, $permClause ? ' AND ' . $permClause : '', false);
                if (!$pageInfo[$deleteField]) {
                    throw new \RuntimeException('You don\'t have access to this page', 1289917924);
                }
            }
        }

        // Use Core Dispatching
        if (isset($moduleConfiguration['routeTarget'])) {
            $dispatcher = GeneralUtility::makeInstance(Dispatcher::class);
            $this->request = $this->request->withAttribute('target', $moduleConfiguration['routeTarget']);
            $response = $dispatcher->dispatch($this->request, $response);
        } else {
            // extbase module
            $configuration = [
                'extensionName' => $moduleConfiguration['extensionName'],
                'pluginName' => $moduleName
            ];
            if (isset($moduleConfiguration['vendorName'])) {
                $configuration['vendorName'] = $moduleConfiguration['vendorName'];
            }

            // Run Extbase
            $bootstrap = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Core\Bootstrap::class);
            $content = $bootstrap->run('', $configuration);

            $response->getBody()->write($content);
        }

        return $response;
    }

    /**
     * Calls traditional modules which are identified by having an index.php in their directory
     * and were previously located within the global scope.
     *
     * @param string $moduleName
     * @return bool Returns TRUE if the module was executed
     */
    protected function callTraditionalModule($moduleName)
    {
        $moduleBasePath = $this->moduleRegistry['_PATHS'][$moduleName];
        // Some modules still rely on this global configuration array in a conf.php file
        // load configuration from an existing conf.php file inside the same directory
        if (file_exists($moduleBasePath . 'conf.php')) {
            require $moduleBasePath . 'conf.php';
            $moduleConfiguration = $MCONF;
        } else {
            $moduleConfiguration = $this->getModuleConfiguration($moduleName);
        }
        $GLOBALS['MCONF'] = $moduleConfiguration;
        if (!empty($moduleConfiguration['access'])) {
            $this->backendUserAuthentication->modAccess($moduleConfiguration, true);
        }
        if (file_exists($moduleBasePath . 'index.php')) {
            global $SOBE;
            require $moduleBasePath . 'index.php';
            return true;
        }
        return false;
    }

    /**
     * Returns the module configuration which is provided during module registration
     *
     * @param string $moduleName
     * @return array
     * @throws \RuntimeException
     */
    protected function getModuleConfiguration($moduleName)
    {
        if (!isset($this->moduleRegistry['_configuration'][$moduleName])) {
            throw new \RuntimeException('Module ' . $moduleName . ' is not configured.', 1289918325);
        }
        return $this->moduleRegistry['_configuration'][$moduleName];
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 90;
    }

    /**
     * Wrapper method for static form protection utility
     *
     * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
     */
    protected function getFormProtection()
    {
        return FormProtectionFactory::get();
    }
}
