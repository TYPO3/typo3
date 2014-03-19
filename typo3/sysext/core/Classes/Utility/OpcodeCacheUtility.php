<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Alexander Opitz <opitz@pluspol-interactive.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class with helper functions for clearing the PHP opcache.
 * It auto detects the opcache system and invalidates/resets it.
 * http://forge.typo3.org/issues/55252
 * Supported opcaches are: OPcache (PHP 5.5), APC, WinCache, XCache, eAccelerator, ZendOptimizerPlus
 *
 * @author Alexander Opitz <opitz@pluspol-interactive.de>
 */
class OpcodeCacheUtility {

	/**
	 * All supported cache types
	 * @var array|null
	 */
	static protected $supportedCaches = NULL;

	/**
	 * Holds all currently active caches
	 * @var array|null
	 */
	static protected $activeCaches = NULL;

	/**
	 * Initialize the cache properties
	 */
	static protected function initialize() {
		$apcVersion = phpversion('apc');

		static::$supportedCaches = array(
			// The ZendOpcache aka OPcache since PHP 5.5
			// http://php.net/manual/de/book.opcache.php
			'OPcache' => array(
				'active' => extension_loaded('Zend OPcache') && ini_get('opcache.enable') === '1',
				'version' => phpversion('Zend OPcache'),
				'canReset' => TRUE, // opcache_reset() ... it seems that it doesn't reset for current run.
				// From documentation this function exists since first version (7.0.0) but from Changelog
				// this function exists since 7.0.2
				// http://pecl.php.net/package-changelog.php?package=ZendOpcache&release=7.0.2
				'canInvalidate' => function_exists('opcache_invalidate'),
				'error' => FALSE,
				'clearCallback' => function ($fileAbsPath) {
					if ($fileAbsPath !== NULL && function_exists('opcache_invalidate')) {
						opcache_invalidate($fileAbsPath);
					} else {
						opcache_reset();
					}
				}
			),

			// The Alternative PHP Cache aka APC
			// http://www.php.net/manual/de/book.apc.php
			'APC' => array(
				// Currently APCu identifies itself both as "apcu" and "apc" (for compatibility) although it doesn't
				// provide the APC-opcache functionality
				'active' => extension_loaded('apc') && !extension_loaded('apcu') && ini_get('apc.enabled') === '1',
				'version' => $apcVersion,
				// apc_clear_cache() since APC 2.0.0 so default yes. In cli it do not clear the http cache.
				'canReset' => TRUE,
				'canInvalidate' => self::canApcInvalidate(),
				// Versions lower then 3.1.7 are known as malfunction
				'error' => $apcVersion && VersionNumberUtility::convertVersionNumberToInteger($apcVersion) < 3001007,
				'clearCallback' => function ($fileAbsPath) {
					if ($fileAbsPath !== NULL && OpcodeCacheUtility::getCanInvalidate('APC')) {
						// This may output a warning like: PHP Warning: apc_delete_file(): Could not stat file
						// This warning isn't true, this means that apc was unable to generate the cache key
						// which depends on the configuration of APC.
						apc_delete_file($fileAbsPath);
					} else {
						apc_clear_cache('opcode');
					}
				}
			),

			// http://www.php.net/manual/de/book.wincache.php
			'WinCache' => array(
				'active' => extension_loaded('wincache') && ini_get('wincache.ocenabled') === '1',
				'version' => phpversion('wincache'),
				'canReset' => FALSE,
				'canInvalidate' => TRUE, // wincache_refresh_if_changed()
				'error' => FALSE,
				'clearCallback' => function ($fileAbsPath) {
					if ($fileAbsPath !== NULL) {
						wincache_refresh_if_changed(array($fileAbsPath));
					} else {
						// No argument means refreshing all.
						wincache_refresh_if_changed();
					}
				}
			),

			// http://xcache.lighttpd.net/
			'XCache' => array(
				'active' => extension_loaded('xcache'),
				'version' => phpversion('xcache'),
				'canReset' => TRUE, // xcache_clear_cache()
				'canInvalidate' => FALSE,
				'error' => FALSE,
				'clearCallback' => function ($fileAbsPath) {
					if (!ini_get('xcache.admin.enable_auth')) {
						xcache_clear_cache(XC_TYPE_PHP);
					}
				}
			),

			// https://github.com/eaccelerator/eaccelerator
			//
			// @see https://github.com/eaccelerator/eaccelerator/blob/master/doc/php/info.php
	        // Only possible if we are in eaccelerator.admin_allowed_path and we can only remove data
			// "that isn't used in the current requests"
			'eAccelerator' => array(
				'active' => extension_loaded('eAccelerator'),
				'version' => phpversion('eaccelerator'),
				'canReset' => FALSE,
				'canInvalidate' => FALSE,
				'error' => TRUE, // eAccelerator is more or less out of date and not functional for what we need.
				'clearCallback' => function ($fileAbsPath) {
					eaccelerator_clear();
				}
			),

			// https://github.com/zendtech/ZendOptimizerPlus
			// http://files.zend.com/help/Zend-Server/zend-server.htm#zendoptimizerplus.html
			'ZendOptimizerPlus' => array(
				'active' => extension_loaded('Zend Optimizer+') && ini_get('zend_optimizerplus.enable') === '1',
				'version' => phpversion('Zend Optimizer+'),
				'canReset' => TRUE, // accelerator_reset()
				'canInvalidate' => FALSE,
				'error' => FALSE,
				'clearCallback' => function ($fileAbsPath) {
					accelerator_reset();
				}
			),
		);

		static::$activeCaches = array();
		// Cache the active ones
		foreach (static::$supportedCaches as $opcodeCache => $properties) {
			if ($properties['active']) {
				static::$activeCaches[$opcodeCache] = $properties;
			}
		}
	}

