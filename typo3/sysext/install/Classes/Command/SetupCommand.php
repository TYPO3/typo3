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

namespace TYPO3\CMS\Install\Command;

use Doctrine\DBAL\Exception as DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\SetupDatabaseService;
use TYPO3\CMS\Install\Service\SetupService;
use TYPO3\CMS\Install\WebserverType;

/**
 * CLI command for setting up TYPO3 via CLI
 */
class SetupCommand extends Command
{
    protected array $connectionLabels = [
        'mysqli' => '[MySQLi] Manually configured MySQL TCP/IP connection',
        'mysqliSocket' => '[MySQLi] Manually configured MySQL socket connection',
        'pdoMysql' => '[PDO] Manually configured MySQL TCP/IP connection',
        'pdoMysqlSocket' => '[PDO] Manually configured MySQL socket connection',
        'postgres' => 'Manually configured PostgreSQL connection',
        'sqlite' => 'Manually configured SQLite connection',
    ];

    public function __construct(
        string $name,
        private readonly SetupDatabaseService $setupDatabaseService,
        private readonly SetupService $setupService,
        private readonly ConfigurationManager $configurationManager,
        private readonly LateBootService $lateBootService,
        private readonly FailsafePackageManager $packageManager,
    ) {
        parent::__construct($name);

    }

    protected function configure()
    {
        $this->setDescription('Setup TYPO3 via CLI using environment variables, CLI options or interactive')
            // Connection Parameters
            ->addOption(
                'driver',
                null,
                InputOption::VALUE_OPTIONAL,
                'Select which database driver to use',
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'Set the database host to use',
                'db'
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set the database port to use',
                '3306'
            )
            ->addOption(
                'dbname',
                null,
                InputOption::VALUE_REQUIRED,
                'Set the database name to use',
                'db'
            )
            ->addOption(
                'username',
                null,
                InputOption::VALUE_REQUIRED,
                'Set the database username to use',
                'db'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Set the database password to use'
            )
            // User to be created
            ->addOption(
                'admin-username',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set a username',
                'admin'
            )
            ->addOption(
                'admin-user-password',
                null,
                InputOption::VALUE_REQUIRED,
                'Set users password'
            )
            ->addOption(
                'admin-email',
                null,
                InputOption::VALUE_REQUIRED,
                'Set users email',
                ''
            )
            ->addOption(
                'project-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Set the TYPO3 project name',
                'New TYPO3 Project'
            )
            ->addOption(
                'create-site',
                null,
                InputOption::VALUE_OPTIONAL,
                'Create a basic site setup (root page and site configuration) with the given domain',
                false
            )
            ->addOption(
                'server-type',
                null,
                InputOption::VALUE_OPTIONAL,
                'Define the web server the TYPO3 installation will be running on',
                'other'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force settings overwrite - use this if TYPO3 has been installed already',
            )
            ->addOption(
                'no-interaction',
                'n',
                InputOption::VALUE_NONE,
                'Do not ask any interactive question',
            )->setHelp(
                <<<EOT
The command offers 3 ways to setup TYPO3:
 1. environment variables
 2. commandline options
 3. interactive guided walk-through

All values are validated no matter where it was set.
If a value is missing, the user will be asked for it.

<fg=green>Setup using environment variables</>
---------------------------------
TYPO3_DB_DRIVER=mysqli \
TYPO3_DB_USERNAME=db \
TYPO3_DB_PORT=3306 \
TYPO3_DB_HOST=db \
TYPO3_DB_DBNAME=db \
TYPO3_SETUP_ADMIN_EMAIL=admin@example.com \
TYPO3_SETUP_ADMIN_USERNAME=admin \
TYPO3_SETUP_CREATE_SITE="https://your-typo3-site.com/" \
TYPO3_PROJECT_NAME="Automated Setup" \
TYPO3_SERVER_TYPE="apache" \
./bin/typo3 setup --force
---------------------------------

<fg=yellow>
Variable `TYPO3_DB_PASSWORD` (option `--password`) can be used to provide a
password for the database and `TYPO3_SETUP_ADMIN_PASSWORD`
(option `--admin-user-password`) for the admin user password.
Using this can be a security risk since the password may end up in shell
history files. Prefer the interactive mode. Additionally, writing a command
to shell history can be suppressed by prefixing the command with a space
when using `bash` or `zsh`.
</>
EOT
            );
    }

