<?php
namespace TYPO3\CMS\Backend\Console;

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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Console\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command Line Interface Request Handler dealing with "cliKey"-based Commands from the cli_dispatch.phpsh script.
 * Picks up requests only when coming from the CLI mode.
 * Resolves the "cliKey" which is registered inside $TYPO3_CONF_VARS[SC_OPTIONS][GLOBAL][cliKeys]
 * and includes the CLI-based script or exits if no valid "cliKey" is found.
 * Also logs into the system as a backend user which needs to be added to the database called _CLI_mymodule
 *
 * This class is deprecated in favor of the Core-based CommandRequestHandler, which uses Symfony Commands.
 */
class CliRequestHandler implements RequestHandlerInterface
{
    /**
     * Instance of the current TYPO3 bootstrap
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Constructor handing over the bootstrap
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Handles any commandline request
     *
     * @param InputInterface $input
     */
    public function handleRequest(InputInterface $input)
    {
        GeneralUtility::deprecationLog('Using cli_dispatch.phpsh as entry point for CLI commands has been marked '
        . 'as deprecated and will be removed in TYPO3 v9. Please use the new CLI entrypoint via /typo3/sysext/core/bin/typo3 instead.');
        $output = GeneralUtility::makeInstance(ConsoleOutput::class);
        $exitCode = 0;

        try {
            $command = $this->validateCommandLineKeyFromInput($input);

            // try and look up if the CLI command exists
            $commandLineScript = $this->getIncludeScriptByCommandLineKey($command);
            $this->boot();

            if (is_callable($commandLineScript)) {
                call_user_func($commandLineScript, $input, $output);
            } else {
                // include the CLI script
                include($commandLineScript);
            }
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Oops, an error occurred: ' . $e->getMessage() . '</error>');
            $output->writeln('');
            $output->writeln('Valid keys are:');
            $output->writeln('');
            $cliKeys = array_keys($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']);
            asort($cliKeys);
            foreach ($cliKeys as $key => $value) {
                $output->writeln('  ' . $value);
            }
            $exitCode = $e->getCode();
        } catch (\RuntimeException $e) {
            $output->writeln('<error>Oops, an error occurred: ' . $e->getMessage() . '</error>');
            $exitCode = $e->getCode();
        } catch (\Throwable $e) {
            $output->writeln('<error>Oops, an error occurred: ' . $e->getMessage() . '</error>');
            $exitCode = $e->getCode();
        }

        if ($exitCode > 0 && 0 === $exitCode % 256) {
            $exitCode = 1;
        }

        exit($exitCode);
    }

    /**
     * Execute TYPO3 bootstrap
     *
     * @throws \RuntimeException when the _CLI_ user cannot be authenticated properly
     */
    protected function boot()
    {
        $this->bootstrap
            ->loadBaseTca()
            ->loadExtTables()
            ->initializeBackendUser(CommandLineUserAuthentication::class);

        // Checks for a user _CLI_, if non exists, will create one
        $this->loadCommandLineBackendUser();

        $this->bootstrap
            ->initializeLanguageObject()
            // Make sure output is not buffered, so command-line output and interaction can take place
            ->endOutputBufferingAndCleanPreviousOutput();
    }

    /**
     * Check CLI parameters.
     * First argument is a key that points to the script configuration.
     * If it is not set or not valid, the script exits with an error message.
     *
     * @param InputInterface $input an instance of the input given to the CLI call
     * @return string the CLI key in use
     * @throws \InvalidArgumentException
     */
    protected function validateCommandLineKeyFromInput(InputInterface $input)
    {
        $cliKey = $input->getFirstArgument();
        if (empty($cliKey)) {
            throw new \InvalidArgumentException('This script must have a command as first argument.', 1476107418);
        }
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$cliKey])) {
            throw new \InvalidArgumentException('This supplied command is not valid.', 1476107480);
        }
        return $cliKey;
    }

    /**
     * Define cli-related parameters and return the include script.
     *
     * @param string $cliKey the CLI key
     * @return string the absolute path to the include script
     */
    protected function getIncludeScriptByCommandLineKey($cliKey)
    {
        $commandLineScript = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$cliKey][0];
        if (!is_callable($commandLineScript)) {
            $commandLineScript = GeneralUtility::getFileAbsFileName($commandLineScript);
        }

        // the CLI key (e.g. "extbase" or "lowlevel"), is removed from the argv, but the script path is kept
        $cliScriptPath = array_shift($_SERVER['argv']);
        array_shift($_SERVER['argv']);
        array_unshift($_SERVER['argv'], $cliScriptPath);
        return $commandLineScript;
    }

    /**
     * If the backend script is in CLI mode, it will try to load a backend user named _cli_
     *
     * @throws \RuntimeException if a non-admin Backend user could not be loaded
     */
    protected function loadCommandLineBackendUser()
    {
        if ($GLOBALS['BE_USER']->user['uid']) {
            throw new \RuntimeException('Another user was already loaded which is impossible in CLI mode!', 1476107444);
        }
        $GLOBALS['BE_USER']->authenticate();
    }

    /**
     * This request handler can handle any CLI request.
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
        return 20;
    }
}
