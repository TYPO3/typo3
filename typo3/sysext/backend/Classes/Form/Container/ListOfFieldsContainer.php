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

namespace TYPO3\CMS\Backend\Form\Container;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render a given list of field of a TCA table.
 *
 * This is an entry container called from FormEngine to handle a
 * list of specific fields. Access rights are checked here and globalOption array
 * is prepared for further processing of single fields by PaletteAndSingleContainer.
 *
 * Using "hiddenFieldListToRender" it's also possible to render additional fields as
 * hidden fields, which is e.g. used for the "generatorFields" of TCA type "slug".
 */
class ListOfFieldsContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $options = $this->data;
        $options['fieldsArray'] = $this->sanitizeFieldList($this->data['fieldListToRender']);

        if ($this->data['hiddenFieldListToRender'] ?? false) {
            $hiddenFieldList = array_diff(
                $this->sanitizeFieldList($this->data['hiddenFieldListToRender']),
                $options['fieldsArray']
            );
            if ($hiddenFieldList !== []) {
                $hiddenFieldList = implode(',', $hiddenFieldList);
                $hiddenPaletteName = 'hiddenFieldsPalette' . md5($hiddenFieldList);
                $options['processedTca']['palettes'][$hiddenPaletteName] = [
                    'isHiddenPalette' => true,
                    'showitem' => $hiddenFieldList,
                ];
                $options['fieldsArray'][] = '--palette--;;' . $hiddenPaletteName;
            }
        }

        $options['renderType'] = 'paletteAndSingleContainer';
        return $this->nodeFactory->create($options)->render();
    }

    protected function sanitizeFieldList(string $fieldList): array
    {
        $fields = array_unique(GeneralUtility::trimExplode(',', $fieldList, true));
        $fieldsByShowitem = $this->data['processedTca']['types'][$this->data['recordTypeValue']]['showitem'];
        $fieldsByShowitem = GeneralUtility::trimExplode(',', $fieldsByShowitem, true);

        $allowedFields = [];
        foreach ($fields as $fieldName) {
            foreach ($fieldsByShowitem as $fieldByShowitem) {
                $fieldByShowitemArray = $this->explodeSingleFieldShowItemConfiguration($fieldByShowitem);
                if ($fieldByShowitemArray['fieldName'] === $fieldName) {
                    $allowedFields[] = implode(';', $fieldByShowitemArray);
                    break;
                }
                if ($fieldByShowitemArray['fieldName'] === '--palette--'
                    && isset($this->data['processedTca']['palettes'][$fieldByShowitemArray['paletteName']]['showitem'])
                    && is_string($this->data['processedTca']['palettes'][$fieldByShowitemArray['paletteName']]['showitem'])
                ) {
                    $paletteName = $fieldByShowitemArray['paletteName'];
                    $paletteFields = GeneralUtility::trimExplode(',', $this->data['processedTca']['palettes'][$paletteName]['showitem'], true);
                    foreach ($paletteFields as $paletteField) {
                        $paletteFieldArray = $this->explodeSingleFieldShowItemConfiguration($paletteField);
                        if ($paletteFieldArray['fieldName'] === $fieldName) {
                            $allowedFields[] = implode(';', $paletteFieldArray);
                            break;
                        }
                    }
                }
            }
        }
        return $allowedFields;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
