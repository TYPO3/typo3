<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Override some child TCA in an inline parent child relation.
 */
class InlineOverrideChildTca implements FormDataProviderInterface
{
    /**
     * Inline parent TCA may override some TCA of children.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        // Replace types definition of inline child if foreign_types is defined in inlineParentConfig
        if (isset($result['inlineParentConfig']['foreign_types'])) {
            foreach ($result['inlineParentConfig']['foreign_types'] as $type => $config) {
                $result['processedTca']['types'][$type] = $config;
            }
        }

        // Override config section of foreign_selector field pointer if given
        if (isset($result['inlineParentConfig']['foreign_selector'])
            && is_string($result['inlineParentConfig']['foreign_selector'])
            && isset($result['inlineParentConfig']['foreign_selector_fieldTcaOverride'])
            && is_array($result['inlineParentConfig']['foreign_selector_fieldTcaOverride'])
            && isset($result['processedTca']['columns'][$result['inlineParentConfig']['foreign_selector']])
            && is_array($result['processedTca']['columns'][$result['inlineParentConfig']['foreign_selector']])
        ) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $result['processedTca']['columns'][$result['inlineParentConfig']['foreign_selector']],
                $result['inlineParentConfig']['foreign_selector_fieldTcaOverride']
            );
        }

        // Set default values for (new) child if foreign_record_defaults is defined in inlineParentConfig
        if (isset($result['inlineParentConfig']['foreign_record_defaults']) && is_array($result['inlineParentConfig']['foreign_record_defaults'])) {
            $foreignTableConfig = $GLOBALS['TCA'][$result['inlineParentConfig']['foreign_table']];
            // The following system relevant fields can't be set by foreign_record_defaults
            $notSetableFields = [
                'uid',
                'pid',
                't3ver_oid',
                't3ver_id',
                't3ver_label',
                't3ver_wsid',
                't3ver_state',
                't3ver_stage',
                't3ver_count',
                't3ver_tstamp',
                't3ver_move_id',
            ];
            // Optional configuration fields used in child table. If set, they must not be overridden, either
            $configurationKeysForNotSettableFields = [
                'crdate',
                'cruser_id',
                'delete',
                'origUid',
                'transOrigDiffSourceField',
                'transOrigPointerField',
                'tstamp',
            ];
            foreach ($configurationKeysForNotSettableFields as $configurationKey) {
                if (isset($foreignTableConfig['ctrl'][$configurationKey])) {
                    $notSetableFields[] = $foreignTableConfig['ctrl'][$configurationKey];
                }
            }
            foreach ($result['inlineParentConfig']['foreign_record_defaults'] as $fieldName => $defaultValue) {
                if (isset($foreignTableConfig['columns'][$fieldName]) && !in_array($fieldName, $notSetableFields, true)) {
                    $result['processedTca']['columns'][$fieldName]['config']['default'] = $defaultValue;
                }
            }
        }
        return $result;
    }
}
