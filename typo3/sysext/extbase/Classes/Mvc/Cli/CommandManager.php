<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

/***************************************************************
 *  Copyright notice
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A helper for CLI commands
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CommandManager implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array<\TYPO3\CMS\Extbase\Mvc\Cli\Command>
	 */
	protected $availableCommands = NULL;

	/**
	 * @var array
	 */
	protected $shortCommandIdentifiers = NULL;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns an array of all commands
	 *
	 * @return array<\TYPO3\CMS\Extbase\Mvc\Cli\Command>
	 * @api
	 */
	public function getAvailableCommands() {
		if ($this->availableCommands === NULL) {
			$this->availableCommands = array();
			$commandControllerClassNames = is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']) ? $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] : array();
			foreach ($commandControllerClassNames as $className) {
				if (!class_exists($className)) {
					continue;
				}
				foreach (get_class_methods($className) as $methodName) {
					if (substr($methodName, -7, 7) === 'Command') {
						$this->availableCommands[] = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', $className, substr($methodName, 0, -7));
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
	 * @return \TYPO3\CMS\Extbase\Mvc\Cli\Command
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException if no matching command is available
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException if more than one Command matches the identifier (the exception contains the matched commands)
	 * @api
	 */
	public function getCommandByIdentifier($commandIdentifier) {
		$commandIdentifier = strtolower(trim($commandIdentifier));
		if ($commandIdentifier === 'help') {
			$commandIdentifier = 'extbase:help:help';
		}
		$matchedCommands = array();
		$availableCommands = $this->getAvailableCommands();
		foreach ($availableCommands as $command) {
			if ($this->commandMatchesIdentifier($command, $commandIdentifier)) {
				$matchedCommands[] = $command;
			}
		}
		if (count($matchedCommands) === 0) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException('No command could be found that matches the command identifier "' . $commandIdentifier . '".', 1310556663);
		}
		if (count($matchedCommands) > 1) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException('More than one command matches the command identifier "' . $commandIdentifier . '"', 1310557169, NULL, $matchedCommands);
		}
		return current($matchedCommands);
	}

	/**
	 * Returns the shortest, non-ambiguous command identifier for the given command
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Cli\Command $command The command
	 * @return string The shortest possible command identifier
	 * @api
	 */
	public function getShortestIdentifierForCommand(\TYPO3\CMS\Extbase\Mvc\Cli\Command $command) {
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
	protected function getShortCommandIdentifiers() {
		if ($this->shortCommandIdentifiers === NULL) {
			$commandsByCommandName = array();
			foreach ($this->getAvailableCommands() as $availableCommand) {
				list($extensionKey, $controllerName, $commandName) = explode(':', $availableCommand->getCommandIdentifier());
				if (!isset($commandsByCommandName[$commandName])) {
					$commandsByCommandName[$commandName] = array();
				}
				if (!isset($commandsByCommandName[$commandName][$controllerName])) {
					$commandsByCommandName[$commandName][$controllerName] = array();
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
	 * @param \TYPO3\CMS\Extbase\Mvc\Cli\Command $command
	 * @param string $commandIdentifier command identifier in the format foo:bar:baz (all lower case)
	 * @return boolean TRUE if the specified command identifier matches this commands identifier
	 */
	protected function commandMatchesIdentifier(\TYPO3\CMS\Extbase\Mvc\Cli\Command $command, $commandIdentifier) {
		$commandIdentifierParts = explode(':', $command->getCommandIdentifier());
		$searchedCommandIdentifierParts = explode(':', $commandIdentifier);
		$extensionKey = array_shift($commandIdentifierParts);
		if (count($searchedCommandIdentifierParts) === 3) {
			$searchedExtensionKey = array_shift($searchedCommandIdentifierParts);
			if ($searchedExtensionKey !== $extensionKey) {
				return FALSE;
			}
		}
		if (count($searchedCommandIdentifierParts) !== 2) {
			return FALSE;
		}
		return $searchedCommandIdentifierParts === $commandIdentifierParts;
	}
}

?>