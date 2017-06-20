<?php
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

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command Line Interface Request Handler dealing with registered commands.
 */
class CommandRequestHandler implements RequestHandlerInterface
{
    /**
     * Instance of the current TYPO3 bootstrap
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Instance of the symfony application
     * @var Application
     */
    protected $application;

    /**
     * Constructor handing over the bootstrap
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->application = new Application('TYPO3 CMS', TYPO3_version);
    }

    /**
     * Handles any commandline request
     *
     * @param InputInterface $input
     */
    public function handleRequest(InputInterface $input)
    {
        $output = new ConsoleOutput();

        $this->bootstrap
            ->loadExtTables()
            // create the BE_USER object (not logged in yet)
            ->initializeBackendUser(CommandLineUserAuthentication::class)
            ->initializeLanguageObject()
            // Make sure output is not buffered, so command-line output and interaction can take place
            ->endOutputBufferingAndCleanPreviousOutput();

        $this->populateAvailableCommands();

        $exitCode = $this->application->run($input, $output);
        exit($exitCode);
    }

    /**
     * This request handler can handle any CLI request
     *
     * @param InputInterface $input
     * @return bool Always TRUE
     */
    public function canHandleRequest(InputInterface $input)
    {
        return true;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 50;
    }

    /**
     * Put all available commands inside the application
     */
    protected function populateAvailableCommands()
    {
        /** @var PackageManager $packageManager */
        $packageManager = Bootstrap::getInstance()->getEarlyInstance(PackageManager::class);

        foreach ($packageManager->getActivePackages() as $package) {
            $commandsOfExtension = $package->getPackagePath() . 'Configuration/Commands.php';
            if (@is_file($commandsOfExtension)) {
                $commands = require_once $commandsOfExtension;
                if (is_array($commands)) {
                    foreach ($commands as $commandName => $commandDescription) {
                        /** @var Command $cmd */
                        $cmd = GeneralUtility::makeInstance($commandDescription['class'], $commandName);
                        // Check if the command name is already in use
                        if ($this->application->has($commandName)) {
                            throw new CommandNameAlreadyInUseException('Command "' . $commandName . '" registered by "' . $package->getPackageKey() . '" is already in use', 1484486383);
                        }
                        $this->application->add($cmd);
                    }
                }
            }
        }
    }
}
