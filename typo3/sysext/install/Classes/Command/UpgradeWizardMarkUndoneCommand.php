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

namespace TYPO3\CMS\Install\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;

/**
 * Upgrade wizard command for marking wizards as undone
 *
 * @internal
 */
class UpgradeWizardMarkUndoneCommand extends Command
{
    private UpgradeWizardsService $upgradeWizardsService;

    public function __construct(
        string $name,
        private readonly LateBootService $lateBootService
    ) {
        parent::__construct($name);
    }

    /**
     * Bootstrap running of upgradeWizards
     */
    protected function bootstrap(): void
    {
        $this->upgradeWizardsService = $this->lateBootService
            ->loadExtLocalconfDatabaseAndExtTables(false)
            ->get(UpgradeWizardsService::class);
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        Bootstrap::initializeBackendAuthentication();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setDescription('Mark upgrade wizard as undone.')
            ->addArgument(
                'wizardIdentifier',
                InputArgument::REQUIRED
            );
    }

    /**
     * Mark an upgrade wizard as undone
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->bootstrap();
        $wizardIdentifier = (string)$input->getArgument('wizardIdentifier');
        $wizardInformation = $this->upgradeWizardsService->getWizardInformationByIdentifier($wizardIdentifier);
        $hasBeenMarkedUndone = $this->upgradeWizardsService->markWizardUndone($wizardIdentifier);
        if ($hasBeenMarkedUndone) {
            $io->success('The wizard "' . $wizardInformation['title'] . '" has been marked as undone.');
            return Command::SUCCESS;
        }
        $io->error('The wizard "' . $wizardInformation['title'] . '" could not be marked undone, because it was most likely not yet run.');
        return Command::FAILURE;
    }
}
