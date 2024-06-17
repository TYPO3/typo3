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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Authentication\BackendLocker;

/**
 * Core function for locking the TYPO3 Backend
 */
#[AsCommand('backend:lock', 'Lock the TYPO3 Backend')]
class LockBackendCommand extends Command
{
    public function __construct(protected readonly BackendLocker $lockService, ?string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this
            ->addArgument(
                'redirect',
                InputArgument::OPTIONAL,
                'If set, a locked TYPO3 Backend will redirect to URI specified with this argument. The URI is saved as a string in the lockfile that is specified in the system configuration.'
            );
    }

    /**
     * Executes the command for adding the lock file
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        if ($this->lockService->isLocked()) {
            $io->note('A lock file already exists. Overwriting it.');
        }
        $output = 'Wrote lock file to "' . $this->lockService->getAbsolutePathToLockFile() . '"';
        if ($input->getArgument('redirect')) {
            $redirectUriFromLockFileContent = $input->getArgument('redirect');
            $redirectUriFromLockFileContent = is_string($redirectUriFromLockFileContent) ? $redirectUriFromLockFileContent : '';
            $output .= LF . 'with target URI "' . $redirectUriFromLockFileContent . '".';
        } else {
            $redirectUriFromLockFileContent = '';
            $output .= '.';
        }
        $this->lockService->lockBackend($redirectUriFromLockFileContent);
        $io->success($output);
        return Command::SUCCESS;
    }
}
