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

die("<strong>This script is for typo3 development, tables.php language maintenance only. You'll probably find it useless for what you do.</strong><br><br>MUST remove this line in TCAupdate script before it'll work for you. This is a security precaution. Anyways, you must be logged in as admin as well.");




define("TYPO3_MOD_PATH", "dev/");
$BACK_PATH="../";
require ($BACK_PATH."init.php");
if (!$BE_USER->isAdmin())	die("Must be admin.");



$exitOnExistingValues=1;		// If set, the script does NOT allow existing labels to be overridden
	
	
	function getValueOfCodeLine($str)	{
		$p = explode("=>",$str,2);
		if (substr($p[1],0,6)=='$LANG_')	{
			return "";
		} else {
			ereg('^"(.*)",$',trim($p[1]),$reg);
			return $reg[1];
		}
	}
	function trim_remove_quote($val)	{
		$val = trim($val);
		$val = ereg_replace('^"','',$val);
		$val = ereg_replace('"$','',$val);
		return $val;
	}
	function insertLabel($line,$newVal,$langIndex,$lineNumber=0,$values="")	{
		$values = $values ? $values : getValueOfCodeLine($line);
		$vParts = explode("|",$values);
		$vParts=array_pad ($vParts, count($GLOBALS["langArray"]), "");
		if (trim($vParts[$langIndex]))	{
			echo ("<font color=blue>There was already content in this line for language index <strong>".$langIndex."</strong> (line# ".$lineNumber."):<BR></font>".$line);
			if ($GLOBALS["exitOnExistingValues"])	{
				echo '<BR><BR><font color=Red><strong>THE VALUE "'.$newVal.'" IS NOT UPDATED because $exitOnExistingValues is set</strong></font><BR><BR>';
				return $line;
			}
		}

		if (strstr($newVal,"|"))	{
			echo ("<font color=blue>There was a '|' character in the value. THis is not allowed!:<BR></font>".$line);
			if ($GLOBALS["exitOnExistingValues"])	{
				echo '<BR><BR><font color=Red><strong>THE VALUE "'.$newVal.'" IS NOT UPDATED because $exitOnExistingValues is set</strong></font><BR><BR>';
				return $line;
			}
		}

		if (strstr($newVal,"'") || strstr($newVal,'"'))	{
			echo ("<font color=blue>There was a \" or ' character in the value. Please check if you need to slash the character! (line# ".$lineNumber."):<BR></font>".$line);
		}

		$vParts[$langIndex]=$newVal;
		$newValues = implode("|",$vParts);
		$newLine = str_replace($values,$newValues,$line);
//debug($line);
		debug(array($line,$newLine));

		return $newLine;
	}

	


