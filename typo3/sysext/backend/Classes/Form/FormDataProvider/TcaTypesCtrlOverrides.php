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
 * Merge type-specific ctrl configuration from the types section into processedTca ctrl section.
 *
 * This allows tables to define type-specific ctrl properties in the types section
 * that override the global ctrl values for that specific record type.
 *
 * @todo This data provider is just an intermediate solution until FormEngine is using TCA Schema
 */
class TcaTypesCtrlOverrides implements FormDataProviderInterface
{
    /**
     * List of ctrl properties that can be overridden per type.
     * This prevents arbitrary ctrl properties from being overridden which might
     * cause issues if they affect the structure or behavior of TCA processing.
     *
     * Currently supported: 'title' and 'previewRenderer'.
     * Additional properties may be added in the future.
     */
    protected array $allowedCtrlOverrides = [
        'title',
        'previewRenderer',
    ];

    public function addData(array $result): array
    {
        $type = $result['recordTypeValue'];
        if (isset($result['processedTca']['types'][$type]) && is_array($result['processedTca']['types'][$type])) {
            $typeConfiguration = $result['processedTca']['types'][$type];

            // Merge allowed ctrl properties from type configuration into ctrl section
            foreach ($this->allowedCtrlOverrides as $property) {
                if (array_key_exists($property, $typeConfiguration)) {
                    $result['processedTca']['ctrl'][$property] = $typeConfiguration[$property];
                }
            }
        }

        return $result;
    }
}
