<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Contains class for icon generation in the backend
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   85: class t3lib_iconWorks	
 *   99:     function getIconImage($table,$row=array(),$backPath,$params='',$shaded=0)	
 *  116:     function getIcon($table,$row=array(),$shaded=0)	
 *  198:     function skinImg($backPath,$src,$wHattribs='',$outputMode=0)	
 *
 *              SECTION: Other functions
 *  267:     function makeIcon($iconfile,$mode, $user, $protectSection=0,$absFile='')	
 *  370:     function imagecopyresized(&$im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h)	
 *  403:     function imagecreatefrom($file)	
 *  420:     function imagemake($im, $path)	
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */










/**
 * Icon generation, backend
 * This library has functions that returns - and if necessary creates - the icon for an element in TYPO3
 * 
 * Expects global vars:
 * - $BACK_PATH
 * - PATH_typo3
 * - $TCA, $PAGES_TYPES
 * 
 * 
 * Notes:
 * These functions are strongly related to the interface of TYPO3.
 * The class is included in eg. init.php
 * ALL functions called without making a class instance, eg. "t3lib_iconWorks::getIconImage()"
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_iconWorks	{
	
	/**
	 * Returns an icon image tag, 18x16 pixels, based on input information. 
	 * This function is recommended to use in your backend modules.
	 * 
	 * @param	string		The table name
	 * @param	array		The table row ("enablefields" are at least needed for correct icon display and for pages records some more fields in addition!)
	 * @param	string		The backpath to the main TYPO3 directory (relative path back to PATH_typo3)
	 * @param	string		Additional attributes for the image tag
	 * @param	boolean		If set, the icon will be grayed/shaded.
	 * @return	string		<img>-tag
	 * @see getIcon()
	 */
	function getIconImage($table,$row=array(),$backPath,$params='',$shaded=0)	{
		$str='<img'.t3lib_iconWorks::skinImg($backPath,t3lib_iconWorks::getIcon($table,$row,$shaded),'width="18" height="16"').(trim($params)?' '.trim($params):'');
		if (!stristr($str,'alt="'))	$str.=' alt=""';
		$str.=' />';
		return $str;
	}

	/**
	 * Creates the icon for input table/row
	 * Returns filename for the image icon, relative to PATH_typo3
	 * 
	 * @param	string		The table name
	 * @param	array		The table row ("enablefields" are at least needed for correct icon display and for pages records some more fields in addition!)
	 * @param	boolean		If set, the icon will be grayed/shaded.
	 * @return	string		Icon filename
	 * @see getIconImage()
	 */
	function getIcon($table,$row=array(),$shaded=0)	{
		global $TCA, $PAGES_TYPES, $ICON_TYPES;
		$user=false;
		if ($table=='pages')	{
			if (!$iconfile = $PAGES_TYPES[$row['doktype']]['icon'])	{
				$iconfile = $PAGES_TYPES['default']['icon'];
			}
			if ($row['module'] && $ICON_TYPES[$row['module']]['icon'])	{
				$iconfile = $ICON_TYPES[$row['module']]['icon'];
			}
		} else {
			if (!$iconfile = $TCA[$table]['ctrl']['typeicons'][$row[$TCA[$table]['ctrl']['typeicon_column']]])	{
				$iconfile = (($TCA[$table]['ctrl']['iconfile']) ? $TCA[$table]['ctrl']['iconfile'] : $table.'.gif');
			}
		}
			// Setting path if not already set:
		if (!strstr($iconfile,'/'))	{
			$iconfile = 'gfx/i/'.$iconfile;
		}
		
		if (substr($iconfile,0,3)=='../')	{
			$absfile=PATH_site.substr($iconfile,3);
		} else {
			$absfile=PATH_typo3.$iconfile;
		}
		
		
		$hidden = false;
		$timing = false;
		$futuretiming = false;
		$deleted = false;
		$protectSection=0;
		if ($enCols=$TCA[$table]['ctrl']['enablecolumns'])	{
			if ($enCol=$row[$enCols['disabled']])	{if ($enCol){$hidden=true;}}
			if ($enCol=$row[$enCols['starttime']])	{if (time() < $enCol){$timing=true;}}
			if ($enCol=$row[$enCols['endtime']])	{
				if ($enCol!=0){
					if ($enCol < time())	{
						$timing=true;
					} else {
						$futuretiming=true;
					}
				} 
			}
			if ($enCol=$row[$enCols['fe_group']])	{
				$user=$enCol;
			}
		}
		if ($col=$row[$TCA[$table]['ctrl']['delete']])	{
			$deleted = true;
		}
		if ($table=='pages' && $row['extendToSubpages'] && ($hidden || $timing || $futuretiming || $user))	{
			$protectSection=1;
		}
		if ($hidden || $timing || $futuretiming || $deleted || $user || $shaded)	{
			$string='';
			if ($deleted)	{
				$string='deleted';
			} else {
				if ($hidden) $string.='hidden';
				if ($timing) $string.='timing';
				if (!$string && $futuretiming) {
					$string='futuretiming';
				}
			}
			$theRes= t3lib_iconWorks::makeIcon($GLOBALS['BACK_PATH'].$iconfile, $string, $user, $protectSection, $absfile);
			return $theRes;
		} else {
			return $iconfile;
		}
	}
	
	/**
	 * Returns the src=... for the input $src value OR any alternative found in $TBE_STYLES['skinImg']
	 * Used for skinning the TYPO3 backend with an alternative set of icons
	 * 
	 * @param	string		Current backpath to PATH_typo3 folder
	 * @param	string		Icon file name relative to PATH_typo3 folder
	 * @param	string		Default width/height, defined like 'width="12" height="14"'
	 * @param	integer		Mode: 0 (zero) is default and returns src/width/height. 1 returns value of src+backpath, 2 returns value of w/h.
	 * @return	string		Returns ' src="[backPath][src]" [wHattribs]'
	 * @see skinImgFile()
	 */
	function skinImg($backPath,$src,$wHattribs='',$outputMode=0)	{
		
			// LOOKING for alternative icons:
		if ($GLOBALS['TBE_STYLES']['skinImg'][$src])	{	// Slower or faster with is_array()? Could be used.
			list($src,$wHattribs) = $GLOBALS['TBE_STYLES']['skinImg'][$src];
		} elseif ($GLOBALS['TBE_STYLES']['skinImgAutoCfg'])	{	// Otherwise, test if auto-detection is enabled:
		
				// Search for alternative icon automatically:
			$fExt = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['forceFileExtension'];
			$scaleFactor = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['scaleFactor'] ? $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['scaleFactor'] : 1;	// Scaling factor
			$lookUpName = $fExt ? ereg_replace('\.[[:alnum:]]+$','',$src).'.'.$fExt : $src;	// Set filename to look for
			
				// If file is found:
			if (@is_file($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'].$lookUpName))	{	// If there is a file...
				$iInfo = @getimagesize($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'].$lookUpName);	// Get width/height:
				
					// Set $src and $wHattribs:
				$src = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['relDir'].$lookUpName;
				$wHattribs = 'width="'.round($iInfo[0]*$scaleFactor).'" height="'.round($iInfo[1]*$scaleFactor).'"';
			}
			
				// In anycase, set currect src / wHattrib - this way we make sure that an entry IS found next time we hit the function, regardless of whether it points to a alternative icon or just the current.
			$GLOBALS['TBE_STYLES']['skinImg'][$src] = array($src,$wHattribs);		// Set default...
		}
	
			// DEBUG: This doubles the size of all icons - for testing/debugging:	
#		if (ereg('^width="([0-9]+)" height="([0-9]+)"$',$wHattribs,$reg))	$wHattribs='width="'.($reg[1]*2).'" height="'.($reg[2]*2).'"';

			// Return icon source/wHattributes:
		switch($outputMode)	{
			case 0:
				return ' src="'.$backPath.$src.'" '.$wHattribs;
			break;
			case 1:
				return $backPath.$src;
			break;
			case 2:
				return $wHattribs;
			break;
		}
	}
	
	









	/***********************************
	 *
	 * Other functions
	 *
	 ***********************************/

	/**
	 * Creates the icon file for the function getIcon()
	 * 
	 * @param	string		Original unprocessed Icon file, relative path to PATH_typo3
	 * @param	string		Mode string, eg. "deleted" or "futuretiming" determining how the icon will look
	 * @param	integer		The number of the fe_group record uid if applicable
	 * @param	boolean		Flag determines if the protected-section icon should be applied.
	 * @param	string		Absolute path to file from which to create the icon.
	 * @return	string		Filename relative to PATH_typo3
	 * @access private
	 */
	function makeIcon($iconfile,$mode, $user, $protectSection=0,$absFile='')	{
		$iconFileName = 'icon_'.t3lib_div::shortMD5($iconfile.'|'.$mode.'|-'.$user.'|'.$protectSection).'.'.($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']?'png':'gif');
		$mainpath = '../typo3temp/'.$iconFileName;
		$path = PATH_site.'typo3temp/'.$iconFileName;
		
			
		if (@file_exists(PATH_typo3.'icons/'.$iconFileName))	{	// Returns if found in typo3/icons/
			return 'icons/'.$iconFileName;
		} elseif (@file_exists($path))	{	// Returns if found in ../typo3temp/icons/
			return $mainpath;
		} else {	// Makes icon:
			if (@file_exists($absFile))	{
				if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'])	{
					$im = t3lib_iconworks::imagecreatefrom($absFile);
					if ($im<0)	return $iconfile;

					if ($mode!='futuretiming' && !(!$mode && $user))	{
						for ($c=0; $c<ImageColorsTotal($im); $c++)	{
							$cols = ImageColorsForIndex($im,$c);
							$newcol = round(($cols['red']+$cols['green']+$cols['blue'])/3);
							$lighten = 2;
							$newcol = round (255-((255-$newcol)/$lighten));
							ImageColorSet($im,$c,$newcol,$newcol,$newcol);
						}
					}
					if ($user)	{
						$black = ImageColorAllocate($im, 0,0,0);
						imagefilledrectangle($im, 0,0,(($user>10)?9:5),8,$black);
						
						$white = ImageColorAllocate($im, 255,255,255);
						imagestring($im, 1, 1, 1, $user, $white);
		
						$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_group.gif');
						if ($ol_im<0)	return $iconfile;

						t3lib_iconworks::imagecopyresized($im, $ol_im, 0, 0, 0, 0, imagesx($ol_im), imagesy($ol_im), imagesx($ol_im), imagesy($ol_im));
					}
					if ($mode)	{
						unset($ol_im);
						switch($mode)	{
							case 'deleted':
								$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_deleted.gif');
							break;
							case 'futuretiming':
								$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_timing.gif');
							break;
							case 'timing':
								$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_timing.gif');
							break;
							case 'hiddentiming':
								$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_hidden_timing.gif');
							break;
							case 'hidden':
							default:
								$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_hidden.gif');
							break;
						}
						if ($ol_im<0)	return $iconfile;
						t3lib_iconworks::imagecopyresized($im, $ol_im, 0, 0, 0, 0, imagesx($ol_im), imagesy($ol_im), imagesx($ol_im), imagesy($ol_im));
					}
					if ($protectSection)	{
						$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_sub5.gif');
						if ($ol_im<0)	return $iconfile;
						t3lib_iconworks::imagecopyresized($im, $ol_im, 0, 0, 0, 0, imagesx($ol_im), imagesy($ol_im), imagesx($ol_im), imagesy($ol_im));
					}
					@t3lib_iconWorks::imagemake($im, $path);
					t3lib_div::gif_compress($path, 'IM');
					ImageDestroy($im);
					return $mainpath;
				} else {
					return $iconfile;
				}
			} else {
				return $GLOBALS['BACK_PATH'].'gfx/fileicons/default.gif';
			}
		}
	}
	
	/**
	 * The necessity of using this function for combining two images if GD is version 2 is that
	 * 	GD2 cannot manage to combine two indexed-color images without totally spoiling everything.
	 * 	In class.t3lib_stdgraphic this was solved by combining the images onto a first created true color image
	 * 	However it has turned out that this method will not work if the indexed png-files contains transparency.
	 * 	So I had to turn my attention to ImageMagick - my 'enemy of death'.
	 * 	And so it happend - ImageMagick is now used to combine my two indexed-color images with transparency. And that works.
	 * 	Of course it works only if ImageMagick is able to create valid png-images - which you cannot be sure of with older versions (still 5+)
	 * 	The only drawback is (apparently) that IM creates true-color png's. The transparency of these will not be shown by MSIE on windows at this time (although it's straight 0%/100% transparency!) and the file size may be larger.
	 * 
	 * For parameters, see PHP function "imagecopyresized()"
	 * 
	 * @param	pointer		see PHP function "imagecopyresized()"
	 * @param	pointer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @param	integer		see PHP function "imagecopyresized()"
	 * @return	void		
	 * @access private
	 */
	function imagecopyresized(&$im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_2'] && $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'])	{	// Maybe I'll have to change this if GD2/gif does not work either...
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'])	{
				$cmd=$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'].
						($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_combine_filename']?$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_combine_filename']:'combine').
						' -compose over ';
				$tempBaseName = PATH_site.'typo3temp/ICRZ_'.md5(uniqid('.'));
				
				ImagePng($im, $tempBaseName.'_im.png');
				ImagePng($cpImg, $tempBaseName.'_cpImg.png');
				exec($cmd.
					$tempBaseName.'_cpImg.png '.
					$tempBaseName.'_im.png '.
					$tempBaseName.'_out.png '
				);
				$im = imagecreatefrompng($tempBaseName.'_out.png');
				unlink($tempBaseName.'_im.png');
				unlink($tempBaseName.'_cpImg.png');
				unlink($tempBaseName.'_out.png');
			}
		} else {
			imagecopyresized($im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h);
		}
	}

	/**
	 * Create new image pointer from input file (either gif/png, in case the wrong format it is converted by t3lib_div::read_png_gif())
	 * 
	 * @param	string		Absolute filename of the image file from which to start the icon creation.
	 * @return	mixed		If success, image pointer, otherwise "-1"
	 * @access private
	 * @see t3lib_div::read_png_gif
	 */
	function imagecreatefrom($file)	{
		$file = t3lib_div::read_png_gif($file,$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']);
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'])	{
			return $file ? imagecreatefrompng($file) : -1;
		} else {
			return $file ? imagecreatefromgif($file) : -1;
		}
	}

	/**
	 * Write the icon in $im pointer to $path
	 * 
	 * @param	pointer		Pointer to GDlib image resource
	 * @param	string		Absolute path to the filename in which to write the icon.
	 * @return	void		
	 * @access private
	 */
	function imagemake($im, $path)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'])	{
			@ImagePng($im, $path);
		} else {
			@ImageGif($im, $path);
		}
	}
}
?>