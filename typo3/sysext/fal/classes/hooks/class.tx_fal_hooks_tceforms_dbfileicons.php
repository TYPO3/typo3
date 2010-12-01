<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * Hook into t3lib_TCEforms::dbFileIcons
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package TYPO3
 * @subpackage tx_fal
 */
class tx_fal_hooks_TCEforms_dbFileIcons implements t3lib_TCEforms_dbFileIconsHook {

	/**
	 * Modifies the parameters for selector box form-field for the db/file/select elements (multiple)
	 *
	 * @param	array			$params				An array of additional parameters, eg: "size", "info", "headers" (array with "selector" and "items"), "noBrowser", "thumbnails"
	 * @param	string			$selector			Alternative selector box.
	 * @param	string			$thumbnails			Thumbnail view of images. Only filled if there are images only. This images will be shown under the selectorbox.
	 * @param	array			$icons				Defined icons next to the selector box.
	 * @param	string			$rightbox			Thumbnail view of images. Only filled if there are other types as images. This images will be shown right next to the selectorbox.
	 * @param	string			$fName				Form element name
	 * @param	array			$uidList			The array of item-uids. Have a look at t3lib_TCEforms::dbFileIcons parameter "$itemArray"
	 * @param	array			$additionalParams	Array with additional parameters which are be available at method call. Includes $mode, $allowed, $itemArray, $onFocus, $table, $field, $uid. For more information have a look at PHPDoc-Comment of t3lib_TCEforms::dbFileIcons
	 * @param	t3lib_TCEforms	$parentObject		parent t3lib_TCEforms object
	 * @return	void
	 */
	public function dbFileIcons_postProcess(array &$params, &$selector, &$thumbnails, array &$icons, &$rightbox, &$fName, array &$uidList, array $additionalParams, t3lib_TCEforms $parentObject) {

		if (tx_fal_tcafunc::isFieldAFalField($additionalParams['field'], $additionalParams['table']) === TRUE) {
			$icons['R'] = array();
			if (!$params['readOnly'] && !$params['noList']) {
				if (!$params['noBrowser'])	{

					//load needed js-libs:
					$pageRenderer = &$GLOBALS['SOBE']->doc->getPageRenderer();
					//disableCompressJavascript is required to make $pageRenderer respect
					//ajax.php?ajaxID=ExtDirect::getAPI&namespace=
					$pageRenderer->disableCompressJavascript();
					$pageRenderer->addExtOnReadyCode("
							Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
					");

					$pageRenderer->addJsInlineCode('loadFalElementBrowser',"
					loadFalElementBrowser = function(fName){
						var l = document.getElementsByName(fName+'_list')[0],
							h = document.getElementsByName(fName)[0];
						top.TYPO3.Components.filelist.loadElementBrowser(l,h);
					}
					");

					//render EB-Icon:
					$table = $additionalParams['table'];
					$field = $additionalParams['field'];

					// check against inline uniqueness
					$inlineParent = $parentObject->inline->getStructureLevel(-1);
					if(is_array($inlineParent) && $inlineParent['uid']) {
						if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
							$objectPrefix = $parentObject->inline->inlineNames['object'].'['.$table.']';
							$aOnClickInline = $objectPrefix.'|inline.checkUniqueElement|inline.setUniqueElement';
							$rOnClickInline = 'inline.revertUnique(\''.$objectPrefix.'\',null,\''.$uid.'\');';
						}
					}
					$aOnClick='loadFalElementBrowser(\''.$fName.'\',\''.$allowed.'\',\''.$aOnClickInline.'\'); return false;';
					$icons['R'][]='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
							t3lib_iconWorks::getSpriteIcon('actions-insert-record', array('title' => htmlspecialchars($parentObject->getLL('l_browse_' . ($mode == 'db' ? 'db' : 'file'))))) .
					'</a>';
				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tx_fal_hooks_tceforms_dbfileicons.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tx_fal_hooks_tceforms_dbfileicons.php']);
}
?>