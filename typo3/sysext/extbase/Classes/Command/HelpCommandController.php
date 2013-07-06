<?php
namespace TYPO3\CMS\Extbase\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A Command Controller which provides help for available commands
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class HelpCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
	 */
	protected $commandManager;

	/**
	 * @var array
	 */
	protected $commandsByExtensionsAndControllers = array();

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager
	 * @return void
	 */
	public function injectCommandManager(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * Displays a short, general help message
	 *
	 * This only outputs the Extbase version number, context and some hint about how to
	 * get more help about commands.
	 *
	 * @return void
	 * @internal
	 */
	public function helpStubCommand() {
		$this->outputLine('Extbase %s', array(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('extbase')));
		$this->outputLine('usage: ./cli_dispatch.phpsh extbase <command identifier>');
		$this->outputLine();
		$this->outputLine('See \'./cli_dispatch.phpsh extbase help\' for a list of all available commands.');
		$this->outputLine();
	}

	/**
	 * Display help for a command
	 *
	 * The help command displays help for a given command:
	 * ./cli_dispatch.phpsh extbase help <command identifier>
	 *
	 * @param string $commandIdentifier Identifier of a command for more details
	 * @return void
	 */
	public function helpCommand($commandIdentifier = NULL) {
		if ($commandIdentifier === NULL) {
			$this->displayHelpIndex();
		} else {
			try {
				$command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
			} catch (\TYPO3\CMS\Extbase\Mvc\Exception\CommandException $exception) {
				$this->outputLine($exception->getMessage());
				return;
			}
			$this->displayHelpForCommand($command);
		}
	}

	/**
	 * @return void
	 */
	protected function displayHelpIndex() {
		$this->buildCommandsIndex();
		$this->outputLine('Extbase %s', array(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('extbase')));
		$this->outputLine('usage: ./cli_dispatch.phpsh extbase <command identifier>');
		$this->outputLine();
		$this->outputLine('The following commands are currently available:');
		foreach ($this->commandsByExtensionsAndControllers as $extensionKey => $commandControllers) {
			$this->outputLine('');
			$this->outputLine('EXTENSION "%s":', array(strtoupper($extensionKey)));
			$this->outputLine(str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			foreach ($commandControllers as $commands) {
				foreach ($commands as $command) {
					$description = wordwrap($command->getShortDescription(), self::MAXIMUM_LINE_LENGTH - 43, PHP_EOL . str_repeat(' ', 43), TRUE);
					$shortCommandIdentifier = $this->commandManager->getShortestIdentifierForCommand($command);
					$this->outputLine('%-2s%-40s %s', array(' ', $shortCommandIdentifier, $description));
				}
				$this->outputLine();
			}
		}
		$this->outputLine('See \'./cli_dispatch.phpsh extbase help <command identifier>\' for more information about a specific command.');
		$this->outputLine();
	}

	/**
	 * Render help text for a single command
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Cli\Command $command
	 * @return void
	 */
	protected function displayHelpForCommand(\TYPO3\CMS\Extbase\Mvc\Cli\Command $command) {
		$this->outputLine();
		$this->outputLine($command->getShortDescription());
		$this->outputLine();
		$this->outputLine('COMMAND:');
		$this->outputLine('%-2s%s', array(' ', $command->getCommandIdentifier()));
		$commandArgumentDefinitions = $command->getArgumentDefinitions();
		$usage = '';
		$hasOptions = FALSE;
		foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
			if (!$commandArgumentDefinition->isRequired()) {
				$hasOptions = TRUE;
			} else {
				$usage .= sprintf(' <%s>', strtolower(preg_replace('/([A-Z])/', ' $1', $commandArgumentDefinition->getName())));
			}
		}
		$usage = './cli_dispatch.phpsh extbase ' . $this->commandManager->getShortestIdentifierForCommand($command) . ($hasOptions ? ' [<options>]' : '') . $usage;
		$this->outputLine();
		$this->outputLine('USAGE:');
		$this->outputLine('  ' . $usage);
		$argumentDescriptions = array();
		$optionDescriptions = array();
		if ($command->hasArguments()) {
			foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
				$argumentDescription = $commandArgumentDefinition->getDescription();
				$argumentDescription = wordwrap($argumentDescription, self::MAXIMUM_LINE_LENGTH - 23, PHP_EOL . str_repeat(' ', 23), TRUE);
				if ($commandArgumentDefinition->isRequired()) {
					$argumentDescriptions[] = vsprintf('  %-20s %s', array($commandArgumentDefinition->getDashedName(), $argumentDescription));
				} else {
					$optionDescriptions[] = vsprintf('  %-20s %s', array($commandArgumentDefinition->getDashedName(), $argumentDescription));
				}
			}
		}
		if (count($argumentDescriptions) > 0) {
			$this->outputLine();
			$this->outputLine('ARGUMENTS:');
			foreach ($argumentDescriptions as $argumentDescription) {
				$this->outputLine($argumentDescription);
			}
		}
		if (count($optionDescriptions) > 0) {
			$this->outputLine();
			$this->outputLine('OPTIONS:');
			foreach ($optionDescriptions as $optionDescription) {
				$this->outputLine($optionDescription);
			}
		}
		if ($command->getDescription() !== '') {
			$this->outputLine();
			$this->outputLine('DESCRIPTION:');
			$descriptionLines = explode(chr(10), $command->getDescription());
			foreach ($descriptionLines as $descriptionLine) {
				$this->outputLine('%-2s%s', array(' ', $descriptionLine));
			}
		}
		$relatedCommandIdentifiers = $command->getRelatedCommandIdentifiers();
		if ($relatedCommandIdentifiers !== array()) {
			$this->outputLine();
			$this->outputLine('SEE ALSO:');
			foreach ($relatedCommandIdentifiers as $commandIdentifier) {
				$command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
				$this->outputLine('%-2s%s (%s)', array(' ', $commandIdentifier, $command->getShortDescription()));
			}
		}
		$this->outputLine();
	}

	/**
	 * Displays an error message
	 *
	 * @internal
	 * @param \TYPO3\CMS\Extbase\Mvc\Exception\CommandException $exception
	 * @return void
	 */
	public function errorCommand(\TYPO3\CMS\Extbase\Mvc\Exception\CommandException $exception) {
		$this->outputLine($exception->getMessage());
		if ($exception instanceof \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException) {
			$this->outputLine('Please specify the complete command identifier. Matched commands:');
			foreach ($exception->getMatchingCommands() as $matchingCommand) {
				$this->outputLine('    %s', array($matchingCommand->getCommandIdentifier()));
			}
		}
		$this->outputLine('');
		$this->outputLine('Enter "./cli_dispatch.phpsh extbase help" for an overview of all available commands');
		$this->outputLine('or "./cli_dispatch.phpsh extbase help <command identifier>" for a detailed description of the corresponding command.');
	}

	/**
	 * Builds an index of available commands. For each of them a Command object is
	 * added to the commands array of this class.
	 *
	 * @return void
	 */
	protected function buildCommandsIndex() {
		$availableCommands = $this->commandManager->getAvailableCommands();
		foreach ($availableCommands as $command) {
			if ($command->isInternal()) {
				continue;
			}
			$commandIdentifier = $command->getCommandIdentifier();
			$extensionKey = strstr($commandIdentifier, ':', TRUE);
			$commandControllerClassName = $command->getControllerClassName();
			$commandName = $command->getControllerCommandName();
			$this->commandsByExtensionsAndControllers[$extensionKey][$commandControllerClassName][$commandName] = $command;
		}
	}
}

?>