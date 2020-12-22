<?php

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

namespace TYPO3\CMS\Core\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\ApplicationInterface;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Entry point for the TYPO3 Command Line for Commands
 * In addition to a simple Symfony Command, this also sets up a CLI user
 */
class CommandApplication implements ApplicationInterface
{
    protected Context $context;

    protected CommandRegistry $commandRegistry;

    protected ConfigurationManager $configurationManager;

    protected BootService $bootService;

    protected Application $application;

    public function __construct(
        Context $context,
        CommandRegistry $commandRegistry,
        ConfigurationManager $configurationMananger,
        BootService $bootService
    ) {
        $this->context = $context;
        $this->commandRegistry = $commandRegistry;
        $this->configurationManager = $configurationMananger;
        $this->bootService = $bootService;

        $this->checkEnvironmentOrDie();
        $this->application = new Application('TYPO3 CMS', sprintf(
            '%s (Application Context: <comment>%s</comment>)',
            (new Typo3Version())->getVersion(),
            Environment::getContext()
        ));
        $this->application->setAutoExit(false);
        $this->application->setCommandLoader($commandRegistry);
        // Replace default list command with TYPO3 override
        $this->application->add($commandRegistry->get('list'));
    }

    /**
     * Run the Symfony Console application in this TYPO3 application
     *
     * @param callable $execute
     */
    public function run(callable $execute = null)
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput();

        $commandName = $this->getCommandName($input);
        if ($this->wantsFullBoot($commandName)) {
            // Do a full boot if command is not a low-level command
            $container = $this->bootService->getContainer();
            $this->application->setCommandLoader($container->get(CommandRegistry::class));
            $this->context = $container->get(Context::class);

            $isLowLevelCommandShortcut = false;
            try {
                $realName = $this->application->find($commandName)->getName();
                // Do not load ext_localconf if a low level command was found
                // due to using a shortcut
                $isLowLevelCommandShortcut = !$this->wantsFullBoot($realName);
            } catch (ExceptionInterface $e) {
                // Errors must be ignored, full binding/validation happens later when the console application runs.
            }
            if (!$isLowLevelCommandShortcut && $this->essentialConfigurationExists()) {
                $this->bootService->loadExtLocalconfDatabaseAndExtTables();
            }
        }

        $this->initializeContext();
        // create the BE_USER object (not logged in yet)
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        $GLOBALS['LANG'] = LanguageService::createFromUserPreferences($GLOBALS['BE_USER']);
        // Make sure output is not buffered, so command-line output and interaction can take place
        ob_clean();

        $exitCode = $this->application->run($input, $output);

        if ($execute !== null) {
            call_user_func($execute);
        }

        exit($exitCode);
    }

    protected function wantsFullBoot(string $commandName): bool
    {
        if ($commandName === 'help') {
            return true;
        }
        return !$this->commandRegistry->has($commandName);
    }

    protected function getCommandName(ArgvInput $input): string
    {
        try {
            $input->bind($this->application->getDefinition());
        } catch (ExceptionInterface $e) {
            // Errors must be ignored, full binding/validation happens later when the console application runs.
        }

        return $input->getFirstArgument() ?? 'list';
    }

    /**
     * Check if LocalConfiguration.php and PackageStates.php exist
     *
     * @return bool TRUE when the essential configuration is available, otherwise FALSE
     */
    protected function essentialConfigurationExists(): bool
    {
        return file_exists($this->configurationManager->getLocalConfigurationFileLocation())
            && file_exists(Environment::getLegacyConfigPath() . '/PackageStates.php');
    }

    /**
     * Check the script is called from a cli environment.
     */
    protected function checkEnvironmentOrDie(): void
    {
        if (PHP_SAPI !== 'cli') {
            die('Not called from a command line interface (e.g. a shell or scheduler).' . LF);
        }
    }

    /**
     * Initializes the Context used for accessing data and finding out the current state of the application
     */
    protected function initializeContext(): void
    {
        $this->context->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . $GLOBALS['EXEC_TIME'])));
        $this->context->setAspect('visibility', new VisibilityAspect(true, true));
        $this->context->setAspect('workspace', new WorkspaceAspect(0));
        $this->context->setAspect('backend.user', new UserAspect(null));
    }
}
