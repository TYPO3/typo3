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

use Doctrine\DBAL\Types\IntegerType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for type=datetime fields with format=timesec
 *
 * @internal
 */
final class TypeDatetimeFormatTimesec extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * General match if type=datetime and format=timesec
     *
     * @var array
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
            ],
        ],
    ];

    /**
     * Returns the generated value to be inserted into DB for this field
     *
     * @param array $data
     * @return string
     */
    public function generate(array $data): string
    {
        // 05:23:42
        $value = '19422';

        // If database field is configured as integer field type, keep the integer-like value.
        $tableSchemaInformation = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($data['tableName'])
            ->getSchemaInformation()
            ->introspectTable($data['tableName']);
        if ($tableSchemaInformation->hasColumn($data['fieldName'])
            && $tableSchemaInformation->getColumn($data['fieldName'])->getType() instanceof IntegerType
        ) {
            return $value;
        }

        // We need to partly do the same work as the DataHandler for some dbTypes for the DateTime type to get
        // database compatible values. Without it, we will get invalid format database exception when inserted.
        // See \TYPO3\CMS\Core\DataHandling\DataHandler::checkValueForDatetime().
        $nativeDateTimeType = $data['fieldConfig']['config']['dbType'] ?? '';
        $dateTimeFormats = QueryHelper::getDateTimeFormats();
        $nativeDateTimeFieldFormat = $dateTimeFormats[$nativeDateTimeType]['format'] ?? 'h:i:s';
        $value =  gmdate($nativeDateTimeFieldFormat, (int)$value);

        return $value;
    }
}
