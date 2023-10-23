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

namespace TYPO3\CMS\Backend\ElementBrowser\Event;

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Listeners to this event are able to define whether a file can be selected in the file browser
 */
final class IsFileSelectableEvent
{
    private bool $isFileSelectable = true;

    public function __construct(
        private readonly FileInterface $file,
    ) {}

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function isFileSelectable(): bool
    {
        return $this->isFileSelectable;
    }

    public function allowFileSelection(): void
    {
        $this->isFileSelectable = true;
    }

    public function denyFileSelection(): void
    {
        $this->isFileSelectable = false;
    }
}
