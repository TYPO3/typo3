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

namespace TYPO3\CMS\Backend\Backend;

/**
 * @internal
 */
enum ThumbnailSize: string
{
    case DEFAULT = 'default';
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';

    public function getDimensions(): array
    {
        return array_map(static fn(int $size) => $size . 'm', $this->getBaseDimensions());
    }

    public function getCroppedDimensions(): array
    {
        return array_map(static fn(int $size) => $size . 'c', $this->getBaseDimensions());
    }

    private function getBaseDimensions(): array
    {
        return match ($this) {
            self::DEFAULT, self::SMALL => [32, 32],
            self::MEDIUM => [64, 64],
            self::LARGE => [96, 96],
        };
    }
}
