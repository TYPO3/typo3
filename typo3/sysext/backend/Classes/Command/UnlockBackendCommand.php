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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Core function for unlocking the TYPO3 Backend
 */
class UnlockBackendCommand extends Command
{
    /**
     * Executes the command for removing the lock file
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        $lockFile = $this->getLockFileName();
        if (@is_file($lockFile)) {
            unlink($lockFile);
            if (@is_file($lockFile)) {
                $io->caution('Could not remove lock file "' . $lockFile . '"!');
                return Command::FAILURE;
            }
            $io->success('Removed lock file "' . $lockFile . '".');
        } else {
            $io->note('No lock file "' . $lockFile . '" was found.' . LF . 'Hence no lock can be removed.');
        }
        return Command::SUCCESS;
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
