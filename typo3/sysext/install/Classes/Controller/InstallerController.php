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

namespace TYPO3\CMS\Install\Controller;

use Doctrine\DBAL\DriverManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Middleware\VerifyHostHeader;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\FluidViewAdapter;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Install\Configuration\FeatureManager;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\Exception\TemplateFileChangedException;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\SetupDatabaseService;
use TYPO3\CMS\Install\Service\SetupService;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;
use TYPO3\CMS\Install\Service\SilentTemplateFileUpgradeService;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\SetupCheck;
use TYPO3\CMS\Install\WebserverType;
use TYPO3Fluid\Fluid\View\TemplateView as FluidTemplateView;

/**
 * Install step controller, dispatcher class of step actions.
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 * @phpstan-import-type Params from DriverManager
 */
final class InstallerController
{
    use ControllerTrait;

    public function __construct(
        private readonly LateBootService $lateBootService,
        private readonly SilentConfigurationUpgradeService $silentConfigurationUpgradeService,
        private readonly SilentTemplateFileUpgradeService $silentTemplateFileUpgradeService,
        private readonly ConfigurationManager $configurationManager,
        private readonly FailsafePackageManager $packageManager,
        private readonly VerifyHostHeader $verifyHostHeader,
        private readonly FormProtectionFactory $formProtectionFactory,
        private readonly SetupService $setupService,
        private readonly SetupDatabaseService $setupDatabaseService,
    ) {}

    /**
     * Init action loads <head> with JS initiating further stuff
     */
    public function initAction(ServerRequestInterface $request): ResponseInterface
    {
        $bust = $GLOBALS['EXEC_TIME'];
        if (!Environment::getContext()->isDevelopment()) {
            $bust = GeneralUtility::hmac((string)(new Typo3Version()) . Environment::getProjectPath());
        }
        $packages = [
            $this->packageManager->getPackage('core'),
            $this->packageManager->getPackage('backend'),
            $this->packageManager->getPackage('install'),
        ];
        $importMap = new ImportMap($packages);
        $sitePath = $request->getAttribute('normalizedParams')->getSitePath();
        $initModule = $sitePath . $importMap->resolveImport('@typo3/install/init-installer.js');
        $view = $this->initializeView();
        $view->assign('bust', $bust);
        $view->assign('initModule', $initModule);
        $nonce = new ConsumableNonce();
        $view->assign('importmap', $importMap->render($sitePath, $nonce));

        return new HtmlResponse(
            $view->render('Installer/Init'),
            200,
            [
                'Content-Security-Policy' => $this->createContentSecurityPolicy()->compile($nonce),
                'Cache-Control' => 'no-cache, no-store',
                'Pragma' => 'no-cache',
            ]
        );
    }

