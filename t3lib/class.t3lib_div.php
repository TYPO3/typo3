<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Contains the reknown class "t3lib_div" with general purpose functions
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 * XHTML compliant
 * Usage counts are based on search 22/2 2003 through whole source including tslib/
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  203: class t3lib_div
 *
 *              SECTION: GET/POST Variables
 *  232:     function _GP($var)
 *  246:     function _GET($var='')
 *  260:     function _POST($var='')
 *  273:     function _GETset($inputGet,$key='')
 *  297:     function GPvar($var,$strip=0)
 *  314:     function setGPvars($list,$strip=0)
 *  331:     function GParrayMerged($var)
 *
 *              SECTION: IMAGE FUNCTIONS
 *  376:     function gif_compress($theFile, $type)
 *  404:     function png_to_gif_by_imagemagick($theFile)
 *  428:     function read_png_gif($theFile,$output_png=0)
 *
 *              SECTION: STRING FUNCTIONS
 *  477:     function fixed_lgd($string,$chars,$preStr='...')
 *  499:     function fixed_lgd_pre($string,$chars)
 *  513:     function breakTextForEmail($str,$implChar="\n",$charWidth=76)
 *  533:     function breakLinesForEmail($str,$implChar="\n",$charWidth=76)
 *  569:     function cmpIP($baseIP, $list)
 *  616:     function inList($in_list,$item)
 *  629:     function rmFromList($element,$list)
 *  648:     function intInRange($theInt,$min,$max=2000000000,$zeroValue=0)
 *  665:     function intval_positive($theInt)
 *  679:     function int_from_ver($verNumberStr)
 *  692:     function md5int($str)
 *  704:     function uniqueList($in_list)
 *  717:     function split_fileref($fileref)
 *  754:     function dirname($path)
 *  771:     function modifyHTMLColor($color,$R,$G,$B)
 *  792:     function modifyHTMLColorAll($color,$all)
 *  804:     function rm_endcomma($string)
 *  817:     function danish_strtoupper($string)
 *  832:     function convUmlauts($str)
 *  847:     function shortMD5($input, $len=10)
 *  859:     function testInt($var)
 *  872:     function isFirstPartOfStr($str,$partStr)
 *  889:     function formatSize($sizeInBytes,$labels='')
 *  925:     function convertMicrotime($microtime)
 *  939:     function splitCalc($string,$operators)
 *  961:     function calcPriority($string)
 * 1001:     function calcParenthesis($string)
 * 1028:     function htmlspecialchars_decode($value)
 * 1042:     function deHSCentities($str)
 * 1055:     function slashJS($string,$extended=0,$char="'")
 * 1069:     function rawUrlEncodeJS($str)
 * 1080:     function rawUrlEncodeFP($str)
 * 1092:     function validEmail($email)
 * 1108:     function formatForTextarea($content)
 *
 *              SECTION: ARRAY FUNCTIONS
 * 1140:     function inArray($in_array,$item)
 * 1158:     function intExplode($delim, $string)
 * 1178:     function revExplode($delim, $string, $count=0)
 * 1199:     function trimExplode($delim, $string, $onlyNonEmptyValues=0)
 * 1224:     function uniqueArray($valueArray)
 * 1247:     function removeArrayEntryByValue($array,$cmpValue)
 * 1276:     function implodeArrayForUrl($name,$theArray,$str='',$skipBlank=0,$rawurlencodeParamName=0)
 * 1303:     function compileSelectedGetVarsFromArray($varList,$getArray,$GPvarAlt=1)
 * 1327:     function addSlashesOnArray(&$theArray)
 * 1352:     function stripSlashesOnArray(&$theArray)
 * 1375:     function slashArray($arr,$cmd)
 * 1392:     function array_merge_recursive_overrule ($arr0,$arr1,$notAddKeys=0)
 * 1422:     function array_merge($arr1,$arr2)
 * 1436:     function csvValues($row,$delim=',',$quote='"')
 *
 *              SECTION: HTML/XML PROCESSING
 * 1479:     function get_tag_attributes($tag)
 * 1517:     function split_tag_attributes($tag)
 * 1552:     function implodeParams($arr,$xhtmlSafe=FALSE,$dontOmitBlankAttribs=FALSE)
 * 1580:     function wrapJS($string, $linebreak=TRUE)
 * 1609:     function xml2tree($string,$depth=999)
 * 1694:     function array2xml($array,$NSprefix='',$level=0,$docTag='phparray',$spaceInd=0, $options=array(),$parentTagName='')
 * 1776:     function xml2array($string,$NSprefix='')
 * 1863:     function xmlRecompileFromStructValArray($vals)
 * 1906:     function xmlGetHeaderAttribs($xmlData)
 *
 *              SECTION: FILES FUNCTIONS
 * 1939:     function getURL($url)
 * 1982:     function writeFile($file,$content)
 * 2002:     function mkdir($theNewFolder)
 * 2019:     function get_dirs($path)
 * 2045:     function getFilesInDir($path,$extensionList='',$prependPath=0,$order='')
 * 2098:     function getAllFilesAndFoldersInPath($fileArr,$path,$extList='',$regDirs=0,$recursivityLevels=99)
 * 2120:     function removePrefixPathFromList($fileArr,$prefixToRemove)
 * 2137:     function fixWindowsFilePath($theFile)
 * 2147:     function resolveBackPath($pathStr)
 * 2175:     function locationHeaderUrl($path)
 *
 *              SECTION: DEBUG helper FUNCTIONS
 * 2215:     function debug_ordvalue($string,$characters=100)
 * 2232:     function view_array($array_in)
 * 2260:     function print_array($array_in)
 * 2276:     function debug($var="",$brOrHeader=0)
 *
 *              SECTION: SYSTEM INFORMATION
 * 2345:     function getThisUrl()
 * 2362:     function linkThisScript($getParams=array())
 * 2386:     function linkThisUrl($url,$getParams=array())
 * 2412:     function getIndpEnv($getEnvName)
 * 2604:     function milliseconds()
 * 2617:     function clientInfo($useragent='')
 *
 *              SECTION: TYPO3 SPECIFIC FUNCTIONS
 * 2706:     function getFileAbsFileName($filename,$onlyRelative=1,$relToTYPO3_mainDir=0)
 * 2742:     function validPathStr($theFile)
 * 2754:     function isAbsPath($path)
 * 2766:     function isAllowedAbsPath($path)
 * 2784:     function verifyFilenameAgainstDenyPattern($filename)
 * 2802:     function upload_copy_move($source,$destination)
 * 2830:     function upload_to_tempfile($uploadedFileName)
 * 2847:     function unlink_tempfile($uploadedTempFileName)
 * 2862:     function tempnam($filePrefix)
 * 2875:     function stdAuthCode($uid_or_record,$fields='')
 * 2909:     function loadTCA($table)
 * 2928:     function resolveSheetDefInDS($dataStructArray,$sheet='sDEF')
 * 2956:     function resolveAllSheetsInDS($dataStructArray)
 * 2986:     function callUserFunction($funcName,&$params,&$ref,$checkPrefix='user_',$silent=0)
 * 3165:     function makeInstanceService($serviceType, $serviceSubType='', $excludeServiceKeys='')
 * 3204:     function makeInstanceClassName($className)
 * 3224:     function plainMailEncoded($email,$subject,$message,$headers='',$enc='',$charset='ISO-8859-1',$dontEncodeSubject=0)
 * 3271:     function quoted_printable($string,$maxlen=76)
 * 3314:     function substUrlsInPlainText($message,$urlmode='76',$index_script_url='')
 * 3349:     function makeRedirectUrl($inUrl,$l=0,$index_script_url='')
 * 3377:     function freetypeDpiComp($font_size)
 * 3396:     function devLog($msg, $extKey, $severity=0, $dataVar=FALSE)
 *
 * TOTAL FUNCTIONS: 109
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */












