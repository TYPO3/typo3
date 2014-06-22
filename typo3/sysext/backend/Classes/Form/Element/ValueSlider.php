<?php
namespace TYPO3\CMS\Backend\Form\Element;

/**
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

/**
 * TCEforms wizard for rendering an AJAX selector for records
 *
 * @author Steffen Kamper <steffen@typo3.org>
 */
class ValueSlider {

	/**
	 * Renders the slider value wizard
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $pObj
	 * @return string
	 * @todo Define visibility
	 */
	public function renderWizard(&$params, &$pObj) {
		$pObj->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/ValueSlider.js');
		$field = $params['field'];
		$value = $params['row'][$field];
		// If Slider is used in a flexform
		if (!empty($params['flexFormPath'])) {
			$flexFormTools = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
			$flexFormValue = $flexFormTools->getArrayValueByPath($params['flexFormPath'], GeneralUtility::xml2array($value));
			if ($flexFormValue !== NULL) {
				$value = $flexFormValue;
			}
		}
		$itemName = $params['itemName'];
		// Set default values (which correspond to those of the JS component)
		$min = 0;
		$max = 10000;
		// Use the range property, if defined, to set min and max values
		if (isset($params['fieldConfig']['range'])) {
			$min = isset($params['fieldConfig']['range']['lower']) ? (int)$params['fieldConfig']['range']['lower'] : 0;
			$max = isset($params['fieldConfig']['range']['upper']) ? (int)$params['fieldConfig']['range']['upper'] : 10000;
		}
		$elementType = $params['fieldConfig']['type'];
		$step = $params['wConf']['step'] ?: 1;
		$width = (int)$params['wConf']['width'] ?: 400;
		$type = 'null';
		if (isset($params['fieldConfig']['eval'])) {
			$eval = GeneralUtility::trimExplode(',', $params['fieldConfig']['eval'], TRUE);
			if (in_array('time', $eval)) {
				$type = 'time';
				$value = (int)$value;
			} elseif (in_array('int', $eval)) {
				$type = 'int';
				$value = (int)$value;
			} elseif (in_array('double2', $eval)) {
				$type = 'double';
				$value = (double) $value;
			}
		}
		if (isset($params['fieldConfig']['items'])) {
			$type = 'array';
			$value = (int)$value;
		}
		$callback = $params['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
		$getField = $params['fieldChangeFunc']['typo3form.fieldGet'];
		$id = 'slider-' . $params['md5ID'];
		$contents = '<div id="' . $id . '"></div>';
		$js = '
		new TYPO3.Components.TcaValueSlider({
			minValue: ' . $min . ',
			maxValue: ' . $max . ',
			value: ' . $value . ',
			increment: ' . $step . ',
			renderTo: "' . $id . '",
			itemName: "' . $itemName . '",
			changeCallback: "' . $callback . '",
			getField: "' . $getField . '",
			width: "' . $width . '",
			type: "' . $type . '",
			elementType: "' . $elementType . '"
		});
		';
		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $GLOBALS['SOBE']->doc->getPageRenderer();
		$pageRenderer->addExtOnReadyCode($js);
		return $contents;
	}

}
