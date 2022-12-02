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

use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Generates and sets field value for type=uuid
 */
class TcaUuid implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (($fieldConfig['config']['type'] ?? '') !== 'uuid') {
                continue;
            }

            // Skip if field is already filled with a valid uuid
            if (Uuid::isValid((string)($result['databaseRow'][$fieldName] ?? ''))) {
                continue;
            }

            $result['databaseRow'][$fieldName] = (string)match ((int)($fieldConfig['config']['version'] ?? 0)) {
                6 => Uuid::v6(),
                7 => Uuid::v7(),
                default => Uuid::v4()
            };
        }

        return $result;
    }
}
