<?php

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
use TYPO3\CMS\Core\Domain\DateTimeFactory;

/**
 * Migrate type=datetime field values to \DateTimeImmutable
 */
class DatabaseRowDateTimeFields implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        foreach ($result['processedTca']['columns'] as $column => $columnConfig) {
            $type = $columnConfig['config']['type'] ?? '';
            if ($type !== 'datetime') {
                continue;
            }
            try {
                $result['databaseRow'][$column] = DateTimeFactory::createFomDatabaseValueAndTCAConfig(
                    $result['databaseRow'][$column] ?? null,
                    $columnConfig['config'] ?? [],
                );
            } catch (\InvalidArgumentException) {
                $result['databaseRow'][$column] = null;
            }
        }
        return $result;
    }
}
