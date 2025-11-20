<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\SystemResource\Publishing\FileSystem;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Exception\PackageAssetsPublishingFailedException;
use TYPO3\CMS\Core\Utility\File\FileSystem;

/**
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
final readonly class SymlinkPublisher implements FileSystemPublisherInterface
{
    private PublishingConfiguration $config;

    public function __construct(private FileSystem $fileSystem)
    {
        $this->config = new PublishingConfiguration();
    }

    public function canPublish(string $source, string $target): bool
    {
        return !Environment::isWindows()
            && $this->config->isLinkPublishingEnabled();
    }

    public function publishFolder(string $source, string $target): void
    {
        $this->ensureSymlinkExists($source, $target);
    }

    public function publishFile(string $source, string $target): void
    {
        $this->ensureSymlinkExists($source, $target);
    }

    /**
     * @throws PackageAssetsPublishingFailedException
     */
    private function ensureSymlinkExists(string $target, string $link): void
    {
        $success = true;
        if (!$this->fileSystem->isSymlinkedDirectory($link)) {
            $success = $this->fileSystem->relativeSymlink($target, $link);
        }

        if (!$success || realpath($target) !== realpath($link)) {
            throw new PackageAssetsPublishingFailedException(
                'symlink',
                1717488536,
            );
        }
    }
}
