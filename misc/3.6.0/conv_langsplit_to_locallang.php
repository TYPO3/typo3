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
 * Dev-script: Translation of TBE
 *
 * This script prints out all language-labels in Typo3 and provides a way of checking for missing translations.
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

 
die("<strong>This script is for typo3 development and maintenance only. You'll probably find it useless for what you do.</strong><br><br>MUST remove this line in descriptions script before it'll work for you. This is a security precaution. Anyways, you must be logged in as admin as well.");






	// Set this to the extension directory (extKey name)
$BASE="tt_products/";
$SOURCE=$BASE."tca.php";	// source file name, typically tca.php. MUST exist
$DEST=$BASE."locallang_tca.php";	// locallang_tca.php - name to write labels to. MUST NOT exist.
$EXTDIR="ext/";	// extension directory. Here its the "global" one. Don't try using the local i think.












$dont_prefix_table_name=0;	// Set if table name should not be prefixed. That is however recommended most of the time (exception is tt_content...)
$strLen=10;
$DROP_TABLES = "sys_template,static_template,sys_filemounts,be_groups,be_users,sys_action,sys_workflows,user_photomarathon,user_snowboard,static_dc_type,sys_dc_type";



 
$BACK_PATH="../";
define("TYPO3_MOD_PATH", "dev/");
require ($BACK_PATH."init.php");


$Lsplit = explode("|",TYPO3_languages);

function includeFile($path,$pre)	{
	if (@is_file($pre))	include($pre);
	include($path);
	return array($TCA,$LANG_GENERAL_LABELS);
}
function addLsplitStr(&$LOCAL_LANG,$str,$name,$mustAdd=0)	{
	global $Lsplit;
	
	$strArr = explode("|",$str);
	reset($Lsplit);
	while(list($k,$v)=each($Lsplit))	{
		if (!is_array($LOCAL_LANG[$v]))	$LOCAL_LANG[$v]=array();
		if (strcmp(trim($strArr[$k]),"") || $mustAdd)	$LOCAL_LANG[$v][$name]=$strArr[$k];
	}
}
function makeLocalLangScript($LL)	{
	$out="";
	$outLines=array();
	reset($LL);
	while(list($k,$v)=each($LL))	{
		$lines=array();
		reset($v);
		while(list($kk,$vv)=each($v))	{
			$lines[]=chr(9).chr(9).'"'.$kk.'" => "'.str_replace('"','\"',$vv).'",';
		}
		$outLines[]='	"'.$k.'" => Array (
'.implode(chr(10),$lines).'
	),';
	}
	
	$out = trim('
<?php

$LOCAL_LANG = Array (
'.implode(chr(10),$outLines).'
);
?>
	');

	return $out;
}


function makeNewSourceScript($content,$subst)	{
	reset($subst);
	while(list(,$v)=each($subst))	{
		$twoParts = explode($v[0], $content, 2);
		if (count($twoParts)==2)		$content = $twoParts[0].$v[1].$twoParts[1];
#		$content = str_replace($v[0],$v[1],$content);
	}
	return $content;
}

function newSourceScriptOutput($content,$subst)	{
	$content=htmlspecialchars($content);
	
	reset($subst);
	while(list(,$v)=each($subst))	{
		$count = count(explode(htmlspecialchars('"'.$v[1].'"'),$content));
		if ($count>2)	debug(array("ERROR: Double occurency.",$v[1],"Times: ".($count-1)));
		if ($count==1)	debug(array("ERROR: NO entries!",$v[1]));
		$content = str_replace(htmlspecialchars('"'.$v[1].'"'),'"<strong><font color=red>'.htmlspecialchars($v[1]).'</font></strong>"',$content);
	}
	
	$content = str_replace("|",'<font color=blue><strong>###|###</strong></font>',$content);
	
	return $content;
}












?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
<style>
PRE{font-size: 11px;}
TD{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
INPUT{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
TEXTAREA{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
SELECT{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
BODY{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}

</style>

	<title>TYPO3 Conversion of "Language Splitted" labels in TCA arrays to locallang based ones</title>
</head>
<body>
<form action="conv_langsplit_to_locallang.php" method="POST">
<h1>Conversion of Language Splitted labels to locallang.php type</h1>
<?php  if (!t3lib_div::GPvar("update"))	{ ?>
In the beginning all labels for fields and tablenames (and other things as well) was entered by splitting different languages by a vertical line, |<br>
For instance the label "Day" might be entered "Day|Dag|Tag|...." and so forth, here for 1) english, 2) danish, 3) german etc.<br>
<br>
However this became hard to manage when the number of languages became high.<br>
So instead you can put in a reference to a label from a socalled "locallang.php" file where the labels are found separated into an array where each language has it's own key. This concept is much easier to handle.<br>
This tool helps you extract old "language-splitted" labels into a locallang.php file. It will 1) extract the labels, 2) compile a locallang*.php file for you, 3) substitute in the old file and 4) write everything back to disk. You just have to manually configure which script to use as source and destination.
<br>
<br>
<strong>Before using this REMEMBER to change the variables inside this script to point to the right files!!!</strong>
<br>
<HR>