    /**
     * Main layout with progress bar, header
     */
    public function mainLayoutAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView();
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Installer/MainLayout'),
        ]);
    }

    /**
     * Render "FIRST_INSTALL file need to exist" view
     */
    public function showInstallerNotAvailableAction(): ResponseInterface
    {
        $view = $this->initializeView();
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Installer/ShowInstallerNotAvailable'),
        ]);
    }

    /**
     * Check if "environment and folders" should be shown
     */
    public function checkEnvironmentAndFoldersAction(): ResponseInterface
    {
        return new JsonResponse([
            'success' => @is_file($this->configurationManager->getSystemConfigurationFileLocation()),
        ]);
    }

    /**
     * Render "environment and folders"
     */
    public function showEnvironmentAndFoldersAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView();
        $systemCheckMessageQueue = new FlashMessageQueue('install');
        $checkMessages = (new Check())->getStatus();
        foreach ($checkMessages as $message) {
            $systemCheckMessageQueue->enqueue($message);
        }
        $setupCheckMessages = (new SetupCheck())->getStatus();
        foreach ($setupCheckMessages as $message) {
            $systemCheckMessageQueue->enqueue($message);
        }
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure(WebserverType::fromRequest($request));
        $structureMessageQueue = $structureFacade->getStatus();
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Installer/ShowEnvironmentAndFolders'),
            'environmentStatusErrors' => $systemCheckMessageQueue->getAllMessages(ContextualFeedbackSeverity::ERROR),
            'environmentStatusWarnings' => $systemCheckMessageQueue->getAllMessages(ContextualFeedbackSeverity::WARNING),
            'structureErrors' => $structureMessageQueue->getAllMessages(ContextualFeedbackSeverity::ERROR),
        ]);
    }

    /**
     * Create main folder layout, LocalConfiguration, PackageStates
     */
    public function executeEnvironmentAndFoldersAction(ServerRequestInterface $request): ResponseInterface
    {
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure(WebserverType::fromRequest($request));
        $structureFixMessageQueue = $structureFacade->fix();
        $errorsFromStructure = $structureFixMessageQueue->getAllMessages(ContextualFeedbackSeverity::ERROR);

        if (@is_dir(Environment::getLegacyConfigPath())) {
            $this->configurationManager->createLocalConfigurationFromFactoryConfiguration();
            // Create a PackageStates.php with all packages activated marked as "part of factory default"
            $this->packageManager->recreatePackageStatesFileIfMissing(true);
            $extensionConfiguration = new ExtensionConfiguration();
            $extensionConfiguration->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();

            return new JsonResponse([
                'success' => true,
            ]);
        }
        return new JsonResponse([
            'success' => false,
            'status' => $errorsFromStructure,
        ]);
    }

    /**
     * Check if trusted hosts pattern needs to be adjusted
     */
    public function checkTrustedHostsPatternAction(ServerRequestInterface $request): ResponseInterface
    {
        $serverParams = $request->getServerParams();
        $host = $serverParams['HTTP_HOST'] ?? '';

        return new JsonResponse([
            'success' => $this->verifyHostHeader->isAllowedHostHeaderValue($host, $serverParams),
        ]);
    }

    /**
     * Adjust trusted hosts pattern to '.*' if it does not match yet
     */
    public function executeAdjustTrustedHostsPatternAction(ServerRequestInterface $request): ResponseInterface
    {
        $serverParams = $request->getServerParams();
        $host = $serverParams['HTTP_HOST'] ?? '';

        if (!$this->verifyHostHeader->isAllowedHostHeaderValue($host, $serverParams)) {
            $this->configurationManager->setLocalConfigurationValueByPath('SYS/trustedHostsPattern', '.*');
        }
        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Execute silent configuration update. May be called multiple times until success = true is returned.
     *
     * @return ResponseInterface success = true if no change has been done
     */
    public function executeSilentConfigurationUpdateAction(): ResponseInterface
    {
        $success = true;
        try {
            $this->silentConfigurationUpgradeService->execute();
        } catch (ConfigurationChangedException $e) {
            $success = false;
        }
        return new JsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * Execute silent template files update. May be called multiple times until success = true is returned.
     *
     * @return ResponseInterface success = true if no change has been done
     */
    public function executeSilentTemplateFileUpdateAction(): ResponseInterface
    {
        $success = true;
        try {
            $this->silentTemplateFileUpgradeService->execute();
        } catch (TemplateFileChangedException $e) {
            $success = false;
        }
        return new JsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * Check if database connect step needs to be shown
     */
    public function checkDatabaseConnectAction(): ResponseInterface
    {
        return new JsonResponse([
            'success' => $this->setupDatabaseService->isDatabaseConfigurationComplete() && $this->setupDatabaseService->isDatabaseConnectSuccessful(),
        ]);
    }

    /**
     * Show database connect step
     */
    public function showDatabaseConnectAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView();

        $driverOptions = $this->setupDatabaseService->getDriverOptions();
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $driverOptions['executeDatabaseConnectToken'] = $formProtection->generateToken('installTool', 'executeDatabaseConnect');
        $view->assignMultiple($driverOptions);

        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Installer/ShowDatabaseConnect'),
        ]);
    }

    /**
     * Test database connect data
     */
    public function executeDatabaseConnectAction(ServerRequestInterface $request): ResponseInterface
    {
        $postValues = $request->getParsedBody()['install']['values'];
        [$success, $messages] = $this->setupDatabaseService->setDefaultConnectionSettings($postValues);

        return new JsonResponse([
            'success' => $success,
            'status' => $messages,
        ]);
    }

    /**
     * Check if a database needs to be selected
     */
    public function checkDatabaseSelectAction(): ResponseInterface
    {
        return new JsonResponse([
            'success' => $this->setupDatabaseService->checkDatabaseSelect(),
        ]);
    }

    /**
     * Render "select a database"
     */
    public function showDatabaseSelectAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView();
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $errors = [];
        try {
            $view->assign('databaseList', $this->setupDatabaseService->getDatabaseList());
        } catch (\Exception $exception) {
            $errors[] = $exception->getMessage();
        }
        $view->assignMultiple([
            'errors' => $errors,
            'executeDatabaseSelectToken' => $formProtection->generateToken('installTool', 'executeDatabaseSelect'),
            'executeCheckDatabaseRequirementsToken' => $formProtection->generateToken('installTool', 'checkDatabaseRequirements'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Installer/ShowDatabaseSelect'),
        ]);
    }

    /**
     * Pre-check whether all requirements for the installed database driver and platform are fulfilled
     */
    public function checkDatabaseRequirementsAction(ServerRequestInterface $request): ResponseInterface
    {
        $success = true;
        $messages = [];
        $databaseDriverName = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['driver'];

        $databaseName = $this->retrieveDatabaseNameFromRequest($request);
        if ($databaseName === '') {
            return new JsonResponse([
                'success' => false,
                'status' => [
                    new FlashMessage(
                        'You must select a database.',
                        'No Database selected',
                        ContextualFeedbackSeverity::ERROR
                    ),
                ],
            ]);
        }

        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname'] = $databaseName;

        foreach ($this->setupDatabaseService->checkDatabaseRequirementsForDriver($databaseDriverName) as $message) {
            if ($message->getSeverity() === ContextualFeedbackSeverity::ERROR) {
                $success = false;
                $messages[] = $message;
            }
        }

        // Check create and drop permissions
        $statusMessages = [];
        foreach ($this->setupDatabaseService->checkRequiredDatabasePermissions() as $checkRequiredPermission) {
            $statusMessages[] = new FlashMessage(
                $checkRequiredPermission,
                'Missing required permissions',
                ContextualFeedbackSeverity::ERROR
            );
        }
        if ($statusMessages !== []) {
            return new JsonResponse([
                'success' => false,
                'status' => $statusMessages,
            ]);
        }

        // if requirements are not fulfilled
        if ($success === false) {
            // remove the database again if we created it
            if ($request->getParsedBody()['install']['values']['type'] === 'new') {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
                $connection
                    ->createSchemaManager()
                    ->dropDatabase($connection->quoteIdentifier($databaseName));
            }

            $this->configurationManager->removeLocalConfigurationKeysByPath(['DB/Connections/Default/dbname']);

            $message = new FlashMessage(
                sprintf(
                    'Database with name "%s" has been removed due to the following errors. '
                    . 'Please solve them first and try again. If you tried to create a new database make also sure, that the DBMS charset is to use UTF-8',
                    $databaseName
                ),
                '',
                ContextualFeedbackSeverity::INFO
            );
            array_unshift($messages, $message);
        }

        unset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['dbname']);

        return new JsonResponse([
            'success' => $success,
            'status' => $messages,
        ]);
    }

    private function retrieveDatabaseNameFromRequest(ServerRequestInterface $request): string
    {
        $postValues = $request->getParsedBody()['install']['values'];
        if ($postValues['type'] === 'new') {
            return $postValues['new'];
        }

        if ($postValues['type'] === 'existing' && !empty($postValues['existing'])) {
            return $postValues['existing'];
        }
        return '';
    }

    /**
     * Select / create and test a database
     */
    public function executeDatabaseSelectAction(ServerRequestInterface $request): ResponseInterface
    {
        $databaseName = $this->retrieveDatabaseNameFromRequest($request);
        if ($databaseName === '') {
            return new JsonResponse([
                'success' => false,
                'status' => [
                    new FlashMessage(
                        'You must select a database.',
                        'No Database selected',
                        ContextualFeedbackSeverity::ERROR
                    ),
                ],
            ]);
        }

        $postValues = $request->getParsedBody()['install']['values'];
        if ($postValues['type'] === 'new') {
            $status = $this->setupDatabaseService->createNewDatabase($databaseName);
            if ($status->getSeverity() === ContextualFeedbackSeverity::ERROR) {
                return new JsonResponse([
                    'success' => false,
                    'status' => [$status],
                ]);
            }
        } elseif ($postValues['type'] === 'existing') {
            $status = $this->setupDatabaseService->checkExistingDatabase($databaseName);
            if ($status->getSeverity() === ContextualFeedbackSeverity::ERROR) {
                return new JsonResponse([
                    'success' => false,
                    'status' => [$status],
                ]);
            }
        }
        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Check if initial data needs to be imported
     */
    public function checkDatabaseDataAction(): ResponseInterface
    {
        $existingTables = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
            ->createSchemaManager()
            ->listTableNames();
        return new JsonResponse([
            'success' => !empty($existingTables),
        ]);
    }

    /**
     * Render "import initial data"
     */
    public function showDatabaseDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView();
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $view->assignMultiple([
            'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            'executeDatabaseDataToken' => $formProtection->generateToken('installTool', 'executeDatabaseData'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Installer/ShowDatabaseData'),
        ]);
    }

    /**
     * Create main db layout
     */
    public function executeDatabaseDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $messages = [];
        $postValues = $request->getParsedBody()['install']['values'];
        $username = (string)$postValues['username'] !== '' ? $postValues['username'] : 'admin';
        // Check password and return early if not good enough
        $password = (string)($postValues['password'] ?? '');
        $email = $postValues['email'] ?? '';
        $passwordValidationErrors = $this->setupDatabaseService->getBackendUserPasswordValidationErrors($password);
        if (!empty($passwordValidationErrors)) {
            $messages[] = new FlashMessage(
                'Administrator password not secure enough!',
                '',
                ContextualFeedbackSeverity::ERROR
            );

            // Add all password validation errors to the messages array
            foreach ($passwordValidationErrors as $error) {
                $messages[] = new FlashMessage(
                    $error,
                    '',
                    ContextualFeedbackSeverity::ERROR
                );
            }

            return new JsonResponse([
                'success' => false,
                'status' => $messages,
            ]);
        }
        // Set site name
        if (!empty($postValues['sitename'])) {
            $this->setupService->setSiteName($postValues['sitename']);
        }
        try {
            $messages = $this->setupDatabaseService->importDatabaseData();
            if (!empty($messages)) {
                return new JsonResponse([
                    'success' => false,
                    'status' => $messages,
                ]);
            }
        } catch (StatementException $exception) {
            $messages[] = new FlashMessage(
                'Error detected in SQL statement:' . LF . $exception->getMessage(),
                'Import of database data could not be performed',
                ContextualFeedbackSeverity::ERROR
            );
            return new JsonResponse([
                'success' => false,
                'status' => $messages,
            ]);
        }

        $this->setupService->createUser($username, $password, $email);
        $this->setupService->setInstallToolPassword($password);

        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * Show last "create empty site / install distribution"
     */
    public function showDefaultConfigurationAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView();
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $view->assignMultiple([
            'composerMode' => Environment::isComposerMode(),
            'executeDefaultConfigurationToken' => $formProtection->generateToken('installTool', 'executeDefaultConfiguration'),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Installer/ShowDefaultConfiguration'),
        ]);
    }

    /**
     * Last step execution: clean up, remove FIRST_INSTALL file, ...
     */
    public function executeDefaultConfigurationAction(ServerRequestInterface $request): ResponseInterface
    {
        $featureManager = new FeatureManager();
        // Get best matching configuration presets
        $configurationValues = $featureManager->getBestMatchingConfigurationForAllFeatures();

        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables();
        // Use the container here instead of makeInstance() to use the factory of the container for building the UriBuilder
        $uriBuilder = $container->get(UriBuilder::class);
        $nextStepUrl = $uriBuilder->buildUriFromRoute('login');
        // Let the admin user redirect to the distributions page on first login
        switch ($request->getParsedBody()['install']['values']['sitesetup']) {
            // Update the URL to redirect after login to the extension manager distributions list
            case 'loaddistribution':
                $nextStepUrl = $uriBuilder->buildUriWithRedirect(
                    'login',
                    [],
                    RouteRedirect::create(
                        'tools_ExtensionmanagerExtensionmanager',
                        [
                            'action' => 'distributions',
                        ]
                    )
                );
                break;

                // Create a page with UID 1 and PID1 and fluid_styled_content for page TS config, respect ownership
            case 'createsite':
                $pageUid = $this->setupService->createSite();

                $normalizedParams = $request->getAttribute('normalizedParams');
                if (!($normalizedParams instanceof NormalizedParams)) {
                    $normalizedParams = NormalizedParams::createFromRequest($request);
                }
                // Check for siteUrl, despite there currently is no UI to provide it,
                // to allow TYPO3 Console (for TYPO3 v10) to set this value to something reasonable,
                // because on cli there is no way to find out which hostname the site is supposed to have.
                // In the future this controller should be refactored to a generic service, where site URL is
                // just one input argument.
                $siteUrl = $request->getParsedBody()['install']['values']['siteUrl'] ?? $normalizedParams->getSiteUrl();
                $this->setupService->createSiteConfiguration('main', (int)$pageUid, $siteUrl);
                break;
        }

        // Mark upgrade wizards as done
        $this->setupDatabaseService->markWizardsDone($container);

        $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationValues);

        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $formProtection->clean();

        EnableFileService::removeFirstInstallFile();

        return new JsonResponse([
            'success' => true,
            'redirect' => (string)$nextStepUrl,
        ]);
    }

    /**
     * Helper method to initialize a standalone view instance.
     */
    protected function initializeView(): ViewInterface
    {
        $templatePaths = [
            'templateRootPaths' => ['EXT:install/Resources/Private/Templates'],
        ];
        $renderingContext = GeneralUtility::makeInstance(RenderingContextFactory::class)->create($templatePaths);
        $fluidView = new FluidTemplateView($renderingContext);
        return new FluidViewAdapter($fluidView);
    }
}
