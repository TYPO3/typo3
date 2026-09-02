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

/**
 * CLI command for setting a configuration value in system/settings.php
 */
#[AsCommand('configuration:set', 'Set a configuration value in system/settings.php')]
#[AsNonSchedulableCommand]
class ConfigurationSetCommand extends Command
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Set a system configuration option in system/settings.php.' . LF . LF
            . 'Examples:' . LF
            . '  <info>bin/typo3 configuration:set SYS/sitename "My Site"</info>' . LF
            . '  <info>bin/typo3 configuration:set BE/debug true --json</info>' . LF
            . '  <info>bin/typo3 configuration:set SYS/features/myFeature true --json</info>'
        );
        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Path to the configuration value (e.g., SYS/sitename or BE/debug)'
        );
        $this->addArgument(
            'value',
            InputArgument::REQUIRED,
            'Value to set'
        );
        $this->addOption(
            'json',
            null,
            InputOption::VALUE_NONE,
            'Parse the value as JSON (allows setting booleans, integers, arrays)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');
        $value = $input->getArgument('value');
        $parseJson = $input->getOption('json');

        if ($parseJson) {
            $decodedValue = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $io->error('Invalid JSON value: ' . json_last_error_msg());
                return Command::FAILURE;
            }
            $value = $decodedValue;
        }

        if (!$this->configurationManager->canWriteConfiguration()) {
            $io->error('Configuration file is not writable.');
            return Command::FAILURE;
        }

        try {
            $result = $this->configurationManager->setLocalConfigurationValueByPath($path, $value);
            if ($result) {
                $io->success(sprintf('Successfully set "%s" in system/settings.php.', $path));
                return Command::SUCCESS;
            }
            $io->error(sprintf(
                'Could not set "%s". The path may be invalid or not allowed to be written.',
                $path
            ));
            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
