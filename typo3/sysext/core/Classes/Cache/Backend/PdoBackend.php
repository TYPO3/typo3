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

namespace TYPO3\CMS\Core\Cache\Backend;

use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A PDO database cache backend
 *
 * @deprecated since v11, will be removed in v12. Use Typo3DatabaseBackend instead.
 *             Drop Resources/Private/Sql/Cache/Backend/PdoBackendCacheAndTags.sql when class is dropped.
 */
class PdoBackend extends AbstractBackend implements TaggableBackendInterface
{
    /**
     * @var string
     */
    protected $dataSourceName;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var \PDO
     */
    protected $databaseHandle;

    /**
     * @var string
     */
    protected $pdoDriver;

    public function __construct($context, array $options = [])
    {
        trigger_error(__CLASS__ . ' will be removed in TYPO3 v12, use Typo3DatabaseBackend instead.', E_USER_DEPRECATED);
        parent::__construct($context, $options);
    }

    /**
     * Sets the DSN to use
     *
     * @param string $DSN The DSN to use for connecting to the DB
     */
    public function setDataSourceName($DSN)
    {
        $this->dataSourceName = $DSN;
    }

    /**
     * Sets the username to use
     *
     * @param string $username The username to use for connecting to the DB
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Sets the password to use
     *
     * @param string $password The password to use for connecting to the DB
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Initialize the cache backend.
     */
    public function initializeObject()
    {
        $this->connect();
    }

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws Exception if no cache frontend has been set.
     * @throws \InvalidArgumentException if the identifier is not valid
     * @throws InvalidDataException if $data is not a string
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('No cache frontend has been set yet via setCache().', 1259515600);
        }
        if (!is_string($data)) {
            throw new InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1259515601);
        }
        $this->remove($entryIdentifier);
        $lifetime = $lifetime ?? $this->defaultLifetime;
        $statementHandle = $this->databaseHandle->prepare('INSERT INTO "cache" ("identifier", "context", "cache", "created", "lifetime", "content") VALUES (?, ?, ?, ?, ?, ?)');
        $result = $statementHandle->execute([$entryIdentifier, $this->context, $this->cacheIdentifier, $GLOBALS['EXEC_TIME'], $lifetime, $data]);
        if ($result === false) {
            throw new Exception('The cache entry "' . $entryIdentifier . '" could not be written.', 1259530791);
        }
        $statementHandle = $this->databaseHandle->prepare('INSERT INTO "tags" ("identifier", "context", "cache", "tag") VALUES (?, ?, ?, ?)');
        foreach ($tags as $tag) {
            $result = $statementHandle->execute([$entryIdentifier, $this->context, $this->cacheIdentifier, $tag]);
            if ($result === false) {
                throw new Exception('The tag "' . $tag . ' for cache entry "' . $entryIdentifier . '" could not be written.', 1259530751);
            }
        }
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     */
    public function get($entryIdentifier)
    {
        $statementHandle = $this->databaseHandle->prepare('SELECT "content" FROM "cache" WHERE "identifier"=? AND "context"=? AND "cache"=?' . $this->getNotExpiredStatement());
        $statementHandle->execute([$entryIdentifier, $this->context, $this->cacheIdentifier]);
        return $statementHandle->fetchColumn();
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     */
    public function has($entryIdentifier)
    {
        $statementHandle = $this->databaseHandle->prepare('SELECT COUNT("identifier") FROM "cache" WHERE "identifier"=? AND "context"=? AND "cache"=?' . $this->getNotExpiredStatement());
        $statementHandle->execute([$entryIdentifier, $this->context, $this->cacheIdentifier]);
        return $statementHandle->fetchColumn() > 0;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove($entryIdentifier)
    {
        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "tags" WHERE "identifier"=? AND "context"=? AND "cache"=?');
        $statementHandle->execute([$entryIdentifier, $this->context, $this->cacheIdentifier]);
        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "cache" WHERE "identifier"=? AND "context"=? AND "cache"=?');
        $statementHandle->execute([$entryIdentifier, $this->context, $this->cacheIdentifier]);
        return $statementHandle->rowCount() > 0;
    }

    /**
     * Removes all cache entries of this cache.
     */
    public function flush()
    {
        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "tags" WHERE "context"=? AND "cache"=?');
        $statementHandle->execute([$this->context, $this->cacheIdentifier]);
        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "cache" WHERE "context"=? AND "cache"=?');
        $statementHandle->execute([$this->context, $this->cacheIdentifier]);
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag)
    {
        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "cache" WHERE "context"=? AND "cache"=? AND "identifier" IN (SELECT "identifier" FROM "tags" WHERE "context"=? AND "cache"=? AND "tag"=?)');
        $statementHandle->execute([$this->context, $this->cacheIdentifier, $this->context, $this->cacheIdentifier, $tag]);
        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "tags" WHERE "context"=? AND "cache"=? AND "tag"=?');
        $statementHandle->execute([$this->context, $this->cacheIdentifier, $tag]);
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     */
    public function findIdentifiersByTag($tag)
    {
        $statementHandle = $this->databaseHandle->prepare('SELECT "identifier" FROM "tags" WHERE "context"=?  AND "cache"=? AND "tag"=?');
        $statementHandle->execute([$this->context, $this->cacheIdentifier, $tag]);
        return $statementHandle->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Does garbage collection
     */
    public function collectGarbage()
    {
        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "tags" WHERE "context"=? AND "cache"=? AND "identifier" IN (SELECT "identifier" FROM "cache" WHERE "context"=? AND "cache"=? AND "lifetime" > 0 AND "created" + "lifetime" < ' . $GLOBALS['EXEC_TIME'] . ')');
        $statementHandle->execute([$this->context, $this->cacheIdentifier, $this->context, $this->cacheIdentifier]);
        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "cache" WHERE "context"=? AND "cache"=? AND "lifetime" > 0 AND "created" + "lifetime" < ' . $GLOBALS['EXEC_TIME']);
        $statementHandle->execute([$this->context, $this->cacheIdentifier]);
    }

    /**
     * Returns an SQL statement that evaluates to TRUE if the entry is not expired.
     *
     * @return string
     */
    protected function getNotExpiredStatement()
    {
        return ' AND ("lifetime" = 0 OR "created" + "lifetime" >= ' . $GLOBALS['EXEC_TIME'] . ')';
    }

    /**
     * Connect to the database
     *
     * @throws \RuntimeException if something goes wrong
     */
    protected function connect()
    {
        try {
            $splitdsn = explode(':', $this->dataSourceName, 2);
            $this->pdoDriver = $splitdsn[0];
            if ($this->pdoDriver === 'sqlite' && !file_exists($splitdsn[1])) {
                $this->databaseHandle = GeneralUtility::makeInstance(\PDO::class, $this->dataSourceName, $this->username, $this->password);
                $this->createCacheTables();
            } else {
                $this->databaseHandle = GeneralUtility::makeInstance(\PDO::class, $this->dataSourceName, $this->username, $this->password);
            }
            $this->databaseHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            if (strpos($this->pdoDriver, 'mysql') === 0) {
                $this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI\';');
            }
        } catch (\PDOException $e) {
            throw new \RuntimeException('Could not connect to cache table with DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage(), 1334736164);
        }
    }

    /**
     * Creates the tables needed for the cache backend.
     *
     * @throws \RuntimeException if something goes wrong
     */
    protected function createCacheTables()
    {
        try {
            $this->importSql(
                $this->databaseHandle,
                $this->pdoDriver,
                ExtensionManagementUtility::extPath('core') .
                'Resources/Private/Sql/Cache/Backend/PdoBackendCacheAndTags.sql'
            );
        } catch (\PDOException $e) {
            throw new \RuntimeException('Could not create cache tables with DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage(), 1259576985);
        }
    }

    /**
     * Pumps the SQL into the database. Use for DDL only.
     *
     * Important: key definitions with length specifiers (needed for MySQL) must
     * be given as "field"(xyz) - no space between double quote and parenthesis -
     * so they can be removed automatically.
     *
     * @param \PDO $databaseHandle
     * @param string $pdoDriver
     * @param string $pathAndFilename
     */
    protected function importSql(\PDO $databaseHandle, string $pdoDriver, string $pathAndFilename): void
    {
        $sql = file($pathAndFilename, FILE_IGNORE_NEW_LINES & FILE_SKIP_EMPTY_LINES);
        if ($sql === false) {
            throw new \RuntimeException('Error while reading file "' . $pathAndFilename . '".', 1601021306);
        }
        // Remove MySQL style key length delimiters (yuck!) if we are not setting up a MySQL db
        if (strpos($pdoDriver, 'mysql') !== 0) {
            $sql = preg_replace('/"\\([0-9]+\\)/', '"', $sql);
        }
        $statement = '';
        foreach ($sql as $line) {
            $statement .= ' ' . trim($line);
            if (substr($statement, -1) === ';') {
                $databaseHandle->exec($statement);
                $statement = '';
            }
        }
    }
}
