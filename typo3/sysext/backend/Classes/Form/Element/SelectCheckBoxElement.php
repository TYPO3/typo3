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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;

/**
 * Creates a widget with check box elements.
 *
 * This is rendered for config type=select, renderMode=checkbox, maxitems > 1
 */
class SelectCheckBoxElement extends AbstractFormElement {

	/**
	 * @var array Result array given returned by render() - This property is a helper until class is properly refactored
	 */
	protected $resultArray = array();

	/**
	 * Render check boxes
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$field = $this->globalOptions['fieldName'];
		$row = $this->globalOptions['databaseRow'];
		$parameterArray = $this->globalOptions['parameterArray'];
		// Field configuration from TCA:
		$config = $parameterArray['fieldConf']['config'];
		$disabled = '';
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		$this->resultArray = $this->initializeResultArray();
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist.
		$specConf = BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras']);
		$selItems = FormEngineUtility::getSelectItems($table, $field, $row, $parameterArray);

		// Creating the label for the "No Matching Value" entry.
		$noMatchingLabel = isset($parameterArray['fieldTSConfig']['noMatchingValue_label'])
			? $this->getLanguageService()->sL($parameterArray['fieldTSConfig']['noMatchingValue_label'])
			: '[ ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue') . ' ]';

		$html = $this->getSingleField_typeSelect_checkbox($table, $field, $row, $parameterArray, $config, $selItems, $noMatchingLabel);

		// Wizards:
		if (!$disabled) {
			$html = $this->renderWizards(array($html), $config['wizards'], $table, $row, $field, $parameterArray, $parameterArray['itemFormElName'], $specConf);
		}
		$this->resultArray['html'] = $html;
		return $this->resultArray;
	}

	/**
	 * Creates a checkbox list (renderMode = "checkbox")
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $parameterArray See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $noMatchingLabel Label for no-matching-value
	 * @return string The HTML code for the item
	 */
	protected function getSingleField_typeSelect_checkbox($table, $field, $row, $parameterArray, $config, $selItems, $noMatchingLabel) {
		if (empty($selItems)) {
			return '';
		}
		// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip(FormEngineUtility::extractValuesOnlyFromValueLabelList($parameterArray['itemFormElValue']));
		$output = '';

		// Disabled
		$disabled = 0;
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = 1;
		}
		// Traverse the Array of selector box items:
		$groups = array();
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
					$groups[$currentGroup]['header'] = array(
						'icon' => $selIcon,
						'title' => htmlspecialchars($p[0])
					);
				} else {

					// Check if some help text is available
					// Since TYPO3 4.5 help text is expected to be an associative array
					// with two key, "title" and "description"
					// For the sake of backwards compatibility, we test if the help text
					// is a string and use it as a description (this could happen if items
					// are modified with an itemProcFunc)
					$hasHelp = FALSE;
					$help = '';
					$helpArray = array();
					if (!empty($p[3])) {
						$hasHelp = TRUE;
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
					$groups[$currentGroup]['items'][] = array(
						'id' => str_replace('.', '', uniqid('select_checkbox_row_', TRUE)),
						'name' => $parameterArray['itemFormElName'] . '[' . $c . ']',
						'value' => $p[1],
						'checked' => $checked,
						'disabled' => $disabled,
						'class' => '',
						'icon' => (!empty($p[2]) ? FormEngineUtility::getIconHtml($p[2]) : IconUtility::getSpriteIcon('empty-empty')),
						'title' => htmlspecialchars($p[0], ENT_COMPAT, 'UTF-8', FALSE),
						'help' => $help
					);
					$c++;
				}
			}
		}
		// Remaining values (invalid):
		if (!empty($itemArray) && !$parameterArray['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			$currentGroup++;
			foreach ($itemArray as $theNoMatchValue => $temp) {
				// Build item array
				$groups[$currentGroup]['items'][] = array(
					'id' => str_replace('.', '', uniqid('select_checkbox_row_', TRUE)),
					'name' => $parameterArray['itemFormElName'] . '[' . $c . ']',
					'value' => $theNoMatchValue,
					'checked' => 1,
					'disabled' => $disabled,
					'class' => 'danger',
					'icon' => '',
					'title' => htmlspecialchars(@sprintf($noMatchingLabel, $theNoMatchValue), ENT_COMPAT, 'UTF-8', FALSE),
					'help' => ''
				);
				$c++;
			}
		}
		// Add an empty hidden field which will send a blank value if all items are unselected.
		$output .= '<input type="hidden" class="select-checkbox" name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" value="" />';

		// Building the checkboxes
		foreach ($groups as $groupKey => $group) {
			$groupId = htmlspecialchars($parameterArray['itemFormElID']) . '-group-' . $groupKey;
			$output .= '<div class="panel panel-default">';
			if (is_array($group['header'])) {
				$output .= '
					<div class="panel-heading">
						<a data-toggle="collapse" href="#' . $groupId . '" aria-expanded="true" aria-controls="' . $groupId . '">
							' . $group['header']['icon'] . '
							' . $group['header']['title'] . '
						</a>
					</div>
					';
			}
			if (is_array($group['items']) && !empty($group['items'])) {
				$tableRows = '';
				$checkGroup = array();
				$uncheckGroup = array();
				$resetGroup = array();

				// Render rows
				foreach ($group['items'] as $item) {
					$tableRows .= '
						<tr class="' . $item['class'] . '">
							<td class="col-checkbox">
								<input type="checkbox"
									id="' . $item['id'] . '"
									name="' . htmlspecialchars($item['name']) . '"
									value="' . htmlspecialchars($item['value']) . '"
									onclick="' . htmlspecialchars($sOnChange) . '"
									' . ($item['checked'] ? ' checked=checked' : '') . '
									' . ($item['disabled'] ? ' disabled=disabled' : '') . '
									' . $parameterArray['onFocus'] . ' />
							</td>
							<td class="col-icon">
								<label class="label-block" for="' . $item['id'] . '">' . $item['icon'] . '</label>
							</td>
							<td class="col-title">
								<label class="label-block" for="' . $item['id'] . '">' . $item['title'] . '</label>
							</td>
							<td>' . $item['help'] . '</td>
						</tr>
						';
					$checkGroup[] = 'document.editform[' . GeneralUtility::quoteJSvalue($item['name']) . '].checked=1;';
					$uncheckGroup[] = 'document.editform[' . GeneralUtility::quoteJSvalue($item['name']) . '].checked=0;';
					$resetGroup[] = 'document.editform[' . GeneralUtility::quoteJSvalue($item['name']) . '].checked='.$item['checked'] . ';';
				}

				// Build toggle group checkbox
				$toggleGroupCheckbox = '';
				if (!empty($resetGroup)) {
					$toggleGroupCheckbox = '
						<input type="checkbox" class="checkbox" onclick="if (checked) {' . htmlspecialchars(implode('', $checkGroup) . '} else {' . implode('', $uncheckGroup)) . '}">
						';
				}

				// Build reset group button
				$resetGroupBtn = '';
				if (!empty($resetGroup)) {
					$title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.revertSelection', TRUE);
					$resetGroupBtn = '
						<a href="#" class="btn btn-default" onclick="' . implode('', $resetGroup) . ' return false;'
						. '" title="' . $title . '">
							' . $this->iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL) . '
							' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.revertSelection') . '
						</a>
						';
				}

				$output .= '
					<div id="' . $groupId . '" class="panel-collapse collapse in" role="tabpanel">
						<div class="table-fit">
							<table class="table table-transparent table-hover">
								<thead>
									<tr>
										<th class="col-checkbox">' . $toggleGroupCheckbox . '</th>
										<th class="col-icon"></th>
										<th class="text-right" colspan="2">' . $resetGroupBtn . '</th>
									</tr>
								</thead>
								<tbody>' . $tableRows . '</tbody>
							</table>
						</div>
					</div>
					';
			}
			$output .= '</div>';
		}

		return $output;
	}

}
