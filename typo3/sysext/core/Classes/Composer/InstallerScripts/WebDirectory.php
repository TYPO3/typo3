<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Composer\InstallerScripts;

/*
 * This file is part of the TYPO3 project.
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

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Semver\Constraint\EmptyConstraint;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Setting up TYPO3 web directory
 */
class WebDirectory implements InstallerScript
{
    /**
     * @var string
     */
    private static $typo3Dir = '/typo3';

    /**
     * @var string
     */
    private static $systemExtensionsDir = '/sysext';

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Config
     */
    private $pluginConfig;

    /**
     * Prepare the web directory with symlinks
     *
     * @param Event $event
     * @return bool
     */
    public function run(Event $event): bool
    {
        $this->io = $event->getIO();
        $this->composer = $event->getComposer();
        $this->filesystem = new Filesystem();
        $this->pluginConfig = Config::load($this->composer);

        $symlinks = $this->initializeSymlinks();
        if ($this->filesystem->someFilesExist($symlinks)) {
            $this->filesystem->removeSymlinks($symlinks);
        }
        $this->filesystem->establishSymlinks($symlinks);

        return true;
    }

    /**
     * Initialize symlinks from configuration
     * @return array
     */
    private function initializeSymlinks(): array
    {
        $webDir = $this->filesystem->normalizePath($this->pluginConfig->get('web-dir'));
        $backendDir = $webDir . self::$typo3Dir;
        // Ensure we delete a previously existing symlink to typo3 folder in web directory
        if ($this->filesystem->isSymlinkedDirectory($backendDir)) {
            $this->filesystem->removeDirectory($backendDir);
        }
        $this->filesystem->ensureDirectoryExists($backendDir);
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $localRepository->findPackage('typo3/cms', new EmptyConstraint());
        $sourcesDir = $this->composer->getInstallationManager()->getInstallPath($package);
        return [
            $sourcesDir . self::$typo3Dir . self::$systemExtensionsDir => $backendDir . self::$systemExtensionsDir,
        ];
    }
}
