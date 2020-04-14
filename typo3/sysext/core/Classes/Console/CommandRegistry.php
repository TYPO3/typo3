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

namespace TYPO3\CMS\Core\Console;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Registry for Symfony commands, populated from extensions
 */
class CommandRegistry implements CommandLoaderInterface, \IteratorAggregate, SingletonInterface
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Map of commands
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Map of command configurations with the command name as key
     *
     * @var array[]
     */
    protected $commandConfigurations = [];

    /**
     * Map of lazy (DI-managed) command configurations with the command name as key
     *
     * @var array
     */
    protected $lazyCommandConfigurations = [];

    /**
     * @param PackageManager $packageManager
     * @param ContainerInterface $container
     */
    public function __construct(PackageManager $packageManager, ContainerInterface $container)
    {
        $this->packageManager = $packageManager;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        $this->populateCommandsFromPackages();

        return array_key_exists($name, $this->commands);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        try {
            return $this->getCommandByIdentifier($name);
        } catch (UnknownCommandException $e) {
            throw new CommandNotFoundException($e->getMessage(), [], 1567969355, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNames()
    {
        $this->populateCommandsFromPackages();

        return array_keys($this->commands);
    }

    /**
     * @return \Generator
     * @deprecated will be removed in TYPO3 v11.0 when support for Configuration/Commands.php is dropped.
     */
    public function getIterator(): \Generator
    {
        trigger_error('Using ' . self::class . ' as iterable has been deprecated and will stop working in TYPO3 11.0.', E_USER_DEPRECATED);

        $this->populateCommandsFromPackages();
        foreach ($this->commands as $commandName => $command) {
            if (is_string($command)) {
                $command = $this->getInstance($command);
            }
            yield $commandName => $command;
        }
    }

    /**
     * Get all commands which are allowed for scheduling recurring commands.
     *
     * @return \Generator
     */
    public function getSchedulableCommands(): \Generator
    {
        $this->populateCommandsFromPackages();
        foreach ($this->commands as $commandName => $command) {
            if ($this->commandConfigurations[$commandName]['schedulable'] ?? true) {
                if (is_string($command)) {
                    $command = $this->getInstance($command);
                }
                yield $commandName => $command;
            }
        }
    }

    /**
     * @return \Generator
     * @internal This method will be removed in TYPO3 v11 when support for Configuration/Commands.php is dropped.
     */
    public function getLegacyCommands(): \Generator
    {
        $this->populateCommandsFromPackages();
        foreach ($this->commands as $commandName => $command) {
            // Type string indicates lazy loading
            if (is_string($command)) {
                continue;
            }
            yield $commandName => $command;
        }
    }

    /**
     * @param string $identifier
     * @throws CommandNameAlreadyInUseException
     * @throws UnknownCommandException
     * @return Command
     */
    public function getCommandByIdentifier(string $identifier): Command
    {
        $this->populateCommandsFromPackages();

        if (!isset($this->commands[$identifier])) {
            throw new UnknownCommandException(
                sprintf('Command "%s" has not been registered.', $identifier),
                1510906768
            );
        }

        $command = $this->commands[$identifier] ?? null;
        if (is_string($command)) {
            $command = $this->getInstance($command);
        }

        return $command;
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

        foreach ($this->lazyCommandConfigurations as $commandName => $commandConfig) {
            // Lazy commands shall be loaded from the Container on demand, store the command as string to indicate lazy loading
            $this->commands[$commandName] = $commandConfig['class'];
            $this->commandConfigurations[$commandName] = $commandConfig;
        }

        foreach ($this->packageManager->getActivePackages() as $package) {
            $commandsOfExtension = $package->getPackagePath() . 'Configuration/Commands.php';
            if (@is_file($commandsOfExtension)) {
                /*
                 * We use require instead of require_once here because it eases the testability as require_once returns
                 * a boolean from the second execution on. As this class is a singleton, this require is only called
                 * once per request anyway.
                 */
                $commands = require $commandsOfExtension;
                if (is_array($commands)) {
                    foreach ($commands as $commandName => $commandConfig) {
                        if (array_key_exists($commandName, $this->lazyCommandConfigurations)) {
                            // Lazy (DI managed) commands override classic commands from Configuration/Commands.php
                            // Skip this case to allow extensions to provide commands via DI config and to allow
                            // TYPO3 v9 backwards compatible configuration via Configuration/Commands.php.
                            // Note: Also the deprecation error is skipped on-demand as the extension has been
                            // adapted and the configuration will be ignored as of TYPO3 v11.
                            continue;
                        }
                        if (array_key_exists($commandName, $this->commands)) {
                            throw new CommandNameAlreadyInUseException(
                                'Command "' . $commandName . '" registered by "' . $package->getPackageKey() . '" is already in use',
                                1484486383
                            );
                        }
                        $this->commands[$commandName] = GeneralUtility::makeInstance($commandConfig['class'], $commandName);
                        $this->commandConfigurations[$commandName] = $commandConfig;

                        trigger_error(
                            'Registering console commands in Configuration/Commands.php has been deprecated and will stop working in TYPO3 v11.0.',
                            E_USER_DEPRECATED
                        );
                    }
                }
            }
        }
    }

    protected function getInstance(string $class): Command
    {
        return $this->container->get($class);
    }

    /**
     * @internal
     */
    public function addLazyCommand(string $commandName, string $serviceName, bool $schedulable = true): void
    {
        $this->lazyCommandConfigurations[$commandName] = [
            'class' => $serviceName,
            'schedulable' => $schedulable,
        ];
    }
}
