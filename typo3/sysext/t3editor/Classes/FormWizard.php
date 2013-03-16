<?php
namespace TYPO3\CMS\T3Editor;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Tobias Liebig <mail_typo3@etobi.de>
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
		$t3editor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\T3Editor\\T3Editor');
		if (!$t3editor->isEnabled()) {
			return;
		}
		if ($parameters['params']['format'] !== '') {
			$t3editor->setModeByType($parameters['params']['format']);
		} else {
			$t3editor->setMode(\TYPO3\CMS\T3Editor\T3Editor::MODE_MIXED);
		}
		$config = $GLOBALS['TCA'][$parameters['table']]['columns'][$parameters['field']]['config'];
		$doc = $GLOBALS['SOBE']->doc;
		$attributes = 'rows="' . $config['rows'] . '" ' . 'cols="' . $config['cols'] . '" ' . 'wrap="off" ' . 'style="' . $config['wizards']['t3editor']['params']['style'] . '" ' . 'onchange="' . $parameters['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] . '" ';
		$parameters['item'] = '';
		$parameters['item'] .= $t3editor->getCodeEditor($parameters['itemName'], 'fixed-font enable-tab', $parameters['row'][$parameters['field']], $attributes, $parameters['table'] . ' > ' . $parameters['field'], array(
			'target' => intval($pObj->target)
		));
		$parameters['item'] .= $t3editor->getJavascriptCode($doc);
		return '';
	}

}


?>