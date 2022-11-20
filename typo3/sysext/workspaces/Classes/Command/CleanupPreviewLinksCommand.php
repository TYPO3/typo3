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

namespace TYPO3\CMS\Workspaces\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Removes unused records from sys_preview
 */
class CleanupPreviewLinksCommand extends Command
{
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
        parent::__construct();
    }

    /**
     * Configuring the command options
     */
    public function configure()
    {
        $this->setHelp('Look for preview links within the database table "sys_preview" that have been expired and and remove them. This command should be called regularly when working with workspaces.');
    }

    /**
     * Executes the command to find versioned records
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_preview');
        /** @var int $affectedRows */
        $affectedRows = $queryBuilder
            ->delete('sys_preview')
            ->where(
                $queryBuilder->expr()->lt(
                    'endtime',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)
                )
            )
            ->executeStatement();

        if ($affectedRows > 0) {
            $io->success('Cleaned up ' . $affectedRows . ' preview links.');
        } else {
            $io->note('No expired preview links found. All done.');
        }
        return Command::SUCCESS;
    }
}
