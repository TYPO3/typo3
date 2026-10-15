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
 * The file storage driver of all drivers provided by a FilesystemAdapterFactoryInterface,
 * which creates the adapter from the storage configuration.
 */
class FlysystemDriver extends AbstractFlysystemDriver
{
    public function __construct(
        array $configuration = [],
        protected readonly ?FilesystemAdapterFactoryInterface $adapterFactory = null,
    ) {
        parent::__construct($configuration);
    }

    protected function createFilesystemAdapter(): FilesystemAdapter
    {
        if ($this->adapterFactory === null) {
            throw new InvalidConfigurationException(
                'The Flysystem driver requires an adapter factory, register one with #[AsFileStorageDriver].',
                1792093601
            );
        }
        return $this->adapterFactory->createAdapter($this->configuration);
    }
}
