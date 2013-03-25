<?php
namespace TYPO3\CMS\Core\Compatibility;

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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is a compatibility layer for systems running PHP < 5.3.7
 * It rewrites the type hints in method definitions so that they are identical to the
 * core interface definition
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class CompatibilityClassLoaderPhpBelow50307 extends \TYPO3\CMS\Core\Core\ClassLoader {

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
	 * @param string $classFilePathAndName
	 * @param string $classCacheEntryIdentifier
	 * @return void
	 */
	static public function addClassToCache($classFilePathAndName, $classCacheEntryIdentifier) {
		if (
			GeneralUtility::isFirstPartOfStr($classCacheEntryIdentifier, 'tx_')
			|| GeneralUtility::isFirstPartOfStr($classCacheEntryIdentifier, 'Tx_')
			|| GeneralUtility::isFirstPartOfStr($classCacheEntryIdentifier, 'ux_')
			|| GeneralUtility::isFirstPartOfStr($classCacheEntryIdentifier, 'user_')
			|| GeneralUtility::isFirstPartOfStr($classCacheEntryIdentifier, 'User_')
		) {
			// If class in question starts with one of the allowed old prefixes
			static::rewriteMethodTypeHintsFromClassPathAndAddToClassCache($classFilePathAndName, $classCacheEntryIdentifier);
		} else {
			parent::addClassToCache($classFilePathAndName, $classCacheEntryIdentifier);
		}
	}

	/**
	 * Loads the class path and rewrites the type hints
	 *
	 * @static
	 * @param string $classFilePath
	 * @param string $classCacheEntryIdentifier
	 * @return string rewritten php code
	 */
	static protected function rewriteMethodTypeHintsFromClassPathAndAddToClassCache($classFilePath, $classCacheEntryIdentifier) {
		/** @var $cacheBackend \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend */
		$cacheBackend = static::$classesCache->getBackend();
		$pcreBacktrackLimitOriginal = ini_get('pcre.backtrack_limit');
		$aliasToClassNameMapping = CompatibilityClassAliasMapPhpBelow50307::getAliasesForClassNames();
		$fileContent = static::getClassFileContent($classFilePath);
		$fileLength = strlen($fileContent);
		$hasReplacements = FALSE;

		// when the class file is bigger than the original pcre backtrace limit increase the limit
		if ($pcreBacktrackLimitOriginal < $fileLength) {
			ini_set('pcre.backtrack_limit', $fileLength);
		}
		$fileContent = preg_replace_callback(
			'/function[ \t]+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\((.*?\$.*?)\)(\s*[{;])/ims',
			function($matches) use($aliasToClassNameMapping, &$hasReplacements) {
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
		if ($pcreBacktrackLimitOriginal < $fileLength) {
			ini_set('pcre.backtrack_limit', $pcreBacktrackLimitOriginal);
		}

		if (!$hasReplacements) {
			$cacheBackend->setLinkToPhpFile($classCacheEntryIdentifier, $classFilePath);
			return;
		}

		// Remove the php tags as they get introduced by the PhpCode cache frontend again
		$fileContent = preg_replace(array(
			'/^\s*<\?php/',
			'/\?>\s*$/'
		), '', $fileContent);

		// Wrap the class in a condition that removes itself again if cache entry is invalid
		$classFileSha1 = sha1_file($classFilePath);
		$relativePathToClassFileFromCacheBackend = \TYPO3\CMS\Core\Utility\PathUtility::getRelativePath(
			$cacheBackend->getCacheDirectory(),
			dirname($classFilePath)
		);
		$relativePathToClassFileFromCacheBackend .= basename($classFilePath);

		$ifClause = sprintf('
			$pathToOriginalClassFile = __DIR__ . \'/%s\';
			if (!file_exists($pathToOriginalClassFile) || sha1_file($pathToOriginalClassFile) !== \'%s\') {
				return FALSE;
			} else {
		', $relativePathToClassFileFromCacheBackend, $classFileSha1);

		$ifClosingClause = LF . '}' . LF;

		static::$classesCache->set($classCacheEntryIdentifier, $ifClause . $fileContent . $ifClosingClause);
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