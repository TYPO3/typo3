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

 
$BACK_PATH="";


// Remove comment, if you don't want user authentication here.
define("TYPO3_PROCEED_IF_NO_USER", 1);



define("TYPO3_MOD_PATH", "dev/");
$BACK_PATH="../";
require ($BACK_PATH."init.php");



// ***************************
// Registering Incoming data
// ***************************
t3lib_div::setGPvars("missing,ONLY");

$ONLY = $ONLY ? $ONLY : "default";

// ***************************
// Configuring
// ***************************
$DROP_MODULES = "mod/tools/dbint/locallang.php,mod/web/ts/locallang.php,mod/web/log/locallang.php";
$DROP_TABLES = "sys_template,static_template,sys_filemounts,be_groups,be_users,sys_action,sys_workflows,user_photomarathon,user_snowboard,static_dc_type,sys_dc_type";
$DROP_TABLES_ITEMLIST = Array (
	"fe_users/TSconfig" => "1",
	"fe_groups/TSconfig" => "1",
	"pages/TSconfig" => "1",
	"pages/treeStop" => "1",
	"tables.php/pages/urltype/items" => "1,2,3",
	"tables.php/pages/doktype/items" => "--div--",
	"tables.php/tt_content/text_color/items" => "--div--",
	"tables.php/tt_content/text_face/items" => "1,2,3",
	"tables.php/tt_content/image_compression/items" => "10,11,12,13,14,15,30,31,32,33,34,35,39",
	"tables.php/tt_content/imagecols/items" => "0,2,3,4,5,6,7,8",
	"tables.php/tt_content/cols/items" => "1,2,3,4,5,6,7,8,9",
	"tables.php/tt_content/table_bgColor/items" => "--div--",
);


/*
// Set extra LOCAL_LANG files:
$extra_mods=array();
$extra_mods[]=array("sysext/lang/locallang_browse_links.php");
$extra_mods[]=array("sysext/lang/locallang_rte_select_image.php");
$extra_mods[]=array("sysext/lang/locallang_rte_user.php");
$extra_mods[]=array("sysext/lang/locallang_view_help.php");
$extra_mods[]=array("sysext/lang/locallang_alt_intro.php");
$extra_mods[]=array("sysext/lang/locallang_misc.php");
$extra_mods[]=array("sysext/lang/locallang_show_rechis.php");
$extra_mods[]=array("sysext/lang/locallang_alt_doc.php");
$extra_mods[]=array("sysext/lang/locallang_wizards.php");
$extra_mods[]=array("sysext/lang/locallang_tsfe.php");
$extra_mods[]=array("sysext/lang/locallang_db_new_content_el.php");
*/

//	$extra_mods[]=array("mod/web/modules/locallang_dmail.php","LOCAL_LANG_DMAIL");

// ***************************
// Functions
// ***************************
function checkOnly($tl)	{
	return (!$GLOBALS["ONLY"] || $tl=="default" || $GLOBALS["ONLY"]==$tl);
}
function invertMissing($flag,$dont=0)	{
	if ($GLOBALS["missing"]<0 && !$dont)	{
		return 1;
//		return !$flag;
	} else return $flag;
}
function printExistingLabel($str,$misArray,$dont=0)	{
	if ($GLOBALS["missing"]<0 && !$dont)	{
		return $str?htmlspecialchars($str)."<BR>".missing_field($misArray):"";
	} else {
		return htmlspecialchars($str);
	}
}
if (!isset($missing))	{
	$missing=1;
}

