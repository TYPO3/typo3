<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * version module cm1
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$LANG->includeLLFile("EXT:version/cm1/locallang.php");
#include ("locallang.php");
require_once (PATH_t3lib."class.t3lib_scbase.php");
	// ....(But no access check here...)
	// DEFAULT initialization of a module [END]





require_once(PATH_t3lib.'class.t3lib_diff.php');



class tx_version_cm1 extends t3lib_SCbase {

	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

			// Draw the header.
		$this->doc = t3lib_div::makeInstance("mediumDoc");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form='<form action="" method="post">';


		$this->uid = intval(t3lib_div::_GP('uid'));
		$this->table = t3lib_div::_GP('table');
		$record = t3lib_BEfunc::getRecord($this->table,$this->uid);

		if (is_array($record) && $TCA[$this->table]['ctrl']['versioning'])	{
				// Access check!
				// The page will show only if there is a valid page and if this page may be viewed by the user
			$this->pageinfo = t3lib_BEfunc::readPageAccess($record['pid'],$this->perms_clause);
			$access = is_array($this->pageinfo) ? 1 : 0;

			if (($record['pid'] && $access) || ($BE_USER->user["admin"] && !$record['pid']))	{

					// JavaScript
				$this->doc->JScode = '
					<script language="javascript" type="text/javascript">
						script_ended = 0;
						function jumpToUrl(URL)	{
							document.location = URL;
						}
					</script>
				';

#				$headerSection = $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"])."<br/>".$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.path").": ".t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],50);

				$this->content.=$this->doc->startPage($LANG->getLL("title"));
				$this->content.=$this->doc->header($LANG->getLL("title"));
#				$this->content.=$this->doc->spacer(5);
#				$this->content.=$this->doc->section('',$headerSection);
#				$this->content.=$this->doc->divider(5);



				// Render content:
				$this->moduleContent();

				// ShortCut
				if ($BE_USER->mayMakeShortcut())	{
					$this->content.=$this->doc->spacer(20).$this->doc->section("",$this->doc->makeShortcutIcon("id",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
				}
			}

			$this->content.=$this->doc->spacer(10);
		}
	}
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	function moduleContent()	{
		global $TCA;

			// Diffing:
		$diff_1 = t3lib_div::_POST('diff_1');
		$diff_2 = t3lib_div::_POST('diff_2');
		if (t3lib_div::_POST('do_diff'))	{
			$content='';
			$content.='<h3>DIFFING:</h3>';
			if ($diff_1 && $diff_2)	{
				$diff_1_record = t3lib_BEfunc::getRecord($this->table, $diff_1);
				$diff_2_record = t3lib_BEfunc::getRecord($this->table, $diff_2);

				if (is_array($diff_1_record) && is_array($diff_2_record))	{
					t3lib_div::loadTCA($this->table);
					$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');

					$tRows=array();
								$tRows[] = '
									<tr class="bgColor5 tableheader">
										<td>Fieldname:</td>
										<td width="98%">Colored diff-view:</td>
									</tr>
								';
					foreach($diff_1_record as $fN => $fV)	{
						if ($TCA[$this->table]['columns'][$fN] && $TCA[$this->table]['columns'][$fN]['config']['type']!='passthrough' && !t3lib_div::inList('t3ver_label',$fN))	{
							if (strcmp($diff_1_record[$fN],$diff_2_record[$fN]))	{

								$diffres = $t3lib_diff_Obj->makeDiffDisplay(
									t3lib_BEfunc::getProcessedValue($this->table,$fN,$diff_2_record[$fN],0,1),
									t3lib_BEfunc::getProcessedValue($this->table,$fN,$diff_1_record[$fN],0,1)
								);

								$tRows[] = '
									<tr class="bgColor4">
										<td>'.$fN.'</td>
										<td width="98%">'.$diffres.'</td>
									</tr>
								';
							}
						}
					}

					if (count($tRows)>1)	{
						$content.='<table border="0" cellpadding="1" cellspacing="1" width="100%">'.implode('',$tRows).'</table><br/><br/>';
					} else {
						$content.='Records matches completely on all editable fields!';
					}
				} else $content.='ERROR: Records could strangely not be found!';
			} else {
				$content.='ERROR: You didn\'t select two sources for diffing!';
			}
		}

			// Element:
		$record = t3lib_BEfunc::getRecord($this->table,$this->uid);
		$recordIcon = t3lib_iconWorks::getIconImage($this->table,$record,$this->doc->backPath,'class="absmiddle"');
		$recTitle = t3lib_BEfunc::getRecordTitle($this->table,$record,1);

			// Display versions:
		$content.='
			'.$recordIcon.$recTitle.'
			<form action="'.t3lib_div::getIndpEnv('REQUEST_URI').'" method="post">
			<table border="0" cellspacing="1" cellpadding="1">';
			$content.='
				<tr class="bgColor5 tableheader">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>Title</td>
					<td>UID</td>
					<td>t3ver_oid</td>
					<td>t3ver_id</td>
					<td>pid</td>
					<td>t3ver_label</td>
					<td colspan="2"><input type="submit" name="do_diff" value="Diff" /></td>
				</tr>';

		$versions = t3lib_BEfunc::selectVersionsOfRecord($this->table, $this->uid);
		foreach($versions as $row)	{
			$adminLinks = $this->adminLinks($this->table,$row);

			$content.='
				<tr class="'.($row['uid']!=$this->uid ? 'bgColor4' : 'bgColor2 tableheader').'">
					<td>'.($row['uid']!=$this->uid ? '<a href="'.$this->doc->issueCommand('&cmd['.$this->table.']['.$this->uid.'][version][swapWith]='.$row['uid'].'&cmd['.$this->table.']['.$this->uid.'][version][action]=swap').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/insert1.gif','width="14" height="14"').' alt="" title="SWAP with current" />'.
						'</a>'.(
							$this->table == 'pages' ?
							'<a href="'.$this->doc->issueCommand('&cmd['.$this->table.']['.$this->uid.'][version][action]=swap&cmd['.$this->table.']['.$this->uid.'][version][swapWith]='.$row['uid'].'&cmd['.$this->table.']['.$this->uid.'][version][swapContent]=1').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/insert2.gif','width="14" height="14"').' alt="" title="Publish page AND content!" />'.
						'</a>'.
							'<a href="'.$this->doc->issueCommand('&cmd['.$this->table.']['.$this->uid.'][version][action]=swap&cmd['.$this->table.']['.$this->uid.'][version][swapWith]='.$row['uid'].'&cmd['.$this->table.']['.$this->uid.'][version][swapContent]=ALL').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/insert4.gif','width="14" height="14"').' alt="" title="Publish page AND content! - AND ALL SUBPAGES!" />'.
						'</a>' : '') : '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/blinkarrow_left.gif','width="5" height="9"').' alt="" title="CURRENT ONLINE VERSION!"/>').'</td>
					<td>'.$adminLinks.'</td>
					<td nowrap="nowrap">'.t3lib_BEfunc::getRecordTitle($this->table,$row,1).'</td>
					<td>'.$row['uid'].'</td>
					<td>'.$row['t3ver_oid'].'</td>
					<td>'.$row['t3ver_id'].'</td>
					<td>'.$row['pid'].'</td>
					<td nowrap="nowrap"><a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit['.$this->table.']['.$row['uid'].']=edit&columnsOnly=t3ver_label',$this->doc->backPath)).'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' alt="" title="Edit"/></a>'.htmlspecialchars($row['t3ver_label']).'</td>
					<td bgcolor="green"><input type="radio" name="diff_1" value="'.$row['uid'].'"'.($diff_1==$row['uid'] ? ' checked="checked"':'').'/></td>
					<td bgcolor="red"><input type="radio" name="diff_2" value="'.$row['uid'].'"'.($diff_2==$row['uid'] ? ' checked="checked"':'').'/></td>
				</tr>';

			if ($this->table == 'pages')	{	//  && $row['uid']!=$this->uid
				$sub = $this->pageSubContent($row['uid']);

				if ($sub)	{
					$content.='
						<tr>
							<td></td>
							<td></td>
							<td colspan="6">'.$sub.'</td>
							<td colspan="2"></td>
						</tr>';
				}
			}
		}
		$content.='</table></form>';

