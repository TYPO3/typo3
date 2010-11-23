<?php
/* **************************************************************
*  Copyright notice
*
*  (c) webservices.nl
*  (c) 2006-2010 Karsten Dambekalns <karsten@typo3.org>
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
/* $Id: class.tx_em_extensions_list.php 2084 2010-03-22 01:46:37Z steffenk $ */

/**
 * This class handles extension listings
 *
 */
class tx_em_Extensions_List {


	protected $parentObject;

	protected $categories;
	protected $types;

	/**
	 * Constructor
	 *
	 * @param object $parentObject
	 * @return void
	 */
	public function __construct($parentObject = NULL) {
		$this->parentObject = $parentObject;
		$this->install = t3lib_div::makeInstance('tx_em_Install', $this);

		$this->categories = array(
			'be' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_BE'),
			'module' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_BE_modules'),
			'fe' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_FE'),
			'plugin' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_FE_plugins'),
			'misc' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_miscellanous'),
			'services' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_services'),
			'templates' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_templates'),
			'example' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_examples'),
			'doc' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_documentation')
		);
		$this->types = array(
			'S' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:type_system'),
			'G' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:type_global'),
			'L' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:type_local'),
		);
	}


	/**
	 * Returns the list of available (installed) extensions
	 *
	 * @return	array		Array with two arrays, list array (all extensions with info) and category index
	 * @see getInstExtList()
	 */
	function getInstalledExtensions($new = FALSE) {
		$list = array();

		if (!$new) {
			$cat = $this->parentObject->defaultCategories;

			$path = PATH_typo3 . 'sysext/';
			$this->getInstExtList($path, $list, $cat, 'S');

			$path = PATH_typo3 . 'ext/';
			$this->getInstExtList($path, $list, $cat, 'G');

			$path = PATH_typo3conf . 'ext/';
			$this->getInstExtList($path, $list, $cat, 'L');

			return array($list, $cat);
		} else {
			$path = PATH_typo3 . 'sysext/';
			$this->getFlatInstExtList($path, $list, 'S');

			$path = PATH_typo3 . 'ext/';
			$this->getFlatInstExtList($path, $list, 'G');

			$path = PATH_typo3conf . 'ext/';
			$this->getFlatInstExtList($path, $list, 'L');

			return $list;
		}
	}

	/**
	 * Gathers all extensions in $path
	 *
	 * @param	string		Absolute path to local, global or system extensions
	 * @param	array		Array with information for each extension key found. Notice: passed by reference
	 * @param	array		Categories index: Contains extension titles grouped by various criteria.
	 * @param	string		Path-type: L, G or S
	 * @return	void		"Returns" content by reference
	 * @see getInstalledExtensions()
	 */
	function getInstExtList($path, &$list, &$cat, $type) {

		if (@is_dir($path)) {
			$extList = t3lib_div::get_dirs($path);
			if (is_array($extList)) {
				foreach ($extList as $extKey) {
					if (@is_file($path . $extKey . '/ext_emconf.php')) {
						$emConf = tx_em_Tools::includeEMCONF($path . $extKey . '/ext_emconf.php', $extKey);
						if (is_array($emConf)) {
							if (is_array($list[$extKey])) {
								$list[$extKey] = array('doubleInstall' => $list[$extKey]['doubleInstall']);
							}
							$list[$extKey]['extkey'] = $extKey;
							$list[$extKey]['doubleInstall'] .= $type;
							$list[$extKey]['type'] = $type;
							$list[$extKey]['installed'] = t3lib_extMgm::isLoaded($extKey);
							$list[$extKey]['EM_CONF'] = $emConf;
							$list[$extKey]['files'] = t3lib_div::getFilesInDir($path . $extKey, '', 0, '', $this->excludeForPackaging);

							tx_em_Tools::setCat($cat, $list[$extKey], $extKey);
						}
					}
				}
			}
		}
	}

	/**
	 * Gathers all extensions in $path
	 *
	 * @param	string		Absolute path to local, global or system extensions
	 * @param	array		Array with information for each extension key found. Notice: passed by reference
	 * @param	array		Categories index: Contains extension titles grouped by various criteria.
	 * @param	string		Path-type: L, G or S
	 * @return	void		"Returns" content by reference
	 * @access private
	 * @see getInstalledExtensions()
	 */
	function getFlatInstExtList($path, &$list, $type) {


		if (@is_dir($path)) {
			$extList = t3lib_div::get_dirs($path);
			if (is_array($extList)) {
				foreach ($extList as $extKey) {
					$this->singleExtInfo($extKey, $path, $list, $type);
				}
			}
		}
	}

