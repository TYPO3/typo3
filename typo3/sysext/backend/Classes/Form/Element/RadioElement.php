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

use TYPO3\CMS\Backend\Form\DataPreprocessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;

/**
 * Generation of TCEform elements of the type "radio"
 */
class RadioElement extends AbstractFormElement {

	/**
	 * This will render a series of radio buttons.
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$parameterArray = $this->globalOptions['parameterArray'];
		$config = $parameterArray['fieldConf']['config'];
		$html = '';
		$disabled = '';
		if ($this->isGlobalReadonly() || $config['readOnly']) {
			$disabled = ' disabled';
		}

		// Get items for the array
		$selectedItems = FormEngineUtility::initItemArray($parameterArray['fieldConf']);
		if ($config['itemsProcFunc']) {
			$dataPreprocessor = GeneralUtility::makeInstance(DataPreprocessor::class);
			$selectedItems = $dataPreprocessor->procItems(
				$selectedItems,
				$parameterArray['fieldTSConfig']['itemsProcFunc.'],
				$config,
				$this->globalOptions['table'],
				$this->globalOptions['databaseRow'],
				$this->globalOptions['fieldName']
			);
		}

		// Traverse the items, making the form elements
		foreach ($selectedItems as $radioButton => $selectedItem) {
			if (isset($parameterArray['fieldTSConfig']['altLabels.'][$radioButton])) {
				$label = $this->getLanguageService()->sL(
					$parameterArray['fieldTSConfig']['altLabels.'][$radioButton]
				);
			} else {
				$label =  $selectedItem[0];
			}
			$radioId = htmlspecialchars($parameterArray['itemFormElID'] . '_' . $radioButton);
			$radioOnClick = implode('', $parameterArray['fieldChangeFunc']);
			$radioChecked = (string)$selectedItem[1] === (string)$parameterArray['itemFormElValue'] ? ' checked="checked"' : '';
			$html .= '<div class="radio' . $disabled . '">'
				. '<label for="' . $radioId . '">'
				. '<input '
				. 'type="radio" '
				. 'name="' . htmlspecialchars($parameterArray['itemFormElName']) . '" '
				. 'id="' . $radioId . '" '
				. 'value="' . htmlspecialchars($selectedItem[1]) . '" '
				. $radioChecked . ' '
				. $parameterArray['onFocus'] . ' '
				. $disabled . ' '
				. 'onclick="' . htmlspecialchars($radioOnClick) . '" '
				. '/>'
				. htmlspecialchars($label)
				. '</label>'
			. '</div>';
		}
		$resultArray = $this->initializeResultArray();
		$resultArray['html'] = $html;
		return $resultArray;
	}

}
