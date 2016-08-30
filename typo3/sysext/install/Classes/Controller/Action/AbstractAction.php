<?php
namespace TYPO3\CMS\Install\Controller\Action;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * General purpose controller action helper methods and bootstrap
 */
abstract class AbstractAction implements ActionInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager = null;

    /**
     * Do NOT refactor to use @inject annotation, as failsafe handling would not work any more
     *
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @var \TYPO3\CMS\Install\View\FailsafeView
     */
    protected $view = null;

    /**
     * Do NOT refactor to use @inject annotation, as failsafe handling would not work any more
     *
     * @param \TYPO3\CMS\Install\View\FailsafeView $view
     */
    public function injectView(\TYPO3\CMS\Install\View\FailsafeView $view)
    {
        $this->view = $view;
    }

    /**
     * @var string Name of controller. One of the strings 'step', 'tool' or 'common'
     */
    protected $controller = '';

    /**
     * @var string Name of target action, set by controller
     */
    protected $action = '';

    /**
     * @var string Form token for CSRF protection
     */
    protected $token = '';

    /**
     * @var array Values in $_POST['install']
     */
    protected $postValues = [];

    /**
     * @var array Contains the fatal error array of the last request when passed. Schema is the one returned by error_get_last()
     */
    protected $lastError = [];

    /**
     * @var array<\TYPO3\CMS\Install\Status\StatusInterface> Optional status message from controller
     */
    protected $messages = [];

    /**
     * Handles the action
     *
     * @return string Rendered content
     */
    public function handle()
    {
        $this->initializeHandle();
        return $this->executeAction();
    }

    /**
     * Initialize the handle action, sets up fluid stuff and assigns default variables.
     *
     * @return void
     */
    protected function initializeHandle()
    {
        // Context service distinguishes between standalone and backend context
        $contextService = $this->objectManager->get(\TYPO3\CMS\Install\Service\ContextService::class);

        $viewRootPath = GeneralUtility::getFileAbsFileName('EXT:install/Resources/Private/');
        $controllerActionDirectoryName = ucfirst($this->controller);
        $mainTemplate = ucfirst($this->action);
        $this->view->setTemplatePathAndFilename($viewRootPath . 'Templates/Action/' . $controllerActionDirectoryName . '/' . $mainTemplate . '.html');
        $this->view->setLayoutRootPath($viewRootPath . 'Layouts/');
        $this->view->setPartialRootPath($viewRootPath . 'Partials/');
        $this->view
            // time is used in js and css as parameter to force loading of resources
            ->assign('time', time())
            ->assign('action', $this->action)
            ->assign('controller', $this->controller)
            ->assign('token', $this->token)
            ->assign('context', $contextService->getContextString())
            ->assign('contextService', $contextService)
            ->assign('lastError', $this->lastError)
            ->assign('messages', $this->messages)
            ->assign('typo3Version', TYPO3_version)
            ->assign('siteName', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
    }

    /**
     * Executes the action
     *
     * @return string|array Rendered content
     */
    abstract protected function executeAction();

    /**
     * Set form protection token
     *
     * @param string $token Form protection token
     * @return void
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Set action group. Either string 'step', 'tool' or 'common'
     *
     * @param string $controller Controller name
     * @return void
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Set action name. This is usually similar to the class name,
     * only for loginForm, the action is login
     *
     * @param string $action Name of target action for forms
     * @return void
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Set POST form values of install tool
     *
     * @param array $postValues
     * @return void
     */
    public function setPostValues(array $postValues)
    {
        $this->postValues = $postValues;
    }

    /**
     * Set the last error array as returned by error_get_last()
     *
     * @param array $lastError
     */
    public function setLastError(array $lastError)
    {
        $this->lastError = $lastError;
    }

    /**
     * Status messages from controller
     *
     * @param array<\TYPO3\CMS\Install\Status\StatusInterface> $messages
     */
    public function setMessages(array $messages = [])
    {
        $this->messages = $messages;
    }

    /**
     * Return TRUE if dbal and adodb extension is loaded
     *
     * @return bool TRUE if dbal and adodb is loaded
     */
    protected function isDbalEnabled()
    {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adodb')
            && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal')
        ) {
            return true;
        }
        return false;
    }

    /**
     * Context determines if the install tool is called within backend or standalone
     *
     * @return string Either 'standalone' or 'backend'
     */
    protected function getContext()
    {
        $context = 'standalone';
        $formValues = GeneralUtility::_GP('install');
        if (isset($formValues['context'])) {
            $context = $formValues['context'] === 'backend' ? 'backend' : 'standalone';
        }
        return $context;
    }

    /**
     * Get database instance.
     * Will be initialized if it does not exist yet.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        static $database;
        if (!is_object($database)) {
            /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
            $database = $this->objectManager->get(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
            $database->setDatabaseUsername($GLOBALS['TYPO3_CONF_VARS']['DB']['username']);
            $database->setDatabasePassword($GLOBALS['TYPO3_CONF_VARS']['DB']['password']);
            $database->setDatabaseHost($GLOBALS['TYPO3_CONF_VARS']['DB']['host']);
            $database->setDatabasePort($GLOBALS['TYPO3_CONF_VARS']['DB']['port']);
            $database->setDatabaseSocket($GLOBALS['TYPO3_CONF_VARS']['DB']['socket']);
            $database->setDatabaseName($GLOBALS['TYPO3_CONF_VARS']['DB']['database']);
            $database->initialize();
            $database->connectDB();
        }
        return $database;
    }

    /**
     * Some actions like the database analyzer and the upgrade wizards need additional
     * bootstrap actions performed.
     *
     * Those actions can potentially fatal if some old extension is loaded that triggers
     * a fatal in ext_localconf or ext_tables code! Use only if really needed.
     *
     * @return void
     */
    protected function loadExtLocalconfDatabaseAndExtTables()
    {
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()
            ->ensureClassLoadingInformationExists()
            ->loadTypo3LoadedExtAndExtLocalconf(false)
            ->defineLoggingAndExceptionConstants()
            ->unsetReservedGlobalVariables()
            ->initializeTypo3DbGlobal()
            ->loadExtensionTables(false);
    }

    /**
     * This function returns a salted hashed key.
     *
     * @param string $password
     * @return string
     */
    protected function getHashedPassword($password)
    {
        $saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null, 'BE');
        return $saltFactory->getHashedPassword($password);
    }

    /**
     * Prepare status messages used in extension compatibility view template
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface[]
     */
    protected function getExtensionCompatibilityTesterMessages()
    {
        $extensionCompatibilityTesterMessages = [];

        /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
        $message = $this->objectManager->get(\TYPO3\CMS\Install\Status\LoadingStatus::class);
        $message->setTitle('Loading...');
        $extensionCompatibilityTesterMessages[] = $message;

        $message = $this->objectManager->get(\TYPO3\CMS\Install\Status\ErrorStatus::class);
        $message->setTitle('Incompatible extension found!');
        $message->setMessage('Something went wrong and no protocol was written.');
        $extensionCompatibilityTesterMessages[] = $message;

        $message = $this->objectManager->get(\TYPO3\CMS\Install\Status\OkStatus::class);
        $message->setTitle('All local extensions can be loaded!');
        $extensionCompatibilityTesterMessages[] = $message;

        return $extensionCompatibilityTesterMessages;
    }
}
