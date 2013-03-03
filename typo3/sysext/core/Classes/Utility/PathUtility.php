<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
 * A copy is found in the textfile GPL.txt and important notices to the license
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
 * Class with helper functions for file paths.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class PathUtility {

	/**
	 * Gets the relative path from the current used script to a given directory.
	 * The allowed TYPO3 path is checked as well, thus it's not possible to go to upper levels.
	 *
	 * @param string $targetPath Absolute target path
	 * @return NULL|string
	 */
	static public function getRelativePathTo($targetPath) {
		return self::getRelativePath(dirname(PATH_thisScript), $targetPath);
	}

	/**
	 * Gets the relative path from a source directory to a target directory.
	 * The allowed TYPO3 path is checked as well, thus it's not possible to go to upper levels.
	 *
	 * @param string $sourcePath Absolute source path
	 * @param string $targetPath Absolute target path
	 * @return NULL|string
	 */
	static public function getRelativePath($sourcePath, $targetPath) {
		$relativePath = NULL;
		$sourcePath = rtrim(GeneralUtility::fixWindowsFilePath($sourcePath), '/');
		$targetPath = rtrim(GeneralUtility::fixWindowsFilePath($targetPath), '/');
		if ($sourcePath !== $targetPath) {
			$commonPrefix = self::getCommonPrefix(array($sourcePath, $targetPath));
			if ($commonPrefix !== NULL && \TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($commonPrefix)) {
				$commonPrefixLength = strlen($commonPrefix);
				$resolvedSourcePath = '';
				$resolvedTargetPath = '';
				$sourcePathSteps = 0;
				if (strlen($sourcePath) > $commonPrefixLength) {
					$resolvedSourcePath = (string) substr($sourcePath, $commonPrefixLength);
				}
				if (strlen($targetPath) > $commonPrefixLength) {
					$resolvedTargetPath = (string) substr($targetPath, $commonPrefixLength);
				}
				if ($resolvedSourcePath !== '') {
					$sourcePathSteps = count(explode('/', $resolvedSourcePath));
				}
				$relativePath = self::sanitizeTrailingSeparator(str_repeat('../', $sourcePathSteps) . $resolvedTargetPath);
			}
		}
		return $relativePath;
	}

	/**
	 * Gets the common path prefix out of many paths.
	 * + /var/www/domain.com/typo3/sysext/cms/
	 * + /var/www/domain.com/typo3/sysext/em/
	 * + /var/www/domain.com/typo3/sysext/file/
	 * = /var/www/domain.com/typo3/sysext/
	 *
	 * @param array $paths Paths to be processed
	 * @return NULL|string
	 */
	static public function getCommonPrefix(array $paths) {
		$paths = array_map(array('TYPO3\\CMS\\Core\\Utility\\GeneralUtility', 'fixWindowsFilePath'), $paths);
		$commonPath = NULL;
		if (count($paths) === 1) {
			$commonPath = array_shift($paths);
		} elseif (count($paths) > 1) {
			$parts = explode('/', array_shift($paths));
			$comparePath = '';
			$break = FALSE;
			foreach ($parts as $part) {
				$comparePath .= $part . '/';
				foreach ($paths as $path) {
					if (strpos($path . '/', $comparePath) !== 0) {
						$break = TRUE;
						break;
					}
				}
				if ($break) {
					break;
				}
				$commonPath = $comparePath;
			}
		}
		if ($commonPath !== NULL) {
			$commonPath = self::sanitizeTrailingSeparator($commonPath, '/');
		}
		return $commonPath;
	}

	/**
	 * Sanitizes a trailing separator.
	 * (e.g. 'some/path' -> 'some/path/')
	 *
	 * @param string $path The path to be sanitized
	 * @param string $separator The separator to be used
	 * @return string
	 */
	static public function sanitizeTrailingSeparator($path, $separator = '/') {
		return rtrim($path, $separator) . $separator;
	}


	/**
	 * Returns trailing name component of path
	 * Since basename() is locale dependent we need to access
	 * the filesystem with the same locale of the system, not
	 * the rendering context.
	 * @see http://www.php.net/manual/en/function.basename.php
	 *
	 *
	 * @param string $path
	 *
	 * @return string
	 *
	 */
	static public function basename($path) {
		$currentLocale = setlocale(LC_CTYPE, 0);
		setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
		$basename = basename($path);
		setlocale(LC_CTYPE, $currentLocale);
		return $basename;
	}

	/**
	 * Returns parent directory's path
	 * Since dirname() is locale dependent we need to access
	 * the filesystem with the same locale of the system, not
	 * the rendering context.
	 * @see http://www.php.net/manual/en/function.dirname.php
	 *
	 *
	 * @param string $path
	 *
	 * @return string
	 *
	 */
	static public function dirname($path) {
		$currentLocale = setlocale(LC_CTYPE, 0);
		setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
		$dirname = dirname($path);
		setlocale(LC_CTYPE, $currentLocale);
		return $dirname;
	}

	/**
	 * Returns parent directory's path
	 * Since dirname() is locale dependent we need to access
	 * the filesystem with the same locale of the system, not
	 * the rendering context.
	 * @see http://www.php.net/manual/en/function.dirname.php
	 *
	 *
	 * @param string $path
	 * @param integer $options
	 *
	 * @return string|array
	 *
	 */
	static public function pathinfo($path, $options = NULL) {
		$currentLocale = setlocale(LC_CTYPE, 0);
		setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
		$pathinfo = $options == NULL ? pathinfo($path) : pathinfo($path, $options);
		setlocale(LC_CTYPE, $currentLocale);
		return $pathinfo;
	}
}


?>