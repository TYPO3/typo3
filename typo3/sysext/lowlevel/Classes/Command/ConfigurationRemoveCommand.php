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

namespace TYPO3\CMS\Lowlevel\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * CLI command for removing configuration values from system/settings.php
 */
#[AsCommand('configuration:remove', 'Remove configuration value(s) from system/settings.php')]
#[AsNonSchedulableCommand]
class ConfigurationRemoveCommand extends Command
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Remove system configuration option(s) from system/settings.php.' . LF . LF
            . 'Multiple paths can be specified separated by comma.' . LF . LF
            . 'Examples:' . LF
            . '  <info>bin/typo3 configuration:remove EXTCONF/myext/setting</info>' . LF
            . '  <info>bin/typo3 configuration:remove "EXTCONF/ext1,EXTCONF/ext2" --force</info>'
        );
        $this->addArgument(
            'paths',
            InputArgument::REQUIRED,
            'Path(s) to remove, multiple paths can be separated by comma'
        );
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Skip confirmation'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pathsInput = $input->getArgument('paths');
        $paths = array_map('trim', explode(',', $pathsInput));
        $force = $input->getOption('force');

        if (!$this->configurationManager->canWriteConfiguration()) {
            $io->error('Configuration file is not writable.');
            return Command::FAILURE;
        }

        $localConfiguration = $this->configurationManager->getLocalConfiguration();
        $pathsToRemove = [];

        foreach ($paths as $path) {
            if (!ArrayUtility::isValidPath($localConfiguration, $path)) {
                $io->warning(sprintf('Path "%s" does not exist in system/settings.php. Skipping.', $path));
                continue;
            }

            if (!$force) {
                $confirm = $io->confirm(
                    sprintf('Remove "%s" from system/settings.php?', $path),
                    false
                );
                if (!$confirm) {
                    $io->note(sprintf('Skipped "%s".', $path));
                    continue;
                }
            }

            $pathsToRemove[] = $path;
        }

        if ($pathsToRemove === []) {
            $io->note('No paths to remove.');
            return Command::SUCCESS;
        }

        try {
            $result = $this->configurationManager->removeLocalConfigurationKeysByPath($pathsToRemove);
            if ($result) {
                foreach ($pathsToRemove as $path) {
                    $io->success(sprintf('Removed "%s" from system/settings.php.', $path));
                }
                return Command::SUCCESS;
            }
            $io->error('Could not remove configuration path(s).');
            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
