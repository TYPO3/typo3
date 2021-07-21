<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\Command;

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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\CMS\Styleguide\TcaDataGenerator\GeneratorFrontend;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Generate TCA for Styleguide backend (create / delete)
 */
class GeneratorCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::OPTIONAL, 'Create page tree data, valid arguments are "tca", "frontend" and "all"', 'all');
        $this->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete page tree and its records for the selected type');
        $this->addOption('create', 'c', InputOption::VALUE_NONE, 'Create page tree and its records for the selected type');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        if (!$input->getOption('create') && !$input->getOption('delete')) {
            $output->writeln('<info>Please specify an option "--create" or "--delete"</info>');
        }

        switch ($input->getArgument('type')) {
            case 'tca':
                if ($input->getOption('create')) {
                    $this->createTca($output);
                }

                if ($input->getOption('delete')) {
                    $this->deleteTca($output);
                }
                break;

            case 'frontend':
                if ($input->getOption('create')) {
                    $this->createFrontend($output);
                }

                if ($input->getOption('delete')) {
                    $this->deleteFrontend($output);
                }
                break;

            case 'all':
                if ($input->getOption('create')) {
                    $this->createTca($output);
                    $this->createFrontend($output);
                }

                if ($input->getOption('delete')) {
                    $this->deleteTca($output);
                    $this->deleteFrontend($output);
                }

                break;
            default:
                $output->writeln('<error>Please specify a valid action. Choose "tca", "frontend" or "all"</error>');
                return 1;
        }

        return 0;
    }

    private function createTca(OutputInterface $output): int
    {
        /** @var RecordFinder $finder */
        $finder = GeneralUtility::makeInstance(RecordFinder::class);
        if (count($finder->findUidsOfStyleguideEntryPages())) {
            $output->writeln('<warning>TCA page tree already exists!</warning>');
        } else {
            $generator = GeneralUtility::makeInstance(Generator::class);
            $generator->create();
            $output->writeln('<info>TCA page tree created!</info>');
        }

        return 0;
    }

    private function deleteTca(OutputInterface $output): int
    {
        /** @var Generator $generator */
        $generator = GeneralUtility::makeInstance(Generator::class);
        $generator->delete();
        $output->writeln('<info>TCA page tree deleted!</info>');

        return 0;
    }

    private function createFrontend(OutputInterface $output): int
    {
        /** @var RecordFinder $recordFinder */
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);

        if (count($recordFinder->findUidsOfFrontendPages())) {
            $output->writeln('<info>Frontend page tree already exists!</info>');
        } else {
            /** @var GeneratorFrontend $frontend */
            $frontend = GeneralUtility::makeInstance(GeneratorFrontend::class);
            $frontend->create();
            $output->writeln('<info>Frontend page tree created!</info>');
        }

        return 0;
    }

    private function deleteFrontend(OutputInterface $output): int
    {
        /** @var GeneratorFrontend $frontend */
        $frontend = GeneralUtility::makeInstance(GeneratorFrontend::class);
        $frontend->delete();

        $output->writeln('<info>Frontend page tree deleted!</info>');

        return 0;
    }
}
