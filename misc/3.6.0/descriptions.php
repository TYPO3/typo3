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



 
 
$BACK_PATH="../";
define("TYPO3_MOD_PATH", "dev/");
require ($BACK_PATH."init.php");


t3lib_extMgm::isLoaded("context_help",1);



// ***************************
// Registering Incoming data
// ***************************
t3lib_div::setGPvars("missing,ONLY,selectKey,prefillTableName");

$ONLY = $ONLY ? $ONLY : "default";




function getCharSet()	{
	if ($GLOBALS["ONLY"])	{
		include($BACK_PATH."sysext/lang/".$GLOBALS["ONLY"]."/conf.php");
	}
	return $charSet ? $charSet : "iso-8859-1";
}

//debug($HTTP_POST_VARS);


$data = t3lib_div::GPvar("data",1);
$update = t3lib_div::GPvar("update");
if ($update)	{
	if (is_array($data))	{
		reset($data);
		while(list($type,$vv)=each($data))	{
			$keyStr = $ONLY=="default" ? "" : "_".$ONLY;
			if (substr($type,0,3)=="new")	{
//	debug($data);
				if ($data[$type]["tablename"])	{
					$query = t3lib_BEfunc::DBcompileInsert("sys_tabledescr".$keyStr,$data[$type]);
					$res = mysql(TYPO3_db,$query);
					echo mysql_error();
	//				debug($query);
				}
			} else {
				$parts = explode(":",$type);
				$where = "tablename='".$parts[0]."' AND fieldname='".$parts[1]."'";
				$query = t3lib_BEfunc::DBcompileUpdate("sys_tabledescr".$keyStr,$where,$data[$type]);
				$res = mysql(TYPO3_db,$query);
					echo mysql_error();
	//			debug($query);
			}
		}
	}
}










// ***************************
// Starting document output
// ***************************

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php 


	echo getCharSet();
	
