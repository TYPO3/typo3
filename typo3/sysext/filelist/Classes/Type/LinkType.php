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

namespace TYPO3\CMS\Filelist\Type;

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * @internal
 */
enum LinkType: string
{
    case FILE = 'file';
    case FOLDER = 'folder';

    public function getResourceType(): string
    {
        return match ($this) {
            LinkType::FILE => File::class,
            LinkType::FOLDER => Folder::class,
        };
    }

    public function getLinkServiceType(): string
    {
        return match ($this) {
            LinkType::FILE => LinkService::TYPE_FILE,
            LinkType::FOLDER => LinkService::TYPE_FOLDER,
        };
    }
}