// ***************************
// Reporting
// ***************************
if (is_array($HTTP_POST_VARS) && count($HTTP_POST_VARS))	{
	reset($HTTP_POST_VARS);
	$reportdata =array();
	while(list($k,$v)=each($HTTP_POST_VARS))	{
		if (strcmp(trim($v),""))	{
//			debug($k);
			$parts = explode(":",$k);
			if ($parts[0]!="Submit")	{
				$lkey = trim(substr($k,strlen($parts[0].$parts[1]."::")));
				$v=stripslashes($v);
				if (strcmp($lkey,""))	{
					$reportdata[$parts[0]][$parts[1]][$lkey]=htmlspecialchars($v);
				} else {
					$reportdata[$parts[0]][$parts[1]]["Label"]=htmlspecialchars($v);
				}
			}
		}
	}
	
	if ($missing<0)	{
	?>
	<h1>Existing Values Changed:</h1>
	<pre>
	<?php
	} else {
	?>
	<h1>Missing Values:</h1>
	<pre>
	<?php
	}
	
	reset($reportdata);
	while(list($lang,$content)=each($reportdata))	{
		echo chr(10)." *** ".strtoupper($lang)." *** ".chr(10).chr(10);
//		debug($content);
		reset($content);
		while(list($place,$keys)=each($content))	{
			echo "___".$place."___".chr(10);
			reset($keys);
			$before="";
			while(list($k,$val)=each($keys))	{
				if ($place=="mainLang" || $place=="php3Lang")	{
					$parts = explode("/",$k,2);
					if ($before!=$parts[0])	{
						echo "(".$parts[0].")".chr(10);
					}
					echo chr(9).chr(9).chr(9);
					if ($place=="mainLang")	{
						echo "\"".$parts[1]."\" => '".str_replace("'","\'",$val)."',".chr(10);
					} else {
						echo '"'.$parts[1].'" => "'.$val.'",'.chr(10);
					}
					$before=$parts[0];
				} elseif (strstr($place,"conf_php") || strstr($place,"sysext/lang/locallang_"))	{
					if (strstr($k,":"))	{
						$parts = explode(":",$k,2);
						echo '$MLANG["'.$lang.'"]["'.$parts[0].'"]["'.$parts[1].'"] = "'.$val.'";'.chr(10);
					} else {
						echo chr(9).chr(9).'"'.$k.'" => "'.$val.'",'.chr(10);
					}
				} else {
					if (substr($place,-5)!="items")	{
						$val=str_replace("'","\'",$val)	;
					}
					echo $k." : ".$val.chr(10);
				}
			}
			
			echo chr(10);
			echo chr(10);
			echo chr(10);
			echo chr(10);
		}
	}
		echo "</pre>";




?>

<HR>
<h3>Now send it!</h3>
SAVE THIS DOCUMENT (as html or txt) and mail it to kasper@typo3.com.<br>
<B>DO NOT</B> copy/paste the content into a plain mail! Send it as an html/txt attachment!

<br>
<br>

<?php

	echo '<a href="translations.php?ONLY='.$ONLY.'&missing='.$missing.'">Back</a>';
	exit;
}	// End reporting


















// ***************************
// More functions
// ***************************
$fieldName_register=Array();


$MISSING_FIELD_COUNTER=0;
$OK_FIELD_COUNTER=0;
$OK_FIELD_DEFAULT_SIZE=0;
$OK_FIELD_LANG_SIZE=0;

