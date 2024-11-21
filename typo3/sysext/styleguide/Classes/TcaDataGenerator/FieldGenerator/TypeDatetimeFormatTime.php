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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for type=datetime fields with format=time
 *
 * @internal
 */
final class TypeDatetimeFormatTime extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
            ],
        ],
    ];

    public function generate(array $data): string|int
    {
        // 05:23
        $value = 19380;

        // We need to partly do the same work as the DataHandler for some dbTypes for the DateTime type to get
        // database compatible values. Without it, we will get invalid format database exception when inserted.
        // See \TYPO3\CMS\Core\DataHandling\DataHandler::checkValueForDatetime().
        $nativeDateTimeType = $data['fieldConfig']['config']['dbType'] ?? '';
        $dateTimeFormats = QueryHelper::getDateTimeFormats();
        $format = $dateTimeFormats[$nativeDateTimeType]['format'] ?? null;
        if ($format === null) {
            // If database field is configured as integer field type, keep the integer-like value.
            return $value;
        }
        return gmdate($format, $value);
    }
}
