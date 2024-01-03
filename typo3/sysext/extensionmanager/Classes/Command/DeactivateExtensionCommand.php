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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Command for deactivating an extension via CLI.
 */
class DeactivateExtensionCommand extends Command
{
    public function __construct(private readonly InstallUtility $installUtility)
    {
        parent::__construct();
    }

    /**
     * This command is not needed in composer mode.
     */
    public function isEnabled(): bool
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $extensionKey = $input->getArgument('extensionkey');

        // @todo: Extbase BackendConfigurationManager triggered by repository calls needs a Request
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->installUtility->uninstall($extensionKey);

        $io->success('Deactivated extension "' . $extensionKey . '" successfully.');
        return Command::SUCCESS;
    }
}