function missing_field($nameArr,$defVal="")	{
	global $MISSING_FIELD_COUNTER;

	if ($GLOBALS["missing"])	{
		$MISSING_FIELD_COUNTER++;
//		debug($nameArr);
		$name = implode($nameArr,":");
		$name = ereg_replace("[^:/\.A-Za-z0-9_-]*","",$name);
//		debug($name);
		if ($GLOBALS["fieldName_register"][$name])	{
			$name.=substr(md5(uniqid("")),0,3);
		}
		
		$GLOBALS["fieldName_register"][$name]=1;
		return '<input type="Text" name="'.$name.'" value="'.htmlspecialchars(trim($defVal)).'">';
	}
}
function printTable($lang, $out_put, $head, $descrip)	{
	$out="";
	if (!$GLOBALS["ONLY"])	{
		$out.="<tr><td colspan=".(count($lang)+1)." align=center><B>".$head."</B><BR>".$descrip."</td></tr>";
	} else {
		$out.="<tr><td colspan=".(2+1)." align=center><B>".$head."</B><BR>".$descrip."</td></tr>";
	}

	$out.='<tr><td bgColor=teal><b><div align="center"><font face="" color="White">KEY</font></div></b></td>';
	while(list(,$val)=each($lang))	{
		if (checkOnly($val))	{
			$out.='<td bgColor=teal><b><div align="center"><font face="" color="White">'.htmlspecialchars($val).'</font></div></b></td>';
		}
	}
	$out.="</tr>";
	
	return "<table border=1>".$out.$out_put."</table><BR>";
}
function printModule($theLanguages, $file, $ll, $varName="")	{
	$out="";
	if (@is_file(PATH_typo3.$file))	{
		include(PATH_typo3.$file);
		$types = explode(",","labels,tabs,buttons");
		while(list(,$t)=each($types))	{
			if (isset($MLANG["default"][$t]))	{
				reset($MLANG["default"][$t]);
				while(list($k,$value)=each($MLANG["default"][$t]))	{
					$out.="<tr>";
					$out.="<td bgColor=silver>[".$t."][".$k."]</td>";
					$out.="<td>".htmlspecialchars($value)."</td>";
			
					reset($theLanguages);
					while(list($lCount,$l)=each($theLanguages))	{
						if ($l!="default" && checkOnly($l))	{
							if (invertMissing(isset($MLANG[$l][$t][$k])))	{
								$cSem = checkSimilar(array(0=>$MLANG["default"][$t][$k],$lCount=>$MLANG[$l][$t][$k]),$lCount);
								$out.="<td".($cSem?' bgColor="'.$cSem.'"':'').">".printExistingLabel($MLANG[$l][$t][$k],Array($l,$file,$t,$k))."</td>";
							} else {
								$out.="<td bgColor=red>MISSING".missing_field(Array($l,$file,$t,$k))."</td>";
							}
						}
					}
					$out.="</tr>";
				}
			}
		}
	}

	if ($ll && !t3lib_div::inList($GLOBALS["DROP_MODULES"],$ll))	{
		include(PATH_typo3.$ll);
		if ($varName)	$LOCAL_LANG = $$varName;
		if (isset($LOCAL_LANG["default"]))	{
			$out.="<tr><td colspan=".(count($theLanguages)+1)." align=center><B>".$ll."</B></td></tr>";

			reset($LOCAL_LANG["default"]);
			while(list($k,$value)=each($LOCAL_LANG["default"]))	{
				$out.="<tr>";
				$out.="<td bgColor=#ccccee>[".$k."]</td>";
				$out.="<td>".htmlspecialchars($value)."</td>";
		
				reset($theLanguages);
				while(list($lCount,$l)=each($theLanguages))	{
					if ($l!="default" && checkOnly($l))	{
						if (invertMissing(isset($LOCAL_LANG[$l][$k])))	{
							$cSem = checkSimilar(array(0=>$LOCAL_LANG["default"][$k],$lCount=>$LOCAL_LANG[$l][$k]),$lCount);
							$out.="<td".($cSem?' bgColor="'.$cSem.'"':'').">".printExistingLabel($LOCAL_LANG[$l][$k],Array($l,$file?$file:$ll,$k))."</td>";
						} else {
//							debug(Array($l,$file,$k));
							$out.="<td bgColor=red>MISSING".missing_field(Array($l,$file?$file:$ll,$k))."</td>";
						}
					}
				}
				$out.="</tr>";
			}
		}
	}
	
	return printTable($theLanguages, $out, $file?$file:$ll,"");
}
function checkSimilar($arr,$k)	{
	global $OK_FIELD_COUNTER, $OK_FIELD_DEFAULT_SIZE, $OK_FIELD_LANG_SIZE;
	$OK_FIELD_COUNTER++;
	$OK_FIELD_DEFAULT_SIZE+=strlen($arr[0]);
	$OK_FIELD_LANG_SIZE+=strlen($arr[$k]);
	
	if (!strcmp(strtoupper(ereg_replace("[[:space:]]*","",$arr[0])),strtoupper(ereg_replace("[[:space:]]*","",$arr[$k])))&&$k)	{
		return "#FF9900";
	}
	if (strcmp(substr(trim($arr[$k]),0,1),strtoupper(substr(trim($arr[$k]),0,1))) && !strcmp(substr(trim($arr[0]),0,1),strtoupper(substr(trim($arr[0]),0,1))))	{	// If not upper case start letter.
		return "#0099FF";
	}
	if (substr(trim($arr[0]),-1)==":" && substr(trim($arr[$k]),-1)!=":")	{	// If not ":" in the end.
		return "#00ccFF";
	}
}
function labels($theLanguages, $data, $fieldkey, $LANG_GENERAL=0, $templateData="")	{
	$out="";
	if (is_array($data))	{
		reset($theLanguages);
		$defLabel=Array();
		$englishValue="";
		while(list($k,$tl)=each($theLanguages))	{
			if (checkOnly($tl))	{
				$inf="";
				reset($data);
				while(list($n,$tVal)=each($data))	{
					$arr = explode("|",$tVal[0]);
					$templateArr = explode("|",$templateData[$n][0]);
					
					if ($tl=="default")	$defLabel[$n] = $arr[$k];
					if ($t1=="default")	$englishValue=$arr[$k];
					
					if ($tl!="default" && ($GLOBALS["DROP_TABLES_ITEMLIST"][$fieldkey] && t3lib_div::inList($GLOBALS["DROP_TABLES_ITEMLIST"][$fieldkey],$tVal[1])))	{
					//	debug($fieldkey);
						$inf.="<nobr><i><font color=Blue>('".$defLabel[$n]."' not translated)</font></i></nobr><BR>";
					} elseif($tl!="default" && in_array($tVal[0],$GLOBALS["LANG_GENERAL_LABELS"]) && !$LANG_GENERAL)	{
						$inf.='<nobr><i><font color=green>("'.$defLabel[$n].'" taken from $GLOBALS["LANG_GENERAL_LABELS"] array)</font></i></nobr><BR>';
					} else {
						if (invertMissing($arr[$k] || !trim($tVal[0]),$tl=="default"))	{
							$cSem = checkSimilar($arr,$k);
							if ($cSem) {
								$inf.="<nobr><font color=".$cSem.">".printExistingLabel($arr[$k],Array($tl, $fieldkey, $tVal[1]),$tl=="default")."</font></nobr><BR>";
							} else {
								$inf.="<nobr>".printExistingLabel($arr[$k],Array($tl, $fieldkey, $tVal[1]),$tl=="default")."</nobr><BR>";
							}
						} else {
							$inf.='<nobr><font color="Red">"'.$defLabel[$n].'" <b>MISSING</b>'.missing_field(Array($tl, $fieldkey, $tVal[1]),$templateArr[$k]).'</font></nobr><BR>';
						}
					}
				}
				$out.="<td>".$inf."</td>";
			}
		}	
	} else {
		$arr = explode("|",$data);
		$templateArr = explode("|",$templateData);
		reset($theLanguages);
		$englishValue="";
		while(list($k,$tl)=each($theLanguages))	{
			if (checkOnly($tl))	{
				if ($t1=="default")	$englishValue=$arr[$k];
				if ($tl!="default" && $GLOBALS["DROP_TABLES_ITEMLIST"][$fieldkey])	{
					$out.="<td><nobr><i><font color=Blue>('".$defLabel[$n]."' not translated)</font></i></nobr></td>";
				} elseif($tl!="default" && in_array($data,$GLOBALS["LANG_GENERAL_LABELS"]) && !$LANG_GENERAL)	{
					$out.='<td><nobr><i><font color=green>taken from $GLOBALS["LANG_GENERAL_LABELS"] array</font></i></nobr></td>';
				} else {
					if (invertMissing($arr[$k] || !trim($data),$tl=="default"))	{
						$cSem = checkSimilar($arr,$k);
						$out.="<td".($cSem?' bgColor="'.$cSem.'"':'').">".printExistingLabel($arr[$k],Array($tl,$fieldkey),$tl=="default")."</td>";
					} else {
						$out.="<td bgColor=red><b>MISSING</b>".missing_field(Array($tl,$fieldkey),$templateArr[$k])."</td>";
					}
				}
			}
		}	
	}
	return $out;
}





















