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
use TYPO3\CMS\Core\Console\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Command Line Interface Request Handler dealing with "cliKey"-based Commands from the cli_dispatch.phpsh script.
 * Picks up requests only when coming from the CLI mode.
 * Resolves the "cliKey" which is registered inside $TYPO3_CONF_VARS[SC_OPTIONS][GLOBAL][cliKeys]
 * and includes the CLI-based script or exits if no valid "cliKey" is found.
 * Also logs into the system as a backend user which needs to be added to the database called _CLI_mymodule
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
     * @return void
     */
    public function handleRequest(InputInterface $input)
    {
        $output = GeneralUtility::makeInstance(ConsoleOutput::class);
        $exitCode = 0;

        try {
            $command = $this->validateCommandLineKeyFromInput($input);

            // try and look up if the CLI command user exists, throws an exception if the CLI
            // user cannot be found
            list($commandLineScript, $commandLineName) = $this->getIncludeScriptByCommandLineKey($command);
            $this->boot($commandLineName);

            if (is_callable($commandLineScript)) {
                call_user_func($commandLineScript);
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
        } catch (\Exception $e) {
            $output->writeln('<error>Oops, an error occurred: ' . $e->getMessage() . '</error>');
            $exitCode = $e->getCode();
        } catch (\Throwable $e) {
            $output->writeln('<error>Oops, an error occurred: ' . $e->getMessage() . '</error>');
            $exitCode = $e->getCode();
        }

        exit($exitCode);
    }

    /**
     * Execute TYPO3 bootstrap
     *
     * @throws \RuntimeException when the _CLI_ user cannot be authenticated properly
     */
    protected function boot($commandLineName)
    {
        $this->bootstrap
            ->loadExtensionTables(true)
            ->initializeBackendUser();

        // Checks for a user called starting with _CLI_ e.g. "_CLI_lowlevel"
        $this->loadCommandLineBackendUser($commandLineName);

        $this->bootstrap
            ->initializeBackendAuthentication()
            ->initializeLanguageObject();

        // Make sure output is not buffered, so command-line output and interaction can take place
        GeneralUtility::flushOutputBuffers();
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
            throw new \InvalidArgumentException('This script must have a command as first argument.', 1);
        } elseif (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$cliKey])) {
            throw new \InvalidArgumentException('This supplied command is not valid.', 1);
        }
        return $cliKey;
    }

    /**
     * Define cli-related parameters and return the include script as well as the command line name. Used for
     * authentication against the backend user in the "loadCommandLineBackendUser()" action.
     *
     * @param string $cliKey the CLI key
     * @return array the absolute path to the include script and the command line name
     */
    protected function getIncludeScriptByCommandLineKey($cliKey)
    {
        list($commandLineScript, $commandLineName) = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$cliKey];
        if (!is_callable($commandLineScript)) {
            $commandLineScript = GeneralUtility::getFileAbsFileName($commandLineScript);
            // Note: These constants are not in use anymore, and marked for deprecation and will be removed in TYPO3 CMS 8
            define('TYPO3_cliKey', $cliKey);
            define('TYPO3_cliInclude', $commandLineScript);
        }
        // This is a compatibility layer: Some cli scripts rely on this, like ext:phpunit cli
        // This layer will be removed in TYPO3 CMS 8
        $GLOBALS['temp_cliScriptPath'] = array_shift($_SERVER['argv']);
        $GLOBALS['temp_cliKey'] = array_shift($_SERVER['argv']);
        array_unshift($_SERVER['argv'], $GLOBALS['temp_cliScriptPath']);
        return [$commandLineScript, $commandLineName];
    }

    /**
     * If the backend script is in CLI mode, it will try to load a backend user named by the CLI module name (in lowercase)
     *
     * @param string $commandLineName the name of the module registered inside $TYPO3_CONF_VARS[SC_OPTIONS][GLOBAL][cliKeys] as second parameter
     * @throws \RuntimeException if a non-admin Backend user could not be loaded
     */
    protected function loadCommandLineBackendUser($commandLineName)
    {
        if ($GLOBALS['BE_USER']->user['uid']) {
            throw new \RuntimeException('Another user was already loaded which is impossible in CLI mode!', 3);
        }
        if (!StringUtility::beginsWith($commandLineName, '_CLI_')) {
            throw new \RuntimeException('Module name, "' . $commandLineName . '", was not prefixed with "_CLI_"', 3);
        }
        $userName = strtolower($commandLineName);
        $GLOBALS['BE_USER']->setBeUserByName($userName);
        if (!$GLOBALS['BE_USER']->user['uid']) {
            throw new \RuntimeException('No backend user named "' . $userName . '" was found!', 3);
        }
        if ($GLOBALS['BE_USER']->isAdmin()) {
            throw new \RuntimeException('CLI backend user "' . $userName . '" was ADMIN which is not allowed!', 3);
        }
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
