<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   85: class t3lib_iconWorks
 *  100:     function getIconImage($table,$row=array(),$backPath,$params='',$shaded=FALSE)
 *  118:     function getIcon($table,$row=array(),$shaded=FALSE)
 *  264:     function skinImg($backPath,$src,$wHattribs='',$outputMode=0)
 *
 *              SECTION: Other functions
 *  353:     function makeIcon($iconfile,$mode, $user, $protectSection,$absFile,$iconFileName_stateTagged)
 *  475:     function imagecopyresized(&$im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h)
 *  505:     function imagecreatefrom($file)
 *  522:     function imagemake($im, $path)
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
final class t3lib_iconWorks	{

	/**
	 * Returns an icon image tag, 18x16 pixels, based on input information.
	 * This function is recommended to use in your backend modules.
	 * Usage: 60
	 *
	 * @param	string		The table name
	 * @param	array		The table row ("enablefields" are at least needed for correct icon display and for pages records some more fields in addition!)
	 * @param	string		The backpath to the main TYPO3 directory (relative path back to PATH_typo3)
	 * @param	string		Additional attributes for the image tag
	 * @param	boolean		If set, the icon will be grayed/shaded
	 * @return	string		<img>-tag
	 * @see getIcon()
	 */
	public static function getIconImage($table, $row = array(), $backPath, $params = '', $shaded = FALSE) {
		$str = '<img'.t3lib_iconWorks::skinImg($backPath, t3lib_iconWorks::getIcon($table, $row, $shaded), 'width="18" height="16"').(trim($params)?' '.trim($params):'');
		if (!stristr($str, 'alt="')) {
			$str.=' alt=""';
		}
		$str.=' />';
		return $str;
	}

