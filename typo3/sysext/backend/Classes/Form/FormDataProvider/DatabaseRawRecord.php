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
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;

/**
 * Create a raw record from databaseRow before its values are processed
 * by subsequent data providers, e.g. into DateTime objects or arrays.
 */
class DatabaseRawRecord implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        $row = $result['databaseRow'];
        $computedProperties = new ComputedProperties(
            isset($row['_ORIG_uid']) ? (int)$row['_ORIG_uid'] : null,
            isset($row['_LOCALIZED_UID']) ? (int)$row['_LOCALIZED_UID'] : null,
        );
        unset($row['_ORIG_uid'], $row['_LOCALIZED_UID'], $row['_REQUESTED_OVERLAY_LANGUAGE'], $row['_TRANSLATION_SOURCE']);
        $fullType = $result['tableName'];
        if (!empty($result['processedTca']['ctrl']['type']) && $result['recordTypeValue'] !== '') {
            $fullType .= '.' . $result['recordTypeValue'];
        }
        $result['rawRecord'] = new RawRecord(
            // Not yet persisted records have a "NEW..." uid, which results in 0
            (int)($row['uid'] ?? 0),
            (int)($row['pid'] ?? 0),
            $row,
            $computedProperties,
            $fullType
        );
        return $result;
    }
}
