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
$BASE="ext/viewpage/view/";
$SOURCE=$BASE."conf.php";	// source file name, typically tca.php. MUST exist
$DEST=$BASE."locallang_mod.php";
#$PREFIX="web_";
#$LL_REF="";	// locallang_tca.php - name to write labels to. MUST NOT exist.
#$EXTDIR="ext/";	// extension directory. Here its the "global" one. Don't try using the local i think.




$BACK_PATH="../";
define("TYPO3_MOD_PATH", "dev/");
require ($BACK_PATH."init.php");

function includeFile($path)	{
	include($path);
	return array($MLANG);
}
function includeLL($path)	{
	if (@is_file($path))	{
		include($path);
	} else debug("NO FILE: ".$path);
	return is_array($LOCAL_LANG)?$LOCAL_LANG:array();;
}
function makeLocalLangScript($LL)	{
	$out="";
	$outLines=array();
	reset($LL);
	while(list($k,$v)=each($LL))	{
		$lines=array();
		reset($v);
		while(list($kk,$vv)=each($v))	{
			$lines[]=chr(9).chr(9).'"'.$kk.'" => "'.str_replace('"','\"',str_replace('$','\$',$vv)).'",';
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

	<title>TYPO3 Conversion of "MLANG" labels in backend modules to locallang based ones</title>
</head>
<body>
<form action="conv_mlang_to_locallang.php" method="POST">
<h1>Conversion of "MLANG" labels in backend modules to locallang based ones</h1>
<?php  if (!t3lib_div::GPvar("update"))	{ ?>
...
<br>
<br>
<strong>Before using this REMEMBER to change the variables inside this script to point to the right files!!!</strong>
<br>
<HR>

<?php
}


if (@is_file(PATH_typo3.$EXTDIR.$SOURCE))	{
	list($MLANG) = includeFile(PATH_typo3.$EXTDIR.$SOURCE);
	
	$LOCAL_LANG=includeLL(PATH_typo3.$EXTDIR.$DEST);

	reset($MLANG);
	while(list($key,$content)=each($MLANG))	{
		$LOCAL_LANG[$key]["mlang_labels_tablabel"]=$content["labels"]["tablabel"];
		$LOCAL_LANG[$key]["mlang_labels_tabdescr"]=$content["labels"]["tabdescr"];
		$LOCAL_LANG[$key]["mlang_tabs_tab"]=$content["tabs"]["tab"];
	}	

	$locallangScript = makeLocalLangScript($LOCAL_LANG);

	if (t3lib_div::GPvar("update"))		{
		echo '<h2><font color=red>WRITING.</font></h2>';

			// locallang:
		if (!@is_file(PATH_typo3.$EXTDIR.$DEST))	{
			debug("File: '".PATH_typo3.$EXTDIR.$DEST."' did not exist!",1);
		}
		debug("Writing File: '".PATH_typo3.$EXTDIR.$DEST."'",1);
		t3lib_div::writeFile(PATH_typo3.$EXTDIR.$DEST, $locallangScript);
	}

	echo '<br>
	<input type="submit" name="cancel" value="Re-draw">
	';

	echo '<hr><pre>'.htmlspecialchars($locallangScript).'</pre><HR>';
	echo '<pre>'.htmlspecialchars('$MLANG["default"]["ll_ref"]="LLL:EXT:'.substr($DEST,4).'";').'</pre><HR>';

	echo '<br>
	<input type="submit" name="update" value="WRITE !!!">
	<input type="submit" name="cancel" value="Re-draw">
	';
} else {echo '<font color=red><strong>No file:</strong> '.PATH_typo3.$EXTDIR.$SOURCE.'</font>';}

?>

</form>
</body>
</html>