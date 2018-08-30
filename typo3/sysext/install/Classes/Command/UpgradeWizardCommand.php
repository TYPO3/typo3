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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\PrerequisiteCollection;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Core function for updating language packs
 */
class UpgradeWizardCommand extends Command
{
    /**
     * @var UpgradeWizardsService
     */
    private $upgradeWizardsService;

    protected function bootstrap(): void
    {
        \TYPO3\CMS\Core\Core\Bootstrap::loadTypo3LoadedExtAndExtLocalconf(false);
        \TYPO3\CMS\Core\Core\Bootstrap::unsetReservedGlobalVariables();
        \TYPO3\CMS\Core\Core\Bootstrap::loadBaseTca(false);
        \TYPO3\CMS\Core\Core\Bootstrap::loadExtTables(false);
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setAliases(['install:wizards']);
        $this->setDescription('Update the language files of all activated extensions')
            ->addArgument(
                'wizardName',
                InputArgument::OPTIONAL
            )->addOption('dry-run', 'd');
    }

    /**
     * Update language packs of all active languages for all active extensions
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap();
        $this->upgradeWizardsService = new UpgradeWizardsService();

        if ($input->getArgument('wizardName')) {
            $wizardToExecute = $input->getArgument('wizardName');
            $this->runSingleWizard($input, $output, $wizardToExecute);
        } else {
            $this->runAllWizards($input, $output);
        }
    }

    /**
     * Handles prerequisites of update wizards, allows a more flexible definition and declaration of dependencies
     * Currently implemented prerequisites include "database needs to be up-to-date" and "referenceIndex needs to be up-
     * to-date"
     * At the moment the install tool automatically displays the database updates when necessary but can't do more
     * prerequisites
     *
     * @todo handle in install tool
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param UpgradeWizardInterface[]
     */
    protected function handlePrerequisites(OutputInterface $output, array $instances): void
    {
        $collection = GeneralUtility::makeInstance(PrerequisiteCollection::class);
        foreach ($instances as $instance) {
            foreach ($instance->getPrerequisites() as $prerequisite) {
                $collection->addPrerequisite($prerequisite);
            }
        }
        foreach ($collection->getPrerequisites() as $prerequisite) {
            if (!$prerequisite->met()) {
                $output->writeln('Prerequisite "' . $prerequisite->getName() . '" not met, will ensure.');
                $prerequisite->ensure();
            } else {
                $output->writeln('Prerequisite "' . $prerequisite->getName() . '" met.');
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $wizardToExecute
     * @return int
     */
    protected function runSingleWizard(
        InputInterface $input,
        OutputInterface $output,
        UpgradeWizardInterface $instance
    ): int {
        $output->writeln('Running Wizard ' . $instance->getTitle());
        if ($instance instanceof ConfirmableInterface) {
            $defaultString = $instance->getConfirmationDefault() ? '(Y/n)' : '(y/N)';
            $question = new ConfirmationQuestion(
                '<info>' .
                $instance->getConfirmationTitle() .
                '</info>' .
                "\r\n" .
                $instance->getConfirmationMessage() .
                ' ' .
                $defaultString,
                $instance->getConfirmationDefault()
            );
            $helper = $this->getHelper('question');
            if (!$helper->ask($input, $output, $question)) {
                $this->upgradeWizardsService->markWizardAsDone($instance->getIdentifier());
                return 0;
            }
        }
        if ($instance->executeUpdate()) {
            $output->writeln('<info>Successfully ran wizard ' . $instance->getTitle() . '</info>');
            $this->upgradeWizardsService->markWizardAsDone($instance->getIdentifier());
            return 0;
        }
        $output->writeln('<error>Something went wrong while running ' . $instance->getTitle() . '</error>');
        return 1;
    }

    /**
     * Get list of registered upgrade wizards.
     *
     * @return array List of upgrade wizards in correct order with detail information
     */
    public function runAllWizards(InputInterface $input, OutputInterface $output): array
    {
        $wizards = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $identifier => $class) {
            $wizardInstance = GeneralUtility::makeInstance($class);
            $registry = GeneralUtility::makeInstance(Registry::class);
            $markedDoneInRegistry = $registry->get(
                'installUpdate',
                $identifier,
                false
            );
            // already done
            if ($markedDoneInRegistry) {
                continue;
            }
            // not cli runnable
            if (!($wizardInstance instanceof UpgradeWizardInterface)) {
                continue;
            }

            if ($wizardInstance instanceof ChattyInterface) {
                $wizardInstance->setOutput($output);
            }

            if ($wizardInstance->updateNecessary()) {
                $wizardInstances = [];
                if (!($wizardInstance instanceof UpgradeWizardInterface)) {
                    $output->writeln(
                        'Wizard ' .
                        $class .
                        ' needs to be manually run from the install tool, as it does not implement ' .
                        UpgradeWizardInterface::class
                    );
                } else {
                    $wizardInstances[] = $wizardInstance;
                }
                if (count($wizardInstances) > 0) {
                    $this->handlePrerequisites($output, $wizardInstances);
                    foreach ($wizardInstances as $wizardInstance) {
                        $this->runSingleWizard($input, $output, $wizardInstance);
                    }
                }
            }
        }
        return $wizards;
    }
}
