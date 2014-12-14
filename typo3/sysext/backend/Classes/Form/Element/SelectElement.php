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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Generation of TCEform elements of the type "select"
 */
class SelectElement extends AbstractFormElement {

	/**
	 * If this value is set during traversal and the traversal chain can
	 * not be walked to the end this value will be returned instead.
	 *
	 * @var string
	 */
	protected $alternativeFieldValue;

	/**
	 * If this is TRUE the alternative field value will be used even if
	 * the detected field value is not empty.
	 *
	 * @var bool
	 */
	protected $forceAlternativeFieldValueUse = FALSE;

	/**
	 * The row data of the record that is currently traversed.
	 *
	 * @var array
	 */
	protected $currentRow;

	/**
	 * Name of the table that is currently traversed.
	 *
	 * @var string
	 */
	protected $currentTable;

	/**
	 * This will render a selector box element, or possibly a special construction with two selector boxes.
	 * That depends on configuration.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function render($table, $field, $row, &$additionalInformation) {
		// Field configuration from TCA:
		$config = $additionalInformation['fieldConf']['config'];
		$disabled = '';
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist.
		$specConf = $this->formEngine->getSpecConfFromString($additionalInformation['extra'], $additionalInformation['fieldConf']['defaultExtras']);
		$selItems = $this->getSelectItems($table, $field, $row, $additionalInformation);

		// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset($additionalInformation['fieldTSConfig']['noMatchingValue_label'])
			? $this->formEngine->sL($additionalInformation['fieldTSConfig']['noMatchingValue_label'])
			: '[ ' . $this->formEngine->getLL('l_noMatchingValue') . ' ]';
		// Prepare some values:
		$maxitems = (int)$config['maxitems'];
		// If a SINGLE selector box...
		if ($maxitems <= 1 && $config['renderMode'] !== 'tree') {
			$item = $this->getSingleField_typeSelect_single($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
		} elseif ($config['renderMode'] === 'checkbox') {
			// Checkbox renderMode
			$item = $this->getSingleField_typeSelect_checkbox($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
		} elseif ($config['renderMode'] === 'singlebox') {
			// Single selector box renderMode
			$item = $this->getSingleField_typeSelect_singlebox($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
		} elseif ($config['renderMode'] === 'tree') {
			// Tree renderMode
			$treeClass = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\Element\TreeElement::class, $this->formEngine);
			$item = $treeClass->renderField($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
			// Register the required number of elements
			$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
			$this->formEngine->registerRequiredProperty('range', $additionalInformation['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		} else {
			// Traditional multiple selector box:
			$item = $this->getSingleField_typeSelect_multiple($table, $field, $row, $additionalInformation, $config, $selItems, $nMV_label);
		}
		// Wizards:
		if (!$disabled) {
			$altItem = '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($additionalInformation['itemFormElValue']) . '" />';
			$item = $this->formEngine->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $additionalInformation, $additionalInformation['itemFormElName'], $specConf);
		}
		return $item;
	}

	/**
	 * Creates a multiple-selector box (two boxes, side-by-side)
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	public function getSingleField_typeSelect_multiple($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		$item = '';
		$disabled = '';
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Setting this hidden field (as a flag that JavaScript can read out)
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '" />';
		}
		// Set max and min items:
		$maxitems = MathUtility::forceIntegerInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		// Register the required number of elements:
		$this->formEngine->registerRequiredProperty('range', $PA['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		// Get "removeItems":
		$removeItems = GeneralUtility::trimExplode(',', $PA['fieldTSConfig']['removeItems'], TRUE);
		// Get the array with selected items:
		$itemArray = GeneralUtility::trimExplode(',', $PA['itemFormElValue'], TRUE);

		// Possibly filter some items:
		$itemArray = GeneralUtility::keepItemsInArray(
			$itemArray,
			$PA['fieldTSConfig']['keepItems'],
			function ($value) {
				$parts = explode('|', $value, 2);
				return rawurldecode($parts[0]);
			}
		);

		// Perform modification of the selected items array:
		foreach ($itemArray as $tk => $tv) {
			$tvP = explode('|', $tv, 2);
			$evalValue = $tvP[0];
			$isRemoved = in_array($evalValue, $removeItems)
				|| $config['form_type'] == 'select' && $config['authMode']
				&& !$this->getBackendUserAuthentication()->checkAuthMode($table, $field, $evalValue, $config['authMode']);
			if ($isRemoved && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
				$tvP[1] = rawurlencode(@sprintf($nMV_label, $evalValue));
			} else {
				if (isset($PA['fieldTSConfig']['altLabels.'][$evalValue])) {
					$tvP[1] = rawurlencode($this->formEngine->sL($PA['fieldTSConfig']['altLabels.'][$evalValue]));
				}
				if (isset($PA['fieldTSConfig']['altIcons.'][$evalValue])) {
					$tvP[2] = $PA['fieldTSConfig']['altIcons.'][$evalValue];
				}
			}
			if ($tvP[1] == '') {
				// Case: flexform, default values supplied, no label provided (bug #9795)
				foreach ($selItems as $selItem) {
					if ($selItem[1] == $tvP[0]) {
						$tvP[1] = html_entity_decode($selItem[0]);
						break;
					}
				}
			}
			$itemArray[$tk] = implode('|', $tvP);
		}
		$itemsToSelect = '';
		$filterTextfield = '';
		$filterSelectbox = '';
		$size = 0;
		if (!$disabled) {
			// Create option tags:
			$opt = array();
			$styleAttrValue = '';
			foreach ($selItems as $p) {
				if ($config['iconsInOptionTags']) {
					$styleAttrValue = $this->formEngine->optionTagStyle($p[2]);
				}
				$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"'
					. ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '')
					. ' title="' . $p[0] . '">' . $p[0] . '</option>';
			}
			// Put together the selector box:
			$selector_itemListStyle = isset($config['itemListStyle'])
				? ' style="' . htmlspecialchars($config['itemListStyle']) . '"'
				: ' style="' . $this->formEngine->defaultMultipleSelectorStyle . '"';
			$size = (int)$config['size'];
			$size = $config['autoSizeMax']
				? MathUtility::forceIntegerInRange(count($itemArray) + 1, MathUtility::forceIntegerInRange($size, 1), $config['autoSizeMax'])
				: $size;
			$sOnChange = implode('', $PA['fieldChangeFunc']);

			$multiSelectId = str_replace('.', '', uniqid('tceforms-multiselect-', TRUE));
			$itemsToSelect = '
				<select data-relatedfieldname="' . htmlspecialchars($PA['itemFormElName']) . '" data-exclusivevalues="'
				. htmlspecialchars($config['exclusiveKeys']) . '" id="' . $multiSelectId . '" name="' . $PA['itemFormElName'] . '_sel" '
				. ' class="' . $this->cssClassTypeElementPrefix . 'select tceforms-multiselect tceforms-itemstoselect t3-form-select-itemstoselect" '
				. ($size ? ' size="' . $size . '"' : '') . ' onchange="' . htmlspecialchars($sOnChange) . '"'
				. $PA['onFocus'] . $selector_itemListStyle . '>
					' . implode('
					', $opt) . '
				</select>';

			// enable filter functionality via a text field
			if ($config['enableMultiSelectFilterTextfield']) {
				$filterTextfield = '<span class="input-group"><span class="input-group-addon"><span class="fa fa-filter"></span></span><input class="t3-form-multiselect-filter-textfield form-control" value="" /></span>';
			}

			// enable filter functionality via a select
			if (isset($config['multiSelectFilterItems']) && is_array($config['multiSelectFilterItems']) && count($config['multiSelectFilterItems']) > 1) {
				$filterDropDownOptions = array();
				foreach ($config['multiSelectFilterItems'] as $optionElement) {
					$optionValue = $this->formEngine->sL(isset($optionElement[1]) && $optionElement[1] != '' ? $optionElement[1]
						: $optionElement[0]);
					$filterDropDownOptions[] = '<option value="' . htmlspecialchars($this->formEngine->sL($optionElement[0])) . '">'
						. htmlspecialchars($optionValue) . '</option>';
				}
				$filterSelectbox = '<select class="t3-form-multiselect-filter-dropdown form-control">
						' . implode('
						', $filterDropDownOptions) . '
					</select>';
			}
		}

		$selectBoxFilterContents = trim($filterSelectbox . $filterTextfield);
		if (!empty($selectBoxFilterContents)) {
			$selectBoxFilterContents = '<div class="form-inline"><div class="t3-form-multiselect-filter-container form-group-sm pull-right">' . $selectBoxFilterContents . '</div></div>';
		}

		// Pass to "dbFileIcons" function:
		$params = array(
			'size' => $size,
			'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
			'style' => isset($config['selectedListStyle'])
				? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
				: ' style="' . $this->formEngine->defaultMultipleSelectorStyle . '"',
			'dontShowMoveIcons' => $maxitems <= 1,
			'maxitems' => $maxitems,
			'info' => '',
			'headers' => array(
				'selector' => $this->formEngine->getLL('l_selected') . ':<br />',
				'items' => '<div class="pull-left">' . $this->formEngine->getLL('l_items') . ':</div>' . $selectBoxFilterContents
			),
			'noBrowser' => 1,
			'thumbnails' => $itemsToSelect,
			'readOnly' => $disabled
		);
		$item .= $this->formEngine->dbFileIcons($PA['itemFormElName'], '', '', $itemArray, '', $params, $PA['onFocus']);
		return $item;
	}

	/**
	 * Collects the items for a select field by reading the configured
	 * select items from the configuration and / or by collecting them
	 * from a foreign table.
	 *
	 * @param string $table The table name of the record
	 * @param string $fieldName The select field name
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return array
	 */
	protected function getSelectItems($table, $fieldName, array $row, array $PA) {
		$config = $PA['fieldConf']['config'];

		// Getting the selector box items from the system
		$selectItems = $this->formEngine->addSelectOptionsToItemArray(
			$this->formEngine->initItemArray($PA['fieldConf']),
			$PA['fieldConf'],
			$this->formEngine->setTSconfig($table, $row),
			$fieldName
		);

		// Possibly filter some items:
		$selectItems = GeneralUtility::keepItemsInArray(
			$selectItems,
			$PA['fieldTSConfig']['keepItems'],
			function ($value) {
				return $value[1];
			}
		);

		// Possibly add some items:
		$selectItems = $this->formEngine->addItems($selectItems, $PA['fieldTSConfig']['addItems.']);

		// Process items by a user function:
		if (isset($config['itemsProcFunc']) && $config['itemsProcFunc']) {
			$selectItems = $this->formEngine->procItems($selectItems, $PA['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $fieldName);
		}

		// Possibly remove some items:
		$removeItems = GeneralUtility::trimExplode(',', $PA['fieldTSConfig']['removeItems'], TRUE);
		foreach ($selectItems as $selectItemIndex => $selectItem) {

			// Checking languages and authMode:
			$languageDeny = FALSE;
			$beUserAuth = $this->getBackendUserAuthentication();
			if (
				!empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])
				&& $GLOBALS['TCA'][$table]['ctrl']['languageField'] === $fieldName
				&& !$beUserAuth->checkLanguageAccess($selectItem[1])
			) {
				$languageDeny = TRUE;
			}

			$authModeDeny = FALSE;
			if (
				($config['form_type'] === 'select')
				&& $config['authMode']
				&& !$beUserAuth->checkAuthMode($table, $fieldName, $selectItem[1], $config['authMode'])
			) {
				$authModeDeny = TRUE;
			}

			if (in_array($selectItem[1], $removeItems) || $languageDeny || $authModeDeny) {
				unset($selectItems[$selectItemIndex]);
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$selectItem[1]])) {
				$selectItems[$selectItemIndex][0] = htmlspecialchars($this->formEngine->sL($PA['fieldTSConfig']['altLabels.'][$selectItem[1]]));
			}

