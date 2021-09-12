<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Form\FieldControl;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Renders the icon with link parameters to edit a selected element,
 * typically used for single elements of type=group or type=select.
 */
class EditPopup extends AbstractNode
{
    use OnFieldChangeTrait;

    /**
     * Edit popup control
     *
     * @return array As defined by FieldControl class
     */
    public function render()
    {
        $options = $this->data['renderData']['fieldControlOptions'];

        $title = $options['title'] ?? 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.edit';

        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];
        $windowOpenParameters = $options['windowOpenParameters'] ?? 'height=800,width=600,status=0,menubar=0,scrollbars=1';

        $flexFormDataStructureIdentifier = $this->data['flexFormDataStructureIdentifier'] ?? '';
        $flexFormDataStructurePath = '';
        if (!empty($flexFormDataStructureIdentifier)) {
            if (empty($this->data['flexFormContainerName'])) {
                // simple flex form element
                $flexFormDataStructurePath = 'sheets/'
                    . $this->data['flexFormSheetName']
                    . '/ROOT/el/'
                    . $this->data['flexFormFieldName']
                    . '/TCEforms/config';
            } else {
                // flex form section container element
                $flexFormDataStructurePath = 'sheets/'
                    . $this->data['flexFormSheetName']
                    . '/ROOT/el/'
                    . $this->data['flexFormFieldName']
                    . '/el/'
                    . $this->data['flexFormContainerName']
                    . '/el/'
                    . $this->data['flexFormContainerFieldName']
                    . '/TCEforms/config';
            }
        }

        $urlParameters = array_merge(
            [
                'table' => $this->data['tableName'],
                'field' => $this->data['fieldName'],
                'formName' => 'editform',
                'flexFormDataStructureIdentifier' => $flexFormDataStructureIdentifier,
                'flexFormDataStructurePath' => $flexFormDataStructurePath,
                'hmac' => GeneralUtility::hmac('editform' . $itemName, 'wizard_js'),
            ],
            $this->forwardOnFieldChangeQueryParams($parameterArray['fieldChangeFunc'] ?? [])
        );
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $url = (string)$uriBuilder->buildUriFromRoute('wizard_edit', ['P' => $urlParameters]);
        $id = StringUtility::getUniqueId('t3js-formengine-fieldcontrol-');

        return [
            'iconIdentifier' => 'actions-open',
            'title' => $title,
            'linkAttributes' => [
                'id' => htmlspecialchars($id),
                'href' => $url,
                'data-element' => $itemName,
                'data-window-parameters' => $windowOpenParameters,
            ],
            'requireJsModules' => [
                JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/FormEngine/FieldControl/EditPopup')->instance('#' . $id),
            ],
        ];
    }
}
