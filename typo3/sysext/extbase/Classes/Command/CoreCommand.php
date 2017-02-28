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

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Main call to register any Extbase command from Extbase command controllers
 *
 * Fetches all registered Extbase commands and adds them to the application as custom Extbase commands
 */
class CoreCommand extends Command
{
    /**
     * @var Bootstrap
     */
    protected $extbaseBootstrap;

    /**
     * Configure the command, since this is a command
     */
    protected function configure()
    {
        $this->setHidden(true);
    }

    /**
     * Sets the application instance for this command.
     * This is done in setApplication() because configure() is called too early to do it in that place.
     * The method 'setApplication()' is done right afterwards but has the application object to call.
     * Then registers additional commands that act as wrappers to the actual Extbase commands.
     *
     * @param Application $application An Application instance
     */
    public function setApplication(Application $application = null)
    {
        parent::setApplication($application);

        // Find any registered Extbase commands
        $this->extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $this->extbaseBootstrap->initialize([]);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var CommandManager $commandManager */
        $commandManager = $objectManager->get(CommandManager::class);
        $commands = $commandManager->getAvailableCommands();
        foreach ($commands as $command) {
            $commandName = $commandManager->getShortestIdentifierForCommand($command);
            $fullCommandName = $command->getCommandIdentifier();
            if ($fullCommandName === 'extbase:help:error' || $fullCommandName === 'extbase:help:helpstub') {
                continue;
            }
            if ($commandName === 'help') {
                $commandName = 'extbase:help';
            }
            $extbaseCommand = GeneralUtility::makeInstance(ExtbaseCommand::class, $fullCommandName);

            if ($commandName !== $fullCommandName) {
                $extbaseCommand->setAliases([$commandName]);
            }

            $extbaseCommand->setExtbaseCommand($command);
            $this->getApplication()->add($extbaseCommand);
        }
    }
}
