<?php
/***************************************************************
*  Copyright notice
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
class Tx_Extbase_MVC_CLI_CommandManager implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array<Tx_Extbase_MVC_CLI_Command>
	 */
	protected $availableCommands = NULL;

	/**
	 * @var array
	 */
	protected $shortCommandIdentifiers = NULL;

	/**
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns an array of all commands
	 *
	 * @return array<Tx_Extbase_MVC_CLI_Command>
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getAvailableCommands() {
		if ($this->availableCommands === NULL) {
			$this->availableCommands = array();

			$commandControllerClassNames = (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']) ? $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] : array());
			foreach ($commandControllerClassNames as $className) {
				if (!class_exists($className)) {
					continue;
				}
				foreach (get_class_methods($className) as $methodName) {
					if (substr($methodName, -7, 7) === 'Command') {
						$this->availableCommands[] = $this->objectManager->get('Tx_Extbase_MVC_CLI_Command', $className, substr($methodName, 0, -7));
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
	 * @return Tx_Extbase_MVC_CLI_Command
	 * @throws Tx_Extbase_MVC_Exception_NoSuchCommand if no matching command is available
	 * @throws Tx_Extbase_MVC_Exception_AmbiguousCommandIdentifier if more than one Command matches the identifier (the exception contains the matched commands)
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
			throw new Tx_Extbase_MVC_Exception_NoSuchCommand('No command could be found that matches the command identifier "' . $commandIdentifier . '".', 1310556663);
		}
		if (count($matchedCommands) > 1) {
			throw new Tx_Extbase_MVC_Exception_AmbiguousCommandIdentifier('More than one command matches the command identifier "' . $commandIdentifier . '"', 1310557169, NULL, $matchedCommands);
		}
		return current($matchedCommands);
	}

	/**
	 * Returns the shortest, non-ambiguous command identifier for the given command
	 *
	 * @param Tx_Extbase_MVC_CLI_Command $command The command
	 * @return string The shortest possible command identifier
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getShortestIdentifierForCommand(Tx_Extbase_MVC_CLI_Command $command) {
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
	 * @author Bastian Waidelich <bastian@typo3.org>
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
					$this->shortCommandIdentifiers[$availableCommand->getCommandIdentifier()] = sprintf('%s:%s:%s', $extensionKey, $controllerName, $commandName);;
				} else {
					$this->shortCommandIdentifiers[$availableCommand->getCommandIdentifier()] = sprintf('%s:%s', $controllerName, $commandName);;
				}
			}
		}
		return $this->shortCommandIdentifiers;
	}

	/**
	 * Returns TRUE if the specified command identifier matches the identifier of the specified command.
	 * This is the case, if the identifiers are the same or if at least the last two command parts match (case sensitive).
	 *
	 * @param Tx_Extbase_MVC_CLI_Command $command
	 * @param string $commandIdentifier command identifier in the format foo:bar:baz (all lower case)
	 * @return boolean TRUE if the specified command identifier matches this commands identifier
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function commandMatchesIdentifier(Tx_Extbase_MVC_CLI_Command $command, $commandIdentifier) {
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