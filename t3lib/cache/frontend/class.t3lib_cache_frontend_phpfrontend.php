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
 * A cache frontend tailored to PHP code.
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @api
 * @scope prototype
 * @version $Id$
 */
class t3lib_cache_frontend_PhpFrontend extends t3lib_cache_frontend_StringFrontend {
	/**
	 * Constructs the cache
	 *
	 * @param string $identifier A identifier which describes this cache
	 * @param t3lib_cache_backend_PhpCapableBackend $backend Backend to be used for this cache
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($identifier, t3lib_cache_backend_PhpCapableBackend $backend) {
		parent::__construct($identifier, $backend);
	}

	/**
	 * Saves the PHP source code in the cache.
	 *
	 * @param string $entryIdentifier An identifier used for this cache entry, for example the class name
	 * @param string $sourceCode PHP source code
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function set($entryIdentifier, $sourceCode, $tags = array(), $lifetime = NULL) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new InvalidArgumentException(
				'"' . $entryIdentifier . '" is not a valid cache entry identifier.',
				1264023823
			);
		}
		if (!is_string($sourceCode)) {
			throw new t3lib_cache_exception_InvalidData(
				'The given source code is not a valid string.',
				1264023824
			);
		}
		foreach ($tags as $tag) {
			if (!$this->isValidTag($tag)) {
				throw new InvalidArgumentException(
					'"' . $tag . '" is not a valid tag for a cache entry.',
					1264023825
				);
			}
		}
		$sourceCode = '<?php' . chr(10) . $sourceCode . chr(10) . '__halt_compiler();';
		$this->backend->set($entryIdentifier, $sourceCode, $tags, $lifetime);
	}

	/**
	 * Loads PHP code from the cache and require_onces it right away.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed Potential return value from the include operation
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function requireOnce($entryIdentifier) {
		return $this->backend->requireOnce($entryIdentifier);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_frontend_phpfrontend.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_frontend_phpfrontend.php']);
}

?>
