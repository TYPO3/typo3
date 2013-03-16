<?php
namespace TYPO3\CMS\Core\Compatibility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Thomas Maroschik <tmaroschik@dfau.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This is a compatibility layer for systems running PHP < 5.3.7
 * It rewrites the type hints in method definitions so that they are identical to the
 * core interface definition
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class CompatbilityClassLoaderPhpBelow50307 extends \TYPO3\CMS\Core\Core\ClassLoader {

	/**
	 * Contains the class loaders class name
	 *
	 * @var string
	 */
	static protected $className = __CLASS__;

	/**
	 * Installs TYPO3 autoloader, and loads the autoload registry for the core.
	 *
	 * @return boolean TRUE in case of success
	 */
	static public function registerAutoloader() {
		return parent::registerAutoloader();
	}

	/**
	 * Unload TYPO3 autoloader and write any additional classes
	 * found during the script run to the cache file.
	 *
	 * This method is called during shutdown of the framework.
	 *
	 * @return boolean TRUE in case of success
	 */
	static public function unregisterAutoloader() {
		return parent::unregisterAutoloader();
	}

	/**
	 * Require the class file and rewrite non sysext files transparently
	 *
	 * @static
	 * @param string $classPath
	 * @param string $className
	 * @return void
	 */
	static public function requireClassFileOnce($classPath, $className) {
		if (
			GeneralUtility::isFirstPartOfStr($className, 'tx_')
			|| GeneralUtility::isFirstPartOfStr($className, 'Tx_')
			|| GeneralUtility::isFirstPartOfStr($className, 'ux_')
			|| GeneralUtility::isFirstPartOfStr($className, 'user_')
			|| GeneralUtility::isFirstPartOfStr($className, 'User_')
		) {
			// If class in question starts with one of the allowed old prefixes
			static::checkClassCacheEntryAndRequire($classPath);
		} else {
			// Do nothing for system extensions or external libraries.
			// They are already using the proper type hints or do not use them at all.
			static::requireClassFile($classPath);
		}
	}

	/**
	 * Require class file from cache and create if it doesn't exist yet
	 *
	 * @param $classPath
	 * @return void
	 */
	static protected function checkClassCacheEntryAndRequire($classPath) {
		$cacheIdentifier = static::getClassPathCacheIdentifier($classPath);
		/** @var $phpCodeCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
		$phpCodeCache = $GLOBALS['typo3CacheManager']->getCache('cache_core');
		if (!$phpCodeCache->has($cacheIdentifier)) {
			$classCode = static::rewriteMethodTypeHintsFromClassPath($classPath);
			$phpCodeCache->set($cacheIdentifier, $classCode, array(), 0);
		}
		$phpCodeCache->requireOnce($cacheIdentifier);
	}

	/**
	 * Generates the cache identifier from the relative class path and the files sha1 hash
	 *
	 * @static
	 * @param string $classPath
	 * @return string
	 */
	static protected function getClassPathCacheIdentifier($classPath) {
		// The relative class path is part of the cache identifier
		$relativeClassPath = (GeneralUtility::isFirstPartOfStr($classPath, PATH_site)) ? substr($classPath, strlen(PATH_site)) : $classPath;
		$fileExtension = strrchr($classPath, '.');
		$fileNameWithoutExtension = substr(basename($classPath), 0, strlen($fileExtension) * -1);
		// The class content has to be part of the identifier too
		// otherwise the old class files get loaded from cache
		$fileSha1 = sha1_file($classPath);
		$cacheIdentifier = 'ClassLoader_' . $fileNameWithoutExtension . '_' . substr(sha1($fileSha1 . '|' . $relativeClassPath), 0, 20);
		// Clean up identifier to be a valid cache entry identifier
		$cacheIdentifier = preg_replace('/[^a-zA-Z0-9_%\-&]/i', '_', $cacheIdentifier);
		return $cacheIdentifier;
	}

	/**
	 * Loads the class path and rewrites the type hints
	 *
	 * @static
	 * @param string $classPath
	 * @return string rewritten php code
	 */
	static protected function rewriteMethodTypeHintsFromClassPath($classPath) {
		$pcreBacktrackLimitOriginal = ini_get('pcre.backtrack_limit');
		$classAliasMap = static::$aliasToClassNameMapping;
		$fileContent = static::getClassFileContent($classPath);
		$fileLength = strlen($fileContent);
		$hasReplacements = FALSE;
		// when the class file is bigger than the original pcre backtrace limit increase the limit
		if ($pcreBacktrackLimitOriginal < $fileLength) {
			ini_set('pcre.backtrack_limit', $fileLength);
		}
		$fileContent = preg_replace_callback(
			'/function[ \t]+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\((.*?\$.*?)\)(\s*[{;])/ims',
			function($matches) use($classAliasMap, &$hasReplacements) {
			if (isset($matches[1]) && isset($matches[2])) {
				list($functionName, $argumentList) = array_slice($matches, 1, 2);
				$arguments = explode(',', $argumentList);
				$arguments = array_map('trim', $arguments);
				$arguments = preg_replace_callback('/([\\a-z0-9_]+\s+)?((\s*[&]*\s*\$[a-z0-9_]+)(\s*=\s*.+)?)/ims', function($argumentMatches) use($classAliasMap, &$hasReplacements)  {
					if (isset($argumentMatches[1]) && isset($argumentMatches[2])) {
						$typeHint = strtolower(ltrim(trim($argumentMatches[1]), '\\'));
						if (isset($classAliasMap[$typeHint])) {
							$hasReplacements = TRUE;
							return '\\' . $classAliasMap[$typeHint] . ' ' . $argumentMatches[2];
						}
					}
					return $argumentMatches[0];
				}, $arguments);
				return 'function ' . $functionName . '(' . implode(', ', $arguments) . ')' . $matches[3];
			}
			return $matches[0];
		}, $fileContent);
		$fileContent = preg_replace(array(
			'/^\s*<\?php/',
			'/\?>\s*$/'
		), '', $fileContent);
		if ($pcreBacktrackLimitOriginal < $fileLength) {
			ini_set('pcre.backtrack_limit', $pcreBacktrackLimitOriginal);
		}

		if (!$hasReplacements) {
			$fileContent = 'require_once \'' . $classPath . '\';';
		}

		return $fileContent;
	}

	/**
	 * Wrapper method to be able to mock in unit tests
	 *
	 * @param string $classPath
	 */
	protected static function requireClassFile($classPath) {
		GeneralUtility::requireOnce($classPath);
	}

	/**
	 * Wrapper method to be able to mock in unit tests
	 *
	 * @param string $classPath
	 * @return string
	 */
	protected static function getClassFileContent($classPath) {
		return file_get_contents($classPath);
	}

}

?>