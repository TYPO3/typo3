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

/**
 * Entry container to a flex form element. This container is created by
 * SingleFieldContainer if a type='flex' field is rendered.
 *
 * It either forks a FlexFormTabsContainer or a FlexFormNoTabsContainer.
 */
class FlexFormEntryContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $flexFormDataStructureArray = $this->data['parameterArray']['fieldConf']['config']['ds'];

        $options = $this->data;
        $options['flexFormDataStructureArray'] = $flexFormDataStructureArray;
        $options['flexFormRowData'] = $this->data['parameterArray']['itemFormElValue'];
        $options['renderType'] = 'flexFormNoTabsContainer';

        // Enable tabs if there is more than one sheet
        if (count($flexFormDataStructureArray['sheets']) > 1) {
            $options['renderType'] = 'flexFormTabsContainer';
        }

        $resultArray = $this->nodeFactory->create($options)->render();
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Backend/FormEngineFlexForm';

        return $resultArray;
    }
}
