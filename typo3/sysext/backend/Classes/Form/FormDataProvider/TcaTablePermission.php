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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Data provider for type=select + renderType=tablePermission fields.
 *
 * @internal Only used for be_groups "tablePermission" renderType.
 */
final class TcaTablePermission extends AbstractItemProvider implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        $table = $result['tableName'];

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (($fieldConfig['config']['type'] ?? '') !== 'select'
                || ($fieldConfig['config']['renderType'] ?? '') !== 'tablePermission'
            ) {
                continue;
            }

            if (!isset($GLOBALS['TCA'][$table]['columns'][$fieldConfig['config']['selectFieldName'] ?? null])) {
                throw new \InvalidArgumentException(
                    'renderType="tablePermission" requires option "selectFieldName" to be set to an existing column of table ' . $table,
                    1720028589
                );
            }

            $result['databaseRow'][$fieldName] = [
                'modify' => array_values(array_unique($this->processDatabaseFieldValue($result['databaseRow'], $fieldName))),
                'select' => array_values(array_unique($this->processDatabaseFieldValue($result['databaseRow'], $fieldConfig['config']['selectFieldName']))),
            ];
        }

        return $result;
    }
}
