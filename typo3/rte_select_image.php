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
 * Displays image selector for the RTE
 * Belongs to the "rte" extension
 * 
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 

$BACK_PATH="";
require ("init.php");
require ("template.php");
require_once (PATH_t3lib."class.t3lib_browsetree.php");
require_once (PATH_t3lib."class.t3lib_foldertree.php");
require_once (PATH_t3lib."class.t3lib_stdgraphic.php");
require_once (PATH_t3lib."class.t3lib_basicfilefunc.php");
include ("sysext/lang/locallang_rte_select_image.php");



// ***************************
// Script Classes
// ***************************
class localFolderTree extends t3lib_folderTree {
	function wrapTitle($title,$v)	{
		if ($this->ext_isLinkable($v))	{
			return '<a href="#" onClick="return jumpToUrl(\'?expandFolder='.rawurlencode($v["path"]).'\');">'.$title.'</a>';
		} else {
			return '<font color="#666666">'.$title.'</font>';
		}
	}
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
			$out.='<tr'.$bgColor.'><td nowrap>'.$v["HTML"].$this->wrapTitle(t3lib_div::fixed_lgd($v["row"]["title"],$titleLen),$v["row"]).'</td></tr>';
		}
		$out='<table border=0 cellpadding=0 cellspacing=0>'.$out.'</table>';
		return $out;
	}
	function PM_ATagWrap($icon,$cmd,$bMark="")	{
		if ($bMark)	{
			$anchor = "#".$bMark;
			$name=' name="'.$bMark.'"';
		}
		return '<a href="#"'.$name.' onClick="return jumpToUrl(\'?PM='.$cmd.'\',\''.$anchor.'\');">'.$icon.'</a>';
	}
	function ext_getRelFolder($path)	{
		return substr($path,strlen(PATH_site));
	}
	function ext_isLinkable($v)	{
		$webpath=t3lib_BEfunc::getPathType_web_nonweb($v["path"]);
		if ($GLOBALS["SOBE"]->act=="magic") return 1;//$webpath="web";	// The web/non-web path does not matter if the mode is "magic"

		if (strstr($v["path"],"_recycler_") || strstr($v["path"],"_temp_") || $webpath!="web")	{
			return 0;
		} 
		return 1;
	}
}
class SC_rte_select_image {
	var $content;
	var $siteUrl;
	
	var $act;
	var $modData;
	var $thisConfig;
	var $allowedItems;
	var $doc;	
	var $imgPath;

	function preinit()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		// Current site url:
		$this->siteUrl = t3lib_div::getIndpEnv("TYPO3_SITE_URL");
		
		// Determine nature of current url:
		$this->act=t3lib_div::GPvar("act");
		
		$this->modData = $BE_USER->getModuleData("rte_select_image.php","ses");
		if ($this->act!="image")	{
			if (isset($this->act))	{
				$this->modData["act"]=$this->act;
				$BE_USER->pushModuleData("rte_select_image.php",$this->modData);
			} else {
				$this->act=$this->modData["act"];
			}
		}
		$expandPage = t3lib_div::GPvar("expandFolder");
		if (isset($expandPage))	{
			$this->modData["expandFolder"]=$expandPage;
			$BE_USER->pushModuleData("rte_select_image.php",$this->modData);
		} else {
			$HTTP_GET_VARS["expandFolder"]=$this->modData["expandFolder"];
		}
		
		if (!$this->act)	{
			$this->act="magic";
		}
		
		
		
