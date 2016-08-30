<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Creates a widget with check box elements.
 *
 * This is rendered for config type=select, renderType=selectCheckBox, maxitems > 1
 */
class SelectCheckBoxElement extends AbstractFormElement
{
    /**
     * Render check boxes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $html = [];
        // Field configuration from TCA:
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $disabled = !empty($config['readOnly']);

        $selItems = $config['items'];
        if (!empty($selItems)) {
            // Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
            $itemArray = array_flip($parameterArray['itemFormElValue']);

            // Traverse the Array of selector box items:
            $groups = [];
            $currentGroup = 0;
            $c = 0;
            $sOnChange = '';
            if (!$disabled) {
                $sOnChange = implode('', $parameterArray['fieldChangeFunc']);
                // Used to accumulate the JS needed to restore the original selection.
                foreach ($selItems as $p) {
                    // Non-selectable element:
                    if ($p[1] === '--div--') {
                        $selIcon = '';
                        if (isset($p[2]) && $p[2] != 'empty-empty') {
                            $selIcon = FormEngineUtility::getIconHtml($p[2]);
                        }
                        $currentGroup++;
                        $groups[$currentGroup]['header'] = [
                            'icon' => $selIcon,
                            'title' => $p[0]
                        ];
                    } else {
                        // Check if some help text is available
                        // Since TYPO3 4.5 help text is expected to be an associative array
                        // with two key, "title" and "description"
                        // For the sake of backwards compatibility, we test if the help text
                        // is a string and use it as a description (this could happen if items
                        // are modified with an itemProcFunc)
                        $hasHelp = false;
                        $help = '';
                        $helpArray = [];
                        if (!empty($p[3])) {
                            $hasHelp = true;
                            if (is_array($p[3])) {
                                $helpArray = $p[3];
                            } else {
                                $helpArray['description'] = $p[3];
                            }
                        }
                        if ($hasHelp) {
                            $help = BackendUtility::wrapInHelp('', '', '', $helpArray);
                        }

                        // Selected or not by default:
                        $checked = 0;
                        if (isset($itemArray[$p[1]])) {
                            $checked = 1;
                            unset($itemArray[$p[1]]);
                        }

                        // Build item array
                        $groups[$currentGroup]['items'][] = [
                            'id' => StringUtility::getUniqueId('select_checkbox_row_'),
                            'name' => $parameterArray['itemFormElName'] . '[' . $c . ']',
                            'value' => $p[1],
                            'checked' => $checked,
                            'disabled' => false,
                            'class' => '',
                            'icon' => (!empty($p[2]) ? FormEngineUtility::getIconHtml($p[2]) : $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render()),
                            'title' => $p[0],
                            'help' => $help
                        ];
                        $c++;
                    }
                }
            }
            // Add an empty hidden field which will send a blank value if all items are unselected.
            $html[] = '<input type="hidden" class="select-checkbox" name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" value="">';

            // Building the checkboxes
            foreach ($groups as $groupKey => $group) {
                $groupId = htmlspecialchars($parameterArray['itemFormElID']) . '-group-' . $groupKey;
                $html[] = '<div class="panel panel-default">';
                if (is_array($group['header'])) {
                    $html[] = '<div class="panel-heading">';
                    $html[] = '<a data-toggle="collapse" href="#' . $groupId . '" aria-expanded="false" aria-controls="' . $groupId . '">';
                    $html[] = $group['header']['icon'];
                    $html[] = htmlspecialchars($group['header']['title']);
                    $html[] = '</a>';
                    $html[] = '</div>';
                }
                if (is_array($group['items']) && !empty($group['items'])) {
                    $tableRows = [];
                    $resetGroup = [];

                    // Render rows
                    foreach ($group['items'] as $item) {
                        $tableRows[] = '<tr class="' . $item['class'] . '">';
                        $tableRows[] =    '<td class="col-checkbox">';
                        $tableRows[] =        '<input type="checkbox" class="t3js-checkbox" '
                                            . 'id="' . $item['id'] . '" '
                                            . 'name="' . htmlspecialchars($item['name']) . '" '
                                            . 'value="' . htmlspecialchars($item['value']) . '" '
                                            . 'onclick="' . htmlspecialchars($sOnChange) . '" '
                                            . ($item['checked'] ? 'checked=checked ' : '')
                                            . ($item['disabled'] ? 'disabled=disabled ' : '')
                                            . $parameterArray['onFocus'] . '>';
                        $tableRows[] =    '</td>';
                        $tableRows[] =    '<td class="col-icon">';
                        $tableRows[] =        '<label class="label-block" for="' . $item['id'] . '">' . $item['icon'] . '</label>';
                        $tableRows[] =    '</td>';
                        $tableRows[] =    '<td class="col-title">';
                        $tableRows[] =        '<label class="label-block" for="' . $item['id'] . '">' . htmlspecialchars($item['title'], ENT_COMPAT, 'UTF-8', false) . '</label>';
                        $tableRows[] =    '</td>';
                        $tableRows[] =    '<td>' . $item['help'] . '</td>';
                        $tableRows[] = '</tr>';
                        $resetGroup[] = 'document.editform[' . GeneralUtility::quoteJSvalue($item['name']) . '].checked=' . $item['checked'] . ';';
                    }

                    // Build reset group button
                    $resetGroupBtn = '';
                    if (!empty($resetGroup)) {
                        $resetGroup[] = 'TYPO3.FormEngine.updateCheckboxState(this);';
                        $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.revertSelection', true);
                        $resetGroupBtn = '<a href="#" '
                            . 'class="btn btn-default btn-sm" '
                            . 'onclick="' . implode('', $resetGroup) . ' return false;" '
                            . 'title="' . $title . '">'
                            . $this->iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL)->render() . ' '
                            . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.revertSelection') . '</a>';
                    }

                    if (is_array($group['header'])) {
                        $html[] = '<div id="' . $groupId . '" class="panel-collapse collapse" role="tabpanel">';
                    }
                    $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.toggleall');
                    $html[] =    '<div class="table-responsive">';
                    $html[] =        '<table class="table table-transparent table-hover">';
                    $html[] =            '<thead>';
                    $html[] =                '<tr>';
                    $html[] =                    '<th class="col-checkbox">';
                    $html[] =                       '<input type="checkbox" class="t3js-toggle-checkboxes" data-trigger="hover" data-placement="right" data-title="' . htmlspecialchars($title) . '" data-toggle="tooltip" />';
                    $html[] =                    '</th>';
                    $html[] =                    '<th class="col-icon"></th>';
                    $html[] =                    '<th class="text-right" colspan="2">' . $resetGroupBtn . '</th>';
                    $html[] =                '</tr>';
                    $html[] =            '</thead>';
                    $html[] =            '<tbody>' . implode(LF, $tableRows) . '</tbody>';
                    $html[] =        '</table>';
                    $html[] =    '</div>';
                    if (is_array($group['header'])) {
                        $html[] = '</div>';
                    }
                }
                $html[] = '</div>';
            }
        }

        if (!$disabled) {
            $html = $this->renderWizards(
                [implode(LF, $html)],
                $config['wizards'],
                $this->data['tableName'],
                $this->data['databaseRow'],
                $this->data['fieldName'],
                $parameterArray,
                $parameterArray['itemFormElName'],
                BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras'])
            );
        }

        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = $html;
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/Backend/Tooltip';

        return $resultArray;
    }
}
