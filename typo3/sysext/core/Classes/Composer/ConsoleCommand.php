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

namespace TYPO3\CMS\Core\Composer;

use Composer\Pcre\Preg;
use Composer\Script\Event;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;

final readonly class ConsoleCommand implements InstallerScript
{
    public function __construct(
        private array $command,
        private string $message = '',
    ) {}

    public function run(Event $event): bool
    {
        $io = $event->getIO();
        if ($this->message) {
            $io->writeError(sprintf('<info>%s</info>', $this->message));
        }
        try {
            $this->executeProcess($event);
        } catch (CommandExecutionFailedException $e) {
            $io->writeError(sprintf('<error>%s</error>', $e->getMessage()));
            return false;
        }
        return true;
    }

    private function executeProcess(Event $event): void
    {
        $io = $event->getIO();
        $typo3Command = $this->getTypo3Command($event);
        array_unshift($typo3Command, ...$this->getPhpExecCommand());

        $process = new ProcessExecutor($io);
        $exitCode = $process->execute($typo3Command, $commandOutput);
        if ($exitCode !== 0) {
            $errorOutput = trim($commandOutput);
            if ($process->getErrorOutput() !== '') {
                $errorOutput .= chr(10) . $process->getErrorOutput();
            }
            throw new CommandExecutionFailedException(
                $this->command,
                $errorOutput,
                1765283208,
            );
        }
        $io->writeError($commandOutput, false);
    }

    private function getTypo3Command(Event $event): array
    {
        $composer = $event->getComposer();
        $binDir = $composer->getConfig()->get('bin-dir');

        $finder = new ExecutableFinder();
        $pathToTypo3Binary = $finder->find('typo3', null, [$binDir]);
        if ($pathToTypo3Binary === null) {
            throw new \RuntimeException('Could not determine path to typo3 binary', 1765273845);
        }
        if (Platform::isWindows()) {
            $pathToTypo3BinaryWithoutExt = Preg::replace('{\.(exe|bat|cmd|com)$}i', '', $pathToTypo3Binary);
            // prefer non-extension file if it exists when executing with PHP
            if (file_exists($pathToTypo3BinaryWithoutExt)) {
                $pathToTypo3Binary = $pathToTypo3BinaryWithoutExt;
            }
            unset($pathToTypo3BinaryWithoutExt);
        }
        $typo3Command = $this->command;
        array_unshift($typo3Command, $pathToTypo3Binary);
        return $typo3Command;
    }

    private function getPhpExecCommand(): array
    {
        $finder = new PhpExecutableFinder();
        $phpPath = $finder->find(false);
        if (!$phpPath) {
            throw new \RuntimeException('Failed to locate PHP binary to execute ' . $phpPath, 1765274260);
        }
        $phpArgs = $finder->findArguments();
        array_unshift($phpArgs, $phpPath);
        $phpArgs[] = '-d';
        $phpArgs[] = 'allow_url_fopen=' . ini_get('allow_url_fopen');
        $phpArgs[] = '-d';
        $phpArgs[] = 'disable_functions=' . ini_get('disable_functions');
        $phpArgs[] = '-d';
        $phpArgs[] = 'memory_limit=' . ini_get('memory_limit');
        return $phpArgs;
    }
}
