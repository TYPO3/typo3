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
 * This class has functions which generates a difference output of a content string
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */


class t3lib_diff {
	var $stripTags = 0;
	var $clearBufferIdx=0;
	
	function getDiff($str1,$str2)	{
		if (TYPO3_OS!="WIN")	{
				// Create file 1 and write string
			$file1 = tempnam("","");
			t3lib_div::writeFile($file1,$str1);
				// Create file 2 and write string
			$file2 = tempnam("","");
			t3lib_div::writeFile($file2,$str2);
				// Perform diff.
			$cmd = $GLOBALS["TYPO3_CONF_VARS"]["BE"]["diff_path"]." ".$file1." ".$file2;
			exec($cmd,$res);
	
			unlink($file1);
			unlink($file2);
			
			return $res;
		}
	}
	function explodeStringIntoWords($str)	{
		$strArr = t3lib_div::trimExplode(chr(10),$str);
		$outArray=array();
		reset($strArr);
		while(list(,$lineOfWords)=each($strArr))	{
			$allWords = t3lib_div::trimExplode(" ",$lineOfWords,1);
			$outArray = array_merge($outArray,$allWords);
			$outArray[]="";
			$outArray[]="";
		}
		return $outArray;
	}
	function tagSpace($str,$rev=0)	{
		if ($rev)	{
			return str_replace(" &lt;","&lt;",str_replace("&gt; ","&gt;",$str));
		} else {
			return str_replace("<"," <",str_replace(">","> ",$str));
		}
	}
	function makeDiffDisplay($str1,$str2)	{
		if ($this->stripTags)	{
			$str1 = strip_tags($str1);
			$str2 = strip_tags($str2);
		} else {
			$str1 = $this->tagSpace($str1);
			$str2 = $this->tagSpace($str2);
		}
		$str1Lines = $this->explodeStringIntoWords($str1);
		$str2Lines = $this->explodeStringIntoWords($str2);
//		debug($str1Lines);
//		debug($str2Lines);
		$diffRes = $this->getDiff(implode(chr(10),$str1Lines).chr(10),implode(chr(10),$str2Lines).chr(10));
//debug($diffRes);
		if (is_array($diffRes))	{
			reset($diffRes);
			$c=0;
			$diffResArray=array();
			while(list(,$lValue)=each($diffRes))	{
				if (intval($lValue))	{
					$c=intval($lValue);
					$diffResArray[$c]["changeInfo"]=$lValue;
				}
				if (substr($lValue,0,1)=="<")	{
					$diffResArray[$c]["old"][]=substr($lValue,2);
				}
				if (substr($lValue,0,1)==">")	{
					$diffResArray[$c]["new"][]=substr($lValue,2);
				}
			}
//			debug($str1Lines);
//			debug($str2Lines);
//			debug($diffResArray);
			
			$outString="";
			$clearBuffer="";
			for ($a=-1;$a<count($str1Lines);$a++)	{
				if (is_array($diffResArray[$a+1]))	{
					if (strstr($diffResArray[$a+1]["changeInfo"],"a"))	{	// a=Add, c=change, d=delete: If a, then the content is Added after the entry and we must insert the line content as well.
						$clearBuffer.=htmlspecialchars($str1Lines[$a])." ";
					}

					$outString.=$this->addClearBuffer($clearBuffer);
					$clearBuffer="";
					if (is_array($diffResArray[$a+1]["old"]))	{
						$outString.='<font color=red>'.htmlspecialchars(implode(" ",$diffResArray[$a+1]["old"])).'</font> ';
					}
					if (is_array($diffResArray[$a+1]["new"]))	{
						$outString.='<font color=green>'.htmlspecialchars(implode(" ",$diffResArray[$a+1]["new"])).'</font> ';
					}
					$chInfParts = explode(",",$diffResArray[$a+1]["changeInfo"]);
					if (!strcmp($chInfParts[0],$a+1))	{
						$newLine = intval($chInfParts[1])-1;
						if ($newLine>$a)	$a=$newLine;	// Security that $a is not set lower than current for some reason...
					}
				} else {
					$clearBuffer.=htmlspecialchars($str1Lines[$a])." ";
				}
			}
			$outString.=$this->addClearBuffer($clearBuffer,1);
			
			$outString = str_replace("  ",chr(10),$outString);
			if (!$this->stripTags)	{
				$outString = $this->tagSpace($outString,1);
			}
			return $outString;
		}
	}
	function addClearBuffer($clearBuffer,$last=0)	{
		if (strlen($clearBuffer)>200)	{
			$clearBuffer=($this->clearBufferIdx?t3lib_div::fixed_lgd($clearBuffer,70):"")."[".strlen($clearBuffer)."]".(!$last?t3lib_div::fixed_lgd_pre($clearBuffer,70):"");
		}
		$this->clearBufferIdx++;
		return $clearBuffer;
	}
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_diff.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_diff.php"]);
}

?>