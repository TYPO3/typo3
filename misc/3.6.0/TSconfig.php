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
 * Dev-script: Update of TSoptions
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

 
die("<strong>This script is for typo3 development and maintenance only. You'll probably find it useless for what you do.</strong><br><br>MUST remove this line in this script before it'll work for you. This is a security precaution. Anyways, you must be logged in as admin as well.");



ini_set("max_execution_time",60);


 
 
define("TYPO3_MOD_PATH", "dev/");
$BACK_PATH="../";
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
require_once (PATH_t3lib."class.t3lib_parsehtml.php");

function processToDB($data,$whichman)	{
	reset($data);
	while(list($k,$v)=each($data))	{
		$uid = intval($v["uid"]);
		unset($v["uid"]);
		$v["appdata"]=addslashes(rawurldecode(stripslashes($v["appdata"])));
		if (!$uid)	{
			$query = t3lib_BEfunc::DBcompileInsert("static_tsconfig_help",$v,0);
			$res = mysql(TYPO3_db,$query);
			echo mysql_error();
		} else {
			$query = t3lib_BEfunc::DBcompileUpdate("static_tsconfig_help","uid=".$uid,$v,0);
			$res = mysql(TYPO3_db,$query);
			echo mysql_error();
		}
	}
}

function procesInput($input, $whichman)	{
	$parser = t3lib_div::makeInstance("t3lib_parsehtml");
	$parts = $parser->splitIntoBlock("table",$input,1);
	
	// getTablesFromMain 
	$query="SELECT uid,md5hash,description,obj_string,title FROM static_tsconfig_help WHERE guide='".addslashes($whichman)."'";
	$res = mysql(TYPO3_db,$query);
	$recs=array();
	while($rec=mysql_fetch_assoc($res))	{
		$recs[$rec["md5hash"]]=$rec;
	}
	
	
	
	
	
	reset($parts);
	while(list($k)=each($parts))	{
		if ($k%2)	{
			
			$input = trim(cleanUpText(substr($parts[$k+1],0,200)));
			$pp=explode("]",$input,2);
			
			$tableIdString = trim(substr($pp[0],1));
			if ($tableIdString && substr($pp[0],0,1)=="[" && count($pp)==2)	{
				$tableCode = getPropertyTable($parts[$k],1);
				$reg=array();
				$cleanedUpPreContent = trim($pp[1])?trim($pp[1]):trim(cleanUpText($parts[$k-1]));
	
				$md5 = md5(ereg_replace("[[:space:]]","",$tableIdString));
	
					$opt=array();
					$opt[]='<option value=0>[ new ]</option>';
					reset($recs);
					while(list($ky,$dat)=each($recs))	{
						$opt[]='<option value='.$dat["uid"].($ky==$md5?" SELECTED":"").'>'.htmlspecialchars(substr($dat["obj_string"],0,30)).'</option>';
					}
					$uidF='<BR><strong>Overwrite:</strong><BR><select name="DATA['.$k.'][uid]">'.implode("",$opt).'</select><BR>';
	
	
				$tC="";
				$tC.= '<h3>'.$tableIdString.'</h3>';
//				$tC.= '<i>'.nl2br(htmlspecialchars($previousHeader[1])).'</i><BR>';
//				$tC.= '<B>'.$md5.'</B>';
//	debug($rec[$md5]);
				$tC.= '<BR><strong>Title:<BR></strong><input type="text" name="DATA['.$k.'][title]" value="'.htmlspecialchars($recs[$md5]["title"]).'">';
				$tC.= '<BR><strong>Description:<BR></strong><textarea cols=60 rows=10 name="DATA['.$k.'][description]">'.t3lib_div::formatForTextarea($recs[$md5]["description"]).'</textarea>';
				$tC.= '<textarea style="background:#dddddd;" cols=60 rows=10 name="_temp">'.t3lib_div::formatForTextarea($cleanedUpPreContent).'</textarea>';
				$tC.= printTable($tableCode);
				$tC.= "<BR>";
				$tC.= $uidF;
				$tC.= '<input type="hidden" name="DATA['.$k.'][appdata]" value="'.rawurlencode(serialize($tableCode)).'">';
				$tC.= '<input type="hidden" name="DATA['.$k.'][obj_string]" value="'.htmlspecialchars($tableIdString).'">';
				$tC.= '<input type="hidden" name="DATA['.$k.'][md5hash]" value="'.$md5.'">';
				$tC.= '<input type="hidden" name="DATA['.$k.'][guide]" value="'.$whichman.'">';
				$tC.= "<HR>";
				$tC.= "<BR>";
				
				if (!isset($recs[$md5]))	{
					$tC='<table border=0 cellpadding=3 cellspacing=0 bgcolor="red"><tr><td>'.$tC.'</td></tr></table>';
				}			
				$content.= $tC;
			}
		}
		/* else {
			$beforePart = cleanUpText(substr($parts[$k],-1000));
			$boldHeaders = $parser->getAllParts($parser->splitIntoBlock("b",$beforePart,1),1,0);
			$headline=end($boldHeaders);
			$previousHeader = array($headline,$beforePart);
		}*/
	}
	
	$content.= '<input type="hidden" name="whichman" value="'.$whichman.'">';
	return $content;
}

