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
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Registry for Symfony commands, populated via dependency injection tags
 */
class CommandRegistry implements CommandLoaderInterface, SingletonInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Map of command configurations with the command name as key
     *
     * @var array[]
     */
    protected $commandConfigurations = [];

    /**
     * Map of command aliases
     *
     * @var array[]
     */
    protected $aliases = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return array_key_exists($name, $this->commandConfigurations);
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
        return array_keys($this->commandConfigurations);
    }

    /**
     * Get all commands which are allowed for scheduling recurring commands.
     *
     * @return \Generator
     */
    public function getSchedulableCommands(): \Generator
    {
        foreach ($this->commandConfigurations as $commandName => $configuration) {
            if ($configuration['schedulable'] ?? true) {
                yield $commandName => $this->getInstance($configuration['serviceName']);
            }
        }
    }

    /**
     * @param string $identifier
     * @throws UnknownCommandException
     * @return Command
     */
    public function getCommandByIdentifier(string $identifier): Command
    {
        if (!isset($this->commandConfigurations[$identifier])) {
            throw new UnknownCommandException(
                sprintf('Command "%s" has not been registered.', $identifier),
                1510906768
            );
        }

        return $this->getInstance($this->commandConfigurations[$identifier]['serviceName']);
    }

    protected function getInstance(string $service): Command
    {
        return $this->container->get($service);
    }

    /**
     * @internal
     */
    public function getNamespaces(): array
    {
        $namespaces = [];
        foreach ($this->commandConfigurations as $commandName => $configuration) {
            if ($configuration['hidden']) {
                continue;
            }
            if ($configuration['aliasFor'] !== null) {
                continue;
            }
            $namespace = $configuration['namespace'];
            $namespaces[$namespace]['id'] = $namespace;
            $namespaces[$namespace]['commands'][] = $commandName;
        }

        ksort($namespaces);
        foreach ($namespaces as &$commands) {
            ksort($commands);
        }

        return $namespaces;
    }

    /**
     * Gets the commands (registered in the given namespace if provided).
     *
     * The array keys are the full names and the values the command instances.
     *
     * @return array An array of Command descriptors
     * @internal
     */
    public function filter(string $namespace = null): array
    {
        $commands = [];
        foreach ($this->commandConfigurations as $commandName => $configuration) {
            if ($configuration['hidden']) {
                continue;
            }
            if ($namespace !== null && $namespace !== $this->extractNamespace($commandName, substr_count($namespace, ':') + 1)) {
                continue;
            }
            if ($configuration['aliasFor'] !== null) {
                continue;
            }

            $commands[$commandName] = $configuration;
            $commands[$commandName]['aliases'] = $this->aliases[$commandName] ?? [];
        }

        return $commands;
    }

    /**
     * @internal
     */
    public function addLazyCommand(
        string $commandName,
        string $serviceName,
        string $description = null,
        bool $hidden = false,
        bool $schedulable = false,
        string $aliasFor = null
    ): void {
        $this->commandConfigurations[$commandName] = [
            'name' => $aliasFor ?? $commandName,
            'serviceName' => $serviceName,
            'description' => $description,
            'hidden' => $hidden,
            'schedulable' => $schedulable,
            'aliasFor' => $aliasFor,
            'namespace' => $this->extractNamespace($commandName, 1),
        ];

        if ($aliasFor !== null) {
            $this->aliases[$aliasFor][] = $commandName;
        }
    }

    /**
     * Returns the namespace part of the command name.
     *
     * This method is not part of public API and should not be used directly.
     *
     * @return string The namespace of the command
     */
    private function extractNamespace(string $name, int $limit = null): string
    {
        $parts = explode(':', $name, -1);
        if (count($parts) === 0) {
            return ApplicationDescription::GLOBAL_NAMESPACE;
        }

        return implode(':', $limit === null ? $parts : array_slice($parts, 0, $limit));
    }
}