	/**
	 * Creates the icon for input table/row
	 * Returns filename for the image icon, relative to PATH_typo3
	 * Usage: 24
	 *
	 * @param	string		The table name
	 * @param	array		The table row ("enablefields" are at least needed for correct icon display and for pages records some more fields in addition!)
	 * @param	boolean		If set, the icon will be grayed/shaded
	 * @return	string		Icon filename
	 * @see getIconImage()
	 */
	public static function getIcon($table, $row = array(), $shaded = FALSE) {
		global $TCA, $PAGES_TYPES, $ICON_TYPES;

			// Flags:
		$doNotGenerateIcon = $GLOBALS['TYPO3_CONF_VARS']['GFX']['noIconProc'];	// If set, the icon will NOT be generated with GDlib. Rather the icon will be looked for as [iconfilename]_X.[extension]
		$doNotRenderUserGroupNumber = TRUE;	// If set, then the usergroup number will NOT be printed unto the icon. NOTICE. the icon is generated only if a default icon for groups is not found... So effectively this is ineffective...

			// Shadow:
		if ($TCA[$table]['ctrl']['versioningWS']) {
			switch((int)$row['t3ver_state']) {
				case 1:
					return 'gfx/i/shadow_hide.png';
				break;
				case 2:
					return 'gfx/i/shadow_delete.png';
				break;
				case 3:
					return 'gfx/i/shadow_moveto_plh.png';
				break;
				case 4:
					return 'gfx/i/shadow_moveto_pointer.png';
				break;
			}
		}

			// First, find the icon file name. This can depend on configuration in TCA, field values and more:
		if ($table=='pages') {
				// @TODO: RFC #7370: doktype 2&5 are deprecated since TYPO3 4.2-beta1
			if ($row['nav_hide'] && ($row['doktype']==1||$row['doktype']==2))	$row['doktype'] = 5;	// Workaround to change the icon if "Hide in menu" was set

			if (!$iconfile = $PAGES_TYPES[$row['doktype']]['icon']) {
				$iconfile = $PAGES_TYPES['default']['icon'];
			}
			if ($row['module'] && $ICON_TYPES[$row['module']]['icon']) {
				$iconfile = $ICON_TYPES[$row['module']]['icon'];
			}
		} else {
			if (!$iconfile = $TCA[$table]['ctrl']['typeicons'][$row[$TCA[$table]['ctrl']['typeicon_column']]]) {
				$iconfile = (($TCA[$table]['ctrl']['iconfile']) ? $TCA[$table]['ctrl']['iconfile'] : $table.'.gif');
			}
		}

			// Setting path of iconfile if not already set. Default is "gfx/i/"
		if (!strstr($iconfile, '/')) {
			$iconfile = 'gfx/i/'.$iconfile;
		}

			// Setting the absolute path where the icon should be found as a file:
		if (substr($iconfile, 0, 3)=='../') {
			$absfile = PATH_site.substr($iconfile, 3);
		} else {
			$absfile = PATH_typo3.$iconfile;
		}

			// Initializing variables, all booleans except otherwise stated:
		$hidden = FALSE;
		$timing = FALSE;
		$futuretiming = FALSE;
		$user = FALSE;				// In fact an integer value...
		$deleted = FALSE;
		$protectSection = FALSE;	// Set, if a page-record (only pages!) has the extend-to-subpages flag set.
		$noIconFound = $row['_NO_ICON_FOUND'] ? TRUE : FALSE;
		// + $shaded which is also boolean!

			// Icon state based on "enableFields":
		if (is_array($TCA[$table]['ctrl']['enablecolumns'])) {
			$enCols = $TCA[$table]['ctrl']['enablecolumns'];
				// If "hidden" is enabled:
			if ($enCols['disabled'])	{ if ($row[$enCols['disabled']]) { $hidden = TRUE; }}
				// If a "starttime" is set and higher than current time:
			if ($enCols['starttime']) {
				if ($GLOBALS['EXEC_TIME'] < intval($row[$enCols['starttime']])) {
					$timing = TRUE;
						// ...And if "endtime" is NOT set:
					if (intval($row[$enCols['endtime']]) == 0) {
						$futuretiming = TRUE;
					}
				}
			}
				// If an "endtime" is set:
			if ($enCols['endtime']) {
				if (intval($row[$enCols['endtime']]) > 0) {
					if (intval($row[$enCols['endtime']]) < $GLOBALS['EXEC_TIME']) {
						$timing = TRUE;	// End-timing applies at this point.
					} else {
						$futuretiming = TRUE;		// End-timing WILL apply in the future for this element.
					}
				}
			}
				// If a user-group field is set:
			if ($enCols['fe_group']) {
				$user = $row[$enCols['fe_group']];
				if ($user && $doNotRenderUserGroupNumber)	$user = 100;	// Limit for user number rendering!
			}
		}

			// If "deleted" flag is set (only when listing records which are also deleted!)
		if ($col = $row[$TCA[$table]['ctrl']['delete']]) {
			$deleted = TRUE;
		}
			// Detecting extendToSubpages (for pages only)
		if ($table=='pages' && $row['extendToSubpages'] && ($hidden || $timing || $futuretiming || $user)) {
			$protectSection = TRUE;
		}

			// If ANY of the booleans are set it means we have to alter the icon:
		if ($hidden || $timing || $futuretiming || $user || $deleted || $shaded || $noIconFound) {
			$flags = '';
			$string = '';
			if ($deleted) {
				$string = 'deleted';
				$flags = 'd';
			} elseif ($noIconFound) {	// This is ONLY for creating icons with "?" on easily...
				$string = 'no_icon_found';
				$flags = 'x';
			} else {
				if ($hidden) $string.='hidden';
				if ($timing) $string.='timing';
				if (!$string && $futuretiming) {
					$string = 'futuretiming';
				}

				$flags.=
					($hidden ? 'h' : '').
					($timing ? 't' : '').
					($futuretiming ? 'f' : '').
					($user ? 'u' : '').
					($protectSection ? 'p' : '').
					($shaded ? 's' : '');
			}

				// Create tagged icon file name:
			$iconFileName_stateTagged = preg_replace('/.([[:alnum:]]+)$/', '__'.$flags.'.\1', basename($iconfile));

				// Check if tagged icon file name exists (a tagget icon means the icon base name with the flags added between body and extension of the filename, prefixed with underscore)
			if (@is_file(dirname($absfile) . '/' . $iconFileName_stateTagged) || @is_file($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'] . '/' . dirname($iconfile) . '/' . $iconFileName_stateTagged)) {	// Look for [iconname]_xxxx.[ext]
				return dirname($iconfile).'/'.$iconFileName_stateTagged;
			} elseif ($doNotGenerateIcon)	{		// If no icon generation can be done, try to look for the _X icon:
				$iconFileName_X = preg_replace('/.([[:alnum:]]+)$/', '__x.\1', basename($iconfile));
				if (@is_file(dirname($absfile).'/'.$iconFileName_X)) {
					return dirname($iconfile).'/'.$iconFileName_X;
				} else {
					return 'gfx/i/no_icon_found.gif';
				}
			} else {	// Otherwise, create the icon:
				$theRes = t3lib_iconWorks::makeIcon($GLOBALS['BACK_PATH'].$iconfile, $string, $user, $protectSection, $absfile, $iconFileName_stateTagged);
				return $theRes;
			}
		} else {
			return $iconfile;
		}
	}

	/**
	 * Returns the src=... for the input $src value OR any alternative found in $TBE_STYLES['skinImg']
	 * Used for skinning the TYPO3 backend with an alternative set of icons
	 * Usage: 336
	 *
	 * @param	string		Current backpath to PATH_typo3 folder
	 * @param	string		Icon file name relative to PATH_typo3 folder
	 * @param	string		Default width/height, defined like 'width="12" height="14"'
	 * @param	integer		Mode: 0 (zero) is default and returns src/width/height. 1 returns value of src+backpath, 2 returns value of w/h.
	 * @return	string		Returns ' src="[backPath][src]" [wHattribs]'
	 * @see skinImgFile()
	 */
	public static function skinImg($backPath, $src, $wHattribs = '', $outputMode = 0)	{

		static $cachedSkinImages = array();

		$imageId = md5($backPath . $src . $wHattribs . $outputMode);

		if (isset($cachedSkinImages[$imageId])) {
			return $cachedSkinImages[$imageId];
		}
			// Setting source key. If the icon is refered to inside an extension, we homogenize the prefix to "ext/":
		$srcKey = preg_replace('/^(\.\.\/typo3conf\/ext|sysext|ext)\//', 'ext/', $src);
		#if ($src!=$srcKey)debug(array($src, $srcKey));

			// LOOKING for alternative icons:
		if ($GLOBALS['TBE_STYLES']['skinImg'][$srcKey]) {	// Slower or faster with is_array()? Could be used.
			list($src, $wHattribs) = $GLOBALS['TBE_STYLES']['skinImg'][$srcKey];
		} elseif ($GLOBALS['TBE_STYLES']['skinImgAutoCfg']) {	// Otherwise, test if auto-detection is enabled:

				// Search for alternative icon automatically:
			$fExt = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['forceFileExtension'];
			$scaleFactor = ($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['scaleFactor'] ? $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['scaleFactor'] : 1);	// Scaling factor
			$lookUpName = ($fExt ? preg_replace('/\.[[:alnum:]]+$/', '', $srcKey) . '.' . $fExt : $srcKey);	// Set filename to look for

			if ($fExt && !@is_file($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'] . $lookUpName)) {
				// fallback to original filename if icon with forced extension doesn't exists
				$lookUpName = $srcKey;
			}
				// If file is found:
			if (@is_file($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'].$lookUpName)) {	// If there is a file...
				$iInfo = @getimagesize($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'] . $lookUpName);	// Get width/height:

					// Set $src and $wHattribs:
				$src = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['relDir'] . $lookUpName;
				$wHattribs = 'width="' . round($iInfo[0] * $scaleFactor) . '" height="' . round($iInfo[1] * $scaleFactor) . '"';
			}

				// In any case, set currect src / wHattrib - this way we make sure that an entry IS found next time we hit the function,
				// regardless of whether it points to a alternative icon or just the current.
			$GLOBALS['TBE_STYLES']['skinImg'][$srcKey] = array($src, $wHattribs);		// Set default...
		}

			// DEBUG: This doubles the size of all icons - for testing/debugging:
		# if (preg_match('/^width="([0-9]+)" height="([0-9]+)"$/', $wHattribs, $reg))	$wHattribs='width="'.($reg[1]*2).'" height="'.($reg[2]*2).'"';


			// rendering disabled (greyed) icons using _i (inactive) as name suffix ("_d" is already used)
		$matches = array();
		$srcBasename = basename($src);
		if (preg_match('/(.*)_i(\....)$/', $srcBasename, $matches)) {
			$temp_path = dirname(PATH_thisScript) . '/';
			if (!@is_file($temp_path . $backPath . $src)) {
				$srcOrg = preg_replace('/_i' . preg_quote($matches[2]) . '$/', $matches[2], $src);
				$src = t3lib_iconWorks::makeIcon($backPath . $srcOrg, 'disabled', 0, false, $temp_path . $backPath . $srcOrg, $srcBasename);
			}
		}


			// Return icon source/wHattributes:
		$output = '';
		switch($outputMode) {
			case 0:
				$output = ' src="' . $backPath . $src . '" ' . $wHattribs;
			break;
			case 1:
				$output = $backPath . $src;
			break;
			case 2:
				$output = $wHattribs;
			break;
		}

		$cachedSkinImages[$imageId] = $output;
		return $output;
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
	 * @param	string		The filename that this icon should have had, basically [icon base name]_[flags].[extension] - used for part of temporary filename
	 * @return	string		Filename relative to PATH_typo3
	 * @access private
	 */
	public static function makeIcon($iconfile, $mode, $user, $protectSection, $absFile, $iconFileName_stateTagged) {
		$iconFileName = 'icon_'.t3lib_div::shortMD5($iconfile.'|'.$mode.'|-'.$user.'|'.$protectSection).'_'.$iconFileName_stateTagged.'.'.($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']?'png':'gif');
		$mainpath = '../typo3temp/'.$iconFileName;
		$path = PATH_site.'typo3temp/'.$iconFileName;


		if (file_exists(PATH_typo3.'icons/'.$iconFileName))	{	// Returns if found in typo3/icons/
			return 'icons/'.$iconFileName;
		} elseif (file_exists($path))	{	// Returns if found in ../typo3temp/icons/
			return $mainpath;
		} else {	// Makes icon:
			if (file_exists($absFile)) {
				if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'])	{

						// Create image pointer, if possible
					$im = t3lib_iconworks::imagecreatefrom($absFile);
					if ($im<0)	return $iconfile;

						// Converting to gray scale, dimming the icon:
					if (($mode=='disabled') OR ($mode!='futuretiming' && $mode!='no_icon_found' && !(!$mode && $user))) {
						for ($c = 0; $c<ImageColorsTotal($im); $c++) {
							$cols = ImageColorsForIndex($im, $c);
							$newcol = round(($cols['red']+$cols['green']+$cols['blue'])/3);
							$lighten = ($mode=='disabled') ? 2.5 : 2;
							$newcol = round(255-((255-$newcol)/$lighten));
							ImageColorSet($im, $c, $newcol, $newcol, $newcol);
						}
					}
						// Applying user icon, if there are access control on the item:
					if ($user) {
						if ($user < 100)	{	// Apply user number only if lower than 100
							$black = ImageColorAllocate($im, 0, 0, 0);
							imagefilledrectangle($im, 0, 0, (($user>10)?9:5), 8, $black);

							$white = ImageColorAllocate($im, 255, 255, 255);
							imagestring($im, 1, 1, 1, $user, $white);
						}

						$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_group.gif');
						if ($ol_im<0)	return $iconfile;

						t3lib_iconworks::imagecopyresized($im, $ol_im, 0, 0, 0, 0, imagesx($ol_im), imagesy($ol_im), imagesx($ol_im), imagesy($ol_im));
					}
						// Applying overlay based on mode:
					if ($mode) {
						unset($ol_im);
						switch($mode) {
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
							case 'no_icon_found':
								$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_no_icon_found.gif');
							break;
							case 'disabled':
									// is already greyed - nothing more
								$ol_im = 0;
							break;
							case 'hidden':
							default:
								$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_hidden.gif');
							break;
						}
						if ($ol_im<0)	return $iconfile;
						if ($ol_im) {
							t3lib_iconworks::imagecopyresized($im, $ol_im, 0, 0, 0, 0, imagesx($ol_im), imagesy($ol_im), imagesx($ol_im), imagesy($ol_im));
						}
					}
						// Protect-section icon:
					if ($protectSection) {
						$ol_im = t3lib_iconworks::imagecreatefrom($GLOBALS['BACK_PATH'].'gfx/overlay_sub5.gif');
						if ($ol_im<0)	return $iconfile;
						t3lib_iconworks::imagecopyresized($im, $ol_im, 0, 0, 0, 0, imagesx($ol_im), imagesy($ol_im), imagesx($ol_im), imagesy($ol_im));
					}

						// Create the image as file, destroy GD image and return:
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
	 * GD2 cannot manage to combine two indexed-color images without totally spoiling everything.
	 * In class.t3lib_stdgraphic this was solved by combining the images onto a first created true color image
	 * However it has turned out that this method will not work if the indexed png-files contains transparency.
	 * So I had to turn my attention to ImageMagick - my 'enemy of death'.
	 * And so it happend - ImageMagick is now used to combine my two indexed-color images with transparency. And that works.
	 * Of course it works only if ImageMagick is able to create valid png-images - which you cannot be sure of with older versions (still 5+)
	 * The only drawback is (apparently) that IM creates true-color png's. The transparency of these will not be shown by MSIE on windows at this time (although it's straight 0%/100% transparency!) and the file size may be larger.
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
	public static function imagecopyresized(&$im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h) {
		imagecopyresized($im, $cpImg, $Xstart, $Ystart, $cpImgCutX, $cpImgCutY, $w, $h, $w, $h);
	}

	/**
	 * Create new image pointer from input file (either gif/png, in case the wrong format it is converted by t3lib_div::read_png_gif())
	 *
	 * @param	string		Absolute filename of the image file from which to start the icon creation.
	 * @return	mixed		If success, image pointer, otherwise "-1"
	 * @access private
	 * @see t3lib_div::read_png_gif
	 */
	public static function imagecreatefrom($file) {
		$file = t3lib_div::read_png_gif($file, $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']);
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
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
	public static function imagemake($im, $path) {
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
			@ImagePng($im, $path);
		} else {
			@ImageGif($im, $path);
		}
		if (@is_file($path)) {
			t3lib_div::fixPermissions($path);
		}
	}
}
?>