function getCharSet()	{
	if ($GLOBALS["ONLY"])	{
		include(PATH_typo3."sysext/lang/".$GLOBALS["ONLY"]."/conf.php");
	}
	return $charSet ? $charSet : "iso-8859-1";
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
INPUT{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px; width:300px;}
BODY{font-family: Verdana,Arial,Helvetica,Sans-serif; font-size: 10px;}

</style>


	<title>TYPO3 Word List</title>
</head>

<body>

<form action="translations.php?ONLY=<?php echo $ONLY;?>&missing=<?php echo $missing;?>" method="POST" target="_blank">
<H2><font color=red>Notice, version 3.5b4:</font></H2>
The whole concept of translation is moved to be an online, automatic event (on typo3.org) with as little manual work as possible. Therefore the descriptions of what to do if you want to translate something are obsolete right now! However the document here represents a small number of things which will still be translated manually by this tool.
<br>
<br>
<br>

<h1>Translations</h1>
This is the wordlists of this Typo3 installation and the installed modules.<br>
<br>
REMOVE THIS FILE (translations.php) FROM YOUR TYPO3 INSTALL IF YOU DON'T WANT OTHERS TO READ THIS INFORMATION!<br>
<br>

<H3>Translators</h3>
Thanks to the translators:<br>
<?php

$trans=array(
	"de" => Array("German (de)", "Michael Bunzel", "mb@netfielders.de", '<a href="http://www.netfielders.de" target=_blank>netfielders websolutions</a>, formerly <!-- mailto:r.fritz@colorcube.de -->Rene Fritz.'),
	"no" => Array("Norwegian (no)", "Oddvar Bærheim", "oddvarba@online.no", 'formerly <!-- mailto:bav@jdata.no -->Bjørn Atle Vorland.'),
	"it" => Array("Italian (it)", "Marco Caldarazzo", "caldarazzo@gnc.it", 'certains parts by enrico.zeffiro@libero.it'),
	"fr" => Array("French (fr)", "Dominique Feyer", "dfeyer@techhandflesh.ch", 'formerly <!-- mailto:jm.poure@freesurf.fr -->Jean-Michel POURE'),
	"nl" => Array("Dutch (nl)", "Ben van 't ende / netcreators.nl", "ben@netcreators.nl", 'formerly <!-- mailto:vvo@ft.han.nl -->Johan Vorsterman van Oijen'),
	"es" => Array("Spanish (es)", "Samuel Castro", "scastro@disc.com.mx", ''),
	"cz" => Array("Czech (cz)", "Jan Rosa", "rosa@maxdorf.cz", 'New? martin.kokes@sitewell.cz'),
	"pl" => Array("Polish (pl)", "Karol Linkiewicz", "Karol.Linkiewicz@stin.pl", ''),
	"si" => Array("Slovenian (si)", "Andrej Blatnik", "andrej@inmed.si", ''),
	"fi" => Array("Finnish (fi)", "Kari Salovaara", "ecosyd@surfnet.fi", ''),
	"tr" => Array("Turkish (tr)", "Mehmet Yasamali", "yasamali@yahoo.com", ''),
	"se" => Array("Swedish (se)", "Tage Malmen (Contact: Kari Salovaara)", "ecosyd@surfnet.fi", ''),
	"pt" => Array("Portuguese (pt)", "Lopo Lencastre de Almeida", "humaneasy@sitaar.com", ''),
	"ru" => Array("Russian (ru)", "Andreas Schwarzkopf", "a.schwarzkopf@meinsystem.de", 'Preview by Anton Melnikov <psylo@mailru.com>'),
	"ro" => Array("Romanian (ro)", "Lucian Alexa","lucian.alexa@catv.telemach.ro"),
);
$moreMails="enrico.zeffiro@libero.it,martin.kokes@sitewell.cz";

reset($trans);
while(list($k,$v)=each($trans))	{
	echo '- '.$v[0].': <a href="mailto:'.$v[2].'">'.$v[1].'</a> - '.$v[3]."<BR>";
	$moreMails.=", ".$v[2];
}



?>
- Default (english) + Danish (da): <a href="mailto:kasper@typo3.com">Kasper Skårhøj</a><br>
<br>
<!--


 <?php echo $moreMails;?> 
 
 
 
 --><br>

All comments, corrections and additions should be directed to the individual translators.<br>
<br>
<br>

<H3>How this document works</h3>
The point of this document is to display all labels in Typo3 and installed modules as well as the table-definition, tables.php.<br>
By looking at these tables you can see which labels are not yet translated to a given language. This is very useful for the translators of Typo3 and everyone else who want to create modules or define their own table-configuration.<br>
<br>
The MISSING-fields means, that there are no language-specific item for the label. Therefore the default will be displayed - which is the english label. <br>
There should always be a translation for each language wherever there is a MISSING-field <em>UNLESS</em> there is still doubt how to translate the field. In that case it should be left without content so it still appears non-translated.<br>
There's also a few tables, which are not translated. These are the administration tables, like be_users, sys_templates and so on.<br>
<br>
<!-- You can also make this page generate a report of additions: <a href="translations.php?missing=1&ONLY=<?php echo $ONLY;?>">REPORT GENERATION!</a> -->

<br>
<H3>Other things to translate?</h3>
There are some files which are not taken care of with this document:<br>
- <strong>ext/rte/rte_res_[langkey].js</strong>	must be translated for the new language. At least a properly named copy of that file must be in the ext/rte/ folder<br>
- <strong>static template 'language.[langkey]'</strong> must be created as well. This is alternative labels to the english labels in the frontend, eg. search box, forums, guestbook. Unfortunately there is no english version (these are mixed into the typoscript all around), so you must kind of guess the correct translations.<br>
- The manual language tables, <strong>sys_tabledescr*</strong>, may be translated. But there is extremely much text here, more than 100kb. You should not begin that task unless you have conferred with the development team. The values are translated with the tool, descriptions.php<br>
<br>
<br>



<H3>New languages?</h3>
Basically all these wordlists must be translated and maintained. 
<br>
In short there are:<br>
- the main language definitions in the "sysext/lang/"-folder and subfolders. Refer to the pagetop.<br>
- the modules "conf.php" files containing labels, tab-labels, button-labels<BR>
- the modules "locallang.php" files containing translations to the modules in whole<BR>
- the "tables.php" file where every field defining a label is split by "|" according to the language order.<br><br>
<br>
You may experience problems using ' and " characters. If so take a look at other translations and possibly slash these characters to make them work. (Problems occurs due to JavaScript)
<br>
<br>
<h3>Coordination</h3>
Always coordinate a translation with the Typo3 development team. Please contact <a href="mailto:kasper@typo3.com">kasper@typo3.com</a> to request reservation and implementation of a new language. After translating the files, send the result to this email-addess.<br>
<br>
<br>


<H2><font color=red>A few important things:</font></H2>
- Before you begin, notice that your login times out within 1½ hour. You can easily refresh the login at that time in another window and thus restore the session. However you may for numerous reasons choose to do the translations in smaller chunks! However nothing will get lost anyways, because the submission opens in a blank window and thus leaves the form-fields with data still filled in.<br>
- Be precise in your translation. It's MUCH better to make it correct now than sending in corrections which are much more timeconsuming to add! Please!<br>
- If you're in doubt (for instance if you don't know the context of the label), then leave the field EMPTY until you find a translation. Do NOT insert the english value! That is used anyways if you don't provide a value in your language.<br>
- Insert the same periods, commas, underscores and colons as the english default text! And begin your words with uppercase if the english label does.<br>
- Please translate a whole section at a time and leave as few fields as possible in that section untranslated (eg. if you're in doubt).<br>
- If you want to make corrections to the existing values, the best thing to do is: 1) In case the correction is in tables.php file, send a notice with a description of what needs to be changed. This is the hard way for all. 2) If the change is not in tables.php, you should modify the file and send it to kasper@typo3.com. Perhaps mention how much has changed and why, but basically the content related to the language you wish to update, will substitute the old values. And this is MUCH better to do than send a description. And you also get to check the translation first on your own system, because you make the changes in the actual file.<br>
<br>

