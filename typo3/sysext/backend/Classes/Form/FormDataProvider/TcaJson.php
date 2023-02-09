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
 * Resolve and prepare json data.
 */
class TcaJson extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        // Currently only new records are considered
        if ($result['command'] !== 'new') {
            return $result;
        }

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (($fieldConfig['config']['type'] ?? '') !== 'json') {
                continue;
            }

            // Ensure that even for new records, the field is always an array - especially if a default value is defined
            if (is_string($result['databaseRow'][$fieldName])) {
                try {
                    $result['databaseRow'][$fieldName] = json_decode($result['databaseRow'][$fieldName], true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $result['databaseRow'][$fieldName] = [];
                }
            }
        }

        return $result;
    }
}
