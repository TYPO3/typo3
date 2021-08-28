<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extensionmanager\Command;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Event\PackagesMayHaveChangedEvent;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Command for activating an existing extension via CLI.
 */
class ActivateExtensionCommand extends Command
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var InstallUtility
     */
    private $installUtility;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        InstallUtility $installUtility
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->installUtility = $installUtility;
        parent::__construct();
    }

    /**
     * This command is not needed in composer mode.
     *
     * @inheritdoc
     */
    public function isEnabled()
    {
        return !Environment::isComposerMode();
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this
            ->setDescription('Activates an extension by key')
            ->setHelp('The extension files must be present in one of the recognized extension folder paths in TYPO3.')
            ->setAliases(['extensionmanager:extension:install', 'extension:install'])
            ->addArgument(
                'extensionkey',
                InputArgument::REQUIRED,
                'The extension key of a currently deactivated extension, located in one of TYPO3\'s extension paths.'
            );
    }

    /**
     * Installs an extension
     *
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $extensionKey = $input->getArgument('extensionkey');

        // Ensure the _cli_ user is authenticated because the extension might import data
        Bootstrap::initializeBackendAuthentication();

        // Emits packages may have changed signal
        $this->eventDispatcher->dispatch(new PackagesMayHaveChangedEvent());

        // Do the installation process
        $this->installUtility->install($extensionKey);

        $io->success('Activated extension ' . $extensionKey . ' successfully.');
        return 0;
    }
}