<br>

<!--
<br>
<br>


<H3>"sysext/lang/"-folder</h3>
This is system labels, messages and so on. The folder "sysext/lang/" contains the default language (english) which is the base and must always be provided. Folders underneath this path holds language specific translations.<br>
<br>

-->
<?php












include(PATH_typo3."sysext/lang/lang.php");
$LANG = t3lib_div::makeInstance("language");
$LANG->langSplit=TYPO3_languages;
echo "Languages: <B>".$LANG->langSplit."</b><br>";
echo "<font face=Verdana size=1>(This means that in a document like 'tables.php' all labels are exploded with '|' and depending on the selected language for the user the correct label is displayed based on the order of the languages shown here. If you wish to add a new language, append the country-code here. Please report this at typo3.com as such changes should be coordinated with the development.)</font><br><br>";

$theLanguages = explode("|",$LANG->langSplit);
reset($theLanguages);
while(list(,$val)=each($theLanguages))	{
	if ($val!="default")	{
		echo 'See: '.$val.' &nbsp; <a href="translations.php?&missing='.($missing?1:0).'&ONLY='.$val.'">Missing fields</a> - <a href="translations.php?&missing=-1&ONLY='.$val.'">Alter existing fields</a><BR>';
	}	
}

reset($theLanguages);
$theMainLang=array();
$thePhpLang=array();
while(list(,$val)=each($theLanguages))	{
	if ($val!="default")	{
		$mainLang=array();
		$php3Lang=array();
	
		include(PATH_typo3."sysext/lang/".ereg_replace("[^a-zA-Z0-9]","",$val)."/conf.php");
		$theMainLang[$val]=$mainLang;
		$thePhpLang[$val]=$php3Lang;
	}
}







