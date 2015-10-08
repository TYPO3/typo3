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

use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Template\DocumentTemplate;

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
        $docTemplate = $this->getDocumentTemplate();

        // All the fields to handle in a flat list
        $fieldsArray = $this->data['fieldsArray'];

        // Create a nested array from flat fieldArray list
        $tabsArray = array();
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
                $tabsArray[$currentTabIndex] = array(
                    'label' => $languageService->sL($fieldArray['fieldLabel']),
                    'elements' => array(),
                );
            } else {
                $tabsArray[$currentTabIndex]['elements'][] = $fieldArray;
            }
        }

        // Iterate over the tabs and compile content in $tabsContent array together with label
        $tabsContent = array();
        $resultArray = $this->initializeResultArray();

        $tabId = 'TCEforms:' . $this->data['tableName'] . ':' . $this->data['databaseRow']['uid'];
        // @todo: This duplicates parts of the docTemplate code
        $tabIdString = $docTemplate->getDynTabMenuId($tabId);

        $tabCounter = 0;
        foreach ($tabsArray as $tabWithLabelAndElements) {
            $tabCounter ++;
            $elements = $tabWithLabelAndElements['elements'];

            // Merge elements of this tab into a single list again and hand over to
            // palette and single field container to render this group
            $options = $this->data;
            $options['tabAndInlineStack'][] = array(
                'tab',
                $tabIdString . '-' . $tabCounter,
            );
            $options['fieldsArray'] = array();
            foreach ($elements as $element) {
                $options['fieldsArray'][] = implode(';', $element);
            }
            $options['renderType'] = 'paletteAndSingleContainer';
            $childArray = $this->nodeFactory->create($options)->render();

            $tabsContent[] = array(
                'label' => $tabWithLabelAndElements['label'],
                'content' => $childArray['html'],
            );
            $childArray['html'] = '';
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childArray);
        }

        // Feed everything to document template for tab rendering
        $resultArray['html'] = $docTemplate->getDynamicTabMenu($tabsContent, $tabId, 1, false, false);
        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @throws \RuntimeException
     * @return DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        $docTemplate = $GLOBALS['TBE_TEMPLATE'];
        if (!is_object($docTemplate)) {
            throw new \RuntimeException('No instance of DocumentTemplate found', 1426459735);
        }
        return $docTemplate;
    }
}
