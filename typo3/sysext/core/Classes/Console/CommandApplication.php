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
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
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
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\SymfonyPsrEventDispatcherAdapter\EventDispatcherAdapter as SymfonyEventDispatcher;

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

    protected LanguageServiceFactory $languageServiceFactory;

    protected Application $application;

    public function __construct(
        Context $context,
        CommandRegistry $commandRegistry,
        EventDispatcherInterface $eventDispatcher,
        ConfigurationManager $configurationMananger,
        BootService $bootService,
        LanguageServiceFactory $languageServiceFactory
    ) {
        $this->context = $context;
        $this->commandRegistry = $commandRegistry;
        $this->configurationManager = $configurationMananger;
        $this->bootService = $bootService;
        $this->languageServiceFactory = $languageServiceFactory;

        $this->checkEnvironmentOrDie();
        $this->application = new Application('TYPO3 CMS', sprintf(
            '%s (Application Context: <comment>%s</comment>)',
            (new Typo3Version())->getVersion(),
            Environment::getContext()
        ));
        $this->application->setAutoExit(false);
        $this->application->setDispatcher($eventDispatcher);
        $this->application->setCommandLoader($commandRegistry);
        // Replace default list command with TYPO3 override
        $this->application->add($commandRegistry->get('list'));
    }

    /**
     * Run the Symfony Console application in this TYPO3 application
     *
     * @param callable $execute Deprecated, will be removed in TYPO3 v12.0
     */
    public function run(callable $execute = null)
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput();

        $commandName = $this->getCommandName($input);
        if ($this->wantsFullBoot($commandName)) {
            // Do a full container boot if command is not a 1:1 matching low-level command
            $container = $this->bootService->getContainer();
            $eventDispatcher = $container->get(SymfonyEventDispatcher::class);
            $commandRegistry = $container->get(CommandRegistry::class);
            $this->application->setDispatcher($eventDispatcher);
            $this->application->setCommandLoader($commandRegistry);
            $this->context = $container->get(Context::class);

            $realName = $this->resolveShortcut($commandName, $commandRegistry);
            $isLowLevelCommandShortcut = $realName !== null && !$this->wantsFullBoot($realName);
            // Load ext_localconf, except if a low level command shortcut was found
            // or if essential configuration is missing
            if (!$isLowLevelCommandShortcut && Bootstrap::checkIfEssentialConfigurationExists($this->configurationManager)) {
                $this->bootService->loadExtLocalconfDatabaseAndExtTables();
            }
        }

        $this->initializeContext();
        // create the BE_USER object (not logged in yet)
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        $GLOBALS['LANG'] = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
        // Make sure output is not buffered, so command-line output and interaction can take place
        ob_clean();

        $exitCode = $this->application->run($input, $output);

        if ($execute !== null) {
            trigger_error('Custom execution of Application code will be removed in TYPO3 v12.0.', E_USER_DEPRECATED);
            $execute();
        }

        exit($exitCode);
    }

    private function resolveShortcut(string $commandName, CommandRegistry $commandRegistry): ?string
    {
        if ($commandRegistry->has($commandName)) {
            return $commandName;
        }

        $allCommands = $commandRegistry->getNames();
        $expr = implode('[^:]*:', array_map('preg_quote', explode(':', $commandName))) . '[^:]*';
        $commands = preg_grep('{^' . $expr . '}', $allCommands);

        if ($commands === false || count($commands) === 0) {
            $commands = preg_grep('{^' . $expr . '}i', $allCommands);
        }

        if ($commands === false || count($commands) !== 1) {
            return null;
        }

        return reset($commands);
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
