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
 * Functions for parsing HTML, specially for TYPO3 processing in relation to TCEmain and Rich Text Editor (RTE)
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @internal
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 * Generally NOT XHTML compatible. Must be revised for XHTML compatibility later.
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   97: class t3lib_parsehtml_proc extends t3lib_parsehtml 
 *  120:     function init($elRef='',$recPid=0)	
 *  131:     function setRelPath($path)	
 *  152:     function getURL($url)	
 *  170:     function evalWriteFile($pArr,$currentRecord)	
 *
 *              SECTION: TRANSFORMATION functions for specific TYPO3 use
 *  235:     function HTMLcleaner_db($content,$tagList='')	
 *  257:     function divideIntoLines($value,$count=5,$returnArray=0)	
 *  341:     function internalizeFontTags($value)	
 *  378:     function setDivTags($value,$dT='P')	
 *  405:     function siteUrl()	
 *  416:     function removeTables($value,$breakChar='<BR>')	
 *  445:     function defaultTStagMapping($code,$direction='rte')	
 *  468:     function getWHFromAttribs($attribArray)	
 *  494:     function urlInfoForLinkTags($url)	
 *  551:     function TS_images_db($value)	
 *  644:     function TS_images_rte($value)	
 *  669:     function TS_reglinks($value,$direction)		
 *  701:     function TS_links_db($value)	
 *  742:     function TS_AtagToAbs($value,$dontSetRTEKEEP=0)	
 *  769:     function TS_links_rte($value)	
 *  842:     function TS_preserve_db($value)	
 *  866:     function TS_preserve_rte($value)	
 *  886:     function TS_strip_db($value)	
 *  896:     function getKeepTags($direction='rte',$tagList='')	
 *  976:     function TS_transform_db($value,$css=0)	
 * 1064:     function TS_transform_rte($value,$css=0)	
 * 1136:     function TS_tmplParts_rte($value)	
 * 1179:     function RTE_transform($value,$specConf,$direction='rte',$thisConfig='')	
 * 1291:     function rteImageStorageDir()	
 *
 * TOTAL FUNCTIONS: 28
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once (PATH_t3lib.'class.t3lib_parsehtml.php');












/**
 * .
 * 
 */
class t3lib_parsehtml_proc extends t3lib_parsehtml {
	var $recPid = 0;		// Set this to the pid of the record manipulated by the class.
	var $elRef = '';
	var $procOptions = '';
	var $headListTags = 'PRE,UL,OL,H1,H2,H3,H4,H5,H6';
	var $preserveTags = '';

	var $relPath='';
	var $relBackPath='';
	var $getKeepTags_cache=array();
	var $allowedClasses=array();
	
		// Internal
	var $TS_transform_db_safecounter=100;
	var $rte_p='';

	/**
	 * Initialize, setting element reference and record PID
	 * 
	 * @param	[type]		$elRef: ...
	 * @param	[type]		$recPid: ...
	 * @return	[type]		...
	 */
	function init($elRef='',$recPid=0)	{
		$this->recPid=$recPid;
		$this->elRef=$elRef;
	}

	/**
	 * Setting the ->relPath and ->relBackPath to proper values so absolute references to links and images can be converted to relative dittos.
	 * 
	 * @param	[type]		$path: ...
	 * @return	[type]		...
	 */
	function setRelPath($path)	{
		$path = trim($path);
		$path = ereg_replace('^/','',$path);
		$path = ereg_replace('/$','',$path);
		if ($path)	{
			$this->relPath = $path;
			$this->relBackPath = '';
			$partsC=count(explode('/',$this->relPath));
			for ($a=0;$a<$partsC;$a++)	{
				$this->relBackPath.='../';
			}
			$this->relPath.='/';
		}
	}

	/**
	 * Reads the file or url $url and returns the content
	 * 
	 * @param	[type]		$url: ...
	 * @return	[type]		...
	 */
	function getURL($url)	{
		if($fd = @fopen($url,'rb'))	{
			$content = '';
			while (!feof($fd))	{
				$content.=fread($fd, 5000);
			}
			fclose($fd);
			return $content;
		}
	}

