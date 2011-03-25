<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasper@typo3.com)
*  (c) 2005-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * User defined content for htmlArea RTE
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */

class tx_rtehtmlarea_user {
	var $content;
	var $modData;
	var $siteUrl;

	/**
	 * document template object
	 *
	 * @var template
	 */
	var $doc;
	var $editorNo;

	/**
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER, $LANG, $BACK_PATH;

		$this->editorNo = t3lib_div::_GP('editorNo');

		$this->siteUrl = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;

		$this->doc->bodyTagAdditions = 'onload="Init();"';
		$this->doc->form = '
	<form action="" id="process" name="process" method="post">
		<input type="hidden" name="processContent" value="" />
		<input type="hidden" name="returnUrl" value="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'" />
		';

		$JScode = '
			var plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("UserElements");
			var HTMLArea = window.parent.HTMLArea;
			var editor = plugin.editor;

			function Init() {
			};
			function insertHTML(content,noHide) {
				plugin.restoreSelection();
				editor.insertHTML(content);
				if(!noHide) plugin.close();
			};
			function wrapHTML(wrap1,wrap2,noHide) {
				plugin.restoreSelection();
				if(editor.hasSelectedText()) {
					editor.surroundHTML(wrap1,wrap2);
				} else {
					alert('.$LANG->JScharCode($LANG->getLL('noTextSelection')).');
				}
				if(!noHide) plugin.close();
			};
			function processSelection(script) {
				plugin.restoreSelection();
				document.process.action = script;
				document.process.processContent.value = editor.getSelectedHTML();
				document.process.submit();
			};
			function jumpToUrl(URL)	{
				var RTEtsConfigParams = "&RTEtsConfigParams='.rawurlencode(t3lib_div::_GP('RTEtsConfigParams')).'";
				var editorNo = "&editorNo=' . rawurlencode($this->editorNo) . '";
				theLocation = "'.t3lib_div::getIndpEnv('SCRIPT_NAME').'"+URL+RTEtsConfigParams+editorNo;
				window.location.href = theLocation;
			}
		';

		$this->doc->JScode = $this->doc->wrapScriptTags($JScode);

		$this->modData = $BE_USER->getModuleData('user.php','ses');
		if (t3lib_div::_GP('OC_key'))	{
			$parts = explode('|',t3lib_div::_GP('OC_key'));
			$this->modData['openKeys'][$parts[1]] = $parts[0]=='O' ? 1 : 0;
			$BE_USER->pushModuleData('user.php',$this->modData);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function main()	{

		$this->content='';
		$this->content.=$this->main_user($this->modData['openKeys']);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function printContent()	{
		echo $this->content;
	}



	/********************************
	 *
	 * Other functions
	 *
	 *********************************/

	/**
	 * @param	[type]		$imgInfo: ...
	 * @param	[type]		$maxW: ...
	 * @param	[type]		$maxH: ...
	 * @return	[type]		...
	 */
	function calcWH($imgInfo,$maxW=380,$maxH=500)	{
		$IW = $imgInfo[0];
		$IH = $imgInfo[1];
		if ($IW>$maxW)	{
			$IH=ceil($IH/$IW*$maxW);
			$IW=$maxW;
		}
		if ($IH>$maxH)	{
			$IW=ceil($IW/$IH*$maxH);
			$IH=$maxH;
		}

		$imgInfo[3]='width="'.$IW.'" height="'.$IH.'"';
		return $imgInfo;
	}

	/**
	 * Rich Text Editor (RTE) user element selector
	 *
	 * @param	[type]		$openKeys: ...
	 * @return	[type]		...
	 */
	function main_user($openKeys)	{
		global $LANG, $BACK_PATH, $BE_USER;
			// Starting content:
		$content.=$this->doc->startPage($LANG->getLL('Insert Custom Element',1));

		$RTEtsConfigParts = explode(':',t3lib_div::_GP('RTEtsConfigParams'));
		$RTEsetup = $BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
		$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);

