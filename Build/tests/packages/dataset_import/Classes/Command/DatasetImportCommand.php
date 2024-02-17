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

namespace TYPO3Tests\DatasetImport\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\DataSet;

/**
 * CLI command for setting up TYPO3 via CLI
 */
#[AsCommand('dataset:import', 'Import Dataset')]
class DatasetImportCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to CSV dataset to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists(DataSet::class)) {
            $io = new SymfonyStyle($input, $output);
            $io->getErrorStyle()->error('Missing typo3/testing-framework dependency.');
            return Command::FAILURE;
        }
        DataSet::import($input->getArgument('path'));
        return Command::SUCCESS;
    }

}
