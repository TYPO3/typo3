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

namespace TYPO3\CMS\Core\Resource\Event;

use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * This event is fired before TYPO3 FAL's native URL generation for a Resource is instantiated.
 *
 * This allows for listeners to create custom links to certain files (e.g. restrictions) for creating
 * authorized deeplinks.
 */
final class GeneratePublicUrlForResourceEvent
{
    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @var ResourceStorage
     */
    private $storage;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var bool
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     */
    private $relativeToCurrentScript;

    /**
     * @var string|null
     */
    private $publicUrl;

    public function __construct(ResourceInterface $resource, ResourceStorage $storage, DriverInterface $driver, bool $relativeToCurrentScript = false)
    {
        $this->resource = $resource;
        $this->storage = $storage;
        $this->driver = $driver;
        $this->relativeToCurrentScript = $relativeToCurrentScript;
        $this->publicUrl = null;
    }

    public function getResource(): ResourceInterface
    {
        return $this->resource;
    }

    public function getStorage(): ResourceStorage
    {
        return $this->storage;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     */
    public function isRelativeToCurrentScript(): bool
    {
        trigger_error('isRelativeToCurrentScript() is deprecated since TYPO3 v11, will be removed in TYPO3 v12.0', E_USER_DEPRECATED);
        return $this->relativeToCurrentScript;
    }

    public function getPublicUrl(): ?string
    {
        return $this->publicUrl;
    }

    public function setPublicUrl(?string $publicUrl): void
    {
        $this->publicUrl = $publicUrl;
    }
}