		if (is_array($thisConfig['userElements.']))	{

			$categories=array();
			foreach ($thisConfig['userElements.'] as $k => $value) {
				$ki=intval($k);
				$v = $thisConfig['userElements.'][$ki.'.'];
				if (substr($k,-1)=="." && is_array($v))	{
					$subcats=array();
					$openK = $ki;
					if ($openKeys[$openK])	{

						$mArray = '';
						switch ((string)$v['load'])	{
							case 'images_from_folder':
								$mArray=array();
								if ($v['path'] && @is_dir(PATH_site.$v['path']))	{
									$files = t3lib_div::getFilesInDir(PATH_site.$v['path'],'gif,jpg,jpeg,png',0,'');
									if (is_array($files))	{
										$c=0;
										foreach ($files as $filename) {
											$iInfo = @getimagesize(PATH_site.$v['path'].$filename);
											$iInfo = $this->calcWH($iInfo,50,100);

											$ks=(string)(100+$c);
											$mArray[$ks]=$filename;
											$mArray[$ks."."]=array(
												'content' => '<img src="'.$this->siteUrl.$v['path'].$filename.'" />',
												'_icon' => '<img src="'.$this->siteUrl.$v['path'].$filename.'" '.$iInfo[3].' />',
												'description' => $LANG->getLL('filesize').': '.str_replace('&nbsp;',' ',t3lib_div::formatSize(@filesize(PATH_site.$v['path'].$filename))).', '.$LANG->getLL('pixels',1).': '.$iInfo[0].'x'.$iInfo[1]
											);
											$c++;
										}
									}
								}
							break;
						}
						if (is_array($mArray))	{
							if ($v['merge'])	{
								$v=t3lib_div::array_merge_recursive_overrule($mArray,$v);
							} else {
								$v=$mArray;
							}
						}
						foreach ($v as $k2 => $dummyValue) {
							$k2i = intval($k2);
							if (substr($k2,-1)=='.' && is_array($v[$k2i.'.']))	{
								$title = trim($v[$k2i]);
								if (!$title)	{
									$title='['.$LANG->getLL('noTitle',1).']';
								} else {
									$title=$LANG->sL($title,1);
								}
								$description = $LANG->sL($v[$k2i.'.']['description'],1).'<br />';
								if (!$v[$k2i.'.']['dontInsertSiteUrl'])	$v[$k2i.'.']['content'] = str_replace('###_URL###',$this->siteUrl,$v[$k2i.'.']['content']);

								$logo = $v[$k2i.'.']['_icon'] ? $v[$k2i.'.']['_icon'] : '';

								$onClickEvent='';
								switch((string)$v[$k2i.'.']['mode'])	{
									case 'wrap':
										$wrap = explode('|',$v[$k2i.'.']['content']);
										$onClickEvent='wrapHTML(' . $LANG->JScharCode($wrap[0]) . ',' . $LANG->JScharCode($wrap[1]) . ',false);';
									break;
									case 'processor':
										$script = trim($v[$k2i.'.']['submitToScript']);
										if (substr($script,0,4)!='http') $script = $this->siteUrl.$script;
										if ($script)	{
											$onClickEvent='processSelection(' . $LANG->JScharCode($script) . ');';
										}
									break;
									case 'insert':
									default:
										$onClickEvent='insertHTML(' . $LANG->JScharCode($v[$k2i . '.']['content']) . ');';
									break;
								}
								$A=array('<a href="#" onClick="'.$onClickEvent.'return false;">','</a>');
								$subcats[$k2i]='<tr>
									<td><img src="clear.gif" width="18" height="1" /></td>
									<td class="bgColor4" valign="top">'.$A[0].$logo.$A[1].'</td>
									<td class="bgColor4" valign="top">'.$A[0].'<strong>'.$title.'</strong><br />'.$description.$A[1].'</td>
								</tr>';
							}
						}
						ksort($subcats);
					}
					$categories[$ki]=implode('',$subcats);
				}
			}
			ksort($categories);

			# Render menu of the items:
			$lines=array();
			foreach ($categories as $k => $v) {
				$title = trim($thisConfig['userElements.'][$k]);
				$openK = $k;
				if (!$title)	{
					$title='['.$LANG->getLL('noTitle',1).']';
				} else {
					$title=$LANG->sL($title,1);
				}
				//$lines[]='<tr><td colspan="3" class="bgColor5"><a href="'.t3lib_div::linkThisScript(array('OC_key' => ($openKeys[$openK]?'C|':'O|').$openK, 'editorNo' => $this->editorNo)).'" title="'.$LANG->getLL('expand',1).'"><img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/'.($openKeys[$openK]?'minus':'plus').'bullet.gif','width="18" height="16"').' title="'.$LANG->getLL('expand',1).'" /><strong>'.$title.'</strong></a></td></tr>';
				$lines[]='<tr><td colspan="3" class="bgColor5"><a href="#" title="'.$LANG->getLL('expand',1).'" onClick="jumpToUrl(\'?OC_key=' .($openKeys[$openK]?'C|':'O|').$openK. '\');return false;"><img' . t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/'.($openKeys[$openK]?'minus':'plus').'bullet.gif','width="18" height="16"').' title="'.$LANG->getLL('expand',1).'" /><strong>'.$title.'</strong></a></td></tr>';
				$lines[]=$v;
			}

			$content.='<table border="0" cellpadding="1" cellspacing="1">'.implode('',$lines).'</table>';
		}

		$content.= $this->doc->endPage();
		return $content;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod5/class.tx_rtehtmlarea_user.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod5/class.tx_rtehtmlarea_user.php']);
}

?>