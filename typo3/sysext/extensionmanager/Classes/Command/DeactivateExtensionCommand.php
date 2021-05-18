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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Command for deactivating an extension via CLI.
 */
class DeactivateExtensionCommand extends Command
{
    /**
     * @var InstallUtility
     */
    private $installUtility;

    public function __construct(InstallUtility $installUtility)
    {
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

        $this->installUtility->uninstall($extensionKey);

        $io->success('Deactivated extension "' . $extensionKey . '" successfully.');
        return 0;
    }
}
