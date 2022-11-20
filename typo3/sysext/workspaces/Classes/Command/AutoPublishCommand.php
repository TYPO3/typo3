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
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Call on the workspace logic to publish workspaces whose publication date is in the past
 */
class AutoPublishCommand extends Command
{
    /**
     * @var WorkspaceService
     */
    private $workspaceService;

    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    public function __construct(WorkspaceService $workspaceService, ConnectionPool $connectionPool)
    {
        $this->workspaceService = $workspaceService;
        $this->connectionPool = $connectionPool;
        parent::__construct();
    }

    /**
     * Configuring the command options
     */
    public function configure()
    {
        $this->setHelp('Some workspaces can have an auto-publish publication date to put all "ready to publish" content online on a certain date.');
    }

    /**
     * Executes the command to find versioned records
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        $io = new SymfonyStyle($input, $output);

        // Select all workspaces that needs to be published / unpublished
        $statement = $this->getAffectedWorkspacesToPublish();

        $affectedWorkspaces = 0;
        while ($workspaceRecord = $statement->fetchAssociative()) {
            // First, clear start/end time so it doesn't get selected once again
            $this->connectionPool
                ->getConnectionForTable('sys_workspace')
                ->update(
                    'sys_workspace',
                    ['publish_time' => 0],
                    ['uid' => (int)$workspaceRecord['uid']]
                );

            // Get CMD array
            $cmd = $this->workspaceService->getCmdArrayForPublishWS($workspaceRecord['uid']);
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], $cmd);
            $tce->process_cmdmap();
            $affectedWorkspaces++;
        }

        if ($affectedWorkspaces > 0) {
            $io->success('Published ' . $affectedWorkspaces . ' workspaces.');
        } else {
            $io->note('Nothing to do.');
        }
        return Command::SUCCESS;
    }

    /**
     * Fetch all sys_workspace records which could fit
     * @return \Doctrine\DBAL\Result
     */
    protected function getAffectedWorkspacesToPublish()
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_workspace');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'publish_time')
            ->from('sys_workspace')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'publish_time',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->lte(
                    'publish_time',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)
                )
            )
            ->executeQuery();
    }
}