		$this->content.=$this->doc->section('',$content,0,1);


			// Create new:
		$content='

			<form action="'.$this->doc->backPath.'tce_db.php" method="post">
			Label: <input type="text" name="cmd['.$this->table.']['.$this->uid.'][version][label]" /><br/>
			'.($this->table == 'pages' ? '<select name="cmd['.$this->table.']['.$this->uid.'][version][treeLevels]">
				<option value="0">Page + content</option>
				<option value="1">1 level</option>
				<option value="2">2 levels</option>
				<option value="3">3 levels</option>
				<option value="4">4 levels</option>
				<option value="-1">Just page record</option>
			</select>' : '').'
			<br/><input type="hidden" name="cmd['.$this->table.']['.$this->uid.'][version][action]" value="new" />
			<input type="hidden" name="prErr" value="1" />
			<input type="hidden" name="redirect" value="'.t3lib_div::getIndpEnv('REQUEST_URI').'" />
			<input type="submit" name="_" value="Create new version" />

			</form>

		';

		$this->content.=$this->doc->spacer(15);
		$this->content.=$this->doc->section("Create new version",$content,0,1);

	}

	function pageSubContent($pid,$c=0)	{
		global $TCA;

		$tableNames = array_keys($TCA);
		foreach($tableNames as $tN)	{
				// Basically list ALL tables - not only those being copied might be found!
			#if ($TCA[$tN]['ctrl']['versioning_followPages'] || $tN=='pages')	{
				$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $tN, 'pid='.intval($pid).t3lib_BEfunc::deleteClause($tN), '', ($TCA[$tN]['ctrl']['sortby'] ? $TCA[$tN]['ctrl']['sortby'] : ''));

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($mres))	{
					$content.='
						<tr>
							<td colspan="4" class="'.($TCA[$tN]['ctrl']['versioning_followPages'] ? 'bgColor6' : ($tN=='pages' ? 'bgColor5' : 'bgColor-10')).'"'.(!$TCA[$tN]['ctrl']['versioning_followPages'] && $tN!='pages' ? ' style="color: #666666; font-style:italic;"':'').'>'.$tN.'</td>
						</tr>';
					while ($subrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))	{
						$ownVer = $this->lookForOwnVersions($tN,$subrow['uid']);
						$content.='
							<tr>
								<td>'.$this->adminLinks($tN,$subrow).'</td>
								<td>'.$subrow['uid'].'</td>
								'.($ownVer>1 ? '<td style="font-weight: bold; background-color: yellow;"><a href="index.php?table='.rawurlencode($tN).'&uid='.$subrow['uid'].'">'.($ownVer-1).'</a></td>' : '<td></td>').'
								<td width="98%">'.t3lib_BEfunc::getRecordTitle($tN,$subrow,1).'</td>
							</tr>';

						if ($tN == 'pages' && $c<10)	{
							$sub = $this->pageSubContent($subrow['uid'],$c+1);

							if ($sub)	{
								$content.='
									<tr>
										<td></td>
										<td></td>
										<td></td>
										<td width="98%">'.$sub.'</td>
									</tr>';
							}
						}
					}
				}
			#}
		}

		return $content ? '<table border="1" cellpadding="1" cellspacing="0" width="100%">'.$content.'</table>' : '';
	}

	function lookForOwnVersions($table,$uid)	{
		global $TCA;

		$versions = t3lib_BEfunc::selectVersionsOfRecord($table, $uid, 'uid');
		if (is_array($versions))	{
			return count($versions);
		}
		return FALSE;
	}

	function adminLinks($table,$row)	{
		global $BE_USER;

		$adminLink = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit['.$table.']['.$row['uid'].']=edit',$this->doc->backPath)).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' alt="" title="Edit"/>'.
						'</a>';

		if ($table == 'pages')	{

				// If another page module was specified, replace the default Page module with the new one
			$newPageModule = trim($BE_USER->getTSConfigVal('options.overridePageModule'));
			$pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

				// Perform some acccess checks:
			$a_wl = $BE_USER->check('modules','web_list');
			$a_wp = t3lib_extMgm::isLoaded('cms') && $BE_USER->check('modules',$pageModule);

			$adminLink.='<a href="#" onclick="top.loadEditId('.$row['uid'].');top.goToModule(\''.$pageModule.'\'); return false;">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('cms').'layout/layout.gif','width="14" height="12"').' title="" alt="" />'.
						'</a>';
			$adminLink.='<a href="#" onclick="top.loadEditId('.$row['uid'].');top.goToModule(\'web_list\'); return false;">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'mod/web/list/list.gif','width="14" height="12"').' title="" alt="" />'.
						'</a>';

				// "View page" icon is added:
			$adminLink.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($row['uid'],$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($row['uid']))).'">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="" alt="" />'.
				'</a>';
		} else {
			if ($row['pid']==-1)	{
				$getVars = '&ADMCMD_vPrev['.rawurlencode($table.':'.$row['t3ver_oid']).']='.$row['uid'];

					// "View page" icon is added:
				$adminLink.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($row['_REAL_PID'],$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($row['_REAL_PID']),'','',$getVars)).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="" alt="" />'.
					'</a>';
			}
		}

		return $adminLink;
	}
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/version/cm1/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/version/cm1/index.php"]);
}




// Make instance:
$SOBE = t3lib_div::makeInstance("tx_version_cm1");
$SOBE->init();


$SOBE->main();
$SOBE->printContent();

?>