    /**
     * Runs the installation / setup process
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->setInteractive(!$input->getOption('no-interaction'));
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        // Ensure all required files and folders exist
        $serverType = $this->getServerType($questionHelper, $input, $output);
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $folderStructureFactory->getStructure($serverType)->fix();
        // Ensure existing PackageStates.php for non-composer installation.
        $this->packageManager->recreatePackageStatesFileIfMissing(true);

        try {
            $force = $input->getOption('force');
            $this->setupService->prepareSystemSettings($force);
        } catch (ExistingTargetFileNameException $exception) {
            $configOverwriteQuestion = new ConfirmationQuestion(
                'Configuration already exists do you want to overwrite it [default: no] ? ',
                false
            );
            $configOverwrite = $questionHelper->ask($input, $output, $configOverwriteQuestion);

            if (!$configOverwrite) {
                return Command::FAILURE;
            }

            $this->setupService->prepareSystemSettings(true);
        }

        // Get database connection details
        $databaseConnection = $this->getConnectionDetails($questionHelper, $input, $output);

        // Select the database and prepare it
        if ($exitCode = $this->selectAndImportDatabase($questionHelper, $input, $output, $databaseConnection)) {
            return $exitCode;
        }

        $username = $this->getAdminUserName($questionHelper, $input, $output);
        $password = $this->getAdminUserPassword($questionHelper, $input, $output);
        $email = $this->getAdminEmailAddress($questionHelper, $input, $output);
        $this->setupService->createUser($username, $password, $email);
        $this->setupService->setInstallToolPassword($password);

        $siteName = $this->getProjectName($questionHelper, $input, $output);
        $this->setupService->setSiteName($siteName);

        $siteUrl = $this->getSiteSetup($questionHelper, $input, $output);
        if ($siteUrl) {
            $pageUid = $this->setupService->createSite();
            $this->setupService->createSiteConfiguration('main', (int)$pageUid, $siteUrl);
        }

        $container = $this->lateBootService->loadExtLocalconfDatabaseAndExtTables();
        $this->setupDatabaseService->markWizardsDone($container);
        $this->writeSuccess($output, 'Congratulations - TYPO3 Setup is done.');

        return Command::SUCCESS;
    }

    protected function selectAndImportDatabase(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output, mixed $databaseConnection): int
    {
        if ($databaseConnection['driver'] !== 'pdo_sqlite') {
            // Set temporary database configuration, so we are able to
            // get the available databases listed
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME] = $databaseConnection;

            try {
                $databaseList = $this->setupDatabaseService->getDatabaseList();
            } catch (DBALException $exception) {
                $this->writeError($output, $exception->getMessage());

                return Command::FAILURE;
            }

            if ($databaseList === []) {
                $this->writeError($output, 'No databases are available to the specified user. At least one usable database needs to be accessible.');
                return Command::FAILURE;
            }

            $dbChoices = [];
            foreach ($databaseList as $database) {
                $usable = $database['tables'] > 0 ? '<fg=red>☓</>' : '<fg=green>✓</>';
                $dbChoices[$database['name']] = $database['name'] . ' (Tables ' . $database['tables'] . ' ' . $usable . ')';
            }

            $dbNameValidator = static function ($dbname) use ($dbChoices, $databaseList) {
                if (!($dbChoices[$dbname] ?? false)) {
                    throw new \RuntimeException(
                        'The selected database "' . $dbname . '" is not available, pick one of these: ' . implode(
                            ', ',
                            array_keys($dbChoices)
                        ) . '.',
                        1669747192,
                    );
                }

                $selectedDatabase = $databaseList[array_search($dbname, array_keys($dbChoices), true)];
                if ($selectedDatabase['tables'] !== 0) {
                    throw new \RuntimeException(
                        'The selected database contains already ' . $selectedDatabase['tables'] . ' tables. Please delete all tables or select another database.',
                        1669747200,
                    );
                }

                return $dbname;
            };

            $dbnameFromCli = $this->getFallbackValueEnvOrOption($input, 'dbname', 'TYPO3_DB_DBNAME');
            if ($dbnameFromCli === false && $input->isInteractive()) {
                $dbname = new ChoiceQuestion('Select which database to use: ', $dbChoices);
                $dbname->setValidator($dbNameValidator);
                $databaseConnection['database'] = $questionHelper->ask($input, $output, $dbname);
            } else {
                try {
                    $dbNameValidator($dbnameFromCli);
                } catch (\RuntimeException $e) {
                    $this->writeError($output, $e->getMessage());

                    return Command::FAILURE;
                }

                $databaseConnection['database'] = $dbnameFromCli;
            }

            $checkDatabase = $this->setupDatabaseService->checkExistingDatabase($databaseConnection['database']);
            if ($checkDatabase->getSeverity() !== ContextualFeedbackSeverity::OK) {
                $this->writeError($output, $checkDatabase->getMessage());

                return Command::FAILURE;
            }
        } else {
            $databaseConnection['availableSet'] = 'sqliteManualConfiguration';
        }

        [$success, $messages] = $this->setupDatabaseService->setDefaultConnectionSettings($databaseConnection);
        if (!$success) {
            foreach ($messages as $message) {
                $this->writeError($output, $message->getMessage());
            }

            return Command::FAILURE;
        }

        // Load the actual config written to disk
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME] = $this->configurationManager->getLocalConfigurationValueByPath('DB/Connections/Default');

        $this->setupDatabaseService->checkRequiredDatabasePermissions();
        $importResults = $this->setupDatabaseService->importDatabaseData();
        foreach ($importResults as $result) {
            $this->writeError($output, (string)$result);
        }

        if (count($importResults) > 0) {
            $this->writeError($output, 'Database import failed. Please see the errors shown above');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function getConnectionDetails(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): array
    {
        $input->hasParameterOption('--driver');
        $driverTypeCli = $this->getFallbackValueEnvOrOption($input, 'driver', 'TYPO3_DB_DRIVER');
        $driverOptions = $this->setupDatabaseService->getDriverOptions();
        $availableConnectionTypes = implode(', ', array_keys($this->connectionLabels));

        $connectionValidator = static function ($connectionType) use ($driverOptions, $availableConnectionTypes) {
            if (!isset($driverOptions[$connectionType . 'ManualConfigurationOptions'])) {
                throw new \RuntimeException(
                    'The connection type "' . $connectionType . '" does not exist. Please use one of the following ' . $availableConnectionTypes,
                    1669905551,
                );
            }

            return $connectionType;
        };

        if ($driverTypeCli === false && $input->isInteractive()) {
            $driver = new ChoiceQuestion('Database driver?', $this->connectionLabels);
            $driver->setValidator($connectionValidator);
            $driverType = $questionHelper->ask($input, $output, $driver);
        } else {
            $driverType = $connectionValidator($driverTypeCli);
        }

        $databaseConnectionOptions = $driverOptions[$driverType . 'ManualConfigurationOptions'];

        // Ask for connection details
        foreach ($databaseConnectionOptions as $key => $value) {
            switch ($key) {
                case 'database':
                case 'socket':
                case 'driver':
                    break;
                case 'username':
                    $usernameFromCli = $this->getFallbackValueEnvOrOption($input, 'username', 'TYPO3_DB_USERNAME');
                    $emptyValidator = static function ($value) {
                        if (empty($value)) {
                            throw new \RuntimeException(
                                'The value must not be empty.',
                                1669747578,
                            );
                        }

                        return $value;
                    };

                    if ($usernameFromCli === false && $input->isInteractive()) {
                        $default = $this->getDefinition()->getOption($key)->getDefault();
                        $defaultLabel = ' [default: ' . $default . ']';
                        $question = new Question('Enter the database "username"' . $defaultLabel . ' ? ', $default);
                        $question->setValidator($emptyValidator);
                        $username = $questionHelper->ask($input, $output, $question);
                        // @todo: Investigate the difference between username and user.... why?
                        $databaseConnectionOptions['username'] = $username;
                        $databaseConnectionOptions['user'] = $username;
                        break;
                    }

                    $validUsername = $emptyValidator($usernameFromCli);
                    $databaseConnectionOptions['username'] = $validUsername;
                    $databaseConnectionOptions['user'] = $validUsername;
                    break;
                default:
                    $envValue = $this->getFallbackValueEnvOrOption($input, $key, 'TYPO3_DB_' . strtoupper($key));
                    $default = $this->getDefinition()->getOption($key)->getDefault();
                    $defaultLabel = empty($value) ? '' : ' [default: ' . $default . ']';
                    $question = new Question('Enter the database "' . $key . '"' . $defaultLabel . ' ? ', $default);

                    if ($key === 'password') {
                        $question = new Question('Enter the database "' . $key . '" ? ', $default);
                        $question->setHidden(true);
                        $question->setHiddenFallback(false);
                    } elseif ($key === 'host') {
                        $hostValidator = function ($host) {
                            if (!$this->setupDatabaseService->isValidDbHost($host)) {
                                throw new \RuntimeException(
                                    'Please enter a valid database host name.',
                                    1669747572
                                );
                            }

                            return $host;
                        };
                        $question->setValidator($hostValidator);
                    } elseif ($key === 'port') {
                        $portValidator = function ($port) {
                            if (!$this->setupDatabaseService->isValidDbPort((int)$port)) {
                                throw new \RuntimeException(
                                    'Please use a port in the range between 1 and 65535.',
                                    1669747592,
                                );
                            }

                            return $port;
                        };
                        $question->setValidator($portValidator);
                    } else {
                        $emptyValidator = function ($value) {
                            if (empty($value)) {
                                throw new \RuntimeException(
                                    'The value must not be empty.',
                                    1669747601,
                                );
                            }

                            return $value;
                        };
                        $question->setValidator($emptyValidator);
                    }

                    if ($envValue === false && $key === 'password') {
                        // Force this question if no `TYPO3_DB_PASSWORD` set via cli.
                        // Thus, the user will always be prompted for a password even --no-interaction is set.
                        $currentlyInteractive = $input->isInteractive();
                        $input->setInteractive(true);
                        $value = $questionHelper->ask($input, $output, $question);
                        $input->setInteractive($currentlyInteractive);
                    } elseif ($envValue === false && $input->isInteractive()) {
                        $value = $questionHelper->ask($input, $output, $question);
                    } else {
                        // All passed in values should go through the set validator,
                        // therefore, we can't break early
                        $validator = $question->getValidator();
                        $envValue = $envValue ?: $default;
                        $value = $validator ? $validator($envValue) : $envValue;
                    }

                    $databaseConnectionOptions[$key] = $value;
            }
        }

        return $databaseConnectionOptions;
    }

    protected function getServerType(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): WebserverType
    {
        $serverTypeValidator = function (string $serverType): WebserverType {
            if (!array_key_exists($serverType, WebserverType::getDescriptions())) {
                throw new \RuntimeException(
                    'Webserver must be any of ' . implode(', ', array_keys(WebserverType::getDescriptions())),
                    1682329380,
                );
            }

            return WebserverType::from($serverType);
        };
        $serverTypeFromCli = $this->getFallbackValueEnvOrOption($input, 'server-type', 'TYPO3_SERVER_TYPE');
        if ($serverTypeFromCli === false && $input->isInteractive()) {
            $questionServerType = new ChoiceQuestion('Which web server is used?', WebserverType::getDescriptions());
            $questionServerType->setValidator($serverTypeValidator);
            return $questionHelper->ask($input, $output, $questionServerType);
        }

        return $serverTypeValidator($serverTypeFromCli);
    }

    protected function getAdminUserName(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): string
    {
        $usernameValidator = static function ($username) {
            if (empty($username)) {
                throw new \RuntimeException(
                    'Admin username must not be empty.',
                    1669747607,
                );
            }

            return $username;
        };

        $usernameFromCli = $this->getFallbackValueEnvOrOption($input, 'admin-username', 'TYPO3_SETUP_ADMIN_USERNAME');
        if ($usernameFromCli === false && $input->isInteractive()) {
            $questionUsername = new Question('Admin username (user will be "system maintainer") ? ');
            $questionUsername->setValidator($usernameValidator);
            return $questionHelper->ask($input, $output, $questionUsername);
        }

        // Use default value for 'admin-username' if in non-interactive mode
        if ($usernameFromCli === false && !$input->isInteractive()) {
            $usernameFromCli = $this->getDefinition()->getOption('admin-username')->getDefault();
        }

        return $usernameValidator($usernameFromCli);
    }

    protected function getAdminUserPassword(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): string
    {
        $passwordValidator = function ($password) {
            $passwordValidationErrors = $this->setupDatabaseService->getBackendUserPasswordValidationErrors((string)$password);
            if (!empty($passwordValidationErrors)) {
                throw new \RuntimeException(
                    'Administrator password not secure enough!' . PHP_EOL
                    . '* ' . implode(PHP_EOL . '* ', $passwordValidationErrors),
                    1669747614,
                );
            }

            return $password;
        };

        $passwordFromCli = $this->getFallbackValueEnvOrOption($input, 'admin-user-password', 'TYPO3_SETUP_ADMIN_PASSWORD');
        if ($passwordFromCli === false) {
            // Force this question if `TYPO3_SETUP_ADMIN_PASSWORD` is not set via cli.
            // Thus, the user will always be prompted for a password even --no-interaction is set.
            $currentlyInteractive = $input->isInteractive();
            $input->setInteractive(true);
            $questionPassword = new Question('Admin user and installer password ? ');
            $questionPassword->setHidden(true);
            $questionPassword->setHiddenFallback(false);
            $questionPassword->setValidator($passwordValidator);
            $password = $questionHelper->ask($input, $output, $questionPassword);
            $input->setInteractive($currentlyInteractive);

            return $password;
        }

        return $passwordValidator($passwordFromCli);
    }

    protected function getAdminEmailAddress(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): string
    {
        $emailValidator = static function ($email) {
            if (!empty($email) && !GeneralUtility::validEmail($email)) {
                throw new \RuntimeException(
                    'The given Email is not valid! Please try again.',
                    1669747620,
                );
            }

            return $email;
        };

        $emailFromCli = $this->getFallbackValueEnvOrOption($input, 'admin-email', 'TYPO3_SETUP_ADMIN_EMAIL');
        if ($emailFromCli === false && $input->isInteractive()) {
            $questionEmail = new Question('Admin user email ? ', '');
            $questionEmail->setValidator($emailValidator);

            return $questionHelper->ask($input, $output, $questionEmail);
        }

        return (string)$emailValidator($emailFromCli);
    }

    protected function getProjectName(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): string
    {
        $nameFromCli = $this->getFallbackValueEnvOrOption($input, 'project-name', 'TYPO3_PROJECT_NAME');
        $defaultProjectName = $this->getDefinition()->getOption('project-name')->getDefault();

        if ($nameFromCli === false && $input->isInteractive()) {
            $question = new Question(
                'Give your project a name [default: ' . $defaultProjectName . '] ? ',
                $defaultProjectName
            );

            return $questionHelper->ask($input, $output, $question);
        }

        return $nameFromCli ?: $defaultProjectName;
    }

    protected function getSiteSetup(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): string|bool
    {
        $urlValidator = static function ($url) {
            if ($url && !GeneralUtility::isValidUrl($url)) {
                throw new \RuntimeException(
                    'The given url for the site name is not valid! Please try again.',
                    1669747625,
                );
            }

            return $url;
        };

        $createSiteFromCli = $this->getFallbackValueEnvOrOption($input, 'create-site', 'TYPO3_SETUP_CREATE_SITE');

        if ($createSiteFromCli === false && $input->isInteractive()) {
            $questionCreateSite = new Question('Create a basic site? Please enter a URL [default: no] ', false);
            $questionCreateSite->setValidator($urlValidator);

            return $questionHelper->ask($input, $output, $questionCreateSite);
        }

        return $urlValidator($createSiteFromCli);
    }

    protected function writeSuccess(OutputInterface $output, string $message): void
    {
        $output->writeln('<fg=green>✓</> ' . $message);
    }

    protected function writeError(OutputInterface $output, string $message): void
    {
        $output->writeln('<fg=red>☓</> [Error]: ' . $message);
    }

    /**
     * Get a value from
     * 1. environment variable
     * 2. cli option
     */
    protected function getFallbackValueEnvOrOption(InputInterface $input, string $option, string $envVar): string|false
    {
        return $input->hasParameterOption('--' . $option) ? $input->getOption($option) : getenv($envVar);
    }
}
