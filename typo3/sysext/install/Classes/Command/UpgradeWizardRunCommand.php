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
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\DatabaseUpgradeWizardsService;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\PrerequisiteCollection;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Upgrade wizard command for running wizards
 *
 * @internal
 */
class UpgradeWizardRunCommand extends Command
{
    private UpgradeWizardsService $upgradeWizardsService;

    /**
     * @var OutputInterface|\Symfony\Component\Console\Style\StyleInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    public function __construct(
        string $name,
        private readonly LateBootService $lateBootService,
        private readonly DatabaseUpgradeWizardsService $databaseUpgradeWizardsService
    ) {
        parent::__construct($name);
    }

    /**
     * Bootstrap running of upgrade wizard,
     * ensure database is utf-8
     */
    protected function bootstrap(): void
    {
        $this->upgradeWizardsService = $this->lateBootService
            ->loadExtLocalconfDatabaseAndExtTables(false)
            ->get(UpgradeWizardsService::class);
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        Bootstrap::initializeBackendAuthentication();
        $this->databaseUpgradeWizardsService->isDatabaseCharsetUtf8()
            ?: $this->databaseUpgradeWizardsService->setDatabaseCharsetUtf8();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setDescription('Run upgrade wizard. Without arguments all available wizards will be run.')
            ->addArgument(
                'wizardName',
                InputArgument::OPTIONAL
            )->setHelp(
                'This command allows running upgrade wizards on CLI. To run a single wizard add the ' .
                'identifier of the wizard as argument. The identifier of the wizard is the name it is ' .
                'registered with in ext_localconf.'
            );
    }

    /**
     * Update language packs of all active languages for all active extensions
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = new SymfonyStyle($input, $output);
        $this->input = $input;
        $this->bootstrap();

        if ($input->getArgument('wizardName')) {
            $wizardToExecute = $input->getArgument('wizardName');
            $wizardToExecute = is_string($wizardToExecute) ? $wizardToExecute : '';
            if ($this->upgradeWizardsService->isWizardDone($wizardToExecute)) {
                $this->output->note(sprintf('Wizard %s marked as done. Skipped.', $wizardToExecute));
                $result = Command::SUCCESS;
            } elseif (($upgradeWizard = $this->getWizard($wizardToExecute)) !== null) {
                $prerequisitesFulfilled = $this->handlePrerequisites([$upgradeWizard]);
                if ($prerequisitesFulfilled === true) {
                    $result = $this->runSingleWizard($upgradeWizard);
                } else {
                    $result = Command::FAILURE;
                }
            } else {
                $this->output->error('No such wizard: ' . $wizardToExecute);
                $result = Command::FAILURE;
            }
        } else {
            $result = $this->runAllWizards();
        }
        return $result;
    }

    /**
     * Get Wizard instance by class name and identifier
     * Returns null if wizard is already done
     */
    protected function getWizard(string $identifier): ?UpgradeWizardInterface
    {
        // already done
        if ($this->upgradeWizardsService->isWizardDone($identifier)) {
            return null;
        }

        $wizard = $this->upgradeWizardsService->getUpgradeWizard($identifier);
        if ($wizard === null) {
            return null;
        }

        if ($wizard instanceof ChattyInterface) {
            $wizard->setOutput($this->output);
        }

        if ($wizard->updateNecessary()) {
            return $wizard;
        }
        if ($wizard instanceof RepeatableInterface) {
            $this->output->note('Wizard ' . $identifier . ' does not need to make changes.');
        } else {
            $this->output->note('Wizard ' . $identifier . ' does not need to make changes. Marking wizard as done.');
            $this->upgradeWizardsService->markWizardAsDone($wizard);
        }
        return null;
    }

