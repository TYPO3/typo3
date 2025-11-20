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

use Symfony\Component\Filesystem\Exception\IOException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Exception\PackageAssetsPublishingFailedException;
use TYPO3\CMS\Core\Utility\File\FileSystem;

/**
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
final readonly class JunctionPublisher implements FileSystemPublisherInterface
{
    private PublishingConfiguration $config;

    public function __construct(private FileSystem $fileSystem)
    {
        $this->config = new PublishingConfiguration();
    }

    public function canPublish(string $source, string $target): bool
    {
        return Environment::isWindows()
            && $this->config->isLinkPublishingEnabled();
    }

    /**
     * @throws PackageAssetsPublishingFailedException
     */
    public function publishFolder(string $source, string $target): void
    {
        $this->ensureJunctionExists($source, $target);
    }

    /**
     * @throws \LogicException
     */
    public function publishFile(string $source, string $target): void
    {
        throw new \LogicException(self::class . ' can not be used to publish single files', 1772535297);
    }

    /**
     * @throws PackageAssetsPublishingFailedException
     */
    private function ensureJunctionExists(string $target, string $junction): void
    {
        $e = null;
        if (!$this->fileSystem->isJunction($junction)) {
            try {
                $this->fileSystem->junction($target, $junction);
            } catch (IOException $e) {
            }
        }

        if ($e !== null || realpath($target) !== realpath($junction)) {
            throw new PackageAssetsPublishingFailedException(
                'junction',
                1717488535,
                $e,
            );
        }
    }
}
