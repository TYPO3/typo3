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

namespace TYPO3\CMS\Frontend\Resource;

/**
 * Enum with different sorting strategies used in the FileCollector.
 *
 * @internal this is an internal TYPO3 implementation and solely used for sorting within FileCollector
 */
enum FileCollectionSorting: string
{
    case Ascending = 'ascending';
    case Descending = 'descending';
    case Random = 'random';

    public static function fromKeyword(string $keyword): self
    {
        $keyword = strtolower($keyword);

        return self::tryFrom($keyword) ?? match ($keyword) {
            'asc' => self::Ascending,
            'desc' => self::Descending,
            'rand' => self::Random,
            default => throw new \ValueError(
                sprintf('"%s" is not a valid keyword for enum "%s"', $keyword, self::class),
                1787816013,
            ),
        };
    }
}
