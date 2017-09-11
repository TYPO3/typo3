<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Console;

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

use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Registry for Symfony commands, populated from extensions
 */
class CommandRegistry implements \IteratorAggregate, SingletonInterface
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * Map of commands
     *
     * @var Command[]
     */
    protected $commands = [];

    /**
     * @param PackageManager $packageManager
     */
    public function __construct(PackageManager $packageManager = null)
    {
        $this->packageManager = $packageManager ?: GeneralUtility::makeInstance(PackageManager::class);
    }

    /**
     * @return \Generator
     */
    public function getIterator(): \Generator
    {
        $this->populateCommandsFromPackages();
        foreach ($this->commands as $commandName => $command) {
            yield $commandName => $command;
        }
    }

    /**
     * Find all Configuration/Commands.php files of extensions and create a registry from it.
     * The file should return an array with a command key as key and the command description
     * as value. The command description must be an array and have a class key that defines
     * the class name of the command. Example:
     *
     * <?php
     * return [
     *     'backend:lock' => [
     *         'class' => \TYPO3\CMS\Backend\Command\LockBackendCommand::class
     *     ],
     * ];
     *
     * @throws CommandNameAlreadyInUseException
     */
    protected function populateCommandsFromPackages()
    {
        if ($this->commands) {
            return;
        }
        foreach ($this->packageManager->getActivePackages() as $package) {
            $commandsOfExtension = $package->getPackagePath() . 'Configuration/Commands.php';
            if (@is_file($commandsOfExtension)) {
                $commands = require_once $commandsOfExtension;
                if (is_array($commands)) {
                    foreach ($commands as $commandName => $commandConfig) {
                        if (array_key_exists($commandName, $this->commands)) {
                            throw new CommandNameAlreadyInUseException(
                                'Command "' . $commandName . '" registered by "' . $package->getPackageKey() . '" is already in use',
                                1484486383
                            );
                        }
                        $this->commands[$commandName] = GeneralUtility::makeInstance($commandConfig['class'], $commandName);
                    }
                }
            }
        }
    }
}