	/**
	 * Gets a single extension info
	 *
	 * @param  $extKey
	 * @param  $path
	 * @param  $list
	 * @param string $type
	 * @return void
	 */
	public function singleExtInfo($extKey, $path, &$list, $type = '') {
		if (@is_file($path . $extKey . '/ext_emconf.php')) {
			$relPath = '../../../../' . substr($path, strlen(PATH_site));
			$emConf = tx_em_Tools::includeEMCONF($path . $extKey . '/ext_emconf.php', $extKey);
			$manual = $path . $extKey . '/doc/manual.sxw';
			if ($type === '') {
				$type = tx_em_Tools::getExtTypeFromPath($path);
			}
			if (is_array($emConf)) {
				$key = count($list);
				$loaded = t3lib_extMgm::isLoaded($extKey);
				if (is_array($list[$key])) {
					$list[$key] = array('doubleInstall' => $list[$key]['doubleInstall']);
				}
				$list[$key]['extkey'] = $extKey;
				$list[$key]['path'] = $path . $extKey;
				$list[$key]['nodePath'] = substr($path . $extKey, strlen(PATH_site));
				$list[$key]['doubleInstall'] .= $this->types[$type];

				$list[$key]['type'] = $this->types[$type];
				$list[$key]['typeShort'] = $type;
				$list[$key]['installed'] = $loaded ? 1 : 0;
				// FIXME: raises PHP warning
				// "Core: Error handler (BE): PHP Warning: htmlspecialchars() expects parameter 1 to be string, array given in [...]/typo3/mod/tools/em/classes/class.tx_em_extensions_list.php line 185
				$list[$key] = t3lib_div::array_merge_recursive_overrule($list[$key], $emConf);
				$list[$key]['title'] = htmlspecialchars($list[$key]['title']);
				$list[$key]['description'] = htmlspecialchars($list[$key]['description']);
				$list[$key]['files'] = t3lib_div::getFilesInDir($path . $extKey, '', 0, '', $this->excludeForPackaging);
				$list[$key]['install'] = $loaded ? '<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
					'CMD[showExt]' => $extKey,
					'CMD[remove]' => 1,
					'CMD[clrCmd]' => 1,
					'SET[singleDetails]' => 'info'
				))) . '">' . tx_em_Tools::removeButton() . '</a>' :
						'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
							'CMD[showExt]' => $extKey,
							'CMD[load]' => 1,
							'CMD[clrCmd]' => 1,
							'SET[singleDetails]' => 'info'
						))) . '">' . tx_em_Tools::installButton() . '</a>';

				$list[$key]['install'] = $loaded ? tx_em_Tools::removeButton() : tx_em_Tools::installButton();

				$list[$key]['download'] = '<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
					'CMD[doBackup]' => 1,
					'SET[singleDetails]' => 'backup',
					'CMD[showExt]' => $extKey
				))) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-system-extension-download') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:download') . '" alt=""></a>';

				$list[$key]['doc'] = '';
				if (@is_file($manual)) {
					$list[$key]['doc'] = '<a href="' . htmlspecialchars($relPath . $extKey . '/doc/manual.sxw') . '" target="_blank">
					<img src="res/icons/oodoc.gif" width="13" height="16" title="' . $GLOBALS['LANG']->getLL('listRow_local_manual') . '" alt="" /></a>';
				}
				$list[$key]['icon'] = @is_file($path . $extKey . '/ext_icon.gif') ? '<img src="' . $relPath . $extKey . '/ext_icon.gif" alt="" width="16" height="16" />' : '<img src="clear.gif" alt="" width="16" height="16" />';

				$list[$key]['categoryShort'] = $list[$key]['category'];
				$list[$key]['category'] = $this->categories[$list[$key]['category']];

				unset($list[$key]['_md5_values_when_last_written']);
			}
		}
	}


	/**
	 * Listing of loaded (installed) extensions
	 *
	 * @return	void
	 */
	function extensionList_loaded() {
		global $TYPO3_LOADED_EXT;

		list($list, $cat) = $this->getInstalledExtensions();

		// Loaded extensions
		$content = '';
		$lines = array();

		// Available extensions
		if (is_array($cat[$this->parentObject->MOD_SETTINGS['listOrder']])) {
			$content = '';
			$lines = array();
			$lines[] = $this->extensionListRowHeader(' class="t3-row-header"', array('<td><img src="clear.gif" width="1" height="1" alt="" /></td>'));

			foreach ($cat[$this->parentObject->MOD_SETTINGS['listOrder']] as $catName => $extEkeys) {

				natcasesort($extEkeys);
				$extensions = array();
				foreach ($extEkeys as $extKey => $data) {
					if (array_key_exists($extKey, $TYPO3_LOADED_EXT) && ($this->parentObject->MOD_SETTINGS['display_shy'] || !$list[$extKey]['EM_CONF']['shy']) && $this->parentObject->searchExtension($extKey, $list[$extKey])) {
						if (in_array($extKey, $this->parentObject->requiredExt)) {
							$loadUnloadLink = '<strong>' . $GLOBALS['TBE_TEMPLATE']->rfw($GLOBALS['LANG']->getLL('extension_required_short')) . '</strong>';
						} else {
							$loadUnloadLink = '<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
								'CMD[showExt]' => $extKey,
								'CMD[remove]' => 1
							))) . '">' . tx_em_Tools::removeButton() . '</a>';
						}

						$extensions[] = $this->extensionListRow($extKey, $list[$extKey], array('<td class="bgColor">' . $loadUnloadLink . '</td>'));

					}
				}
				if (count($extensions)) {
					$lines[] = '<tr><td colspan="' . (3 + $this->parentObject->detailCols[$this->parentObject->MOD_SETTINGS['display_details']]) . '"><br /></td></tr>';
					$lines[] = '<tr><td colspan="' . (3 + $this->parentObject->detailCols[$this->parentObject->MOD_SETTINGS['display_details']]) . '">' . t3lib_iconWorks::getSpriteIcon('apps-filetree-folder-default') . '<strong>' . htmlspecialchars($this->parentObject->listOrderTitle($this->parentObject->MOD_SETTINGS['listOrder'], $catName)) . '</strong></td></tr>';
					$lines[] = implode(LF, $extensions);
				}
			}
		}

		$content .= '<form action="' . $this->parentObject->script . '" method="post" name="lookupform">';
		$content .= '<label for="lookUp">' . $GLOBALS['LANG']->getLL('look_up') . '</label> <input type="text" id="lookUp" name="lookUp" value="' . htmlspecialchars($this->lookUpStr) . '" /><input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:search') . '" /><br /><br />';

		$content .= '</form>

			<!-- Loaded Extensions List -->
			<table border="0" cellpadding="2" cellspacing="1">' . implode('', $lines) . '</table>';

		return $content;
	}

	/**
	 * Listing of available (installed) extensions
	 *
	 * @return	void
	 */
	function extensionList_installed() {

		list($list, $cat) = $this->getInstalledExtensions();

		// Available extensions
		if (is_array($cat[$this->parentObject->MOD_SETTINGS['listOrder']])) {
			$content = '';
			$lines = array();
			$lines[] = $this->extensionListRowHeader(' class="t3-row-header"', array('<td><img src="clear.gif" width="18" height="1" alt="" /></td>'));

			$allKeys = array();
			foreach ($cat[$this->parentObject->MOD_SETTINGS['listOrder']] as $catName => $extEkeys) {
				if (!$this->parentObject->MOD_SETTINGS['display_obsolete'] && $catName == 'obsolete') {
					continue;
				}

				$allKeys[] = '';
				$allKeys[] = 'TYPE: ' . $catName;

				natcasesort($extEkeys);
				$extensions = array();
				foreach ($extEkeys as $extKey => $value) {
					$allKeys[] = $extKey;
					if ((!$list[$extKey]['EM_CONF']['shy'] || $this->parentObject->MOD_SETTINGS['display_shy']) &&
							($list[$extKey]['EM_CONF']['state'] != 'obsolete' || $this->parentObject->MOD_SETTINGS['display_obsolete'])
							&& $this->parentObject->searchExtension($extKey, $list[$extKey])) {
						$loadUnloadLink = t3lib_extMgm::isLoaded($extKey) ?
								'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
									'CMD[showExt]' => $extKey,
									'CMD[remove]' => 1,
									'CMD[clrCmd]' => 1,
									'SET[singleDetails]' => 'info'
								))) . '">' . tx_em_Tools::removeButton() . '</a>' :
								'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
									'CMD[showExt]' => $extKey,
									'CMD[load]' => 1,
									'CMD[clrCmd]' => 1,
									'SET[singleDetails]' => 'info'
								))) . '">' . tx_em_Tools::installButton() . '</a>';
						if (in_array($extKey, $this->parentObject->requiredExt)) {
							$loadUnloadLink = '<strong>' . $GLOBALS['TBE_TEMPLATE']->rfw($GLOBALS['LANG']->getLL('extension_required_short')) . '</strong>';
						}
						$theRowClass = t3lib_extMgm::isLoaded($extKey) ? 'em-listbg1' : 'em-listbg2';
						$extensions[] = $this->extensionListRow($extKey, $list[$extKey], array('<td class="bgColor">' . $loadUnloadLink . '</td>'), $theRowClass);
					}
				}
				if (count($extensions)) {
					$lines[] = '<tr><td colspan="' . (3 + $this->parentObject->detailCols[$this->parentObject->MOD_SETTINGS['display_details']]) . '"><br /></td></tr>';
					$lines[] = '<tr><td colspan="' . (3 + $this->parentObject->detailCols[$this->parentObject->MOD_SETTINGS['display_details']]) . '">' . t3lib_iconWorks::getSpriteIcon('apps-filetree-folder-default') . '<strong>' . htmlspecialchars($this->parentObject->listOrderTitle($this->parentObject->MOD_SETTINGS['listOrder'], $catName)) . '</strong></td></tr>';
					$lines[] = implode(LF, $extensions);
				}
			}

			$content .= '


