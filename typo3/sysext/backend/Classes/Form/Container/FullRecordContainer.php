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
 * A container rendering a "full record". This is an entry container used as first
 * step into the rendering tree..
 *
 * This container determines the to be rendered fields depending on the record type,
 * initializes possible language base data, finds out if tabs should be rendered and
 * then calls either TabsContainer or a NoTabsContainer for further processing.
 */
class FullRecordContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $recordTypeValue = $this->data['recordTypeValue'];

        // List of items to be rendered
        $itemList = $this->data['processedTca']['types'][$recordTypeValue]['showitem'];

        $fieldsArray = GeneralUtility::trimExplode(',', $itemList, true);

        // Streamline the fields array
        // First, make sure there is always a --div-- definition for the first element
        if (!str_starts_with($fieldsArray[0], '--div--')) {
            array_unshift($fieldsArray, '--div--;LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.generalTab');
        }
        // If first tab has no label definition, add "general" label
        $firstTabHasLabel = count(GeneralUtility::trimExplode(';', $fieldsArray[0])) > 1;
        if (!$firstTabHasLabel) {
            $fieldsArray[0] = '--div--;LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.generalTab';
        }
        // If there are at least two --div-- definitions, inner container will be a TabContainer, else a NoTabContainer
        $tabCount = 0;
        foreach ($fieldsArray as $field) {
            if (str_starts_with($field, '--div--')) {
                $tabCount++;
            }
        }
        $hasTabs = true;
        if ($tabCount < 2) {
            // Remove first tab definition again if there is only one tab defined
            array_shift($fieldsArray);
            $hasTabs = false;
        }

        $data = $this->data;
        $data['fieldsArray'] = $fieldsArray;
        if ($hasTabs) {
            $data['renderType'] = 'tabsContainer';
        } else {
            $data['renderType'] = 'noTabsContainer';
        }

        return $this->nodeFactory->create($data)->render();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
