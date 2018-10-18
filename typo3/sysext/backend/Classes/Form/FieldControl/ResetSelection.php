<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\FieldControl;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * "Reset selection to previous selected items" icon,
 * typically used by type=select with renderType=selectSingleBox
 */
class ResetSelection extends AbstractNode
{
    /**
     * Add button control
     *
     * @return array As defined by FieldControl class
     */
    public function render()
    {
        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];

        $selectItems = $parameterArray['fieldConf']['config']['items'];
        $itemArray = array_flip($parameterArray['itemFormElValue']);
        $initiallySelectedIndices = [];
        foreach ($selectItems as $i => $item) {
            $value = $item[1];
            // Selected or not by default
            if (isset($itemArray[$value])) {
                $initiallySelectedIndices[] = $i;
            }
        }

        $id = StringUtility::getUniqueId('t3js-formengine-fieldcontrol-');

        return [
            'iconIdentifier' => 'actions-edit-undo',
            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.revertSelection',
            'linkAttributes' => [
                'id' => htmlspecialchars($id),
                'data-item-name' => htmlspecialchars($itemName),
                'data-selected-indices' => json_encode($initiallySelectedIndices),
            ],
            'requireJsModules' => [
                ['TYPO3/CMS/Backend/FormEngine/FieldControl/ResetSelection' => 'function(FieldControl) {new FieldControl(' . GeneralUtility::quoteJSvalue('#' . $id) . ');}'],
            ],
        ];
    }
}
