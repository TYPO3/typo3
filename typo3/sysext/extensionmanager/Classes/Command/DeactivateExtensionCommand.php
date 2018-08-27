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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Command for deactivating an extension via CLI.
 */
class DeactivateExtensionCommand extends Command
{
    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this
            ->setDescription('Deactivates an extension by extension key')
            ->setAliases(['extensionmanager:extension:uninstall', 'extension:uninstall'])
            ->addArgument(
                'extensionkey',
                InputArgument::REQUIRED,
                'The extension key of a currently activated extension.'
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

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $objectManager->get(InstallUtility::class)->uninstall($extensionKey);

        $io->success('Deactivated extension "' . $extensionKey . '" successfully.');
    }
}
