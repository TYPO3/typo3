<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * Shows information about a database or file item
 *
 * HTTP_GET_VARS:
 * $table	:		Record table (or filename)
 * $uid	:		Record uid  (or "" when filename)
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 
 
$BACK_PATH="";
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
require_once (PATH_t3lib."class.t3lib_page.php");
require_once (PATH_t3lib."class.t3lib_loaddbgroup.php");
require_once (PATH_t3lib."class.t3lib_transferdata.php");


// ***************************
// Script Classes
// ***************************
class transferData extends t3lib_transferData	{
	var $formname = "loadform";
	var $loading = 1;
	

		// Extra for show_item.php:
	var $theRecord = Array();

	function regItem($table, $id, $field, $content)	{
		t3lib_div::loadTCA($table);
		$config = $GLOBALS["TCA"][$table]["columns"][$field]["config"];
		switch($config["type"])	{
			case "input":
				if (isset($config["checkbox"]) && $content==$config["checkbox"])	{$content=""; break;}
				if (t3lib_div::inList($config["eval"],"date"))	{$content = Date($GLOBALS["TYPO3_CONF_VARS"]["SYS"]["ddmmyy"],$content); }
			break;
			case "group":
			break;
			case "select":
				
			break;
		}
		$this->theRecord[$field]=$content;
	}
}
class SC_show_item {
	var $include_once=array();
	var $content;

	var $perms_clause;
	var $access;
	var $pageinfo;
	var $type;
	var $file;
	var $relPath;
	var $row;
	var $table;
	var $uid;
	var $doc;	
	
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		
		$this->perms_clause = $BE_USER->getPagePermsClause(1);
		$this->table = t3lib_div::GPvar("table");
		$this->uid = t3lib_div::GPvar("uid");
		
