<?php

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

namespace TYPO3\CMS\Backend\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Command\ProgressListener\ReferenceIndexProgressListener;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Core function to check/update the Reference Index
 */
class ReferenceIndexUpdateCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this->addOption(
            'check',
            'c',
            InputOption::VALUE_NONE,
            'Only check the reference index of TYPO3'
        );
    }

    /**
     * Executes the command for adding or removing the lock file
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Bootstrap::initializeBackendAuthentication();
        $io = new SymfonyStyle($input, $output);

        $isTestOnly = $input->getOption('check');

        $progressListener = GeneralUtility::makeInstance(ReferenceIndexProgressListener::class);
        $progressListener->initialize($io);
        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        if ($isTestOnly) {
            $io->section('Reference Index being TESTED (nothing written, remove the "--check" argument)');
        } else {
            $io->section('Reference Index is now being updated');
        }
        $referenceIndex->updateIndex($isTestOnly, $progressListener);
        return 0;
    }
}
