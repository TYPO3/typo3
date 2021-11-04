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
 * Resolve checkbox items and set processed item list in processedTca
 */
class TcaCheckboxItems extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * Resolve checkbox items
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
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'check') {
                continue;
            }

            if (!is_array($fieldConfig['config']['items'] ?? null)) {
                $fieldConfig['config']['items'] = [];
            }

            $config = $fieldConfig['config'];
            $items = $this->sanitizeConfiguration($config, $fieldName, $table);

            // Resolve "itemsProcFunc"
            if (!empty($config['itemsProcFunc'])) {
                $items = $this->resolveItemProcessorFunction($result, $fieldName, $items);
                // itemsProcFunc must not be used anymore
                unset($result['processedTca']['columns'][$fieldName]['config']['itemsProcFunc']);
            }

            // Set label overrides from pageTsConfig if given
            if (isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'])
                && \is_array($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'])
            ) {
                foreach ($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'] as $itemKey => $label) {
                    if (isset($items[$itemKey][0])) {
                        $items[$itemKey][0] = $languageService->sL($label);
                    }
                }
            }

            $result['processedTca']['columns'][$fieldName]['config']['items'] = $items;
        }

        return $result;
    }

    /**
     * @param array $config
     * @param string $fieldName
     * @param string $tableName
     * @return array
     * @throws \UnexpectedValueException
     */
    private function sanitizeConfiguration(array $config, string $fieldName, string $tableName)
    {
        $newItems = [];
        foreach ($config['items'] as $itemKey => $checkboxEntry) {
            $this->basicChecks($fieldName, $tableName, $checkboxEntry, $itemKey);
            $newItems[$itemKey] = [
                $this->getLanguageService()->sL(trim($checkboxEntry[0])),
            ];
            if (isset($config['renderType']) && $config['renderType'] === 'checkboxToggle') {
                $newItems = $this->sanitizeToggleCheckbox($checkboxEntry, $itemKey, $newItems);
            } elseif (isset($config['renderType']) && $config['renderType'] === 'checkboxLabeledToggle') {
                $newItems = $this->sanitizeLabeledToggleCheckbox($checkboxEntry, $itemKey, $newItems);
            } else {
                $newItems = $this->sanitizeIconToggleCheckbox($checkboxEntry, $itemKey, $newItems);
            }
        }
        return $newItems;
    }

    /**
     * @param string $fieldName
     * @param string $tableName
     * @param mixed $checkboxEntry
     * @param int $checkboxKey
     * @throws \UnexpectedValueException
     */
    private function basicChecks(string $fieldName, string $tableName, $checkboxEntry, int $checkboxKey)
    {
        if (!\is_array($checkboxEntry)) {
            throw new \UnexpectedValueException(
                'Item ' . $checkboxKey . ' of field ' . $fieldName . ' of TCA table ' . $tableName . ' is not an array as expected',
                1440499337
            );
        }
        if (!\array_key_exists(0, $checkboxEntry)) {
            throw new \UnexpectedValueException(
                'Item ' . $checkboxKey . ' of field ' . $fieldName . ' of TCA table ' . $tableName . ' has no label',
                1440499338
            );
        }
    }

    /**
     * @param array $item
     * @param int $itemKey
     * @param array $newItems
     * @return array
     */
    private function sanitizeToggleCheckbox(array $item, int $itemKey, array $newItems)
    {
        if (array_key_exists('invertStateDisplay', $item)) {
            $newItems[$itemKey]['invertStateDisplay'] = (bool)$item['invertStateDisplay'];
        } else {
            $newItems[$itemKey]['invertStateDisplay'] = false;
        }
        return $newItems;
    }

    /**
     * @param array $item
     * @param int $itemKey
     * @param array $newItems
     * @return array
     */
    private function sanitizeLabeledToggleCheckbox(array $item, int $itemKey, array $newItems)
    {
        if (array_key_exists('labelChecked', $item)) {
            $newItems[$itemKey]['labelChecked'] = $this->getLanguageService()->sL($item['labelChecked']);
        }
        if (array_key_exists('labelUnchecked', $item)) {
            $newItems[$itemKey]['labelUnchecked'] = $this->getLanguageService()->sL($item['labelUnchecked']);
        }
        if (array_key_exists('invertStateDisplay', $item)) {
            $newItems[$itemKey]['invertStateDisplay'] = (bool)$item['invertStateDisplay'];
        } else {
            $newItems[$itemKey]['invertStateDisplay'] = false;
        }
        return $newItems;
    }

    /**
     * @param array $item
     * @param int $itemKey
     * @param array $newItems
     * @return array
     */
    private function sanitizeIconToggleCheckbox(array $item, int $itemKey, array $newItems)
    {
        if (array_key_exists('iconIdentifierChecked', $item)) {
            $newItems[$itemKey]['iconIdentifierChecked'] = $item['iconIdentifierChecked'];
        }
        if (array_key_exists('iconIdentifierUnchecked', $item)) {
            $newItems[$itemKey]['iconIdentifierUnchecked'] = $item['iconIdentifierUnchecked'];
        }
        if (array_key_exists('invertStateDisplay', $item)) {
            $newItems[$itemKey]['invertStateDisplay'] = (bool)$item['invertStateDisplay'];
        } else {
            $newItems[$itemKey]['invertStateDisplay'] = false;
        }
        return $newItems;
    }
}
