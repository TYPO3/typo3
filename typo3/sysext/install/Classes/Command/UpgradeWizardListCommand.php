<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\Command;

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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    /**
     * @var UpgradeWizardsService
     */
    private $upgradeWizardsService;

    /**
     * @var OutputInterface|\Symfony\Component\Console\Style\StyleInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * Bootstrap running of upgradeWizards
     */
    protected function bootstrap(): void
    {
        Bootstrap::loadTypo3LoadedExtAndExtLocalconf(false);
        Bootstrap::unsetReservedGlobalVariables();
        Bootstrap::loadBaseTca(false);
        Bootstrap::loadExtTables(false);
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
     *
     * @param InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = new SymfonyStyle($input, $output);
        $this->input = $input;
        $this->bootstrap();
        $this->upgradeWizardsService = new UpgradeWizardsService();

        $result = 0;
        $wizards = [];
        $all = $input->getOption('all');
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $identifier => $wizardToExecute) {
            $upgradeWizard = $this->getWizard($wizardToExecute, $identifier, (bool)$all);
            if ($upgradeWizard !== null) {
                $wizardInfo = [
                    'identifier' => $upgradeWizard->getIdentifier(),
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
        } else {
            if ($all === true) {
                $this->output->table(['Identifier', 'Title', 'Description', 'Status'], $wizards);
            } else {
                $this->output->table(['Identifier', 'Title', 'Description'], $wizards);
            }
        }
        return $result;
    }

    /**
     * Get Wizard instance by class name and identifier
     * Returns null if wizard is already done
     *
     * @param string $className
     * @param string $identifier
     * @param bool $all
     * @return \TYPO3\CMS\Install\Updates\UpgradeWizardInterface|null
     */
    protected function getWizard(string $className, string $identifier, $all = false): ?UpgradeWizardInterface
    {
        // already done
        if (!$all && $this->upgradeWizardsService->isWizardDone($identifier)) {
            return null;
        }

        $wizardInstance = GeneralUtility::makeInstance($className);
        if ($wizardInstance instanceof ChattyInterface) {
            $wizardInstance->setOutput($this->output);
        }

        if (!($wizardInstance instanceof UpgradeWizardInterface)) {
            return null;
        }

        return !$all ? $wizardInstance->updateNecessary() ? $wizardInstance : null : $wizardInstance;
    }
}