		$this->access=0;
		$this->type="";
		if (isset($TCA[$this->table]) && $BE_USER->check("tables_select",$this->table))	{
			t3lib_div::loadTCA($this->table);
			$this->type="db";
			$this->uid=intval($this->uid);
			if ($this->uid)	{
				if ((string)$this->table=="pages")	{
					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->uid,$this->perms_clause);
					$this->access = is_array($this->pageinfo) ? 1 : 0;
					$this->row=$this->pageinfo;
				} else {
					$this->row=t3lib_BEfunc::getRecord ($this->table,$this->uid);
					if ($this->row)	{
						$this->pageinfo = t3lib_BEfunc::readPageAccess($this->row["pid"],$this->perms_clause);
						$this->access = is_array($this->pageinfo) ? 1 : 0;
					}
				}
				
				
				$treatData = t3lib_div::makeInstance("t3lib_transferData");
				$treatData->renderRecord($this->table, $this->uid, 0, $this->row);
				$cRow = $treatData->theRecord;
			}
		} else	{
			// if the filereference $this->file is relative, we correct the path
			if (substr($this->table,0,3)=="../")	{
				$this->file = PATH_site.ereg_replace("^\.\./","",$this->table);
				$this->relPath=1;
			} else {
				$this->file = $this->table;
				$this->relPath=0;
			}
			if (@is_file($this->file))	{
				$this->type="file";
				$this->access=1;

				$this->include_once[]=PATH_t3lib."class.t3lib_stdgraphic.php";
			}
		}
		
		
		$this->doc = t3lib_div::makeInstance("smallDoc");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->tableLayout = Array (
			"defRow" => Array (
				"0" => Array('<TD valign="top">','</td>'),
				"defCol" => Array('<TD><img src="'.$this->backPath.'clear.gif" width=15 height=1></td><td valign="top">','</td>')
			)
		);
		
		
		$this->content.=$this->doc->startPage($LANG->sL("LLL:EXT:lang/locallang_core.php:show_item.php.viewItem"));
		$this->content.=$this->doc->header($LANG->sL("LLL:EXT:lang/locallang_core.php:show_item.php.viewItem"));
		$this->content.=$this->doc->spacer(5);
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		
		if ($this->access)	{
			$returnLinkTag = t3lib_div::GPvar("returnUrl") ? '<a href="'.t3lib_div::GPvar("returnUrl").'" class="typo3-goBack">' : '<a href="#" onClick="window.close();">';
			
			if ($this->type=="db")	{
				$code=$this->doc->getHeader($this->table,$this->row,$this->pageinfo["_thePath"],1).'<BR>';
				$this->content.=$this->doc->section('',$code);
		
				$codeArr=Array();
				$i=0;
		
				$fieldList=explode(",",$TCA[$this->table]["interface"]["showRecordFieldList"]);
				while(list(,$name)=each($fieldList))	{
					$name=trim($name);
					if ($TCA[$this->table]["columns"][$name])	{
						if (!$TCA[$this->table]["columns"][$name]["exclude"] || $GLOBALS["BE_USER"]->check("non_exclude_fields",$this->table.":".$name))	{		
							$i++;
							$codeArr[$i][]=stripslashes($LANG->sL(t3lib_BEfunc::getItemLabel($this->table,$name)));
							$codeArr[$i][]=t3lib_BEfunc::getProcessedValue($this->table,$name,$this->row[$name]);
						}
					}
				}
				$this->content.=$this->doc->section('',$this->doc->table($codeArr));
				$this->content.=$this->doc->divider(2);
				
				$code="";
				$code.='Path: '.t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],48).'<BR>';
				$code.='Table: '.$LANG->sL($TCA[$this->table]["ctrl"]["title"]).' ('.$this->table.') - UID: '.$this->uid.'<BR>';
				$this->content.=$this->doc->section('',$code);
			}
			if ($this->type=="file")	{
				$imgInfo="";
		
				$imgObj = t3lib_div::makeInstance("t3lib_stdGraphic");
				$imgObj->init();
				$imgObj->mayScaleUp=0;
				$imgObj->tempPath=PATH_site.$imgObj->tempPath;
		
				$imgInfo = $imgObj->getImageDimensions($this->file);		
		
				$fI = t3lib_div::split_fileref($this->file);
				$ext = $fI["fileext"];
		//		debug($fI);
				if ($imgInfo)	{
					$code="";
					if ($this->relPath || t3lib_div::isFirstPartOfStr($this->file,PATH_site))	{
						$code.='<a href="../'.substr($this->file,strlen(PATH_site)).'" target="_blank"><b>'.$LANG->sL("LLL:EXT:lang/locallang_core.php:show_item.php.file").':</b> '.$fI["file"].'</a>';
					} else {
						$code.='<b>'.$LANG->sL("LLL:EXT:lang/locallang_core.php:show_item.php.file").':</b> '.$fI["file"];
					}
					$code.=' &nbsp;&nbsp;<b>'.$LANG->sL("LLL:EXT:lang/locallang_core.php:show_item.php.filesize").':</b> '.t3lib_div::formatSize(@filesize($this->file));
					$code.='<BR>';
					$code.='<b>'.$LANG->sL("LLL:EXT:lang/locallang_core.php:show_item.php.dimensions").':</b> '.$imgInfo[0].'x'.$imgInfo[1].' pixels';
					$this->content.=$this->doc->section('',$code);
		
					$this->content.=$this->doc->divider(2);
			
					$imgInfo = $imgObj->imageMagickConvert($this->file,"web","346","200m","","","",1);
					$imgInfo[3] = "../".substr($imgInfo[3],strlen(PATH_site));
					$code= '<BR><div align="center">'.$returnLinkTag.$imgObj->imgTag($imgInfo).'</a></div>';
					$this->content.=$this->doc->section('',$code);
				} else {
					$code="";
					$icon = t3lib_BEfunc::getFileIcon($ext);	
					$url = 'gfx/fileicons/'.$icon;
					$code.='<a href="../'.substr($this->file,strlen(PATH_site)).'" target="_blank"><img src="'.$url.'" width=18 height=16 align="top" border=0> <b>File:</b> '.$fI["file"].'</a> &nbsp;&nbsp;<b>Size:</b> '.t3lib_div::formatSize(@filesize($this->file)).'<BR>';
					$this->content.=$this->doc->section('',$code);
		
					$lowerFilename = strtolower($this->file);
					if (TYPO3_OS!="WIN" && !$GLOBALS["TYPO3_CONF_VARS"]["BE"]["disable_exec_function"])	{
						if ($ext=="zip")	{
							$this->content.=$this->doc->divider(10);
							$code="";
							exec("unzip -l ".$this->file, $t);
							if (is_array($t))	{
								reset($t);
								next($t);
								next($t);
								next($t);
								while(list(,$val)=each($t))	{
									$parts = explode(" ",trim($val),7);
									$code.=$parts[6]."<BR>";
								}
								$code="<nobr>".$code."</nobr>";
							}
							$this->content.=$this->doc->section('',$code);
						} elseif($ext=="tar" || $ext=="tgz" || substr($lowerFilename,-6)=="tar.gz" || substr($lowerFilename,-5)=="tar.z")	{
							$this->content.=$this->doc->divider(10);
							$code="";
							if ($ext=="tar")	{
								$compr="";
							} else {
								$compr="z";
							}
							exec("tar t".$compr."f ".$this->file, $t);
							if (is_array($t))	{
								reset($t);
								while(list(,$val)=each($t))	{
									$code.=$val."<BR>";
								}
								$code="<nobr>".$code."</nobr>";
							}
							$this->content.=$this->doc->section('',$code);
						}
					}
					if ($ext=="ttf")	{
						$thumbScript="thumbs.php";
						$params = "&file=".rawurlencode($this->file);
						$url = $thumbScript.'?&dummy='.$GLOBALS["EXEC_TIME"].$params;
						$thumb='<BR><img src="'.$url.'" hspace=40 border=0 title="'.trim($this->file).'">';
		//				$thumb = t3lib_BEfunc::thumbCode(array("resources"=>$fI["file"]),"sys_template","resources","","",$fI["path"],1);
						$this->content.=$this->doc->section('',$thumb);
					}
				}
			}
		
			if (t3lib_div::GPvar("returnUrl"))	{
				$this->content.=$this->doc->section('','<BR>'.$returnLinkTag.'<strong>&lt; '.$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.goBack").'</strong></a>');
			}
		}		
	}
	function printContent()	{
		global $SOBE;

		$this->content.=$this->doc->spacer(8);
		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
	
	// ***************************
	// OTHER FUNCTIONS:	
	// ***************************
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/show_item.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/show_item.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_show_item");
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->main();
$SOBE->printContent();
?>