// PROCESS INPUT:
if (t3lib_div::GPvar("TCAinput"))	{
	$lines = t3lib_div::trimExplode(chr(10),t3lib_div::GPvar("TCAinput"),0);
	reset($lines);
	$parsedLabels=array();
	$langKey="";
	while(list($n,$lineContent)=each($lines))	{
		if (ereg("\*\*\*([[:alnum:]/_ -]*)\*\*\*",$lineContent,$reg))	{
			$langKey = strtolower(trim($reg[1]));
			debug("LANGKEY found: ".$langKey,1);
		}
		if ($langKey)	{
			if (ereg("___([[:alnum:]/_-]*)___",$lineContent,$reg))	{
				$parts = explode("/",$reg[1]);
				if (count($parts)==4 && $parts[3]=="items")	{
					for ($a=1;$a<20;$a++)	{		// 20 is safety...
						if (trim($lines[$n+$a]))	{
							$labelP = explode(":",$lines[$n+$a],2);
							$parsedLabels[$reg[1]."/".trim($labelP[0])]=trim_remove_quote($labelP[1]);
						} else break;
					}
				} else {
					$labelP = explode(":",$lines[$n+1],2);
					if (trim(strtolower($labelP[0]))=="label")	{
						$parsedLabels[$reg[1]]=trim_remove_quote($labelP[1]);
					}
				}
			}
		}
	}
	
	$langArray = explode("|",TYPO3_languages);
	debug($langArray);
	$langSplitIndex = 0;
	reset($langArray);
	while(list($n,$lK)=each($langArray))	{
		if (!strcmp($lK,$langKey) && $langKey)	{
			$langSplitIndex=$n;
			break;
		}
	}
	debug("LANG SPLIT INDEX: ".$langSplitIndex,1);
	echo "<BR>";
	debug("PARSED LABELS:",1);
	debug($parsedLabels);
	
	
	$tables_def_files=explode(",","tables,tbl_sys,tbl_tt,tbl_tt_content,tbl_users");

	// PROCESS TABLES.PHP
	if ($langSplitIndex)	{
		reset($tables_def_files);
		while(list(,$cur_table_def_file)=each($tables_def_files))	{
			$curTableCounter=0;
			$tables_php = explode(chr(10),t3lib_div::getUrl(PATH_t3lib."stddb/".$cur_table_def_file.".php"));
			reset($tables_php);
			while(list($lineN,$line)=each($tables_php))	{
						// Ended?
				switch($mode)	{
					case "LANG_GENERAL_LABELS":
						if (!strcmp(trim($line),');'))	{
							$mode="";
						} else {
							list(,$LGL_key) = explode('"',$line);
							$key = "LANG_GENERAL_LABELS/".$LGL_key;
							if (isset($parsedLabels[$key]))	{
								debug($LGL_key,1);
								$tables_php[$lineN] = insertLabel($line,$parsedLabels[$key],$langSplitIndex,$lineN);
								unset($parsedLabels[$key]);
								$curTableCounter++;
							}
						}
					break;
					case "TABLE":
						switch($part)	{
							case "ctrl":
								if (substr(trim($line),0,7)=='"title"')	{
									$key = "tables_php/".$table."/ctrl/title";
									if (isset($parsedLabels[$key]))	{
										$tables_php[$lineN] = insertLabel($line,$parsedLabels[$key],$langSplitIndex,$lineN);
										unset($parsedLabels[$key]);
										$curTableCounter++;
									}
								}
							break;
							case "columns":
							
//if (strstr($line,"JPG/Very High"))						debug("HERE IT IS!");

								if (substr($line,0,3)=='		"')	{
									list($theField) = explode('"',substr($line,3));
									$itemsMode=0;
								} elseif ($theField)	{
									if (substr(trim($line),0,7)=='"items"')	{
										$itemsMode=1;
									}
									if ($itemsMode)	{
										if (substr(trim(strtolower($line)),0,5)=='array')	{
											$lParts = explode('"',$line);
											if (!strstr($line,'$LANG_GENERAL_LABELS') && count($lParts)>=3)	{	// 3-5-7
												$valKey = strcmp(ereg_replace("[^0-9]","",$lParts[2]),"")?ereg_replace("[^0-9]","",$lParts[2]):$lParts[3];
												$key = "tables_php/".$table."/".$theField."/items/".$valKey;
												if (isset($parsedLabels[$key]))	{
													$tables_php[$lineN] = insertLabel($line,$parsedLabels[$key],$langSplitIndex,$lineN,$lParts[1]);
													unset($parsedLabels[$key]);
													$curTableCounter++;
												}// else debug(array("Not-found key: ".$key,$lParts));
											}
										}
									} else {
										if (substr(trim($line),0,7)=='"label"')	{
											$key = $table."/".$theField;
											if (isset($parsedLabels[$key]))	{
												debug($theField,1);
												$tables_php[$lineN] = insertLabel($line,$parsedLabels[$key],$langSplitIndex,$lineN);
												unset($parsedLabels[$key]);
												$curTableCounter++;
											}
										}
									}
								}
							break;
						}
						
					break;
				}
			
				if (!strcmp(trim($line),'$LANG_GENERAL_LABELS = array('))	{
					$mode = "LANG_GENERAL_LABELS";
				}
				if (!strcmp(substr(trim($line),0,6),'$TCA["'))	{
					$mode = "TABLE";
					list($table) = explode('"',substr(trim($line),6));
					$part = "";
			
						echo "<HR>";
					debug($table,1);
				}
				
				if ($mode=="TABLE")	{
					if (!strcmp(substr($line,0,2),'	"'))	{
						$part="";
					}
					if (!strcmp(rtrim($line),'	"ctrl" => Array ('))	{
						$part="ctrl";
						debug(strtoupper($part),1);
					}
					if (!strcmp(rtrim($line),'	"columns" => Array ('))	{
						$part="columns";
						$theField="";
						debug(strtoupper($part),1);
					}
				}
			}
			
			if ($curTableCounter)	{
				$fileName = PATH_t3lib."stddb/".$cur_table_def_file."_updated_".date("Ymdhis").".php";
				echo "<HR>".$fileName.": ".$curTableCounter." labels to update<BR>";
				if (t3lib_div::GPvar("submit_write"))	{
					echo "<font color=red>WRITING ".$curTableCounter." labels to '".$fileName."':</font><BR>";
					t3lib_div::writeFile($fileName,implode(chr(10), $tables_php));
				}
			}
		}
	}


	echo "<BR>";
	debug("REMAINING LABELS:",1);
	debug($parsedLabels);
}









