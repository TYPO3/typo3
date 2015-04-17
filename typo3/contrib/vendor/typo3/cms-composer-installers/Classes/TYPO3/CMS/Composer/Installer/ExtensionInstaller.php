<?php
namespace TYPO3\CMS\Composer\Installer;

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

use Composer\Package\PackageInterface;

/**
 * Enter descriptions here
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class ExtensionInstaller implements \Composer\Installer\InstallerInterface {

	const TYPO3_CONF_DIR = 'typo3conf';
	const TYPO3_EXT_DIR = 'ext';

	/**
	 * @var string
	 */
	protected $extensionDir;

	/**
	 * @var \Composer\Composer
	 */
	protected $composer;

	/**
	 * @var \Composer\Downloader\DownloadManager
	 */
	protected $downloadManager;

	/**
	 * @var \Composer\Util\Filesystem
	 */
	protected $filesystem;

	/**
	 * @param \Composer\Composer $composer
	 * @param \Composer\Util\Filesystem $filesystem
	 */
	public function __construct(\Composer\Composer $composer, \Composer\Util\Filesystem $filesystem = NULL) {
		$this->composer = $composer;
		$this->downloadManager = $composer->getDownloadManager();

		$this->filesystem = $filesystem ? : new \Composer\Util\Filesystem();
		$this->extensionDir = self::TYPO3_CONF_DIR . DIRECTORY_SEPARATOR . self::TYPO3_EXT_DIR;
	}

	/**
	 * Decides if the installer supports the given type
	 *
	 * @param  string $packageType
	 * @return bool
	 */
	public function supports($packageType) {
		return $packageType !== 'typo3-cms-core'
			// strncmp is about 20% faster than substr
			&& strncmp('typo3-cms-', $packageType, 10) === 0;
	}

	/**
	 * Checks that provided package is installed.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param PackageInterface $package package instance
	 *
	 * @return bool
	 */
	public function isInstalled(\Composer\Repository\InstalledRepositoryInterface $repo, PackageInterface $package) {
		return $repo->hasPackage($package) && is_readable($this->getInstallPath($package));
	}

	/**
	 * Installs specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param PackageInterface $package package instance
	 */
	public function install(\Composer\Repository\InstalledRepositoryInterface $repo, PackageInterface $package) {
		$this->initializeExtensionDir();

		$this->installCode($package);
		if (!$repo->hasPackage($package)) {
			$repo->addPackage(clone $package);
		}
	}

	/**
	 * Updates specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param PackageInterface $initial already installed package version
	 * @param PackageInterface $target updated version
	 *
	 * @throws \InvalidArgumentException if $initial package is not installed
	 */
	public function update(\Composer\Repository\InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target) {
		if (!$repo->hasPackage($initial)) {
			throw new \InvalidArgumentException('Package is not installed: ' . $initial);
		}

		$this->initializeExtensionDir();

		$this->updateCode($initial, $target);
		$repo->removePackage($initial);
		if (!$repo->hasPackage($target)) {
			$repo->addPackage(clone $target);
		}
	}

	/**
	 * Uninstalls specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param PackageInterface $package package instance
	 *
	 * @throws \InvalidArgumentException if $initial package is not installed
	 */
	public function uninstall(\Composer\Repository\InstalledRepositoryInterface $repo, PackageInterface $package) {
		if (!$repo->hasPackage($package)) {
			throw new \InvalidArgumentException('Package is not installed: ' . $package);
		}

		$this->removeCode($package);
		$repo->removePackage($package);
	}

	/**
	 * Returns the installation path of a package
	 *
	 * @param  PackageInterface $package
	 * @return string           path
	 */
	public function getInstallPath(PackageInterface $package) {
		$extensionKey = '';
		foreach ($package->getReplaces() as $packageName => $version) {
			if (strpos($packageName, '/') === FALSE) {
				$extensionKey = trim($packageName);
				break;
			}
		}
		if (empty($extensionKey)) {
			list(, $extensionKey) = explode('/', $package->getName(), 2);
			$extensionKey = str_replace('-', '_', $extensionKey);
		}
		return $this->extensionDir . DIRECTORY_SEPARATOR . $extensionKey;
	}

	/**
	 * @param PackageInterface $package
	 */
	protected function installCode(PackageInterface $package) {
		$this->downloadManager->download($package, $this->getInstallPath($package));
	}

	/**
	 * @param PackageInterface $initial
	 * @param PackageInterface $target
	 */
	protected function updateCode(PackageInterface $initial, PackageInterface $target) {
		$initialDownloadPath = $this->getInstallPath($initial);
		$targetDownloadPath = $this->getInstallPath($target);
		if ($targetDownloadPath !== $initialDownloadPath) {
			// if the target and initial dirs intersect, we force a remove + install
			// to avoid the rename wiping the target dir as part of the initial dir cleanup
			if (substr($initialDownloadPath, 0, strlen($targetDownloadPath)) === $targetDownloadPath
				|| substr($targetDownloadPath, 0, strlen($initialDownloadPath)) === $initialDownloadPath
			) {
				$this->removeCode($initial);
				$this->installCode($target);

				return;
			}

			$this->filesystem->rename($initialDownloadPath, $targetDownloadPath);
		}
		$this->downloadManager->update($initial, $target, $targetDownloadPath);
	}

	/**
	 * @param PackageInterface $package
	 */
	protected function removeCode(PackageInterface $package) {
		$this->downloadManager->remove($package, $this->getInstallPath($package));
	}

	/**
	 *
	 */
	protected function initializeExtensionDir() {
		$this->filesystem->ensureDirectoryExists($this->extensionDir);
		$this->extensionDir = realpath($this->extensionDir);
	}
}
