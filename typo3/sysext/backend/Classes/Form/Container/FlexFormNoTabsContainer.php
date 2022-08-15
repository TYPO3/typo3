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

/**
 * Handle a flex form that has no tabs.
 *
 * This container is called by FlexFormEntryContainer if only a default sheet
 * exists. It evaluates the display condition and hands over rendering of single
 * fields to FlexFormElementContainer.
 */
class FlexFormNoTabsContainer extends AbstractContainer
{
    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $parameterArray = $this->data['parameterArray'];
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
        $flexFormRowData = $this->data['flexFormRowData'];
        $resultArray = $this->initializeResultArray();

        // Determine this single sheet name, most often it ends up with sDEF, except if only one sheet was defined
        $flexFormSheetNames = array_keys($flexFormDataStructureArray['sheets']);
        $sheetName = array_pop($flexFormSheetNames);
        $flexFormRowDataSubPart = $flexFormRowData['data'][$sheetName]['lDEF'] ?? [];

        unset($flexFormDataStructureArray['meta']);

        if (!is_array($flexFormDataStructureArray['sheets'][$sheetName]['ROOT']['el'])) {
            $resultArray['html'] = 'Data Structure ERROR: No [\'ROOT\'][\'el\'] element found in flex form definition.';
            return $resultArray;
        }

        $options = $this->data;
        $options['flexFormDataStructureArray'] = $flexFormDataStructureArray['sheets'][$sheetName]['ROOT']['el'];
        $options['flexFormRowData'] = $flexFormRowDataSubPart;
        $options['flexFormSheetName'] = $sheetName;
        $options['flexFormFormPrefix'] = '[data][' . $sheetName . '][lDEF]';
        $options['parameterArray'] = $parameterArray;

        $resultArray = $this->initializeResultArray();

        $fieldInformationResult = $this->renderFieldInformation();
        $resultArray['html'] = '<div>' . $fieldInformationResult['html'] . '</div>';
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $options['renderType'] = 'flexFormElementContainer';
        $childResult = $this->nodeFactory->create($options)->render();
        return $this->mergeChildReturnIntoExistingResult($resultArray, $childResult, true);
    }
}