?>">
<style>
TD{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
INPUT{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
TEXTAREA{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
SELECT{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
BODY{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}

</style>


	<title>TYPO3 Description translation</title>
</head>

<body>

<form action="descriptions.php?ONLY=<?php echo $ONLY;?>&missing=<?php echo $missing;?>" method="POST">
<h1>Descriptions</h1>
<?php  if (!t3lib_div::GPvar("update"))	{ ?>
Distributed with Typo3 there is a series of database tables called sys_tabledescr_*. This is descriptions of primarily the default table fields distributed in Typo3. The descriptions are first and foremost in english, but if anyone has the capacity to translate them into the other languages, they are welcome.<br>

This document provides a way to edit the tables. When a language has been updated you should make an SQL-dump of only that language table and send to kasper@typo3.com. The authorized translators are the only ones who may submit changes. If you want to help out, you must certainly be approved by the authorized translator. It's most likely that he/she would not mind some help with this extensive task.

<br>
REMOVE THIS FILE (descriptions.php) FROM YOUR TYPO3 INSTALL IF YOU DON'T WANT OTHERS TO READ THIS INFORMATION!<br>
<br>
<br>
The "LOCAL" language is NOT globally distributed (as the other languages are) but a layer which will substitute any system help text if content is present. This table may therefore be edited by you for each individual Typo3 database you've got.
<br>
<br>


<?php
}


include($BACK_PATH."sysext/lang/lang.php");
$LANG = t3lib_div::makeInstance("language");
$LANG->langSplit=TYPO3_languages;
echo "Languages:<br>";

$theLanguages = explode("|",$LANG->langSplit."|LOCAL");
reset($theLanguages);
while(list(,$val)=each($theLanguages))	{
//	if ($val!="default")	{
		$val_l = strtolower($val);
		echo '<a href="descriptions.php?&missing='.$missing.'&ONLY='.$val_l.'#formF">'.($val_l==$ONLY?"<B>":"").$val.($val_l==$ONLY?"</B>":"").'</a> ';
//	}	
}

echo "<HR>";










$query = "SELECT tablename,fieldname FROM sys_tabledescr ORDER BY tablename,fieldname";
$opt=array();
$res = mysql(TYPO3_db,$query);

if ($ONLY=="default")	{
	$opt[]='<option value="">[NEW]</option>';
} else {
	$opt[]='<option value=""></option>';
}

if (!is_array($selectKey))	$selectKey=array();
while($row=mysql_fetch_assoc($res))	{
	$opt[]='<option value="'.($row["tablename"].":".$row["fieldname"]).'"'.(in_array(($row["tablename"].":".$row["fieldname"]),$selectKey)?" SELECTED":"").'>'.htmlspecialchars($row["tablename"].":".$row["fieldname"]).'</option>';
	
	t3lib_div::loadTCA($row["tablename"]);
	
	if (substr($row["tablename"],0,5)!="_MOD_")	{
		if (!isset($TCA[$row["tablename"]]))	{
//			debug("Table, ".$row["tablename"].", not found");
		} elseif ($row["fieldname"] && !isset($TCA[$row["tablename"]]["columns"][$row["fieldname"]]))	{
			debug("Field, ".$row["tablename"].":".$row["fieldname"].", not found");
		}
	}
}

$selector = '<a name="formF"></a><select name="selectKey[]" size=15 multiple>'.implode("",$opt).'</select> <input type="submit" name="Read" value="Read">
<BR>Prefill table name: <input type="text" name="prefillTableName"><br>
';

echo $selector;




$c=0;
if (count($selectKey)==1 && current($selectKey)=="")	$selectKey=array("","","","","","","","","","","","");
reset($selectKey);
while(list(,$selectKeyItem)=each($selectKey))	{
	$c++;
	$keyStr = $selectKeyItem ? $selectKeyItem : "new".$c;
	$rec=array();
	$defRow=array();
	
	$parts = explode(":",$selectKeyItem);
	$query = "SELECT * FROM sys_tabledescr WHERE tablename='".trim($parts[0])."' AND fieldname='".trim($parts[1])."'";
	$res = mysql(TYPO3_db,$query);
	$defRow = mysql_fetch_assoc($res);

echo '<h3>'.$selectKeyItem.'</h3>';
echo '<a href="#" onClick="vHWin=window.open(\''.$BACK_PATH.'view_help.php?tfID='.($parts[0].".".$parts[1]).'\',\'viewFieldHelp\',\'height=300,width=250,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;"><img src="'.$BACK_PATH.'gfx/helpbubble.gif" width="14" height="14" hspace=2 border=0 align=absmiddle></a>';
			
	$fieldKey = "";
	if ($ONLY=="default")	{
		$rec = $defRow;
		$defRow=array();
	} else {
	
		$query = "SELECT * FROM sys_tabledescr_".$ONLY." WHERE tablename='".trim($parts[0])."' AND fieldname='".trim($parts[1])."'";
		$res = mysql(TYPO3_db,$query);
		$fieldKey = $ONLY=="local" ? "" : "_".$ONLY;
		if ($Lrec = mysql_fetch_assoc($res))	{	
			$rec=array();
			$rec["description"] = $Lrec["description".$fieldKey];
			$rec["details"] = $Lrec["details".$fieldKey];
			$rec["syntax"] = $Lrec["syntax".$fieldKey];
			$rec["image"] = $Lrec["image".$fieldKey];
			$rec["image_descr"] = $Lrec["image_descr".$fieldKey];
			$rec["seeAlso"] = $Lrec["seeAlso".$fieldKey];

			$rec["mode"] = $Lrec["mode".$fieldKey];
		} else {
			$keyStr="new".$c;
		}
	}


		echo '
<input type="submit" name="update" value="Submit">
<input type="submit" name="cancel" value="Cancel">
<br>
<br>
		<table border=1>';
	
		if (substr($keyStr,0,3)=="new")	{
			if ($ONLY=="default")	{
				echo '<tr><td valign=top>Tablename:</td><td valign=top>'.htmlspecialchars($defRow["tablename"]).'</td><td valign=top><input type="text" size=30 name="data['.$keyStr.'][tablename]" value="'.htmlspecialchars($prefillTableName).'"></td></tr>';
				echo '<tr><td valign=top>Fieldname:</td><td valign=top>'.htmlspecialchars($defRow["fieldname"]).'</td><td valign=top><input type="text" size=30 name="data['.$keyStr.'][fieldname]" value="'.htmlspecialchars($rec["fieldname"]).'"></td></tr>';
			} else {
				echo '<input type="hidden" name="data['.$keyStr.'][tablename]" value="'.$parts[0].'"><input type="hidden" name="data['.$keyStr.'][fieldname]" value="'.$parts[1].'">';
			}
		}
		if ($ONLY=="local")	{
			echo '<tr><td valign=top>MODE:</td><td valign=top></td><td valign=top>
				<select name="data['.$keyStr.'][mode'.$fieldKey.']">
	<option value="0"'.($rec["mode"]==0?" SELECTED":"").'></option>
	<option value="1"'.($rec["mode"]==1?" SELECTED":"").'>Substitute</option>
	<option value="2"'.($rec["mode"]==2?" SELECTED":"").'>UNSET</option>
</select>
			</td></tr>';
		}
		echo '<tr><td valign=top>Description:</td><td valign=top>'.nl2br(htmlspecialchars($defRow["description"])).'</td><td valign=top><textarea cols="60" rows="6" name="data['.$keyStr.'][description'.$fieldKey.']">'.t3lib_div::formatForTextarea($rec["description"]).'</textarea></td></tr>';
		echo '<tr><td valign=top>Details:</td><td valign=top>'.nl2br(htmlspecialchars($defRow["details"])).'</td><td valign=top><textarea cols="60" rows="'.t3lib_div::intInRange(strlen($defRow["details"])/30,10,100).'" name="data['.$keyStr.'][details'.$fieldKey.']">'.t3lib_div::formatForTextarea($rec["details"]).'</textarea></td></tr>';
		echo '<tr><td valign=top>Syntax:</td><td valign=top>'.nl2br(htmlspecialchars($defRow["syntax"])).'</td><td valign=top><textarea cols="60" rows="6" name="data['.$keyStr.'][syntax'.$fieldKey.']">'.t3lib_div::formatForTextarea($rec["syntax"]).'</textarea></td></tr>';
	if ($ONLY=="default" || $ONLY=="local")	echo '<tr><td valign=top>Image:</td><td valign=top>'.htmlspecialchars($defRow["image"]).'</td><td valign=top><input type="text" size=70 name="data['.$keyStr.'][image'.$fieldKey.']" value="'.htmlspecialchars($rec["image"]).'"></td></tr>';
		echo '<tr><td valign=top>Caption:</td><td valign=top>'.nl2br(htmlspecialchars($defRow["image_descr"])).'</td><td valign=top><textarea cols="60" rows="6" name="data['.$keyStr.'][image_descr'.$fieldKey.']">'.t3lib_div::formatForTextarea($rec["image_descr"]).'</textarea></td></tr>';
	if ($ONLY=="default" || $ONLY=="local")	echo '<tr><td valign=top>See Also:</td><td valign=top>'.htmlspecialchars($defRow["seeAlso"]).'</td><td valign=top><textarea cols="40" rows="4" name="data['.$keyStr.'][seeAlso'.$fieldKey.']" Wrap="OFF">'.t3lib_div::formatForTextarea($rec["seeAlso"]).'</textarea></td></tr>';
		echo "</table>";
	
		echo '<input type=hidden name="data['.$keyStr.'][tstamp'.$fieldKey.']" value="'.time().'">';
}
?>

<br>
<input type="submit" name="update" value="Submit">
<input type="submit" name="cancel" value="Cancel">





</form>
</body>
</html>