/*


// MAINLANG
$out="";
$theTypes = explode(",","cm,buttons,labels,mess,rm,err");
while(list(,$t)=each($theTypes))	{
	while(list($k,$value)=each($LANG->mainLang["$t"]))	{
		$out.="<tr>";
		$out.="<td bgColor=silver>[".$t."][".$k."]</td>";
		$out.="<td>".$value."</td>";

		reset($theLanguages);
		while(list($lCount,$l)=each($theLanguages))	{
			if ($l!="default" && checkOnly($l))	{
				if (invertMissing(isset($theMainLang[$l][$t][$k])))	{
					$cSem = checkSimilar(array(0=>$value,$lCount=>$theMainLang[$l][$t][$k]),$lCount);
					$out.="<td".($cSem?' bgColor="'.$cSem.'"':'').">".printExistingLabel($theMainLang[$l][$t][$k],Array($l, "mainLang", $t."/".$k))."</td>";
				} else {
					$out.="<td bgColor=red>MISSING".missing_field(Array($l, "mainLang", $t."/".$k))."</td>";
				}
			}
		}
		$out.="</tr>";
	}
}
$out= printTable($theLanguages,$out,'The $LANG->mainLang Array','This is language values loaded into the javascript interface');
echo $out;


// PHPLANG
$out="";
//$theTypes = explode(",","labels,db_new.php,file_upload.php,file_rename.php,file_newfolder.php,file_clipupload.php,mess");



reset($LANG->php3Lang);
while(list($t)=each($LANG->php3Lang))	{
	while(list($k,$value)=each($LANG->php3Lang[$t]))	{
		$out.="<tr>";
		$out.="<td bgColor=silver>[".$t."][".$k."]</td>";
		$out.="<td>".$value."</td>";

		reset($theLanguages);
		while(list($lCount,$l)=each($theLanguages))	{
			if ($l!="default" && checkOnly($l))	{
				if (invertMissing(isset($thePhpLang[$l][$t][$k])))	{
					$cSem = checkSimilar(array(0=>$value,$lCount=>$thePhpLang[$l][$t][$k]),$lCount);
					$out.="<td".($cSem?' bgColor="'.$cSem.'"':'').">".printExistingLabel($thePhpLang[$l][$t][$k],Array($l, "php3Lang", $t."/".$k))."</td>";
				} else {
					$out.="<td bgColor=red>MISSING".missing_field(Array($l, "php3Lang", $t."/".$k))."</td>";
				}
			}
		}
		$out.="</tr>";
	}
}
$out= printTable($theLanguages,$out,'The $LANG->php3Lang Array','This is language values used from PHP-code');
echo $out;

*/
?>




