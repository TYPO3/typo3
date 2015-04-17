<?php
namespace TYPO3\CMS\Composer\Installer\Util;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Thomas Maroschik <tmaroschik@dfau.de>
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
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * An additional wrapper around filesystem
 */
class Filesystem extends \Composer\Util\Filesystem {

	/**
	 * @param array $files
	 * @return bool
	 */
	public function allFilesExist(array $files) {
		foreach ($files as $file) {
			if (!file_exists($file)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * @param array $files
	 * @return bool
	 */
	public function someFilesExist(array $files) {
		foreach ($files as $file) {
			if (file_exists($file)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 *
	 */
	public function establishSymlinks(array $links) {
		foreach ($links as $source => $target) {
			$this->symlink($source, $target);
		}
	}

	/**
	 *
	 */
	public function removeSymlinks(array $links) {
		foreach ($links as $target) {
			$this->remove($target);
		}
	}

	/**
	 * @param string $source
	 * @param string $target
	 * @param bool $copyOnFailure
	 */
	public function symlink($source, $target, $copyOnFailure = TRUE) {
		if (!file_exists($source)) {
			throw new \InvalidArgumentException('The symlink source "' . $source . '" is not available.');
		}
		if (file_exists($target)) {
			throw new \InvalidArgumentException('The symlink target "' . $target . '" already exists.');
		}
		$symlinkSuccessfull = @symlink($source, $target);
		if (!$symlinkSuccessfull && !$copyOnFailure) {
			throw new \RuntimeException('Symlinking target "' . $target . '" to source "' . $source . '" failed.');
		} elseif (!$symlinkSuccessfull && $copyOnFailure) {
			try {
				$this->copy($source, $target);
			} catch (\Exception $exception) {
				throw new \RuntimeException('Neiter symlinking nor copying target "' . $target . '" to source "' . $source . '" worked.');
			}
		}
	}

	/**
	 * @param string $source
	 * @param string $target
	 *
	 * @return void
	 */
	public function copy($source, $target) {
		if (!file_exists($source)) {
			throw new \RuntimeException('The source "' . $source . '" does not exist and cannot be copied.');
		}
		if (is_file($source)) {
			$this->ensureDirectoryExists(dirname($target));
			$this->copyFile($source, $target);
			return;
		} elseif (is_dir($source)) {
			$this->copyDirectory($source, $target);
			return;
		}
		throw new \RuntimeException('The source "' . $source . '" is neither a file nor a directory.');
	}

	/**
	 * @param string $source
	 * @param string $target
	 */
	protected function copyFile($source, $target) {
		$copySuccessful = @copy($source, $target);
		if (!$copySuccessful) {
			throw new \RuntimeException('The source "' . $source . '" could not be copied to target "' . $target . '".');
		}
	}

	/**
	 * @param string $source
	 * @param string $target
	 */
	protected function copyDirectory($source, $target) {
		$iterator = new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
		$recursiveIterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
		$this->ensureDirectoryExists($target);

		foreach ($recursiveIterator as $file) {
			$targetPath = $target . DIRECTORY_SEPARATOR . $recursiveIterator->getSubPathName();
			if ($file->isDir()) {
				$this->ensureDirectoryExists($targetPath);
			} else {
				$this->copyFile($file->getPathname(), $targetPath);
			}
		}
	}

}