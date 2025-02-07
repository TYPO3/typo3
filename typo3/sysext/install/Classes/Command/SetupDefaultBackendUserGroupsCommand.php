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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Install\Service\SetupService;

final class SetupDefaultBackendUserGroupsCommand extends Command
{
    private BackendUserGroupType $userGroupEnum = BackendUserGroupType::ALL;
    private array $availableUserGroups = [];

    public function __construct(
        string $name,
        private readonly SetupService $setupService,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->availableUserGroups = $this->userGroupEnum->getAllUserGroupTypes();
        $actualGroups = $this->userGroupEnum->getActualUserGroupTypes();
        $stringUserGroupList = $actualGroups !== []
            ? '- ' . implode("\n- ", $actualGroups)
            : 'No groups available.';
        $this->setDescription('Setup default backend user groups')
            ->addOption(
                'no-interaction',
                'n',
                InputOption::VALUE_NONE,
                'Do not ask any interactive question',
            )->addOption(
                'groups',
                'g',
                InputOption::VALUE_OPTIONAL,
                'Which backend user groups do you want to create? [ ' . implode(', ', $this->availableUserGroups) . ']',
                $this->userGroupEnum->value,
                $this->availableUserGroups
            )->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force creating a new group with the same name, even if a group with that name already exists.'
            )->setHelp(
                <<<EOT
The command will allow you to create base backend user groups for your TYPO3 installation.

You can create either both or one of the following groups:

$stringUserGroupList

EOT
            );
    }

    /**
     * Runs the backend groups setup command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->setInteractive(!$input->getOption('no-interaction'));
        $io = new SymfonyStyle($input, $output);

        $createGroups = $this->userGroupEnum->value;
        if ($input->hasParameterOption('--groups') || $input->hasParameterOption('-g')) {
            $createGroups = $input->getOption('groups');
        } elseif (!$input->getOption('no-interaction')) {
            $createGroups = $io->choice(
                'Which backend groups do you want to create?',
                $this->availableUserGroups,
                $this->userGroupEnum->value
            );
        }
        if ($createGroups === BackendUserGroupType::NONE->value) {
            $io->info('No backend groups have been created.');
            return Command::SUCCESS;
        }
        if (!in_array($createGroups, $this->availableUserGroups, true)) {
            $io->error('Invalid user group specified.');
            return Command::FAILURE;
        }

        $createEditor = false;
        $createAdvancedEditor = false;
        $creationNotices = [];
        if ($createGroups === BackendUserGroupType::EDITOR->value || $createGroups === BackendUserGroupType::ALL->value) {
            $createEditor = true;
            $creationNotices[] = BackendUserGroupType::EDITOR->value;
        }
        if ($createGroups === BackendUserGroupType::ADVANCED_EDITOR->value || $createGroups === BackendUserGroupType::ALL->value) {
            $createAdvancedEditor = true;
            $creationNotices[] = BackendUserGroupType::ADVANCED_EDITOR->value;
        }
        $messages = $this->setupService->createBackendUserGroups($createEditor, $createAdvancedEditor, $input->hasParameterOption('-f'));

        if ($messages !== []) {
            foreach ($messages as $message) {
                $io->warning($message);
            }
            return Command::FAILURE;
        }
        $io->success(sprintf('Backend user group(s) created: %s', implode(', ', $creationNotices)));
        return Command::SUCCESS;
    }
}
