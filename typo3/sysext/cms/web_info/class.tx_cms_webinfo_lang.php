<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains a class with functions for page related overview of translations.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Class for displaying translation status of pages in the tree.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_cms
 */
class tx_cms_webinfo_lang extends t3lib_extobjbase {

	/**
	 * Returns the menu array
	 *
	 * @return	array
	 */
	function modMenu()	{
		global $LANG;

		$menuArray = array (
			'depth' => array(
				0 => $LANG->getLL('depth_0'),
				1 => $LANG->getLL('depth_1'),
				2 => $LANG->getLL('depth_2'),
				3 => $LANG->getLL('depth_3'),
				999 => $LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_infi'),
			),
		);

			// Languages:
		$lang = $this->getSystemLanguages();
		$menuArray['lang']=array(
			0 => '[All]'
		);
		foreach($lang as $langRec)	{
			$menuArray['lang'][$langRec['uid']] = $langRec['title'];
		}

		return $menuArray;
	}

	/**
	 * MAIN function for page information of localization
	 *
	 * @return	string		Output HTML for the module.
	 */
	function main()	{
		global $BACK_PATH,$LANG,$SOBE;

		$theOutput = $this->pObj->doc->header($GLOBALS['LANG']->getLL('lang_title'));

		if ($this->pObj->id) {
				// Depth selector:
			$h_func = t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[depth]',$this->pObj->MOD_SETTINGS['depth'],$this->pObj->MOD_MENU['depth'],'index.php');
			$h_func.= t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[lang]',$this->pObj->MOD_SETTINGS['lang'],$this->pObj->MOD_MENU['lang'],'index.php');
			$theOutput.= $h_func;

				// Add CSH:
			$theOutput .= t3lib_BEfunc::cshItem('_MOD_web_info', 'lang', $GLOBALS['BACK_PATH'], '|<br />');

				// Showing the tree:
				// Initialize starting point of page tree:
			$treeStartingPoint = intval($this->pObj->id);
			$treeStartingRecord = t3lib_BEfunc::getRecordWSOL('pages', $treeStartingPoint);
			$depth = $this->pObj->MOD_SETTINGS['depth'];

				// Initialize tree object:
			$tree = t3lib_div::makeInstance('t3lib_pageTree');
			$tree->init('AND '.$GLOBALS['BE_USER']->getPagePermsClause(1));
			$tree->addField('l18n_cfg');

				// Creating top icon; the current page
			$HTML = t3lib_iconWorks::getSpriteIconForRecord('pages', $treeStartingRecord);
			$tree->tree[] = array(
				'row' => $treeStartingRecord,
				'HTML'=>$HTML
			);

				// Create the tree from starting point:
			if ($depth)	$tree->getTree($treeStartingPoint, $depth, '');

				// Render information table:
			$theOutput.= $this->renderL10nTable($tree);
		}

		return $theOutput;
	}

