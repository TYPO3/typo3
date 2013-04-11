<?php
namespace TYPO3\CMS\Backend\Form\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Ritter <info@steffen-ritter.net>
 *  (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
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
		$jsPath = '../t3lib/js/extjs/components/slider/';
		$pObj->loadJavascriptLib($jsPath . 'ValueSlider.js');
		$field = $params['field'];
		$value = $params['row'][$field];
		// If Slider is used in a flexform
		if (!empty($params['flexFormPath'])) {
			$flexFormTools = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
			$flexFormValue = $flexFormTools->getArrayValueByPath($params['flexFormPath'], \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($value));
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
			$min = isset($params['fieldConfig']['range']['lower']) ? intval($params['fieldConfig']['range']['lower']) : 0;
			$max = isset($params['fieldConfig']['range']['upper']) ? intval($params['fieldConfig']['range']['upper']) : 10000;
		}
		$elementType = $params['fieldConfig']['type'];
		$step = $params['wConf']['step'] ? $params['wConf']['step'] : 1;
		$width = intval($params['wConf']['width']) ? intval($params['wConf']['width']) : 400;
		$type = 'null';
		if (isset($params['fieldConfig']['eval'])) {
			$eval = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $params['fieldConfig']['eval'], TRUE);
			if (in_array('time', $eval)) {
				$type = 'time';
				$value = (int) $value;
			} elseif (in_array('int', $eval)) {
				$type = 'int';
				$value = (int) $value;
			} elseif (in_array('double2', $eval)) {
				$type = 'double';
				$value = (double) $value;
			}
		}
		if (isset($params['fieldConfig']['items'])) {
			$type = 'array';
			$value = (int) $value;
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


?>