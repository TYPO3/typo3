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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Works on processedTca to determine the final value of field labels.
 *
 * processedTca['columns]['aField']['label']
 */
class TcaColumnsProcessFieldLabels implements FormDataProviderInterface
{
    /**
     * Iterate over all processedTca columns fields
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    public function addData(array $result)
    {
        $result = $this->setLabelFromShowitemAndPalettes($result);
        $result = $this->setLabelFromPageTsConfig($result);
        $result = $this->translateLabels($result);
        return $result;
    }

    /**
     * The label of a single field can be set in the showitem configuration
     * of the record type and as palettes showitem as second ";" separated argument:
     *
     * processedTca['types']['aType']['showitem'] = 'aFieldName;aLabelOverride, --palette--;;aPaletteName'
     * processedTca['palettes']['aPaletteName']['showitem'] = 'anotherFieldName;anotherLabelOverride'
     *
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    protected function setLabelFromShowitemAndPalettes(array $result)
    {
        $recordTypeValue = $result['recordTypeValue'];
        // flex forms don't have a showitem / palettes configuration - early return
        if (!isset($result['processedTca']['types'][$recordTypeValue]['showitem'])) {
            return $result;
        }
        $showItemArray = GeneralUtility::trimExplode(',', $result['processedTca']['types'][$recordTypeValue]['showitem']);
        foreach ($showItemArray as $aShowItemFieldString) {
            $aShowItemFieldArray = GeneralUtility::trimExplode(';', $aShowItemFieldString);
            $aShowItemFieldArray = [
                'fieldName' => $aShowItemFieldArray[0],
                'fieldLabel' => $aShowItemFieldArray[1] ?: null,
                'paletteName' => $aShowItemFieldArray[2] ?: null,
            ];
            if ($aShowItemFieldArray['fieldName'] === '--div--') {
                // tabs are not of interest here
                continue;
            }
            if ($aShowItemFieldArray['fieldName'] === '--palette--') {
                // showitem references to a palette field. unpack the palette and process
                // label overrides that may be in there.
                if (!isset($result['processedTca']['palettes'][$aShowItemFieldArray['paletteName']]['showitem'])) {
                    // No palette with this name found? Skip it.
                    continue;
                }
                $palettesArray = GeneralUtility::trimExplode(
                    ',',
                    $result['processedTca']['palettes'][$aShowItemFieldArray['paletteName']]['showitem']
                );
                foreach ($palettesArray as $aPalettesString) {
                    $aPalettesArray = GeneralUtility::trimExplode(';', $aPalettesString);
                    $aPalettesArray = [
                        'fieldName' => $aPalettesArray[0],
                        'fieldLabel' => $aPalettesArray[1] ?: null,
                    ];
                    if (!empty($aPalettesArray['fieldLabel'])
                        && isset($result['processedTca']['columns'][$aPalettesArray['fieldName']])
                    ) {
                        $result['processedTca']['columns'][$aPalettesArray['fieldName']]['label'] = $aPalettesArray['fieldLabel'];
                    }
                }
            } else {
                // If the field has a label in the showitem configuration of this record type, use it.
                // showitem = 'aField, aFieldWithLabelOverride;theLabel, anotherField'
                if (!empty($aShowItemFieldArray['fieldLabel'])
                    && isset($result['processedTca']['columns'][$aShowItemFieldArray['fieldName']])
                ) {
                    $result['processedTca']['columns'][$aShowItemFieldArray['fieldName']]['label'] = $aShowItemFieldArray['fieldLabel'];
                }
            }
        }
        return $result;
    }

    /**
     * pageTsConfig can override labels:
     *
     * TCEFORM.aTable.aField.label = 'override'
     * TCEFORM.aTable.aField.label.en = 'override'
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    protected function setLabelFromPageTsConfig(array $result)
    {
        $languageService = $this->getLanguageService();
        $table = $result['tableName'];
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfiguration) {
            $fieldTSConfig = [];
            if (isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'])
                && is_array($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'])
            ) {
                $fieldTSConfig = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.'];
            }
            if (!empty($fieldTSConfig['label'])) {
                $result['processedTca']['columns'][$fieldName]['label'] = $fieldTSConfig['label'];
            }
            if (!empty($fieldTSConfig['label.'][$languageService->lang])) {
                $result['processedTca']['columns'][$fieldName]['label'] = $fieldTSConfig['label.'][$languageService->lang];
            }
        }
        return $result;
    }

    /**
     * Translate all labels if needed.
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    protected function translateLabels(array $result)
    {
        $languageService = $this->getLanguageService();
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfiguration) {
            if (!isset($fieldConfiguration['label'])) {
                continue;
            }
            $result['processedTca']['columns'][$fieldName]['label'] = $languageService->sL($fieldConfiguration['label']);
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