function getPropertyTable($tableCode,$all=0)	{
	$parser = t3lib_div::makeInstance("t3lib_parsehtml");
	$tableBody=$parser->getAllParts($parser->splitIntoBlock("tr",$tableCode,1),1,0);
	
	
	reset($tableBody);

		// Header:
	$thParts = $parser->getAllParts($parser->splitIntoBlock("th",current($tableBody),1),1,0);
	$colMap=array();
	reset($thParts);
	while(list($k,$thV)=each($thParts))	{
		$thV = ereg_replace("[^[:alnum:]]*","",trim(strtolower(strip_tags($thV))));
		$colMap[$thV]=$k;
	}
//debug($colMap);
//debug($thParts);

	if (count($colMap) && ((isset($colMap["property"]) && isset($colMap["description"]))||$all))	{
		next($tableBody);
		$table["rows"]=array();
		while(list(,$v)=each($tableBody))	{
			$tdParts = $parser->getAllParts($parser->splitIntoBlock("td",$v,1),1,0);
			if (count($tdParts))	{
				if (isset($colMap["property"]) && isset($colMap["description"]))	{
					$table["rows"][] = array (
						"property" => trim(strip_tags($tdParts[$colMap["property"]])),
						"datatype" => trim(cleanUpText($tdParts[$colMap["datatype"]])),
						"description" => trim(cleanUpText($tdParts[$colMap["description"]])),
						"default" => trim(cleanUpText($tdParts[$colMap["default"]])),
						"column_count" => count($tdParts),
						"is_propertyTable" => 1
					);
				} else {
					$table["rows"][] = array (
						"property" => trim(strip_tags($tdParts[0])),
						"datatype" => trim(cleanUpText($tdParts[1])),
						"description" => trim(cleanUpText($tdParts[2])),
						"default" => trim(cleanUpText($tdParts[3])),
						"column_count" => count($tdParts)
					);
				}
			}
		}
		return $table;
//		echo printTable($table);
	} else {
		debug("Skipping table");
	}
}
function cleanUpText($text)	{
	$parser = t3lib_div::makeInstance("t3lib_parsehtml");
	$textBody=$parser->getAllParts($parser->splitIntoBlock("p,h1,h2,h3,h4,h5",$text,1),1,0);
	
	reset($textBody);
	$lines=array();
	while(list(,$v)=each($textBody))	{
		$lines[]=str_replace(chr(10),"",str_replace("<br>",chr(10),$parser->stripTagsExcept(trim($v),"b,u,i,br,p")));
	}
	return implode(chr(10),$lines);
}


function printTable($table)	{
	if (is_array($table["rows"]))	{
		$lines=array();

			$lines[]='<tr>
					<td><img src=clear.gif width=175 height=1></td>
					<td><img src=clear.gif width=100 height=1></td>
					<td><img src=clear.gif width=400 height=1></td>
					<td><img src=clear.gif width=70 height=1></td>
				</tr>';

				
		reset($table["rows"]);
		while(list(,$row)=each($table["rows"]))	{
			$lines[]='<tr bgColor="'.$GLOBALS["TBE_TEMPLATE"]->bgColor4.'">
				<td valign=top bgColor="'.t3lib_div::modifyHtmlColor($GLOBALS["TBE_TEMPLATE"]->bgColor4,-20,-20,-20).'"><strong>'.implode("<BR>",t3lib_div::trimExplode(chr(10),$row["property"],1)).'</strong></td>
				<td valign=top>'.nl2br($row["datatype"]).'</td>
				<td valign=top>'.nl2br($row["description"]).'</td>
				<td valign=top>'.nl2br($row["default"]).'</td>
				</tr>';
		}
		return '<table border=0 cellpadding=0 cellspacing=1 width=500>'.implode("",$lines).'</table>';
	}
}















// ***************************
// Starting document output
// ***************************

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
<style>
TD{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
INPUT{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
TEXTAREA{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
SELECT{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
BODY{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}

</style>
	<title>TYPO3 TSoptions maintenance</title>
</head>

<body>
<?php 
if ($HTTP_POST_VARS && t3lib_div::GPvar("whichman"))	{
	if (isset($HTTP_POST_VARS["DATA"]))	{
		processToDB($HTTP_POST_VARS["DATA"], t3lib_div::GPvar("whichman"));
	}
?>
<h1>Add TSconfig to database:</h1>
<form action="TSconfig.php" method="POST">

<?php
	if (is_file($HTTP_POST_FILES["manfile"]["tmp_name"]))	{
echo		procesInput(t3lib_div::getUrl($HTTP_POST_FILES["manfile"]["tmp_name"]),t3lib_div::GPvar("whichman"));
	}


?>
<input type="submit" name="save" value="Save">
</form>

<strong><a href="TSconfig.php">BACK</a></strong>

<?php 
	} else {
?>
<h1>Upload Manual HTML file</h1>
<form action="TSconfig.php" method="POST" enctype="multipart/form-data">

<select name="whichman">
	<option value="0"></option>
	<option value="tsref">TSref</option>
	<option value="adminguide">AdminGuide</option>
</select><br>
<input type="file" name="manfile" size=100><BR>
<input type="submit" name="Upload" value="Upload">

</form>
<?php } ?>
</body>
</html>