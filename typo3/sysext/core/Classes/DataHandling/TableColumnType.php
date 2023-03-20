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

namespace TYPO3\CMS\Core\DataHandling;

/**
 * Enumeration object for tca type
 */
enum TableColumnType: string
{
    case INPUT = 'input';
    case TEXT = 'text';
    case CHECK = 'check';
    case RADIO = 'radio';
    case SELECT = 'select';
    case GROUP = 'group';
    case FOLDER = 'folder';
    case NONE = 'none';
    case LANGUAGE = 'language';
    case PASSTHROUGH = 'passthrough';
    case USER = 'user';
    case FLEX = 'flex';
    case INLINE = 'inline';
    case IMAGEMANIPULATION = 'imagemanipulation';
    case SLUG = 'slug';
    case CATEGORY = 'category';
    case EMAIL = 'email';
    case LINK = 'link';
    case PASSWORD = 'password';
    case DATETIME = 'datetime';
    case COLOR = 'color';
    case NUMBER = 'number';
    case FILE = 'file';
    case JSON = 'json';
    case UUID = 'uuid';
}
