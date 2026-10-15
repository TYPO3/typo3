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

namespace TYPO3\CMS\Core\Resource\Driver;

use League\Flysystem\FilesystemAdapter;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;

/**
 * Creates the Flysystem adapter of a file storage. Implementations are services
 * registered with #[AsFileStorageDriver], so they can get their
 * dependencies injected, and are used by the FlysystemDriver.
 */
interface FilesystemAdapterFactoryInterface
{
    /**
     * @param array<string, mixed> $configuration The "configuration" of the storage
     * @throws InvalidConfigurationException if the configuration is incomplete or invalid
     */
    public function createAdapter(array $configuration): FilesystemAdapter;
}
