<?php
namespace TYPO3\CMS\Backend\Form\Container;

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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render all tabs of a record that has tabs.
 *
 * This container is called from FullRecordContainer and resolves the --div-- structure,
 * operates on given fieldArrays and calls a PaletteAndSingleContainer for each single tab.
 */
class TabsContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @throws \RuntimeException
     */
    public function render()
    {
        $languageService = $this->getLanguageService();

        // All the fields to handle in a flat list
        $fieldsArray = $this->data['fieldsArray'];

        // Create a nested array from flat fieldArray list
        $tabsArray = [];
        // First element will be a --div--, so it is safe to start -1 here to trigger 0 as first array index
        $currentTabIndex = -1;
        foreach ($fieldsArray as $fieldString) {
            $fieldArray = $this->explodeSingleFieldShowItemConfiguration($fieldString);
            if ($fieldArray['fieldName'] === '--div--') {
                $currentTabIndex++;
                if (empty($fieldArray['fieldLabel'])) {
                    throw new \RuntimeException(
                        'A --div-- has no label (--div--;fieldLabel) in showitem of ' . implode(',', $fieldsArray),
                        1426454001
                    );
                }
                $tabsArray[$currentTabIndex] = [
                    'label' => $languageService->sL($fieldArray['fieldLabel']),
                    'elements' => [],
                ];
            } else {
                $tabsArray[$currentTabIndex]['elements'][] = $fieldArray;
            }
        }

        $resultArray = $this->initializeResultArray();
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Backend/Tabs';

        $domIdPrefix = 'DTM-' . GeneralUtility::shortMD5($this->data['tableName'] . $this->data['databaseRow']['uid']);
        $tabCounter = 0;
        $tabElements = [];
        foreach ($tabsArray as $tabWithLabelAndElements) {
            $tabCounter++;
            $elements = $tabWithLabelAndElements['elements'];

            // Merge elements of this tab into a single list again and hand over to
            // palette and single field container to render this group
            $options = $this->data;
            $options['tabAndInlineStack'][] = [
                'tab',
                $domIdPrefix . '-' . $tabCounter,
            ];
            $options['fieldsArray'] = [];
            foreach ($elements as $element) {
                $options['fieldsArray'][] = implode(';', $element);
            }
            $options['renderType'] = 'paletteAndSingleContainer';
            $childArray = $this->nodeFactory->create($options)->render();

            if ($childArray['html'] !== '') {
                $tabElements[] = [
                    'label' => $tabWithLabelAndElements['label'],
                    'content' => $childArray['html'],
                ];
            }
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childArray, false);
        }

        $resultArray['html'] = $this->renderTabMenu($tabElements, $domIdPrefix);
        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
