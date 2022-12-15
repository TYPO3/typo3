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
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\Service\SetupDatabaseService;
use TYPO3\CMS\Install\Service\SetupService;

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

    protected SetupDatabaseService $setupDatabaseService;
    protected SetupService $setupService;
    protected ConfigurationManager $configurationManager;
    protected OutputInterface $output;
    protected InputInterface $input;
    protected QuestionHelper $questionHelper;

    public function __construct(
        string $name,
        SetupDatabaseService $setupDatabaseService,
        SetupService $setupService,
        ConfigurationManager $configurationManager,
    ) {
        $this->setupDatabaseService = $setupDatabaseService;
        $this->setupService = $setupService;
        $this->configurationManager = $configurationManager;
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
                InputOption::VALUE_REQUIRED,
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
                InputOption::VALUE_REQUIRED,
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
TYPO3_DB_PASSWORD=db \
TYPO3_DB_PORT=3306 \
TYPO3_DB_HOST=db \
TYPO3_DB_DBNAME=db \
TYPO3_SETUP_ADMIN_EMAIL=admin@email.com \
TYPO3_SETUP_ADMIN_USERNAME=admin \
TYPO3_SETUP_PASSWORD=password \
TYPO3_PROJECT_NAME="Automated Setup" \
TYPO3_CREATE_SITE="https://your-typo3-site.com/" \
./bin/typo3 setup --force
---------------------------------

EOT
            );
    }

    /**
     * Runs the installation / setup process
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;
        $this->input->setInteractive(!$input->getOption('no-interaction'));
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $this->questionHelper = $questionHelper;

        // Ensure all required files and folders exist
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $folderStructureFactory->getStructure()->fix();

        try {
            $force = $input->getOption('force');
            $this->setupService->prepareSystemSettings($force);
        } catch (ExistingTargetFileNameException $exception) {
            $configOverwriteQuestion = new ConfirmationQuestion(
                'Configuration already exists do you want to overwrite it [default: no] ? ',
                false
            );
            $configOverwrite = $this->questionHelper->ask($input, $output, $configOverwriteQuestion);

            if (!$configOverwrite) {
                return Command::FAILURE;
            }

            $this->setupService->prepareSystemSettings(true);
        }

        // Get database connection details
        $databaseConnection = $this->getConnectionDetails();

        // Select the database and prepare it
        if ($exitCode = $this->selectAndImportDatabase($databaseConnection)) {
            return $exitCode;
        }

        $username = $this->getAdminUserName();
        $password = $this->getAdminUserPassword();
        $email = $this->getAdminEmailAddress();
        try {
            $this->setupService->createUser($username, $password, $email, true, true);
            $this->setupService->setInstallToolPassword($password);
        } catch (\RuntimeException $exception) {
            $this->writeError($exception->getMessage());

            return Command::FAILURE;
        }

        $siteName = $this->getProjectName();
        $this->setupService->setSiteName($siteName);

        $siteUrl = $this->getSiteSetup();
        if ($siteUrl) {
            $pageUid = $this->setupService->createSite();
            $this->setupService->createSiteConfiguration('main', (int)$pageUid, $siteUrl);
        }

        $this->setupDatabaseService->markWizardsDone();
        $this->writeSuccess('Congratulations - TYPO3 Setup is done.');

        return Command::SUCCESS;
    }

    protected function selectAndImportDatabase(mixed $databaseConnection): int
    {
        if ($databaseConnection['driver'] !== 'pdo_sqlite') {
            // Set temporary database configuration, so we are able to
            // get the available databases listed
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME] = $databaseConnection;

            try {
                $databaseList = $this->setupDatabaseService->getDatabaseList();
            } catch (DBALException $exception) {
                $this->writeError($exception->getMessage());

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

            $dbnameFromCli = $this->getFallbackValueEnvOrOption('dbname', 'TYPO3_SETUP_DBNAME');
            if ($dbnameFromCli === false && $this->input->isInteractive()) {
                $dbname = new ChoiceQuestion('Select which database to use: ', $dbChoices);
                $dbname->setValidator($dbNameValidator);
                $databaseConnection['database'] = $this->questionHelper->ask($this->input, $this->output, $dbname);
            } else {
                $dbNameValidator($dbnameFromCli);
                $databaseConnection['database'] = $dbnameFromCli;
            }

            $checkDatabase = $this->setupDatabaseService->checkExistingDatabase($databaseConnection['database']);
            if ($checkDatabase->getSeverity() !== ContextualFeedbackSeverity::OK) {
                $this->writeError($checkDatabase->getMessage());

                return Command::FAILURE;
            }
        } else {
            $databaseConnection['availableSet'] = 'sqliteManualConfiguration';
        }

        [$success, $messages] = $this->setupDatabaseService->setDefaultConnectionSettings($databaseConnection);
        if (!$success) {
            foreach ($messages as $message) {
                $this->writeError($message->getMessage());
            }

            return Command::FAILURE;
        }

        // Load the actual config written to disk
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME] = $this->configurationManager->getLocalConfigurationValueByPath('DB/Connections/Default');

        $this->setupDatabaseService->checkRequiredDatabasePermissions();
        $importResults = $this->setupDatabaseService->importDatabaseData();
        foreach ($importResults as $result) {
            $this->writeError((string)$result);
        }

        if (count($importResults) > 0) {
            $this->writeError('Database import failed. Please see the errors shown above');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function getConnectionDetails(): array
    {
        $this->input->hasParameterOption('--driver');
        $driverTypeCli = $this->getFallbackValueEnvOrOption('driver', 'TYPO3_DB_DRIVER');
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

        if ($driverTypeCli === false && $this->input->isInteractive()) {
            $driver = new ChoiceQuestion('Database driver?', $this->connectionLabels);
            $driver->setValidator($connectionValidator);
            $driverType = $this->questionHelper->ask($this->input, $this->output, $driver);
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
                    $usernameFromCli = $this->getFallbackValueEnvOrOption('username', 'TYPO3_DB_USERNAME');
                    $emptyValidator = static function ($value) {
                        if (empty($value)) {
                            throw new \RuntimeException(
                                'The value must not be empty.',
                                1669747578,
                            );
                        }

                        return $value;
                    };

                    if ($usernameFromCli === false && $this->input->isInteractive()) {
                        $default = $this->getDefinition()->getOption($key)->getDefault();
                        $defaultLabel = ' [default: ' . $default . ']';
                        $question = new Question('Enter the database "username"' . $defaultLabel . ' ? ', $default);
                        $question->setValidator($emptyValidator);
                        $username = $this->questionHelper->ask($this->input, $this->output, $question);
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
                    $envValue = $this->getFallbackValueEnvOrOption($key, 'TYPO3_DB_' . strtoupper($key));
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

                    if ($envValue === false && $this->input->isInteractive()) {
                        $value = $this->questionHelper->ask($this->input, $this->output, $question);
                    } else {
                        // All passed in values should go through the set validator,
                        // therefore, we can't break early
                        $validator = $question->getValidator();
                        $value = $validator ? $validator($envValue) : $envValue;
                    }

                    $databaseConnectionOptions[$key] = $value;
            }
        }

        return $databaseConnectionOptions;
    }

    protected function getAdminUserName(): string
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

        $usernameFromCli = $this->getFallbackValueEnvOrOption('admin-username', 'TYPO3_SETUP_ADMIN_USERNAME');
        if ($usernameFromCli === false && $this->input->isInteractive()) {
            $questionUsername = new Question('Admin username (user will be "system maintainer") ? ');
            $questionUsername->setValidator($usernameValidator);
            return $this->questionHelper->ask($this->input, $this->output, $questionUsername);
        }

        return $usernameValidator($usernameFromCli);
    }

    protected function getAdminUserPassword(): string
    {
        $passwordValidator = function ($password) {
            if (!$this->setupDatabaseService->isValidBackendUserPassword((string)$password)) {
                throw new \RuntimeException(
                    'Please use a password with at least 8 characters.',
                    1669747614,
                );
            }

            return $password;
        };

        $passwordFromCli = $this->getFallbackValueEnvOrOption('admin-user-password', 'TYPO3_SETUP_PASSWORD');
        if ($passwordFromCli === false && $this->input->isInteractive()) {
            $questionPassword = new Question('Admin user and installer password ? ');
            $questionPassword->setHidden(true);
            $questionPassword->setHiddenFallback(false);
            $questionPassword->setValidator($passwordValidator);

            return $this->questionHelper->ask($this->input, $this->output, $questionPassword);
        }

        return $passwordValidator($passwordFromCli);
    }

    protected function getAdminEmailAddress(): string|false
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

        $emailFromCli = $this->getFallbackValueEnvOrOption('admin-email', 'TYPO3_SETUP_ADMIN_EMAIL');
        if ($emailFromCli === false && $this->input->isInteractive()) {
            $questionEmail = new Question('Admin user email ? ', '');
            $questionEmail->setValidator($emailValidator);

            return $this->questionHelper->ask($this->input, $this->output, $questionEmail);
        }

        return $emailValidator($emailFromCli);
    }

    protected function getProjectName(): string
    {
        $nameFromCli = $this->getFallbackValueEnvOrOption('project-name', 'TYPO3_PROJECT_NAME');
        $defaultProjectName = $this->getDefinition()->getOption('project-name')->getDefault();

        if ($nameFromCli === false && $this->input->isInteractive()) {
            $question = new Question(
                'Give your project a name [default: ' . $defaultProjectName . '] ? ',
                $defaultProjectName
            );

            return $this->questionHelper->ask($this->input, $this->output, $question);
        }

        return $nameFromCli ?: $defaultProjectName;
    }

    protected function getSiteSetup(): string|bool
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

        $createSiteFromCli = $this->getFallbackValueEnvOrOption('create-site', 'TYPO3_SETUP_CREATE_SITE');

        if ($createSiteFromCli === false && $this->input->isInteractive()) {
            $questionCreateSite = new Question('Create a basic site? Please enter a URL [default: no] ', false);
            $questionCreateSite->setValidator($urlValidator);

            return $this->questionHelper->ask($this->input, $this->output, $questionCreateSite);
        }

        return $urlValidator($createSiteFromCli);
    }

    protected function writeSuccess(string $message): void
    {
        $this->output->writeln('<fg=green>✓</> ' . $message);
    }

    protected function writeError(string $message): void
    {
        $this->output->writeln('<fg=red>☓</> [Error]: ' . $message);
    }

    /**
     * Get a value from
     * 1. environment variable
     * 2. cli option
     */
    protected function getFallbackValueEnvOrOption(string $option, string $envVar): string|false
    {
        return $this->input->hasParameterOption('--' . $option) ? $this->input->getOption($option) : getenv($envVar);
    }
}