<br>
<br>

<H3>"mod/"-folder</h3>
This is typo3 modules, both ThirdParty and default modules.<br>
The files "conf.php" contains labels for buttons, tabs and so on (Keys= "labels","buttons","tabs"). This is generally loaded into the JavaScript interface of Typo3.<br>
The files "locallang.php" contains more lengthy translations which are included when a certain module is used. This is in general all translations for the functions of a certain module.<br>

<br>

<?php

$mods = t3lib_div::get_dirs(PATH_typo3."mod/");
reset($mods);
while(list(,$val)=each($mods))	{
	$file = "mod/".$val."/conf.php";
	$locallang = "mod/".$val."/locallang.php___";	// disabled...
	if (@is_file(PATH_typo3.$file))	{
		$out= printModule($theLanguages,$file, @is_file(PATH_typo3.$locallang)?$locallang:"");
		echo $out;
		
		$submods = t3lib_div::get_dirs(PATH_typo3."mod/".$val."/");
		if (is_array($submods))	{
			while(list(,$sval)=each($submods))	{
				$file = "mod/".$val."/".$sval."/conf.php";
				$locallang = "mod/".$val."/".$sval."/locallang.php___";	// disabled
				if (@is_file(PATH_typo3.$file))	{
					$out= printModule($theLanguages,$file, (@is_file(PATH_typo3.$locallang)?$locallang:""));
					echo $out;
				}
			}
		}
	}
}

/*
reset($extra_mods);
while(list(,$locallang)=each($extra_mods))	{
	if (@is_file(PATH_typo3.$locallang[0]))	{
		$out= printModule($theLanguages,"",$locallang[0],$locallang[1]);
		echo $out;
	}
}
*/


		
?>
<br>
<br>
<H3>typo3conf/tables.php</h3>
<font color=red><strong>This is currently disabled because something new is coming up instead...</strong></font>

<!--
This is the file that holds the definition of the tables, Typo3 works with.<br>
In this file tables, fields and items in lists have "label"-definitions. When a label is displayed, it's first exploded by the "|" character and the appropriate label for the language of the current user is selected depending upon the value of $LANG->langSplit. See above.<br>
Notice that the type-definitions of each table MAY contain overriding definitions!<br>
<br>
-->
<?php
/*
	reset($TCA);
	while(list($table)=each($TCA))	{
		t3lib_div::loadTCA($table);
		$arr=$TCA[$table];
		$out="";
			// table title
		$out.="<tr>";
		$out.="<td bgColor=silver>[ctrl][title]</td>";
		$out.=labels($theLanguages, $arr["ctrl"]["title"], "tables.php/".$table."/ctrl/title",0,$TCA_TEMPLATE[$table]["ctrl"]["title"]);
		$out.="</tr>";
		
		if (!t3lib_div::inList($DROP_TABLES,$table))	{
				// columns
			reset($arr["columns"]);
			while(list($col,$conf)=each($arr["columns"]))	{
				$out.="<tr>";
				$out.="<td bgColor=silver>[column][".$col."][label]</td>";
				$out.=labels($theLanguages, $conf["label"], $table."/".$col,0,$TCA_TEMPLATE[$table]["columns"][$col]["label"]);
				$out.="</tr>";
				
				if (is_array($conf["config"]["items"]))	{
					$out.="<tr>";
					$out.="<td bgColor=#ccccee>[column][".$col."][config][items]</td>";
					$out.=labels($theLanguages, $conf["config"]["items"], "tables.php/".$table."/".$col."/items",0,$TCA_TEMPLATE[$table]["columns"][$col]["config"]["items"]);
					$out.="</tr>";
				}
			}
				// types
			reset($arr["types"]);
			while(list($col,$conf)=each($arr["types"]))	{
				$typeItems = explode(",",$conf["showitem"]);
				while(list($cou,$theConf)=each($typeItems))	{
					$parts = explode(";",$theConf);
					if ($parts[1])	{
						$out.="<tr>";
						$out.="<td bgColor=#eecccc>[types][".$col."][showitem]</td>";
						$out.=labels($theLanguages, $parts[1], "tables.php/".$table."/types/".$col.$cou);
						$out.="</tr>";
					}
				}
			}
		}	
		
		$out= printTable($theLanguages,$out,$table,'');
		echo $out;
			
	}
*/




