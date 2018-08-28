<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Workspaces\Command;

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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Removes unused records from sys_preview
 */
class CleanupPreviewLinksCommand extends Command
{

    /**
     * Configuring the command options
     */
    public function configure()
    {
        $this
            ->setDescription('Clean up expired preview links from shared workspace previews.')
            ->setHelp('Look for preview links within the database table "sys_preview" that have been expired and and remove them. This command should be called regularly when working with workspaces.');
    }

    /**
     * Executes the command to find versioned records
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_preview');
        $affectedRows = $queryBuilder
            ->delete('sys_preview')
            ->where(
                $queryBuilder->expr()->lt(
                    'endtime',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                )
            )
            ->execute();

        if ($affectedRows > 0) {
            $io->success('Cleaned up ' . $affectedRows . ' preview links.');
        } else {
            $io->note('No expired preview links found. All done.');
        }
    }
}
