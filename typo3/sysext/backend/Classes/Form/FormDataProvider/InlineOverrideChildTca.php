<?php
declare(strict_types = 1);
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

/**
 * Override child TCA in an inline parent child relation.
 *
 * This basically merges the inline property ['overrideChildTca'] from
 * parent TCA over given child TCA.
 */
class InlineOverrideChildTca implements FormDataProviderInterface
{
    /**
     * ['columns'] section child TCA field names that can not be overridden
     * by overrideChildTca from parent.
     *
     * @var array
     */
    protected $notSettableFields = [
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

    /**
     * Configuration fields in ctrl section. Their values are field names and if the
     * keys are set in ['ctrl'] section, they are added to the $notSettableFields list
     * and can not be overridden, too.
     *
     * @var array
     */
    protected $configurationKeysForNotSettableFields = [
        'crdate',
        'cruser_id',
        'delete',
        'origUid',
        'transOrigDiffSourceField',
        'transOrigPointerField',
        'tstamp',
    ];

    /**
     * Inline parent TCA may override some TCA of children.
     *
     * @param array $result Main result array
     * @return array Modified result array
     */
    public function addData(array $result): array
    {
        $result = $this->overrideTypes($result);
        return $this->overrideColumns($result);
    }

    /**
     * Override ['types'] configuration in child TCA
     *
     * @param array $result Main result array
     * @return array Modified result array
     */
    protected function overrideTypes(array $result): array
    {
        if (!isset($result['inlineParentConfig']['overrideChildTca']['types'])) {
            return $result;
        }
        $result['processedTca']['types'] = array_replace_recursive(
            $result['processedTca']['types'],
            $result['inlineParentConfig']['overrideChildTca']['types']
        );
        return $result;
    }

    /**
     * Override ['columns'] configuration in child TCA.
     * Sanitizes that various hard dependencies can not be changed.
     *
     * @param array $result Main result array
     * @return array Modified result array
     * @throws \RuntimeException
     */
    protected function overrideColumns(array $result): array
    {
        if (!isset($result['inlineParentConfig']['overrideChildTca']['columns'])) {
            return $result;
        }
        $fieldBlackList = $this->generateFieldBlackList($result);
        foreach ($fieldBlackList as $notChangeableFieldName) {
            if (isset($result['inlineParentConfig']['overrideChildTca']['columns'][$notChangeableFieldName])) {
                throw new \RuntimeException(
                    'System field \'' . $notChangeableFieldName . '\' can not be overridden in inline config'
                    . ' \'overrideChildTca\' from parent TCA',
                    1490371322
                );
            }
        }
        $result['processedTca']['columns'] = array_replace_recursive(
            $result['processedTca']['columns'],
            $result['inlineParentConfig']['overrideChildTca']['columns']
        );
        return $result;
    }

    /**
     * Add field names defined in ctrl section of child table to black list
     *
     * @param array $result Main result array
     * @return array Column field names which can not be changed by parent TCA
     */
    protected function generateFieldBlackList(array $result): array
    {
        $notSettableFields = $this->notSettableFields;
        foreach ($this->configurationKeysForNotSettableFields as $configurationKey) {
            if (isset($result['processedTca']['ctrl'][$configurationKey])) {
                $notSettableFields[] = $result['processedTca']['ctrl'][$configurationKey];
            }
        }
        return $notSettableFields;
    }
}
