<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 * XHTML compliant
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  151: class TBE_browser_recordList extends localRecordList 
 *  160:     function listURL($altId="",$table=-1,$exclList="")	
 *  179:     function ext_addP()	
 *  194:     function linkWrapItems($table,$uid,$code,$row)	
 *
 *
 *  224: class localPageTree extends t3lib_browseTree 
 *  232:     function wrapTitle($title,$v,$ext_pArrPages)	
 *  249:     function printTree($treeArr="")	
 *  277:     function ext_isLinkable($doktype,$uid)	
 *  291:     function PM_ATagWrap($icon,$cmd,$bMark="")	
 *  306:     function wrapIcon($icon,$row)	
 *
 *
 *  322: class rtePageTree extends localPageTree 
 *
 *
 *  336: class TBE_PageTree extends localPageTree 
 *  343:     function ext_isLinkable($doktype,$uid)	
 *  355:     function wrapTitle($title,$v,$ext_pArrPages)	
 *
 *
 *  378: class localFolderTree extends t3lib_browseTree 
 *  385:     function wrapTitle($title,$v)	
 *  400:     function printTree($treeArr="")	
 *  434:     function ext_isLinkable($v)	
 *  452:     function PM_ATagWrap($icon,$cmd,$bMark="")	
 *  466:     function ext_getRelFolder($path)	
 *
 *
 *  483: class rteFolderTree extends localFolderTree 
 *
 *
 *  495: class TBE_FolderTree extends localFolderTree 
 *  502:     function ext_isLinkable($v)	
 *  515:     function wrapTitle($title,$v)	
 *
 *
 *  535: class SC_browse_links 
 *  547:     function init()	
 *  611:     function setTarget(target)	
 *  616:     function setValue(value)	
 *  642:     function link_typo3Page(id,anchor)	
 *  648:     function link_folder(folder)	
 *  654:     function link_current()	
 *  665:     function checkReference()	
 *  673:     function updateValueInMainForm(input)	
 *  684:     function link_typo3Page(id,anchor)	
 *  690:     function link_folder(folder)	
 *  696:     function link_spec(theLink)	
 *  701:     function link_current()	
 *  711:     function jumpToUrl(URL,anchor)	
 *  733:     function launchView(url)	
 *  741:     function setReferences()	
 *  755:     function insertElement(table, uid, type, filename,fp,filetype,imagefile,action, close)	
 *  775:     function addElement(elName,elValue,altElValue,close)	
 *  795:     function main()	
 *  845:     function printContent()	
 *
 *              SECTION: OTHER FUNCTIONS:
 *  863:     function expandPage()	
 *  899:     function TBE_expandPage($tables)	
 * 1002:     function isWebFolder($folder)	
 * 1013:     function checkFolder($folder)	
 *
 *              SECTION: OTHER FUNCTIONS:
 * 1037:     function expandFolder($expandFolder=0,$extensionList="")	
 * 1080:     function TBE_expandFolder($expandFolder=0,$extensionList="")	
 * 1164:     function TBE_dragNDrop($expandFolder=0,$extensionList="")	
 * 1245:     function getMsgBox($in_msg,$icon="icon_note")	
 *
 *              SECTION: Miscellaneous functions
 * 1266:     function barheader($str)	
 * 1276:     function printCurrentUrl($str)	
 * 1287:     function parseCurUrl($href,$siteUrl)	
 * 1348:     function uploadForm($path)	
 * 1370:     function createFolder($path)	
 * 1398:     function main_rte($content="",$wiz=0)	
 * 1564:     function main_db($content="")	
 * 1610:     function main_file($content="",$mode)	
 *
 * TOTAL FUNCTIONS: 52
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
$BACK_PATH='';
require ('init.php');
require ('template.php');
require_once (PATH_t3lib.'class.t3lib_browsetree.php');
require_once (PATH_t3lib.'class.t3lib_foldertree.php');
require_once (PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');
include ('sysext/lang/locallang_browse_links.php');

// **************************
// Functions and classes 
// **************************
require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_recordlist.php');
require_once ('class.db_list.inc');
require_once ('class.db_list_extra.inc');



















/**
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TBE_browser_recordList extends localRecordList {
	var $script='browse_links.php';

	/**
	 * @param	[type]		$altId: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$exclList: ...
	 * @return	[type]		...
	 */
	function listURL($altId='',$table=-1,$exclList='')	{
		return $this->script.
			'?id='.(strcmp($altId,'')?$altId:$this->id).
			'&table='.rawurlencode($table==-1?$this->table:$table).
			($this->thumbs?'&imagemode='.$this->thumbs:'').
			($this->searchString?'&search_field='.rawurlencode($this->searchString):'').
			($this->searchLevels?'&search_levels='.rawurlencode($this->searchLevels):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortField')) && $this->sortField?'&sortField='.rawurlencode($this->sortField):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortRev')) && $this->sortRev?'&sortRev='.rawurlencode($this->sortRev):'').
				// extra:
			$this->ext_addP()
			;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function ext_addP()	{
		$str = '&act='.$GLOBALS['act'].
				'&mode='.$GLOBALS['mode'].
				'&expandPage='.t3lib_div::GPvar('expandPage').
				'&bparams='.rawurlencode(t3lib_div::GPvar('bparams'));
		return $str;
	}

	/**
	 * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record...)
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$code: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function linkWrapItems($table,$uid,$code,$row)	{
		global $TCA;

		if (!$code) {$code='<i>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title').']</i>';}
		$code=t3lib_div::fixed_lgd('&nbsp;'.$code,$this->fixedL);

		$titleCol = $TCA[$table]['ctrl']['label'];
		$title=$row[$titleCol];

		$ficon=t3lib_iconWorks::getIcon($table,$row);
		$aOnClick = "return insertElement('".$table."', '".$row['uid']."', 'db', unescape('".rawurlencode($title)."'), '', '', '".$ficon."');";
		$ATag='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">';
		$ATag_alt=substr($ATag,0,-4).',\'\',1);">';
		$ATag_e='</a>';

		return $ATag.
				'<img src="gfx/plusbullet2.gif" width="18" height="16" border="0" align="top"'.t3lib_BEfunc::titleAttrib($GLOBALS['LANG']->getLL('addToList')).' alt="" />'.
				$ATag_e.
				$ATag_alt.
				$code.
				$ATag_e;
	}
}






/**
 * Class which generates the page tree
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localPageTree extends t3lib_browseTree {

	function localPageTree() {
		$this->init();
	}
	
	/**
	 * @param	[type]		$title: ...
	 * @param	[type]		$v: ...
	 * @param	[type]		$ext_pArrPages: ...
	 * @return	[type]		...
	 */
	function wrapTitle($title,$v,$ext_pArrPages)	{
		$title= (!strcmp(trim($title),'')) ? '<em>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title').']</em>' : htmlspecialchars($title);

		if ($this->ext_isLinkable($v['doktype'],$v['uid']))	{
			return '<a href="#" onclick="return link_typo3Page(\''.$v["uid"].'\');">'.$title.'</a>';
		} else {
			return '<font color=#666666>'.$title.'</font>';
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$treeArr: ...
	 * @return	[type]		...
	 */
	function printTree($treeArr="")	{
		$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
		if (!is_array($treeArr))	$treeArr=$this->tree;
		reset($treeArr);
		$out="";
		$c=0;
		$xCol = t3lib_div::modifyHTMLColor($GLOBALS["SOBE"]->doc->bgColor,-10,-10,-10);
		while(list($k,$v)=each($treeArr))	{
			$c++;
			$bgColor=' bgColor="'.(($c+1)%2 ? $GLOBALS["SOBE"]->doc->bgColor : $xCol).'"';
			if ($GLOBALS["curUrlInfo"]["act"]=="page" && $GLOBALS["curUrlInfo"]["pageid"]==$v["row"]["uid"] && $GLOBALS["curUrlInfo"]["pageid"])	{
				$arrCol='<td><img src="gfx/blinkarrow_right.gif" width="5" height="9" vspace=1></td>';
				$bgColor=' bgColor="'.$GLOBALS["SOBE"]->doc->bgColor4.'"';
			} else {$arrCol='<td></td>';}
			$cEbullet = $this->ext_isLinkable($v["row"]["doktype"],$v["row"]["uid"]) ? '<a href="#" onclick="return jumpToUrl(\''.$this->script.'?act='.$GLOBALS["act"].'&mode='.$GLOBALS["mode"].'&expandPage='.$v["row"]["uid"].'\');"><img src="gfx/ol/arrowbullet.gif" width="18" hspace=5 height="16" border="0"></a>' : '';
			$out.='<tr'.$bgColor.'><td nowrap>'.$v["HTML"].$this->wrapTitle(t3lib_div::fixed_lgd($v["row"]["title"],$titleLen),$v["row"],$this->ext_pArrPages).'</td>'.$arrCol.'<td>'.$cEbullet.'</td></tr>';
		}
		$out='<table border=0 cellpadding=0 cellspacing=0>'.$out.'</table>';
		return $out;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$doktype: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function ext_isLinkable($doktype,$uid)	{
		if ($uid && $doktype<199)	{
			return true;
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$icon: ...
	 * @param	[type]		$cmd: ...
	 * @param	[type]		$bMark: ...
	 * @return	[type]		...
	 */
	function PM_ATagWrap($icon,$cmd,$bMark="")	{
		if ($bMark)	{
			$anchor = "#".$bMark;
			$name=' name="'.$bMark.'"';
		}
		return '<a href="#"'.$name.' onclick="return jumpToUrl(\''.$this->script.'?PM='.$cmd.'\',\''.$anchor.'\');">'.$icon.'</a>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$icon: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function wrapIcon($icon,$row)	{
		return substr($icon,0,-1).' title="id='.$row["uid"].'">';
	}
}





/**
 * For RTE
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class rtePageTree extends localPageTree {
}





/**
 * For TBE record browser
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TBE_PageTree extends localPageTree {

	/**
	 * @param	[type]		$doktype: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function ext_isLinkable($doktype,$uid)	{
		return true;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$title: ...
	 * @param	[type]		$v: ...
	 * @param	[type]		$ext_pArrPages: ...
	 * @return	[type]		...
	 */
	function wrapTitle($title,$v,$ext_pArrPages)	{
		$title= (!strcmp(trim($title),"")) ? "<em>[".$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.no_title")."]</em>" : htmlspecialchars($title);
		if ($ext_pArrPages)	{
			$ficon=t3lib_iconWorks::getIcon("pages",$v);
			$onClick = "return insertElement('pages', '".$v["uid"]."', 'db', unescape('".rawurlencode($v["title"])."'), '', '', '".$ficon."','',1);";
		} else {
			$onClick = 'return jumpToUrl(\'browse_links.php?act='.$GLOBALS["act"].'&mode='.$GLOBALS["mode"].'&expandPage='.$v["uid"].'\');';
		}
		return '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.$title.'</a>';
	}
}





/**
 * Class which generates the folder tree
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localFolderTree extends t3lib_folderTree {

	/**
	 * @param	[type]		$title: ...
	 * @param	[type]		$v: ...
	 * @return	[type]		...
	 */
	function wrapTitle($title,$v)	{
		if ($this->ext_isLinkable($v))	{
			return '<a href="#" onclick="return jumpToUrl(\''.$this->script.'?act='.$GLOBALS["act"].'&mode='.$GLOBALS["mode"].'&expandFolder='.rawurlencode($v["path"]).'\');">'.$title.'</a>';
//			return '<a href="#" onclick="return link_folder(\''.$this->ext_getRelFolder($v["path"]).'\');">'.$title.'</a>';
		} else {
			return '<font color=#666666>'.$title.'</font>';
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$treeArr: ...
	 * @return	[type]		...
	 */
	function printTree($treeArr="")	{
		$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
		if (!is_array($treeArr))	$treeArr=$this->tree;
		reset($treeArr);
		$out="";
		$c=0;
		$xCol = t3lib_div::modifyHTMLColorAll($GLOBALS["SOBE"]->doc->bgColor,-10);
		if (!$GLOBALS["curUrlInfo"]["value"])	{
			$cmpPath="";
		} else if (substr(trim($GLOBALS["curUrlInfo"]["info"]),-1)!="/")	{
			$cmpPath=PATH_site.dirname($GLOBALS["curUrlInfo"]["info"])."/";
		} else {
			$cmpPath=PATH_site.$GLOBALS["curUrlInfo"]["info"];
		}
		while(list($k,$v)=each($treeArr))	{
			$c++;
			$bgColor=' bgColor="'.(($c+1)%2 ? $GLOBALS["SOBE"]->doc->bgColor : $xCol).'"';
			if ($GLOBALS["curUrlInfo"]["act"]=="file" && $cmpPath==$v["row"]["path"])	{
				$arrCol='<td><img src="gfx/blinkarrow_right.gif" width="5" height="9" vspace=1></td>';
				$bgColor=' bgColor="'.$GLOBALS["SOBE"]->doc->bgColor4.'"';
			} else {$arrCol='<td></td>';}
			$cEbullet = $this->ext_isLinkable($v["row"]) ? '<a href="#" onclick="return jumpToUrl(\''.$this->script.'?act='.$GLOBALS["act"].'&mode='.$GLOBALS["mode"].'&expandFolder='.rawurlencode($v["row"]["path"]).'\');"><img src="gfx/ol/arrowbullet.gif" width="18" hspace=5 height="16" border="0"></a>' : '';
			$out.='<tr'.$bgColor.'><td nowrap>'.$v["HTML"].$this->wrapTitle(t3lib_div::fixed_lgd($v["row"]["title"],$titleLen),$v["row"]).'</td>'.$arrCol.'<td>'.$cEbullet.'</td></tr>';
		}
		$out='<table border=0 cellpadding=0 cellspacing=0>'.$out.'</table>';
		return $out;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$v: ...
	 * @return	[type]		...
	 */
	function ext_isLinkable($v)	{
//		debug($v);
		$webpath=t3lib_BEfunc::getPathType_web_nonweb($v["path"]);
//		debug($webpath);
		if (strstr($v["path"],"_recycler_") || strstr($v["path"],"_temp_") || $webpath!="web")	{
			return 0;
		} 
		return 1;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$icon: ...
	 * @param	[type]		$cmd: ...
	 * @param	[type]		$bMark: ...
	 * @return	[type]		...
	 */
	function PM_ATagWrap($icon,$cmd,$bMark="")	{
		if ($bMark)	{
			$anchor = "#".$bMark;
			$name=' name="'.$bMark.'"';
		}
		return '<a href="#"'.$name.' onclick="return jumpToUrl(\''.$this->script.'?PM='.$cmd.'\',\''.$anchor.'\');">'.$icon.'</a>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$path: ...
	 * @return	[type]		...
	 */
	function ext_getRelFolder($path)	{
		return substr($path,strlen(PATH_site));
	}
}






/**
 * For RTE
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class rteFolderTree extends localFolderTree {
}



/**
 * For TBE File Browser
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TBE_FolderTree extends localFolderTree {
	var $ext_noTempRecyclerDirs=0;

	/**
	 * @param	[type]		$v: ...
	 * @return	[type]		...
	 */
	function ext_isLinkable($v)	{
		if ($this->ext_noTempRecyclerDirs && (substr($v["path"],-7)=="_temp_/" || substr($v["path"],-11)=="_recycler_/"))	{
			return 0;
		} return 1;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$title: ...
	 * @param	[type]		$v: ...
	 * @return	[type]		...
	 */
	function wrapTitle($title,$v)	{
		if ($this->ext_isLinkable($v))	{
			return '<a href="#" onclick="return jumpToUrl(\'browse_links.php?act='.$GLOBALS["act"].'&mode='.$GLOBALS["mode"].'&expandFolder='.rawurlencode($v["path"]).'\');">'.$title.'</a>';
		} else {
			return '<font color=#666666>'.$title.'</font>';
		}
	}
}





/**
 * Script class
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_browse_links {
	var $pointer;
	var $siteURL;
	var $thisConfig;
	var $setTarget;
	var $doc;	
	
	/**
	 * Constructor
	 * 
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		global $act,$mode,$curUrlInfo,$curUrlArray;

		$this->pointer = t3lib_div::GPvar("pointer");
		
		
		// **************************
		// For RTE/Link: Pre-processing
		// **************************
		$addPassOnParams="";
		
		// Current site url:
		$this->siteURL = t3lib_div::getIndpEnv("TYPO3_SITE_URL");
		
		// CurrentUrl - the current link url must be passed around if it exists
		$curUrlArray = t3lib_div::GPvar("curUrl",1);
		if ($curUrlArray["all"])	{
			$curUrlArray=t3lib_div::get_tag_attributes($curUrlArray["all"]);
		}
		//	debug($curUrlArray);
		$curUrlInfo=$this->parseCurUrl($curUrlArray["href"],$this->siteURL);
		
		// Determine nature of current url:
		$act=t3lib_div::GPvar("act");
		if (!$act)	{
			$act=$curUrlInfo["act"];
		}
		
		$mode=t3lib_div::GPvar("mode");
		if (!$mode)	{
			$mode="rte";
		}
		
		
		if ((string)$mode=="rte")	{
			$RTEtsConfigParts = explode(":",t3lib_div::GPvar("RTEtsConfigParams"));
			$addPassOnParams.="&RTEtsConfigParams=".rawurlencode(t3lib_div::GPvar("RTEtsConfigParams"));
			$RTEsetup = $GLOBALS["BE_USER"]->getTSConfig("RTE",t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5])); 
			$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup["properties"],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		}
		$this->setTarget = $curUrlArray["target"];
		if ($this->thisConfig["defaultLinkTarget"] && !isset($curUrlArray["target"]))	{
			$this->setTarget=$this->thisConfig["defaultLinkTarget"];
		}
		
		// **************************
		// Main thing:
		// **************************
		
		$this->doc = t3lib_div::makeInstance("template");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='
		<script language="javascript" type="text/javascript">
			// This JavaScript is primarily for RTE/Link. jumpToUrl is used in the other cases as well...
		
			var add_href="'.($curUrlArray["href"]?"&curUrl[href]=".rawurlencode($curUrlArray["href"]):"").'";
			var add_target="'.($this->setTarget?"&curUrl[target]=".rawurlencode($this->setTarget):"").'";
			var add_params="'.(t3lib_div::GPvar("bparams")?"&bparams=".rawurlencode(t3lib_div::GPvar("bparams")):"").'";
		
			var cur_href="'.($curUrlArray["href"]?$curUrlArray["href"]:"").'";
			var cur_target="'.($this->setTarget?$this->setTarget:"").'";
		
				//
			function setTarget(target)	{
				cur_target=target;
				add_target="&curUrl[target]="+target;
			}
				//
			function setValue(value)	{
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}
		';
		
		
		if ($mode=="wizard")	{
			$P = t3lib_div::GPvar("P",1);
		//debug($P);
			unset($P["fieldChangeFunc"]["alert"]);
			reset($P["fieldChangeFunc"]);
			$update="";
			while(list($k,$v)=each($P["fieldChangeFunc"]))	{
				$update.= "
				window.opener.".$v;
			}
		
			$P2=array();
			$P2["itemName"]=$P["itemName"];
			$P2["formName"]=$P["formName"];
			$P2["fieldChangeFunc"]=$P["fieldChangeFunc"];
			$addPassOnParams.=t3lib_div::implodeArrayForUrl("P",$P2);
		
			$this->doc->JScode.='
					//
				function link_typo3Page(id,anchor)	{
					updateValueInMainForm(id+(anchor?anchor:"")+" "+cur_target);
					close();
					return false;
				}
					//
				function link_folder(folder)	{
					updateValueInMainForm(folder+" "+cur_target);
					close();
					return false;
				}
					//
				function link_current()	{
					if (cur_href!="http://" && cur_href!="mailto:")	{
						var setValue = cur_href+" "+cur_target;
						if (setValue.substr(0,7)=="http://")	setValue = setValue.substr(7);
						if (setValue.substr(0,7)=="mailto:")	setValue = setValue.substr(7);
						updateValueInMainForm(setValue);
						close();
					}
					return false;
				}
					//
				function checkReference()	{
					if (window.opener && window.opener.document && window.opener.document.'.$P["formName"].' && window.opener.document.'.$P["formName"].'["'.$P["itemName"].'"] )	{
						return window.opener.document.'.$P["formName"].'["'.$P["itemName"].'"];
					} else {
						close();
					}
				}
					//
				function updateValueInMainForm(input)	{
					var field = checkReference();
					if (field)	{
						field.value = input;
						'.$update.'
					}
				}
			';
		} else {
			$this->doc->JScode.='
					//
				function link_typo3Page(id,anchor)	{
					var theLink = \''.$this->siteURL.'?id=\'+id+(anchor?anchor:"");
					self.parent.parent.renderPopup_addLink(theLink,cur_target);
					return false;
				}
					//
				function link_folder(folder)	{
					var theLink = \''.$this->siteURL.'\'+folder;
					self.parent.parent.renderPopup_addLink(theLink,cur_target);
					return false;
				}
					//
				function link_spec(theLink)	{
					self.parent.parent.renderPopup_addLink(theLink,cur_target);
					return false;
				}
					//
				function link_current()	{
					if (cur_href!="http://" && cur_href!="mailto:")	{
						self.parent.parent.renderPopup_addLink(cur_href,cur_target);	
					}
					return false;
				}
			';
		}
		$this->doc->JScode.='
				//
			function jumpToUrl(URL,anchor)	{
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$act.'" : "";
				var add_mode = URL.indexOf("mode=")==-1 ? "&mode='.$mode.'" : "";
				var theLocation = URL+add_act+add_mode+add_href+add_target+add_params'.($addPassOnParams?'+"'.$addPassOnParams.'"':"").'+(anchor?anchor:"");
				document.location = theLocation;
				return false;
			}
		</script>
		';
		
		
		// This is JavaScript especially for the TBE Element Browser!
		
		$pArr = explode("|",t3lib_div::GPvar("bparams"));
#debug($pArr);
		$formFieldName = 'data['.$pArr[0].']['.$pArr[1].']['.$pArr[2].']';
		$this->doc->JScode.='
		<script language="javascript" type="text/javascript">
			var elRef="";
			var targetDoc="";
		
				//
			function launchView(url)	{
				var thePreviewWindow="";
				thePreviewWindow = window.open("show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
				//
			function setReferences()	{
				if (parent.typoWin
				&& parent.typoWin.content
				&& parent.typoWin.content.document.editform
				&& parent.typoWin.content.document.editform["'.$formFieldName.'"]
						) {
					targetDoc = parent.typoWin.content.document;
					elRef = targetDoc.editform["'.$formFieldName.'"];
					return true;
				} else {
					return false;
				}
			}
				//
			function insertElement(table, uid, type, filename,fp,filetype,imagefile,action, close)	{
				if (1=='.($pArr[0]&&!$pArr[1]&&!$pArr[2] ? 1 : 0).')	{
					addElement(filename,table+"_"+uid,fp,close);
				} else {
					if (setReferences())	{
						if (parent.typoWin.clipBrd.clipboard)	parent.typoWin.clipBrd.clipboard.closing();
						parent.typoWin.clipBrd.swPad("normal");
						parent.typoWin.clipBrd.aI("normal", table, uid, type, filename,fp,filetype,imagefile,action);
						parent.typoWin.group_change("add","'.$pArr[0].'","'.$pArr[1].'","'.$pArr[2].'",elRef,targetDoc);
					} else {
						alert("Error - reference to main window is not set properly!");
					}
					if (close)	{
						parent.typoWin.focus();
						parent.close();
					}
				}
				return false;
			}
				//
			function addElement(elName,elValue,altElValue,close)	{
				if (parent.typoWin && parent.typoWin.setFormValueFromBrowseWin)	{
					parent.typoWin.setFormValueFromBrowseWin("'.$pArr[0].'",altElValue?altElValue:elValue,elName);
					if (close)	{
						parent.typoWin.focus();
						parent.close();
					}
				} else {
					alert("Error - refderence to main window is not set properly!");
					parent.close();
				}
			}
		</script>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		global $act,$mode,$curUrlInfo,$curUrlArray;

		$modData = $BE_USER->getModuleData("browse_links.php","ses");
		
		// Output the correct content according to $mode
		switch((string)$mode)	{
			case "rte":
				$this->content="";
				$this->content.=$this->main_rte();
			break;
			case "db":
				$expandPage = t3lib_div::GPvar("expandPage");
				if (isset($expandPage))	{
					$modData["expandPage"]=$expandPage;
					$BE_USER->pushModuleData("browse_links.php",$modData);
				} else {
					$HTTP_GET_VARS["expandPage"]=$modData["expandPage"];
				}
		
				$this->content="";
				$this->content.=$this->main_db();
			break;
			case "file":
			case "filedrag":
				$expandPage = t3lib_div::GPvar("expandFolder");
				if (isset($expandPage))	{
					$modData["expandFolder"]=$expandPage;
					$BE_USER->pushModuleData("browse_links.php",$modData);
				} else {
					$HTTP_GET_VARS["expandFolder"]=$modData["expandFolder"];
				}
		
				$this->content="";
				$this->content.=$this->main_file("",$mode);
			break;
			case "wizard":
				$this->content="";
				$this->content.=$this->main_rte("",1);
			break;
		}

	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		echo $this->content;
	}
	


	
	/******************************************************************
	 *
	 * OTHER FUNCTIONS:	
	 * These functions are designed to display the records from a page
	 * 
	 ******************************************************************/
	/**
	 * For RTE: This displays all content elements on a page and lets you create a link to the element.
	 * 
	 * @return	[type]		...
	 */
	function expandPage()	{
		$expandPage = t3lib_div::GPvar("expandPage");
		$out="";
		if ($expandPage)	{
			$out.=$this->barheader($GLOBALS["LANG"]->getLL("contentElements").':');
		
			$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
			$mainPageRec = t3lib_BEfunc::getRecord("pages",$expandPage);
			$picon=t3lib_iconWorks::getIconImage("pages",$mainPageRec,"","align=top");
			$picon.=htmlspecialchars(t3lib_div::fixed_lgd($mainPageRec["title"],$titleLen));
			$out.='<nobr>'.$picon.'</nobr><BR>';
			
			$query="SELECT uid,header,hidden,starttime,endtime,fe_group,CType,colpos FROM tt_content WHERE pid=".intval($expandPage).t3lib_BEfunc::deleteClause("tt_content")." ORDER BY colpos,sorting";
			$res = mysql(TYPO3_db,$query);
			echo mysql_error();
			$cc=mysql_num_rows($res);
			$c=0;
			while($row=mysql_fetch_assoc($res))	{
				$c++;
				$icon=t3lib_iconWorks::getIconImage("tt_content",$row,"","align=top");
				if ($GLOBALS["curUrlInfo"]["act"]=="page" && $GLOBALS["curUrlInfo"]["cElement"]==$row["uid"])	{
					$arrCol='<img src="gfx/blinkarrow_left.gif" width="5" height="9" vspace=3 hspace=2 align=top>';
				} else {$arrCol="";}
				$out.='<nobr><img src="gfx/ol/join'.($c==$cc?"bottom":"").'.gif" width="18" height="16" align=top>'.$arrCol.'<a href="#" onclick="return link_typo3Page(\''.$expandPage.'\',\'#'.$row["uid"].'\');">'.$icon.htmlspecialchars(t3lib_div::fixed_lgd($row["header"],$titleLen)).'</a></nobr><BR>';
	//				debug($row);
			}
		}
		return $out;
	}

	/**
	 * For TBE Record Browser: This lists all content elements from the given category!
	 * 
	 * @param	[type]		$tables: ...
	 * @return	[type]		...
	 */
	function TBE_expandPage($tables)	{
		global $TCA;
		$expandPage = t3lib_div::GPvar("expandPage");
/*		if (isset($id))	{
			$expandPage=$id;
		}
	*/	$out="";
		if (t3lib_div::testInt($expandPage) && $expandPage>=0)	{
			if (!strcmp(trim($tables),"*"))	{
				$tablesArr = array_keys($TCA);
			} else {
				$tablesArr = t3lib_div::trimExplode(",",$tables,1);
			}
	
			reset($tablesArr);
			$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
	
			$out.=$this->barheader($GLOBALS["LANG"]->getLL("selectRecords").':');
				
			$mainPageRec = t3lib_BEfunc::getRecord("pages",$expandPage);
			$ATag="";
			$ATag_e="";
				if (in_array("pages",$tablesArr))	{
					$ficon=t3lib_iconWorks::getIcon("pages",$mainPageRec);
					$ATag="<a href=\"#\" onclick=\"return insertElement('pages', '".$mainPageRec["uid"]."', 'db', unescape('".rawurlencode($mainPageRec["title"])."'), '', '', '".$ficon."','',1);\">";
					$ATag2="<a href=\"#\" onclick=\"return insertElement('pages', '".$mainPageRec["uid"]."', 'db', unescape('".rawurlencode($mainPageRec["title"])."'), '', '', '".$ficon."','',0);\">";
					$ATag_alt=substr($ATag,0,-4).",'',1);\">";
					$ATag_e="</a>";
				}
			$picon=t3lib_iconWorks::getIconImage("pages",$mainPageRec,"","align=top");
			$pBicon='<img src="gfx/plusbullet2.gif" width="18" height="16" border="0" align="top">';
			$pText=htmlspecialchars(t3lib_div::fixed_lgd($mainPageRec["title"],$titleLen));
			$out.='<nobr>'.$picon.$ATag2.$pBicon.$ATag_e.$ATag.$pText.$ATag_e.'</nobr><BR>';
	
	
			
			
			
			$id = $expandPage;
			$pointer = t3lib_div::intInRange($this->pointer,0,100000);
			$perms_clause = $GLOBALS["BE_USER"]->getPagePermsClause(1);
			$pageinfo = t3lib_BEfunc::readPageAccess($id,$perms_clause);
			$table="";
	
			$dblist = t3lib_div::makeInstance("TBE_browser_recordList");
			$dblist->script="browse_links.php";
			$dblist->backPath = $GLOBALS["BACK_PATH"];
			$dblist->thumbs = 0;
			$dblist->calcPerms = $GLOBALS["BE_USER"]->calcPerms($pageinfo);
			$dblist->noControlPanels=1;
			$dblist->tableList=implode(",",$tablesArr);
	
			$dblist->start($id,t3lib_div::GPvar("table"),$pointer,
				t3lib_div::GPvar("search_field"),
				t3lib_div::GPvar("search_levels"),
				t3lib_div::GPvar("showLimit")
			);
			$dblist->setDispFields();
	//		$dblist->writeTop($pageinfo,$pageinfo["_thePath"]);
			$dblist->generateList($id,$table);
			$dblist->writeBottom();
	
	
			$out.=$dblist->HTMLcode;
			$out.=$dblist->getSearchBox();
	
	/*
	
			while(list(,$table)=each($tablesArr))	{
				if ($table=="tt_content" && $TCA[$table])	{
					$query="SELECT uid,header,hidden,starttime,endtime,fe_group,CType,colpos FROM tt_content WHERE pid=".intval($expandPage).t3lib_BEfunc::deleteClause("tt_content")." ORDER BY colpos,sorting";
					$res = mysql(TYPO3_db,$query);
					echo mysql_error();
					$cc=mysql_num_rows($res);
					$c=0;
					while($row=mysql_fetch_assoc($res))	{
						$c++;
						$icon=t3lib_iconWorks::getIconImage("tt_content",$row,"","align=top");
						
						$ficon=t3lib_iconWorks::getIcon("tt_content",$row);
						$ATag="<a href=\"#\" onclick=\"return insertElement('".$table."', '".$row["uid"]."', 'db', unescape('".rawurlencode($row["header"])."'), '', '', '".$ficon."');\">";
						$ATag_alt=substr($ATag,0,-4).",'',1);\">";
						$ATag_e="</a>";
						
						$out.='<nobr><img src="gfx/ol/join'.($c==$cc?"bottom":"").'.gif" width="18" height="16" align=top>'.$ATag.$icon.htmlspecialchars(t3lib_div::fixed_lgd($row["header"],$titleLen)).$ATag_e.'</nobr><BR>';
			//				debug($row);
					}
				}
			}
	*/		
			
			
			
		}
		return $out;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$folder: ...
	 * @return	[type]		...
	 */
	function isWebFolder($folder)	{
		$folder = ereg_replace("\/$","",$folder)."/";
		return t3lib_div::isFirstPartOfStr($folder,PATH_site);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$folder: ...
	 * @return	[type]		...
	 */
	function checkFolder($folder)	{
		$fileProcessor = t3lib_div::makeInstance("t3lib_basicFileFunctions");
		$fileProcessor->init($GLOBALS["FILEMOUNTS"], $GLOBALS["TYPO3_CONF_VARS"]["BE"]["fileExtensions"]);
	//debug(array($GLOBALS["FILEMOUNTS"],$folder."/"));
		$ret= $fileProcessor->checkPathAgainstMounts(ereg_replace("\/$","",$folder)."/");
	//debug($ret);
		return $ret;
	}
	

	
	/******************************************************************
	 *
	 * OTHER FUNCTIONS:	
	 * These functions are designed to display the files from a folder
	 * 
	 ******************************************************************/
	/**
	 * For RTE: This displays all files from folder. No thumbnails shown
	 * 
	 * @param	[type]		$expandFolder: ...
	 * @param	[type]		$extensionList: ...
	 * @return	[type]		...
	 */
	function expandFolder($expandFolder=0,$extensionList="")	{
		$expandFolder = $expandFolder?$expandFolder:t3lib_div::GPvar("expandFolder");
		$out="";
		if ($expandFolder && $this->checkFolder($expandFolder))	{
			$out.=$this->barheader($GLOBALS["LANG"]->getLL("files").':');
		
			if (!$GLOBALS["curUrlInfo"]["value"])	{
				$cmpPath="";
			} else $cmpPath=PATH_site.$GLOBALS["curUrlInfo"]["info"];
	
			$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
			$picon='<img src="gfx/i/_icon_webfolders.gif" width="18" height="16" align=top border=0>';
			$picon.=htmlspecialchars(t3lib_div::fixed_lgd(basename($expandFolder),$titleLen));
			$picon='<a href="#" onclick="return link_folder(\''.substr($expandFolder,strlen(PATH_site)).'\');">'.$picon.'</a>';
			$out.='<nobr>'.$picon.'</nobr><BR>';
			
			$files = t3lib_div::getFilesInDir($expandFolder,$extensionList,1,1);	// $extensionList="",$prependPath=0,$order="")
			reset($files);
			$c=0;
			$cc=count($files);
			while(list(,$filepath)=each($files))	{
				$c++;
				$fI=pathinfo($filepath);
	//			debug($fI);
				$icon = t3lib_BEfunc::getFileIcon(strtolower($fI["extension"]));
				if ($GLOBALS["curUrlInfo"]["act"]=="file" && $cmpPath==$filepath)	{
					$arrCol='<img src="gfx/blinkarrow_left.gif" width="5" height="9" vspace=3 hspace=2 align=top>';
				} else {$arrCol='';}
				$size=" (".t3lib_div::formatSize(filesize($filepath))."bytes)";
				$icon = '<img src="gfx/fileicons/'.$icon.'" width=18 height=16 border=0 title="'.$fI["basename"].$size.'" align=top>';
				$out.='<nobr><img src="gfx/ol/join'.($c==$cc?"bottom":"").'.gif" width="18" height="16" align=top>'.$arrCol.'<a href="#" onclick="return link_folder(\''.substr($filepath,strlen(PATH_site)).'\');">'.$icon.htmlspecialchars(t3lib_div::fixed_lgd(basename($filepath),$titleLen)).'</a></nobr><BR>';
			}
		}
		return $out;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$expandFolder: ...
	 * @param	[type]		$extensionList: ...
	 * @return	[type]		...
	 */
	function TBE_expandFolder($expandFolder=0,$extensionList="")	{
		global $LANG;
		$expandFolder = $expandFolder?$expandFolder:t3lib_div::GPvar("expandFolder");
		$out="";
		if ($expandFolder && $this->checkFolder($expandFolder))	{
			$files = t3lib_div::getFilesInDir($expandFolder,$extensionList,1,1);	// $extensionList="",$prependPath=0,$order="")
			if (is_array($files))	{
				reset($files);
		
				$out.=$this->barheader(sprintf($GLOBALS["LANG"]->getLL("files").' (%s):',count($files)));
			
				$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
				$picon='<img src="gfx/i/_icon_webfolders.gif" width="18" height="16" align=top>';
				$picon.=htmlspecialchars(t3lib_div::fixed_lgd(basename($expandFolder),$titleLen));
				$out.='<nobr>'.$picon.'</nobr><BR>';
				
				$imgObj = t3lib_div::makeInstance("t3lib_stdGraphic");
				$imgObj->init();
				$imgObj->mayScaleUp=0;
				$imgObj->tempPath=PATH_site.$imgObj->tempPath;
		
				$noThumbs = $GLOBALS["BE_USER"]->getTSConfigVal("options.noThumbsInEB");
		
				$lines=array();
				while(list(,$filepath)=each($files))	{
					$fI=pathinfo($filepath);
					
					$iurl = $this->siteURL.substr($filepath,strlen(PATH_site));
					
					if (t3lib_div::inList($GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],$fI["extension"]) && !$noThumbs)	{
						$imgInfo = $imgObj->getImageDimensions($filepath);
						$pDim = $imgInfo[0]."x".$imgInfo[1]." pixels";
						$clickIcon = t3lib_BEfunc::getThumbNail("thumbs.php",$filepath,"hspace=5 vspace=5 border=1");
					} else {
						$clickIcon = '';
						$pDim = "";
					}
					
					$ficon = t3lib_BEfunc::getFileIcon(strtolower($fI["extension"]));
					$size=" (".t3lib_div::formatSize(filesize($filepath))."bytes".($pDim?", ".$pDim:"").")";
					$icon = '<img src="gfx/fileicons/'.$ficon.'" width=18 height=16 border=0 title="'.$fI["basename"].$size.'" align=absmiddle>';
		
		//			table, uid, type, filename,fp,filetype,imagefile,action
					$ATag = "<a href=\"#\" onclick=\"return insertElement('','".t3lib_div::shortMD5($filepath)."', 'file', '".rawurlencode($fI["basename"])."', unescape('".rawurlencode($filepath)."'), '".$fI["extension"]."', '".$ficon."');\">";
					$ATag_alt = substr($ATag,0,-4).",'',1);\">";
					$ATag_e="</a>";
		
	//				$ATag2='<a href="#" onclick="launchView(\''.rawurlencode($filepath).'\'); return false;">';
					$ATag2='<a href="show_item.php?table='.rawurlencode($filepath).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'">';
					$ATag2_e="</a>";
					
					$filenameAndIcon=$ATag_alt.$icon.htmlspecialchars(t3lib_div::fixed_lgd(basename($filepath),$titleLen)).$ATag_e;
					
					if ($pDim)	{		// Image...
						$lines[]='<tr bgcolor="'.$this->doc->bgColor4.'">
							<td nowrap>'.$filenameAndIcon.'&nbsp;</td>
							<td>'.$ATag.'<img src="gfx/plusbullet2.gif" width="18" height="16" border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("addToList")).'>'.$ATag_e.'</td>
							<td nowrap>'.($ATag2.'<img src="gfx/zoom2.gif" width="12" height="12" border="0" align=top'.t3lib_BEfunc::titleAttrib($LANG->getLL("info")).'> '.$LANG->getLL("info").$ATag2_e).'</td>
							<td nowrap>&nbsp;'.$pDim.'</td>
						</tr>';
						$lines[]='<tr><td colspan=4>'.$ATag_alt.$clickIcon.$ATag_e.'</td></tr>';
					} else {
						$lines[]='<tr bgcolor="'.$this->doc->bgColor4.'">
							<td nowrap>'.$filenameAndIcon.'&nbsp;</td>
							<td>'.$ATag.'<img src="gfx/plusbullet2.gif" width="18" height="16" border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("addToList")).'>'.$ATag_e.'</td>
							<td nowrap>'.($ATag2.'<img src="gfx/zoom2.gif" width="12" height="12" border="0" align=top'.t3lib_BEfunc::titleAttrib($LANG->getLL("info")).'> '.$LANG->getLL("info").$ATag2_e).'</td>
							<td>&nbsp;</td>
						</tr>';
					}
					$lines[]='<tr><td colspan=3><img src=clear.gif width=1 height=3></td></tr>';
				}
				$out.='<table border=0 cellpadding=0 cellspacing=1>'.implode("",$lines).'</table>';
			}
		}
		return $out;
	}

	/**
	 * For RTE: This displays all files (from extensionList) from folder. Thumbnails are shown for images.
	 * 
	 * @param	[type]		$expandFolder: ...
	 * @param	[type]		$extensionList: ...
	 * @return	[type]		...
	 */
	function TBE_dragNDrop($expandFolder=0,$extensionList="")	{
		$expandFolder = $expandFolder?$expandFolder:t3lib_div::GPvar("expandFolder");
		$out="";
		if ($expandFolder && $this->checkFolder($expandFolder))	{
			if ($this->isWebFolder($expandFolder))	{
				$files = t3lib_div::getFilesInDir($expandFolder,$extensionList,1,1);	// $extensionList="",$prependPath=0,$order="")
				if (is_array($files))	{
					reset($files);
			
					$out.=$this->barheader(sprintf($GLOBALS["LANG"]->getLL("files").' (%s):',count($files)));
				
					$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
					$picon='<img src="gfx/i/_icon_webfolders.gif" width="18" height="16" align=top>';
					$picon.=htmlspecialchars(t3lib_div::fixed_lgd(basename($expandFolder),$titleLen));
					$out.='<nobr>'.$picon.'</nobr><BR>';
					
					$lines=array();
					$lines[]='<tr><td colspan=2>'.$this->getMsgBox($GLOBALS["LANG"]->getLL("findDragDrop")).'</td></tr>';
		 
					while(list(,$filepath)=each($files))	{
						$fI=pathinfo($filepath);
						
						$iurl = $this->siteURL.substr($filepath,strlen(PATH_site));
						
						if (t3lib_div::inList("gif,jpeg,jpg,png",$fI["extension"]))	{
							$imgInfo = @getimagesize($filepath);
							$pDim = $imgInfo[0]."x".$imgInfo[1]." pixels";
						
							$ficon = t3lib_BEfunc::getFileIcon(strtolower($fI["extension"]));
							$size=" (".t3lib_div::formatSize(filesize($filepath))."bytes".($pDim?", ".$pDim:"").")";
							$icon = '<img src="gfx/fileicons/'.$ficon.'" width=18 height=16 border=0 title="'.$fI["basename"].$size.'" align=absmiddle>';
							$filenameAndIcon=$icon.htmlspecialchars(t3lib_div::fixed_lgd(basename($filepath),$titleLen));
		
							if (t3lib_div::GPvar("noLimit"))	{
								$maxW=10000;
								$maxH=10000;
							} else {
								$maxW=380;
								$maxH=500;
							}
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
							
		
							$lines[]='<tr bgcolor="'.$this->doc->bgColor4.'">
								<td nowrap>'.$filenameAndIcon.'&nbsp;</td>
								<td nowrap>'.
								($imgInfo[0]!=$IW ? '<a href="'.t3lib_div::linkThisScript(array("noLimit"=>"1")).'"><img src="gfx/icon_warning2.gif" width="18" height="16" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("clickToRedrawFullSize")).' align=top></a>':'').
								
								$pDim.'&nbsp;</td>
							</tr>';
							
							$lines[]='<tr><td colspan=2><img src="'.$iurl.'" width="'.$IW.'" height="'.$IH.'" border=1></td></tr>';
							$lines[]='<tr><td colspan=2><img src=clear.gif width=1 height=3></td></tr>';
						}
					}
					$out.='<table border=0 cellpadding=0 cellspacing=1>'.implode("",$lines).'</table>';
				}
			} else {
				$out.=$this->barheader($GLOBALS["LANG"]->getLL("files"));
				$out.=$this->getMsgBox($GLOBALS["LANG"]->getLL("noWebFolder"),"icon_warning2");
			}
		}
		return $out;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$in_msg: ...
	 * @param	[type]		$icon: ...
	 * @return	[type]		...
	 */
	function getMsgBox($in_msg,$icon="icon_note")	{
		$msg = '<img src="gfx/'.$icon.'.gif" width="18" height="16" align=top>'.$in_msg;
		$msg = '<table border=1 align=center width="95%" bordercolor="black" cellpadding=10 cellspacing=0 bgColor="'.$this->doc->bgColor4.'"><tr><td>'.$msg.'</td></tr></table><BR>';
		return $msg;
	}	
	
	
	
	
	/******************************************************************
	 *
	 * Miscellaneous functions
	 *
	 ******************************************************************/

	/**
	 * Prints a 'header' where string is in a tablecell
	 * 
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function barheader($str)	{
		return '<table border=0 cellpadding=2 cellspacing=0 width=100% bgcolor="'.$this->doc->bgColor5.'"><tr><td><strong>'.$str.'</strong></td></tr></table>';
	}

	/**
	 * For RTE/link: This prints the 'currentUrl'
	 * 
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function printCurrentUrl($str)	{
		return '<table border=0 cellpadding=0 cellspacing=0 width=100% bgcolor="'.$this->doc->bgColor5.'"><tr><td><strong>'.$GLOBALS["LANG"]->getLL("currentLink").':</strong> '.$str.'</td></tr></table>';
	}
	
	/**
	 * For RTE/link: Parses the incoming URL and determins if it's a page, file, external or mail address.
	 * 
	 * @param	[type]		$href: ...
	 * @param	[type]		$siteUrl: ...
	 * @return	[type]		...
	 */
	function parseCurUrl($href,$siteUrl)	{
		$href = trim($href);
		if ($href)	{
			$info=array();
			
				// Default is "url":
			$info["value"]=$href;
			$info["act"]="url";
	
			$specialParts = explode("#_SPECIAL",$href);
			if (count($specialParts)==2)	{
				$info["value"]="#_SPECIAL".$specialParts[1];
				$info["act"]="spec";
			} elseif (t3lib_div::isFirstPartOfStr($href,$siteUrl))	{	// Checking for other kinds:
				$rel = substr($href,strlen($siteUrl));
				if (@file_exists(PATH_site.$rel))	{
					$info["value"]=$rel;
					$info["act"]="file";
				} else {
					$uP=parse_url($rel);
					if (!trim($uP["path"]))	{
						$pp = explode("id=",$uP["query"]);
						$id = $pp[1];
						if ($id)	{
								// Checking if the id-parameter is an alias.
							if (!t3lib_div::testInt($id))	{
								list($idPartR) = t3lib_BEfunc::getRecordsByField("pages","alias",$id);
								$id=intval($idPartR["uid"]);
							}
							
							$pageRow = t3lib_BEfunc::getRecord("pages",$id);
							$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
							$info["value"]=$GLOBALS["LANG"]->getLL("page")." '".htmlspecialchars(t3lib_div::fixed_lgd($pageRow["title"],$titleLen))."' (ID:".$id.($uP["fragment"]?", #".$uP["fragment"]:"").")";
							$info["pageid"]=$id;
							$info["cElement"]=$uP["fragment"];
							$info["act"]="page";
						}
					}
				}
			} else {
				if (strtolower(substr($href,0,7))=="mailto:")	{
					$info["value"]=trim(substr($href,7));
					$info["act"]="mail";
				}	
			}
			$info["info"] = $info["value"];
		} else {
			$info=array();
			$info["info"]=$GLOBALS["LANG"]->getLL("none");
			$info["value"]="";
			$info["act"]="page";
		}
		return $info;
	}
	
	/**
	 * For TBE: Makes an upload form (similar to the one from rte_select_image.php)
	 * 
	 * @param	[type]		$path: ...
	 * @return	[type]		...
	 */
	function uploadForm($path)	{
	//	debug($path);
		$count=3;
		$header = t3lib_div::isFirstPartOfStr($path,PATH_site)?substr($path,strlen(PATH_site)):$path;
		$code=$this->barheader($GLOBALS["LANG"]->getLL("uploadImage").":");
		$code.='<table border=0 cellpadding=0 cellspacing=3><FORM action="tce_file.php" method="POST" name="editform" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'"><tr><td>';
		$code.="<strong>".$GLOBALS["LANG"]->getLL("path").":</strong> ".$header."</td></tr><tr><td>";
		for ($a=1;$a<=$count;$a++)	{
			$code.='<input type="File" name="upload_'.$a.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(30).'>
				<input type="Hidden" name="file[upload]['.$a.'][target]" value="'.$path.'">
				<input type="Hidden" name="file[upload]['.$a.'][data]" value="'.$a.'"><BR>';
		}
		$code.='<input type="Hidden" name="redirect" value="browse_links.php?act='.$GLOBALS["act"].'&mode='.$GLOBALS["mode"].'&expandFolder='.rawurlencode($path).'&bparams='.rawurlencode(t3lib_div::GPvar("bparams")).'"><input type="Submit" name="submit" value="'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:file_upload.php.submit").'"></td></tr></FORM></table>';
		return $code;
	}
	
	/**
	 * For TBE: Makes an upload form (similar to the one from rte_select_image.php)
	 * 
	 * @param	[type]		$path: ...
	 * @return	[type]		...
	 */
	function createFolder($path)	{
		$header = t3lib_div::isFirstPartOfStr($path,PATH_site)?substr($path,strlen(PATH_site)):$path;
		$code=$this->barheader($GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:file_newfolder.php.pagetitle").":");
		$code.='<table border=0 cellpadding=0 cellspacing=3><FORM action="tce_file.php" method="POST" name="editform2"><tr><td>';
		$code.="<strong>".$GLOBALS["LANG"]->getLL("path").":</strong> ".$header."</td></tr><tr><td>";
		$a=1;
		$code.='<input'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).' type="Text" name="file[newfolder]['.$a.'][data]"><input type="Hidden" name="file[newfolder]['.$a.'][target]" value="'.$path.'">';
		$code.='<input type="Hidden" name="redirect" value="browse_links.php?act='.$GLOBALS["act"].'&mode='.$GLOBALS["mode"].'&expandFolder='.rawurlencode($path).'&bparams='.rawurlencode(t3lib_div::GPvar("bparams")).'"><input type="Submit" name="submit" value="'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:file_newfolder.php.submit").'"></td></tr></FORM></table>';
		return $code;
	}
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * Rich Text Editor (RTE) link selector (MAIN function)
	 * 
	 * @param	[type]		$content: ...
	 * @param	[type]		$wiz: ...
	 * @return	[type]		...
	 */
	function main_rte($content="",$wiz=0)	{
		global $SOBE,$act,$mode,$curUrlInfo,$curUrlArray,$LANG;
			// Starting content:
	//		debug($this->thisConfig);
		$content.=$this->doc->startPage("RTE link");
		
		$allowedItems = array_diff(explode(",","page,file,url,mail,spec"),t3lib_div::trimExplode(",",$this->thisConfig["blindLinkOptions"],1));
		reset($allowedItems);
		if (!in_array($act,$allowedItems))	$act = current($allowedItems);
		
			// Making menu in top:
		$menu='<table border=0 cellpadding=2 cellspacing=1><tr>';
		$bgcolor=' bgcolor="'.$this->doc->bgColor4.'"';
		$bgcolorA=' bgcolor="'.$this->doc->bgColor5.'"';
		if (!$wiz)	$menu.='<td align=center nowrap width=15%'.$bgcolor.'><a href="#" onclick="self.parent.parent.renderPopup_unLink();return false;"><strong>'.$GLOBALS["LANG"]->getLL("removeLink").'</strong></a></td>';
		if (in_array("page",$allowedItems))	$menu.='<td align=center nowrap width=15%'.($act=="page"?$bgcolorA:$bgcolor).'><a href="#" onclick="jumpToUrl(\'?act=page\');return false;"><strong>'.$GLOBALS["LANG"]->getLL("page").'</strong></a></td>';
		if (in_array("file",$allowedItems))	$menu.='<td align=center nowrap width=15%'.($act=="file"?$bgcolorA:$bgcolor).'><a href="#" onclick="jumpToUrl(\'?act=file\');return false;"><strong>'.$GLOBALS["LANG"]->getLL("file").'</strong></a></td>';
		if (in_array("url",$allowedItems))	$menu.='<td align=center nowrap width=15%'.($act=="url"?$bgcolorA:$bgcolor).'><a href="#" onclick="jumpToUrl(\'?act=url\');return false;"><strong>'.$GLOBALS["LANG"]->getLL("extUrl").'</strong></a></td>';
		if (in_array("mail",$allowedItems))	$menu.='<td align=center nowrap width=15%'.($act=="mail"?$bgcolorA:$bgcolor).'><a href="#" onclick="jumpToUrl(\'?act=mail\');return false;"><strong>'.$GLOBALS["LANG"]->getLL("email").'</strong></a></td>';
		if (is_array($this->thisConfig["userLinks."]) && in_array("spec",$allowedItems))	$menu.='<td align=center nowrap width=15%'.($act=="spec"?$bgcolorA:$bgcolor).'><a href="#" onclick="jumpToUrl(\'?act=spec\');return false;"><strong>'.$GLOBALS["LANG"]->getLL("special").'</strong></a></td>';
		$menu.='</tr></table>';
		
		$content.='<img src=clear.gif width=1 height=2>';
		$content.=$this->printCurrentUrl($curUrlInfo["info"]);
		$content.='<img src=clear.gif width=1 height=2>';
		$content.=$menu;
		$content.='<img src=clear.gif width=1 height=10>';
			
		switch($act)	{
			case "mail":
				$extUrl='<table border=0 cellpadding=2 cellspacing=1><form name="lurlform" id="lurlform"><tr>';
				$extUrl.='<td width=90>'.$GLOBALS["LANG"]->getLL("emailAddress").':</td>';
				$extUrl.='<td><input type="text" name="lemail"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).' value="'.($curUrlInfo["act"]=="mail"?$curUrlInfo["info"]:"").'"> <input type="submit" value="'.$GLOBALS["LANG"]->getLL("setLink").'" onclick="setTarget(\'\');setValue(\'mailto:\'+document.lurlform.lemail.value); return link_current();"></td>';
				$extUrl.='</tr></form></table>';
		
				$content.=$extUrl;
			break;
			case "url":
				$extUrl='<table border=0 cellpadding=2 cellspacing=1><form name="lurlform" id="lurlform"><tr>';
				$extUrl.='<td width=90>URL:</td>';
				$extUrl.='<td><input type="text" name="lurl"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).' value="'.($curUrlInfo["act"]=="url"?$curUrlInfo["info"]:"http://").'"> <input type="submit" value="'.$GLOBALS["LANG"]->getLL("setLink").'" onclick="setValue(document.lurlform.lurl.value); return link_current();"></td>';
				$extUrl.='</tr></form></table>';
				
				$content.=$extUrl;
			break;
			case "file":
				$foldertree = t3lib_div::makeInstance("rteFolderTree");
				$tree=$foldertree->getBrowsableTree();
				
	
		//		debug($GLOBALS["HTTP_GET_VARS"]);
				if (!$GLOBALS["curUrlInfo"]["value"] || $GLOBALS["curUrlInfo"]["act"]!="file")	{
					$cmpPath="";
				} else if (substr(trim($GLOBALS["curUrlInfo"]["info"]),-1)!="/")	{
					$cmpPath=PATH_site.dirname($GLOBALS["curUrlInfo"]["info"])."/";
					if (!isset($GLOBALS["HTTP_GET_VARS"]["expandFolder"]))			$GLOBALS["HTTP_GET_VARS"]["expandFolder"] = $cmpPath;
				} else {
					$cmpPath=PATH_site.$GLOBALS["curUrlInfo"]["info"];
				}
	
				
				list(,,$specUid) = explode("_",t3lib_div::GPvar("PM"));
				$files = $this->expandFolder($foldertree->specUIDmap[$specUid]);
				
				$content.= '<table border=0 cellpadding=0 cellspacing=0>
				<tr>
					<td valign=top><font face=verdana size=1 color=black>'.$this->barheader($GLOBALS["LANG"]->getLL("folderTree").':').$tree.'</font></td>
					<td>&nbsp;</td>
					<td valign=top><font face=verdana size=1 color=black>'.$files.'</font></td>
				</tr>
				</table>
				<BR>';
			break;
			case "spec":
				if (is_array($this->thisConfig["userLinks."]))	{
					$subcats=array();
					$v=$this->thisConfig["userLinks."];
					reset($v);
					while(list($k2)=each($v))	{
						$k2i = intval($k2);
						if (substr($k2,-1)=="." && is_array($v[$k2i."."]))	{
							$title = trim($v[$k2i]);
							if (!$title)	{
								$title=$v[$k2i."."]["url"];
							} else {
								$title=$LANG->sL($title,1);
							}
							$description=$v[$k2i."."]["description"] ? $LANG->sL($v[$k2i."."]["description"],1)."<BR>" : "";
							$onClickEvent='';
							if (isset($v[$k2i."."]["target"]))	$onClickEvent.="setTarget('".$v[$k2i."."]["target"]."');";
							$v[$k2i."."]["url"] = str_replace("###_URL###",$this->siteURL,$v[$k2i."."]["url"]);
	
							if (substr($v[$k2i."."]["url"],0,7)=="http://" || substr($v[$k2i."."]["url"],0,7)=="mailto:")	{
								$onClickEvent.="cur_href=unescape('".rawurlencode($v[$k2i."."]["url"])."');link_current();";
							} else {
								$onClickEvent.="link_spec(unescape('".$this->siteURL.rawurlencode($v[$k2i."."]["url"])."'));";
							}
							$icon = ''; //'<img src="gfx/123_go.png" width="50" border=0>';
							$A=array('<a href="#" onclick="'.htmlspecialchars($onClickEvent).'return false;">','</a>');
	
	//	debug(array($onClickEvent));
							$subcats[$k2i]='<tr>
								<td bgColor="'.$this->doc->bgColor4.'" valign=top>'.$A[0].$icon.$A[1].'</td>
								<td bgColor="'.$this->doc->bgColor4.'" valign=top>'.$A[0].'<strong>'.$title.($curUrlInfo["info"]==$v[$k2i."."]["url"]?'<img src="gfx/blinkarrow_right.gif" width="5" height="9" align=top vspace=1 hspace=5 border=0>':'').'</strong><BR>'.$description.$A[1].'</td>
							</tr>';
						}
					}
					ksort($subcats);
					$content.= '<table border=0 cellpadding=1 cellspacing=1>
					<tr>
								<td bgColor="'.$this->doc->bgColor5.'" valign=top colspan=2><strong>'.$LANG->getLL("special").'</strong></td>
							</tr>
					'.implode("",$subcats).'</table><BR>';
	//				debug($subcats);
				}
			break;
			case "page":
			default:
				$pagetree = t3lib_div::makeInstance("rtePageTree");
				$tree=$pagetree->getBrowsableTree(" AND doktype!=255");
				$cElements = $this->expandPage();
				$content.= '<table border=0 cellpadding=0 cellspacing=0>
				<tr>
					<td valign=top><font face=verdana size=1 color=black>'.$this->barheader($GLOBALS["LANG"]->getLL("pageTree").':').$tree.'</font></td>
					<td>&nbsp;</td>
					<td valign=top><font face=verdana size=1 color=black>'.$cElements.'</font></td>
				</tr>
				</table>
				<BR>';
			break;
		}
		
			// Target:
		if ($act!="mail")	{
			$ltarget='<table border=0 cellpadding=2 cellspacing=1><form name="ltargetform" id="ltargetform"><tr>';
			$ltarget.='<td width=90>'.$GLOBALS["LANG"]->getLL("target").':</td>';
			$ltarget.='<td><input type="text" name="ltarget" onChange="setTarget(this.value);" value="'.htmlspecialchars($this->setTarget).'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).'></td>';
			$ltarget.='<td><select name="ltarget_type" onChange="setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
			<option></option>
			<option value="_top">'.$GLOBALS["LANG"]->getLL("top").'</option>
			<option value="_blank">'.$GLOBALS["LANG"]->getLL("newWindow").'</option>
			</select></td>';
			if (($curUrlInfo["act"]=="page" || $curUrlInfo["act"]=="file") && $curUrlArray["href"])	{
				$ltarget.='<td><input type="submit" value="'.$GLOBALS["LANG"]->getLL("update").'" onclick="return link_current();"></td>';
			}
			$ltarget.='</tr></form></table>';
			
			$content.=$ltarget;
		}
		
		$content.= $this->doc->endPage();
		return $content;
	}
	
	
	
	
	
	
	
	/**
	 * TBE Record Browser (MAIN function)
	 * 
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function main_db($content="")	{
		global $SOBE;
	
			// Starting content:
		$content.=$this->doc->startPage("TBE file selector");
		$content.='<img src=clear.gif width=1 height=2>';
		$pArr = explode("|",t3lib_div::GPvar("bparams"));
	
	//	debug($pArr);
	
		$pagetree = t3lib_div::makeInstance("TBE_PageTree");
		$pagetree->script="browse_links.php";
		$pagetree->ext_pArrPages = !strcmp($pArr[3],"pages")?1:0;
	
		$tree=$pagetree->getBrowsableTree(" AND doktype!=255");
		$cElements = $this->TBE_expandPage($pArr[3]);
	
		$content.= '<table border=0 cellpadding=0 cellspacing=0>
		<tr>
			<td valign=top><font face=verdana size=1 color=black>'.$this->barheader($GLOBALS["LANG"]->getLL("pageTree").':').$tree.'</font></td>
			<td>&nbsp;</td>
			<td valign=top><font face=verdana size=1 color=black>'.$cElements.'</font></td>
		</tr>
		</table>
		<BR>';
	
		return $content;
	}
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * TBE File Browser (MAIN function)
	 * 
	 * @param	[type]		$content: ...
	 * @param	[type]		$mode: ...
	 * @return	[type]		...
	 */
	function main_file($content="",$mode)	{
		global $SOBE,$BE_USER;
	
			// Starting content:
		$content.=$this->doc->startPage("TBE file selector");
		$content.='<img src=clear.gif width=1 height=2>';
		$pArr = explode("|",t3lib_div::GPvar("bparams"));
	
	
		// ***************************
		// Upload
		// ***************************
		$fileProcessor = t3lib_div::makeInstance("t3lib_basicFileFunctions");
		$fileProcessor->init($GLOBALS["FILEMOUNTS"], $GLOBALS["TYPO3_CONF_VARS"]["BE"]["fileExtensions"]);
		$path=t3lib_div::GPvar("expandFolder");
		if (!$path || !@is_dir($path))	{
			$path = $fileProcessor->findTempFolder()."/";	// The closest TEMP-path is found
		}
		if ($path!="/" && @is_dir($path))	{
			$uploadForm=$this->uploadForm($path)."<BR>";
			$createFolder=$this->createFolder($path)."<BR>";
		} else {
			$createFolder="";
			$uploadForm="";
		}
	
		if ($BE_USER->getTSConfigVal("options.uploadFieldsInTopOfEB"))	$content.=$uploadForm;
	
		// FOLDER TREE:
	
		$foldertree = t3lib_div::makeInstance("TBE_FolderTree");
		$foldertree->script="browse_links.php";
		$foldertree->ext_noTempRecyclerDirs = ($mode == "filedrag");
		$tree=$foldertree->getBrowsableTree();
		
		list(,,$specUid) = explode("_",t3lib_div::GPvar("PM"));
		
		if ($mode=="filedrag")	{
			$files = $this->TBE_dragNDrop($foldertree->specUIDmap[$specUid],$pArr[3]);
		} else {
			$files = $this->TBE_expandFolder($foldertree->specUIDmap[$specUid],$pArr[3]);
		}
		
		$content.= '<table border=0 cellpadding=0 cellspacing=0>
		<tr>
			<td valign=top><font face=verdana size=1 color=black>'.$this->barheader($GLOBALS["LANG"]->getLL("folderTree").':').$tree.'</font></td>
			<td>&nbsp;</td>
			<td valign=top><font face=verdana size=1 color=black>'.$files.'</font></td>
		</tr>
		</table>
		<BR>';
	
		if (!$BE_USER->getTSConfigVal("options.uploadFieldsInTopOfEB"))	$content.=$uploadForm;
		if ($BE_USER->isAdmin() || $BE_USER->getTSConfigVal("options.createFoldersInEB"))	$content.=$createFolder;
		
		$content.= $this->doc->endPage();
		return $content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/browse_links.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/browse_links.php"]);
}








// Make instance:
$SOBE = t3lib_div::makeInstance("SC_browse_links");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>