	/**
	 * Gets the state of canInvalidate for given cache system.
	 *
	 * @param string $system The cache system to test (APC, ...)
	 *
	 * @return boolean The calculated value from array or FALSE if cache system not exists.
	 * @internal Do not rely on this function. Will be removed if PHP5.4 is minimum requirement.
	 */
	static public function getCanInvalidate($system) {
		return isset(static::$supportedCaches[$system])
			? static::$supportedCaches[$system]['canInvalidate']
			: FALSE;
	}

	/**
	 * Clears a file from an opcache, if one exists.
	 *
	 * @param string|NULL $fileAbsPath The file as absolute path to be cleared or NULL to clear completely.
	 *
	 * @return void
	 */
	static public function clearAllActive($fileAbsPath = NULL) {
		foreach (static::getAllActive() as $properties) {
			$callback = $properties['clearCallback'];
			$callback($fileAbsPath);
		}
	}

	/**
	 * Returns all supported and active opcaches
	 *
	 * @return array Array filled with supported and active opcaches
	 */
	static public function getAllActive() {
		if (static::$activeCaches === NULL) {
			static::initialize();
		}
		return static::$activeCaches;
	}

	/**
	 * Checks if the APC configuration is useable to clear cache of one file.
	 * https://bugs.php.net/bug.php?id=66819
	 *
	 * @return bool Returns TRUE if file can be invalidated and FALSE if complete cache needs to be removed
	 */
	static public function canApcInvalidate() {
		// apc_delete_file() should exists since APC 3.1.1 but you never know so default is no
		$canInvalidate = FALSE;

		if (function_exists('apc_delete_file')) {
			// Deleting files from cache depends on generating the cache key.
			// This cache key generation depends on unnecessary configuration options
			// http://git.php.net/?p=pecl/caching/apc.git;a=blob;f=apc_cache.c;h=d15cf8c1b4b9d09b9bac75b16c062c8b40458dda;hb=HEAD#l931

			// If stat=0 then canonicalized path may be used
			$stat = (int)ini_get('apc.stat');
			// If canonicalize (default = 1) then file_update_protection isn't checked
			$canonicalize = (int)ini_get('apc.canonicalize');
			// If file file_update_protection is checked, then we will fail, 'cause we generated the file and then try to
			// remove it. But the file is not older than file_update_protection and therefore hash generation will stop with error.
			$protection = (int)ini_get('apc.file_update_protection');

			if ($protection === 0 || ($stat === 0 && $canonicalize === 1)) {
				$canInvalidate = TRUE;
			}
		}

		return $canInvalidate;
	}
}