	/**
	 * Rendering the localization information table.
	 *
	 * @param	array		The Page tree data
	 * @return	string		HTML for the localization information table.
	 */
	function renderL10nTable(&$tree)	{
		global $LANG;

			// System languages retrieved:
		$languages = $this->getSystemLanguages();

			// Title length:
		$titleLen = $GLOBALS['BE_USER']->uc['titleLen'];

			// Put together the TREE:
		$output = '';
		$newOL_js = array();
		$langRecUids = array();
		foreach($tree->tree as $data)	{
			$tCells = array();
			$langRecUids[0][] = $data['row']['uid'];

				// Page icons / titles etc.
			$tCells[] = '<td'.($data['row']['_CSSCLASS'] ? ' class="'.$data['row']['_CSSCLASS'].'"' : '').'>'.
							$data['HTML'].
							htmlspecialchars(t3lib_div::fixed_lgd_cs($data['row']['title'],$titleLen)).
							(strcmp($data['row']['nav_title'],'') ? ' [Nav: <em>'.htmlspecialchars(t3lib_div::fixed_lgd_cs($data['row']['nav_title'],$titleLen)).'</em>]' : '').
							'</td>';

				// DEFAULT language:
				// "View page" link is created:
			$viewPageLink= '<a href="#" onclick="'.
					htmlspecialchars(t3lib_BEfunc::viewOnClick($data['row']['uid'],$GLOBALS['BACK_PATH'],'','','','&L=###LANG_UID###')).'" title="' . $LANG->getLL('lang_renderl10n_viewPage', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-view') .
					'</a>';
			$status = $data['row']['l18n_cfg']&1 ? 'c-blocked' : 'c-ok';

				// Create links:
			$info = '';
			$editUid = $data['row']['uid'];
			$params = '&edit[pages]['.$editUid.']=edit';
			$info.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'" title="' . $LANG->getLL('lang_renderl10n_editDefaultLanguagePage', TRUE) . '">'.
						t3lib_iconWorks::getSpriteIcon('actions-document-open') .
					'</a>';
			$info.= '<a href="#" onclick="'.htmlspecialchars('top.loadEditId('.intval($data['row']['uid']).',"&SET[language]=0"); return false;').'" title="' . $LANG->getLL('lang_renderl10n_editPage', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-page-open') .
					'</a>';
			$info.= str_replace('###LANG_UID###','0',$viewPageLink);

			$info.= '&nbsp;';
			$info.= $data['row']['l18n_cfg']&1 ? '<span title="'.$LANG->sL('LLL:EXT:cms/locallang_tca.php:pages.l18n_cfg.I.1','1').'">D</span>' : '&nbsp;';
			$info.= t3lib_div::hideIfNotTranslated($data['row']['l18n_cfg']) ? '<span title="'.$LANG->sL('LLL:EXT:cms/locallang_tca.php:pages.l18n_cfg.I.2','1').'">N</span>' : '&nbsp;';

				// Put into cell:
			$tCells[] = '<td class="'.$status.' c-leftLine">'.$info.'</td>';
			$tCells[] = '<td class="'.$status.'" title="'.$LANG->getLL('lang_renderl10n_CEcount','1').'" align="center">'.$this->getContentElementCount($data['row']['uid'],0).'</td>';

			$modSharedTSconfig = t3lib_BEfunc::getModTSconfig($data['row']['uid'], 'mod.SHARED');
			$disableLanguages = isset($modSharedTSconfig['properties']['disableLanguages']) ? t3lib_div::trimExplode(',', $modSharedTSconfig['properties']['disableLanguages'], 1) : array();

				// Traverse system languages:
			foreach($languages as $langRow)	{
				if ($this->pObj->MOD_SETTINGS['lang']==0 || (int)$this->pObj->MOD_SETTINGS['lang']===(int)$langRow['uid'])	{
					$row = $this->getLangStatus($data['row']['uid'], $langRow['uid']);
					$info = '';

					if (is_array($row))	{
						$langRecUids[$langRow['uid']][] = $row['uid'];
						$status = $row['_HIDDEN'] ? (t3lib_div::hideIfNotTranslated($data['row']['l18n_cfg']) || $data['row']['l18n_cfg']&1 ? 'c-blocked' : 'c-fallback') : 'c-ok';
						$icon = t3lib_iconWorks::getSpriteIconForRecord(
							'pages_language_overlay',
							$row,
							array('class' => 'c-recIcon')
						);

						$info = $icon.
									htmlspecialchars(t3lib_div::fixed_lgd_cs($row['title'],$titleLen)).
									(strcmp($row['nav_title'],'') ? ' [Nav: <em>'.htmlspecialchars(t3lib_div::fixed_lgd_cs($row['nav_title'],$titleLen)).'</em>]' : '').
									($row['_COUNT']>1 ? '<div>'.$LANG->getLL('lang_renderl10n_badThingThereAre','1').'</div>':'');
						$tCells[] = '<td class="'.$status.' c-leftLine">'.
										$info.
										'</td>';

							// Edit whole record:
						$info = '';
						$editUid = $row['uid'];
						$params = '&edit[pages_language_overlay]['.$editUid.']=edit';
						$info.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'" title="' . $LANG->getLL('lang_renderl10n_editLanguageOverlayRecord', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-document-open') .
								'</a>';

						$info.= '<a href="#" onclick="'.htmlspecialchars('top.loadEditId('.intval($data['row']['uid']).',"&SET[language]='.$langRow['uid'].'"); return false;').'" title="' . $LANG->getLL('lang_renderl10n_editPageLang', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-page-open') .
								'</a>';
						$info.= str_replace('###LANG_UID###',$langRow['uid'],$viewPageLink);

						$tCells[] = '<td class="'.$status.'">'.$info.'</td>';
						$tCells[] = '<td class="'.$status.'" title="'.$LANG->getLL('lang_renderl10n_CEcount','1').'" align="center">'.$this->getContentElementCount($data['row']['uid'],$langRow['uid']).'</td>';
					} else {
						if (in_array($langRow['uid'], $disableLanguages)) {
								// Language has been disabled for this page
							$status = 'c-blocked';
							$info = '';
						} else {
							$status = t3lib_div::hideIfNotTranslated($data['row']['l18n_cfg']) || $data['row']['l18n_cfg']&1 ? 'c-blocked' : 'c-fallback';
							$info = '<input type="checkbox" name="newOL['.$langRow['uid'].']['.$data['row']['uid'].']" value="1" />';
							$newOL_js[$langRow['uid']].= '
								+(document.webinfoForm[\'newOL['.$langRow['uid'].']['.$data['row']['uid'].']\'].checked ? \'&edit[pages_language_overlay]['.$data['row']['uid'].']=new\' : \'\')
							';
						}

						$tCells[] = '<td class="'.$status.' c-leftLine">&nbsp;</td>';
						$tCells[] = '<td class="'.$status.'">&nbsp;</td>';
						$tCells[] = '<td class="'.$status.'">'.$info.'</td>';
					}
				}
			}

			$output.= '
				<tr class="bgColor4">
					'.implode('
					',$tCells).'
				</tr>';
		}

			// Put together HEADER:
		$tCells = array();
		$tCells[] = '<td>'.$LANG->getLL('lang_renderl10n_page','1').':</td>';

		if (is_array($langRecUids[0]))	{
			$params = '&edit[pages]['.implode(',',$langRecUids[0]).']=edit&columnsOnly=title,nav_title,l18n_cfg,hidden';
			$editIco = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'" title="' . $LANG->getLL('lang_renderl10n_editPageProperties', TRUE) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-new') .
				'</a>';
		} else $editIco = '';
		$tCells[] = '<td class="c-leftLine" colspan="2">'.
					$LANG->getLL('lang_renderl10n_default','1').':'.
					$editIco.
					'</td>';

		foreach($languages as $langRow)	{
			if ($this->pObj->MOD_SETTINGS['lang']==0 || (int)$this->pObj->MOD_SETTINGS['lang']===(int)$langRow['uid'])	{
					// Title:
				$tCells[] = '<td class="c-leftLine">'.htmlspecialchars($langRow['title']).'</td>';

					// Edit language overlay records:
				if (is_array($langRecUids[$langRow['uid']]))	{
					$params = '&edit[pages_language_overlay]['.implode(',',$langRecUids[$langRow['uid']]).']=edit&columnsOnly=title,nav_title,hidden';
					$tCells[] = '<td><a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'" title="' . $LANG->getLL('lang_renderl10n_editLangOverlays', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-open') .
						'</a></td>';
				} else {
					$tCells[] = '<td>&nbsp;</td>';
				}

					// Create new overlay records:
				$params = "'".$newOL_js[$langRow['uid']]."+'&columnsOnly=title,hidden,sys_language_uid&defVals[pages_language_overlay][sys_language_uid]=".$langRow['uid'];
				$tCells[] = '<td><a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'])).'" title="' . $LANG->getLL('lang_getlangsta_createNewTranslationHeaders', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-new') .
					'</a></td>';
			}
		}

		$output = '
			<tr class="t3-row-header">
				'.implode('
				',$tCells).'
			</tr>'.$output;

		$output = '

		<table border="0" cellspacing="0" cellpadding="0" id="langTable" class="typo3-dblist">' . $output . '
		</table>';

		return $output;
	}

	/**
	 * Selects all system languages (from sys_language)
	 *
	 * @return	array		System language records in an array.
	 */
	function getSystemLanguages()	{
		if (!$GLOBALS['BE_USER']->user['admin'] &&
			strlen($GLOBALS['BE_USER']->groupData['allowed_languages'])) {

			$allowed_languages = array_flip(explode(',', $GLOBALS['BE_USER']->groupData['allowed_languages']));
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'sys_language',
			'1=1'.t3lib_BEfunc::deleteClause('sys_language')
		);

		$outputArray = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if (is_array($allowed_languages) && count($allowed_languages)) {
				if (isset($allowed_languages[$row['uid']])) {
					$outputArray[] = $row;
				}
			}
			else {
				$outputArray[] = $row;
			}
		}

		return $outputArray;
	}

	/**
	 * Get an alternative language record for a specific page / language
	 *
	 * @param	integer		Page ID to look up for.
	 * @param	integer		Language UID to select for.
	 * @return	array		pages_languages_overlay record
	 */
	function getLangStatus($pageId, $langId)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'pages_language_overlay',
			'pid='.intval($pageId).
				' AND sys_language_uid='.intval($langId).
				t3lib_BEfunc::deleteClause('pages_language_overlay').
				t3lib_BEfunc::versioningPlaceholderClause('pages_language_overlay')
		);

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		t3lib_BEfunc::workspaceOL('pages_language_overlay',$row);
		if (is_array($row))	{
			$row['_COUNT'] = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
			$row['_HIDDEN'] = $row['hidden'] ||
							(intval($row['endtime']) > 0 && intval($row['endtime']) < $GLOBALS['EXEC_TIME']) ||
							($GLOBALS['EXEC_TIME'] < intval($row['starttime']));
		}

		return $row;
	}

	/**
	 * Counting content elements for a single language on a page.
	 *
	 * @param	integer		Page id to select for.
	 * @param	integer		Sys language uid
	 * @return	integer		Number of content elements from the PID where the language is set to a certain value.
	 */
	function getContentElementCount($pageId,$sysLang)	{
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'uid',
			'tt_content',
			'pid=' . intval($pageId) .
				' AND sys_language_uid=' . intval($sysLang) .
				t3lib_BEfunc::deleteClause('tt_content') .
				t3lib_BEfunc::versioningPlaceholderClause('tt_content')
		);
		return $count ? $count : '-';
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cms/web_info/class.tx_cms_webinfo_lang.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cms/web_info/class.tx_cms_webinfo_lang.php']);
}

?>