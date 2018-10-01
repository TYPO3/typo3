<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extensionmanager\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Command for activating an existing extension via CLI.
 */
class ActivateExtensionCommand extends Command
{
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

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        // Emits packages may have changed signal
        $signalSlotDispatcher = $objectManager->get(Dispatcher::class);
        $signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');

        // Do the installation process
        $objectManager->get(InstallUtility::class)->install($extensionKey);

        $io->success('Activated extension ' . $extensionKey . ' successfully.');
    }
}