		$RTEtsConfigParts = explode(":",t3lib_div::GPvar("RTEtsConfigParams"));
		if (count($RTEtsConfigParts)<2)	die("Error: The GET parameter 'RTEtsConfigParams' was missing. Close the window.");
		$RTEsetup = $GLOBALS["BE_USER"]->getTSConfig("RTE",t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5])); 
		$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup["properties"],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		$this->imgPath = $RTEtsConfigParts[6];

		$this->allowedItems = array_diff(explode(",","magic,plain,dragdrop,image"),t3lib_div::trimExplode(",",$this->thisConfig["blindImageOptions"],1));
		reset($this->allowedItems);
		if (!in_array($this->act,$this->allowedItems))	$this->act = current($this->allowedItems);
	}
	function rteImageStorageDir()	{
#		debug($this->thisConfig);
#		exit;
		$dir = $this->imgPath ? $this->imgPath : $GLOBALS["TYPO3_CONF_VARS"]["BE"]["RTE_imageStorageDir"];;
#debug($dir);
		return $dir;
#		return $this->thisConfig["proc."]["RTE_imageStorageDir"]?$this->thisConfig["proc."]["RTE_imageStorageDir"]:$GLOBALS["TYPO3_CONF_VARS"]["BE"]["RTE_imageStorageDir"];
	}
	function magicProcess()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		if ($this->act=="magic" && t3lib_div::GPvar("insertMagicImage"))	{
			$filepath = t3lib_div::GPvar("insertMagicImage");
			
			$imgObj = t3lib_div::makeInstance("t3lib_stdGraphic");
			$imgObj->init();
			$imgObj->mayScaleUp=0;
			$imgObj->tempPath=PATH_site.$imgObj->tempPath;
		
			$imgInfo = $imgObj->getImageDimensions($filepath);
			
			if (is_array($imgInfo) && count($imgInfo)==4 && $this->rteImageStorageDir())	{
				$fI=pathinfo($imgInfo[3]);
				$fileFunc = t3lib_div::makeInstance("t3lib_basicFileFunctions");
				$basename = $fileFunc->cleanFileName("RTEmagicP_".$fI["basename"]);
				$destPath =PATH_site.$this->rteImageStorageDir();
				if (@is_dir($destPath))	{
					$destName = $fileFunc->getUniqueName($basename,$destPath);
					@copy($imgInfo[3],$destName);
		
					$cHeight=t3lib_div::intInRange(t3lib_div::GPvar("cHeight"),0,500);
					$cWidth=t3lib_div::intInRange(t3lib_div::GPvar("cWidth"),0,500);
					if (!$cHeight)	$cHeight=200;
					if (!$cWidth)	$cWidth=300;
						// This thing allows images to be based on their width - to a certain degree - by setting a high height. Then we're almost certain the image will be based on the width 
							$cHeight=1000;
		//			debug(array($cHeight,$cWidth));
		//exit;			
					$imgI = $imgObj->imageMagickConvert($filepath,"WEB",$cWidth."m",$cHeight."m");	// ($imagefile,$newExt,$w,$h,$params,$frame,$options,$mustCreate=0)
			//		debug($imgI);
					if ($imgI[3])	{
						$fI=pathinfo($imgI[3]);
						$mainBase="RTEmagicC_".substr(basename($destName),10).".".$fI["extension"];
						$destName = $fileFunc->getUniqueName($mainBase,$destPath);
						@copy($imgI[3],$destName);
		
						$iurl = $this->siteUrl.substr($destName,strlen(PATH_site));
						echo'
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Untitled</title>
</head>
<script language="javascript" type="text/javascript">
	function insertImage(file,width,height)	{
		self.parent.parent.renderPopup_insertImage(\'<img src="\'+file+\'" width="\'+width+\'" height="\'+height+\'" border=0>\');
	}
</script>
<body>
<script language="javascript" type="text/javascript">
	insertImage(\''.$iurl.'\','.$imgI[0].','.$imgI[1].');
</script>
</body>
</html>';
					}
					
				}
			}
			exit;
		}
	}
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->doc = t3lib_div::makeInstance("template");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='
		<script language="javascript" type="text/javascript">
			function jumpToUrl(URL,anchor)	{
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$this->act.'" : "";
				var RTEtsConfigParams = "&RTEtsConfigParams='.rawurlencode(t3lib_div::GPvar("RTEtsConfigParams")).'";
		
				var cur_width = selectedImageRef ? "&cWidth="+selectedImageRef.width : "";
				var cur_height = selectedImageRef ? "&cHeight="+selectedImageRef.height : "";
		
				var theLocation = URL+add_act+RTEtsConfigParams+cur_width+cur_height+(anchor?anchor:"");
				document.location = theLocation;
				return false;
			}
			function insertImage(file,width,height)	{
				self.parent.parent.renderPopup_insertImage(\'<img src="\'+file+\'" width="\'+width+\'" height="\'+height+\'" border=0>\');
			}
			function launchView(url)	{
				var thePreviewWindow="";
				thePreviewWindow = window.open("'.$this->siteUrl.TYPO3_mainDir.'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");	
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function getCurrentImageRef()	{
				if (self.parent.parent 
				&& self.parent.parent.document.idPopup 
				&& self.parent.parent.document.idPopup.document 
				&& self.parent.parent.document.idPopup.document._selectedImage)	{
		//			self.parent.parent.debugObj(self.parent.parent.document.idPopup.document._selectedImage);
					return self.parent.parent.document.idPopup.document._selectedImage;
				}
				return "";
			}
			function printCurrentImageOptions()	{
		//		alert(selectedImageRef.href);
				var styleSelector=\'<select name="iClass" style="width:140px;"><option value=""></option><option value="TestClass">TestClass</option></select>\';
				var alignSelector=\'<select name="iAlign" style="width:60px;"><option value=""></option><option value="left">Left</option><option value="right">Right</option></select>\';
				var bgColor=\' bgColor="'.$this->doc->bgColor4.'"\';
				var sz="";
				sz+=\'<table border=0 cellpadding=1 cellspacing=1><form action="" name="imageData">\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL("width").': <input type="text" name="iWidth" value=""'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).'>&nbsp;&nbsp;'.$LANG->getLL("height").': <input type="text" name="iHeight" value=""'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).'>&nbsp;&nbsp;'.$LANG->getLL("border").': <input type="checkbox" name="iBorder" value="1"></td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL("margin_lr").': <input type="text" name="iHspace" value=""'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).'>&nbsp;&nbsp;'.$LANG->getLL("margin_tb").': <input type="text" name="iVspace" value=""'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).'></td></tr>\';
		//		sz+=\'<tr><td\'+bgColor+\'>Textwrapping: \'+alignSelector+\'&nbsp;&nbsp;Style: \'+styleSelector+\'</td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL("title").': <input type="text" name="iTitle"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).'></td></tr>\';
				sz+=\'<tr><td><input type="submit" value="'.$LANG->getLL("update").'" onClick="return setImageProperties();"></td></tr>\';
				sz+=\'</form></table>\';
				return sz;
			}
			function setImageProperties()	{
				if (selectedImageRef)	{
					selectedImageRef.width=document.imageData.iWidth.value;
					selectedImageRef.height=document.imageData.iHeight.value;
					selectedImageRef.vspace=document.imageData.iVspace.value;
					selectedImageRef.hspace=document.imageData.iHspace.value;
					selectedImageRef.title=document.imageData.iTitle.value;
					selectedImageRef.alt=document.imageData.iTitle.value;
		
					selectedImageRef.border= (document.imageData.iBorder.checked ? 1 : 0);
		
		/*			
					var iAlign = document.imageData.iAlign.options[document.imageData.iAlign.selectedIndex].value;
					if (iAlign || selectedImageRef.align)	{
						selectedImageRef.align=iAlign;
					}
		
					selectedImageRef.style.cssText="";
		
					var iClass = document.imageData.iClass.options[document.imageData.iClass.selectedIndex].value;
					if (iClass || (selectedImageRef.attributes["class"] && selectedImageRef.attributes["class"].value))	{
						selectedImageRef["class"]=iClass;
						selectedImageRef.attributes["class"].value=iClass;
					}
		*/
		//			selectedImageRef.style="";
					self.parent.parent.edHidePopup();
				}
				return false;
			}
			function insertImagePropertiesInForm()	{
				if (selectedImageRef)	{
					document.imageData.iWidth.value = selectedImageRef.width;
					document.imageData.iHeight.value = selectedImageRef.height;
					document.imageData.iVspace.value = selectedImageRef.vspace;
					document.imageData.iHspace.value = selectedImageRef.hspace;
					document.imageData.iTitle.value = selectedImageRef.title;
					if (parseInt(selectedImageRef.border))	{
						document.imageData.iBorder.checked = 1;
					}
		/*
						// Update align
					var fObj=document.imageData.iAlign;
					var value=selectedImageRef.align;
					var l=fObj.length;
					for (a=0;a<l;a++)	{
						if (fObj.options[a].value == value)	{
							fObj.selectedIndex = a;
						}
					}
						// Update class
							// selectedImageRef.className ??
					var fObj=document.imageData.iClass;
					var value=selectedImageRef.attributes["class"].value;
					var l=fObj.length;
					for (a=0;a<l;a++)	{
						if (fObj.options[a].value == value)	{
							fObj.selectedIndex = a;
						}
					}
					*/
					
				}
			//	alert(document.imageData);
				return false;
			}
			
			function openDragDrop()	{
				var url = "browse_links.php?mode=filedrag&bparams=|||"+escape("gif,jpg,jpeg,png");
				browserWin = window.open(url,"Typo3WinBrowser","height=350,width=600,status=0,menubar=0,resizable=1,scrollbars=1");
				browserWin.focus();
				self.parent.parent.edHidePopup(1);
			}
		
			var selectedImageRef = getCurrentImageRef();	// Setting this to a reference to the image object.
		
			'.($this->act=="dragdrop"?"openDragDrop();":"").'
			
		//	alert(selectedImageRef.href);
		</script>
		';
		
			// Starting content:
		$this->content="";
		$this->content.=$this->doc->startPage("RTE image insert");
	
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		global $FILEMOUNTS;
		
		$menu='<table border=0 cellpadding=2 cellspacing=1><tr>';
		$bgcolor=' bgcolor="'.$this->doc->bgColor4.'"';
		$bgcolorA=' bgcolor="'.$this->doc->bgColor5.'"';
		if ($this->act=="image" || t3lib_div::GPvar("cWidth"))	{	// If $this->act is specifically set to "image" or if cWidth is passed around...
			$menu.='<td align=center nowrap width="25%"'.($this->act=="image"?$bgcolorA:$bgcolor).'><a href="#" onClick="jumpToUrl(\'?act=image\');return false;"><strong>'.$LANG->getLL("currentImage").'</strong></a></td>';
		}
			if (in_array("magic",$this->allowedItems))	$menu.='<td align=center nowrap width="25%"'.($this->act=="magic"?$bgcolorA:$bgcolor).'><a href="#" onClick="jumpToUrl(\'?act=magic\');return false;"><strong>'.$LANG->getLL("magicImage").'</strong></a></td>';
			if (in_array("plain",$this->allowedItems))	$menu.='<td align=center nowrap width="25%"'.($this->act=="plain"?$bgcolorA:$bgcolor).'><a href="#" onClick="jumpToUrl(\'?act=plain\');return false;"><strong>'.$LANG->getLL("plainImage").'</strong></a></td>';
			if (in_array("dragdrop",$this->allowedItems))	$menu.='<td align=center nowrap width="25%"'.$bgcolor.'><a href="#" onClick="openDragDrop();return false;"><strong>'.$LANG->getLL("dragDropImage").'</strong></a></td>';
		$menu.='</tr></table>';
		
		$this->content.='<img src=clear.gif width=1 height=2>';
		$this->content.=$menu;
		$this->content.='<img src=clear.gif width=1 height=10>';
		
		if ($this->act!="image")	{
				// File-folders:	
			$foldertree = t3lib_div::makeInstance("localFolderTree");
			$tree=$foldertree->getBrowsableTree();
			list(,,$specUid) = explode("_",t3lib_div::GPvar("PM"));
			$files = $this->expandFolder($foldertree->specUIDmap[$specUid],$this->act=="plain");
			
			$this->content.= '<table border=0 cellpadding=0 cellspacing=0>
			<tr>
				<td valign=top><font face=verdana size=1 color=black>'.$this->barheader($LANG->getLL("folderTree").':').$tree.'</font></td>
				<td>&nbsp;</td>
				<td valign=top><font face=verdana size=1 color=black>'.$files.'</font></td>
			</tr>
			</table>
			<BR>';
			
			/*
				// Target:
			if ($this->act!="mail")	{
				$ltarget='<table border=0 cellpadding=2 cellspacing=1><form name="ltargetform" id="ltargetform"><tr>';
				$ltarget.='<td width=90>Target:</td>';
				$ltarget.='<td><input type="text" name="ltarget" onChange="setTarget(this.value);" value="'.htmlspecialchars($curUrlArray["target"]).'"></td>';
				$ltarget.='<td><select name="ltarget_type" onChange="setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
				<option></option>
				<option value="_top">Top</option>
				<option value="_blank">New window</option>
				</select></td>';
				if (($curUrlInfo["act"]=="page" || $curUrlInfo["act"]=="file") && $curUrlArray["href"])	{
					$ltarget.='<td><input type="submit" value="Update" onClick="return link_current();"></td>';
				}
				$ltarget.='</tr></form></table>';
				
				$this->content.=$ltarget;
			}
			*/
			
			
			
			
			// ***************************
			// Upload
			// ***************************
			$fileProcessor = t3lib_div::makeInstance("t3lib_basicFileFunctions");
			$fileProcessor->init($FILEMOUNTS, $TYPO3_CONF_VARS["BE"]["fileExtensions"]);
			$path=t3lib_div::GPvar("expandFolder");
			if (!$path || $path=="/" || !@is_dir($path))	{
				$path = $fileProcessor->findTempFolder();	// The closest TEMP-path is found
				if ($path)	$path.="/";
			}
			if ($path && @is_dir($path))	{
				$this->content.=$this->uploadForm($path)."<BR>";
			}
		
			// ***************************
			// Help
			// ***************************
			
			if ($this->act=="magic")	{
				$this->content.='<img src="gfx/icon_note.gif" width="18" height="16" align=top>'.$LANG->getLL("magicImage_msg").'<BR>';
			}
			if ($this->act=="plain")	{
				$this->content.='<img src="gfx/icon_note.gif" width="18" height="16" align=top>'.$LANG->getLL("plainImage_msg").'<BR>';
			}
		} else {
			$this->content.='
			<script language="javascript" type="text/javascript">
		document.write(printCurrentImageOptions());
		insertImagePropertiesInForm();
			</script>
			';
		}

	}
	function printContent()	{
		global $SOBE;

		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
	
	// ***************************
	// OTHER FUNCTIONS:	
	// ***************************

	function expandFolder($expandFolder=0,$plainFlag=0)	{
		global $LANG;

		$expandFolder = $expandFolder ? $expandFolder :t3lib_div::GPvar("expandFolder");
		$out="";
		
		$resolutionLimit_x=640;
		$resolutionLimit_y=680;
		
		if ($expandFolder)	{
			$files = t3lib_div::getFilesInDir($expandFolder,($plainFlag?"jpg,jpeg,gif,png":$GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"]),1,1);	// $extensionList="",$prependPath=0,$order="")
			if (is_array($files))	{
				reset($files);
		
				$out.=$this->barheader(sprintf($LANG->getLL("images").' (%s):',count($files)));
			
				$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
				$picon='<img src="gfx/i/_icon_webfolders.gif" width="18" height="16" align=top>';
				$picon.=htmlspecialchars(t3lib_div::fixed_lgd(basename($expandFolder),$titleLen));
				$out.='<nobr>'.$picon.'</nobr><BR>';
				
				$imgObj = t3lib_div::makeInstance("t3lib_stdGraphic");
				$imgObj->init();
				$imgObj->mayScaleUp=0;
				$imgObj->tempPath=PATH_site.$imgObj->tempPath;

				$noThumbs = $GLOBALS["BE_USER"]->getTSConfigVal("options.noThumbsInRTEimageSelect");
		
				$lines=array();
				while(list(,$filepath)=each($files))	{
					$fI=pathinfo($filepath);
					
					$iurl = $this->siteUrl.substr($filepath,strlen(PATH_site));
//debug($iurl);
					$imgInfo = $imgObj->getImageDimensions($filepath);
					
					
		//			debug($imgInfo);
					
		//			debug($fI);
					$icon = t3lib_BEfunc::getFileIcon(strtolower($fI["extension"]));
					$pDim = $imgInfo[0]."x".$imgInfo[1]." pixels";
					$size=" (".t3lib_div::formatSize(filesize($filepath))."bytes, ".$pDim.")";
					$icon = '<img src="gfx/fileicons/'.$icon.'" width=18 height=16 border=0 title="'.$fI["basename"].$size.'" align=absmiddle>';
					if (!$plainFlag)	{
						$ATag = '<a href="#" onClick="return jumpToUrl(\'?insertMagicImage='.rawurlencode($filepath).'\');">';
					} else {
						$ATag = '<a href="#" onClick="return insertImage(\''.$iurl.'\','.$imgInfo[0].','.$imgInfo[1].');">';
					}
					$ATag_e="</a>";
					if ($plainFlag && ($imgInfo[0]>$resolutionLimit_x || $imgInfo[1]>$resolutionLimit_y))	{
						$ATag="";
						$ATag_e="";
						$ATag2="";
						$ATag2_e="";
					} else {
						$ATag2='<a href="#" onClick="launchView(\''.rawurlencode($filepath).'\'); return false;">';
						$ATag2_e="</a>";
					}
					
					$filenameAndIcon=$ATag.$icon.htmlspecialchars(t3lib_div::fixed_lgd(basename($filepath),$titleLen)).$ATag_e;
					
					$lines[]='<tr bgcolor="'.$this->doc->bgColor4.'"><td nowrap>'.$filenameAndIcon.'&nbsp;</td><td nowrap>'.$pDim.'&nbsp;</td></tr>';
					$lines[]='<tr><td colspan=2>'.(
						$noThumbs ? 
						"" :
						$ATag2.t3lib_BEfunc::getThumbNail("thumbs.php",$filepath,"hspace=5 vspace=5 border=1").$ATag2_e).
						'</td></tr>';
					$lines[]='<tr><td colspan=2><img src=clear.gif width=1 height=3></td></tr>';
				}
				$out.='<table border=0 cellpadding=0 cellspacing=1>'.implode("",$lines).'</table>';
			}
		}
		return $out;
	}
	function uploadForm($path)	{
		global $LANG,$SOBE;

	//	debug($path);
		$count=1;
		$header = t3lib_div::isFirstPartOfStr($path,PATH_site)?substr($path,strlen(PATH_site)):$path;
		$code=$this->barheader($LANG->getLL("uploadImage").":");
		$code.='<table border=0 cellpadding=0 cellspacing=3><FORM action="tce_file.php" method="POST" name="editform" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'"><tr><td>';
		$code.="<strong>".$LANG->getLL("path").":</strong> ".$header."</td></tr><tr><td>";
		for ($a=1;$a<=$count;$a++)	{
			$code.='<input type="File" name="upload_'.$a.'"'.$this->doc->formWidth(30).'>
				<input type="Hidden" name="file[upload]['.$a.'][target]" value="'.$path.'">
				<input type="Hidden" name="file[upload]['.$a.'][data]" value="'.$a.'"><BR>';
		}
		$code.='<input type="Hidden" name="redirect" value="rte_select_image.php?act='.$this->act.'&expandFolder='.rawurlencode($path).'&RTEtsConfigParams='.rawurlencode(t3lib_div::GPvar("RTEtsConfigParams")).'"><input type="Submit" name="submit" value="'.$LANG->sL("LLL:EXT:lang/locallang_core.php:file_upload.php.submit").'"></td></tr></FORM></table>';
		return $code;
	}
	function barheader($str)	{
		global $LANG,$SOBE;

		return '<table border=0 cellpadding=2 cellspacing=0 width=100% bgcolor="'.$this->doc->bgColor5.'"><tr><td><strong>'.$str.'</strong></td></tr></table>';
	}
	function printCurrentUrl($str)	{
		global $LANG,$SOBE;

		return '<table border=0 cellpadding=0 cellspacing=0 width=100% bgcolor="'.$this->doc->bgColor5.'"><tr><td><strong>Current Link:</strong> '.$str.'</td></tr></table>';
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/rte_select_image.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/rte_select_image.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_rte_select_image");
$SOBE->preinit();
$SOBE->magicProcess();
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>