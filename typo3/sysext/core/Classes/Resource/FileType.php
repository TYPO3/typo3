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

namespace TYPO3\CMS\Core\Resource;

enum FileType: int
{
    /**
     * any other file
     */
    case UNKNOWN = 0;

    /**
     * Any kind of text
     * @see http://www.iana.org/assignments/media-types/text
     */
    case TEXT = 1;

    /**
     * Any kind of image
     * @see http://www.iana.org/assignments/media-types/image
     */
    case IMAGE = 2;

    /**
     * Any kind of audio file
     * @see http://www.iana.org/assignments/media-types/audio
     */
    case AUDIO = 3;

    /**
     * Any kind of video
     * @see http://www.iana.org/assignments/media-types/video
     */
    case VIDEO = 4;

    /**
     * Any kind of application
     * @see http://www.iana.org/assignments/media-types/application
     */
    case APPLICATION = 5;

    public static function tryFromMimeType(string $mimeType): self
    {
        [$fileType] = explode('/', $mimeType);
        return match (strtolower($fileType)) {
            'text' => FileType::TEXT,
            'image' => FileType::IMAGE,
            'audio' => FileType::AUDIO,
            'video' => FileType::VIDEO,
            'application', 'software' => FileType::APPLICATION,
            default => FileType::UNKNOWN,
        };
    }
}
