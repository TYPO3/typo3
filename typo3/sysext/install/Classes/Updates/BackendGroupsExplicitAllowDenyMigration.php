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

namespace TYPO3\CMS\Install\Updates;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('backendGroupsExplicitAllowDenyMigration')]
final class BackendGroupsExplicitAllowDenyMigration implements UpgradeWizardInterface, ChattyInterface
{
    private const TABLE_NAME = 'be_groups';

    private OutputInterface $output;

    public function getTitle(): string
    {
        return 'Migrate backend groups "explicit_allowdeny" field to simplified format.';
    }

    public function getDescription(): string
    {
        return 'Backend groups field "explicit_allowdeny" storage format has been simplified. This updates all be_groups records.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $needsUpdate = false;
        $queryBuilder = $this->getPreparedQueryBuilder();
        $result = $queryBuilder->select('uid', 'explicit_allowdeny')->from(self::TABLE_NAME)->executeQuery();
        while ($row = $result->fetchAssociative()) {
            // Target is a comma separated list of colon seperated "tableName:fieldName:valueName"
            // If there are four colon fields, the update should remove the last one.
            $tuples = GeneralUtility::trimExplode(',', (string)$row['explicit_allowdeny'], true);
            foreach ($tuples as $tuple) {
                if (count(GeneralUtility::trimExplode(':', $tuple, true)) > 3) {
                    $needsUpdate = true;
                    $result->free();
                    break 2;
                }
            }
        }
        return $needsUpdate;
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME);
        $queryBuilder = $this->getPreparedQueryBuilder();
        $result = $queryBuilder->select('uid', 'explicit_allowdeny')->from(self::TABLE_NAME)->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $tuples = GeneralUtility::trimExplode(',', (string)$row['explicit_allowdeny'], true);
            $newTuples = [];
            $updateNeeded = false;
            foreach ($tuples as $tuple) {
                $explodedTuples = GeneralUtility::trimExplode(':', $tuple, true);
                if (count($explodedTuples) > 3) {
                    $updateNeeded = true;
                    $newTupleString  = implode(':', array_chunk($explodedTuples, 3, true)[0]);
                    if ($explodedTuples[3] === 'DENY') {
                        // We can't migrate 'explicitDeny' values. Fully remove that tuple and add an output note.
                        $this->output->writeln(
                            '<error>Access rights setup "Explicitly allow field values" of be_groups row uid "' . $row['uid'] . '"'
                            . ' had explicit DENY set for the table/field/value combination "' . $newTupleString . '".'
                            . ' This is not allowed anymore. This be_groups row needs a manual update to fix access rights.</error>'
                        );
                        continue;
                    }
                    $newTuples[] = $newTupleString;
                } else {
                    $newTuples[] = $tuple;
                }
            }
            if ($updateNeeded) {
                $connection->update(
                    self::TABLE_NAME,
                    ['explicit_allowdeny' => implode(',', $newTuples)],
                    ['uid' => (int)$row['uid']],
                    ['explicit_allowdeny' => Connection::PARAM_STR]
                );
            }
        }
        return true;
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    private function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder->from(self::TABLE_NAME);
    }

    private function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
