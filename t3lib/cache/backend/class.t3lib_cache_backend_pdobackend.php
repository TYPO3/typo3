<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Christian Kuhn <lolli@schwarzbu.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * A PDO database cache backend
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @api
 * @scope prototype
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @version $Id$
 */
class t3lib_cache_backend_PdoBackend extends t3lib_cache_backend_AbstractBackend {
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
	 * Used to seperate stored data by user, SAPI, context, ...
	 * @var string
	 */
	protected $scope;

	/**
	 * @var PDO
	 */
	protected $databaseHandle;

	/**
	 * @var string
	 */
	protected $pdoDriver;

	/**
	 * Constructs this backend
	 *
	 * @param mixed $options Configuration options - depends on the actual backend
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function __construct(array $options = array()) {
		parent::__construct($options);

		$this->connect();
	}

	/**
	 * Sets the DSN to use
	 *
	 * @param string $DSN The DSN to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setDataSourceName($DSN) {
		$this->dataSourceName = $DSN;
	}

	/**
	 * Sets the username to use
	 *
	 * @param string $username The username to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * Sets the password to use
	 *
	 * @param string $password The password to use for connecting to the DB
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * Initializes the identifier prefix when setting the cache.
	 *
	 * @param t3lib_cache_frontend_Frontend $cache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setCache(t3lib_cache_frontend_Frontend $cache) {
		parent::setCache($cache);
		$processUser = extension_loaded('posix') ? posix_getpwuid(posix_geteuid()) : array('name' => 'default');
		$this->scope = t3lib_div::shortMD5(PATH_site . $processUser['name'], 12);
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws t3lib_cache_Exception if no cache frontend has been set.
	 * @throws t3lib_cache_exception_InvalidData if $data is not a string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'No cache frontend has been set yet via setCache().',
				1259515600
			);
		}

		if (!is_string($data)) {
			throw new t3lib_cache_exception_InvalidData(
				'The specified data is of type "' . gettype($data) . '" but a string is expected.',
				1259515601
			);
		}

		if ($this->has($entryIdentifier)) {
			$this->remove($entryIdentifier);
		}

		$lifetime = ($lifetime === NULL) ? $this->defaultLifetime : $lifetime;

		$statementHandle = $this->databaseHandle->prepare(
			'INSERT INTO "cache" ("identifier", "scope", "cache", "created", "lifetime", "content") VALUES (?, ?, ?, ?, ?, ?)'
		);
		$result = $statementHandle->execute(
			array($entryIdentifier, $this->scope, $this->cacheIdentifier, $GLOBALS['EXEC_TIME'], $lifetime, $data)
		);

		if ($result === FALSE) {
			throw new t3lib_cache_Exception(
				'The cache entry "' . $entryIdentifier . '" could not be written.',
				1259530791
			);
		}

		$statementHandle = $this->databaseHandle->prepare(
			'INSERT INTO "tags" ("identifier", "scope", "cache", "tag") VALUES (?, ?, ?, ?)'
		);

		foreach ($tags as $tag) {
			$result = $statementHandle->execute(
				array($entryIdentifier, $this->scope, $this->cacheIdentifier, $tag)
			);
			if ($result === FALSE) {
				throw new t3lib_cache_Exception(
					'The tag "' . $tag . ' for cache entry "' . $entryIdentifier . '" could not be written.',
					1259530751
				);
			}
		}
	}

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function get($entryIdentifier) {
		$statementHandle = $this->databaseHandle->prepare(
			'SELECT "content" FROM "cache" WHERE "identifier"=? AND "scope"=? AND "cache"=?' . $this->getNotExpiredStatement()
		);
		$statementHandle->execute(
			array($entryIdentifier, $this->scope, $this->cacheIdentifier)
		);
		return $statementHandle->fetchColumn();
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function has($entryIdentifier) {
		$statementHandle = $this->databaseHandle->prepare(
			'SELECT COUNT("identifier") FROM "cache" WHERE "identifier"=? AND "scope"=? AND "cache"=?' . $this->getNotExpiredStatement()
		);
		$statementHandle->execute(
			array($entryIdentifier, $this->scope, $this->cacheIdentifier)
		);
		return ($statementHandle->fetchColumn() > 0);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function remove($entryIdentifier) {
		$statementHandle = $this->databaseHandle->prepare(
			'DELETE FROM "tags" WHERE "identifier"=? AND "scope"=? AND "cache"=?'
		);
		$statementHandle->execute(
			array($entryIdentifier, $this->scope, $this->cacheIdentifier)
		);

		$statementHandle = $this->databaseHandle->prepare(
			'DELETE FROM "cache" WHERE "identifier"=? AND "scope"=? AND "cache"=?'
		);
		$statementHandle->execute(
			array($entryIdentifier, $this->scope, $this->cacheIdentifier)
		);

		return ($statementHandle->rowCount() > 0);
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function flush() {
		$statementHandle = $this->databaseHandle->prepare(
			'DELETE FROM "tags" WHERE "scope"=? AND "cache"=?'
		);
		$statementHandle->execute(
			array($this->scope, $this->cacheIdentifier)
		);

		$statementHandle = $this->databaseHandle->prepare(
			'DELETE FROM "cache" WHERE "scope"=? AND "cache"=?'
		);
		$statementHandle->execute(
			array($this->scope, $this->cacheIdentifier)
		);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function flushByTag($tag) {
		$statementHandle = $this->databaseHandle->prepare(
			'DELETE FROM "cache" WHERE "scope"=? AND "cache"=? AND "identifier" IN (SELECT "identifier" FROM "tags" WHERE "scope"=? AND "cache"=? AND "tag"=?)'
		);
		$statementHandle->execute(
			array($this->scope, $this->cacheIdentifier,$this->scope, $this->cacheIdentifier, $tag)
		);

		$statementHandle = $this->databaseHandle->prepare(
			'DELETE FROM "tags" WHERE "scope"=? AND "cache"=? AND "tag"=?'
		);
		$statementHandle->execute(
			array($this->scope, $this->cacheIdentifier, $tag)
		);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tags.
	 * This method doesn't exist in FLOW3, but is mandatory for TYPO3v4.
	 *
	 * @TODO: Make smarter
	 * @param array $tags The tags the entries must have
	 * @return void
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function flushBytags(array $tags) {
		foreach($tags as $tag) {
			$this->flushByTag($tag);
		}
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function findIdentifiersByTag($tag) {
		$statementHandle = $this->databaseHandle->prepare(
			'SELECT "identifier" FROM "tags" WHERE "scope"=?  AND "cache"=? AND "tag"=?'
		);
		$statementHandle->execute(
			array($this->scope, $this->cacheIdentifier, $tag)
		);
		return $statementHandle->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged with
	 * all of the specified tags.
	 * This method doesn't exist in FLOW3, but is mandatory for TYPO3v4.
	 *
	 * @TODO: Make smarter
	 * @param array $tags Tags to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function findIdentifiersByTags(array $tags) {
		$taggedEntries = array();
		$foundEntries  = array();

		foreach ($tags as $tag) {
			$taggedEntries[$tag] = $this->findIdentifiersByTag($tag);
		}

		$intersectedTaggedEntries = call_user_func_array('array_intersect', $taggedEntries);

		foreach ($intersectedTaggedEntries as $entryIdentifier) {
			if ($this->has($entryIdentifier)) {
				$foundEntries[$entryIdentifier] = $entryIdentifier;
			}
		}

		return $foundEntries;
	}

	/**
	 * Does garbage collection
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function collectGarbage() {
		$statementHandle = $this->databaseHandle->prepare(
			'DELETE FROM "tags" WHERE "scope"=? AND "cache"=? AND "identifier" IN ' .
				'(SELECT "identifier" FROM "cache" WHERE "scope"=? AND "cache"=? AND "lifetime" > 0 AND "created" + "lifetime" < ' . $GLOBALS['EXEC_TIME'] . ')'
		);
		$statementHandle->execute(
			array($this->scope, $this->cacheIdentifier, $this->scope, $this->cacheIdentifier)
		);

		$statementHandle = $this->databaseHandle->prepare(
			'DELETE FROM "cache" WHERE "scope"=? AND "cache"=? AND "lifetime" > 0 AND "created" + "lifetime" < ' . $GLOBALS['EXEC_TIME']
		);
		$statementHandle->execute(
			array($this->scope, $this->cacheIdentifier)
		);
	}

	/**
	 * Returns an SQL statement that evaluates to true if the entry is not expired.
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getNotExpiredStatement() {
		return ' AND ("lifetime" = 0 OR "created" + "lifetime" >= ' . $GLOBALS['EXEC_TIME'] . ')';
	}

	/**
	 * Connect to the database
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function connect() {
		try {
			$splitdsn = explode(':', $this->dataSourceName, 2);
			$this->pdoDriver = $splitdsn[0];

			if ($this->pdoDriver === 'sqlite' && !file_exists($splitdsn[1])) {
				$this->createCacheTables();
			}

			$this->databaseHandle = t3lib_div::makeInstance('PDO', $this->dataSourceName, $this->username, $this->password);
			$this->databaseHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			if ($this->pdoDriver === 'mysql') {
				$this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI\';');
			}
		} catch (PDOException $e) {
#			$this->createCacheTables();
		}
	}

	/**
	 * Creates the tables needed for the cache backend.
	 *
	 * @return void
	 * @throws RuntimeException if something goes wrong
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createCacheTables() {
		try {
			$pdoHelper = t3lib_div::makeInstance('t3lib_PdoHelper', $this->dataSourceName, $this->username, $this->password);
			$pdoHelper->importSql(PATH_t3lib . 'cache/backend/resources/ddl.sql');
		} catch (PDOException $e) {
			throw new RuntimeException(
				'Could not create cache tables with DSN "' . $this->dataSourceName . '". PDO error: ' . $e->getMessage(),
				1259576985
			);
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_pdobackend.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_pdobackend.php']);
}
?>
