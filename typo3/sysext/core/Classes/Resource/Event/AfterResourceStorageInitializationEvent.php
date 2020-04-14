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

use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * This event is fired after a resource object was built/created.
 *
 * Custom handlers can be initialized at this moment for any kind of source  as well.
 */
final class AfterResourceStorageInitializationEvent
{
    /**
     * @var ResourceStorage
     */
    private $storage;

    public function __construct(ResourceStorage $storage)
    {
        $this->storage = $storage;
    }

    public function getStorage(): ResourceStorage
    {
        return $this->storage;
    }

    public function setStorage(ResourceStorage $storage): void
    {
        $this->storage = $storage;
    }
}
