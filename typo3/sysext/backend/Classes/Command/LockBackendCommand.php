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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Core function for locking the TYPO3 Backend
 */
class LockBackendCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this
            ->addArgument(
                'redirect',
                InputArgument::OPTIONAL,
                'If set, then the TYPO3 Backend will redirect to the locking state (only used when locking the TYPO3 Backend'
            );
    }

    /**
     * Executes the command for adding the lock file
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        $lockFile = $this->getLockFileName();
        if (@is_file($lockFile)) {
            $io->note('A lock file already exists. Overwriting it.');
        }
        $output = 'Wrote lock file to "' . $lockFile . '"';
        if ($input->getArgument('redirect')) {
            $lockFileContent = $input->getArgument('redirect');
            $lockFileContent = is_string($lockFileContent) ? $lockFileContent : '';
            $output .= LF . 'with content "' . $lockFileContent . '".';
        } else {
            $lockFileContent = '';
            $output .= '.';
        }
        GeneralUtility::writeFile($lockFile, $lockFileContent);
        $io->success($output);
        return 0;
    }

    /**
     * Location of the file name
     *
     * @return string
     */
    protected function getLockFileName()
    {
        return Environment::getLegacyConfigPath() . '/LOCK_BACKEND';
    }
}
