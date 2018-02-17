<?php
namespace TYPO3\CMS\Backend\Command;

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
        $this->setDescription('Update the reference index of TYPO3')
            ->addOption(
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Bootstrap::initializeBackendAuthentication();

        $isTestOnly = $input->getOption('check');
        $isSilent = $output->getVerbosity() !== OutputInterface::VERBOSITY_QUIET;

        /** @var ReferenceIndex $referenceIndex */
        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        $referenceIndex->enableRuntimeCache();
        $referenceIndex->updateIndex($isTestOnly, $isSilent);
    }
}
