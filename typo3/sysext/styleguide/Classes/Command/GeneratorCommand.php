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

namespace TYPO3\CMS\Styleguide\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\CMS\Styleguide\TcaDataGenerator\GeneratorFrontend;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Generate TCA for Styleguide backend (create / delete)
 *
 * @internal
 */
#[AsCommand('styleguide:generate', 'Generate page tree for Styleguide TCA backend and/or Styleguide frontend')]
final class GeneratorCommand extends Command
{
    public function __construct(
        private readonly Generator $generator,
        private readonly GeneratorFrontend $generatorFrontend,
        private readonly RecordFinder $recordFinder,
    ) {
        parent::__construct('styleguide:generate');
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::OPTIONAL, 'Create page tree data, valid arguments are "tca", "frontend", "frontend-systemplate" and "all"', 'all');
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
            $output->writeln('<comment>Please specify an option "--create" or "--delete"</comment>');
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
                    $this->createFrontend($output, true);
                }

                if ($input->getOption('delete')) {
                    $this->deleteFrontend($output);
                }
                break;

            case 'frontend-systemplate':
                if ($input->getOption('create')) {
                    $this->createFrontend($output, false);
                }

                if ($input->getOption('delete')) {
                    $this->deleteFrontend($output);
                }
                break;

            case 'all':
                if ($input->getOption('create')) {
                    $this->createTca($output);
                    $this->createFrontend($output, true);
                }

                if ($input->getOption('delete')) {
                    $this->deleteTca($output);
                    $this->deleteFrontend($output);
                }

                break;
            default:
                $output->writeln('<error>Please specify a valid action. Choose "tca", "frontend", "frontend-systemplate" or "all"</error>');
                return 1;
        }

        return 0;
    }

    private function createTca(OutputInterface $output): int
    {
        if (count($this->recordFinder->findUidsOfStyleguideEntryPages())) {
            $output->writeln('<comment>TCA page tree already exists!</comment>');
        } else {
            $this->generator->create();
            $output->writeln('<info>TCA page tree created!</info>');
        }

        return 0;
    }

    private function deleteTca(OutputInterface $output): int
    {
        $this->generator->delete();
        $output->writeln('<info>TCA page tree deleted!</info>');

        return 0;
    }

    private function createFrontend(OutputInterface $output, bool $useSiteSets): int
    {

        if (count($this->recordFinder->findUidsOfFrontendPages())) {
            $output->writeln('<info>Frontend page tree already exists!</info>');
        } else {
            $this->generatorFrontend->create('', 1, $useSiteSets);
            $output->writeln('<info>Frontend page tree created!</info>');
        }

        return 0;
    }

    private function deleteFrontend(OutputInterface $output): int
    {
        $this->generatorFrontend->delete();

        $output->writeln('<info>Frontend page tree deleted!</info>');

        return 0;
    }
}
