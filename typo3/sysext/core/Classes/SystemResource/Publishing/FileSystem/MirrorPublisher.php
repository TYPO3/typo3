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

use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use TYPO3\CMS\Core\Package\Exception\PackageAssetsPublishingFailedException;

/**
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
final readonly class MirrorPublisher implements FileSystemPublisherInterface
{
    private PublishingConfiguration $config;

    public function __construct()
    {
        $this->config = new PublishingConfiguration();
    }

    public function canPublish(string $source, string $target): bool
    {
        return $this->config->isMirrorPublishingEnabled();
    }

    public function publishFolder(string $source, string $target): void
    {
        if (realpath($source) === realpath($target)) {
            throw new PackageAssetsPublishingFailedException(
                'mirror',
                1773140314,
            );
        }
        $symfonyFilesystem = new SymfonyFilesystem();
        $symfonyFilesystem->mirror(
            $source,
            $target,
            null,
            [
                'delete' => true,
                'override' => true,
            ],
        );
    }

    public function publishFile(string $source, string $target): void
    {
        if (!is_file($source)) {
            throw new \LogicException('Can not publish file, because source is not a file', 1772538042);
        }
        if (realpath($source) === realpath($target)) {
            throw new PackageAssetsPublishingFailedException(
                'mirror',
                1773140294,
            );
        }
        $symfonyFilesystem = new SymfonyFilesystem();
        $symfonyFilesystem->copy($source, $target, true);
    }
}