			// Removing doktypes with no access:
			if (($table === 'pages' || $table === 'pages_language_overlay') && $fieldName === 'doktype') {
				if (!($beUserAuth->isAdmin() || GeneralUtility::inList($beUserAuth->groupData['pagetypes_select'], $selectItem[1]))) {
					unset($selectItems[$selectItemIndex]);
				}
			}
		}

		return $selectItems;
	}

	/**
	 * Creates a single-selector box
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	public function getSingleField_typeSelect_single($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		// check against inline uniqueness
		$inlineParent = $this->formEngine->inline->getStructureLevel(-1);
		$uniqueIds = NULL;
		if (is_array($inlineParent) && $inlineParent['uid']) {
			if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
				$uniqueIds = $this->formEngine->inline->inlineData['unique'][$this->formEngine->inline->inlineNames['object']
				. InlineElement::Structure_Separator . $table]['used'];
				$PA['fieldChangeFunc']['inlineUnique'] = 'inline.updateUnique(this,\'' . $this->formEngine->inline->inlineNames['object']
					. InlineElement::Structure_Separator . $table . '\',\'' . $this->formEngine->inline->inlineNames['form']
					. '\',\'' . $row['uid'] . '\');';
			}
			// hide uid of parent record for symmetric relations
			if (
				$inlineParent['config']['foreign_table'] == $table
				&& ($inlineParent['config']['foreign_field'] == $field || $inlineParent['config']['symmetric_field'] == $field)
			) {
				$uniqueIds[] = $inlineParent['uid'];
			}
		}
		// Initialization:
		$c = 0;
		$sI = 0;
		$noMatchingValue = 1;
		$opt = array();
		$selicons = array();
		$onlySelectedIconShown = 0;
		$size = (int)$config['size'];
		// Style set on <select/>
		$selectedStyle = '';
		$item = '';
		$disabled = '';
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
			$onlySelectedIconShown = 1;
		}
		// Register as required if minitems is greater than zero
		if (($minItems = MathUtility::forceIntegerInRange($config['minitems'], 0)) > 0) {
			$this->formEngine->registerRequiredProperty('field', $table . '_' . $row['uid'] . '_' . $field, $PA['itemFormElName']);
		}

		// Icon configuration:
		if ($config['suppress_icons'] == 'IF_VALUE_FALSE') {
			$suppressIcons = !$PA['itemFormElValue'] ? 1 : 0;
		} elseif ($config['suppress_icons'] == 'ONLY_SELECTED') {
			$suppressIcons = 0;
			$onlySelectedIconShown = 1;
		} elseif ($config['suppress_icons']) {
			$suppressIcons = 1;
		} else {
			$suppressIcons = 0;
		}
		// Traverse the Array of selector box items:
		$optGroupStart = array();
		$optGroupOpen = FALSE;
		$classesForSelectTag = array();
		foreach ($selItems as $p) {
			$sM = (string)$PA['itemFormElValue'] === (string)$p[1] ? ' selected="selected"' : '';
			if ($sM) {
				$sI = $c;
				$noMatchingValue = 0;
			}
			// Getting style attribute value (for icons):
			$styleAttrValue = '';
			if ($config['iconsInOptionTags']) {
				$styleAttrValue = $this->formEngine->optionTagStyle($p[2]);
				if ($sM) {
					list($selectIconFile, $selectIconInfo) = $this->formEngine->getIcon($p[2]);
					if (!empty($selectIconInfo)) {
						$selectedStyle = ' style="background-image:url(' . $selectIconFile . ');"';
						$classesForSelectTag[] = 'typo3-TCEforms-select-selectedItemWithBackgroundImage';
					}
				}
			}
			// Compiling the <option> tag:
			if (!($p[1] != $PA['itemFormElValue'] && is_array($uniqueIds) && in_array($p[1], $uniqueIds))) {
				if ($p[1] === '--div--') {
					$optGroupStart[0] = $p[0];
					if ($config['iconsInOptionTags']) {
						$optGroupStart[1] = $this->formEngine->optgroupTagStyle($p[2]);
					} else {
						$optGroupStart[1] = $styleAttrValue;
					}
				} else {
					if (count($optGroupStart)) {
						// Closing last optgroup before next one starts
						if ($optGroupOpen) {
							$opt[] = '</optgroup>' . LF;
						}
						$opt[] = '<optgroup label="' . htmlspecialchars($optGroupStart[0], ENT_COMPAT, 'UTF-8', FALSE)
							. '"' . ($optGroupStart[1] ? ' style="' . htmlspecialchars($optGroupStart[1]) . '"' : '')
							. ' class="c-divider">' . LF;
						$optGroupOpen = TRUE;
						$c--;
						$optGroupStart = array();
					}
					$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' . $sM
						. ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '') . '>'
						. htmlspecialchars($p[0], ENT_COMPAT, 'UTF-8', FALSE) . '</option>' . LF;
				}
			}
			// If there is an icon for the selector box (rendered in selicon-table below)...:
			// if there is an icon ($p[2]), icons should be shown, and, if only selected are visible, is it selected
			if ($p[2] && !$suppressIcons && (!$onlySelectedIconShown || $sM)) {
				list($selIconFile, $selIconInfo) = $this->formEngine->getIcon($p[2]);
				$iOnClick = $this->formEngine->elName($PA['itemFormElName']) . '.selectedIndex=' . $c . '; ' . $this->formEngine->elName($PA['itemFormElName']);
				$iOnClickOptions = $this->formEngine->elName($PA['itemFormElName']) . '.options[' . $c . ']';
				if (empty($selIconInfo)) {
					$iOnClick .= '.className=' . $iOnClickOptions . '.className; ';
				} else {
					$iOnClick .= '.style.backgroundImage=' . $iOnClickOptions . '.style.backgroundImage; ';
				}
				$iOnClick .= implode('', $PA['fieldChangeFunc']) . 'this.blur(); return false;';
				$selicons[] = array(
					(!$onlySelectedIconShown ? '<a href="#" onclick="' . htmlspecialchars($iOnClick) . '">' : '')
					. $this->formEngine->getIconHtml($p[2], htmlspecialchars($p[0]), htmlspecialchars($p[0]))
					. (!$onlySelectedIconShown ? '</a>' : ''),
					$c,
					$sM
				);
			}
			$c++;
		}
		// Closing optgroup if open
		if ($optGroupOpen) {
			$opt[] = '</optgroup>';
		}
		// No-matching-value:
		if ($PA['itemFormElValue'] && $noMatchingValue && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			$nMV_label = @sprintf($nMV_label, $PA['itemFormElValue']);
			$opt[] = '<option value="' . htmlspecialchars($PA['itemFormElValue']) . '" selected="selected">' . htmlspecialchars($nMV_label) . '</option>';
		}
		// Create item form fields:
		$sOnChange = 'if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex=' . $sI . ';} ' . implode('', $PA['fieldChangeFunc']);
		if (!$disabled) {
			// MUST be inserted before the selector - else is the value of the hiddenfield here mysteriously submitted...
			$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_selIconVal" value="' . htmlspecialchars($sI) . '" />';
		}
		if ($config['iconsInOptionTags']) {
			$classesForSelectTag[] = 'icon-select';
		}
		$item .= '<select' . $selectedStyle . ' id="' . str_replace('.', '', uniqid('tceforms-select-', TRUE)) . '" name="' . $PA['itemFormElName'] . '" class="' . $this->cssClassTypeElementPrefix . 'select ' . implode(' ', $classesForSelectTag) . '"' . ($size ? ' size="' . $size . '"' : '') . ' onchange="' . htmlspecialchars($sOnChange) . '"' . $PA['onFocus'] . $disabled . '>';
		$item .= implode('', $opt);
		$item .= '</select>';
		// Create icon table:
		if (count($selicons) && !$config['noIconsBelowSelect']) {
			$item .= '<div class="typo3-TCEforms-selectIcons">';
			$selicon_cols = (int)$config['selicon_cols'];
			if (!$selicon_cols) {
				$selicon_cols = count($selicons);
			}
			$sR = ceil(count($selicons) / $selicon_cols);
			$selicons = array_pad($selicons, $sR * $selicon_cols, '');
			for ($sa = 0; $sa < $sR; $sa++) {
				$item .= '<div>';
				for ($sb = 0; $sb < $selicon_cols; $sb++) {
					$sk = $sa * $selicon_cols + $sb;
					$imgN = 'selIcon_' . $table . '_' . $row['uid'] . '_' . $field . '_' . $selicons[$sk][1];
					$imgS = $selicons[$sk][2] ? $this->formEngine->backPath . 'gfx/content_selected.gif' : 'clear.gif';
					$item .= '<span><img name="' . htmlspecialchars($imgN) . '" src="' . htmlspecialchars($imgS) . '" width="7" height="10" alt="" /></span>';
					$item .= '<span>' . $selicons[$sk][0] . '</span>';
				}
				$item .= '</div>';
			}
			$item .= '</div>';
		}
		return $item;
	}

	/**
	 * Creates a checkbox list (renderMode = "checkbox")
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	public function getSingleField_typeSelect_checkbox($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		if (empty($selItems)) {
			return '';
		}
		// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->formEngine->extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));
		$item = '';
		$disabled = '';
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Traverse the Array of selector box items:
		$tRows = array();
		$c = 0;
		$setAll = array();
		$unSetAll = array();
		$restoreCmd = array();
		$sOnChange = '';
		if (!$disabled) {
			$sOnChange = implode('', $PA['fieldChangeFunc']);
			// Used to accumulate the JS needed to restore the original selection.
			foreach ($selItems as $p) {
				// Non-selectable element:
				if ($p[1] === '--div--') {
					$selIcon = '';
					if (isset($p[2]) && $p[2] != 'empty-emtpy') {
						$selIcon = $this->formEngine->getIconHtml($p[2]);
					}
					$tRows[] = '
						<tr class="c-header">
							<td colspan="3">' . $selIcon . htmlspecialchars($p[0]) . '</td>
						</tr>';
				} else {
					// Selected or not by default:
					$sM = '';
					if (isset($itemArray[$p[1]])) {
						$sM = ' checked="checked"';
						unset($itemArray[$p[1]]);
					}
					// Icon:
					if (!empty($p[2])) {
						$selIcon = $this->formEngine->getIconHtml($p[2]);
					} else {
						$selIcon = IconUtility::getSpriteIcon('empty-empty');
					}
					// Compile row:
					$rowId = str_replace('.', '', uniqid('select_checkbox_row_', TRUE));
					$onClickCell = $this->formEngine->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked=!' . $this->formEngine->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked;';
					$onClick = 'this.attributes.getNamedItem("class").nodeValue = ' . $this->formEngine->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked ? "c-selectedItem" : "c-unselectedItem";';
					$setAll[] = $this->formEngine->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked=1;';
					$setAll[] = '$(\'' . $rowId . '\').removeClassName(\'c-unselectedItem\');$(\'' . $rowId . '\').addClassName(\'c-selectedItem\');';
					$unSetAll[] = $this->formEngine->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked=0;';
					$unSetAll[] = '$(\'' . $rowId . '\').removeClassName(\'c-selectedItem\');$(\'' . $rowId . '\').addClassName(\'c-unselectedItem\');';
					$restoreCmd[] = $this->formEngine->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked=' . ($sM ? 1 : 0) . ';' . '$(\'' . $rowId . '\').removeClassName(\'c-selectedItem\');$(\'' . $rowId . '\').removeClassName(\'c-unselectedItem\');' . '$(\'' . $rowId . '\').addClassName(\'c-' . ($sM ? '' : 'un') . 'selectedItem\');';
					// Check if some help text is available
					// Since TYPO3 4.5 help text is expected to be an associative array
					// with two key, "title" and "description"
					// For the sake of backwards compatibility, we test if the help text
					// is a string and use it as a description (this could happen if items
					// are modified with an itemProcFunc)
					$hasHelp = FALSE;
					$help = '';
					$helpArray = array();
					if (is_array($p[3]) && count($p[3]) > 0 || !empty($p[3])) {
						$hasHelp = TRUE;
						if (is_array($p[3])) {
							$helpArray = $p[3];
						} else {
							$helpArray['description'] = $p[3];
						}
					}
					$label = htmlspecialchars($p[0], ENT_COMPAT, 'UTF-8', FALSE);
					if ($hasHelp) {
						$help = BackendUtility::wrapInHelp('', '', '', $helpArray);
					}
					$tRows[] = '
						<tr id="' . $rowId . '" class="' . ($sM ? 'c-selectedItem' : 'c-unselectedItem')
						. '" onclick="' . htmlspecialchars($onClick) . '" style="cursor: pointer;">
							<td class="c-checkbox"><input type="checkbox" class="' . $this->cssClassTypeElementPrefix . 'check"'
						. ' name="' . htmlspecialchars(($PA['itemFormElName'] . '[' . $c . ']'))
						. '" value="' . htmlspecialchars($p[1]) . '"' . $sM . ' onclick="' . htmlspecialchars($sOnChange)
						. '"' . $PA['onFocus'] . ' /></td>
							<td class="c-labelCell" onclick="' . htmlspecialchars($onClickCell) . '">' . $selIcon . $label . '</td>
								<td class="c-descr" onclick="' . htmlspecialchars($onClickCell) . '">' . (empty($help) ? '' : $help) . '</td>
						</tr>';
					$c++;
				}
			}
		}
		// Remaining values (invalid):
		if (count($itemArray) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			foreach ($itemArray as $theNoMatchValue => $temp) {
				// Compile <checkboxes> tag:
				array_unshift($tRows, '
						<tr class="c-invalidItem">
							<td class="c-checkbox"><input type="checkbox" class="' . $this->cssClassTypeElementPrefix . 'check"'
					. ' name="' . htmlspecialchars(($PA['itemFormElName'] . '[' . $c . ']'))
					. '" value="' . htmlspecialchars($theNoMatchValue) . '" checked="checked" onclick="' . htmlspecialchars($sOnChange) . '"'
					. $PA['onFocus'] . $disabled . ' /></td>
							<td class="c-labelCell">' . htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue), ENT_COMPAT, 'UTF-8', FALSE) . '</td><td>&nbsp;</td>
						</tr>');
				$c++;
			}
		}
		// Add an empty hidden field which will send a blank value if all items are unselected.
		$item .= '<input type="hidden" class="select-checkbox" name="' . htmlspecialchars($PA['itemFormElName']) . '" value="" />';
		// Remaining checkboxes will get their set-all link:
		$tableHead = '';
		if (count($setAll)) {
			$tableHead = '<thead>
					<tr class="c-header-checkbox-controls t3-row-header">
						<td class="c-checkbox">
						<input type="checkbox" class="checkbox" onclick="if (checked) {' . htmlspecialchars(implode('', $setAll) . '} else {' . implode('', $unSetAll)) . '}">
						</td>
						<td colspan="2">
						</td>
					</tr></thead>';
		}
		// Implode rows in table:
		$item .= '
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-select-checkbox">' . $tableHead . '<tbody>' . implode('', $tRows) . '</tbody>
			</table>
			';
		// Add revert icon
		if (!empty($restoreCmd)) {
			$item .= '<a href="#" onclick="' . implode('', $restoreCmd) . ' return false;' . '">'
				. IconUtility::getSpriteIcon('actions-edit-undo', array('title' => htmlspecialchars($this->formEngine->getLL('l_revertSelection')))) . '</a>';
		}
		return $item;
	}

	/**
	 * Creates a selectorbox list (renderMode = "singlebox")
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	public function getSingleField_typeSelect_singlebox($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->formEngine->extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));
		$item = '';
		$disabled = '';
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Traverse the Array of selector box items:
		$opt = array();
		// Used to accumulate the JS needed to restore the original selection.
		$restoreCmd = array();
		$c = 0;
		foreach ($selItems as $p) {
			// Selected or not by default:
			$sM = '';
			if (isset($itemArray[$p[1]])) {
				$sM = ' selected="selected"';
				$restoreCmd[] = $this->formEngine->elName(($PA['itemFormElName'] . '[]')) . '.options[' . $c . '].selected=1;';
				unset($itemArray[$p[1]]);
			}
			// Non-selectable element:
			$nonSel = '';
			if ((string)$p[1] === '--div--') {
				$nonSel = ' onclick="this.selected=0;" class="c-divider"';
			}
			// Icon style for option tag:
			$styleAttrValue = '';
			if ($config['iconsInOptionTags']) {
				$styleAttrValue = $this->formEngine->optionTagStyle($p[2]);
			}
			// Compile <option> tag:
			$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' . $sM . $nonSel
				. ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '') . '>'
				. htmlspecialchars($p[0], ENT_COMPAT, 'UTF-8', FALSE) . '</option>';
			$c++;
		}
		// Remaining values:
		if (count($itemArray) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			foreach ($itemArray as $theNoMatchValue => $temp) {
				// Compile <option> tag:
				array_unshift($opt, '<option value="' . htmlspecialchars($theNoMatchValue) . '" selected="selected">'
					. htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue), ENT_COMPAT, 'UTF-8', FALSE) . '</option>');
			}
		}
		// Compile selector box:
		$sOnChange = implode('', $PA['fieldChangeFunc']);
		$selector_itemListStyle = isset($config['itemListStyle'])
			? ' style="' . htmlspecialchars($config['itemListStyle']) . '"'
			: ' style="' . $this->formEngine->defaultMultipleSelectorStyle . '"';
		$size = (int)$config['size'];
		$cssPrefix = $size === 1 ? 'tceforms-select' : 'tceforms-multiselect';
		$size = $config['autoSizeMax']
			? MathUtility::forceIntegerInRange(count($selItems) + 1, MathUtility::forceIntegerInRange($size, 1), $config['autoSizeMax'])
			: $size;
		$selectBox = '<select id="' . str_replace('.', '', uniqid($cssPrefix, TRUE)) . '" name="' . $PA['itemFormElName'] . '[]" '
			. 'class="' . $this->cssClassTypeElementPrefix . 'select ' . $cssPrefix . '"' . ($size ? ' size="' . $size . '" ' : '')
			. ' multiple="multiple" onchange="' . htmlspecialchars($sOnChange) . '"' . $PA['onFocus']
			. ' ' . $selector_itemListStyle . $disabled . '>
						' . implode('
						', $opt) . '
					</select>';
		// Add an empty hidden field which will send a blank value if all items are unselected.
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . htmlspecialchars($PA['itemFormElName']) . '" value="" />';
		}
		// Put it all into a table:
		$onClick = htmlspecialchars($this->formEngine->elName(($PA['itemFormElName'] . '[]')) . '.selectedIndex=-1;' . implode('', $restoreCmd) . ' return false;');
		$item .= '
			<table border="0" cellspacing="0" cellpadding="0" width="1" class="typo3-TCEforms-select-singlebox">
				<tr>
					<td>
					' . $selectBox . '
					<br/>
					<em>' . htmlspecialchars($this->formEngine->getLL('l_holdDownCTRL')) . '</em>
					</td>
					<td valign="top">
						<a href="#" onclick="' . $onClick . '" title="' . htmlspecialchars($this->formEngine->getLL('l_revertSelection')) . '">'
			. IconUtility::getSpriteIcon('actions-edit-undo') . '</a>
					</td>
				</tr>
			</table>
				';
		return $item;
	}

	/**
	 * If the select field is build by a foreign_table the related UIDs
	 * will be returned.
	 *
	 * Otherwise the label of the currently selected value will be written
	 * to the alternativeFieldValue class property.
	 *
	 * @param array $fieldConfig The "config" section of the TCA for the current select field.
	 * @param string $fieldName The name of the select field.
	 * @param string $value The current value in the local record, usually a comma separated list of selected values.
	 * @return array Array of related UIDs.
	 */
	public function getRelatedSelectFieldUids(array $fieldConfig, $fieldName, $value) {
		$relatedUids = array();

		$isTraversable = FALSE;
		if (isset($fieldConfig['foreign_table'])) {
			$isTraversable = TRUE;
			// if a foreign_table is used we pre-filter the records for performance
			$fieldConfig['foreign_table_where'] .= ' AND ' . $fieldConfig['foreign_table'] . '.uid IN (' . $value . ')';
		}

		$PA = array();
		$PA['fieldConf']['config'] = $fieldConfig;
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type'];
		$PA['fieldTSConfig'] = $this->formEngine->setTSconfig($this->currentTable, $this->currentRow, $fieldName);
		$PA['fieldConf']['config'] = $this->formEngine->overrideFieldConf($PA['fieldConf']['config'], $PA['fieldTSConfig']);
		$selectItemArray = $this->getSelectItems($this->currentTable, $fieldName, $this->currentRow, $PA);

		if ($isTraversable && count($selectItemArray)) {
			$this->currentTable = $fieldConfig['foreign_table'];
			$relatedUids = $this->getSelectedValuesFromSelectItemArray($selectItemArray, $value);
		} else {
			$selectedLabels = $this->getSelectedValuesFromSelectItemArray($selectItemArray, $value, 1, TRUE);
			if (count($selectedLabels) === 1) {
				$this->alternativeFieldValue = $selectedLabels[0];
				$this->forceAlternativeFieldValueUse = TRUE;
			}
		}

		return $relatedUids;
	}

	/**
	 * Extracts the selected values from a given array of select items.
	 *
	 * @param array $selectItemArray The select item array generated by \TYPO3\CMS\Backend\Form\FormEngine->getSelectItems.
	 * @param string $value The currently selected value(s) as comma separated list.
	 * @param int|NULL $maxItems Optional value, if set processing is skipped and an empty array will be returned when the number of selected values is larger than the provided value.
	 * @param bool $returnLabels If TRUE the select labels will be returned instead of the values.
	 * @return array
	 */
	protected function getSelectedValuesFromSelectItemArray(array $selectItemArray, $value, $maxItems = NULL, $returnLabels = FALSE) {
		$values = GeneralUtility::trimExplode(',', $value);
		$selectedValues = array();

		if ($maxItems !== NULL && (count($values) > (int)$maxItems)) {
			return $selectedValues;
		}

		foreach ($selectItemArray as $selectItem) {
			$selectItemValue = $selectItem[1];
			if (in_array($selectItemValue, $values)) {
				if ($returnLabels) {
					$selectedValues[] = $selectItem[0];
				} else {
					$selectedValues[] = $selectItemValue;
				}
			}
		}

		return $selectedValues;
	}

	/**
	 * @param string $alternativeFieldValue
	 */
	public function setAlternativeFieldValue($alternativeFieldValue) {
		$this->alternativeFieldValue = $alternativeFieldValue;
	}

	/**
	 * @param array $currentRow
	 */
	public function setCurrentRow($currentRow) {
		$this->currentRow = $currentRow;
	}

	/**
	 * @param string $currentTable
	 */
	public function setCurrentTable($currentTable) {
		$this->currentTable = $currentTable;
	}

	/**
	 * @param boolean $forceAlternativeFieldValueUse
	 */
	public function setForceAlternativeFieldValueUse($forceAlternativeFieldValueUse) {
		$this->forceAlternativeFieldValueUse = $forceAlternativeFieldValueUse;
	}

	/**
	 * @return string
	 */
	public function getAlternativeFieldValue() {
		return $this->alternativeFieldValue;
	}

	/**
	 * @return array
	 */
	public function getCurrentRow() {
		return $this->currentRow;
	}

	/**
	 * @return string
	 */
	public function getCurrentTable() {
		return $this->currentTable;
	}

	/**
	 * @return boolean
	 */
	public function isForceAlternativeFieldValueUse() {
		return $this->forceAlternativeFieldValueUse;
	}




}
