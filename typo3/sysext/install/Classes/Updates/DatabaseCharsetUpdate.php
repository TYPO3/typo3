<?php
namespace TYPO3\CMS\Install\Updates;

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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Move "wizard done" flags to system registry
 */
class DatabaseCharsetUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Set default database charset to utf-8';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone() || !$this->isDefaultConnectionMySQL()) {
            return false;
        }

        $result = false;
        $description = 'Sets the default database charset to utf-8 to'
            . ' ensure new tables are created with correct charset.
        WARNING: This will NOT convert any existing data.';

        // check if database charset is utf-8, also allows utf8mb4
        if (strpos($this->getDefaultDatabaseCharset(), 'utf8') !== 0) {
            $result = true;
        } else {
            $this->markWizardAsDone();
        }

        return $result;
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool Whether everything went smoothly or not
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function performUpdate(array &$dbQueries, &$customMessage)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $sql = 'ALTER DATABASE ' . $connection->quoteIdentifier($connection->getDatabase()) . ' CHARACTER SET utf8';

        try {
            $connection->exec($sql);
        } catch (DBALException $e) {
            $customMessage = 'SQL-ERROR: ' . htmlspecialchars($e->getPrevious()->getMessage());
            return false;
        }
        $dbQueries[] = $sql;
        $this->markWizardAsDone();

        return true;
    }

    /**
     * Return TRUE if this TYPO3 instance runs on a MySQL compatible databasa instance
     *
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function isDefaultConnectionMySQL(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

        return strpos($connection->getServerVersion(), 'MySQL') === 0;
    }

    /**
     * Retrieves the default character set of the database.
     *
     * @return string
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getDefaultDatabaseCharset(): string
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $queryBuilder = $connection->createQueryBuilder();
        return (string)$queryBuilder->select('DEFAULT_CHARACTER_SET_NAME')
            ->from('information_schema.SCHEMATA')
            ->where(
                $queryBuilder->expr()->eq(
                    'SCHEMA_NAME',
                    $queryBuilder->createNamedParameter($connection->getDatabase(), \PDO::PARAM_STR)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();
    }
}
