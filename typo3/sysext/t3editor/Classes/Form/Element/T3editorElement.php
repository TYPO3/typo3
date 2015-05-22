<?php
namespace TYPO3\CMS\T3editor\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\T3editor\T3editor;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * t3editor FormEngine widget
 */
class T3editorElement extends AbstractFormElement {

	/**
	 * Render t3editor element
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$resultArray = $this->initializeResultArray();

		$parameterArray = $this->globalOptions['parameterArray'];

		$rows = MathUtility::forceIntegerInRange($parameterArray['fieldConf']['config']['rows'] ?: 10, 1, 40);

		$t3editor = GeneralUtility::makeInstance(T3editor::class);
		$t3editor->setMode(isset($parameterArray['fieldConf']['config']['format']) ? $parameterArray['fieldConf']['config']['format'] : T3editor::MODE_MIXED);

		$doc = $GLOBALS['SOBE']->doc;
		$attributes = 'rows="' . $rows . '"' .
			' wrap="off"' .
			' style="width:96%; height: 60%;"' .
			' onchange="' . $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] . '" ';

		$resultArray['html'] = $t3editor->getCodeEditor(
			$parameterArray['itemFormElName'],
			'text-monospace enable-tab',
			$parameterArray['itemFormElValue'],
			$attributes,
			$this->globalOptions['table'] . ' > ' . $this->globalOptions['fieldName'],
			array('target' => 0)
		);
		$resultArray['html'] .= $t3editor->getJavascriptCode($doc);

		return $resultArray;
	}

}
