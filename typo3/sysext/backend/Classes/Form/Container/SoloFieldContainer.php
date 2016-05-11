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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * An entry container to render just a single field.
 *
 * The container operates on $this->globalOptions['singleFieldToRender'] to render
 * this field. It initializes language stuff and prepares data in globalOptions for
 * processing of the single field in SingleFieldContainer.
 *
 * @todo: It should be possible to merge this container to ListOfFieldsContainer
 */
class SoloFieldContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function render()
    {
        GeneralUtility::logDeprecatedFunction();
        $table = $this->data['tableName'];
        $fieldToRender = $this->data['singleFieldToRender'];
        $recordTypeValue = $this->data['recordTypeValue'];
        $resultArray = $this->initializeResultArray();

        // Load the description content for the table if requested
        if ($GLOBALS['TCA'][$table]['interface']['always_description']) {
            $languageService = $this->getLanguageService();
            $languageService->loadSingleTableDescription($table);
        }

        $itemList = $this->data['processedTca']['types'][$recordTypeValue]['showitem'];
        $fields = GeneralUtility::trimExplode(',', $itemList, true);
        foreach ($fields as $fieldString) {
            $fieldConfiguration = $this->explodeSingleFieldShowItemConfiguration($fieldString);
            $fieldName = $fieldConfiguration['fieldName'];
            if ((string)$fieldName === (string)$fieldToRender) {
                // Field is in showitem configuration
                // @todo: This field is not rendered if it is "hidden" in a palette!
                if ($GLOBALS['TCA'][$table]['columns'][$fieldName]) {
                    $options = $this->data;
                    $options['fieldName'] = $fieldName;
                    $options['renderType'] = 'singleFieldContainer';
                    $resultArray = $this->nodeFactory->create($options)->render();
                }
            }
        }

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
