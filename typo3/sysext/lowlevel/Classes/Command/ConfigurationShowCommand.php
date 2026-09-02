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
 * CLI command for showing configuration values
 */
#[AsCommand('configuration:show', 'Show configuration value')]
#[AsNonSchedulableCommand]
class ConfigurationShowCommand extends Command
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Show a system configuration value.' . LF . LF
            . 'By default, if the active value differs from the local value,' . LF
            . 'both values are shown with the difference highlighted.' . LF . LF
            . 'Use --type to show a specific configuration source:' . LF
            . '  active - effective runtime value from $GLOBALS[\'TYPO3_CONF_VARS\']' . LF
            . '  local  - value from system/settings.php only' . LF . LF
            . 'Examples:' . LF
            . '  <info>bin/typo3 configuration:show SYS/sitename</info>' . LF
            . '  <info>bin/typo3 configuration:show SYS/sitename --type=active</info>' . LF
            . '  <info>bin/typo3 configuration:show DB/Connections/Default --type=local --json</info>'
        );
        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Path to the configuration value (e.g., SYS/sitename)'
        );
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_REQUIRED,
            'Configuration source: "active" (effective runtime) or "local" (settings.php only)'
        );
        $this->addOption(
            'json',
            null,
            InputOption::VALUE_NONE,
            'Output as JSON'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');
        $type = $input->getOption('type');
        $asJson = $input->getOption('json');

        return match ($type) {
            'active' => $this->showActive($path, $asJson, $io, $output),
            'local' => $this->showLocal($path, $asJson, $io, $output),
            null => $this->showWithDiff($path, $io, $output),
            default => $this->invalidType($type, $io),
        };
    }

    private function showActive(string $path, bool $asJson, SymfonyStyle $io, OutputInterface $output): int
    {
        if (!ArrayUtility::isValidPath($GLOBALS['TYPO3_CONF_VARS'], $path)) {
            $io->error(sprintf('No configuration found for path "%s".', $path));
            return Command::FAILURE;
        }

        $value = ArrayUtility::getValueByPath($GLOBALS['TYPO3_CONF_VARS'], $path);
        $output->writeln($this->renderValue($value, $asJson));
        return Command::SUCCESS;
    }

    private function showLocal(string $path, bool $asJson, SymfonyStyle $io, OutputInterface $output): int
    {
        $localConfiguration = $this->configurationManager->getLocalConfiguration();

        if (!ArrayUtility::isValidPath($localConfiguration, $path)) {
            $io->error(sprintf('No configuration found for path "%s" in system/settings.php.', $path));
            return Command::FAILURE;
        }

        $value = ArrayUtility::getValueByPath($localConfiguration, $path);
        $output->writeln($this->renderValue($value, $asJson));
        return Command::SUCCESS;
    }

    private function showWithDiff(string $path, SymfonyStyle $io, OutputInterface $output): int
    {
        $localConfiguration = $this->configurationManager->getLocalConfiguration();
        $hasLocal = ArrayUtility::isValidPath($localConfiguration, $path);
        $hasActive = ArrayUtility::isValidPath($GLOBALS['TYPO3_CONF_VARS'], $path);

        if (!$hasLocal && !$hasActive) {
            $io->error(sprintf('No configuration found for path "%s".', $path));
            return Command::FAILURE;
        }

        $localValue = $hasLocal ? ArrayUtility::getValueByPath($localConfiguration, $path) : null;
        $activeValue = $hasActive ? ArrayUtility::getValueByPath($GLOBALS['TYPO3_CONF_VARS'], $path) : null;

        // Check if local value equals active value
        if ($hasLocal && $hasActive && $this->valuesAreEqual($localValue, $activeValue)) {
            $output->writeln($this->renderValue($activeValue, false));
            return Command::SUCCESS;
        }

        // Values differ or one is missing - show diff
        $io->section('Configuration differs between local and active');

        if ($hasLocal) {
            $io->writeln('<fg=red>-- system/settings.php:</>');
            $io->writeln($this->renderValue($localValue, false));
        } else {
            $io->writeln('<fg=red>-- system/settings.php: (not set)</>');
        }

        $io->newLine();

        if ($hasActive) {
            $io->writeln('<fg=green>++ active (effective) value:</>');
            $io->writeln($this->renderValue($activeValue, false));
        } else {
            $io->writeln('<fg=green>++ active (effective) value: (not set)</>');
        }

        return Command::SUCCESS;
    }

    private function invalidType(string $type, SymfonyStyle $io): int
    {
        $io->error(sprintf('Invalid type "%s". Use "active" or "local".', $type));
        return Command::FAILURE;
    }

    private function valuesAreEqual(mixed $a, mixed $b): bool
    {
        if (is_array($a) && is_array($b)) {
            return $this->arraysAreEqual($a, $b);
        }
        return $a === $b;
    }

    private function arraysAreEqual(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        foreach ($a as $key => $value) {
            if (!array_key_exists($key, $b)) {
                return false;
            }
            if (!$this->valuesAreEqual($value, $b[$key])) {
                return false;
            }
        }
        return true;
    }

    private function renderValue(mixed $value, bool $asJson): string
    {
        if ($asJson) {
            return (string)json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if (is_array($value)) {
            return ArrayUtility::arrayExport($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return 'null';
        }
        return (string)$value;
    }
}
