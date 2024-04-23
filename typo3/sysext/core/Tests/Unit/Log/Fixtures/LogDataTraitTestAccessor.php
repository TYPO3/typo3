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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Fixtures;

use TYPO3\CMS\Core\Log\LogDataTrait;

final class LogDataTraitTestAccessor
{
    use LogDataTrait;

    public function callUnserializeLogData(mixed $logData): ?array
    {
        return $this->unserializeLogData($logData);
    }

    public function callFormatLogDetails(string $detailString, mixed $substitutes): string
    {
        return $this->formatLogDetails($detailString, $substitutes);
    }

    public static function callFormatLogDetailsStatic(string $detailString, array $substitutes): string
    {
        return self::formatLogDetailsStatic($detailString, $substitutes);
    }
}