<?php
}


if (@is_file(PATH_typo3.$EXTDIR.$SOURCE))	{
	list($TCA,$LANG_GENERAL_LABELS) = includeFile(PATH_typo3.$EXTDIR.$SOURCE,PATH_typo3.$EXTDIR.$SOURCE_PRE);
	
	$LOCAL_LANG=array();
	$subst=array();
	

	reset($TCA);
	while(list($table,$tC)=each($TCA))	{
		addLsplitStr($LOCAL_LANG,$tC["ctrl"]["title"],$table,0);
		if ($tC["ctrl"]["title"])	{
			$name="LLL:".($EXTDIR?"EXT:":"").$DEST.":".$table;
			$subst[]=array($tC["ctrl"]["title"],$name);
		}
				
		if (is_array($tC["columns"]) && !t3lib_div::inList($DROP_TABLES,$table))	{

			reset($tC["columns"]);
			while(list($fieldname,$config)=each($tC["columns"]))	{
				if (strlen($config["label"]) >= $strLen)	{
					$name=($dont_prefix_table_name?"":$table.".").$fieldname;
					addLsplitStr($LOCAL_LANG,stripslashes($config["label"]),$name);
	
					$name="LLL:".($EXTDIR?"EXT:":"").$DEST.":".$name;
					$subst[]=array($config["label"],$name);
				}
				if (is_array($config["config"]["items"]))	{
					reset($config["config"]["items"]);
					while(list($k,$Iconfig)=each($config["config"]["items"]))	{
						if (strlen($Iconfig[0]) >= $strLen)	{
							$name=($dont_prefix_table_name?"":$table.".").$fieldname.".I.".$k;
							addLsplitStr($LOCAL_LANG,$Iconfig[0],$name);
			
							$name="LLL:".($EXTDIR?"EXT:":"").$DEST.":".$name;
							$subst[]=array($Iconfig[0],$name);
						}
					}
				}
			}
		}
		
		if (is_array($tC["types"]))	{
			reset($tC["types"]);
			while(list($typeVal,$typeConfig)=each($tC["types"]))	{
				$showItem = explode(",",$typeConfig["showitem"]);
				reset($showItem);
				while(list(,$part)=each($showItem))	{
					$sParts=t3lib_div::trimExplode(";",$part);
//$sParts[0]!="--palette--" && 					
					if (strlen($sParts[1]) >= $strLen)	{
						$name=($dont_prefix_table_name?"":$table.".").$sParts[0].".ALT.".$typeVal;
						addLsplitStr($LOCAL_LANG,$sParts[1],$name);
		
						$name="LLL:".($EXTDIR?"EXT:":"").$DEST.":".$name;
						$subst[]=array($sParts[1],$name);
					}
				}
			}
		}
	}	

/*
	if (is_array($LANG_GENERAL_LABELS))	{
		reset($LANG_GENERAL_LABELS);
		while(list($key,$value)=each($LANG_GENERAL_LABELS))	{
			if (strlen($value) >= $strLen)	{
				$name="LGL.".$key;
				addLsplitStr($LOCAL_LANG,$value,$name);

				$name="LLL:".($EXTDIR?"EXT:":"").$DEST.":".$name;
				$subst[]=array($value,$name);
			}
		}
	}
*/





	$locallangScript = makeLocalLangScript($LOCAL_LANG);
	$newSourceScript = makeNewSourceScript(t3lib_div::getUrl(PATH_typo3.$EXTDIR.$SOURCE),$subst);

	if (t3lib_div::GPvar("update"))		{
		echo '<h2><font color=red>WRITING.</font></h2>';

			// source file
		if (@is_file(PATH_typo3.$EXTDIR.$SOURCE))	{
			t3lib_div::writeFile(PATH_typo3.$EXTDIR.$SOURCE, $newSourceScript);
		} else debug("File: '".PATH_typo3.$EXTDIR.$SOURCE."' did not exist!");

			// locallang:
		if (!@is_file(PATH_typo3.$EXTDIR.$DEST))	{
			t3lib_div::writeFile(PATH_typo3.$EXTDIR.$DEST, $locallangScript);
		} else debug("File: '".PATH_typo3.$EXTDIR.$DEST."' did not exist!");
	}

	echo '<br>
	<input type="submit" name="cancel" value="Re-draw">
	';

	echo '<hr><pre>'.htmlspecialchars($locallangScript).'</pre><HR>';
	echo '<hr><pre>'.newSourceScriptOutput($newSourceScript,$subst).'</pre><HR>';


#	debug($LOCAL_LANG);
#	debug($subst);












	echo '<br>
	<input type="submit" name="update" value="WRITE !!!">
	<input type="submit" name="cancel" value="Re-draw">
	';
} else {echo '<font color=red><strong>No file:</strong> '.PATH_typo3.$EXTDIR.$SOURCE.'</font>';}

?>

</form>
</body>
</html>