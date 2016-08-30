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
use TYPO3\CMS\Lang\LanguageService;

/**
 * Resolve radio items and set processed item list in processedTca
 */
class TcaRadioItems extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * Resolve radio items
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        $languageService = $this->getLanguageService();
        $table = $result['tableName'];

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'radio') {
                continue;
            }

            if (!is_array($fieldConfig['config']['items'])) {
                throw new \UnexpectedValueException(
                    'Radio field ' . $fieldName . ' of TCA table ' . $result['tableName'] . ' must have \'config\' \'items\' definition',
                    1438594829
                );
            }

            $config = $fieldConfig['config'];
            $items = $config['items'];

            // Sanitize items and translate labels
            $newItems = [];
            foreach ($items as $itemKey => $itemValue) {
                if (!is_array($itemValue)) {
                    throw new \UnexpectedValueException(
                        'Item ' . $itemKey . ' of field ' . $fieldName . ' of TCA table ' . $result['tableName'] . ' is no array as exepcted',
                        1438607163
                    );
                }
                if (!array_key_exists(0, $itemValue)) {
                    throw new \UnexpectedValueException(
                        'Item ' . $itemKey . ' of field ' . $fieldName . ' of TCA table ' . $result['tableName'] . ' has no label',
                        1438607164
                    );
                }
                if (!array_key_exists(1, $itemValue)) {
                    throw new \UnexpectedValueException(
                        'Item ' . $itemKey . ' of field ' . $fieldName . ' of TCA table ' . $result['tableName'] . ' has no value',
                        1438607165
                    );
                }
                $newItems[$itemKey] = [
                    $languageService->sL(trim($itemValue[0])),
                    $itemValue[1]
                ];
            }
            $items = $newItems;

            // Resolve "itemsProcFunc"
            if (!empty($config['itemsProcFunc'])) {
                $items = $this->resolveItemProcessorFunction($result, $fieldName, $items);
                // itemsProcFunc must not be used anymore
                unset($result['processedTca']['columns'][$fieldName]['config']['itemsProcFunc']);
            }

            // Set label overrides from pageTsConfig if given
            if (isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'])
                && is_array($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'])
            ) {
                foreach ($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'] as $itemKey => $label) {
                    if (isset($items[$itemKey][0])) {
                        $items[$itemKey][0] = $languageService->sL(trim($label));
                    }
                }
            }

            $result['processedTca']['columns'][$fieldName]['config']['items'] = $items;
        }

        return $result;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
