<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command Line Interface Request Handler dealing with registered commands.
 */
class CommandRequestHandler implements RequestHandlerInterface
{
    /**
     * Instance of the symfony application
     * @var Application
     */
    protected $application;

    /**
     * Constructor initializing the symfony application
     */
    public function __construct()
    {
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

        Bootstrap::loadExtTables();
        // create the BE_USER object (not logged in yet)
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        Bootstrap::initializeLanguageObject();
        // Make sure output is not buffered, so command-line output and interaction can take place
        ob_clean();

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
    public function canHandleRequest(InputInterface $input): bool
    {
        return true;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Put all available commands inside the application
     * @throws \TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException
     */
    protected function populateAvailableCommands()
    {
        $commands = GeneralUtility::makeInstance(CommandRegistry::class);

        foreach ($commands as $commandName => $command) {
            /** @var Command $command */
            $this->application->add($command);
        }
    }
}