?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
<style>
TD{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}
INPUT{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px; width:300px;}
BODY{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}

</style>
	<title>TYPO3 TCA Update</title>
</head>
<body>

<form action="TCAupdate.php" method="POST">
<h1>TCA Update</h1>
This document is able to process the tables.php file and merge it with output for tables.php-fields made with "translations.php".
<br>
<br>
<textarea cols="80" rows="30" name="TCAinput" wrap=OFF><?php echo t3lib_div::formatForTextarea(t3lib_div::GPvar("TCAinput"));?></textarea><BR>
<input type="submit" name="submit" value="Analyse and Merge"><BR><input type="submit" name="submit_write" value="WRITE to <?php echo "*_updated_".date("Ymdhis").".php"; ?>">
</form>
</body>
</html>
<?php










// IF a translator delivers a tables.php which is already correctly translated, insert this in the end of that tables.php file and it will generate the keys which can be parsed and merged by this script!
/*




$keyNumber=13;	// language number among lang-keys (1 being default)
define("TYPO3_languages", "default|dk|de|no|it|fr|es|nl|cz|pl|si|fi|tr");	// 13 languages, WOW!!

function splitLine($code)	{
	$splitParts = explode("|",$code);
	return trim($splitParts[$GLOBALS["keyNumber"]-1]);
}

echo "<PRE>";
echo "*** ".strtoupper(splitLine(TYPO3_languages))." ***

";



reset($TCA);
while(list($table,$conf)=each($TCA))	{
	if (splitLine($conf["ctrl"]["title"]))	{
		echo "___tables_php/".$table."/ctrl/title___\n";
		echo "Label : ".splitLine($conf["ctrl"]["title"])."\n\n\n";
	}

	reset($conf["columns"]);
	while(list($field,$config)=each($conf["columns"]))	{
		if (splitLine($config["label"]))	{
			echo "___".$table."/".$field."___\n";
			echo "Label : ".splitLine($config["label"])."\n\n\n";
		}
		if (is_array($config["config"]["items"]))	{
			reset($config["config"]["items"]);
			echo "___tables_php/".$table."/".$field."/items___\n";
			while(list(,$vv)=each($config["config"]["items"]))	{
				if (splitLine($vv[0]))	{
					echo $vv[1]." : ".splitLine($vv[0])."\n";
				}
			}
			echo "\n\n";
		}
	}
}

reset($LANG_GENERAL_LABELS);
while(list($k,$v)=each($LANG_GENERAL_LABELS))	{
	if (splitLine($v))	{
		echo "___LANG_GENERAL_LABELS/".$k."___\n";
		echo "Label : ".splitLine($v)."\n\n\n";
	}
}

echo "</PRE>";

*/



?>