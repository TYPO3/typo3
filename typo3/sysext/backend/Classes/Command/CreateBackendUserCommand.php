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

namespace TYPO3\CMS\Backend\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyValidator;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Create a new backend user
 */
class CreateBackendUserCommand extends Command
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ConfigurationManager $configurationManager,
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly PasswordHashFactory $passwordHashFactory,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption(
                'username',
                'u',
                InputOption::VALUE_REQUIRED,
                'The username of the backend user',
            )->addOption(
                'password',
                'p',
                InputOption::VALUE_REQUIRED,
                'The password of the backend user. See security note below.',
            )->addOption(
                'email',
                'e',
                InputOption::VALUE_REQUIRED,
                'The email address of the backend user',
                '',
            )
            ->addOption(
                'groups',
                'g',
                InputOption::VALUE_REQUIRED,
                'Assign given groups to the user'
            )
            ->addOption(
                'admin',
                'a',
                InputOption::VALUE_NONE,
                'Create user with admin privileges'
            )->addOption(
                'maintainer',
                'm',
                InputOption::VALUE_NONE,
                'Create user with maintainer privileges',
            )->setHelp(
                <<<EOT

<fg=green>Create a backend user using environment variables</>

Example:
-------------------------------------------------
TYPO3_BE_USER_NAME=username \
TYPO3_BE_USER_EMAIL=admin@example.com \
TYPO3_BE_USER_GROUPS=<comma-separated-list-of-group-ids> \
TYPO3_BE_USER_ADMIN=0 \
TYPO3_BE_USER_MAINTAINER=0 \
./bin/typo3 backend:user:create --no-interaction
-------------------------------------------------
<fg=yellow>
Variable "TYPO3_BE_USER_PASSWORD" and options "-p" or "--password" can be
used to provide a password. Using this can be a security risk since the password
may end up in shell history files. Prefer the interactive mode. Additionally,
writing a command to shell history can be suppressed by prefixing the command
with a space when using `bash` or `zsh`.
</>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->setInteractive(!$input->getOption('no-interaction'));

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $username = $this->getUsername($questionHelper, $input, $output);
        $password = $this->getPassword($questionHelper, $input, $output);
        $email = $this->getEmail($questionHelper, $input, $output) ?: '';
        $maintainer = $this->getMaintainer($questionHelper, $input, $output);

        // If the user is 'maintainer' it is also required to set the 'admin' flag.
        if ($maintainer) {
            $admin = true;
        } else {
            $admin = $this->getAdmin($questionHelper, $input, $output);
        }

        // If 'admin' flag was set, this prompt is skipped.
        // Because this user does already have access to the entire system.
        if ($admin) {
            $groups = [];
        } else {
            $groups = $this->getGroups($questionHelper, $input, $output);
        }

        $this->createUser($username, $password, $email, $admin, $maintainer, $groups);

        return Command::SUCCESS;
    }

    private function getUsername(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): string
    {
        // Taking deleted users into account as we want the username to be unique.
        // So in case a user was deleted and will be restored, this could cause duplicated usernames.
        $queryBuilder = $this->connectionPool->getConnectionForTable('be_users');
        $userList = $queryBuilder->select(['username'], 'be_users')->fetchAllAssociative();
        $usernames = array_map(static function ($user) {
            return $user['username'];
        }, $userList);

        $usernameValidator = static function ($username) use ($usernames) {
            if (empty($username)) {
                throw new \RuntimeException(
                    'Backend username must not be empty.',
                    1669822315,
                );
            }

            if (in_array($username, $usernames, true)) {
                throw new \RuntimeException(
                    'The username "' . $username . '" is already taken. Please use another username.',
                    1670797516,
                );
            }

            return $username;
        };

        $usernameFromCli = $this->getFallbackValueEnvOrOption($input, 'username', 'TYPO3_BE_USER_NAME');
        if ($usernameFromCli === false && $input->isInteractive()) {
            $questionUsername = new Question('Enter the backend username of the new account: ');
            $questionUsername->setValidator($usernameValidator);

            return $questionHelper->ask($input, $output, $questionUsername);
        }

        return $usernameValidator($usernameFromCli);
    }

    private function getPassword(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): string
    {
        $passwordValidator = function ($password) {
            $passwordValidationErrors = $this->getBackendUserPasswordValidationErrors((string)$password);
            if (!empty($passwordValidationErrors)) {
                throw new \RuntimeException(
                    'The given password is not secure enough!' . PHP_EOL
                    . ' * ' . implode(PHP_EOL . ' * ', $passwordValidationErrors),
                    1670267532,
                );
            }

            return $password;
        };

        $passwordFromCli = $this->getFallbackValueEnvOrOption($input, 'password', 'TYPO3_BE_USER_PASSWORD');

        // Force this question if no password set via cli.
        // Thus, the user will always be prompted for a password even --no-interaction is set.
        $currentlyInteractive = $input->isInteractive();
        $input->setInteractive(true);
        if ($passwordFromCli === false) {
            $questionPassword = new Question('Enter a password for the backend user: ');
            $questionPassword->setHidden(true);
            $questionPassword->setHiddenFallback(false);
            $questionPassword->setValidator($passwordValidator);

            return $questionHelper->ask($input, $output, $questionPassword);
        }
        $input->setInteractive($currentlyInteractive);

        return $passwordValidator($passwordFromCli);
    }

    private function getEmail(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): string
    {
        $emailValidator = static function ($email) {
            if (!empty($email) && !GeneralUtility::validEmail($email)) {
                throw new \RuntimeException(
                    'The given email is not valid! Please try again.',
                    1669813635,
                );
            }

            return $email;
        };

        $emailFromCli = $this->getFallbackValueEnvOrOption($input, 'email', 'TYPO3_BE_USER_EMAIL');
        if ($emailFromCli === false && $input->isInteractive()) {
            $questionEmail = new Question('Enter the email for the backend user: ', '');
            $questionEmail->setValidator($emailValidator);

            return $questionHelper->ask($input, $output, $questionEmail);
        }

        return (string)$emailValidator($emailFromCli);
    }

    private function getGroups(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): array
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('be_groups');
        $groupsList = $queryBuilder->select(['uid', 'title'], 'be_groups')->fetchAllAssociative();

        $groupChoices = [];
        foreach ($groupsList as $group) {
            $groupChoices[$group['uid']] = $group['title'];
        }

        $groupValidator = static function ($groupList) use ($groupChoices) {
            $groups = GeneralUtility::intExplode(',', $groupList ?: '');
            foreach ($groups as $group) {
                if (!empty($group) && !isset($groupChoices[$group])) {
                    throw new \RuntimeException(
                        'The given group uid "' . $group . '"  does not exist.',
                        1670812929,
                    );
                }
            }

            return $groups;
        };

        $groupsFromCli = $this->getFallbackValueEnvOrOption($input, 'groups', 'TYPO3_BE_USER_GROUPS');
        if ($groupsFromCli === false && $input->isInteractive()) {
            if (empty($groupChoices)) {
                return [];
            }

            $questionGroups = new ChoiceQuestion('Select groups the newly created backend user should be assigned to (use comma seperated list for multiple groups): ', $groupChoices);
            $questionGroups->setMultiselect(true);
            $questionGroups->setValidator($groupValidator);
            // Ensure keys are selected and not the values
            $questionGroups->setAutocompleterValues(array_keys($groupChoices));
            return $questionHelper->ask($input, $output, $questionGroups);
        }

        return $groupValidator($groupsFromCli);
    }

    private function getMaintainer(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): bool
    {
        $maintainerFromCli = $this->getFallbackValueEnvOrOption($input, 'maintainer', 'TYPO3_BE_USER_MAINTAINER');
        if ($maintainerFromCli === false && $input->isInteractive()) {
            $questionMaintainer = new ConfirmationQuestion('Create user with maintainer privileges [y/n default: n] ? ', false);
            return (bool)$questionHelper->ask($input, $output, $questionMaintainer);
        }

        return (bool)$maintainerFromCli;
    }

    private function getAdmin(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output): bool
    {
        $adminFromCli = $this->getFallbackValueEnvOrOption($input, 'admin', 'TYPO3_BE_USER_ADMIN');
        if ($adminFromCli === false && $input->isInteractive()) {
            $questionAdmin = new ConfirmationQuestion('Create user with admin privileges [y/n default: n] ? ', false);
            return (bool)$questionHelper->ask($input, $output, $questionAdmin);
        }

        return (bool)$adminFromCli;
    }

    /**
     * Get a value from
     * 1. environment variable
     * 2. cli option
     */
    private function getFallbackValueEnvOrOption(InputInterface $input, string $option, string $envVar): string|bool
    {
        return $input->hasParameterOption('--' . $option) ? $input->getOption($option) : getenv($envVar);
    }

    private function getBackendUserPasswordValidationErrors(string $password): array
    {
        $GLOBALS['LANG'] = $this->languageServiceFactory->create('default');
        $passwordPolicy = $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] ?? 'default';
        $passwordPolicyValidator = new PasswordPolicyValidator(
            PasswordPolicyAction::NEW_USER_PASSWORD,
            is_string($passwordPolicy) ? $passwordPolicy : ''
        );
        $contextData = new ContextData();
        $passwordPolicyValidator->isValidPassword($password, $contextData);

        return $passwordPolicyValidator->getValidationErrors();
    }

    /**
     * Create a backend user.
     * similar to "\TYPO3\CMS\Install\Service\SetupService::createUser()",
     * but accepts admin/maintainer flag and groups
     */
    private function createUser(string $username, string $password, string $email = '', bool $admin = false, bool $maintainer = false, array $groups = []): void
    {
        $adminUserFields = [
            'username' => $username,
            'password' => $this->passwordHashFactory->getDefaultHashInstance('BE')->getHashedPassword($password),
            'email' => GeneralUtility::validEmail($email) ? $email : '',
            'admin' => $admin ? 1 : 0,
            'usergroup' => empty($groups) ? null : implode(',', $groups),
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
        ];

        $databaseConnection = $this->connectionPool->getConnectionForTable('be_users');
        $databaseConnection->insert('be_users', $adminUserFields);
        $adminUserUid = (int)$databaseConnection->lastInsertId('be_users');

        if ($maintainer) {
            $maintainerIds = $this->configurationManager->getConfigurationValueByPath('SYS/systemMaintainers') ?? [];
            sort($maintainerIds);
            $maintainerIds[] = $adminUserUid;
            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs([
                'SYS/systemMaintainers' => array_unique($maintainerIds),
            ]);
        }
    }
}
