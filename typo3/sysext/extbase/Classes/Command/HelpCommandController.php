<?php
namespace TYPO3\CMS\Extbase\Command;

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
 * A Command Controller which provides help for available commands
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class HelpCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
     */
    protected $commandManager;

    /**
     * @var array
     */
    protected $commandsByExtensionsAndControllers = [];

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager
     */
    public function injectCommandManager(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager)
    {
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
    public function helpStubCommand()
    {
        $this->outputLine('Extbase %s', [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('extbase')]);
        $this->outputLine('usage: ' . $this->request->getCallingScript() . ' <command identifier>');
        $this->outputLine();
        $this->outputLine('See \'' . $this->request->getCallingScript() . ' help\' for a list of all available commands.');
        $this->outputLine();
    }

    /**
     * Display help for a command
     *
     * The help command displays help for a given command:
     * ./cli_dispatch.phpsh extbase help <command identifier>
     *
     * @param string $commandIdentifier Identifier of a command for more details
     * @cli
     */
    public function helpCommand($commandIdentifier = null)
    {
        if ($commandIdentifier === null) {
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
    protected function displayHelpIndex()
    {
        $this->buildCommandsIndex();
        $this->outputLine('Extbase %s', [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('extbase')]);
        $this->outputLine('usage: ' . $this->request->getCallingScript() . ' <command identifier>');
        $this->outputLine();
        $this->outputLine('The following commands are currently available:');
        foreach ($this->commandsByExtensionsAndControllers as $extensionKey => $commandControllers) {
            $this->outputLine('');
            $this->outputLine('EXTENSION "%s":', [strtoupper($extensionKey)]);
            $this->outputLine(str_repeat('-', $this->output->getMaximumLineLength()));
            foreach ($commandControllers as $commands) {
                foreach ($commands as $command) {
                    $description = wordwrap($command->getShortDescription(), $this->output->getMaximumLineLength() - 43, PHP_EOL . str_repeat(' ', 43), true);
                    $shortCommandIdentifier = $this->commandManager->getShortestIdentifierForCommand($command);
                    $this->outputLine('%-2s%-40s %s', [' ', $shortCommandIdentifier, $description]);
                }
                $this->outputLine();
            }
        }
        $this->outputLine('See \'' . $this->request->getCallingScript() . ' help <command identifier>\' for more information about a specific command.');
        $this->outputLine();
    }

    /**
     * Render help text for a single command
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\Command $command
     * @return void
     */
    protected function displayHelpForCommand(\TYPO3\CMS\Extbase\Mvc\Cli\Command $command)
    {
        $this->outputLine();
        $this->outputLine($command->getShortDescription());
        $this->outputLine();
        $this->outputLine('COMMAND:');
        $this->outputLine('%-2s%s', [' ', $command->getCommandIdentifier()]);
        $commandArgumentDefinitions = $command->getArgumentDefinitions();
        $usage = '';
        $hasOptions = false;
        foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
            if (!$commandArgumentDefinition->isRequired()) {
                $hasOptions = true;
            } else {
                $usage .= sprintf(' <%s>', strtolower(preg_replace('/([A-Z])/', ' $1', $commandArgumentDefinition->getName())));
            }
        }
        $usage = $this->request->getCallingScript() . ' ' . $this->commandManager->getShortestIdentifierForCommand($command) . ($hasOptions ? ' [<options>]' : '') . $usage;
        $this->outputLine();
        $this->outputLine('USAGE:');
        $this->outputLine('  ' . $usage);
        $argumentDescriptions = [];
        $optionDescriptions = [];
        if ($command->hasArguments()) {
            foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
                $argumentDescription = $commandArgumentDefinition->getDescription();
                $argumentDescription = wordwrap($argumentDescription, $this->output->getMaximumLineLength() - 23, PHP_EOL . str_repeat(' ', 23), true);
                if ($commandArgumentDefinition->isRequired()) {
                    $argumentDescriptions[] = vsprintf('  %-20s %s', [$commandArgumentDefinition->getDashedName(), $argumentDescription]);
                } else {
                    $optionDescriptions[] = vsprintf('  %-20s %s', [$commandArgumentDefinition->getDashedName(), $argumentDescription]);
                }
            }
        }
        if (!empty($argumentDescriptions)) {
            $this->outputLine();
            $this->outputLine('ARGUMENTS:');
            foreach ($argumentDescriptions as $argumentDescription) {
                $this->outputLine($argumentDescription);
            }
        }
        if (!empty($optionDescriptions)) {
            $this->outputLine();
            $this->outputLine('OPTIONS:');
            foreach ($optionDescriptions as $optionDescription) {
                $this->outputLine($optionDescription);
            }
        }
        if ($command->getDescription() !== '') {
            $this->outputLine();
            $this->outputLine('DESCRIPTION:');
            $descriptionLines = explode(LF, $command->getDescription());
            foreach ($descriptionLines as $descriptionLine) {
                $this->outputLine('%-2s%s', [' ', $descriptionLine]);
            }
        }
        $relatedCommandIdentifiers = $command->getRelatedCommandIdentifiers();
        if ($relatedCommandIdentifiers !== []) {
            $this->outputLine();
            $this->outputLine('SEE ALSO:');
            foreach ($relatedCommandIdentifiers as $commandIdentifier) {
                $command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
                $this->outputLine('%-2s%s (%s)', [' ', $commandIdentifier, $command->getShortDescription()]);
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
    public function errorCommand(\TYPO3\CMS\Extbase\Mvc\Exception\CommandException $exception)
    {
        $this->outputLine($exception->getMessage());
        if ($exception instanceof \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException) {
            $this->outputLine('Please specify the complete command identifier. Matched commands:');
            foreach ($exception->getMatchingCommands() as $matchingCommand) {
                $this->outputLine('    %s', [$matchingCommand->getCommandIdentifier()]);
            }
        }
        $this->outputLine('');
        $this->outputLine('Enter "' . $this->request->getCallingScript() . ' help" for an overview of all available commands');
        $this->outputLine('or "' . $this->request->getCallingScript() . ' help <command identifier>" for a detailed description of the corresponding command.');
    }

    /**
     * Builds an index of available commands. For each of them a Command object is
     * added to the commands array of this class.
     *
     * @return void
     */
    protected function buildCommandsIndex()
    {
        $availableCommands = $this->commandManager->getAvailableCommands();
        foreach ($availableCommands as $command) {
            if ($command->isInternal()) {
                continue;
            }
            $commandIdentifier = $command->getCommandIdentifier();
            $extensionKey = strstr($commandIdentifier, ':', true);
            $commandControllerClassName = $command->getControllerClassName();
            $commandName = $command->getControllerCommandName();
            $this->commandsByExtensionsAndControllers[$extensionKey][$commandControllerClassName][$commandName] = $command;
        }
    }
}
