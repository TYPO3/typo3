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
use TYPO3\CMS\Core\Core\Bootstrap;
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
     * Configuring the command options
     */
    public function configure()
    {
        $this
            ->setDescription('Publish a workspace with a publication date.')
            ->setHelp('Some workspaces can have an auto-publish publication date to put all "ready to publish" content online on a certain date.');
    }

    /**
     * Executes the command to find versioned records
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        $io = new SymfonyStyle($input, $output);

        $workspaceService = GeneralUtility::makeInstance(WorkspaceService::class);

        // Select all workspaces that needs to be published / unpublished
        $statement = $this->getAffectedWorkspacesToPublish();

        $affectedWorkspaces = 0;
        while ($workspaceRecord = $statement->fetch()) {
            // First, clear start/end time so it doesn't get selected once again
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_workspace')
                ->update(
                    'sys_workspace',
                    ['publish_time' => 0],
                    ['uid' => (int)$workspaceRecord['uid']]
                );

            // Get CMD array
            $cmd = $workspaceService->getCmdArrayForPublishWS(
                $workspaceRecord['uid'],
                (int)$workspaceRecord['swap_modes'] === 1
            );
            // $workspaceRecord['swap_modes'] == 1 means that auto-publishing will swap versions,
            // not just publish and empty the workspace.
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
    }

    /**
     * Fetch all sys_workspace records which could fit
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    protected function getAffectedWorkspacesToPublish()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_workspace');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'swap_modes', 'publish_time')
            ->from('sys_workspace')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'publish_time',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->lte(
                    'publish_time',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                )
            )
            ->execute();
    }
}
