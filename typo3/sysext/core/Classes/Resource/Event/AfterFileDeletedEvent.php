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
 * This event is fired after a file was deleted.
 *
 * Example: If an extension provides additional functionality (e.g. variants), this event allows listener to also clean
 * up their custom handling. This can also be used for versioning of files.
 */
final class AfterFileDeletedEvent
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
