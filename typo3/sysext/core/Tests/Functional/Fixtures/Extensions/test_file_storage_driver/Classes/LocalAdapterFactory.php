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

namespace TYPO3Tests\TestFileStorageDriver;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use TYPO3\CMS\Core\Attribute\AsFileStorageDriver;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\FilesystemAdapterFactoryInterface;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;

#[AsFileStorageDriver(identifier: 'LocalFlysystem')]
final readonly class LocalAdapterFactory implements FilesystemAdapterFactoryInterface
{
    public function createAdapter(array $configuration): FilesystemAdapter
    {
        $directory = trim((string)($configuration['directory'] ?? ''), '/');
        if ($directory === '') {
            throw new InvalidConfigurationException('Configuration must contain a directory.', 1792093602);
        }
        return new LocalFilesystemAdapter(Environment::getProjectPath() . '/' . $directory, mimeTypeDetector: new FinfoMimeTypeDetector());
    }
}