    /**
     * Handles prerequisites of update wizards, allows a more flexible definition and declaration of dependencies
     * Currently implemented prerequisites include "database needs to be up-to-date" and "referenceIndex needs to be up-
     * to-date"
     * At the moment the install tool automatically displays the database updates when necessary but can't do more
     * prerequisites
     *
     * @param UpgradeWizardInterface[] $instances
     */
    protected function handlePrerequisites(array $instances): bool
    {
        $prerequisites = GeneralUtility::makeInstance(PrerequisiteCollection::class);
        foreach ($instances as $instance) {
            foreach ($instance->getPrerequisites() as $prerequisite) {
                $prerequisites->add($prerequisite);
            }
        }
        $result = true;
        foreach ($prerequisites as $prerequisite) {
            if ($prerequisite instanceof ChattyInterface) {
                $prerequisite->setOutput($this->output);
            }
            if (!$prerequisite->isFulfilled()) {
                $this->output->writeln('Prerequisite "' . $prerequisite->getTitle() . '" not fulfilled, will ensure.');
                $result = $prerequisite->ensure();
                if ($result === false) {
                    $this->output->error(
                        '<error>Error running ' .
                        $prerequisite->getTitle() .
                        '. Please ensure this prerequisite manually and try again.</error>'
                    );
                    break;
                }
            } else {
                $this->output->writeln('Prerequisite "' . $prerequisite->getTitle() . '" fulfilled.');
            }
        }
        return $result;
    }

    protected function runSingleWizard(
        UpgradeWizardInterface $instance
    ): int {
        $this->output->title('Running Wizard "' . $instance->getTitle() . '"');
        if ($instance instanceof ConfirmableInterface) {
            $confirmation = $instance->getConfirmation();
            $defaultString = $confirmation->getDefaultValue() ? 'Y/n' : 'y/N';
            $question = new ConfirmationQuestion(
                sprintf(
                    '<info>%s</info>' . LF . '%s' . LF . '%s %s (%s)',
                    $confirmation->getTitle(),
                    $confirmation->getMessage(),
                    $confirmation->getConfirm(),
                    $confirmation->getDeny(),
                    $defaultString
                ),
                $confirmation->getDefaultValue()
            );
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            if (!$helper->ask($this->input, $this->output, $question)) {
                if ($confirmation->isRequired()) {
                    $this->output->error('You have to acknowledge this wizard to continue');
                    return Command::FAILURE;
                }
                if ($instance instanceof RepeatableInterface) {
                    $this->output->note('No changes applied.');
                } else {
                    $this->upgradeWizardsService->markWizardAsDone($instance);
                    $this->output->note('No changes applied, marking wizard as done.');
                }
                return Command::SUCCESS;
            }
        }
        if ($instance->executeUpdate()) {
            $this->output->success('Successfully ran wizard ' . $instance->getTitle());
            if (!$instance instanceof RepeatableInterface) {
                $this->upgradeWizardsService->markWizardAsDone($instance);
            }
            return Command::SUCCESS;
        }
        $this->output->error('<error>Something went wrong while running ' . $instance->getTitle() . '</error>');
        return Command::FAILURE;
    }

    /**
     * Get list of registered upgrade wizards.
     *
     * @return int 0 if all wizards were successful, 1 on error
     */
    public function runAllWizards(): int
    {
        $returnCode = Command::SUCCESS;
        $wizardInstances = [];
        foreach ($this->upgradeWizardsService->getUpgradeWizardIdentifiers() as $identifier) {
            $wizardInstances[] = $this->getWizard($identifier);
        }
        $wizardInstances = array_filter($wizardInstances);
        if (count($wizardInstances) > 0) {
            $prerequisitesResult = $this->handlePrerequisites($wizardInstances);
            if ($prerequisitesResult === false) {
                $returnCode = Command::FAILURE;
                $this->output->error('Error handling prerequisites, aborting.');
            } else {
                $this->output->title('Found ' . count($wizardInstances) . ' wizard(s) to run.');
                foreach ($wizardInstances as $wizardInstance) {
                    $result = $this->runSingleWizard($wizardInstance);
                    if ($result > 0) {
                        $returnCode = Command::FAILURE;
                    }
                }
            }
        } else {
            $this->output->success('No wizards left to run.');
        }
        return $returnCode;
    }
}
