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

use TYPO3\CMS\Core\Resource\Folder;

/**
 * This event is fired before a folder is about to be renamed.
 * Listeners can be used to modify a folder name before it is actually moved or to ensure consistency
 * or specific rules when renaming folders.
 */
final class BeforeFolderRenamedEvent
{
    /**
     * @var Folder
     */
    private $folder;

    /**
     * @var string
     */
    private $targetName;

    public function __construct(Folder $folder, string $targetName)
    {
        $this->folder = $folder;
        $this->targetName = $targetName;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }

    public function getTargetName(): string
    {
        return $this->targetName;
    }
}
