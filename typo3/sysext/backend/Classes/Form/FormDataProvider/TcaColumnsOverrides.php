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

/**
 * Merge type specific columnsOverrides into columns of processedTca
 */
class TcaColumnsOverrides implements FormDataProviderInterface
{
    /**
     * Merge columnsOverrides
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $type = $result['recordTypeValue'];
        if (isset($result['processedTca']['types'][$type]['columnsOverrides'])
            && is_array($result['processedTca']['types'][$type]['columnsOverrides'])
        ) {
            $result['processedTca']['columns'] = array_replace_recursive(
                $result['processedTca']['columns'],
                $result['processedTca']['types'][$type]['columnsOverrides']
            );
            if ($result['command'] === 'new') {
                $tableNameWithDot = $result['tableName'] . '.';
                foreach ($result['processedTca']['types'][$type]['columnsOverrides'] as $field => $columnsOverrideConfig) {
                    $overridenDefault = $columnsOverrideConfig['config']['default'] ?? '';
                    if ($overridenDefault !== ''
                        && !isset($result['userTsConfig']['TCAdefaults.'][$tableNameWithDot][$field])
                        && !isset($result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot][$field])
                        && ($result['databaseRow'][$field] ?? '') !== $overridenDefault
                    ) {
                        $result['databaseRow'][$field] = $overridenDefault;
                    }
                }
            }
            unset($result['processedTca']['types'][$type]['columnsOverrides']);
        }
        return $result;
    }
}
