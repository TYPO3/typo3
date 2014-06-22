<?php
namespace TYPO3\CMS\T3editor;

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

/**
 * Wizard for tceforms
 *
 * @author Tobias Liebig <mail_typo3@etobi.de>
 */
class FormWizard {

	/**
	 * Main function
	 *
	 * @param array $parameters
	 * @param object $pObj
	 * @return string|NULL
	 */
	public function main($parameters, $pObj) {
		$t3editor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\T3editor\\T3editor');
		if (!$t3editor->isEnabled()) {
			return;
		}
		if ($parameters['params']['format'] !== '') {
			$t3editor->setModeByType($parameters['params']['format']);
		} else {
			$t3editor->setMode(\TYPO3\CMS\T3editor\T3editor::MODE_MIXED);
		}
		$config = $GLOBALS['TCA'][$parameters['table']]['columns'][$parameters['field']]['config'];
		$doc = $GLOBALS['SOBE']->doc;
		$attributes = 'rows="' . $config['rows'] . '" ' . 'cols="' . $config['cols'] . '" ' . 'wrap="off" ' . 'style="' . $config['wizards']['t3editor']['params']['style'] . '" ' . 'onchange="' . $parameters['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] . '" ';
		$parameters['item'] = '';
		$parameters['item'] .= $t3editor->getCodeEditor($parameters['itemName'], 'fixed-font enable-tab', $parameters['row'][$parameters['field']], $attributes, $parameters['table'] . ' > ' . $parameters['field'], array(
			'target' => (int)$pObj->target
		));
		$parameters['item'] .= $t3editor->getJavascriptCode($doc);
		return '';
	}

}