	/**
	 * Evaluate the environment for editing a staticFileEdit file.
	 * 
	 * @param	[type]		$pArr: ...
	 * @param	[type]		$currentRecord: ...
	 * @return	mixed		On success an array with various information is returned, otherwise a string with an error message
	 */
	function evalWriteFile($pArr,$currentRecord)	{
			// Write file configuration:
		if (is_array($pArr))	{
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['staticFileEditPath'] 
				&& substr($GLOBALS['TYPO3_CONF_VARS']['BE']['staticFileEditPath'],-1)=='/' 
				&& @is_dir(PATH_site.$GLOBALS['TYPO3_CONF_VARS']['BE']['staticFileEditPath']))	{
				
				$SW_p = $pArr['parameters'];
				$SW_editFileField = trim($SW_p[0]);
				$SW_editFile = $currentRecord[$SW_editFileField];
				if ($SW_editFileField && $SW_editFile && t3lib_div::validPathStr($SW_editFile))	{
					$SW_relpath = $GLOBALS['TYPO3_CONF_VARS']['BE']['staticFileEditPath'].$SW_editFile;
					$SW_editFile = PATH_site.$SW_relpath;
					if (@is_file($SW_editFile))	{
						return array(
							'editFile' => $SW_editFile,
							'relEditFile' => $SW_relpath,
							'contentField' => trim($SW_p[1]),
							'markerField' => trim($SW_p[2]),
							'loadFromFileField' => trim($SW_p[3]),
							'statusField' => trim($SW_p[4])
						);
					} else return "ERROR: Editfile '".$SW_relpath."' did not exist";
				} else return "ERROR: Edit file name could not be found or was bad.";
			} else return "ERROR: staticFileEditPath was not set, not set correctly or did not exist!";
		}
	}




	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/************************************
	 *
	 * TRANSFORMATION functions for specific TYPO3 use
	 *
	 *************************************/

	/**
	 * Function for cleaning content going to the database.
	 * 
	 * @param	[type]		$content: ...
	 * @param	[type]		$tagList: ...
	 * @return	[type]		...
	 */
	 function HTMLcleaner_db($content,$tagList='')	{
	 	if (!$tagList)	{
			$keepTags = $this->getKeepTags('db');
		} else {
			$keepTags = $this->getKeepTags('db',$tagList);
		}
		$kUknown = $this->procOptions['dontRemoveUnknownTags_db'] ? 1 : 0;	// Default: remove unknown tags.
		$hSC = $this->procOptions['dontUndoHSC_db'] ? 0 : -1;	// Default: re-convert literals to characters (that is &lt; to <)
		
	 	return $this->HTMLcleaner($content,$keepTags,$kUknown,$hSC);
	 }
	 
	/**
	 * This resolves the $value into parts based on <div></div>-sections and <P>-sections and <BR>-tags. These are returned as lines separated by chr(10).
	 * This point is to resolve the HTML-code returned from RTE into ordinary lines so it's 'human-readable'
	 * The function ->setDivTags does the opposite.
	 * 
	 * @param	[type]		$value: ...
	 * @param	[type]		$count: ...
	 * @param	[type]		$returnArray: ...
	 * @return	[type]		...
	 */
	function divideIntoLines($value,$count=5,$returnArray=0)	{
		if ($this->procOptions['internalizeFontTags'])	{$value = $this->internalizeFontTags($value);}
		$allowTagsOutside = t3lib_div::trimExplode(',',strtolower($this->procOptions['allowTagsOutside']?$this->procOptions['allowTagsOutside']:'img'),1);
		$remapParagraphTag = strtoupper($this->procOptions['remapParagraphTag']);
		$divSplit = $this->splitIntoBlock('div,p',$value,1);	// Setting the third param to 1 will eliminate false end-tags. Maybe this is a good thing to do...?

		$keepAttribListArr = array();
		if ($this->procOptions['keepPDIVattribs'])	{
			$keepAttribListArr = t3lib_div::trimExplode(',',strtolower($this->procOptions['keepPDIVattribs']),1);
		}
			
			// returns plainly the value if there was no div/p sections in it
		if (count($divSplit)<=1 || $count<=0)	{
			return $value;
		}

		reset($divSplit);
		while(list($k,$v)=each($divSplit))	{
			if ($k%2)	{	// Inside
				$v=$this->removeFirstAndLastTag($v);

					// Fetching 'sub-lines' - which will explode any further p/div nesting...
				$subLines = $this->divideIntoLines($v,$count-1,1);
				if (is_array($subLines))	{	// So, if there happend to be sub-nesting of p/div, this is written directly as the new content of THIS section. (This would be considered 'an error')

				} else {	//... but if NO subsection was found, we process it as a TRUE line without erronous content:
					$subLines=array($subLines);
					if (!$this->procOptions['dontConvBRtoParagraph'])	{	// process break-tags, if configured for. Simply, the breaktags will here be treated like if each was a line of content...
						$subLines = spliti('<BR[[:space:]]*>',$v);
					}
					reset($subLines);
					while(list($sk)=each($subLines))	{
						$subLines[$sk]=$this->HTMLcleaner_db($subLines[$sk]);
						
						$fTag = $this->getFirstTag($divSplit[$k]);
						$tagName=$this->getFirstTagName($divSplit[$k]);
						$attribs=$this->get_tag_attributes($fTag);
						
						$newAttribs=array();
							// Keep attributes
						if (count($keepAttribListArr))	{
							reset($keepAttribListArr);
							while(list(,$keepA)=each($keepAttribListArr))	{
								if (isset($attribs[0][$keepA]))	{$newAttribs[$keepA]=$attribs[0][$keepA];}
							}
						}
							// ALIGN attribute:
						if (!$this->procOptions['skipAlign'] && strcmp($attribs[0]['align'],'') && strtolower($attribs[0]['align'])!='left')	{	// Set to value, but not 'left'
							$newAttribs['align']=$attribs[0]['align'];
						}
							// CLASS attribute:
						if (!$this->procOptions['skipClass'] && strcmp($attribs[0]['class'],''))	{	// Set to whatever value
							if (!count($this->allowedClasses) || in_array(strtoupper($attribs[0]['class']),$this->allowedClasses))	{
								$newAttribs['class']=$attribs[0]['class'];
							}
						}
						$subLines[$sk]=ereg_replace(chr(10).'|'.chr(13),'',$subLines[$sk]);
						if (count($newAttribs) && strcmp($remapParagraphTag,'1'))		{
							if ($remapParagraphTag=='P')	$tagName='P';
							if ($remapParagraphTag=='DIV')	$tagName='DIV';
							$subLines[$sk]='<'.trim($tagName.' '.$this->compileTagAttribs($newAttribs)).'>'.$subLines[$sk].'</'.$tagName.'>';
						}
					}
				}
				$divSplit[$k] = implode(chr(10),$subLines);
				if (trim(strip_tags($divSplit[$k]))=='&nbsp;')		$divSplit[$k]='';
			} else {	// outside div:
					// Remove positions which are outside div/p tags and without content
				$divSplit[$k]=trim(strip_tags($divSplit[$k],'<'.implode('><',$allowTagsOutside).'>'));
				if (!$divSplit[$k])	unset($divSplit[$k]);	// Remove part if it's 
			}
		}
		return $returnArray ? $divSplit : implode(chr(10),$divSplit);
	}

	/**
	 * This splits the $value in font-tag chunks. 
	 * If there are any <P>/<DIV> sections inside of them, the font-tag is wrapped AROUND the content INSIDE of the P/DIV sections and the outer font-tag is removed.
	 * This functions seems to be a good choice for pre-processing content if it has been pasted into the RTE from eg. star-office. 
	 * In that case the font-tags is normally on the OUTSIDE of the sections.
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function internalizeFontTags($value)	{
		$fontSplit = $this->splitIntoBlock('font',$value);

		reset($fontSplit);
		while(list($k,$v)=each($fontSplit))	{
			if ($k%2)	{	// Inside
				$fTag = $this->getFirstTag($v);	// Fint font-tag
				
				$divSplit_sub = $this->splitIntoBlock('div,p',$this->removeFirstAndLastTag($v),1);
				if (count($divSplit_sub)>1)	{	// If there were div/p sections inside the font-tag, do something about it...
						// traverse those sections:
					reset($divSplit_sub);
					while(list($k2,$v2)=each($divSplit_sub))	{
						if ($k2%2)	{	// Inside
							$div_p = $this->getFirstTag($v2);	// Fint font-tag
							$div_p_tagname = $this->getFirstTagName($v2);	// Fint font-tag
							$v2=$this->removeFirstAndLastTag($v2); // ... and remove it from original.
							$divSplit_sub[$k2]=$div_p.$fTag.$v2.'</FONT>'.'</'.$div_p_tagname.'>';
						} elseif (trim(strip_tags($v2))) {
							$divSplit_sub[$k2]=$fTag.$v2.'</FONT>';
						}
					}
					$fontSplit[$k]=implode('',$divSplit_sub);
				}
			}
		}

		return implode('',$fontSplit);
	}
	
	/**
	 * Converts all lines into <div></div>-sections (unless the line is a div-section already
	 * 
	 * @param	[type]		$value: ...
	 * @param	[type]		$dT: ...
	 * @return	[type]		...
	 */
	function setDivTags($value,$dT='P')	{
		$keepTags = $this->getKeepTags('rte');
		$kUknown = $this->procOptions['dontProtectUnknownTags_rte'] ? 0 : 'protect';	// Default: remove unknown tags.
		$hSC = $this->procOptions['dontHSC_rte'] ? 0 : 1;	// Default: re-convert literals to characters (that is &lt; to <)
		$convNBSP = !$this->procOptions['dontConvAmpInNBSP_rte']?1:0;

		$parts = explode(chr(10),$value);
		reset($parts);
		while(list($k,$v)=each($parts))	{
			$parts[$k]=$this->HTMLcleaner($parts[$k],$keepTags,$kUknown,$hSC);
			if ($convNBSP)	$parts[$k]=str_replace('&amp;nbsp;','&nbsp;',$parts[$k]);
			if (!$v)	$parts[$k]='&nbsp;';
			if (substr(strtoupper(trim($parts[$k])),0,4)!='<DIV' || substr(strtoupper(trim($parts[$k])),-6)!='</DIV>')	{
				if (substr(strtoupper(trim($parts[$k])),0,2)!='<P' || substr(strtoupper(trim($parts[$k])),-4)!='</P>')	{
						// Only set p-tags if there is not already div or p tags:
					$parts[$k]='<'.$dT.'>'.(trim($parts[$k])?$parts[$k]:'&nbsp;').'</'.$dT.'>';	
				}
			}
		}
		return implode(chr(10),$parts);
	}

	/**
	 * Returns SiteURL based on thisScript.
	 * 
	 * @return	[type]		...
	 */
	function siteUrl()	{
		return t3lib_div::getIndpEnv('TYPO3_SITE_URL');
	}

	/**
	 * Remove all tables from incoming code
	 * 
	 * @param	[type]		$value: ...
	 * @param	[type]		$breakChar: ...
	 * @return	[type]		...
	 */
	function removeTables($value,$breakChar='<BR>')	{
		$tableSplit = $this->splitIntoBlock('table',$value);
		reset($tableSplit);
		while(list($k,$v)=each($tableSplit))	{
			if ($k%2)	{
				$tableSplit[$k]='';
				$rowSplit = $this->splitIntoBlock('tr',$v);
				reset($rowSplit);
				while(list($k2,$v2)=each($rowSplit))	{
					if ($k2%2)	{
						$cellSplit = $this->getAllParts($this->splitIntoBlock('td',$v2),1,0);
						reset($cellSplit);
						while(list($k3,$v3)=each($cellSplit))	{
							$tableSplit[$k].=$v3.$breakChar;
						}
					}
				}
			}
		}
		return implode($breakChar,$tableSplit);
	}
	
	/**
	 * Default tag mapping for TS
	 * 
	 * @param	[type]		$code: ...
	 * @param	[type]		$direction: ...
	 * @return	[type]		...
	 */
	function defaultTStagMapping($code,$direction='rte')	{
		if ($direction=='db')	{
			$code=$this->mapTags($code,array(	// Map tags
				'STRONG' => 'B',
				'EM' => 'I'
			));
		}
		if ($direction=='rte')	{
			$code=$this->mapTags($code,array(	// Map tags
				'B' => 'STRONG',
				'I' => 'EM'
			));
		}
		return $code;
	}
	
	/**
	 * Finds width and height from attrib-array
	 * If the width and height is found in the style-attribute, use that!
	 * 
	 * @param	[type]		$attribArray: ...
	 * @return	[type]		...
	 */
	function getWHFromAttribs($attribArray)	{
		$style =trim($attribArray['style']);
		if ($style)	{
			$regex='[[:space:]]*:[[:space:]]*([0-9]*)[[:space:]]*px';
				// Width
			eregi('width'.$regex,$style,$reg);
			$w = intval($reg[1]);
				// Height
			eregi('height'.$regex,$style,$reg);
			$h = intval($reg[1]);
		}
		if (!$w)	{
			$w = $attribArray['width'];
		}
		if (!$h)	{
			$h = $attribArray['height'];
		}
		return array(intval($w),intval($h));
	}
	
	/**
	 * Parse <A>-tag href and return status of email,external,file or page
	 * 
	 * @param	[type]		$url: ...
	 * @return	[type]		...
	 */
	function urlInfoForLinkTags($url)	{
		$url = trim($url);
		if (substr(strtolower($url),0,7)=='mailto:')	{
			$info['url']=trim(substr($url,7));
			$info['type']='email';
		} else {
			$curURL = $this->siteUrl(); 	// 100502, removed this: 'http://'.t3lib_div::getThisUrl(); Reason: The url returned had typo3/ in the end - should be only the site's url as far as I see...
			for($a=0;$a<strlen($url);$a++)	{
				if ($url[$a]!=$curURL[$a])	{
					break;
				}
			}
			
			$info['relScriptPath']=substr($curURL,$a);
			$info['relUrl']=substr($url,$a);
			$info['url']=$url;
			$info['type']='ext';

			$siteUrl_parts = parse_url($url);
			$curUrl_parts = parse_url($curURL);
			
			if ($siteUrl_parts['host']==$curUrl_parts['host'] 	// Hosts should match
				&& (!$info['relScriptPath']	|| (defined('TYPO3_mainDir') && substr($info['relScriptPath'],0,strlen(TYPO3_mainDir))==TYPO3_mainDir)))	{	// If the script path seems to match or is empty (FE-EDIT)

					// New processing order 100502
				$uP=parse_url($info['relUrl']);

				if (!strcmp('#'.$siteUrl_parts['fragment'],$info['relUrl'])) {
					$info['url']=$info['relUrl'];
					$info['type']='anchor';
				} elseif (!trim($uP['path']) || !strcmp($uP['path'],'index.php'))	{
					$pp = explode('id=',$uP['query']);
					$id = trim($pp[1]);
					if ($id)	{
						$info['pageid']=$id;
						$info['cElement']=$uP['fragment'];
						$info['url']=$id.($info['cElement']?'#'.$info['cElement']:'');
						$info['type']='page';
					}
				} else {
					$info['url']=$info['relUrl'];
					$info['type']='file';
				}
			} else {
				unset($info['relScriptPath']);
				unset($info['relUrl']);
			}
		}
		return $info;
	}

	/**
	 * Processing images inserted in the RTE.
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function TS_images_db($value)	{
		$imgSplit = $this->splitTags('img',$value);
		reset($imgSplit);
		while(list($k,$v)=each($imgSplit))	{
			if ($k%2)	{	// image:
				$attribArray=$this->get_tag_attributes_classic($v,1);
				$siteUrl = $this->siteUrl();
				$absRef = trim($attribArray['src']);
				
					// External?
				if (!t3lib_div::isFirstPartOfStr($absRef,$siteUrl) && !$this->procOptions['dontFetchExtPictures'])	{
					$externalFile = $this->getUrl($absRef);	// Get it
					if ($externalFile)	{
						$pU = parse_url($absRef);
						$pI=pathinfo($pU['path']);
						
						if (t3lib_div::inList('gif,png,jpeg,jpg',strtolower($pI['extension'])))	{
							$filename = t3lib_div::shortMD5($absRef).'.'.$pI['extension'];
							$origFilePath = PATH_site.$this->rteImageStorageDir().'RTEmagicP_'.$filename;
							$C_origFilePath = PATH_site.$this->rteImageStorageDir().'RTEmagicC_'.$filename.'.'.$pI['extension'];
							if (!@is_file($origFilePath))	{
								t3lib_div::writeFile($origFilePath,$externalFile);
								t3lib_div::writeFile($C_origFilePath,$externalFile);
							}
							$absRef = $siteUrl.$this->rteImageStorageDir().'RTEmagicC_'.$filename.'.'.$pI['extension'];
						
							$attribArray['src']=$absRef;
							if (!isset($attribArray['alt']))	$attribArray['alt']='';
							$params = t3lib_div::implodeParams($attribArray,1);
							$imgSplit[$k]='<img '.$params.' />';
						}
					}
				}
					// Check file
				if (t3lib_div::isFirstPartOfStr($absRef,$siteUrl))	{
					$path = substr($absRef,strlen($siteUrl));
					$pathPre=$this->rteImageStorageDir().'RTEmagicC_';
					
					if (t3lib_div::isFirstPartOfStr($path,$pathPre))	{
						$filepath = PATH_site.$path;
						if (@is_file($filepath))	{
							// Find original file:
							$pI=pathinfo(substr($path,strlen($pathPre)));
							$filename = substr($pI['basename'],0,-strlen('.'.$pI['extension']));
							$origFilePath = PATH_site.$this->rteImageStorageDir().'RTEmagicP_'.$filename;
							if (@is_file($origFilePath))	{
								$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
								$imgObj->init();
								$imgObj->mayScaleUp=0;
								$imgObj->tempPath=PATH_site.$imgObj->tempPath;
							
								$curInfo = $imgObj->getImageDimensions($filepath);	// Image dimensions of the current image
								$curWH = $this->getWHFromAttribs($attribArray);	// Image dimensions as set in the image tag
									// Compare dimensions:
								if ($curWH[0]!=$curInfo[0] || $curWH[1]!=$curInfo[1])	{
									$origImgInfo = $imgObj->getImageDimensions($origFilePath);	// Image dimensions of the current image
									$cW = $curWH[0];
									$cH = $curWH[1];
										$cH = 1000;	// Make the image based on the width solely...
									$imgI = $imgObj->imageMagickConvert($origFilePath,$pI['extension'],$cW.'m',$cH.'m');
									if ($imgI[3])	{
										$fI=pathinfo($imgI[3]);
										@copy($imgI[3],$filepath);	// Override the child file
										unset($attribArray['style']);
										$attribArray['width']=$imgI[0];
										$attribArray['height']=$imgI[1];
										if (!$attribArray['border'])	$attribArray['border']=0;
										if (!isset($attribArray['alt']))	$attribArray['alt']='';
										$params = t3lib_div::implodeParams($attribArray,1);
										$imgSplit[$k]='<img '.$params.' />';
									}
								}
							}
						}
					}
				}

					// Convert abs to rel url
				$attribArray=$this->get_tag_attributes_classic($imgSplit[$k],1);
				$absRef = trim($attribArray['src']);
				if (t3lib_div::isFirstPartOfStr($absRef,$siteUrl))	{
					$attribArray['src'] = $this->relBackPath.substr($absRef,strlen($siteUrl));
					if (!isset($attribArray['alt']))	$attribArray['alt']='';
					$imgSplit[$k]='<img '.t3lib_div::implodeParams($attribArray,1).' />';
				}
			}
		}		
		return implode('',$imgSplit);
	}
	
	/**
	 * Processing images inserted in the RTE.
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function TS_images_rte($value)	{
		$imgSplit = $this->splitTags('img',$value);
		reset($imgSplit);
		while(list($k,$v)=each($imgSplit))	{
			if ($k%2)	{	// image:
				$attribArray=$this->get_tag_attributes_classic($v,1);
				$siteUrl = $this->siteUrl();
				$absRef = trim($attribArray['src']);
				if (strtolower(substr($absRef,0,4))!='http')	{
					$attribArray['src'] = $siteUrl.substr($attribArray['src'],strlen($this->relBackPath));
					if (!isset($attribArray['alt']))	$attribArray['alt']='';
					$params = t3lib_div::implodeParams($attribArray);
					$imgSplit[$k]='<img '.$params.' />';
				}
			}
		}		
		return implode('',$imgSplit);
	}
	
	/**
	 * Converting <A>-tags to/from abs/rel
	 * 
	 * @param	[type]		$value: ...
	 * @param	[type]		$direction: ...
	 * @return	[type]		...
	 */
	function TS_reglinks($value,$direction)		{
		switch($direction)	{
			case 'rte':
				return $this->TS_AtagToAbs($value,1);
			break;
			case 'db':
				$siteURL = $this->siteUrl();
				$blockSplit = $this->splitIntoBlock('A',$value);
				reset($blockSplit);
				while(list($k,$v)=each($blockSplit))	{
					if ($k%2)	{	// block:
						$attribArray=$this->get_tag_attributes_classic($this->getFirstTag($v),1);
							// If the url is local, remove url-prefix
						if ($siteURL && substr($attribArray['href'],0,strlen($siteURL))==$siteURL)	{
							$attribArray['href']=$this->relBackPath.substr($attribArray['href'],strlen($siteURL));
						}
						$bTag='<a '.t3lib_div::implodeParams($attribArray,1).'>';
						$eTag='</a>';
						$blockSplit[$k] = $bTag.$this->TS_reglinks($this->removeFirstAndLastTag($blockSplit[$k]),$direction).$eTag;
					}
				}
				return implode('',$blockSplit);
			break;
		}
	}
	
	/**
	 * Converting <A>-tags to <LINK tags>
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function TS_links_db($value)	{
		$blockSplit = $this->splitIntoBlock('A',$value);
		reset($blockSplit);
		while(list($k,$v)=each($blockSplit))	{
			if ($k%2)	{	// block:
				$attribArray=$this->get_tag_attributes_classic($this->getFirstTag($v),1);
				$info = $this->urlInfoForLinkTags($attribArray['href']);
				
					// Check options:
				$attribArray_copy = $attribArray;
				unset($attribArray_copy['href']);
				unset($attribArray_copy['target']);
				unset($attribArray_copy['class']);
				if (!count($attribArray_copy))	{	// Only if href, target and class are the only attributes, we can alter the link!
					$bTag='<LINK '.$info['url'].($attribArray['target']?' '.$attribArray['target']:($attribArray['class']?' -':'')).($attribArray['class']?' '.$attribArray['class']:'').'>';
					$eTag='</LINK>';
					$blockSplit[$k] = $bTag.$this->TS_links_db($this->removeFirstAndLastTag($blockSplit[$k])).$eTag;
				} else {	// ... otherwise store the link as a-tag.
						// Unsetting 'rtekeep' attribute if that had been set.
					unset($attribArray['rtekeep']);
						// If the url is local, remove url-prefix
					$siteURL = $this->siteUrl();
					if ($siteURL && substr($attribArray['href'],0,strlen($siteURL))==$siteURL)	{
						$attribArray['href']=$this->relBackPath.substr($attribArray['href'],strlen($siteURL));
					}
					$bTag='<a '.t3lib_div::implodeParams($attribArray,1).'>';
					$eTag='</a>';
					$blockSplit[$k] = $bTag.$this->TS_links_db($this->removeFirstAndLastTag($blockSplit[$k])).$eTag;
				}
			}
		}
		return implode('',$blockSplit);
	}
	
	/**
	 * Converting <A>-tags to absolute URLs (+ setting rtekeep attribute)
	 * 
	 * @param	[type]		$value: ...
	 * @param	[type]		$dontSetRTEKEEP: ...
	 * @return	[type]		...
	 */
	function TS_AtagToAbs($value,$dontSetRTEKEEP=0)	{
		$blockSplit = $this->splitIntoBlock('A',$value);
		reset($blockSplit);
		while(list($k,$v)=each($blockSplit))	{
			if ($k%2)	{	// block:
				$attribArray=$this->get_tag_attributes_classic($this->getFirstTag($v),1);
					// Checking if there is a scheme, and if not, prepend the current url.
				$uP = parse_url(strtolower($attribArray['href']));
				if (!$uP['scheme'])	{
					$attribArray['href']=$this->siteUrl().substr($attribArray['href'],strlen($this->relBackPath));
				}
				if (!$dontSetRTEKEEP)	$attribArray['rtekeep']=1;

				$bTag='<a '.t3lib_div::implodeParams($attribArray,1).'>';
				$eTag='</a>';
				$blockSplit[$k] = $bTag.$this->TS_AtagToAbs($this->removeFirstAndLastTag($blockSplit[$k])).$eTag;
			}
		}
		return implode('',$blockSplit);
	}
	
	/**
	 * Converting <LINK tags> to <A>-tags
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function TS_links_rte($value)	{
		$value = $this->TS_AtagToAbs($value);
		
		$blockSplit = $this->splitIntoBlock('link',$value,1);
		reset($blockSplit);
		while(list($k,$v)=each($blockSplit))	{
			if ($k%2)	{	// block:
				$tagCode = t3lib_div::trimExplode(' ',trim(substr($this->getFirstTag($v),0,-1)),1);
				$link_param=$tagCode[1];
				$href='';
				$siteUrl = $this->siteUrl();
					// Parsing the typolink data. This parsing is roughly done like in tslib_content->typolink()
				if(strstr($link_param,'@'))	{		// mailadr
					$href = 'mailto:'.eregi_replace('^mailto:','',$link_param);
				} elseif (substr($link_param,0,1)=='#') {	// check if anchor
					$href = $siteUrl.$link_param;
				} else {
					$fileChar=intval(strpos($link_param, '/'));
					$urlChar=intval(strpos($link_param, '.'));

						// Detects if a file is found in site-root OR is a simulateStaticDocument.
					list($rootFileDat) = explode('?',$link_param);
					$rFD_fI = pathinfo($rootFileDat);
					if (trim($rootFileDat) && !strstr($link_param,'/') && (@is_file(PATH_site.$rootFileDat) || t3lib_div::inList('php,html,htm',strtolower($rFD_fI['extension']))))	{
						$href = $siteUrl.$link_param;
					} elseif($urlChar && (strstr($link_param,'//') || !$fileChar || $urlChar<$fileChar))	{	// url (external): If doubleSlash or if a '.' comes before a '/'.
						if (!ereg('^[a-z]*://',trim(strtolower($link_param))))	{$scheme='http://';} else {$scheme='';}
						$href = $scheme.$link_param;
					} elseif($fileChar)	{	// file (internal)
						$href = $siteUrl.$link_param;
					} else {	// integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to tables.php!!)
						$link_params_parts=explode('#',$link_param);
						$idPart = trim($link_params_parts[0]);		// Link-data del
						if (!strcmp($idPart,''))	{$idPart=$this->recPid;}	// If no id or alias is given, set it to class record pid
						if ($link_params_parts[1] && !$sectionMark)	{
							$sectionMark='#'.trim($link_params_parts[1]);
						}
							// Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/? pair
						$pairParts = t3lib_div::trimExplode(',',$idPart);
						if (count($pairParts)>1)	{
							$idPart = $pairParts[0];
							// Type ? future support for?
						}
							// Checking if the id-parameter is an alias.
						if (!t3lib_div::testInt($idPart))	{
							list($idPartR) = t3lib_BEfunc::getRecordsByField('pages','alias',$idPart);
							$idPart=intval($idPartR['uid']);
						}
						$page = t3lib_BEfunc::getRecord('pages',$idPart);
						if (is_array($page))	{	// Page must exist...
							$href = $siteUrl.'?id='.$link_param;
						} else {
							$href='';
							$error='no page: '.$idPart;
						}
					}		
				}

				// Setting the A-tag:
				$bTag='<A href="'.$href.'"'.($tagCode[2]&&$tagCode[2]!="-"?' target="'.$tagCode[2].'"':'').($tagCode[3]?' class="'.$tagCode[3].'"':'').'>';
				$eTag='</A>';
				$blockSplit[$k] = $bTag.$this->TS_links_rte($this->removeFirstAndLastTag($blockSplit[$k])).$eTag;
			}			
		}
		return implode('',$blockSplit);
	}
	
	/**
	 * Preserve special tags
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function TS_preserve_db($value)	{
		if (!$this->preserveTags)	return $value;

		$blockSplit = $this->splitIntoBlock('SPAN',$value);
		reset($blockSplit);
		while(list($k,$v)=each($blockSplit))	{
			if ($k%2)	{	// block:
				$attribArray=$this->get_tag_attributes_classic($this->getFirstTag($v));
				if ($attribArray['specialtag'])	{
					$theTag = rawurldecode($attribArray['specialtag']);
					$theTagName = $this->getFirstTagName($theTag);
					$blockSplit[$k] = $theTag.$this->removeFirstAndLastTag($blockSplit[$k]).'</'.$theTagName.'>';
				}
			}
		}
		return implode('',$blockSplit);
	}
	
	/**
	 * Preserve special tags
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function TS_preserve_rte($value)	{
		if (!$this->preserveTags)	return $value;
		
		$blockSplit = $this->splitIntoBlock($this->preserveTags,$value);
		reset($blockSplit);
		while(list($k,$v)=each($blockSplit))	{
			if ($k%2)	{	// block:
				$blockSplit[$k] = '<SPAN specialtag="'.rawurlencode($this->getFirstTag($v)).'">'.$this->removeFirstAndLastTag($blockSplit[$k]).'</SPAN>';
			}
		}
		return implode('',$blockSplit);
	}
	
	/**
	 * Removing all non-allowed tags
	 * DEPRECIATED!
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function TS_strip_db($value)	{
		$value = $this->stripTagsExcept($value,'b,i,u,a,img,br,div,center,pre,font,hr,sub,sup,p,strong,em,li,ul,ol,blockquote');
		return $value;
	}

	/**
	 * @param	[type]		$direction: ...
	 * @param	[type]		$tagList: ...
	 * @return	[type]		...
	 */
	function getKeepTags($direction='rte',$tagList='')	{
		if (!is_array($this->getKeepTags_cache[$direction]) || $tagList)	{
			$typoScript_list = 'b,i,u,a,img,br,div,center,pre,font,hr,sub,sup,p,strong,em,li,ul,ol,blockquote,strike,span';
			$keepTags = array_flip(t3lib_div::trimExplode(',',$typoScript_list.','.strtolower($this->procOptions['allowTags']),1));
			$denyTags = t3lib_div::trimExplode(',',$this->procOptions['denyTags'],1);
			reset($denyTags);
			while(list(,$dKe)=each($denyTags))	{
				unset($keepTags[$dKe]);
			}
			
			if (strcmp($tagList,''))	{
				$keepTags = array_flip(t3lib_div::trimExplode(',',$tagList,1));
			}
			
			switch ($direction)	{
				case 'rte':
					if (isset($keepTags['b']))	{$keepTags['b']=array('remap'=>'STRONG');}
					if (isset($keepTags['i']))	{$keepTags['i']=array('remap'=>'EM');}
					list($keepTags) = $this->HTMLparserConfig($this->procOptions['HTMLparser_rte.'],$keepTags);
				break;
				case 'db':
					if (isset($keepTags['strong']))	{$keepTags['strong']=array('remap'=>'B');}
					if (isset($keepTags['em']))		{$keepTags['em']=array('remap'=>'I');}
					if (isset($keepTags['span']))		{
						$classes=array_merge(array(''),$this->allowedClasses);
						$keepTags['span']=array(
							'allowedAttribs'=>'class',
							'fixAttrib' => Array(
								'class' => Array (
									'list' => $classes,
									'removeIfFalse' => 1
								)
							),
							'rmTagIfNoAttrib' => 1
						);
						if (!$this->procOptions['allowedClasses'])	unset($keepTags['span']['fixAttrib']['class']['list']);
					}
					if (isset($keepTags['font']))		{
						$colors=array_merge(array(''),t3lib_div::trimExplode(',',$this->procOptions['allowedFontColors'],1));
						$keepTags['font']=array(
							'allowedAttribs'=>'face,color,size',
							'fixAttrib' => Array(
								'face' => Array (
									'removeIfFalse' => 1
								),
								'color' => Array (
									'removeIfFalse' => 1,
									'list'=>$colors
								),
								'size' => Array (
									'removeIfFalse' => 1,
								)
							),
							'rmTagIfNoAttrib' => 1
						);
						if (!$this->procOptions['allowedFontColors'])	unset($keepTags['font']['fixAttrib']['color']['list']);
					}

					$TSc = $this->procOptions['HTMLparser_db.'];
					if (!$TSc['globalNesting'])	$TSc['globalNesting']='b,i,u,a,center,font,sub,sup,strong,em,strike,span';
					if (!$TSc['noAttrib'])	$TSc['noAttrib']='b,i,u,br,center,hr,sub,sup,strong,em,li,ul,ol,blockquote,strike';
					list($keepTags) = $this->HTMLparserConfig($TSc,$keepTags);
				break;
			}
			if (!$tagList)	{
				$this->getKeepTags_cache[$direction] = $keepTags;
			} else {
				return $keepTags;
			}
		}
		return $this->getKeepTags_cache[$direction];
	}

	/**
	 * Cleaning (->db) for standard content elements (ts)
	 * 
	 * @param	[type]		$value: ...
	 * @param	[type]		$css: ...
	 * @return	[type]		...
	 */
	function TS_transform_db($value,$css=0)	{
		$this->TS_transform_db_safecounter--;
		if ($this->TS_transform_db_safecounter<0)	return $value;	// safety... so forever loops are avoided (they should not occur, but an error would potentially do this...)
			// Work on everything but these blocks.
		$blockSplit = $this->splitIntoBlock('TABLE,BLOCKQUOTE,'.$this->headListTags,$value);

		$cc=0;
		$aC = count($blockSplit);
		reset($blockSplit);
		while(list($k,$v)=each($blockSplit))	{
			$cc++;
			$lastBR = $cc==$aC ? '' : chr(10);

			if ($k%2)	{	// block:
				$tag=$this->getFirstTag($v);
				$tagName=$this->getFirstTagName($v);
				switch($tagName)	{
					case 'BLOCKQUOTE':	// Keep blockquotes, but clean the inside recursively in the same manner as the main code
						$blockSplit[$k]='<'.$tagName.'>'.$this->TS_transform_db($this->removeFirstAndLastTag($blockSplit[$k]),$css).'</'.$tagName.'>'.$lastBR;
					break;
					case 'OL':
					case 'UL':	// Transform lists into <typolist>-tags:
						if (!$css)	{
							if (!isset($this->procOptions['typolist']) || $this->procOptions['typolist'])	{
								$parts = $this->getAllParts($this->splitIntoBlock('LI',$this->removeFirstAndLastTag($blockSplit[$k])),1,0);
								while(list($k2)=each($parts))	{
									$parts[$k2]=ereg_replace(chr(10).'|'.chr(13),'',$parts[$k2]);	// remove all linesbreaks!
									$parts[$k2]=$this->defaultTStagMapping($parts[$k2],'db');
									$parts[$k2]=$this->cleanFontTags($parts[$k2],0,0,0);
									$parts[$k2] = $this->HTMLcleaner_db($parts[$k2],strtolower($this->procOptions['allowTagsInTypolists']?$this->procOptions['allowTagsInTypolists']:'br,font,b,i,u,a,img,span,strong,em'));
								}
								if ($tagName=='OL')	{$params=' type="1"';}else{$params='';}
								$blockSplit[$k]='<typolist'.$params.'>'.chr(10).implode(chr(10),$parts).chr(10).'</typolist>'.$lastBR;
							}
						} else {
							$blockSplit[$k].=$lastBR;
						}
					break;
					case 'TABLE':	// Tables are NOT allowed in any form.
						if (!$this->procOptions['preserveTables'] && !$css)	{
							$blockSplit[$k]=$this->TS_transform_db($this->removeTables($blockSplit[$k]));
						} else {
							$blockSplit[$k]=str_replace(chr(10),'',$blockSplit[$k]).$lastBR;
						}
					break;
					case 'H1':
					case 'H2':
					case 'H3':
					case 'H4':
					case 'H5':
					case 'H6':
						if (!$css)	{
							$attribArray=$this->get_tag_attributes_classic($tag);
								// Processing inner content here:
							$innerContent = $this->HTMLcleaner_db($this->removeFirstAndLastTag($blockSplit[$k]));
							
							if (!isset($this->procOptions['typohead']) || $this->procOptions['typohead'])	{
								$type = intval(substr($tagName,1));
								$blockSplit[$k]='<typohead'.($type!=6?' type='.$type:'').($attribArray['align']?' align='.$attribArray['align']:'').($attribArray['class']?' class='.$attribArray['class']:'').'>'.$innerContent.'</typohead>'.$lastBR;
							} else {
								$blockSplit[$k]='<'.$tagName.($attribArray['align']?' align='.$attribArray['align']:'').($attribArray['class']?' class='.$attribArray['class']:'').'>'.$innerContent.'</'.$tagName.'>'.$lastBR;
							}
						} else {
							$blockSplit[$k].=$lastBR;
						}
					break;
					default:
						$blockSplit[$k].=$lastBR;
					break;
				}
			} else {	// NON-block:
				if (strcmp(trim($blockSplit[$k]),''))	{
					$blockSplit[$k]=$this->divideIntoLines($blockSplit[$k]).$lastBR;
				} else unset($blockSplit[$k]);
			}
		}
		$this->TS_transform_db_safecounter++;
		
		return implode('',$blockSplit);
	}

	/**
	 * Set (->rte) for standard content elements (ts)
	 * 
	 * @param	[type]		$value: ...
	 * @param	[type]		$css: ...
	 * @return	[type]		...
	 */
	function TS_transform_rte($value,$css=0)	{
			// Work on everything but the blocks.
		$blockSplit = $this->splitIntoBlock('TABLE,BLOCKQUOTE,TYPOLIST,TYPOHEAD,'.$this->headListTags,$value);
		reset($blockSplit);
		while(list($k,$v)=each($blockSplit))	{
//debug(t3lib_div::debug_ordvalue($blockSplit[$k]),1);		
		}

		reset($blockSplit);
		while(list($k,$v)=each($blockSplit))	{
			if ($k%2)	{	// block:
				$tag=$this->getFirstTag($v);
				$tagName=$this->getFirstTagName($v);
				$attribArray=$this->get_tag_attributes_classic($tag);
				switch($tagName)	{
					case 'BLOCKQUOTE':	// Keep blockquotes:
						$blockSplit[$k]=$tag.$this->TS_transform_rte($this->removeFirstAndLastTag($blockSplit[$k]),$css).'</'.$tagName.'>';
					break;
					case 'TYPOLIST':	// Transform typolist blocks into OL/UL lists. Type 1 is expected to be numerical block
						if (!isset($this->procOptions['typolist']) || $this->procOptions['typolist'])	{
							$tListContent = $this->removeFirstAndLastTag($blockSplit[$k]);
							$tListContent = ereg_replace('^[ ]*'.chr(10),'',$tListContent);
							$tListContent = ereg_replace(chr(10).'[ ]*$','',$tListContent);
							$lines=explode(chr(10),$tListContent);
							$typ= $attribArray['type']==1?'ol':'ul';
							$blockSplit[$k]='<'.$typ.'>'.chr(10).'<li>'.implode('</li>'.chr(10).'<li>',$lines).'</li></'.$typ.'>';
						}
					break;
					case 'TYPOHEAD':	// Transform typohead into Hx tags.
						if (!isset($this->procOptions['typohead']) || $this->procOptions['typohead'])	{
							$tC=$this->removeFirstAndLastTag($blockSplit[$k]);
							$typ=t3lib_div::intInRange($attribArray['type'],0,6);
							if (!$typ)	$typ=6;
							$align = $attribArray['align']?' align='.$attribArray['align']: '';
							$class = $attribArray['class']?' class='.$attribArray['class']: '';
							$blockSplit[$k]='<h'.$typ.$align.$class.'>'.$tC.'</h'.$typ.'>';
						}
					break;
				}
				$blockSplit[$k+1]=ereg_replace('^[ ]*'.chr(10),'',$blockSplit[$k+1]);	// Removing linebreak if typohead
			} else {	// NON-block:
				$nextFTN = $this->getFirstTagName($blockSplit[$k+1]);
				$singleLineBreak = $blockSplit[$k]==chr(10);
				if (t3lib_div::inList('TABLE,BLOCKQUOTE,TYPOLIST,TYPOHEAD,'.$this->headListTags,$nextFTN))	{	// Removing linebreak if typolist/typohead
					$blockSplit[$k]=ereg_replace(chr(10).'[ ]*$','',$blockSplit[$k]);
				}
					// If $blockSplit[$k] is blank then unset the line. UNLESS the line happend to be a single line break.
				if (!strcmp($blockSplit[$k],'') && !$singleLineBreak)	{
					unset($blockSplit[$k]);	
				} else {
					$blockSplit[$k]=$this->setDivTags($blockSplit[$k],($this->procOptions['useDIVasParagraphTagForRTE']?'DIV':'P'));
				}
			}
		}
		return implode(chr(10),$blockSplit);
	}
	









	/**
	 * Tranform value for RTE based on specConf in the direction specified by $direction (rte/db)
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function TS_tmplParts_rte($value)	{
		$substMarkers=Array(
			'###POST_TIME###' => 'time',
			'###POST_AGE###' => 'age',
			'###POST_AUTHOR###' => 'name',
			'###POST_EMAIL###' => 'email',
			'###POST_WWW###' => 'url',
			'###POST_DATE###' => 'date',
//			'###POST_TITLE###' => 'Caeli ut dividant diem ac noctem!',
//			'###POST_CONTENT###' => 'Dixit autem Deus fiant luminaria in firmamento caeli ut dividant diem ac noctem et sint in signa<BR>et tempora et dies et annos ut luceant in firmamento caeli et inluminent terram et factum est ita fecitque Deus duo magna luminaria luminare maius ut praeesset diei et luminare minus ut praeesset nocti et stellas et posuit eas in firmamento caeli ut lucerent super terram.',
		);

		
		reset($substMarkers);
		while(list($key,$content)=each($substMarkers))	{
			$value=str_replace($key,
				'<img src="'.TYPO3_mainDir.'ext/rte/markers/'.$content.'.gif" align="absmiddle" alt="" />'
			,$value);
		}
		return $value;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * Tranform value for RTE based on specConf in the direction specified by $direction (rte/db)
	 * 
	 * @param	[type]		$value: ...
	 * @param	[type]		$specConf: ...
	 * @param	[type]		$direction: ...
	 * @param	[type]		$thisConfig: ...
	 * @return	[type]		...
	 */
	function RTE_transform($value,$specConf,$direction='rte',$thisConfig='')	{
		$this->procOptions=$thisConfig['proc.'];
		$this->preserveTags = strtoupper(implode(',',t3lib_div::trimExplode(',',$this->procOptions['preserveTags'])));

			// Get parameters for rte_transformation:
		$p = $this->rte_p = t3lib_BEfunc::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);

		if (strcmp($this->procOptions['overruleMode'],''))	{
			$modes=array_unique(t3lib_div::trimExplode(',',$this->procOptions['overruleMode']));
		} else {
			$modes=array_unique(t3lib_div::trimExplode('-',$p['mode']));
		}

		$revmodes=array_flip($modes);

			// Find special modes and extract them:
		if (isset($revmodes['ts']))	{
			$modes[$revmodes['ts']]='ts_transform,ts_preserve,ts_images,ts_links';
		}
			// Find special modes and extract them:
		if (isset($revmodes['ts_css']))	{
			$modes[$revmodes['ts_css']]='css_transform,ts_images,ts_links';
		}
		$modes = array_unique(t3lib_div::trimExplode(',',implode(',',$modes),1));
		if ($direction=='rte')	{
			$modes=array_reverse($modes);
		}

		$entry_HTMLparser = $this->procOptions['entryHTMLparser_'.$direction] ? $this->HTMLparserConfig($this->procOptions['entryHTMLparser_'.$direction.'.']) : '';
		$exit_HTMLparser = $this->procOptions['exitHTMLparser_'.$direction] ? $this->HTMLparserConfig($this->procOptions['exitHTMLparser_'.$direction.'.']) : '';

		if (!$this->procOptions['disableUnifyLineBreaks'])	{
			$value = str_replace(chr(13).chr(10),chr(10),$value);
		}

		if (is_array($entry_HTMLparser))	{
			$value = $this->HTMLcleaner($value,$entry_HTMLparser[0],$entry_HTMLparser[1],$entry_HTMLparser[2]);
		}
			// Traverse modes:
		reset($modes);
		while(list(,$cmd)=each($modes))	{
				// ->DB
			if ($direction=='db')	{
				switch($cmd)	{
					case 'ts_transform':
					case 'css_transform':
						$value = str_replace(chr(13),'',$value);	// Has a very disturbing effect, so just remove all '13' - depend on '10'
						$this->allowedClasses = t3lib_div::trimExplode(',',strtoupper($this->procOptions['allowedClasses']),1);
						$value=$this->TS_transform_db($value,$cmd=='css_transform');
					break;
					case 'ts_links':
						$value=$this->TS_links_db($value);
					break;
					case 'ts_reglinks':
						$value=$this->TS_reglinks($value,'db');
					break;
					case 'ts_preserve':
						$value=$this->TS_preserve_db($value);
					break;
					case 'ts_images':
						$value=$this->TS_images_db($value);
					break;
					case 'ts_strip':
						$value=$this->TS_strip_db($value);
					break;
					case 'dummy':
					break;
				}
			}
				// ->RTE
			if ($direction=='rte')	{
				switch($cmd)	{
					case 'ts_transform':
					case 'css_transform':
						$value = str_replace(chr(13),'',$value);	// Has a very disturbing effect, so just remove all '13' - depend on '10'
						$value=$this->TS_transform_rte($value,$cmd=='css_transform');
					break;
					case 'ts_links':
						$value=$this->TS_links_rte($value);
					break;
					case 'ts_reglinks':
						$value=$this->TS_reglinks($value,'rte');
					break;
					case 'ts_preserve':
						$value=$this->TS_preserve_rte($value);
					break;
					case 'ts_images':
						$value=$this->TS_images_rte($value);
					break;
					case 'dummy':
					break;
				}
			}
		}

		if (is_array($exit_HTMLparser))	{
			$value = $this->HTMLcleaner($value,$exit_HTMLparser[0],$exit_HTMLparser[1],$exit_HTMLparser[2]);
		}

		if (!$this->procOptions['disableUnifyLineBreaks'])	{
			$value = str_replace(chr(13).chr(10),chr(10),$value);	// Make sure no \r\n sequences has entered in the meantime...
			$value = str_replace(chr(10),chr(13).chr(10),$value);	// ... and then change all \n into \r\n
		}

		return $value;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function rteImageStorageDir()	{
		return $this->rte_p['imgpath'] ? $this->rte_p['imgpath'] : $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'];
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_parsehtml_proc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_parsehtml_proc.php']);
}
?>