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

namespace TYPO3\CMS\Core\Package\Cache;

use Composer\Util\Filesystem;

/**
 * TYPO3 Package "cache" for Composer mode.
 * This class is used in two contexts:
 * During Composer build time the artifact is stored
 * and during TYPO3 runtime the artifact is only read.
 * The context is decided on object construction.
 *
 * @internal This class is an implementation detail and does not represent public API
 */
class ComposerPackageArtifact implements PackageCacheInterface
{
    /**
     * Location of the file inside the var folder
     */
    private const ARTIFACTS_FILE = '/PackageArtifact.php';

    /**
     * Full filesystem path to the file
     *
     * @var string
     */
    private string $packageArtifactsFile;

    /**
     * The cache entry generated from the artifact
     *
     * @var PackageCacheEntry
     */
    private PackageCacheEntry $cacheEntry;

    /**
     * Composer filesystem, provided during Composer build time
     *
     * @var Filesystem|null
     */
    private ?Filesystem $filesystem;

    /**
     * The cache identifier that is stored alongside the artifact
     * and used as part of TYPO3 cache identifiers
     *
     * @var string|null
     */
    private ?string $cacheIdentifier;

    public function __construct(string $packageArtifactsPath, ?Filesystem $filesystem = null, string $cacheIdentifier = null)
    {
        $this->packageArtifactsFile = $packageArtifactsPath . self::ARTIFACTS_FILE;
        $this->filesystem = $filesystem;
        $this->cacheIdentifier = $cacheIdentifier;
    }

    public function fetch(): PackageCacheEntry
    {
        if ($this->isComposerBuildContext()) {
            throw new \RuntimeException('Can not load package states during generation', 1629820498);
        }
        if (isset($this->cacheEntry)) {
            return $this->cacheEntry;
        }
        $packageData = @include $this->packageArtifactsFile;
        if (!$packageData) {
            throw new \RuntimeException('Package artifact not found. Run "composer install" to create it.', 1629819799);
        }

        return $this->cacheEntry = PackageCacheEntry::fromCache($packageData);
    }

    public function store(PackageCacheEntry $cacheEntry): void
    {
        if (!$this->isComposerBuildContext()) {
            throw new \RuntimeException('Can not modify package states in Composer mode', 1629819858);
        }
        $this->filesystem->ensureDirectoryExists(dirname($this->packageArtifactsFile));

        file_put_contents($this->packageArtifactsFile, '<?php' . PHP_EOL . 'return ' . PHP_EOL . $cacheEntry->withIdentifier($this->cacheIdentifier)->serialize() . ';');
    }

    public function invalidate(): void
    {
        throw new \RuntimeException('Can not modify package states in Composer mode', 1629824596);
    }

    public function getIdentifier(): string
    {
        if (!isset($this->cacheEntry)) {
            $this->fetch();
        }

        return $this->cacheEntry->getIdentifier();
    }

    private function isComposerBuildContext(): bool
    {
        return isset($this->filesystem, $this->cacheIdentifier);
    }
}
