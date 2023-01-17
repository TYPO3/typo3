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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Upgrade wizard command for listing wizards
 *
 * @internal
 */
class UpgradeWizardListCommand extends Command
{
    private UpgradeWizardsService $upgradeWizardsService;

    /**
     * @var OutputInterface|\Symfony\Component\Console\Style\StyleInterface
     */
    private $output;

    public function __construct(string $name, private readonly LateBootService $lateBootService)
    {
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
    protected function configure()
    {
        $this->setDescription('List available upgrade wizards.')
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Include wizards already done.'
            );
    }

    /**
     * List available upgrade wizards. If -all is given, already done wizards are listed, too.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = new SymfonyStyle($input, $output);
        $this->bootstrap();

        $wizards = [];
        $all = $input->getOption('all');
        foreach ($this->upgradeWizardsService->getUpgradeWizardIdentifiers() as $identifier) {
            $upgradeWizard = $this->getWizard($identifier, (bool)$all);
            if ($upgradeWizard !== null) {
                $wizardInfo = [
                    'identifier' => $identifier,
                    'title' => $upgradeWizard->getTitle(),
                    'description' => wordwrap($upgradeWizard->getDescription()),
                ];
                if ($all === true) {
                    $wizardInfo['status'] = $this->upgradeWizardsService->isWizardDone($identifier) ? 'DONE' : 'AVAILABLE';
                }
                $wizards[] = $wizardInfo;
            }
        }
        if (empty($wizards)) {
            $this->output->success('No wizards available.');
        } elseif ($all === true) {
            $this->output->table(['Identifier', 'Title', 'Description', 'Status'], $wizards);
        } else {
            $this->output->table(['Identifier', 'Title', 'Description'], $wizards);
        }
        return Command::SUCCESS;
    }

    /**
     * Get Wizard instance by identifier
     * Returns null if wizard is already done
     */
    protected function getWizard(string $identifier, bool $all = false): ?UpgradeWizardInterface
    {
        // already done
        if (!$all && $this->upgradeWizardsService->isWizardDone($identifier)) {
            return null;
        }

        $wizard = $this->upgradeWizardsService->getUpgradeWizard($identifier);
        if ($wizard === null) {
            return null;
        }

        if ($wizard instanceof ChattyInterface) {
            $wizard->setOutput($this->output);
        }

        return !$all ? $wizard->updateNecessary() ? $wizard : null : $wizard;
    }
}