<!--
EXTENSION KEYS:

' . trim(implode(LF, $allKeys)) . '

-->

';

			$content .= sprintf($GLOBALS['LANG']->getLL('how_to_install'), tx_em_Tools::installButton()) . ' <br />' .
					sprintf($GLOBALS['LANG']->getLL('how_to_uninstall'), tx_em_Tools::removeButton()) . ' <br /><br />';
			$content .= '<form action="' . $this->parentObject->script . '" method="post" name="lookupform">';
			$content .= '<label for="lookUp">' . $GLOBALS['LANG']->getLL('look_up') . '</label> <input type="text" id="lookUp" name="lookUp" value="' . htmlspecialchars($this->parentObject->lookUpStr) . '" /><input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:search') . '" /></form><br /><br />';
			$content .= $this->securityHint . '<br /><br />';

			$content .= '<table border="0" cellpadding="2" cellspacing="1">' . implode('', $lines) . '</table>';

			return $content;
		}
	}


	/**
	 * Prints the header row for the various listings
	 *
	 * @param	string		Attributes for the <tr> tag
	 * @param	array		Preset cells in the beginning of the row. Typically a blank cell with a clear-gif
	 * @param	boolean		If set, the list is coming from remote server.
	 * @return	string		HTML <tr> table row
	 */
	function extensionListRowHeader($trAttrib, $cells, $import = 0) {
		$cells[] = '<td></td>';
		$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_title') . '</td>';

		if (!$this->parentObject->MOD_SETTINGS['display_details']) {
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_description') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_author') . '</td>';
		} elseif ($this->parentObject->MOD_SETTINGS['display_details'] == 2) {
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_priority') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_modifies_tables_short') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_modules') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_clear_cache_short') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_internal') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_shy') . '</td>';
		} elseif ($this->parentObject->MOD_SETTINGS['display_details'] == 3) {
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_tables_fields') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_ts_files') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_affects') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_modules') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_config') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_code_warnings') . '</td>';
		} elseif ($this->parentObject->MOD_SETTINGS['display_details'] == 4) {
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_locallang') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_classes') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_code_warnings') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_annoyances') . '</td>';
		} elseif ($this->parentObject->MOD_SETTINGS['display_details'] == 5) {
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_changed_files') . '</td>';
		} else {
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_ext_key') . '</td>';
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_version') . '</td>';
			if (!$import) {
				$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_download_short') . '</td>';
				$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_documentation_short') . '</td>';
				$cells[] = '<td>' . $GLOBALS['LANG']->getLL('listRowHeader_type') . '</td>';
			} else {
				$cells[] = '<td' . tx_em_Tools::labelInfo($GLOBALS['LANG']->getLL('listRowHeader_title_upload_date')) . '>' .
						$GLOBALS['LANG']->getLL('listRowHeader_upload_date') . '</td>';
				$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_author') . '</td>';
				$cells[] = '<td' . tx_em_Tools::labelInfo($GLOBALS['LANG']->getLL('listRowHeader_title_current_version')) . '>' .
						$GLOBALS['LANG']->getLL('listRowHeader_current_version') . '</td>';
				$cells[] = '<td' . tx_em_Tools::labelInfo($GLOBALS['LANG']->getLL('listRowHeader_title_current_type')) . '>' .
						$GLOBALS['LANG']->getLL('listRowHeader_current_type') . '</td>';
				$cells[] = '<td' . tx_em_Tools::labelInfo($GLOBALS['LANG']->getLL('listRowHeader_title_number_of_downloads')) . '>' .
						$GLOBALS['LANG']->getLL('listRowHeader_download_short') . '</td>';
			}
			$cells[] = '<td>' . $GLOBALS['LANG']->getLL('extInfoArray_state') . '</td>';
		}
		return '
			<tr' . $trAttrib . '>
				' . implode('
				', $cells) . '
			</tr>';
	}


	/**
	 * Prints a row with data for the various extension listings
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	array		Preset table cells, eg. install/uninstall icons.
	 * @param	string		<tr> tag class
	 * @param	array		Array with installed extension keys (as keys)
	 * @param	boolean		If set, the list is coming from remote server.
	 * @param	string		Alternative link URL
	 * @return	string		HTML <tr> content
	 */
	function extensionListRow($extKey, $extInfo, $cells, $bgColorClass = '', $inst_list = array(), $import = 0, $altLinkUrl = '') {
		// Icon:
		$imgInfo = @getImageSize(tx_em_Tools::getExtPath($extKey, $extInfo['type']) . '/ext_icon.gif');
		if (is_array($imgInfo)) {
			$cells[] = '<td><img src="' . $GLOBALS['BACK_PATH'] . tx_em_Tools::typeRelPath($extInfo['type']) . $extKey . '/ext_icon.gif' . '" ' . $imgInfo[3] . ' alt="" /></td>';
		} elseif ($extInfo['_ICON']) {
			$cells[] = '<td>' . $extInfo['_ICON'] . '</td>';
		} else {
			$cells[] = '<td><img src="clear.gif" width="1" height="1" alt="" /></td>';
		}

		// Extension title:
		$cells[] = '<td nowrap="nowrap"><a href="' . htmlspecialchars($altLinkUrl ? $altLinkUrl : t3lib_div::linkThisScript(array(
			'CMD[showExt]' => $extKey,
			'SET[singleDetails]' => 'info'
		))) . '" title="' . htmlspecialchars($extInfo['EM_CONF']['description']) . '">'
				. t3lib_div::fixed_lgd_cs($extInfo['EM_CONF']['title'] ? htmlspecialchars($extInfo['EM_CONF']['title']) : '<em>' . $extKey . '</em>', 40) . '</a></td>';

		// Based on the display mode you will see more or less details:
		if (!$this->parentObject->MOD_SETTINGS['display_details']) {
			$cells[] = '<td>' . htmlspecialchars(t3lib_div::fixed_lgd_cs($extInfo['EM_CONF']['description'], 400)) . '<br /><img src="clear.gif" width="300" height="1" alt="" /></td>';
			$cells[] = '<td nowrap="nowrap">' . ($extInfo['EM_CONF']['author_email'] ? '<a href="mailto:' . htmlspecialchars($extInfo['EM_CONF']['author_email']) . '">' : '') . htmlspecialchars($extInfo['EM_CONF']['author']) . (htmlspecialchars($extInfo['EM_CONF']['author_email']) ? '</a>' : '') . ($extInfo['EM_CONF']['author_company'] ? '<br />' . htmlspecialchars($extInfo['EM_CONF']['author_company']) : '') . '</td>';
		} elseif ($this->parentObject->MOD_SETTINGS['display_details'] == 2) {
			$cells[] = '<td nowrap="nowrap">' . $extInfo['EM_CONF']['priority'] . '</td>';
			$cells[] = '<td nowrap="nowrap">' . implode('<br />', t3lib_div::trimExplode(',', $extInfo['EM_CONF']['modify_tables'], 1)) . '</td>';
			$cells[] = '<td nowrap="nowrap">' . $extInfo['EM_CONF']['module'] . '</td>';
			$cells[] = '<td nowrap="nowrap">' . ($extInfo['EM_CONF']['clearCacheOnLoad'] ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes') : '') . '</td>';
			$cells[] = '<td nowrap="nowrap">' . ($extInfo['EM_CONF']['internal'] ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes') : '') . '</td>';
			$cells[] = '<td nowrap="nowrap">' . ($extInfo['EM_CONF']['shy'] ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes') : '') . '</td>';
		} elseif ($this->parentObject->MOD_SETTINGS['display_details'] == 3) {
			$techInfo = $this->install->makeDetailedExtensionAnalysis($extKey, $extInfo);

			$cells[] = '<td>' . $this->parentObject->extensionDetails->extInformationArray_dbReq($techInfo) .
					'</td>';
			$cells[] = '<td nowrap="nowrap">' . (is_array($techInfo['TSfiles']) ? implode('<br />', $techInfo['TSfiles']) : '') . '</td>';
			$cells[] = '<td nowrap="nowrap">' . (is_array($techInfo['flags']) ? implode('<br />', $techInfo['flags']) : '') . '</td>';
			$cells[] = '<td nowrap="nowrap">' . (is_array($techInfo['moduleNames']) ? implode('<br />', $techInfo['moduleNames']) : '') . '</td>';
			$cells[] = '<td nowrap="nowrap">' . ($techInfo['conf'] ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes') : '') . '</td>';
			$cells[] = '<td>' .
					$GLOBALS['TBE_TEMPLATE']->rfw((t3lib_extMgm::isLoaded($extKey) && $techInfo['tables_error'] ?
							'<strong>' . $GLOBALS['LANG']->getLL('extInfoArray_table_error') . '</strong><br />' .
									$GLOBALS['LANG']->getLL('extInfoArray_missing_fields') : '') .
							(t3lib_extMgm::isLoaded($extKey) && $techInfo['static_error'] ?
									'<strong>' . $GLOBALS['LANG']->getLL('extInfoArray_static_table_error') . '</strong><br />' .
											$GLOBALS['LANG']->getLL('extInfoArray_static_tables_missing_empty') : '')) .
					'</td>';
		} elseif ($this->parentObject->MOD_SETTINGS['display_details'] == 4) {
			$techInfo = $this->install->makeDetailedExtensionAnalysis($extKey, $extInfo, 1);

			$cells[] = '<td>' . (is_array($techInfo['locallang']) ? implode('<br />', $techInfo['locallang']) : '') . '</td>';
			$cells[] = '<td>' . (is_array($techInfo['classes']) ? implode('<br />', $techInfo['classes']) : '') . '</td>';
			$cells[] = '<td>' . (is_array($techInfo['errors']) ? $GLOBALS['TBE_TEMPLATE']->rfw(implode('<hr />', $techInfo['errors'])) : '') . '</td>';
			$cells[] = '<td>' . (is_array($techInfo['NSerrors']) ?
					(!t3lib_div::inList($this->parentObject->nameSpaceExceptions, $extKey) ?
							t3lib_utility_Debug::viewarray($techInfo['NSerrors']) :
							$GLOBALS['TBE_TEMPLATE']->dfw($GLOBALS['LANG']->getLL('extInfoArray_exception'))) : '') . '</td>';
		} elseif ($this->parentObject->MOD_SETTINGS['display_details'] == 5) {
			$currentMd5Array = $this->parentObject->extensionDetails->serverExtensionMD5array($extKey, $extInfo);
			$affectedFiles = '';
			$msgLines = array();
			$msgLines[] = $GLOBALS['LANG']->getLL('listRow_files') . ' ' . count($currentMd5Array);
			if (strcmp($extInfo['EM_CONF']['_md5_values_when_last_written'], serialize($currentMd5Array))) {
				$msgLines[] = $GLOBALS['TBE_TEMPLATE']->rfw('<br /><strong>' . $GLOBALS['LANG']->getLL('extInfoArray_difference_detected') . '</strong>');
				$affectedFiles = tx_em_Tools::findMD5ArrayDiff($currentMd5Array, unserialize($extInfo['EM_CONF']['_md5_values_when_last_written']));
				if (count($affectedFiles)) {
					$msgLines[] = '<br /><strong>' . $GLOBALS['LANG']->getLL('extInfoArray_modified_files') . '</strong><br />' .
							$GLOBALS['TBE_TEMPLATE']->rfw(implode('<br />', $affectedFiles));
				}
			}
			$cells[] = '<td>' . implode('<br />', $msgLines) . '</td>';
		} else {
			// Default view:
			$verDiff = $inst_list[$extKey] && tx_em_Tools::versionDifference($extInfo['EM_CONF']['version'], $inst_list[$extKey]['EM_CONF']['version'], $this->parentObject->versionDiffFactor);

			$cells[] = '<td nowrap="nowrap"><em>' . $extKey . '</em></td>';
			$cells[] = '<td nowrap="nowrap">' . ($verDiff ? '<strong>' . $GLOBALS['TBE_TEMPLATE']->rfw(htmlspecialchars($extInfo['EM_CONF']['version'])) . '</strong>' : $extInfo['EM_CONF']['version']) . '</td>';
			if (!$import) { // Listing extension on LOCAL server:
				// Extension Download:
				$cells[] = '<td nowrap="nowrap"><a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
					'CMD[doBackup]' => 1,
					'SET[singleDetails]' => 'backup',
					'CMD[showExt]' => $extKey
				))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:download') . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-system-extension-download') .
						'</a></td>';

				// Manual download
				$fileP = PATH_site . tx_em_Tools::typePath($extInfo['type']) . $extKey . '/doc/manual.sxw';
				$cells[] = '<td nowrap="nowrap">' .
						(tx_em_Tools::typePath($extInfo['type']) && @is_file($fileP) ?
								'<a href="' . htmlspecialchars(t3lib_div::resolveBackPath($this->parentObject->doc->backPath . '../' .
									tx_em_Tools::typePath($extInfo['type']) . $extKey . '/doc/manual.sxw')) . '" target="_blank" title="' . $GLOBALS['LANG']->getLL('listRow_local_manual') . '">' .
										t3lib_iconWorks::getSpriteIcon('actions-system-extension-documentation') . '</a>' : '') .
						'</td>';

				// Double installation (inclusion of an extension in more than one of system, global or local scopes)
				$doubleInstall = '';
				if (strlen($extInfo['doubleInstall']) > 1) {
					// Separate the "SL" et al. string into an array and replace L by Local, G by Global etc.
					$doubleInstallations = str_replace(
						array('S', 'G', 'L'),
						array(
							$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:sysext'),
							$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:globalext'),
							$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:localext')
						),
						str_split($extInfo['doubleInstall'])
					);
					// Last extension is the one actually used
					$usedExtension = array_pop($doubleInstallations);
					// Next extension is overridden
					$overriddenExtensions = array_pop($doubleInstallations);
					// If the array is not yet empty, the extension is actually installed 3 times (SGL)
					if (count($doubleInstallations) > 0) {
						$lastExtension = array_pop($doubleInstallations);
						$overriddenExtensions .= ' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:and') . ' ' . $lastExtension;
					}
					$doubleInstallTitle = sprintf(
						$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:double_inclusion'),
						$usedExtension,
						$overriddenExtensions
					);
					$doubleInstall = ' <strong><abbr title="' . $doubleInstallTitle . '">' . $GLOBALS['TBE_TEMPLATE']->rfw($extInfo['doubleInstall']) . '</abbr></strong>';
				}
				$cells[] = '<td nowrap="nowrap">' . $this->parentObject->typeLabels[$extInfo['type']] . $doubleInstall . '</td>';
			} else { // Listing extensions from REMOTE repository:
				$inst_curVer = $inst_list[$extKey]['EM_CONF']['version'];
				if (isset($inst_list[$extKey])) {
					if ($verDiff) {
						$inst_curVer = '<strong>' . $GLOBALS['TBE_TEMPLATE']->rfw($inst_curVer) . '</strong>';
					}
				}
				$cells[] = '<td nowrap="nowrap">' . t3lib_befunc::date($extInfo['EM_CONF']['lastuploaddate']) . '</td>';
				$cells[] = '<td nowrap="nowrap">' . htmlspecialchars(t3lib_div::fixed_lgd_cs($extInfo['EM_CONF']['author'], $GLOBALS['BE_USER']->uc[titleLen])) . '</td>';
				$cells[] = '<td nowrap="nowrap">' . $inst_curVer . '</td>';
				$cells[] = '<td nowrap="nowrap">' . $this->parentObject->typeLabels[$inst_list[$extKey]['type']] . (strlen($inst_list[$extKey]['doubleInstall']) > 1 ? '<strong> ' . $GLOBALS['TBE_TEMPLATE']->rfw($inst_list[$extKey]['doubleInstall']) . '</strong>' : '') . '</td>';
				$cells[] = '<td nowrap="nowrap">' . ($extInfo['downloadcounter_all'] ? $extInfo['downloadcounter_all'] : '&nbsp;&nbsp;') . '/' . ($extInfo['downloadcounter'] ? $extInfo['downloadcounter'] : '&nbsp;') . '</td>';
			}
			$cells[] = '<td nowrap="nowrap" class="extstate" style="background-color:' . $this->parentObject->stateColors[$extInfo['EM_CONF']['state']] . ';">' . $this->parentObject->states[$extInfo['EM_CONF']['state']] . '</td>';
		}

		// show a different background through a different class for insecure (-1) extensions,
		// for unreviewed (0) and reviewed extensions (1), just use the regular class
		if ($this->parentObject->xmlhandler->getReviewState($extKey, $extInfo['EM_CONF']['version']) < 0) {
			$bgclass = ' class="unsupported-ext"';
		} else {
			$bgclass = ' class="' . ($bgColorClass ? $bgColorClass : 'em-listbg1') . '"';
		}

		return '
			<tr' . $bgclass . '>
				' . implode('
				', $cells) . '
			</tr>';
	}


	/**
	 *  Displays a list of extensions where a newer version is available
	 *  in the TER than the one that is installed right now
	 *  integrated from the extension "ter_update_check" for TYPO3 4.2 by Christian Welzel
	 *
	 * @return string
	 */
	function showExtensionsToUpdate() {
		global $LANG;
		$extList = $this->getInstalledExtensions();

		$content = '<table border="0" cellpadding="2" cellspacing="1">' .
				'<tr class="t3-row-header">' .
				'<td></td>' .
				'<td>' . $LANG->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:tab_mod_name') . '</td>' .
				'<td>' . $LANG->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:tab_mod_key') . '</td>' .
				'<td>' . $LANG->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:tab_mod_loc_ver') . '</td>' .
				'<td>' . $LANG->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:tab_mod_rem_ver') . '</td>' .
				'<td>' . $LANG->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:tab_mod_location') . '</td>' .
				'<td>' . $LANG->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:tab_mod_comment') . '</td>' .
				'</tr>';

		foreach ($extList[0] as $name => $data) {
			$this->parentObject->xmlhandler->searchExtensionsXMLExact($name, '', '', TRUE, TRUE);
			if (!is_array($this->parentObject->xmlhandler->extensionsXML[$name])) {
				continue;
			}

			$v = $this->parentObject->xmlhandler->extensionsXML[$name]['versions'];
			$versions = array_keys($v);
			natsort($versions);
			$lastversion = end($versions);

			if ((t3lib_extMgm::isLoaded($name) || $this->parentObject->MOD_SETTINGS['display_installed']) &&
					($data['EM_CONF']['shy'] == 0 || $this->parentObject->MOD_SETTINGS['display_shy']) &&
					tx_em_Tools::versionDifference($lastversion, $data['EM_CONF']['version'], 1)) {

				$imgInfo = @getImageSize(tx_em_Tools::getExtPath($name, $data['type']) . '/ext_icon.gif');
				if (is_array($imgInfo)) {
					$icon = '<img src="' . $GLOBALS['BACK_PATH'] . tx_em_Tools::typeRelPath($data['type']) . $name . '/ext_icon.gif' . '" ' . $imgInfo[3] . ' alt="" />';
				} elseif ($data['_ICON']) { //TODO: see if this can be removed, seems to be wrong in this context
					$icon = $data['_ICON'];
				} else {
					$icon = '<img src="clear.gif" width="1" height="1" alt="" />';
				}
				$comment = '<table cellpadding="0" cellspacing="0" width="100%">';
				foreach ($versions as $vk) {
					$va = & $v[$vk];
					if (t3lib_div::int_from_ver($vk) <= t3lib_div::int_from_ver($data['EM_CONF']['version'])) {
						continue;
					}
					$comment .= '<tr><td valign="top" style="padding-right:2px;border-bottom:1px dotted gray">' . $vk . '</td>' . '<td valign="top" style="border-bottom:1px dotted gray">' . nl2br($va[uploadcomment]) . '</td></tr>';
				}
				$comment .= '</table>';

				$serverMD5Array = $this->parentObject->extensionDetails->serverExtensionMD5array($name, $data);
				if (is_array($serverMD5Array)) {
					ksort($serverMD5Array);
				}
				$currentMD5Array = unserialize($data['EM_CONF']['_md5_values_when_last_written']);
				if (is_array($currentMD5Array)) {
					@ksort($currentMD5Array);
				}
				$warn = '';
				if (strcmp(serialize($currentMD5Array), serialize($serverMD5Array))) {
					$warn = '<tr class="bgColor4" style="color:red"><td colspan="7">' . $GLOBALS['TBE_TEMPLATE']->rfw('<br /><strong>' . $name . ': ' . $LANG->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:msg_warn_diff') . '</strong>') . '</td></tr>' . LF;
					if ($this->parentObject->MOD_SETTINGS['display_files'] == 1) {
						$affectedFiles = tx_em_Tools::findMD5ArrayDiff($serverMD5Array, $currentMD5Array);
						if (count($affectedFiles)) {
							$warn .= '<tr class="bgColor4"><td colspan="7"><strong>' . $LANG->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:msg_modified') . '</strong><br />' . $GLOBALS['TBE_TEMPLATE']->rfw(implode('<br />', $affectedFiles)) . '</td></tr>' . LF;
						}
					}
				}
				//TODO: $extInfo is unknown in this context
				$content .= '<tr class="bgColor4"><td valign="top">' . $icon . '</td>' .
						'<td valign="top">' . ($data['EM_CONF']['state'] == 'excludeFromUpdates'
							? '<span style="color:#cf7307">' . $data['EM_CONF']['title'] . ' ' . $LANG->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:write_protected') . '</span>'
							: '<a href="' . t3lib_div::linkThisScript(array(
								'CMD[importExtInfo]' => $name
								)) . '">' . $data[EM_CONF][title] . '</a>') . '</td>' .
						'<td valign="top">' . $name . '</td>' .
						'<td valign="top" align="right">' . $data[EM_CONF][version] . '</td>' .
						'<td valign="top" align="right">' . $lastversion . '</td>' .
						'<td valign="top" nowrap="nowrap">' . $this->parentObject->typeLabels[$data['type']] . (strlen($data['doubleInstall']) > 1 ? '<strong> ' . $GLOBALS['TBE_TEMPLATE']->rfw($extInfo['doubleInstall']) . '</strong>' : '') . '</td>' .
						'<td valign="top">' . $comment . '</td></tr>' . LF .
						$warn .
						'<tr class="bgColor4"><td colspan="7"><hr style="margin:0px" /></td></tr>' . LF;
			}
		}

		return $content . '</table><br />';
	}

	/**
	 * Maps remote extensions information into $cat/$list arrays for listing
	 *
	 * @param	boolean		If set the info in the internal extensionsXML array will be unset before returning the result.
	 * @return	array		List array and category index as key 0 / 1 in an array.
	 */
	function prepareImportExtList($unsetProc = false) {
		$list = array();
		$cat = $this->parentObject->defaultCategories;
		$filepath = $this->parentObject->getMirrorURL();

		foreach ($this->parentObject->xmlhandler->extensionsXML as $extKey => $data) {
			$GLOBALS['LANG']->csConvObj->convarray($data, 'utf-8', $GLOBALS['LANG']->charSet); // is there a better place for conversion?
			$list[$extKey]['type'] = '_';
			$version = array_keys($data['versions']);
			$extPath = t3lib_div::strtolower($extKey);
			$list[$extKey]['_ICON'] = '<img alt="" src="' . $filepath . $extPath{0} . '/' . $extPath{1} . '/' . $extPath . '_' . end($version) . '.gif" />';
			$list[$extKey]['downloadcounter'] = $data['downloadcounter'];

			foreach (array_keys($data['versions']) as $version) {
				$list[$extKey]['versions'][$version]['downloadcounter'] = $data['versions'][$version]['downloadcounter'];

				$list[$extKey]['versions'][$version]['EM_CONF'] = array(
					'version' => $version,
					'title' => $data['versions'][$version]['title'],
					'description' => $data['versions'][$version]['description'],
					'category' => $data['versions'][$version]['category'],
					'constraints' => $data['versions'][$version]['dependencies'],
					'state' => $data['versions'][$version]['state'],
					'reviewstate' => $data['versions'][$version]['reviewstate'],
					'lastuploaddate' => $data['versions'][$version]['lastuploaddate'],
					'author' => $data['versions'][$version]['authorname'],
					'author_email' => $data['versions'][$version]['authoremail'],
					'author_company' => $data['versions'][$version]['authorcompany'],
				);
			}
			tx_em_Tools::setCat($cat, $list[$extKey]['versions'][$version], $extKey);
			if ($unsetProc) {
				unset($this->parentObject->xmlhandler->extensionsXML[$extKey]);
			}
		}

		return array($list, $cat);
	}


	/**
	 * Adds extension to extension list and returns new list. If -1 is returned, an error happend.
	 * Checks dependencies etc.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array - information about installed extensions
	 * @return	string		New list of installed extensions or -1 if error
	 * @see showExtDetails()
	 */
	function addExtToList($extKey, $instExtInfo) {
		global $TYPO3_LOADED_EXT;

		// ext_emconf.php information:
		$conf = $instExtInfo[$extKey]['EM_CONF'];

		// Get list of installed extensions and add this one.
		$listArr = array_keys($TYPO3_LOADED_EXT);
		if ($conf['priority'] == 'top') {
			array_unshift($listArr, $extKey);
		} else {
			$listArr[] = $extKey;
		}

		// Manage other circumstances:
		$listArr = tx_em_Tools::managesPriorities($listArr, $instExtInfo);
		$listArr = $this->removeRequiredExtFromListArr($listArr);

		// Implode unique list of extensions to load and return:
		$list = implode(',', array_unique($listArr));

		return $list;
	}

	/**
	 * Remove extension key from the list of currently installed extensions and return list. If -1 is returned, an error happend.
	 * Checks dependencies etc.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array - information about installed extensions
	 * @return	string		New list of installed extensions or -1 if error
	 * @see showExtDetails()
	 */
	function removeExtFromList($extKey, $instExtInfo) {
		global $TYPO3_LOADED_EXT;

		// Initialize:
		$depList = array();
		$listArr = array_keys($TYPO3_LOADED_EXT);

		// Traverse all installed extensions to check if any of them have this extension as dependency since if that is the case it will not work out!
		foreach ($listArr as $k => $ext) {
			if ($instExtInfo[$ext]['EM_CONF']['dependencies']) {
				$dep = t3lib_div::trimExplode(',', $instExtInfo[$ext]['EM_CONF']['dependencies'], 1);
				if (in_array($extKey, $dep)) {
					$depList[] = $ext;
				}
			}
			if (!strcmp($ext, $extKey)) {
				unset($listArr[$k]);
			}
		}

		// Returns either error or the new list
		if (count($depList)) {
			$msg = sprintf($GLOBALS['LANG']->getLL('removeExtFromList_dependency'),
				implode(', ', $depList)
			);
			$this->parentObject->content .= $this->parentObject->doc->section($GLOBALS['LANG']->getLL('removeExtFromList_dependency_error'), $msg, 0, 1, 2);
			return -1;
		} else {
			$listArr = $this->removeRequiredExtFromListArr($listArr);
			$list = implode(',', array_unique($listArr));
			return $list;
		}
	}

	/**
	 * This removes any required extensions from the $listArr - they should NOT be added to the common extension list, because they are found already in "requiredExt" list
	 *
	 * @param	array		Array of extension keys as values
	 * @return	array		Modified array
	 * @see removeExtFromList(), addExtToList()
	 */
	function removeRequiredExtFromListArr($listArr) {
		$requiredExtensions = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt'], 1);
		foreach ($listArr as $k => $ext) {
			if (in_array($ext, $requiredExtensions) || !strcmp($ext, '_CACHEFILE')) {
				unset($listArr[$k]);
			}
		}
		return $listArr;
	}


}

?>