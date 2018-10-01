<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

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

/**
 * A helper for CLI commands
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use symfony/console commands instead.
 */
class CommandManager implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array<\TYPO3\CMS\Extbase\Mvc\Cli\Command>
     */
    protected $availableCommands;

    /**
     * @var array
     */
    protected $shortCommandIdentifiers;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Returns an array of all commands
     *
     * @return Command[]
     */
    public function getAvailableCommands()
    {
        if ($this->availableCommands === null) {
            $this->availableCommands = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] ?? [] as $className) {
                if (!class_exists($className)) {
                    continue;
                }
                foreach (get_class_methods($className) as $methodName) {
                    if (substr($methodName, -7, 7) === 'Command') {
                        $this->availableCommands[] = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Cli\Command::class, $className, substr($methodName, 0, -7));
                    }
                }
            }
        }
        return $this->availableCommands;
    }

    /**
     * Returns a Command that matches the given identifier.
     * If no Command could be found a CommandNotFoundException is thrown
     * If more than one Command matches an AmbiguousCommandIdentifierException is thrown that contains the matched Commands
     *
     * @param string $commandIdentifier command identifier in the format foo:bar:baz
     * @return Command
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException if no matching command is available
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException if more than one Command matches the identifier (the exception contains the matched commands)
     */
    public function getCommandByIdentifier($commandIdentifier)
    {
        $commandIdentifier = strtolower(trim($commandIdentifier));
        if ($commandIdentifier === 'help') {
            $commandIdentifier = 'extbase:help:help';
        }
        $matchedCommands = [];
        $availableCommands = $this->getAvailableCommands();
        foreach ($availableCommands as $command) {
            if ($this->commandMatchesIdentifier($command, $commandIdentifier)) {
                $matchedCommands[] = $command;
            }
        }
        if (empty($matchedCommands)) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException('No command could be found that matches the command identifier "' . $commandIdentifier . '".', 1310556663);
        }
        if (count($matchedCommands) > 1) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException('More than one command matches the command identifier "' . $commandIdentifier . '"', 1310557169, null, $matchedCommands);
        }
        return current($matchedCommands);
    }

    /**
     * Returns the shortest, non-ambiguous command identifier for the given command
     *
     * @param Command $command The command
     * @return string The shortest possible command identifier
     */
    public function getShortestIdentifierForCommand(Command $command)
    {
        if ($command->getCommandIdentifier() === 'extbase:help:help') {
            return 'help';
        }
        $shortCommandIdentifiers = $this->getShortCommandIdentifiers();
        if (!isset($shortCommandIdentifiers[$command->getCommandIdentifier()])) {
            $command->getCommandIdentifier();
        }
        return $shortCommandIdentifiers[$command->getCommandIdentifier()];
    }

    /**
     * Returns an array that contains all available command identifiers and their shortest non-ambiguous alias
     *
     * @return array in the format array('full.command:identifier1' => 'alias1', 'full.command:identifier2' => 'alias2')
     */
    protected function getShortCommandIdentifiers()
    {
        if ($this->shortCommandIdentifiers === null) {
            $commandsByCommandName = [];
            foreach ($this->getAvailableCommands() as $availableCommand) {
                list($extensionKey, $controllerName, $commandName) = explode(':', $availableCommand->getCommandIdentifier());
                if (!isset($commandsByCommandName[$commandName])) {
                    $commandsByCommandName[$commandName] = [];
                }
                if (!isset($commandsByCommandName[$commandName][$controllerName])) {
                    $commandsByCommandName[$commandName][$controllerName] = [];
                }
                $commandsByCommandName[$commandName][$controllerName][] = $extensionKey;
            }
            foreach ($this->getAvailableCommands() as $availableCommand) {
                list($extensionKey, $controllerName, $commandName) = explode(':', $availableCommand->getCommandIdentifier());
                if (count($commandsByCommandName[$commandName][$controllerName]) > 1) {
                    $this->shortCommandIdentifiers[$availableCommand->getCommandIdentifier()] = sprintf('%s:%s:%s', $extensionKey, $controllerName, $commandName);
                } else {
                    $this->shortCommandIdentifiers[$availableCommand->getCommandIdentifier()] = sprintf('%s:%s', $controllerName, $commandName);
                }
            }
        }
        return $this->shortCommandIdentifiers;
    }

    /**
     * Returns TRUE if the specified command identifier matches the identifier of the specified command.
     * This is the case, if the identifiers are the same or if at least the last two command parts match (case sensitive).
     *
     * @param Command $command
     * @param string $commandIdentifier command identifier in the format foo:bar:baz (all lower case)
     * @return bool TRUE if the specified command identifier matches this commands identifier
     */
    protected function commandMatchesIdentifier(Command $command, $commandIdentifier)
    {
        $commandIdentifierParts = explode(':', $command->getCommandIdentifier());
        $searchedCommandIdentifierParts = explode(':', $commandIdentifier);
        $extensionKey = array_shift($commandIdentifierParts);
        if (count($searchedCommandIdentifierParts) === 3) {
            $searchedExtensionKey = array_shift($searchedCommandIdentifierParts);
            if ($searchedExtensionKey !== $extensionKey) {
                return false;
            }
        }
        if (count($searchedCommandIdentifierParts) !== 2) {
            return false;
        }
        return $searchedCommandIdentifierParts === $commandIdentifierParts;
    }
}
