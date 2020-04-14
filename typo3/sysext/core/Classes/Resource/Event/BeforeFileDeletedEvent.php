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

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * This event is fired before a file is about to be deleted.
 *
 * Event listeners can clean up third-party references with this event.
 */
final class BeforeFileDeletedEvent
{
    /**
     * @var FileInterface
     */
    private $file;

    public function __construct(FileInterface $file)
    {
        $this->file = $file;
    }

    public function getFile(): FileInterface
    {
        return $this->file;
    }
}