/**
 * The legendary "t3lib_div" class - Miscellaneous functions for general purpose.
 * Most of the functions does not relate specifically to TYPO3
 * However a section of functions requires certain TYPO3 features available
 * See comments in the source.
 * You are encouraged to use this library in your own scripts!
 *
 * USE:
 * The class is intended to be used without creating an instance of it.
 * So: Don't instantiate - call functions with "t3lib_div::" prefixed the function name.
 * So use t3lib_div::[method-name] to refer to the functions, eg. 't3lib_div::milliseconds()'
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_div {





	/*************************
	 *
	 * GET/POST Variables
	 *
	 * Background:
	 * Input GET/POST variables in PHP may have their quotes escaped with "\" or not depending on configuration.
	 * TYPO3 has always converted quotes to BE escaped if the configuration told that they would not be so.
	 * But the clean solution is that quotes are never escaped and that is what the functions below offers.
	 * Eventually TYPO3 should provide this in the global space as well.
	 * In the transitional phase (or forever..?) we need to encourage EVERY to read and write GET/POST vars through the API functions below.
	 *
	 *************************/

	/**
	 * Returns the 'GLOBAL' value of incoming data from POST or GET, with priority to POST (that is equalent to 'GP' order)
	 * Strips slashes from all output, both strings and arrays.
	 * This function substitutes t3lib_div::GPvar()
	 * To enhancement security in your scripts, please consider using t3lib_div::_GET or t3lib_div::_POST if you already know by which method your data is arriving to the scripts!
	 *
	 * @param	string		GET/POST var to return
	 * @return	mixed		POST var named $var and if not set, the GET var of the same name.
	 * @see GPvar()
	 */
	function _GP($var)	{
		$value = isset($GLOBALS['HTTP_POST_VARS'][$var]) ? $GLOBALS['HTTP_POST_VARS'][$var] : $GLOBALS['HTTP_GET_VARS'][$var];
		if (isset($value))	{
			if (is_array($value))	{ t3lib_div::stripSlashesOnArray($value); } else { $value = stripslashes($value); }
		}
		return $value;
	}

	/**
	 * Returns the global GET array (or value from) normalized to contain un-escaped values.
	 * ALWAYS use this API function to acquire the GET variables!
	 *
	 * @param	string		Optional pointer to value in GET array (basically name of GET var)
	 * @return	mixed		If $var is set it returns the value of $HTTP_GET_VARS[$var]. If $var is blank or zero, returns $HTTP_GET_VARS itself. In any case *slashes are stipped from the output!*
	 * @see _POST(), _GP(), _GETset()
	 */
	function _GET($var='')	{
		$getA = $GLOBALS['HTTP_GET_VARS'];
		if (is_array($getA))	t3lib_div::stripSlashesOnArray($getA);	// Removes slashes since TYPO3 has added them regardless of magic_quotes setting.
		return $var ? $getA[$var] : $getA;
	}

	/**
	 * Returns the global POST array (or value from) normalized to contain un-escaped values.
	 * ALWAYS use this API function to acquire the POST variables!
	 *
	 * @param	string		Optional pointer to value in POST array (basically name of POST var)
	 * @return	mixed		If $var is set it returns the value of $HTTP_POST_VARS[$var]. If $var is blank or zero, returns $HTTP_POST_VARS itself. In any case *slashes are stipped from the output!*
	 * @see _GET(), _GP()
	 */
	function _POST($var='')	{
		$postA = $GLOBALS['HTTP_POST_VARS'];
		if (is_array($postA))	t3lib_div::stripSlashesOnArray($postA);	// Removes slashes since TYPO3 has added them regardless of magic_quotes setting.
		return $var ? $postA[$var] : $postA;
	}

	/**
	 * Writes input value to $HTTP_GET_VARS / $_GET
	 *
	 * @param	array		Array to write to $HTTP_GET_VARS / $_GET. Values should NOT be escaped at input time (but will be escaped before writing according to TYPO3 standards).
	 * @param	string		Alternative key; If set, this will not set the WHOLE GET array, but only the key in it specified by this value!
	 * @return	void
	 */
	function _GETset($inputGet,$key='')	{
			// ADDS slashes since TYPO3 standard currently is that slashes MUST be applied (regardless of magic_quotes setting).
		if (strcmp($key,''))	{
			if (is_array($inputGet))	{ t3lib_div::addSlashesOnArray($inputGet); } else { $inputGet = addslashes($inputGet); }
			$GLOBALS['HTTP_GET_VARS'][$key] = $_GET[$key] = $inputGet;
		} elseif (is_array($inputGet)){
			t3lib_div::addSlashesOnArray($inputGet);
			$GLOBALS['HTTP_GET_VARS'] = $_GET = $inputGet;
		}
	}

	/**
	 * GET/POST variable
	 * Returns the 'GLOBAL' value of incoming data from POST or GET, with priority to POST (that is equalent to 'GP' order)
	 * Strips slashes of string-outputs, but not arrays UNLESS $strip is set. If $strip is set all output will have escaped characters unescaped.
	 *
	 * Usage: 686
	 *
	 * @param	string		GET/POST var to return
	 * @param	boolean		If set, values are stripped of return values that are *arrays!* - string/integer values returned are always strip-slashed()
	 * @return	mixed		POST var named $var and if not set, the GET var of the same name.
	 * @depreciated		Use t3lib_div::_GP instead (ALWAYS delivers a value with un-escaped values!)
	 * @see _GP()
	 */
	function GPvar($var,$strip=0)	{
		$value = isset($GLOBALS['HTTP_POST_VARS'][$var]) ? $GLOBALS['HTTP_POST_VARS'][$var] : $GLOBALS['HTTP_GET_VARS'][$var];
		if (isset($value) && is_string($value))	{ $value = stripslashes($value); }	// Originally check '&& get_magic_quotes_gpc() ' but the values of HTTP_GET_VARS are always slashed regardless of get_magic_quotes_gpc() because HTTP_POST/GET_VARS are run through addSlashesOnArray in the very beginning of index_ts.php eg.
		if ($strip && isset($value) && is_array($value)) { t3lib_div::stripSlashesOnArray($value); }
		return $value;
	}

	/**
	 * Sets global variables from HTTP_POST_VARS or HTTP_GET_VARS
	 *
	 * Usage: 9
	 *
	 * @param	string		List of GET/POST var keys to set globally
	 * @param	boolean		If set, values are passed through stripslashes()
	 * @return	void
	 * @depreciated
	 */
	function setGPvars($list,$strip=0)	{
		$vars = t3lib_div::trimExplode(',',$list,1);
		while(list(,$var)=each($vars))	{
			$GLOBALS[$var] = t3lib_div::GPvar($var,$strip);
		}
	}

	/**
	 * Returns the GET/POST global arrays merged with POST taking precedence.
	 *
	 * Usage: 1
	 *
	 * @param	string		Key (variable name) from GET or POST vars
	 * @return	array		Returns the GET vars merged recursively onto the POST vars.
	 * @ignore
	 * @depreciated
	 */
	function GParrayMerged($var)	{
		$postA = is_array($GLOBALS['HTTP_POST_VARS'][$var]) ? $GLOBALS['HTTP_POST_VARS'][$var] : array();
		$getA = is_array($GLOBALS['HTTP_GET_VARS'][$var]) ? $GLOBALS['HTTP_GET_VARS'][$var] : array();
		$mergedA = t3lib_div::array_merge_recursive_overrule($getA,$postA);
		t3lib_div::stripSlashesOnArray($mergedA);
		return $mergedA;
	}










	/*************************
	 *
	 * IMAGE FUNCTIONS
	 *
	 *************************/


	/**
	 * Compressing a GIF file if not already LZW compressed
	 * This function is a workaround for the fact that ImageMagick and/or GD does not compress GIF-files to their minimun size (that is RLE or no compression used)
	 *
	 * 		The function takes a file-reference, $theFile, and saves it again through GD or ImageMagick in order to compress the file
	 * 		GIF:
	 * 		If $type is not set, the compression is done with ImageMagick (provided that $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'] is pointing to the path of a lzw-enabled version of 'convert') else with GD (should be RLE-enabled!)
	 * 		If $type is set to either 'IM' or 'GD' the compression is done with ImageMagick and GD respectively
	 * 		PNG:
	 * 		No changes.
	 *
	 * 		$theFile is expected to be a valid GIF-file!
	 * 		The function returns a code for the operation.
	 *
	 * Usage: 11
	 *
	 * @param	string		Filepath
	 * @param	string		See description of function
	 * @return	string		Returns "GD" if GD was used, otherwise "IM" if ImageMagick was used. If nothing done at all, it returns empty string.
	 * @internal
	 */
	function gif_compress($theFile, $type)	{
		$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		$returnCode='';
		if ($gfxConf['gif_compress'] && strtolower(substr($theFile,-4,4))=='.gif')	{	// GIF...
			if (($type=='IM' || !$type) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'])	{	// IM
				exec($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'].'convert "'.$theFile.'" "'.$theFile.'"');
				$returnCode='IM';
			} elseif (($type=='GD' || !$type) && $gfxConf['gdlib'] && !$gfxConf['gdlib_png'])	{	// GD
				$tempImage = imageCreateFromGif($theFile);
				imageGif($tempImage, $theFile);
				imageDestroy($tempImage);
				$returnCode='GD';
			}
		}
		return $returnCode;
	}

	/**
	 * Converts a png file to gif
	 *
	 * This converts a png file to gif IF the FLAG $GLOBALS['TYPO3_CONF_VARS']['FE']['png_to_gif'] is set true.
	 *
	 * Usage: 5
	 *
	 * @param	string		$theFile	the filename with path
	 * @return	string		new filename
	 * @internal
	 */
	function png_to_gif_by_imagemagick($theFile)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['png_to_gif']
			&& $GLOBALS['TYPO3_CONF_VARS']['GFX']['im']
			&& $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw']
			&& strtolower(substr($theFile,-4,4))=='.png'
			&& @is_file($theFile))	{	// IM
				$newFile = substr($theFile,0,-4).'.gif';
				exec($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'].'convert "'.$theFile.'" "'.$newFile.'"');
				$theFile = $newFile;
					// unlink old file?? May be bad idea bacause TYPO3 would then recreate the file every time as TYPO3 thinks the file is not generated because it's missing!! So do not unlink $theFile here!!
		}
		return $theFile;
	}

	/**
	 * Returns filename of the png/gif version of the input file (which can be png or gif).
	 * If input file type does not match the wanted output type a conversion is made and temp-filename returned.
	 * Usage: 1
	 *
	 * @param	string		Filepath of image file
	 * @param	boolean		If set, then input file is converted to PNG, otherwise to GIF
	 * @return	string		If the new image file exists, it's filepath is returned
	 * @internal
	 */
	function read_png_gif($theFile,$output_png=0)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] && @is_file($theFile))	{
			$ext = strtolower(substr($theFile,-4,4));
			if (
					((string)$ext=='.png' && $output_png)	||
					((string)$ext=='.gif' && !$output_png)
				)	{
				return $theFile;
			} else {
				$newFile = PATH_site.'typo3temp/readPG_'.md5($theFile.'|'.filemtime($theFile)).($output_png?'.png':'.gif');
				exec($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'].'convert "'.$theFile.'" "'.$newFile.'"');
				if (@is_file($newFile))	return $newFile;
			}
		}
	}















	/*************************
	 *
	 * STRING FUNCTIONS
	 *
	 *************************/

	/**
	 * Truncate string
	 * Returns a new string of max. $chars length.
	 * If the string is longer, it will be truncated and appended with '...'.
	 *
	 * Usage: 119
	 *
	 * @param	string		$string 	string to truncate
	 * @param	integer		$chars 	must be an integer of at least 4
	 * @param	string		String to append to the the output if it is truncated, default is '...'
	 * @return	string		new string
	 * @see fixed_lgd_pre()
	 */
	function fixed_lgd($string,$chars,$preStr='...')	{
		if ($chars >= 4)	{
			if(strlen($string)>$chars)  {
				return trim(substr($string, 0, $chars-3)).$preStr;
			}
		}
		return $string;
	}

	/**
	 * Truncate string
	 * Returns a new string of max. $chars length.
	 * If the string is longer, it will be truncated and prepended with '...'.
	 * This works like fixed_lgd, but is truncated in the start of the string instead of the end
	 *
	 * Usage: 19
	 *
	 * @param	string		$string 	string to truncate
	 * @param	integer		$chars 	must be an integer of at least 4
	 * @return	string		new string
	 * @see fixed_lgd()
	 */
	function fixed_lgd_pre($string,$chars)	{
		return strrev(t3lib_div::fixed_lgd(strrev($string),$chars));
	}

	/**
	 * Breaks up the text for emails
	 *
	 * Usage: 1
	 *
	 * @param	string		The string to break up
	 * @param	string		The string to implode the broken lines with (default/typically \n)
	 * @param	integer		The line length
	 * @return	string
	 */
	function breakTextForEmail($str,$implChar="\n",$charWidth=76)	{
		$lines = explode(chr(10),$str);
		$outArr=array();
		while(list(,$lStr)=each($lines))	{
			$outArr = array_merge($outArr,t3lib_div::breakLinesForEmail($lStr,$implChar,$charWidth));
		}
		return implode(chr(10),$outArr);
	}

	/**
	 * Breaks up a single line of text for emails
	 *
	 * Usage: 3
	 *
	 * @param	string		The string to break up
	 * @param	string		The string to implode the broken lines with (default/typically \n)
	 * @param	integer		The line length
	 * @return	string
	 * @see breakTextForEmail()
	 */
	function breakLinesForEmail($str,$implChar="\n",$charWidth=76)	{
		$lines=array();
		$l=$charWidth;
		$p=0;
		while(strlen($str)>$p)	{
			$substr=substr($str,$p,$l);
			if (strlen($substr)==$l)	{
				$count = count(explode(' ',trim(strrev($substr))));
				if ($count>1)	{	// OK...
					$parts = explode(' ',strrev($substr),2);
					$theLine = strrev($parts[1]);
				} else {
					$afterParts = explode(' ',substr($str,$l+$p),2);
					$theLine = $substr.$afterParts[0];
				}
				if (!strlen($theLine))	{break; }	// Error, because this would keep us in an endless loop.
			} else {
				$theLine=$substr;
			}

			$lines[]=trim($theLine);
			$p+=strlen($theLine);
			if (!trim(substr($str,$p,$l)))	break;	// added...
		}
		return implode($implChar,$lines);
	}

	/**
	 * Match IP number with list of numbers with wildcard
	 *
	 * Usage: 8
	 *
	 * @param	string		$baseIP is the current remote IP address for instance, typ. REMOTE_ADDR
	 * @param	string		$list is a comma-list of IP-addresses to match with. *-wildcard allowed instead of number, plus leaving out parts in the IP number is accepted as wildcard (eg. 192.168.*.* equals 192.168)
	 * @return	boolean		True if an IP-mask from $list matches $baseIP
	 */
	function cmpIP($baseIP, $list)	{
		$IPpartsReq = explode('.',$baseIP);
		if (count($IPpartsReq)==4)	{
			$values = t3lib_div::trimExplode(',',$list,1);

			foreach($values as $test)	{
				list($test,$mask) = explode('/',$test);

				if(intval($mask)) {
						// "192.168.3.0/24"
					$lnet = ip2long($test);
					$lip = ip2long($baseIP);
					$binnet = str_pad( decbin($lnet),32,"0","STR_PAD_LEFT" );
					$firstpart = substr($binnet,0,$mask);
					$binip = str_pad( decbin($lip),32,"0","STR_PAD_LEFT" );
					$firstip = substr($binip,0,$mask);
					$yes = (strcmp($firstpart,$firstip)==0);
				} else {
						// "192.168.*.*"
					$IPparts = explode('.',$test);
					$yes = 1;
					reset($IPparts);
					while(list($index,$val)=each($IPparts))	{
						$val = trim($val);
						if (strcmp($val,'*') && strcmp($IPpartsReq[$index],$val))	{
							$yes=0;
						}
					}
				}
				if ($yes) return true;
			}
		}
		return false;
	}

	/**
	 * Match fully qualified domain name with list of strings with wildcard
	 *
	 * @param       string         $baseIP is the current remote IP address for instance, typ. REMOTE_ADDR
	 * @param       string          $list is a comma-list of domain names to match with. *-wildcard allowed but cannot be part of a string, so it must match the full host name (eg. myhost.*.com => correct, myhost.*domain.com => wrong)
	 * @return      boolean         True if a domain name mask from $list matches $baseIP
	 */
	function cmpFQDN($baseIP, $list)        {
		if (count(explode('.',$baseIP))==4)     {
			$resolvedHostName = explode('.', gethostbyaddr($baseIP));
			$values = t3lib_div::trimExplode(',',$list,1);

			foreach($values as $test)	{
				$hostNameParts = explode('.',$test);
				$yes = 1;

				foreach($hostNameParts as $index => $val)	{
					$val = trim($val);
					if (strcmp($val,'*') && strcmp($resolvedHostName[$index],$val)) {
						$yes=0;
					}
				}
				if ($yes) return true;
			}
		}
		return false;
	}

	/**
	 * Check for item in list
	 *
	 * Check if an item exists in a comma-separated list of items.
	 *
	 * Usage: 166
	 *
	 * @param	string		$in_list 	comma-separated list of items (string)
	 * @param	string		$item 	item to check for
	 * @return	boolean		true if $item is in $in_list
	 */
	function inList($in_list,$item)	{
		return strstr(','.$in_list.',', ','.$item.',');
	}

	/**
	 * Removes an item from a comma-separated list of items.
	 *
	 * Usage: 1
	 *
	 * @param	string		$element  	element to remove
	 * @param	string		$list 	comma-separated list of items (string)
	 * @return	string		new comma-separated list of items
	 */
	function rmFromList($element,$list)	{
		$items = explode(',',$list);
		while(list($k,$v)=each($items))	{
			if ($v==$element)	{unset($items[$k]);}
		}
		return implode($items,',');
	}

	/**
	 * Forces the integer $theInt into the boundaries of $min and $max. If the $theInt is 'false' then the $zeroValue is applied.
	 *
	 * Usage: 226
	 *
	 * @param	integer		Input value
	 * @param	integer		Lower limit
	 * @param	integer		Higher limit
	 * @param	integer		Default value if input is false.
	 * @return	integer		The input value forced into the boundaries of $min and $max
	 */
	function intInRange($theInt,$min,$max=2000000000,$zeroValue=0)	{
		// Returns $theInt as an integer in the integerspace from $min to $max
		$theInt = intval($theInt);
		if ($zeroValue && !$theInt)	{$theInt=$zeroValue;}	// If the input value is zero after being converted to integer, zeroValue may set another default value for it.
		if ($theInt<$min){$theInt=$min;}
		if ($theInt>$max){$theInt=$max;}
		return $theInt;
	}

	/**
	 * Returns the $integer if greater than zero, otherwise returns zero.
	 *
	 * Usage: 1
	 *
	 * @param	integer		Integer string to process
	 * @return	integer
	 */
	function intval_positive($theInt)	{
		$theInt = intval($theInt);
		if ($theInt<0){$theInt=0;}
		return $theInt;
	}

	/**
	 * Returns an integer from a three part version number, eg '4.12.3' -> 4012003
	 *
	 * Usage: 2
	 *
	 * @param	string		Version number on format x.x.x
	 * @return	integer		Integer version of version number (where each part can count to 999)
	 */
	function int_from_ver($verNumberStr)	{
		$verParts = explode('.',$verNumberStr);
		return intval((int)$verParts[0].str_pad((int)$verParts[1],3,'0',STR_PAD_LEFT).str_pad((int)$verParts[2],3,'0',STR_PAD_LEFT));
	}

	/**
	 * Makes a positive integer hash out of the first 7 chars from the md5 hash of the input
	 *
	 * Usage: 0
	 *
	 * @param	string		String to md5-hash
	 * @return	integer		Returns 28bit integer-hash
	 */
	function md5int($str)	{
		return hexdec(substr(md5($str),0,7));
	}

	/**
	 * Takes a comma-separated list and removes all duplicates
	 *
	 * Usage: 16
	 *
	 * @param	string		$in_list is a comma-separated list of values.
	 * @return	string		Returns the list without any duplicates of values, space around values are trimmed
	 */
	function uniqueList($in_list)	{
		$theTempArray = explode(',',$in_list);
		return implode(t3lib_div::uniqueArray($theTempArray),',');
	}

	/**
	 * Splits a reference to a file in 5 parts
	 *
	 * Usage: 43
	 *
	 * @param	string		Filename/filepath to be analysed
	 * @return	array		Contains keys [path], [file], [filebody], [fileext], [realFileext]
	 */
	function split_fileref($fileref)	{
		if (	ereg('(.*/)(.*)$',$fileref,$reg)	)	{
			$info['path'] = $reg[1];
			$info['file'] = $reg[2];
		} else {
			$info['path'] = '';
			$info['file'] = $fileref;
		}
		$reg='';
		if (	ereg('(.*)\.([^\.]*$)',$info['file'],$reg)	)	{
			$info['filebody'] = $reg[1];
			$info['fileext'] = strtolower($reg[2]);
			$info['realFileext'] = $reg[2];
		} else {
			$info['filebody'] = $info['file'];
			$info['fileext'] = '';
		}
		reset($info);
		return $info;
	}

	/**
	 * Returns the directory part of a path without trailing slash
	 * If there is no dir-part, then an empty string is returned.
	 * Behaviour:
	 *
	 * '/dir1/dir2/script.php' => '/dir1/dir2'
	 * '/dir1/' => '/dir1'
	 * 'dir1/script.php' => 'dir1'
	 * 'd/script.php' => 'd'
	 * '/script.php' => ''
	 * '' => ''
	 * Usage: 5
	 *
	 * @param	string		Directory name / path
	 * @return	string		Processed input value. See function description.
	 */
	function dirname($path)	{
		$p=t3lib_div::revExplode('/',$path,2);
		return count($p)==2?$p[0]:'';
	}

	/**
	 * Modifies a HTML Hex color by adding/subtracting $R,$G and $B integers
	 *
	 * Usage: 37
	 *
	 * @param	string		A hexadecimal color code, #xxxxxx
	 * @param	integer		Offset value 0-255
	 * @param	integer		Offset value 0-255
	 * @param	integer		Offset value 0-255
	 * @return	string		A hexadecimal color code, #xxxxxx, modified according to input vars
	 * @see modifyHTMLColorAll()
	 */
	function modifyHTMLColor($color,$R,$G,$B)	{
		// This takes a hex-color (# included!) and adds $R, $G and $B to the HTML-color (format: #xxxxxx) and returns the new color
		$nR = t3lib_div::intInRange(hexdec(substr($color,1,2))+$R,0,255);
		$nG = t3lib_div::intInRange(hexdec(substr($color,3,2))+$G,0,255);
		$nB = t3lib_div::intInRange(hexdec(substr($color,5,2))+$B,0,255);
		return '#'.
			substr('0'.dechex($nR),-2).
			substr('0'.dechex($nG),-2).
			substr('0'.dechex($nB),-2);
	}

	/**
	 * Modifies a HTML Hex color by adding/subtracting $all integer from all R/G/B channels
	 *
	 * Usage: 4
	 *
	 * @param	string		A hexadecimal color code, #xxxxxx
	 * @param	integer		Offset value 0-255 for all three channels.
	 * @return	string		A hexadecimal color code, #xxxxxx, modified according to input vars
	 * @see modifyHTMLColor()
	 */
	function modifyHTMLColorAll($color,$all)	{
		return t3lib_div::modifyHTMLColor($color,$all,$all,$all);
	}

	/**
	 * Removes comma (if present) in the end of string
	 *
	 * Usage: 4
	 *
	 * @param	string		String from which the comma in the end (if any) will be removed.
	 * @return	string
	 */
	function rm_endcomma($string)	{
		return ereg_replace(',$','',$string);
	}

	/**
	 * strtoupper which converts danish (and other characters) characters as well
	 * (Depreciated, use PHP function with locale settings instead or for HTML output, wrap your content in <span class="uppercase">...</span>)
	 * Usage: 4
	 *
	 * @param	string		String to process
	 * @return	string
	 * @ignore
	 */
	function danish_strtoupper($string)	{
		$value = strtoupper($string);
		return strtr($value, 'áéúíâêûôîæøåäöü', 'ÁÉÚÍÄËÜÖÏÆØÅÄÖÜ');
	}

	/**
	 * Change umlaut characters to plain ASCII with normally two character target
	 * Only known characters will be converted, so don't expect a result for any character.
	 * Works only for western europe single-byte charsets!
	 *
	 * ä => ae, Ö => Oe
	 *
	 * @param	string		String to convert.
	 * @return	string
	 */
	function convUmlauts($str)	{
		$pat  = array (	'/ä/',	'/Ä/',	'/ö/',	'/Ö/',	'/ü/',	'/Ü/',	'/ß/',	'/å/',	'/Å/',	'/ø/',	'/Ø/',	'/æ/',	'/Æ/'	);
		$repl = array (	'ae',	'Ae',	'oe',	'Oe',	'ue',	'Ue',	'ss',	'aa',	'AA',	'oe',	'OE',	'ae',	'AE'	);
		return preg_replace($pat,$repl,$str);
	}

	/**
	 * Returns the first 10 positions of the MD5-hash		(changed from 6 to 10 recently)
	 *
	 * Usage: 43
	 *
	 * @param	string		Input string to be md5-hashed
	 * @param	integer		The string-length of the output
	 * @return	string		Substring of the resulting md5-hash, being $len chars long (from beginning)
	 */
	function shortMD5($input, $len=10)	{
		return substr(md5($input),0,$len);
	}

	/**
	 * Tests if the input is an integer.
	 *
	 * Usage: 74
	 *
	 * @param	mixed		Any input variable to test.
	 * @return	boolean		Returns true if string is an integer
	 */
	function testInt($var)	{
		return !strcmp($var,intval($var));
	}

	/**
	 * Returns true if the first part of $str matches the string $partStr
	 *
	 * Usage: 58
	 *
	 * @param	string		Full string to check
	 * @param	string		Reference string which must be found as the "first part" of the full string
	 * @return	boolean		True if $partStr was found to be equal to the first part of $str
	 */
	function isFirstPartOfStr($str,$partStr)	{
		// Returns true, if the first part of a $str equals $partStr and $partStr is not ''
		$psLen = strlen($partStr);
		if ($psLen)	{
			return substr($str,0,$psLen)==(string)$partStr;
		} else return false;
	}

	/**
	 * Formats the input integer $sizeInBytes as bytes/kilobytes/megabytes (-/K/M)
	 *
	 * Usage: 54
	 *
	 * @param	integer		Number of bytes to format.
	 * @param	string		Labels for bytes, kilo, mega and giga separated by vertical bar (|) and possibly encapsulated in "". Eg: " | K| M| G" (which is the default value)
	 * @return	string		Formatted representation of the byte number, for output.
	 */
	function formatSize($sizeInBytes,$labels='')	{

			// Set labels:
		if (strlen($labels) == 0) {
		    $labels = ' | K| M| G';
		} else {
		    $labels = str_replace('"','',$labels);
		}
		$labelArr = explode('|',$labels);

			// Find size:
		if ($sizeInBytes>900)	{
			if ($sizeInBytes>900000000)	{	// GB
				$val = $sizeInBytes/(1024*1024*1024);
				return number_format($val, (($val<20)?1:0), '.', '').$labelArr[3];
			}
			elseif ($sizeInBytes>900000)	{	// MB
				$val = $sizeInBytes/(1024*1024);
				return number_format($val, (($val<20)?1:0), '.', '').$labelArr[2];
			} else {	// KB
				$val = $sizeInBytes/(1024);
				return number_format($val, (($val<20)?1:0), '.', '').$labelArr[1];
			}
		} else {	// Bytes
			return $sizeInBytes.$labelArr[0];
		}
	}

	/**
	 * Returns microtime input to milliseconds
	 *
	 * Usage: 2
	 *
	 * @param	string		Microtime
	 * @return	integer		Microtime input string converted to an integer (milliseconds)
	 */
	function convertMicrotime($microtime)	{
		$parts = explode(' ',$microtime);
		return round(($parts[0]+$parts[1])*1000);
	}

	/**
	 * This splits a string by the chars in $operators (typical /+-*) and returns an array with them in
	 * Usage: 2
	 *
	 * @param	string		Input string, eg "123 + 456 / 789 - 4"
	 * @param	string		Operators to split by, typically "/+-*"
	 * @return	array		Array with operators and operands separated.
	 * @see tslib_cObj::calc(), tslib_gifBuilder::calcOffset()
	 */
	function splitCalc($string,$operators)	{
		$res = Array();
		$sign='+';
		while($string)	{
			$valueLen=strcspn($string,$operators);
			$value=substr($string,0,$valueLen);
			$res[] = Array($sign,trim($value));
			$sign=substr($string,$valueLen,1);
			$string=substr($string,$valueLen+1);
		}
		reset($res);
		return $res;
	}

	/**
	 * Calculates the input by +,-,*,/,%,^ with priority to + and -
	 * Usage: 1
	 *
	 * @param	string		Input string, eg "123 + 456 / 789 - 4"
	 * @return	integer		Calculated value. Or error string.
	 * @see calcParenthesis()
	 */
	function calcPriority($string)	{
		$string=ereg_replace('[[:space:]]*','',$string);	// removing all whitespace
		$string='+'.$string;	// Ensuring an operator for the first entrance
		$qm='\*\/\+-^%';
		$regex = '(['.$qm.'])(['.$qm.']?[0-9\.]*)';
			// split the expression here:
		preg_match_all('/'.$regex.'/',$string,$reg);

		reset($reg[2]);
		$number=0;
		$Msign='+';
		$err='';
		$buffer=doubleval(current($reg[2]));
		next($reg[2]);	// Advance pointer
		while(list($k,$v)=each($reg[2]))	{
			$v=doubleval($v);
			$sign = $reg[1][$k];
			if ($sign=='+' || $sign=='-')	{
				$number = $Msign=='-' ? $number-=$buffer : $number+=$buffer;
				$Msign = $sign;
				$buffer=$v;
			} else {
				if ($sign=='/')	{if ($v) $buffer/=$v; else $err='dividing by zero';}
				if ($sign=='%')	{if ($v) $buffer%=$v; else $err='dividing by zero';}
				if ($sign=='*')	{$buffer*=$v;}
				if ($sign=='^')	{$buffer=pow($buffer,$v);}
			}
		}
		$number = $Msign=='-' ? $number-=$buffer : $number+=$buffer;
		return $err ? 'ERROR: '.$err : $number;
	}

	/**
	 * Calculates the input with parenthesis levels
	 * Usage: 2
	 *
	 * @param	string		Input string, eg "(123 + 456) / 789 - 4"
	 * @return	integer		Calculated value. Or error string.
	 * @see calcPriority(), tslib_cObj::stdWrap()
	 */
	function calcParenthesis($string)	{
		$securC=100;
		do {
			$valueLenO=strcspn($string,'(');
			$valueLenC=strcspn($string,')');
			if ($valueLenC==strlen($string) || $valueLenC < $valueLenO)	{
				$value = t3lib_div::calcPriority(substr($string,0,$valueLenC));
				$string = $value.substr($string,$valueLenC+1);
				return $string;
			} else {
				$string = substr($string,0,$valueLenO).t3lib_div::calcParenthesis(substr($string,$valueLenO+1));
			}
				// Security:
			$securC--;
			if ($securC<=0)	break;
		} while($valueLenO<strlen($string));
		return $string;
	}

	/**
	 * Inverse version of htmlspecialchars()
	 *
	 * Usage: 2
	 *
	 * @param	string		Value where &gt;, &lt;, &quot; and &amp; should be converted to regular chars.
	 * @return	string		Converted result.
	 */
	function htmlspecialchars_decode($value)	{
		$value = str_replace('&gt;','>',$value);
		$value = str_replace('&lt;','<',$value);
		$value = str_replace('&quot;','"',$value);
		$value = str_replace('&amp;','&',$value);
		return $value;
	}

	/**
	 * Re-converts HTML entities if they have been converted by htmlspecialchars()
	 *
	 * @param	string		String which contains eg. "&amp;amp;" which should stay "&amp;". Or "&amp;#1234;" to "&#1234;". Or "&amp;#x1b;" to "&#x1b;"
	 * @return	string		Converted result.
	 */
	function deHSCentities($str)	{
		return ereg_replace('&amp;([#[:alnum:]]*;)','&\1',$str);
	}

	/**
	 * This function is used to escape any ' -characters when transferring text to JavaScript!
	 * Usage: 6
	 *
	 * @param	string		String to escape
	 * @param	boolean		If set, also backslashes are escaped.
	 * @param	string		The character to escape, default is ' (single-quote)
	 * @return	string		Processed input string
	 */
	function slashJS($string,$extended=0,$char="'")	{
		if ($extended)	{$string = str_replace ("\\", "\\\\", $string);}
		return str_replace ($char, "\\".$char, $string);
	}

	/**
	 * Version of rawurlencode() where all spaces (%20) are re-converted to space-characters.
	 * Usefull when passing text to JavaScript where you simply url-encode it to get around problems with syntax-errors, linebreaks etc.
	 *
	 * Usage: 8
	 *
	 * @param	string		String to raw-url-encode with spaces preserved
	 * @return	string		Rawurlencoded result of input string, but with all %20 (space chars) converted to real spaces.
	 */
	function rawUrlEncodeJS($str)	{
		return str_replace('%20',' ',rawurlencode($str));
	}

	/**
	 * rawurlencode which preserves "/" chars
	 * Usefull when filepaths should keep the "/" chars, but have all other special chars encoded.
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function rawUrlEncodeFP($str)	{
		return str_replace('%2F','/',rawurlencode($str));
	}

	/**
	 * Checking syntax of input email address
	 *
	 * Usage: 4
	 *
	 * @param	string		Input string to evaluate
	 * @return	boolean		Returns true if the $email address (input string) is valid; Has a "@", domain name with at least one period and only allowed a-z characters.
	 */
	function validEmail($email)	{
		$email = trim ($email);
		if (strstr($email,' '))	 return FALSE;
		return ereg('^[A-Za-z0-9\._-]+[@][A-Za-z0-9\._-]+[\.].[A-Za-z0-9]+$',$email) ? TRUE : FALSE;
	}

	/**
	 * Formats a string for output between <textarea>-tags
	 * All content outputted in a textarea form should be passed through this function
	 * Not only is the content htmlspecialchar'ed on output but there is also a single newline added in the top. The newline is necessary because browsers will ignore the first newline after <textarea> if that is the first character. Therefore better set it!
	 *
	 * Usage: 30
	 *
	 * @param	string		Input string to be formatted.
	 * @return	string		Formatted for <textarea>-tags
	 */
	function formatForTextarea($content)	{
		return chr(10).htmlspecialchars($content);
	}












	/*************************
	 *
	 * ARRAY FUNCTIONS
	 *
	 *************************/

	/**
	 * Check if an item exists in an array
	 * Please note that the order of parameters is reverse compared to the php4-function in_array()!!!
	 *
	 * Usage: 3
	 *
	 * @param	array		$in_array		one-dimensional array of items
	 * @param	string		$item 	item to check for
	 * @return	boolean		true if $item is in the one-dimensional array $in_array
	 * @internal
	 */
	function inArray($in_array,$item)	{
		if (is_array($in_array))	{
			while (list(,$val)=each($in_array))	{
				if (!is_array($val) && !strcmp($val,$item)) return true;
			}
		}
	}

	/**
	 * Explodes a $string delimited by $delim and passes each item in the array through intval().
	 * Corresponds to explode(), but with conversion to integers for all values.
	 *
	 * Usage: 86
	 *
	 * @param	string		Delimiter string to explode with
	 * @param	string		The string to explode
	 * @return	array		Exploded values, all converted to integers
	 */
	function intExplode($delim, $string)	{
		$temp = explode($delim,$string);
		while(list($key,$val)=each($temp))	{
			$temp[$key]=intval($val);
		}
		reset($temp);
		return $temp;
	}

	/**
	 * Reverse explode which explodes the string counting from behind.
	 * Thus t3lib_div::revExplode(':','my:words:here',2) will return array('my:words','here')
	 *
	 * Usage: 6
	 *
	 * @param	string		Delimiter string to explode with
	 * @param	string		The string to explode
	 * @param	integer		Number of array entries
	 * @return	array		Exploded values
	 */
	function revExplode($delim, $string, $count=0)	{
		$temp = explode($delim,strrev($string),$count);
		while(list($key,$val)=each($temp))	{
			$temp[$key]=strrev($val);
		}
		$temp=array_reverse($temp);
		reset($temp);
		return $temp;
	}

	/**
	 * Explodes a string and trims all values for whitespace in the ends.
	 * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
	 *
	 * Usage: 239
	 *
	 * @param	string		Delimiter string to explode with
	 * @param	string		The string to explode
	 * @param	boolean		If set, all empty values (='') will NOT be set in output
	 * @return	array		Exploded values
	 */
	function trimExplode($delim, $string, $onlyNonEmptyValues=0)	{
		$temp = explode($delim,$string);
		$newtemp=array();
		while(list($key,$val)=each($temp))	{
			if (!$onlyNonEmptyValues || strcmp('',trim($val)))	{
				$newtemp[]=trim($val);
			}
		}
		reset($newtemp);
		return $newtemp;
	}

	/**
	 * Takes a one-dimensional array and returns an array where the values are unique
	 * The keys in the array are substituted with some md5-hashes
	 * If the value is trim(empty), the value is ignored.
	 * Values are trimmed
	 * (Depreciated, use PHP function array_unique instead)
	 * Usage: 2
	 *
	 * @param	array		Array of values to make unique
	 * @return	array
	 * @ignore
	 * @depreciated
	 */
	function uniqueArray($valueArray)	{
		$array_out=array();
		if (is_array($valueArray))	{
			while (list($key,$val)=each($valueArray)) {
				$val=trim($val);
				if ((string)$val!='')	{
					$array_out[md5($val)]=$val;
				}
			}
		}
		reset($array_out);
		return $array_out;
	}

	/**
	 * Removes the value $cmpValue from the $array if found there. Returns the modified array
	 *
	 * Usage: 2
	 *
	 * @param	array		Array containing the values
	 * @param	string		Value to search for and if found remove array entry where found.
	 * @return	array		Output array with entries removed if search string is found
	 */
	function removeArrayEntryByValue($array,$cmpValue)	{
		if (is_array($array))	{
			reset($array);
			while(list($k,$v)=each($array))	{
				if (is_array($v))	{
					$array[$k] = t3lib_div::removeArrayEntryByValue($v,$cmpValue);
				} else {
					if (!strcmp($v,$cmpValue))	{
						unset($array[$k]);
					}
				}
			}
		}
		reset($array);
		return $array;
	}

	/**
	 * Implodes a multidim-array into GET-parameters (eg. &param[key][key2]=value2&param[key][key3]=value3)
	 *
	 * Usage: 24
	 *
	 * @param	string		Name prefix for entries. Set to blank if you wish none.
	 * @param	array		The (multidim) array to implode
	 * @param	boolean		If set, all values that are blank (='') will NOT be imploded
	 * @param	boolean		If set, parameters which were blank strings would be removed.
	 * @param	boolean		If set, the param name itselt (for example "param[key][key2]") would be rawurlencoded as well.
	 * @return	string		Imploded result, fx. &param[key][key2]=value2&param[key][key3]=value3
	 */
	function implodeArrayForUrl($name,$theArray,$str='',$skipBlank=0,$rawurlencodeParamName=0)	{
		if (is_array($theArray))	{
			foreach($theArray as $Akey => $AVal)	{
				$thisKeyName = $name ? $name.'['.$Akey.']' : $Akey;
				if (is_array($AVal))	{
					$str = t3lib_div::implodeArrayForUrl($thisKeyName,$AVal,$str,$skipBlank,$rawurlencodeParamName);
				} else {
					if (!$skipBlank || strcmp($AVal,''))	{
						$str.='&'.($rawurlencodeParamName ? rawurlencode($thisKeyName) : $thisKeyName).
							'='.rawurlencode($AVal);	// strips slashes because HTTP_POST_VARS / GET_VARS input is with slashes...
					}
				}
			}
		}
		return $str;
	}

	/**
	 * Returns an array with selected keys from incoming data.
	 * (Better read source code if you want to find out...)
	 * Usage: 3
	 *
	 * @param	string		List of variable/key names
	 * @param	array		Array from where to get values based on the keys in $varList
	 * @param	boolean		If set, then t3lib_div::_GP() is used to fetch the value if not found (isset) in the $getArray
	 * @return	array		Output array with selected variables.
	 */
	function compileSelectedGetVarsFromArray($varList,$getArray,$GPvarAlt=1)	{
		$keys = t3lib_div::trimExplode(',',$varList,1);
		$outArr=array();
		foreach($keys as $v)	{
			if (isset($getArray[$v]))	{
				$outArr[$v]=$getArray[$v];
			} elseif ($GPvarAlt) {
				$outArr[$v]=t3lib_div::_GP($v);
			}
		}
		return $outArr;
	}

	/**
	 * AddSlash array
	 * This function traverses a multidimentional array and adds slashes to the values.
	 * NOTE that the input array is and argument by reference.!!
	 * Twin-function to stripSlashesOnArray
	 *
	 * Usage: 6
	 *
	 * @param	array		Multidimensional input array, (REFERENCE!)
	 * @return	array
	 */
	function addSlashesOnArray(&$theArray)	{
		if (is_array($theArray))	{
			reset($theArray);
			while(list($Akey,$AVal)=each($theArray))	{
				if (is_array($AVal))	{
					t3lib_div::addSlashesOnArray($theArray[$Akey]);
				} else {
					$theArray[$Akey] = addslashes($AVal);
				}
			}
			reset($theArray);
		}
	}

	/**
	 * StripSlash array
	 * This function traverses a multidimentional array and strips slashes to the values.
	 * NOTE that the input array is and argument by reference.!!
	 * Twin-function to addSlashesOnArray
	 *
	 * Usage: 7
	 *
	 * @param	array		Multidimensional input array, (REFERENCE!)
	 * @return	array
	 */
	function stripSlashesOnArray(&$theArray)	{
		if (is_array($theArray))	{
			reset($theArray);
			while(list($Akey,$AVal)=each($theArray))	{
				if (is_array($AVal))	{
					t3lib_div::stripSlashesOnArray($theArray[$Akey]);
				} else {
					$theArray[$Akey] = stripslashes($AVal);
				}
			}
			reset($theArray);
		}
	}

	/**
	 * Either slashes ($cmd=add) or strips ($cmd=strip) array $arr depending on $cmd
	 *
	 * Usage: 6
	 *
	 * @param	array		Multidimensional input array
	 * @param	string		"add" or "strip", depending on usage you wish.
	 * @return	array
	 */
	function slashArray($arr,$cmd)	{
		if ($cmd=='strip')	t3lib_div::stripSlashesOnArray($arr);
		if ($cmd=='add')	t3lib_div::addSlashesOnArray($arr);
		return $arr;
	}

	/**
	 * Merges two arrays recursively, overruling similar the values in the first array ($arr0) with the values of the second array ($arr1)
	 * In case of identical keys, ie. keeping the values of the second.
	 *
	 * Usage: 26
	 *
	 * @param	array		First array
	 * @param	array		Second array, overruling the first array
	 * @param	boolean		If set, keys that are NOT found in $arr0 (first array) will not be set. Thus only existing value can/will be overruled from second array.
	 * @return	array		Resulting array where $arr1 values has overruled $arr0 values
	 */
	function array_merge_recursive_overrule ($arr0,$arr1,$notAddKeys=0) {
		reset($arr1);
		while(list($key,$val) = each($arr1)) {
			if(is_array($arr0[$key])) {
				if (is_array($arr1[$key]))	{
					$arr0[$key] = t3lib_div::array_merge_recursive_overrule($arr0[$key],$arr1[$key],$notAddKeys);
				}
			} else {
				if ($notAddKeys) {
					if (isset($arr0[$key])) {
						$arr0[$key] = $val;
					}
				} else {
					$arr0[$key] = $val;
				}
			}
		}
		reset($arr0);
		return $arr0;
	}

	/**
	 * An array_merge function where the keys are NOT renumbered as they happen to be with the real php-array_merge function
	 *
	 * Usage: 27
	 *
	 * @param	array		First array
	 * @param	array		Second array
	 * @return	array		Merged result.
	 */
	function array_merge($arr1,$arr2)	{
		return $arr2+$arr1;
	}

	/**
	 * Takes a row and returns a CSV string of the values with $delim (default is ,) and $quote (default is ") as separator chars.
	 *
	 * Usage: 5
	 *
	 * @param	array		Input array of values
	 * @param	string		Delimited, default is comman
	 * @param	string		Quote-character to wrap around the values.
	 * @return	string		A single line of CSV
	 */
	function csvValues($row,$delim=',',$quote='"')	{
		reset($row);
		$out=array();
		while(list(,$value)=each($row))	{
			list($valPart) = explode(chr(10),$value);
			$valPart = trim($valPart);
			$out[]=str_replace($quote,$quote.$quote,$valPart);
		}
		$str = $quote.implode($quote.$delim.$quote,$out).$quote;
		return $str;
	}
















	/*************************
	 *
	 * HTML/XML PROCESSING
	 *
	 *************************/

	/**
	 * $tag is either a whole tag (eg '<TAG OPTION ATTRIB=VALUE>') or the parameterlist (ex ' OPTION ATTRIB=VALUE>')
	 * Returns an array with all attributes as keys. Attributes are only lowercase a-z
	 * If a attribute is empty (I call it 'an option'), then the value for the key is empty. You can check if it existed with isset()
	 *
	 * Usage: 9
	 *
	 * @param	string		HTML-tag string (or attributes only)
	 * @return	array		Array with the attribute values.
	 */
	function get_tag_attributes($tag)	{
		$components = t3lib_div::split_tag_attributes($tag);
		$name = '';	 // attribute name is stored here
		$valuemode = '';
		if (is_array($components))	{
			while (list($key,$val) = each ($components))	{
				if ($val != '=')	{	// Only if $name is set (if there is an attribute, that waits for a value), that valuemode is enabled. This ensures that the attribute is assigned it's value
					if ($valuemode)	{
						if ($name)	{
							$attributes[$name] = $val;
							$name = '';
						}
					} else {
						if ($key = strtolower(ereg_replace('[^a-zA-Z0-9]','',$val)))	{
							$attributes[$key] = '';
							$name = $key;
						}
					}
					$valuemode = '';
				} else {
					$valuemode = 'on';
				}
			}
			if (is_array($attributes))	reset($attributes);
			return $attributes;
		}
	}

	/**
	 * Returns an array with the 'components' from an attribute list from an HTML tag. The result is normally analyzed by get_tag_attributes
	 * Removes tag-name if found
	 *
	 * Usage: 1
	 *
	 * @param	string		HTML-tag string (or attributes only)
	 * @return	array		Array with the attribute values.
	 * @internal
	 */
	function split_tag_attributes($tag)	{
		$tag_tmp = trim(eregi_replace ('^<[^[:space:]]*','',trim($tag)));
			// Removes any > in the end of the string
		$tag_tmp = trim(eregi_replace ('>$','',$tag_tmp));

		while (strcmp($tag_tmp,''))	{	// Compared with empty string instead , 030102
			$firstChar=substr($tag_tmp,0,1);
			if (!strcmp($firstChar,'"') || !strcmp($firstChar,"'"))	{
				$reg=explode($firstChar,$tag_tmp,3);
				$value[]=$reg[1];
				$tag_tmp=trim($reg[2]);
			} elseif (!strcmp($firstChar,'=')) {
				$value[] = '=';
				$tag_tmp = trim(substr($tag_tmp,1));		// Removes = chars.
			} else {
					// There are '' around the value. We look for the next ' ' or '>'
				$reg = split('[[:space:]=]',$tag_tmp,2);
				$value[] = trim($reg[0]);
				$tag_tmp = trim(substr($tag_tmp,strlen($reg[0]),1).$reg[1]);
			}
		}
		if (is_array($value))	reset($value);
		return $value;
	}

	/**
	 * Implodes attributes in the array $arr for an attribute list in eg. and HTML tag (with quotes)
	 *
	 * Usage: 10
	 *
	 * @param	array		Array with attribute key/value pairs, eg. "bgcolor"=>"red", "border"=>0
	 * @param	boolean		If set the resulting attribute list will have a) all attributes in lowercase (and duplicates weeded out, first entry taking precedence) and b) all values htmlspecialchar()'ed. It is recommended to use this switch!
	 * @param	boolean		If true, don't check if values are blank. Default is to omit attributes with blank values.
	 * @return	string		Imploded attributes, eg. 'bgcolor="red" border="0"'
	 */
	function implodeParams($arr,$xhtmlSafe=FALSE,$dontOmitBlankAttribs=FALSE)	{
		if (is_array($arr))	{
			if ($xhtmlSafe)	{
				$newArr=array();
				foreach($arr as $p => $v)	{
					if (!isset($newArr[strtolower($p)])) $newArr[strtolower($p)] = htmlspecialchars($v);
				}
				$arr = $newArr;
			}
			$list = array();
			foreach($arr as $p => $v)	{
				if (strcmp($v,'') || $dontOmitBlankAttribs)	{$list[]=$p.'="'.$v.'"';}
			}
			return implode(' ',$list);
		}
	}

	/**
	 * Wraps JavaScript code XHTML ready with <script>-tags
	 * Automatic re-identing of the JS code is done by using the first line as ident reference.
	 * This is nice for identing JS code with PHP code on the same level.
	 *
	 * @param	string		JavaScript code
	 * @param	boolean		Wrap script element in linebreaks? Default is TRUE.
	 * @return	string		The wrapped JS code, ready to put into a XHTML page
	 * @author	Ingmar Schlecht <ingmars@web.de>
	 * @author	René Fritz <r.fritz@colorcube.de>
	 */
	function wrapJS($string, $linebreak=TRUE) {
		if(trim($string)) {
				// <script wrapped in nl?
			$cr = $linebreak? "\n" : '';

				// remove nl from the beginning
			$string = preg_replace ('/^\n+/', '', $string);
				// re-ident to one tab using the first line as reference
			if(preg_match('/^(\t+)/',$string,$match)) {
				$string = str_replace($match[1],"\t", $string);
			}
			$string = $cr.'<script type="text/javascript">
/*<![CDATA[*/
'.$string.'
/*]]>*/
</script>'.$cr;
		}
		return trim($string);
	}


	/**
	 * Parses XML input into a PHP array with associative keys
	 *
	 * @param	string		XML data input
	 * @param	integer		Number of element levels to resolve the XML into an array. Any further structure will be set as XML.
	 * @return	mixed		The array with the parsed structure unless the XML parser returns with an error in which case the error message string is returned.
	 * @author bisqwit at iki dot fi dot not dot for dot ads dot invalid / http://dk.php.net/xml_parse_into_struct + kasper@typo3.com
	 */
	function xml2tree($string,$depth=999) {
		$parser = xml_parser_create();
		$vals = array();
		$index = array();

		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
		xml_parse_into_struct($parser, $string, $vals, $index);

		if (xml_get_error_code($parser))	return 'Line '.xml_get_current_line_number($parser).': '.xml_error_string(xml_get_error_code($parser));
		xml_parser_free($parser);

		$stack = array( array() );
		$stacktop = 0;
		$startPoint=0;

		unset($tagi);
		foreach($vals as $key => $val) {
			$type = $val['type'];

				// open tag:
			if ($type=='open' || $type=='complete') {
				$stack[$stacktop++] = $tagi;

				if ($depth==$stacktop)	{
					$startPoint=$key;
				}

				$tagi = array('tag' => $val['tag']);

				if(isset($val['attributes']))  $tagi['attrs'] = $val['attributes'];
				if(isset($val['value']))	$tagi['values'][] = $val['value'];
			}
				// finish tag:
			if ($type=='complete' || $type=='close')	{
				$oldtagi = $tagi;
				$tagi = $stack[--$stacktop];
				$oldtag = $oldtagi['tag'];
				unset($oldtagi['tag']);

				if ($depth==($stacktop+1))	{
					if ($key-$startPoint > 0)	{
						$partArray = array_slice(
							$vals,
							$startPoint+1,
							$key-$startPoint-1
						);
						#$oldtagi=array('XMLvalue'=>t3lib_div::xmlRecompileFromStructValArray($partArray));
						$oldtagi['XMLvalue']=t3lib_div::xmlRecompileFromStructValArray($partArray);
					} else {
						$oldtagi['XMLvalue']=$oldtagi['values'][0];
					}
				}

				$tagi['ch'][$oldtag][] = $oldtagi;
				unset($oldtagi);
			}
				// cdata
			if($type=='cdata') {
				$tagi['values'][] = $val['value'];
			}
		}
		return $tagi['ch'];
	}

	/**
	 * Converts a PHP array into an XML string.
	 * The XML output is optimized for readability since associative keys are used as tagnames.
	 * This also means that only alphanumeric characters are allowed in the tag names AND only keys NOT starting with numbers (so watch your usage of keys!). However there are options you can set to avoid this problem.
	 * Numeric keys are stored with the default tagname "numIndex" but can be overridden to other formats)
	 * The function handles input values from the PHP array in a binary-safe way; All characters below 32 (except 9,10,13) will trigger the content to be converted to a base64-string
	 * The PHP variable type of the data is IS preserved as long as the types are strings, arrays, integers and booleans. Strings are the default type unless the "type" attribute is set.
	 * The output XML has been tested with the PHP XML-parser and parses OK under all tested circumstances.
	 * However using MSIE to read the XML output didn't always go well: One reason could be that the character encoding is not observed in the PHP data. The other reason may be if the tag-names are invalid in the eyes of MSIE. Also using the namespace feature will make MSIE break parsing. There might be more reasons...
	 *
	 * @param	array		The input PHP array with any kind of data; text, binary, integers. Not objects though.
	 * @param	string		tag-prefix, eg. a namespace prefix like "T3:"
	 * @param	integer		Current recursion level. Don't change, stay at zero!
	 * @param	string		Alternative document tag. Default is "phparray".
	 * @param	integer		If set, the number of spaces corresponding to this number is used for indenting, otherwise a single chr(9) (TAB) is used
	 * @param	array		Options for the compilation. Key "useNindex" => 0/1 (boolean: whether to use "n0, n1, n2" for num. indexes); Key "useIndexTagForNum" => "[tag for numerical indexes]"; Key "useIndexTagForAssoc" => "[tag for associative indexes"; Key "parentTagMap" => array('parentTag' => 'thisLevelTag')
	 * @param	string		Parent tag name. Don't touch.
	 * @return	string		An XML string made from the input content in the array.
	 * @see xml2array()
	 */
	function array2xml($array,$NSprefix='',$level=0,$docTag='phparray',$spaceInd=0, $options=array(),$parentTagName='')	{
			// The list of byte values which will trigger binary-safe storage. If any value has one of these char values in it, it will be encoded in base64
		$binaryChars = chr(0).chr(1).chr(2).chr(3).chr(4).chr(5).chr(6).chr(7).chr(8).
						chr(11).chr(12).chr(14).chr(15).chr(16).chr(17).chr(18).chr(19).
						chr(20).chr(21).chr(22).chr(23).chr(24).chr(25).chr(26).chr(27).chr(28).chr(29).
						chr(30).chr(31);
			// Set indenting mode:
		$indentChar = $spaceInd ? ' ' : chr(9);
		$indentN = $spaceInd>0 ? $spaceInd : 1;

			// Init output variable:
		$output='';

			// Traverse the input array
		foreach($array as $k=>$v)	{
			$attr = '';
			$tagName = $k;

				// Construct the tag name.
			if (!strcmp(intval($tagName),$tagName))	{	// If integer...;
				if ($options['useNindex']) {	// If numeric key, prefix "n"
					$tagName = 'n'.$tagName;
				} else {	// Use special tag for num. keys:
					$attr.=' index="'.$tagName.'"';
					$tagName = $options['useIndexTagForNum'] ? $options['useIndexTagForNum'] : 'numIndex';
				}
			} elseif($options['useIndexTagForAssoc']) {		// Use tag for all associative keys:
				$attr.=' index="'.htmlspecialchars($tagName).'"';
				$tagName = $options['useIndexTagForAssoc'];
			} elseif(isset($options['parentTagMap'][$parentTagName])) {		// Use tag based on parent tag name:
				$attr.=' index="'.htmlspecialchars($tagName).'"';
				$tagName = (string)$options['parentTagMap'][$parentTagName];
			}

				// The tag name is cleaned up so only alphanumeric chars (plus - and _) are in there and not longer than 100 chars either.
			$tagName = substr(ereg_replace('[^[:alnum:]_-]','',$tagName),0,100);

				// If the value is an array then we will call this function recursively:
			if (is_array($v))	{
				// Sub elements:
				$content = chr(10).t3lib_div::array2xml($v,$NSprefix,$level+1,'',$spaceInd,$options,$tagName).
							str_pad('',($level+1)*$indentN,$indentChar);
				$attr.=' type="array"';
			} else {	// Just a value:

					// Look for binary chars:
				if (strcspn($v,$binaryChars) != strlen($v))	{	// Go for base64 encoding if the initial segment NOT matching any binary char has the same length as the whole string!
						// If the value contained binary chars then we base64-encode it an set an attribute to notify this situation:
					$content = chr(10).chunk_split(base64_encode($v));
					$attr.=' base64="1"';
				} else {
						// Otherwise, just htmlspecialchar the stuff:
					$content = htmlspecialchars($v);
					$dType = gettype($v);
					if ($dType!='string')	{ $attr.=' type="'.$dType.'"'; }
				}
			}

				// Add the element to the output string:
			$output.=str_pad('',($level+1)*$indentN,$indentChar).'<'.$NSprefix.$tagName.$attr.'>'.$content.'</'.$NSprefix.$tagName.'>'.chr(10);
		}

			// If we are at the outer-most level, then we finally wrap it all in the document tags and return that as the value:
		if (!$level)	{
			$output =
				'<'.$docTag.'>'.chr(10).
				$output.
				'</'.$docTag.'>';
		}

		return $output;
	}

	/**
	 * Converts an XML string to a PHP array.
	 * This is the reverse function of array2xml()
	 *
	 * @param	string		XML content to convert into an array
	 * @param	string		The tag-prefix resolve, eg. a namespace like "T3:"
	 * @return	mixed		If the parsing had errors, a string with the error message is returned. Otherwise an array with the content.
	 * @see array2xml()
	 */
	function xml2array($string,$NSprefix='') {

			// Create parser:
		$parser = xml_parser_create();
		$vals = array();
		$index = array();

		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
		xml_parse_into_struct($parser, $string, $vals, $index);

			// If error, return error message:
		if (xml_get_error_code($parser))	return 'Line '.xml_get_current_line_number($parser).': '.xml_error_string(xml_get_error_code($parser));
		xml_parser_free($parser);

			// Init vars:
		$stack = array(array());
		$stacktop = 0;
		$current=array();
		$tagName='';

			// Traverse the parsed XML structure:
		foreach($vals as $key => $val) {

				// First, process the tag-name (which is used in both cases, whether "complete" or "close")
			$tagName = $val['tag'];

				// Test for name space:
			$tagName = ($NSprefix && substr($tagName,0,strlen($NSprefix))==$NSprefix) ? substr($tagName,strlen($NSprefix)) : $tagName;

				// Test for numeric tag, encoded on the form "nXXX":
			$testNtag = substr($tagName,1);	// Closing tag.
			$tagName = (substr($tagName,0,1)=='n' && !strcmp(intval($testNtag),$testNtag)) ? intval($testNtag) : $tagName;

				// Test for alternative index value:
			if (strlen($val['attributes']['index']))	{ $tagName = $val['attributes']['index']; }

				// Setting tag-values, manage stack:
			switch($val['type'])	{
				case 'open':		// If open tag it means there is an array stored in sub-elements. Therefore increase the stackpointer and reset the accumulation array:
					$current[$tagName] = array();	// Setting blank place holder
					$stack[$stacktop++] = $current;
					$current = array();
				break;
				case 'close':	// If the tag is "close" then it is an array which is closing and we decrease the stack pointer.
					$oldCurrent = $current;
					$current = $stack[--$stacktop];
					end($current);	// Going to the end of array to get placeholder key, key($current), and fill in array next:
					$current[key($current)] = $oldCurrent;
					unset($oldCurrent);
				break;
				case 'complete':	// If "complete", then it's a value. If the attribute "base64" is set, then decode the value, otherwise just set it.
					if ($val['attributes']['base64'])	{
						$current[$tagName] = base64_decode($val['value']);
					} else {
						$current[$tagName] = (string)$val['value']; // Had to cast it as a string - otherwise it would be evaluate false if tested with isset()!!

							// Cast type:
						switch((string)$val['attributes']['type'])	{
							case 'integer':
								$current[$tagName] = (integer)$current[$tagName];
							break;
							case 'double':
								$current[$tagName] = (double)$current[$tagName];
							break;
							case 'boolean':
								$current[$tagName] = (bool)$current[$tagName];
							break;
							case 'array':
								$current[$tagName] = array();	// MUST be an empty array since it is processed as a value; Empty arrays would end up here because they would have no tags inside...
							break;
						}
					}
				break;
			}
		}

			// Finally return the content of the document tag.
		return $current[$tagName];
	}

	/**
	 * This implodes an array of XML parts (made with xml_parse_into_struct()) into XML again.
	 *
	 * @param	array		A array of XML parts, see xml2tree
	 * @return	string		Re-compiled XML data.
	 */
	function xmlRecompileFromStructValArray($vals)	{
		$XMLcontent='';

		foreach($vals as $val) {
			$type = $val['type'];

				// open tag:
			if ($type=='open' || $type=='complete') {
				$XMLcontent.='<'.$val['tag'];
				if(isset($val['attributes']))  {
					foreach($val['attributes'] as $k => $v)	{
						$XMLcontent.=' '.$k.'="'.htmlspecialchars($v).'"';
					}
				}
				if ($type=='complete')	{
					if(isset($val['value']))	{
						$XMLcontent.='>'.htmlspecialchars($val['value']).'</'.$val['tag'].'>';
					} else $XMLcontent.='/>';
				} else $XMLcontent.='>';

				if ($type=='open' && isset($val['value']))	{
					$XMLcontent.=htmlspecialchars($val['value']);
				}
			}
				// finish tag:
			if ($type=='close')	{
				$XMLcontent.='</'.$val['tag'].'>';
			}
				// cdata
			if($type=='cdata') {
				$XMLcontent.=htmlspecialchars($val['value']);
			}
		}

		return $XMLcontent;
	}

	/**
	 * Extract the encoding scheme as found in the first line of an XML document (typically)
	 *
	 * @param	string		XML data
	 * @return	string		Encoding scheme (lowercase), if found.
	 */
	function xmlGetHeaderAttribs($xmlData)	{
		$xmlHeader = substr(trim($xmlData),0,200);
		$reg=array();
		if (eregi('^<\?xml([^>]*)\?\>',$xmlHeader,$reg))	{
			return t3lib_div::get_tag_attributes($reg[1]);
		}
	}











	/*************************
	 *
	 * FILES FUNCTIONS
	 *
	 *************************/

	/**
	 * Reads the file or url $url and returns the content
	 * If you are having trouble with proxys when reading URLs you can configure your way out of that with settings like $TYPO3_CONF_VARS['SYS']['curlUse'] etc.
	 *
	 * Usage: 79
	 *
	 * @param	string		Filepath/URL to read
	 * @return	string		The content from the resource given as input.
	 */
	function getURL($url)	{
		$content = '';

			// (Proxy support implemented by Arco <arco@appeltaart.mine.nu>)
		if((substr($url,0,7)=='http://') && ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse']=='1'))	{
			//external URL without error checking.
			$ch = curl_init();
			curl_setopt ($ch,CURLOPT_URL, $url);
			curl_setopt ($ch,CURLOPT_HEADER, 0);
			curl_setopt ($ch,CURLOPT_RETURNTRANSFER, 1);

			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']) {
				curl_setopt ($ch, CURLOPT_PROXY, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']);

				// I don't know if it will be needed
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']) {
					curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel'] );
				}
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']) {
					curl_setopt ($ch, CURLOPT_PROXYUSERPWD, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass'] );
				}
			}
			$content=curl_exec ($ch);
			curl_close ($ch);
			return $content;
		} elseif($fd = fopen($url,'rb'))    {
			while (!feof($fd))	{
				$content.=fread($fd, 5000);
			}
			fclose($fd);
			return $content;
		}
	}

	/**
	 * Writes $content to the file $file
	 *
	 * Usage: 31
	 *
	 * @param	string		Filepath to write to
	 * @param	string		Content to write
	 * @return	boolean		True if the file was successfully opened and written to.
	 */
	function writeFile($file,$content)	{
		if($fd = fopen($file,'wb'))	{
			fwrite( $fd, $content);
			fclose( $fd );

				// Setting file system mode of file:
			if (@is_file($file) && TYPO3_OS!='WIN')	{
				@chmod($file, octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask']));		// "@" is there because file is not necessarily OWNED by the user
			}

			return true;
		}
	}

	/**
	 * Wrapper function for mkdir, setting folder permissions according to $GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']
	 *
	 * @param	string		Absolute path to folder, see PHP mkdir() function. Removes trailing slash internally.
	 * @return	boolean		TRUE if @mkdir went well!
	 */
	function mkdir($theNewFolder)	{
		$theNewFolder = ereg_replace('\/$','',$theNewFolder);
		if (mkdir($theNewFolder, octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']))){
			chmod($theNewFolder, octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'])); //added this line, because the mode at 'mkdir' has a strange behaviour sometimes
			return TRUE;
		}
	}

	/**
	 * Returns an array with the names of folders in a specific path
	 * Will return 'error' (string) if there were an error with reading directory content.
	 *
	 * Usage: 13
	 *
	 * @param	string		Path to list directories from
	 * @return	array		Returns an array with the directory entries as values. If no path, the return value is nothing.
	 */
	function get_dirs($path)	{
		if ($path)	{
			$d = @dir($path);
			if (is_object($d))	{
				while($entry=$d->read()) {
					if (@is_dir($path.'/'.$entry) && $entry!= '..' && $entry!= '.')	{
					    $filearray[]=$entry;
					}
				}
				$d->close();
			} else return 'error';
			return $filearray;
		}
	}

	/**
	 * Returns an array with the names of files in a specific path
	 *
	 * Usage: 17
	 *
	 * @param	string		$path: Is the path to the file
	 * @param	string		$extensionList is the comma list of extensions to read only (blank = all)
	 * @param	boolean		If set, then the path is prepended the filenames. Otherwise only the filenames are returned in the array
	 * @param	string		$order is sorting: 1= sort alphabetically, 'mtime' = sort by modification time.
	 * @return	array		Array of the files found
	 */
	function getFilesInDir($path,$extensionList='',$prependPath=0,$order='')	{

			// Initialize variabels:
		$filearray = array();
		$sortarray = array();
		$path = ereg_replace('\/$','',$path);

			// Find files+directories:
		if (@is_dir($path))	{
			$extensionList = strtolower($extensionList);
			$d = dir($path);
			if (is_object($d))	{
				while($entry=$d->read()) {
					if (@is_file($path.'/'.$entry))	{
						$fI = pathinfo($entry);
						$key = md5($path.'/'.$entry);
						if (!$extensionList || t3lib_div::inList($extensionList,strtolower($fI['extension'])))	{
						    $filearray[$key]=($prependPath?$path.'/':'').$entry;
							if ($order=='mtime') {$sortarray[$key]=filemtime($path.'/'.$entry);}
								elseif ($order)	{$sortarray[$key]=$entry;}
						}
					}
				}
				$d->close();
			} else return 'error opening path: "'.$path.'"';
		}

			// Sort them:
		if ($order) {
			asort($sortarray);
			reset($sortarray);
			$newArr=array();
			while(list($k,$v)=each($sortarray))	{
				$newArr[$k]=$filearray[$k];
			}
			$filearray=$newArr;
		}

			// Return result
		reset($filearray);
		return $filearray;
	}

	/**
	 * Recursively gather all files and folders of a path.
	 *
	 * @param	array		$fileArr: Empty input array (will have files added to it)
	 * @param	string		$path: The path to read recursively from (absolute) (include trailing slash!)
	 * @param	string		$extList: Comma list of file extensions: Only files with extensions in this list (if applicable) will be selected.
	 * @param	boolean		$regDirs: If set, directories are also included in output.
	 * @param	integer		$recursivityLevels: The number of levels to dig down...
	 * @return	array		An array with the found files/directories.
	 */
	function getAllFilesAndFoldersInPath($fileArr,$path,$extList='',$regDirs=0,$recursivityLevels=99)	{
		if ($regDirs)	$fileArr[] = $path;
		$fileArr = array_merge($fileArr, t3lib_div::getFilesInDir($path,$extList,1,1));

		$dirs = t3lib_div::get_dirs($path);
		if (is_array($dirs) && $recursivityLevels>0)	{
			foreach ($dirs as $subdirs)	{
				if ((string)$subdirs!='')	{
					$fileArr = t3lib_div::getAllFilesAndFoldersInPath($fileArr,$path.$subdirs.'/',$extList,$regDirs,$recursivityLevels-1);
				}
			}
		}
		return $fileArr;
	}

	/**
	 * Removes the absolute part of all files/folders in fileArr
	 *
	 * @param	array		$fileArr: The file array to remove the prefix from
	 * @param	string		$prefixToRemove: The prefix path to remove (if found as first part of string!)
	 * @return	array		The input $fileArr processed.
	 */
	function removePrefixPathFromList($fileArr,$prefixToRemove)	{
		foreach($fileArr as $k => $absFileRef)	{
			if(t3lib_div::isFirstPartOfStr($absFileRef,$prefixToRemove))	{
				$fileArr[$k] = substr($absFileRef,strlen($prefixToRemove));
			} else return 'ERROR: One or more of the files was NOT prefixed with the prefix-path!';
		}
		return $fileArr;
	}

	/**
	 * Fixes a path for windows-backslashes and reduces double-slashes to single slashes
	 *
	 * Usage: 2
	 *
	 * @param	string		File path to process
	 * @return	string
	 */
	function fixWindowsFilePath($theFile)	{
		return str_replace('//','/', str_replace('\\','/', $theFile));
	}

	/**
	 * Resolves "../" sections in the input path string
	 *
	 * @param	string		File path in which "/../" is resolved
	 * @return	string
	 */
	function resolveBackPath($pathStr)	{
		$parts = explode('/',$pathStr);
		$output=array();
		foreach($parts as $pV)	{
			if ($pV=='..')	{
				if ($c)	{
					array_pop($output);
					$c--;
				} else $output[]=$pV;
			} else {
				$c++;
				$output[]=$pV;
			}
		}
		return implode('/',$output);
	}

	/**
	 * Prefixes a URL used with 'header-location' with 'http://...' depending on whether it has it already.
	 * - If already having a scheme, nothing is prepended
	 * - If having REQUEST_URI slash '/', then prefixing 'http://[host]' (relative to host)
	 * - Otherwise prefixed with TYPO3_REQUEST_DIR (relative to current dir / TYPO3_REQUEST_DIR)
	 *
	 * Usage: 31
	 *
	 * @param	string		URL / path to prepend full URL addressing to.
	 * @return	string
	 */
	function locationHeaderUrl($path)	{
		$uI = parse_url($path);
		if (substr($path,0,1)=='/')	{ // relative to HOST
			$path = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').$path;
		} elseif (!$uI['scheme'])	{ // No scheme either
			$path = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR').$path;
		}
		return $path;
	}
















	/*************************
	 *
	 * DEBUG helper FUNCTIONS
	 *
	 *************************/

	/**
	 * Returns a string with a list of ascii-values for the first $characters characters in $string
	 * Usage: 5
	 *
	 * @param	string		String to show ASCII value for
	 * @param	integer		Number of characters to show
	 * @return	string		The string with ASCII values in separated by a space char.
	 * @internal
	 */
	function debug_ordvalue($string,$characters=100)	{
		if(strlen($string) < $characters)	$characters = strlen($string);
		for ($i=0; $i<$characters; $i++)	{
			$valuestring.=' '.ord(substr($string,$i,1));
		}
		return trim($valuestring);
	}

	/**
	 * Returns HTML-code, which is a visual representation of a multidimensional array
	 * use t3lib_div::print_array() in order to print an array
	 * Returns false if $array_in is not an array
	 * Usage: 27
	 *
	 * @param	array		Array to view
	 * @return	string		HTML output
	 */
	function view_array($array_in)	{
		if (is_array($array_in))	{
			$result='<table border="1" cellpadding="1" cellspacing="0" bgcolor="white">';
			if (!count($array_in))	{$result.= '<tr><td><font face="Verdana,Arial" size="1"><b>'.HTMLSpecialChars("EMPTY!").'</b></font></td></tr>';}
			while (list($key,$val)=each($array_in))	{
				$result.= '<tr><td><font face="Verdana,Arial" size="1">'.HTMLSpecialChars((string)$key).'</font></td><td>';
				if (is_array($array_in[$key]))	{
					$result.=t3lib_div::view_array($array_in[$key]);
				} else
					$result.= '<font face="Verdana,Arial" size="1" color="red">'.nl2br(HTMLSpecialChars((string)$val)).'<br /></font>';
				$result.= '</td></tr>';
			}
			$result.= '</table>';
		} else	{
			$result  = false;
		}
		return $result;
	}

	/**
	 * Prints an array
	 * Usage: 28
	 *
	 * @param	array		Array to print visually (in a table).
	 * @return	void
	 * @internal
	 * @see view_array()
	 */
	function print_array($array_in)	{
		echo t3lib_div::view_array($array_in);
	}

	/**
	 * Makes debug output
	 * Prints $var in bold between two vertical lines
	 * If not $var the word 'debug' is printed
	 * If $var is an array, the array is printed by t3lib_div::print_array()
	 *
	 * Usage: 8
	 *
	 * @param	mixed		Variable to print
	 * @param	mixed		If the parameter is a string it will be used as header. Otherwise number of break tags to apply after (positive integer) or before (negative integer) the output.
	 * @return	void
	 */
	function debug($var="",$brOrHeader=0)	{
		if ($brOrHeader && !t3lib_div::testInt($brOrHeader)) {
			echo '<table border="0" cellpadding="0" cellspacing="0" bgcolor="white" style="border:0px;margin-top:3px;margin-bottom:3px;"><tr><td bgcolor="#bbbbbb"><font face="Verdana,Arial" size="1">&nbsp;<b>'.(string)HTMLSpecialChars($brOrHeader).'</b></font></td></tr><td>';
		} elseif ($brOrHeader<0) {
			for($a=0;$a<abs(intval($brOrHeader));$a++){echo '<br />';}
		}

		if (is_array($var))	{
			t3lib_div::print_array($var);
		} elseif (is_object($var))	{
			echo '<b>|Object:<pre>';
			print_r($var);
			echo '</pre>|</b>';
		} elseif ((string)$var!='') {
			echo '<b>|'.HTMLSpecialChars((string)$var).'|</b>';
		} else {
			echo '<b>| debug |</b>';
		}

		if ($brOrHeader && !t3lib_div::testInt($brOrHeader)) {
			echo '</td></tr></table>';
		} elseif ($brOrHeader>0) {
			for($a=0;$a<intval($brOrHeader);$a++){echo '<br />';}
		}
	}
































	/*************************
	 *
	 * SYSTEM INFORMATION
	 *
	 *************************/

	/**
	 * Returns the HOST+DIR-PATH of the current script (The URL, but without 'http://' and without script-filename)
	 * Usage: 1
	 *
	 * @return	string
	 */
	function getThisUrl()	{
		$p=parse_url(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT'));		// Url of this script
		$dir=t3lib_div::dirname($p['path']).'/';	// Strip file
		$url = str_replace('//','/',$p['host'].($p['port']?':'.$p['port']:'').$dir);
		return $url;
	}

	/**
	 * Returns the link-url to the current script.
	 * In $getParams you can set associative keys corresponding to the get-vars you wish to add to the url. If you set them empty, they will remove existing get-vars from the current url.
	 * REMEMBER to always htmlspecialchar() content in href-properties to ampersands get converted to entities (XHTML requirement and XSS precaution)
	 *
	 * Usage: 54
	 *
	 * @param	array		Array of GET parameters to include
	 * @return	string
	 */
	function linkThisScript($getParams=array())	{
		$parts = t3lib_div::getIndpEnv('SCRIPT_NAME');
		$params = t3lib_div::_GET();

		foreach($getParams as $k => $v)	{
			if (strcmp($v,''))	{
				$params[$k]=$v;
			} else unset($params[$k]);
		}

		$pString = t3lib_div::implodeArrayForUrl('',$params);

		return $pString ? $parts.'?'.ereg_replace('^&','',$pString) : $parts;
	}

	/**
	 * Takes a full URL, $url, possibly with a querystring and overlays the $getParams arrays values onto the quirystring, packs it all together and returns the URL again.
	 * So basically it adds the parameters in $getParams to an existing URL, $url
	 * Usage: 2
	 *
	 * @param	string		URL string
	 * @param	array		Array of key/value pairs for get parameters to add/overrule with. Can be multidimensional.
	 * @return	string		Output URL with added getParams.
	 */
	function linkThisUrl($url,$getParams=array())	{
		$parts = parse_url($url);
		if ($parts['query'])	{
			parse_str($parts['query'],$getP);
		} else {
			$getP = array();
		}

		$getP = t3lib_div::array_merge_recursive_overrule($getP,$getParams);
		$uP = explode('?',$url);

		$params = t3lib_div::implodeArrayForUrl('',$getP);
		$outurl = $uP[0].($params ? '?'.substr($params, 1) : '');

		return $outurl;
	}

	/**
	 * Abstraction method which returns System Environment Variables regardless of server OS, CGI/MODULE version etc. Basically this is SERVER variables for most of them.
	 * This should be used instead of getEnv() and HTTP_SERVER_VARS/ENV_VARS to get reliable values for all situations.
	 *
	 * Usage: 226
	 *
	 * @param	string		Name of the "environment variable"/"server variable" you wish to use. Valid values are SCRIPT_NAME, SCRIPT_FILENAME, REQUEST_URI, PATH_INFO, REMOTE_ADDR, REMOTE_HOST, HTTP_REFERER, HTTP_HOST, HTTP_USER_AGENT, HTTP_ACCEPT_LANGUAGE, QUERY_STRING, TYPO3_DOCUMENT_ROOT, TYPO3_HOST_ONLY, TYPO3_HOST_ONLY, TYPO3_REQUEST_HOST, TYPO3_REQUEST_URL, TYPO3_REQUEST_SCRIPT, TYPO3_REQUEST_DIR, TYPO3_SITE_URL, _ARRAY
	 * @return	string		Value based on the input key, independent of server/os environment.
	 */
	function getIndpEnv($getEnvName)	{
		global $HTTP_SERVER_VARS;
		/*
			Conventions:
			output from parse_url():
			URL:	http://username:password@192.168.1.4:8080/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value#link1
			    [scheme] => 'http'
			    [user] => 'username'
			    [pass] => 'password'
			    [host] => '192.168.1.4'
				[port] => '8080'
			    [path] => '/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/'
			    [query] => 'arg1,arg2,arg3&p1=parameter1&p2[key]=value'
			    [fragment] => 'link1'

				Further definition: [path_script] = '/typo3/32/temp/phpcheck/index.php'
									[path_dir] = '/typo3/32/temp/phpcheck/'
									[path_info] = '/arg1/arg2/arg3/'
									[path] = [path_script/path_dir][path_info]


			Keys supported:

			URI______:
				REQUEST_URI		=	[path]?[query]		= /typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value
				HTTP_HOST		=	[host][:[port]]		= 192.168.1.4:8080
				SCRIPT_NAME		=	[path_script]++		= /typo3/32/temp/phpcheck/index.php		// NOTICE THAT SCRIPT_NAME will return the php-script name ALSO. [path_script] may not do that (eg. '/somedir/' may result in SCRIPT_NAME '/somedir/index.php')!
				PATH_INFO		=	[path_info]			= /arg1/arg2/arg3/
				QUERY_STRING	=	[query]				= arg1,arg2,arg3&p1=parameter1&p2[key]=value
				HTTP_REFERER	=	[scheme]://[host][:[port]][path]	= http://192.168.1.4:8080/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value
										(Notice: NO username/password + NO fragment)

			CLIENT____:
				REMOTE_ADDR		=	(client IP)
				REMOTE_HOST		=	(client host)
				HTTP_USER_AGENT	=	(client user agent)
				HTTP_ACCEPT_LANGUAGE	= (client accept language)

			SERVER____:
				SCRIPT_FILENAME	=	Absolute filename of script		(Differs between windows/unix). On windows 'C:\\blabla\\blabl\\' will be converted to 'C:/blabla/blabl/'

			Special extras:
				TYPO3_HOST_ONLY	=		[host]			= 192.168.1.4
				TYPO3_PORT		=		[port]			= 8080 (blank if 80, taken from host value)
				TYPO3_REQUEST_HOST = 	[scheme]://[host][:[port]]
				TYPO3_REQUEST_URL =		[scheme]://[host][:[port]][path]?[query]	(sheme will by default be 'http' until we can detect if it's https -
				TYPO3_REQUEST_SCRIPT =  [scheme]://[host][:[port]][path_script]
				TYPO3_REQUEST_DIR =		[scheme]://[host][:[port]][path_dir]
				TYPO3_SITE_URL = 		[scheme]://[host][:[port]][path_dir] of the TYPO3 website
				TYPO3_SITE_SCRIPT = 	[script / Speaking URL] of the TYPO3 website
				TYPO3_DOCUMENT_ROOT	=	Absolute path of root of documents:	TYPO3_DOCUMENT_ROOT.SCRIPT_NAME = SCRIPT_FILENAME (typically)

			Notice: [fragment] is apparently NEVER available to the script!


			Testing suggestions:
			- Output all the values.
			- In the script, make a link to the script it self, maybe add some parameters and click the link a few times so HTTP_REFERER is seen
			- ALSO TRY the script from the ROOT of a site (like 'http://www.mytest.com/' and not 'http://www.mytest.com/test/' !!)

		*/

#		if ($getEnvName=='HTTP_REFERER')	return '';
		switch((string)$getEnvName)	{
			case 'SCRIPT_NAME':
				return php_sapi_name()=='cgi' ? $HTTP_SERVER_VARS['PATH_INFO'] : $HTTP_SERVER_VARS['SCRIPT_NAME'];
			break;
			case 'SCRIPT_FILENAME':
				return str_replace('//','/', str_replace('\\','/', php_sapi_name()=='cgi'||php_sapi_name()=='isapi' ? $HTTP_SERVER_VARS['PATH_TRANSLATED']:$HTTP_SERVER_VARS['SCRIPT_FILENAME']));
			break;
			case 'REQUEST_URI':
				// Typical application of REQUEST_URI is return urls, forms submitting to itselt etc. Eg:	returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))
				if (!$HTTP_SERVER_VARS['REQUEST_URI'])	{	// This is for ISS/CGI which does not have the REQUEST_URI available.
					return '/'.ereg_replace('^/','',t3lib_div::getIndpEnv('SCRIPT_NAME')).
						($HTTP_SERVER_VARS['QUERY_STRING']?'?'.$HTTP_SERVER_VARS['QUERY_STRING']:'');
				} else return $HTTP_SERVER_VARS['REQUEST_URI'];
			break;
			case 'PATH_INFO':
					// $HTTP_SERVER_VARS['PATH_INFO']!=$HTTP_SERVER_VARS['SCRIPT_NAME'] is necessary because some servers (Windows/CGI) are seen to set PATH_INFO equal to script_name
					// Further, there must be at least one '/' in the path - else the PATH_INFO value does not make sense.
					// IF 'PATH_INFO' never works for our purpose in TYPO3 with CGI-servers, then 'php_sapi_name()=='cgi'' might be a better check. Right now strcmp($HTTP_SERVER_VARS['PATH_INFO'],t3lib_div::getIndpEnv('SCRIPT_NAME')) will always return false for CGI-versions, but that is only as long as SCRIPT_NAME is set equal to PATH_INFO because of php_sapi_name()=='cgi' (see above)
//				if (strcmp($HTTP_SERVER_VARS['PATH_INFO'],t3lib_div::getIndpEnv('SCRIPT_NAME')) && count(explode('/',$HTTP_SERVER_VARS['PATH_INFO']))>1)	{
				if (php_sapi_name()!='cgi')	{
					return $HTTP_SERVER_VARS['PATH_INFO'];
				} else return '';
			break;
				// These are let through without modification
			case 'REMOTE_ADDR':
			case 'REMOTE_HOST':
			case 'HTTP_REFERER':
			case 'HTTP_HOST':
			case 'HTTP_USER_AGENT':
			case 'HTTP_ACCEPT_LANGUAGE':
			case 'QUERY_STRING':
				return $HTTP_SERVER_VARS[$getEnvName];
			break;
			case 'TYPO3_DOCUMENT_ROOT':
				// Some CGI-versions (LA13CGI) and mod-rewrite rules on MODULE versions will deliver a 'wrong' DOCUMENT_ROOT (according to our description). Further various aliases/mod_rewrite rules can disturb this as well.
				// Therefore the DOCUMENT_ROOT is now always calculated as the SCRIPT_FILENAME minus the end part shared with SCRIPT_NAME.
				$SFN = t3lib_div::getIndpEnv('SCRIPT_FILENAME');
				$SN_A = explode('/',strrev(t3lib_div::getIndpEnv('SCRIPT_NAME')));
				$SFN_A = explode('/',strrev($SFN));
				$acc = array();
				while(list($kk,$vv)=each($SN_A))	{
					if (!strcmp($SFN_A[$kk],$vv))	{
						$acc[] = $vv;
					} else break;
				}
				$commonEnd=strrev(implode('/',$acc));
				if (strcmp($commonEnd,''))		$DR = substr($SFN,0,-(strlen($commonEnd)+1));
				return $DR;
			break;
			case 'TYPO3_HOST_ONLY':
				$p = explode(':',$HTTP_SERVER_VARS['HTTP_HOST']);
				return $p[0];
			break;
			case 'TYPO3_PORT':
				$p = explode(':',$HTTP_SERVER_VARS['HTTP_HOST']);
				return $p[1];
			break;
			case 'TYPO3_REQUEST_HOST':
				return (t3lib_div::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://').
					$HTTP_SERVER_VARS['HTTP_HOST'];
			break;
			case 'TYPO3_REQUEST_URL':
				return t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').t3lib_div::getIndpEnv('REQUEST_URI');
			break;
			case 'TYPO3_REQUEST_SCRIPT':
				return t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').t3lib_div::getIndpEnv('SCRIPT_NAME');
			break;
			case 'TYPO3_REQUEST_DIR':
				return t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')).'/';
			break;
			case 'TYPO3_SITE_URL':
				if (defined('PATH_thisScript') && defined('PATH_site'))	{
					$lPath = substr(dirname(PATH_thisScript),strlen(PATH_site)).'/';
					$url = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR');
					$siteUrl = substr($url,0,-strlen($lPath));
					if (substr($siteUrl,-1)!='/')	$siteUrl.='/';
					return $siteUrl;
				} else return '';
			break;
			case 'TYPO3_SITE_SCRIPT':
				return substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),strlen(t3lib_div::getIndpEnv('TYPO3_SITE_URL')));
			break;
			case 'TYPO3_SSL':
				return ($HTTP_SERVER_VARS['SSL_SESSION_ID'] || $HTTP_SERVER_VARS['HTTPS']) ? TRUE : FALSE;
			break;
			case '_ARRAY':
				$out = array();
					// Here, list ALL possible keys to this function for debug display.
				$envTestVars = t3lib_div::trimExplode(',','
					HTTP_HOST,
					TYPO3_HOST_ONLY,
					TYPO3_PORT,
					PATH_INFO,
					QUERY_STRING,
					REQUEST_URI,
					HTTP_REFERER,
					TYPO3_REQUEST_HOST,
					TYPO3_REQUEST_URL,
					TYPO3_REQUEST_SCRIPT,
					TYPO3_REQUEST_DIR,
					TYPO3_SITE_URL,
					TYPO3_SITE_SCRIPT,
					TYPO3_SSL,
					SCRIPT_NAME,
					TYPO3_DOCUMENT_ROOT,
					SCRIPT_FILENAME,
					REMOTE_ADDR,
					REMOTE_HOST,
					HTTP_USER_AGENT,
					HTTP_ACCEPT_LANGUAGE',1);
				reset($envTestVars);
				while(list(,$v)=each($envTestVars))	{
					$out[$v]=t3lib_div::getIndpEnv($v);
				}
				reset($out);
				return $out;
			break;
		}
	}

	/**
	 * milliseconds
	 *
	 * microtime recalculated to t3lib_div::milliseconds(1/1000 sec)
	 *
	 * Usage: 39
	 *
	 * @return	integer
	 */
	function milliseconds()	{
		$p=explode(' ',microtime());
		return round(($p[0]+$p[1])*1000);
	}

	/**
	 * Client Browser Information
	 *
	 * Usage: 4
	 *
	 * @param	string		Alternative User Agent string (if empty, t3lib_div::getIndpEnv('HTTP_USER_AGENT') is used)
	 * @return	array		Parsed information about the HTTP_USER_AGENT in categories BROWSER, VERSION, SYSTEM and FORMSTYLE
	 */
	function clientInfo($useragent='')	{
		if (!$useragent) $useragent=t3lib_div::getIndpEnv('HTTP_USER_AGENT');

		$bInfo=array();
			// Which browser?
		if (strstr($useragent,'Konqueror'))	{
			$bInfo['BROWSER']= 'konqu';
		} elseif (strstr($useragent,'Opera'))	{
			$bInfo['BROWSER']= 'opera';
		} elseif (strstr($useragent,'MSIE 4') || strstr($useragent,'MSIE 5') || strstr($useragent,'MSIE 6'))	{
			$bInfo['BROWSER']= 'msie';
		} elseif (strstr($useragent,'Mozilla/4') || strstr($useragent,'Mozilla/5'))	{
			$bInfo['BROWSER']='net';
		}
		if ($bInfo['BROWSER'])	{
				// Browser version
			switch($bInfo['BROWSER'])	{
				case 'net':
					$bInfo['VERSION']= doubleval(substr($useragent,8));
					if (strstr($useragent,'Netscape6/')) {$bInfo['VERSION']=doubleval(substr(strstr($useragent,'Netscape6/'),10));}
					if (strstr($useragent,'Netscape/7')) {$bInfo['VERSION']=doubleval(substr(strstr($useragent,'Netscape/7'),9));}
				break;
				case 'msie':
					$tmp = strstr($useragent,'MSIE');
					$bInfo['VERSION'] = doubleval(ereg_replace('^[^0-9]*','',substr($tmp,4)));
				break;
				case 'opera':
					$tmp = strstr($useragent,'Opera');
					$bInfo['VERSION'] = doubleval(ereg_replace('^[^0-9]*','',substr($tmp,5)));
				break;
				case 'konqu':
					$tmp = strstr($useragent,'Konqueror/');
					$bInfo['VERSION'] = doubleval(substr($tmp,10));
				break;
			}
				// Client system
			if (strstr($useragent,'Win'))	{
				$bInfo['SYSTEM'] = 'win';
			} elseif (strstr($useragent,'Mac'))	{
				$bInfo['SYSTEM'] = 'mac';
			} elseif (strstr($useragent,'Linux') || strstr($useragent,'X11') || strstr($useragent,'SGI') || strstr($useragent,' SunOS ') || strstr($useragent,' HP-UX '))	{
				$bInfo['SYSTEM'] = 'unix';
			}
		}
			// Is true if the browser supports css to format forms, especially the width
		$bInfo['FORMSTYLE']=($bInfo['BROWSER']=='msie' || ($bInfo['BROWSER']=='net'&&$bInfo['VERSION']>=5) || $bInfo['BROWSER']=='opera' || $bInfo['BROWSER']=='konqu');

		return $bInfo;
	}























	/*************************
	 *
	 * TYPO3 SPECIFIC FUNCTIONS
	 *
	 *************************/

	/**
	 * Returns the absolute filename of $filename.
	 * Decodes the prefix EXT: for TYPO3 Extensions.
	 *
	 * Usage: 9
	 *
	 * @param	string		The input filename/filepath to evaluate
	 * @param	boolean		If $onlyRelative is set (which it is by default), then only return values relative to the current PATH_site is accepted.
	 * @param	boolean		If $relToTYPO3_mainDir is set, then relative paths are relative to PATH_typo3 constant - otherwise (default) they are relative to PATH_site
	 * @return	string		Returns the absolute filename of $filename IF valid, otherwise blank string.
	 */
	function getFileAbsFileName($filename,$onlyRelative=1,$relToTYPO3_mainDir=0)	{
		if (!strcmp($filename,''))		return '';

		if ($relToTYPO3_mainDir)	{
			if (!defined('PATH_typo3'))	return '';
			$relPathPrefix = PATH_typo3;
		} else {
			$relPathPrefix = PATH_site;
		}
		if (substr($filename,0,4)=='EXT:')	{	// extension
			list($extKey,$local) = explode('/',substr($filename,4),2);
			$filename='';
			if (strcmp($extKey,'') && t3lib_extMgm::isLoaded($extKey) && strcmp($local,''))	{
				$filename = t3lib_extMgm::extPath($extKey).$local;
			}
		} elseif (!t3lib_div::isAbsPath($filename))	{	// relative. Prepended with $relPathPrefix
			$filename=$relPathPrefix.$filename;
		} elseif ($onlyRelative && !t3lib_div::isFirstPartOfStr($filename,$relPathPrefix)) {	// absolute, but set to blank if not allowed
			$filename='';
		}
		if (strcmp($filename,'') && t3lib_div::validPathStr($filename))	{	// checks backpath.
			return $filename;
		}
	}

	/**
	 * Returns true if no '//', '..' or '\' is in the $theFile
	 * This should make sure that the path is not pointing 'backwards' and further doesn't contain double/back slashes.
	 * So it's compatible with  the UNIX style path strings valid for TYPO3 internally.
	 *
	 * Usage: 8
	 *
	 * @param	string		Filepath to evaluate
	 * @return	boolean		True, if no '//', '..' or '\' is in the $theFile
	 * @todo	Possible improvement: Should it rawurldecode the string first to check if any of these characters is encoded ?
	 */
	function validPathStr($theFile)	{
		if (!strstr($theFile,'//') && !strstr($theFile,'..') && !strstr($theFile,'\\'))	return true;
	}

	/**
	 * Checks if the $path is absolute or relative (detecting either '/' or 'x:/' as first part of string) and returns true if so.
	 *
	 * Usage: 9
	 *
	 * @param	string		Filepath to evaluate
	 * @return	boolean
	 */
	function isAbsPath($path)	{
		return TYPO3_OS=='WIN' ? substr($path,1,2)==':/' :  substr($path,0,1)=='/';
	}

	/**
	 * Returns true if the path is absolute, without backpath '..' and within the PATH_site OR within the lockRootPath
	 *
	 * Usage: 1
	 *
	 * @param	string		Filepath to evaluate
	 * @return	boolean
	 */
	function isAllowedAbsPath($path)	{
		if (t3lib_div::isAbsPath($path) &&
			t3lib_div::validPathStr($path) &&
				(	t3lib_div::isFirstPartOfStr($path,PATH_site)
					||
					($GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] && t3lib_div::isFirstPartOfStr($path,$GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']))
				)
			)	return true;
	}

	/**
	 * Verifies the input filename againts the 'fileDenyPattern'. Returns true if OK.
	 *
	 * Usage: 2
	 *
	 * @param	string		Filepath to evaluate
	 * @return	boolean
	 */
	function verifyFilenameAgainstDenyPattern($filename)	{
		if (strcmp($filename,'') && strcmp($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'],''))	{
			$result = eregi($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'],$filename);
			if ($result)	return false;	// so if a matching filename is found, return false;
		}
		return true;
	}

	/**
	 * Moves $source file to $destination if uploaded, otherwise try to make a copy
	 * Usage: 3
	 *
	 * @param	string		Source file, absolute path
	 * @param	string		Destination file, absolute path
	 * @return	boolean		Returns true if the file was moved.
	 * @coauthor	Dennis Petersen <fessor@software.dk>
	 * @see upload_to_tempfile()
	 */
	function upload_copy_move($source,$destination)	{
		if (is_uploaded_file($source))	{
			$uploaded = TRUE;
			// Return the value of move_uploaded_file, and if false the temporary $source is still around so the user can use unlink to delete it:
			$uploadedResult = move_uploaded_file($source, $destination);
		} else {
			$uploaded = FALSE;
			@copy($source,$destination);
		}

			// Setting file system mode of file:
		if (@is_file($destination) && TYPO3_OS!='WIN')	{
			chmod($destination, octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask']));
		}

			// If here the file is copied and the temporary $source is still around, so when returning false the user can try unlink to delete the $source
		return $uploaded ? $uploadedResult : FALSE;
	}

	/**
	 * Will move an uploaded file (normally in "/tmp/xxxxx") to a temporary filename in PATH_site."typo3temp/" from where TYPO3 can use it under safe_mode.
	 * Use this function to move uploaded files to where you can work on them.
	 * REMEMBER to use t3lib_div::unlink_tempfile() afterwards - otherwise temp-files will build up! They are NOT automatically deleted in PATH_site."typo3temp/"!
	 *
	 * @param	string		The temporary uploaded filename, eg. $GLOBALS['HTTP_POST_FILES']['[upload field name here]']['tmp_name']
	 * @return	string		If a new file was successfully created, return its filename, otherwise blank string.
	 * @see unlink_tempfile(), upload_copy_move()
	 */
	function upload_to_tempfile($uploadedFileName)	{
		if (is_uploaded_file($uploadedFileName))	{
			$tempFile = t3lib_div::tempnam('upload_temp_');
			move_uploaded_file($uploadedFileName, $tempFile);
			return @is_file($tempFile) ? $tempFile : '';
		}
	}

	/**
	 * Deletes (unlink) a temporary filename in 'PATH_site."typo3temp/"' given as input.
	 * The function will check that the file exists, is in PATH_site."typo3temp/" and does not contain back-spaces ("../") so it should be pretty safe.
	 * Use this after upload_to_tempfile() or tempnam() from this class!
	 *
	 * @param	string		Filepath for a file in PATH_site."typo3temp/". Must be absolute.
	 * @return	boolean		Returns true if the file was unlink()'ed
	 * @see upload_to_tempfile(), tempnam()
	 */
	function unlink_tempfile($uploadedTempFileName)	{
		if ($uploadedTempFileName && t3lib_div::validPathStr($uploadedTempFileName) && t3lib_div::isFirstPartOfStr($uploadedTempFileName,PATH_site.'typo3temp/') && @is_file($uploadedTempFileName))	{
			if (unlink($uploadedTempFileName))	return TRUE;
		}
	}

	/**
	 * Create temporary filename (Create file with unique file name)
	 * This function should be used for getting temporary filenames - will make your applications safe for open_basedir = on
	 * REMEMBER to delete the temporary files after use! This is done by t3lib_div::unlink_tempfile()
	 *
	 * @param	string		Prefix to temp file (which will have no extension btw)
	 * @return	string		result from PHP function tempnam() with PATH_site.'typo3temp/' set for temp path.
	 * @see unlink_tempfile()
	 */
	function tempnam($filePrefix)	{
		return tempnam(PATH_site.'typo3temp/',$filePrefix);
	}

	/**
	 * standard authentication code - can't remember what it's used for.
	 * Usage: 2
	 *
	 * @param	mixed		Uid (integer) or record (array)
	 * @param	string		List of fields from the record if that is given.
	 * @return	string		MD5 hash of 8 chars.
	 * @internal
	 */
	function stdAuthCode($uid_or_record,$fields='')	{
		if (is_array($uid_or_record))	{
			$recCopy_temp=array();
			if ($fields)	{
				$fieldArr = t3lib_div::trimExplode(',',$fields,1);
				reset($fieldArr);
				while(list($k,$v)=each($fieldArr))	{
					$recCopy_temp[$k]=$recCopy[$v];
				}
			} else {
				$recCopy_temp=$recCopy;
			}
			$preKey = implode('|',$recCopy_temp);
		} else {
			$preKey = $uid_or_record;
		}

		$authCode = $preKey.'||'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
		$authCode = substr(md5($authCode),0,8);
		return $authCode;
	}

	/**
	 * Loads the $TCA (Table Configuration Array) for the $table
	 *
	 * Requirements:
	 * 1) must be configured table (the ctrl-section configured),
	 * 2) columns must not be an array (which it is always if whole table loaded), and
	 * 3) there is a value for dynamicConfigFile (filename in typo3conf)
	 * Usage: 92
	 *
	 * @param	string		Table name for which to load the full TCA array part into the global $TCA
	 * @return	void
	 */
	function loadTCA($table)	{
		global $TCA,$LANG_GENERAL_LABELS;
		if (isset($TCA[$table]) && !is_array($TCA[$table]['columns']) && $TCA[$table]['ctrl']['dynamicConfigFile'])	{
			if (!strcmp(substr($TCA[$table]['ctrl']['dynamicConfigFile'],0,6),'T3LIB:'))	{
				include(PATH_t3lib.'stddb/'.substr($TCA[$table]['ctrl']['dynamicConfigFile'],6));
			} elseif (t3lib_div::isAbsPath($TCA[$table]['ctrl']['dynamicConfigFile']) && @is_file($TCA[$table]['ctrl']['dynamicConfigFile']))	{	// Absolute path...
				include($TCA[$table]['ctrl']['dynamicConfigFile']);
			} else include(PATH_typo3conf.$TCA[$table]['ctrl']['dynamicConfigFile']);
		}
	}

	/**
	 * Looks for a sheet-definition in the input data structure array. If found it will return the data structure for the sheet given as $sheet (if found).
	 * If the sheet definition is in an external file that file is parsed and the data structure inside of that is returned.
	 *
	 * @param	array		Input data structure, possibly with a sheet-definition and references to external data source files.
	 * @param	string		The sheet to return, preferably.
	 * @return	array		An array with two num. keys: key0: The data structure is returned in this key (array) UNLESS an error happend in which case an error string is returned (string). key1: The used sheet key value!
	 */
	function resolveSheetDefInDS($dataStructArray,$sheet='sDEF')	{
		if (is_array($dataStructArray['sheets']))	{
			if (!isset($dataStructArray['sheets'][$sheet]))	{
				$sheet='sDEF';
			}
			$dataStruct =  $dataStructArray['sheets'][$sheet];

				// If not an array, but still set, then regard it as a relative reference to a file:
			if ($dataStruct && !is_array($dataStruct))	{
				$file = t3lib_div::getFileAbsFileName($dataStruct);
				if ($file && @is_file($file))	{
					$dataStruct = t3lib_div::xml2array(t3lib_div::getUrl($file));
				}
			}
		} else {
			$dataStruct = $dataStructArray;
			$sheet = 'sDEF';	// Default sheet
		}
		return array($dataStruct,$sheet);
	}

	/**
	 * Resolves ALL sheet definitions in dataStructArray
	 * If no sheet is found, then the default "sDEF" will be created with the dataStructure inside.
	 *
	 * @param	array		Input data structure, possibly with a sheet-definition and references to external data source files.
	 * @return	array		Output data structure with all sheets resolved as arrays.
	 */
	function resolveAllSheetsInDS($dataStructArray)	{
		if (is_array($dataStructArray['sheets']))	{
			$out=array('sheets'=>array());
			foreach($dataStructArray['sheets'] as $sheetId => $sDat)	{
				list($ds,$aS) = t3lib_div::resolveSheetDefInDS($dataStructArray,$sheetId);
				if ($sheetId==$aS)	{
					$out['sheets'][$aS]=$ds;
				}
			}
		} else {
			list($ds) = t3lib_div::resolveSheetDefInDS($dataStructArray);
			$out = array('sheets' => array('sDEF' => $ds));
		}
		return $out;
	}

	/**
	 * Calls a userdefined function/method in class
	 * Such a function/method should look like this: "function proc(&$params, &$ref)	{...}"
	 *
	 * Usage: 3
	 *
	 * @param	string		Function/Method reference, '[file-reference":"]["&"]class/function["->"method-name]'. You can prefix this reference with "[file-reference]:" and t3lib_div::getFileAbsFileName() will then be used to resolve the filename and subsequently include it by "require_once()" which means you don't have to worry about including the class file either! Example: "EXT:realurl/class.tx_realurl.php:&tx_realurl->encodeSpURL". Finally; you can prefix the class name with "&" if you want to reuse a former instance of the same object call.
	 * @param	mixed		Parameters to be pass along (typically an array) (REFERENCE!)
	 * @param	mixed		Reference to be passed along (typically "$this" - being a reference to the calling object) (REFERENCE!)
	 * @param	string		Required prefix of class or function name
	 * @param	boolean		If set, no debug() error message is shown if class/function is not present.
	 * @return	mixed		Content from method/function call
	 * @see getUserObj()
	 */
	function callUserFunction($funcName,&$params,&$ref,$checkPrefix='user_',$silent=0)	{

			// Check persistent object and if found, call directly and exit.
		if (is_array($GLOBALS['T3_VAR']['callUserFunction'][$funcName]))	{
			return call_user_method(
						$GLOBALS['T3_VAR']['callUserFunction'][$funcName]['method'],
						$GLOBALS['T3_VAR']['callUserFunction'][$funcName]['obj'],
						$params,
						$ref
					);
		}

			// Check file-reference prefix; if found, require_once() the file (should be library of code)
		if (strstr($funcName,':'))	{
			list($file,$funcRef) = t3lib_div::revExplode(':',$funcName,2);
			$requireFile = t3lib_div::getFileAbsFileName($file);
			if ($requireFile) require_once($requireFile);
		} else {
			$funcRef = $funcName;
		}

			// Check for persistent object token, "&"
		if (substr($funcRef,0,1)=='&')	{
			$funcRef = substr($funcRef,1);
			$storePersistentObject = TRUE;
		} else {
			$storePersistentObject = FALSE;
		}

			// Check prefix is valid:
		if ($checkPrefix &&
			!t3lib_div::isFirstPartOfStr(trim($funcRef),$checkPrefix) &&
			!t3lib_div::isFirstPartOfStr(trim($funcRef),'tx_')
			)	{
			if (!$silent)	debug("Function/Class '".$funcRef."' was not prepended with '".$checkPrefix."'",1);
			return FALSE;
		}

			// Call function or method:
		$parts = explode('->',$funcRef);
		if (count($parts)==2)	{	// Class

				// Check if class/method exists:
			if (class_exists($parts[0]))	{

					// Get/Create object of class:
				if ($storePersistentObject)	{	// Get reference to current instance of class:
					if (!is_object($GLOBALS['T3_VAR']['callUserFunction_classPool'][$parts[0]]))	{
						$GLOBALS['T3_VAR']['callUserFunction_classPool'][$parts[0]] = &t3lib_div::makeInstance($parts[0]);
					}
					$classObj = &$GLOBALS['T3_VAR']['callUserFunction_classPool'][$parts[0]];
				} else {	// Create new object:
					$classObj = &t3lib_div::makeInstance($parts[0]);
				}

				if (method_exists($classObj, $parts[1]))	{

						// If persistent object should be created, set reference:
					if ($storePersistentObject)	{
						$GLOBALS['T3_VAR']['callUserFunction'][$funcName] = array (
							'method' => $parts[1],
							'obj' => &$classObj
						);
					}
						// Call method:
					$content = call_user_method(
						$parts[1],
						$classObj,
						$params,
						$ref
					);
				} else {
					if (!$silent)	debug("<strong>ERROR:</strong> No method name '".$parts[1]."' in class ".$parts[0],1);
				}
			} else {
				if (!$silent)	debug("<strong>ERROR:</strong> No class named: ".$parts[0],1);
			}
		} else {	// Function
			if (function_exists($funcRef))	{
			 	$content = call_user_func($funcRef, $params, $ref);
			} else {
				if (!$silent)	debug("<strong>ERROR:</strong> No function named: ".$funcRef,1);
			}
		}
		return $content;
	}






	/**
	 * Creates and returns reference to a user defined object.
	 * This function can return an object reference if you like. Just prefix the function call with "&": "$objRef = &t3lib_div::getUserObj('EXT:myext/class.tx_myext_myclass.php:&tx_myext_myclass');". This will work ONLY if you prefix the class name with "&" as well. See description of function arguments.
	 *
	 * @param	string		Class reference, '[file-reference":"]["&"]class-name'. You can prefix the class name with "[file-reference]:" and t3lib_div::getFileAbsFileName() will then be used to resolve the filename and subsequently include it by "require_once()" which means you don't have to worry about including the class file either! Example: "EXT:realurl/class.tx_realurl.php:&tx_realurl". Finally; for the class name you can prefix it with "&" and you will reuse the previous instance of the object identified by the full reference string (meaning; if you ask for the same $classRef later in another place in the code you will get a reference to the first created one!).
	 * @param	string		Required prefix of class name. By default "tx_" is allowed.
	 * @param	boolean		If set, no debug() error message is shown if class/function is not present.
	 * @return	object		The instance of the class asked for. Instance is created with t3lib_div::makeInstance
	 * @see callUserFunction()
	 */
	function &getUserObj($classRef,$checkPrefix='user_',$silent=0)	{

			// Check persistent object and if found, call directly and exit.
		if (is_object($GLOBALS['T3_VAR']['getUserObj'][$classRef]))	{
			return $GLOBALS['T3_VAR']['getUserObj'][$classRef];
		} else {

				// Check file-reference prefix; if found, require_once() the file (should be library of code)
			if (strstr($classRef,':'))	{
				list($file,$class) = t3lib_div::revExplode(':',$classRef,2);
				$requireFile = t3lib_div::getFileAbsFileName($file);
				if ($requireFile)	require_once($requireFile);
			} else {
				$class = $classRef;
			}

				// Check for persistent object token, "&"
			if (substr($class,0,1)=='&')	{
				$class = substr($class,1);
				$storePersistentObject = TRUE;
			} else {
				$storePersistentObject = FALSE;
			}

				// Check prefix is valid:
			if ($checkPrefix &&
				!t3lib_div::isFirstPartOfStr(trim($class),$checkPrefix) &&
				!t3lib_div::isFirstPartOfStr(trim($class),'tx_')
				)	{
				if (!$silent)	debug("Class '".$class."' was not prepended with '".$checkPrefix."'",1);
				return FALSE;
			}

				// Check if class exists:
			if (class_exists($class))	{
				$classObj = &t3lib_div::makeInstance($class);

					// If persistent object should be created, set reference:
				if ($storePersistentObject)	{
					$GLOBALS['T3_VAR']['getUserObj'][$classRef] = &$classObj;
				}

				return $classObj;
			} else {
				if (!$silent)	debug("<strong>ERROR:</strong> No class named: ".$class,1);
			}
		}
	}

	/**
	 * Make instance of class
	 * Takes the class-extensions API of TYPO3 into account
	 * Please USE THIS instead of the PHP "new" keyword. Eg. "$obj = new myclass;" should be "$obj = t3lib_div::makeInstance("myclass")" instead!
	 *
	 * Usage: 455
	 *
	 * @param	string		Class name to instantiate
	 * @return	object		The object
	 */
	function &makeInstance($className)	{
		return class_exists('ux_'.$className) ? t3lib_div::makeInstance('ux_'.$className) : new $className;
	}

	/**
	 * Return classname for new instance
	 * Takes the class-extensions API of TYPO3 into account
	 *
	 * Usage: 18
	 *
	 * @param	string		Base Class name to evaluate
	 * @return	string		Final class name to instantiate with "new [classname]"
	 */
	function makeInstanceClassName($className)	{
		return class_exists('ux_'.$className) ? t3lib_div::makeInstanceClassName('ux_'.$className) : $className;
	}

	/**
	 * Find the best service and check if it works.
	 * Returns object of the service class.
	 *
	 * @param	string		Type of service (service key).
	 * @param	string		Sub type like file extensions or similar. Defined by the service.
	 * @param	string		List of service keys which should be exluded in the search for a service.
	 * @return	object		The service object or an array with error info's.
	 * @author	René Fritz <r.fritz@colorcube.de>
	 */
	function makeInstanceService($serviceType, $serviceSubType='', $excludeServiceKeys='')	{
		global $T3_SERVICES;

		$error = FALSE;

		while ($info = t3lib_extMgm::findService($serviceType, $serviceSubType, $excludeServiceKeys))	{

			if (@is_file($info['classFile'])) {
				require_once ($info['classFile']);
				$obj = t3lib_div::makeInstance($info['className']);
				if (is_object($obj)) {
					if(!@is_callable(array($obj,'init')))	{
// use silent logging???
						die ('Broken service:'.t3lib_div::view_array($info));
					}
					if ($obj->init()) { // service available?

						$obj->info = $info;
						return $obj; // objects are passed always as reference - right?
					}
					$error = $obj->getLastErrorArray();
					unset($obj);
				}
			}
				// deactivate the service
			t3lib_extMgm::deactivateService($info['serviceType'],$info['serviceKey']);
		}
		return $error;
	}

	/**
	 * Simple substitute for the PHP function mail().
	 * The fifth parameter, $enc, will allow you to specify 'base64' encryption for the output (set $enc=base64)
	 * Further the output has the charset set to ISO-8859-1 by default.
	 *
	 * Usage: 4
	 *
	 * @param	string		Email address to send to. (see PHP function mail())
	 * @param	string		Subject line, non-encoded. (see PHP function mail())
	 * @param	string		Message content, non-encoded. (see PHP function mail())
	 * @param	string		Headers, separated by chr(10)
	 * @param	string		Encoding type: "base64", "quoted-printable", "8bit". If blank, no encoding will be used, no encoding headers set.
	 * @param	string		Charset used in encoding-headers (only if $enc is set to a valid value which produces such a header)
	 * @param	boolean		If set, the content of $subject will not be encoded.
	 * @return	void
	 */
	function plainMailEncoded($email,$subject,$message,$headers='',$enc='',$charset='ISO-8859-1',$dontEncodeSubject=0)	{
		switch((string)$enc)	{
			case 'base64':
				$headers=trim($headers).chr(10).
				'Mime-Version: 1.0'.chr(10).
				'Content-Type: text/plain; charset="'.$charset.'"'.chr(10).
				'Content-Transfer-Encoding: base64';

				$message=trim(chunk_split(base64_encode($message.chr(10)))).chr(10);	// Adding chr(10) because I think MS outlook 2002 wants it... may be removed later again.

				if (!$dontEncodeSubject)	$subject='=?'.$charset.'?B?'.base64_encode($subject).'?=';
			break;
			case 'quoted-printable':
				$headers=trim($headers).chr(10).
				'Mime-Version: 1.0'.chr(10).
				'Content-Type: text/plain; charset="'.$charset.'"'.chr(10).
				'Content-Transfer-Encoding: quoted-printable';

				$message=t3lib_div::quoted_printable($message);

				if (!$dontEncodeSubject)	$subject='=?'.$charset.'?Q?'.trim(t3lib_div::quoted_printable(ereg_replace('[[:space:]]','_',$subject),1000)).'?=';
			break;
			case '8bit':
				$headers=trim($headers).chr(10).
				'Mime-Version: 1.0'.chr(10).
				'Content-Type: text/plain; charset="'.$charset.'"'.chr(10).
				'Content-Transfer-Encoding: 8bit';
			break;
		}
		$headers=trim(implode(chr(10),t3lib_div::trimExplode(chr(10),$headers,1)));	// make sure no empty lines are there.
#debug(array($email,$subject,$message,$headers));

		mail($email,$subject,$message,$headers);
	}

	/**
	 * Implementation of quoted-printable encode.
	 * This functions is buggy. It seems that in the part where the lines are breaked every 76th character, that it fails if the break happens right in a quoted_printable encode character!
	 * (Originally taken from class.t3lib_htmlmail.php - which may be updated if this function should ever be improved!
	 * See RFC 1521, section 5.1 Quoted-Printable Content-Transfer-Encoding
	 *
	 * Usage: 2
	 *
	 * @param	string		Content to encode
	 * @param	integer		Length of the lines, default is 76
	 * @return	string		The QP encoded string
	 */
	function quoted_printable($string,$maxlen=76)	{
		$newString = '';
		$theLines = explode(chr(10),$string);	// Break lines. Doesn't work with mac eol's which seems to be 13. But 13-10 or 10 will work
		while (list(,$val)=each($theLines))	{
			$val = ereg_replace(chr(13).'$','',$val);		// removes possible character 13 at the end of line

			$newVal = '';
			$theValLen = strlen($val);
			$len = 0;
			for ($index=0;$index<$theValLen;$index++)	{
				$char = substr($val,$index,1);
				$ordVal =Ord($char);
				if ($len>($maxlen-4) || ($len>(($maxlen-10)-4)&&$ordVal==32))	{
					$len=0;
					$newVal.='='.chr(13).chr(10);
				}
				if (($ordVal>=33 && $ordVal<=60) || ($ordVal>=62 && $ordVal<=126) || $ordVal==9 || $ordVal==32)	{
					$newVal.=$char;
					$len++;
				} else {
					$newVal.=sprintf('=%02X',$ordVal);
					$len+=3;
				}
			}
			$newVal = ereg_replace(chr(32).'$','=20',$newVal);		// replaces a possible SPACE-character at the end of a line
			$newVal = ereg_replace(chr(9).'$','=09',$newVal);		// replaces a possible TAB-character at the end of a line
			$newString.=$newVal.chr(13).chr(10);
		}
		return ereg_replace(chr(13).chr(10).'$','',$newString);
	}

	/**
	 * Takes a clear-text message body for a plain text email, finds all 'http://' links and if they are longer than 76 chars they are converted to a shorter URL with a hash parameter. The real parameter is stored in the database and the hash-parameter/URL will be redirected to the real parameter when the link is clicked.
	 * This function is about preserving long links in messages.
	 *
	 * Usage: 3
	 *
	 * @param	string		Message content
	 * @param	string		URL mode; "76" or "all"
	 * @param	string		URL of index script (see makeRedirectUrl())
	 * @return	string		Processed message content
	 * @see makeRedirectUrl()
	 */
	function substUrlsInPlainText($message,$urlmode='76',$index_script_url='')	{
			// Substitute URLs with shorter links:
		$urlSplit=explode('http://',$message);
		reset($urlSplit);
		while(list($c,$v)=each($urlSplit))	{
			if ($c)	{
				$newParts = split('[[:space:]]|\)|\(',$v,2);
				$newURL='http://'.$newParts[0];
					switch((string)$urlmode)	{
						case 'all':
							$newURL=t3lib_div::makeRedirectUrl($newURL,0,$index_script_url);
						break;
						case '76':
							$newURL=t3lib_div::makeRedirectUrl($newURL,76,$index_script_url);
						break;
					}
				$urlSplit[$c]=$newURL.substr($v,strlen($newParts[0]));
			}
		}

		$message=implode('',$urlSplit);
		return $message;
	}

	/**
	 * Subfunction for substUrlsInPlainText() above.
	 *
	 * Usage: 2
	 *
	 * @param	string		Input URL
	 * @param	integer		URL string length limit
	 * @param	string		URL of "index script" - the prefix of the "?RDCT=..." parameter. If not supplyed it will default to t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR')
	 * @return	string		Processed URL
	 * @internal
	 */
	function makeRedirectUrl($inUrl,$l=0,$index_script_url='')	{
		if (strlen($inUrl)>$l)	{
			$md5 = substr(md5($inUrl),0,20);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('md5hash', 'cache_md5params', 'md5hash="'.$GLOBALS['TYPO3_DB']->quoteStr($md5, 'cache_md5params').'"');
			if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				$insertFields = array(
					'md5hash' => $md5,
					'tstamp' => time(),
					'type' => 2,
					'params' => $inUrl
				);

				$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_md5params', $insertFields);
			}
			$inUrl=($index_script_url ? $index_script_url : t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR')).
				'?RDCT='.$md5;
		}
		return $inUrl;
	}

	/**
	 * Function to compensate for FreeType2 96 dpi
	 *
	 * Usage: 16
	 *
	 * @param	integer		Fontsize for freetype function call
	 * @return	integer		Compensated fontsize based on $GLOBALS['TYPO3_CONF_VARS']['GFX']['TTFdpi']
	 */
	function freetypeDpiComp($font_size)	{
		$dpi = intval($GLOBALS['TYPO3_CONF_VARS']['GFX']['TTFdpi']);
		if ($dpi!=72)	$font_size = $font_size/$dpi*72;
		return $font_size;
	}

	/**
	 * Developer log; This should be implemented around the source code, both frontend and backend, logging everything from the flow through an application, messages, results from comparisons to fatal errors.
	 * The result is meant to make sense to developers during development or debugging of a site.
	 * The idea is that this function is only a wrapper for external extensions which can set a hook which will be allowed to handle the logging of the information to any format they might wish and with any kind of filter they would like.
	 * If you want to implement the devLog in your applications, simply add lines like:
	 * 		if (TYPO3_DLOG)	t3lib_div::devLog('[write message in english here]', 'extension key');
	 *
	 * @param	string		Message (in english).
	 * @param	string		Extension key (from which extension you are calling the log)
	 * @param	integer		Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
	 * @param	array		Additional data you want to pass to the logger.
	 * @return	void
	 */
	function devLog($msg, $extKey, $severity=0, $dataVar=FALSE)	{
		global $TYPO3_CONF_VARS;

		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog']))	{
			$params = array('msg'=>$msg, 'extKey'=>$extKey, 'severity'=>$severity, 'dataVar'=>$dataVar);
			$fakeThis = FALSE;
			foreach($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog'] as $hookMethod)	{
				t3lib_div::callUserFunction($hookMethod,$params,$fakeThis);
			}
		}
	}
}

?>