?>

<br>
<br>

<strong>GLOBALS["LANG_GENERAL_LABELS"]:</strong>


<?php

/*
	reset($LANG_GENERAL_LABELS);
	$out="";
	while(list($col,$val)=each($LANG_GENERAL_LABELS))	{
		$out.="<tr>";
		$out.="<td bgColor=silver>LANG_GENERAL_LABELS[".$col."]</td>";
		$out.=labels($theLanguages, $val, "LANG_GENERAL_LABELS/".$col, 1, $LANG_GENERAL_LABELS_TEMPLATE[$col]);
		$out.="</tr>";
	}
	$out= printTable($theLanguages,$out,"LANG_GENERAL_LABELS",'');
	echo $out;
	*/
?>




<h2>And finally submit the translation...</h2>
<strong>Make sure you're still logged in! </strong>
After and 1½ hour (approx) the login times out. Login with another window and the session should be restored.
Anyways, nothing will be lost, because the form data is submitted into another window, so the content of these fields are not lost anyway.
<br>
<br>


<?php

	if ($missing)	{
		echo '<input type="Submit" name="Submit" value="Make Report">';
	}

	
?>

</form>


<?php 
echo "
	Where was <strong>$MISSING_FIELD_COUNTER (".round($MISSING_FIELD_COUNTER/($OK_FIELD_COUNTER+$MISSING_FIELD_COUNTER)*100)."%)</strong> missing fields and <strong>$OK_FIELD_COUNTER</strong> OK fields (Total: ".($OK_FIELD_COUNTER+$MISSING_FIELD_COUNTER).").<BR>
	Of the OK fields the total size in bytes of the default language is <strong>$OK_FIELD_DEFAULT_SIZE</strong> bytes and of the local language <strong>$OK_FIELD_LANG_SIZE</strong> bytes<br>
		";

?>

<br>
<br>
<HR>


The most important is to send the output from this form to kasper@typo3.com to insert it in the distribution. But if you like you can test it on your version. This is an example of how it works:<br>
<br>
(If you want to update the tables.php file, there is a tool called TCAupdate.php which will insert it, but this is NOT commented very well and not for use by anyone but kasper@typo3.com and thus totally unsupported.)<br>
<br>


<h3>HOWTO Example</h3>
You should create a file like "conf.php" found in "sysext/lang/dk/". When you open the file you'll see that it contains values that override the content of the file "lang.php" in the folder "sysext/lang/". Refer to "lang.php" and substitute the values in "sysext/lang/dk/conf.php". <br>
<br>
(Actually you might do this directly in the danish translation and see the result immediately. Typo3 generates new gif-files for the interface automatically. You just need to clear out the contents of "typo3/temp/"-folder!!!) <br>
<br>
The "conf.php" is only labels for the core part of Typo3. In order to translate for each module you should look at the "conf.php" file of each module. <br>
<br>
Therefore you should also go through all folders in "typo3/mod/" + subfolders. In each you'll find a "conf.php" and "locallang.php" file. This is an example of such a file: 

<pre>
&lt;? 
define("TYPO3_MOD_PATH", "mod/file/list/"); 
$BACK_PATH=""; 
  
$MLANG["default"]["labels"]["tablabel"] = "Listing of files in the directory"; 
$MLANG["default"]["tabs"]["tab"] = "List"; 
$MLANG["default"]["tabs_images"]["tab"] = "list.gif"; 
  
$MLANG["dk"]["labels"]["tablabel"] = "Listning af filer i kataloget"; 
$MLANG["dk"]["tabs"]["tab"] = "Liste"; 
  
$MCONF["script"]="../../../file_list.php"; 
$MCONF["name"]="file_list"; 
$MCONF["access"]="group"; 
  
?&gt;
</pre><br>
<br>

As you see, there is a "default"-configuration. This is always in english. But there is also the danish translation. This works in this way: If there is a danish substitute to the default value, the danish value is used instead of the default (if danish language is chosen that is!) <br>
<br>
This means if you would like to translate a module, you should deliver translated values for your language - like the danish lines! <br>
<br>
The default language of Typo3 is english. <br>
<br>

</body>
</html>