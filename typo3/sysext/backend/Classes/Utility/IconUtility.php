<?php
namespace TYPO3\CMS\Backend\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Contains class for icon generation in the backend
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
 * Static class, functions called without making a class instance.
 * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8, use IconFactory instead
 */
class IconUtility
{
    /**
     * @var string[]
     */
    public static $fileSpriteIconNames = [
        'htm' => 'mimetypes-text-html',
        'html' => 'mimetypes-text-html',
        'css' => 'mimetypes-text-css',
        'js' => 'mimetypes-text-js',
        'csv' => 'mimetypes-text-csv',
        'php' => 'mimetypes-text-php',
        'php7' => 'mimetypes-text-php',
        'php6' => 'mimetypes-text-php',
        'php5' => 'mimetypes-text-php',
        'php4' => 'mimetypes-text-php',
        'php3' => 'mimetypes-text-php',
        'inc' => 'mimetypes-text-php',
        'ts' => 'mimetypes-text-ts',
        'txt' => 'mimetypes-text-text',
        'class' => 'mimetypes-text-text',
        'tmpl' => 'mimetypes-text-text',
        'jpg' => 'mimetypes-media-image',
        'jpeg' => 'mimetypes-media-image',
        'gif' => 'mimetypes-media-image',
        'png' => 'mimetypes-media-image',
        'bmp' => 'mimetypes-media-image',
        'tif' => 'mimetypes-media-image',
        'tiff' => 'mimetypes-media-image',
        'tga' => 'mimetypes-media-image',
        'psd' => 'mimetypes-media-image',
        'eps' => 'mimetypes-media-image',
        'ai' => 'mimetypes-media-image',
        'svg' => 'mimetypes-media-image',
        'pcx' => 'mimetypes-media-image',
        'avi' => 'mimetypes-media-video',
        'mpg' => 'mimetypes-media-video',
        'mpeg' => 'mimetypes-media-video',
        'mov' => 'mimetypes-media-video',
        'wav' => 'mimetypes-media-audio',
        'mp3' => 'mimetypes-media-audio',
        'mid' => 'mimetypes-media-audio',
        'swf' => 'mimetypes-media-flash',
        'swa' => 'mimetypes-media-flash',
        'exe' => 'mimetypes-application',
        'com' => 'mimetypes-application',
        't3x' => 'mimetypes-compressed',
        't3d' => 'mimetypes-compressed',
        'zip' => 'mimetypes-compressed',
        'tgz' => 'mimetypes-compressed',
        'gz' => 'mimetypes-compressed',
        'pdf' => 'mimetypes-pdf',
        'doc' => 'mimetypes-word',
        'dot' => 'mimetypes-word',
        'docm' => 'mimetypes-word',
        'docx' => 'mimetypes-word',
        'dotm' => 'mimetypes-word',
        'dotx' => 'mimetypes-word',
        'rtf' => 'mimetypes-word',
        'xls' => 'mimetypes-excel',
        'xlsm' => 'mimetypes-excel',
        'xlsx' => 'mimetypes-excel',
        'xltm' => 'mimetypes-excel',
        'xltx' => 'mimetypes-excel',
        'pps' => 'mimetypes-powerpoint',
        'ppsx' => 'mimetypes-powerpoint',
        'ppt' => 'mimetypes-powerpoint',
        'pptm' => 'mimetypes-powerpoint',
        'pptx' => 'mimetypes-powerpoint',
        'potm' => 'mimetypes-powerpoint',
        'potx' => 'mimetypes-powerpoint',
        'odb' => 'mimetypes-open-document-database',
        'odg' => 'mimetypes-open-document-drawing',
        'sxd' => 'mimetypes-open-document-drawing',
        'odf' => 'mimetypes-open-document-formula',
        'sxm' => 'mimetypes-open-document-formula',
        'odp' => 'mimetypes-open-document-presentation',
        'sxi' => 'mimetypes-open-document-presentation',
        'ods' => 'mimetypes-open-document-spreadsheet',
        'sxc' => 'mimetypes-open-document-spreadsheet',
        'odt' => 'mimetypes-open-document-text',
        'sxw' => 'mimetypes-open-document-text',
        'mount' => 'apps-filetree-mount',
        'folder' => 'apps-filetree-folder-default',
        'default' => 'mimetypes-other-other'
    ];

    /**
     * Array of icons rendered by getSpriteIcon(). This contains only icons
     * without overlays or options. These are the most common form.
     *
     * @var array
     */
    protected static $spriteIconCache = [];

    /**
     * Creates the icon for input table/row
     * Returns filename for the image icon, relative to PATH_typo3
     *
     * @param string $table The table name
     * @param array $row The table row ("enablefields" are at least needed for correct icon display and for pages records some more fields in addition!)
     * @param bool $shaded If set, the icon will be grayed/shaded
     * @return string Icon filename
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8, use IconUtility::getSpriteIcon() instead
     */
    public static function getIcon($table, $row = [], $shaded = false)
    {
        GeneralUtility::logDeprecatedFunction();
        // Flags
        // If set, then the usergroup number will NOT be printed unto the icon. NOTICE.
        // The icon is generated only if a default icon for groups is not found... So effectively this is ineffective.
        $doNotRenderUserGroupNumber = true;
        // Shadow
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) && !empty($row['t3ver_state'])) {
            switch (VersionState::cast($row['t3ver_state'])) {
                case new VersionState(VersionState::NEW_PLACEHOLDER):
                    return 'gfx/i/shadow_hide.png';
                    break;
                case new VersionState(VersionState::DELETE_PLACEHOLDER):
                    return 'gfx/i/shadow_delete.png';
                    break;
                case new VersionState(VersionState::MOVE_PLACEHOLDER):
                    return 'gfx/i/shadow_moveto_plh.png';
                    break;
                case new VersionState(VersionState::MOVE_POINTER):
                    return 'gfx/i/shadow_moveto_pointer.png';
                    break;
            }
        }
        // First, find the icon file name. This can depend on configuration in TCA, field values and more:
        if ($table === 'pages') {
            $iconfile = $GLOBALS['PAGES_TYPES'][$row['doktype']]['icon'];
            if (empty($iconfile)) {
                $iconfile = $GLOBALS['PAGES_TYPES']['default']['icon'];
            }
        }

        if (empty($iconfile)) {
            $iconfile = $GLOBALS['TCA'][$table]['ctrl']['iconfile'] ?: $table . '.gif';
        }

        // Setting path of iconfile if not already set. Default is "gfx/i/"
        if (!strstr($iconfile, '/')) {
            $iconfile = 'gfx/i/' . $iconfile;
        }
        // Setting the absolute path where the icon should be found as a file:
        if (substr($iconfile, 0, 3) == '../') {
            $absfile = PATH_site . substr($iconfile, 3);
        } else {
            $absfile = PATH_typo3 . $iconfile;
        }
        // Initializing variables, all booleans except otherwise stated:
        $hidden = false;
        $timing = false;
        $futuretiming = false;
        // In fact an integer value
        $user = false;
        $deleted = false;
        // Set, if a page-record (only pages!) has the extend-to-subpages flag set.
        $protectSection = false;
        $noIconFound = (bool)$row['_NO_ICON_FOUND'];
        // + $shaded which is also boolean!
        // Icon state based on "enableFields":
        if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
            $enCols = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'];
            // If "hidden" is enabled:
            if ($enCols['disabled']) {
                if ($row[$enCols['disabled']]) {
                    $hidden = true;
                }
            }
            // If a "starttime" is set and higher than current time:
            if ($enCols['starttime']) {
                if ($GLOBALS['EXEC_TIME'] < (int)$row[$enCols['starttime']]) {
                    $timing = true;
                    // And if "endtime" is NOT set:
                    if ((int)$row[$enCols['endtime']] === 0) {
                        $futuretiming = true;
                    }
                }
            }
            // If an "endtime" is set:
            if ($enCols['endtime']) {
                if ((int)$row[$enCols['endtime']] > 0) {
                    if ((int)$row[$enCols['endtime']] < $GLOBALS['EXEC_TIME']) {
                        // End-timing applies at this point.
                        $timing = true;
                    } else {
                        // End-timing WILL apply in the future for this element.
                        $futuretiming = true;
                    }
                }
            }
            // If a user-group field is set:
            if ($enCols['fe_group']) {
                $user = $row[$enCols['fe_group']];
                if ($user && $doNotRenderUserGroupNumber) {
                    $user = 100;
                }
            }
        }
        // If "deleted" flag is set (only when listing records which are also deleted!)
        if ($col = $row[$GLOBALS['TCA'][$table]['ctrl']['delete']]) {
            $deleted = true;
        }
        // Detecting extendToSubpages (for pages only)
        if ($table == 'pages' && $row['extendToSubpages'] && ($hidden || $timing || $futuretiming || $user)) {
            $protectSection = true;
        }
        // If ANY of the booleans are set it means we have to alter the icon:
        if ($hidden || $timing || $futuretiming || $user || $deleted || $shaded || $noIconFound) {
            $flags = '';
            $string = '';
            if ($deleted) {
                $string = 'deleted';
                $flags = 'd';
            } elseif ($noIconFound) {
                // This is ONLY for creating icons with "?" on easily...
                $string = 'no_icon_found';
                $flags = 'x';
            } else {
                if ($hidden) {
                    $string .= 'hidden';
                }
                if ($timing) {
                    $string .= 'timing';
                }
                if (!$string && $futuretiming) {
                    $string = 'futuretiming';
                }
                $flags .= ($hidden ? 'h' : '') . ($timing ? 't' : '') . ($futuretiming ? 'f' : '') . ($user ? 'u' : '') . ($protectSection ? 'p' : '') . ($shaded ? 's' : '');
            }
            // Create tagged icon file name:
            $iconFileName_stateTagged = preg_replace('/.([[:alnum:]]+)$/', '__' . $flags . '.\\1', basename($iconfile));
            // Check if tagged icon file name exists (a tagged icon means the icon base name with the flags added between body and extension of the filename, prefixed with underscore)
            if (@is_file((dirname($absfile) . '/' . $iconFileName_stateTagged)) || @is_file(($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'] . '/' . dirname($iconfile) . '/' . $iconFileName_stateTagged))) {
                // Look for [iconname]_xxxx.[ext]
                return dirname($iconfile) . '/' . $iconFileName_stateTagged;
            } else {
                // Otherwise, create the icon:
                $theRes = self::makeIcon($GLOBALS['BACK_PATH'] . $iconfile, $string, $user, $protectSection, $absfile, $iconFileName_stateTagged);
                return $theRes;
            }
        } else {
            return $iconfile;
        }
    }

    /**
     * Returns the src=... for the input $src value OR any alternative found in $TBE_STYLES['skinImg']
     * Used for skinning the TYPO3 backend with an alternative set of icons
     *
     * @param string $backPath Current backpath to PATH_typo3 folder
     * @param string $src Icon file name relative to PATH_typo3 folder
     * @param string $wHattribs Default width/height, defined like 'width="12" height="14"'
     * @param int $outputMode Mode: 0 (zero) is default and returns src/width/height. 1 returns value of src+backpath, 2 returns value of w/h.
     * @return string Returns ' src="[backPath][src]" [wHattribs]'
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     * @see skinImgFile()
     */
    public static function skinImg($backPath, $src, $wHattribs = '', $outputMode = 0)
    {
        GeneralUtility::logDeprecatedFunction();
        static $cachedSkinImages = [];
        $imageId = md5($backPath . $src . $wHattribs . $outputMode);
        if (isset($cachedSkinImages[$imageId])) {
            return $cachedSkinImages[$imageId];
        }
        // Setting source key. If the icon is referred to inside an extension, we homogenize the prefix to "ext/":
        $srcKey = preg_replace('/^(\\.\\.\\/typo3conf\\/ext|sysext|ext)\\//', 'ext/', $src);
        // LOOKING for alternative icons:
        if ($GLOBALS['TBE_STYLES']['skinImg'][$srcKey]) {
            // Slower or faster with is_array()? Could be used.
            list($src, $wHattribs) = $GLOBALS['TBE_STYLES']['skinImg'][$srcKey];
        } elseif ($GLOBALS['TBE_STYLES']['skinImgAutoCfg']) {
            // Otherwise, test if auto-detection is enabled:
            // Search for alternative icon automatically:
            $fExt = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['forceFileExtension'];
            $scaleFactor = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['scaleFactor'] ?: 1;
            // Scaling factor
            $lookUpName = $fExt ? preg_replace('/\\.[[:alnum:]]+$/', '', $srcKey) . '.' . $fExt : $srcKey;
            // Set filename to look for
            if ($fExt && !@is_file(($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'] . $lookUpName))) {
                // Fallback to original filename if icon with forced extension doesn't exists
                $lookUpName = $srcKey;
            }
            // If file is found:
            if (@is_file(($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'] . $lookUpName))) {
                // If there is a file...
                $iInfo = @getimagesize(($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['absDir'] . $lookUpName));
                // Get width/height:
                // Set $src and $wHattribs:
                $src = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['relDir'] . $lookUpName;
                $wHattribs = 'width="' . round($iInfo[0] * $scaleFactor) . '" height="' . round($iInfo[1] * $scaleFactor) . '"';
            }
            // In any case, set currect src / wHattrib - this way we make sure that an entry IS found next time we hit the function,
            // regardless of whether it points to an alternative icon or just the current.
            $GLOBALS['TBE_STYLES']['skinImg'][$srcKey] = [$src, $wHattribs];
        }
        // Rendering disabled (greyed) icons using _i (inactive) as name suffix ("_d" is already used)
        $matches = [];
        $srcBasename = basename($src);
        if (preg_match('/(.*)_i(\\....)$/', $srcBasename, $matches)) {
            $temp_path = dirname(PATH_thisScript) . '/';
            if (!@is_file(($temp_path . $backPath . $src))) {
                $srcOrg = preg_replace('/_i' . preg_quote($matches[2], '/') . '$/', $matches[2], $src);
                $src = self::makeIcon($backPath . $srcOrg, 'disabled', 0, false, $temp_path . $backPath . $srcOrg, $srcBasename);
            }
        }
        // Return icon source/wHattributes:
        $output = '';
        switch ($outputMode) {
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
     * @param string $iconfile Original unprocessed Icon file, relative path to PATH_typo3
     * @param string $mode Mode string, eg. "deleted" or "futuretiming" determining how the icon will look
     * @param int $user The number of the fe_group record uid if applicable
     * @param bool $protectSection Flag determines if the protected-section icon should be applied.
     * @param string $absFile Absolute path to file from which to create the icon.
     * @param string $iconFileName_stateTagged The filename that this icon should have had, basically [icon base name]_[flags].[extension] - used for part of temporary filename
     * @return string Filename relative to PATH_typo3
     * @access private
     */
    public static function makeIcon($iconfile, $mode, $user, $protectSection, $absFile, $iconFileName_stateTagged)
    {
        $iconFileName = GeneralUtility::shortMD5(($iconfile . '|' . $mode . '|-' . $user . '|' . $protectSection)) . '_' . $iconFileName_stateTagged . '.' . ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'] ? 'png' : 'gif');
        $mainpath = '../typo3temp/Icons/' . $iconFileName;
        $path = PATH_site . 'typo3temp/Icons/' . $iconFileName;
        if (file_exists($path)) {
            // Returns if found in ../typo3temp/Icons/
            return $mainpath;
        } else {
            // Makes icon:
            if (file_exists($absFile)) {
                if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
                    // Create image pointer, if possible
                    $im = self::imagecreatefrom($absFile);
                    if ($im < 0) {
                        return $iconfile;
                    }
                    // Converting to gray scale, dimming the icon:
                    if ($mode == 'disabled' or $mode != 'futuretiming' && $mode != 'no_icon_found' && !(!$mode && $user)) {
                        $totalImageColors = imagecolorstotal($im);
                        for ($c = 0; $c < $totalImageColors; $c++) {
                            $cols = imagecolorsforindex($im, $c);
                            $newcol = round(($cols['red'] + $cols['green'] + $cols['blue']) / 3);
                            $lighten = $mode == 'disabled' ? 2.5 : 2;
                            $newcol = round(255 - (255 - $newcol) / $lighten);
                            imagecolorset($im, $c, $newcol, $newcol, $newcol);
                        }
                    }
                    // Applying user icon, if there are access control on the item:
                    if ($user) {
                        if ($user < 100) {
                            // Apply user number only if lower than 100
                            $black = imagecolorallocate($im, 0, 0, 0);
                            imagefilledrectangle($im, 0, 0, $user > 10 ? 9 : 5, 8, $black);
                            $white = imagecolorallocate($im, 255, 255, 255);
                            imagestring($im, 1, 1, 1, $user, $white);
                        }
                        $ol_im = self::imagecreatefrom($GLOBALS['BACK_PATH'] . 'typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_group.gif');
                        if ($ol_im < 0) {
                            return $iconfile;
                        }
                        self::imagecopyresized($im, $ol_im, 0, 0, 0, 0, imagesx($ol_im), imagesy($ol_im), imagesx($ol_im), imagesy($ol_im));
                    }
                    // Applying overlay based on mode:
                    if ($mode) {
                        unset($ol_im);
                        switch ($mode) {
                            case 'deleted':
                                $ol_im = self::imagecreatefrom($GLOBALS['BACK_PATH'] . 'typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_deleted.gif');
                                break;
                            case 'futuretiming':
                                $ol_im = self::imagecreatefrom($GLOBALS['BACK_PATH'] . 'typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_timing.gif');
                                break;
                            case 'timing':
                                $ol_im = self::imagecreatefrom($GLOBALS['BACK_PATH'] . 'typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_timing.gif');
                                break;
                            case 'hiddentiming':
                                $ol_im = self::imagecreatefrom($GLOBALS['BACK_PATH'] . 'typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_hidden_timing.gif');
                                break;
                            case 'no_icon_found':
                                $ol_im = self::imagecreatefrom($GLOBALS['BACK_PATH'] . 'typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_no_icon_found.gif');
                                break;
                            case 'disabled':
                                // is already greyed - nothing more
                                $ol_im = 0;
                                break;
                            case 'hidden':

                            default:
                                $ol_im = self::imagecreatefrom($GLOBALS['BACK_PATH'] . 'typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_hidden.gif');
                        }
                        if ($ol_im < 0) {
                            return $iconfile;
                        }
                        if ($ol_im) {
                            self::imagecopyresized($im, $ol_im, 0, 0, 0, 0, imagesx($ol_im), imagesy($ol_im), imagesx($ol_im), imagesy($ol_im));
                        }
                    }
                    // Protect-section icon:
                    if ($protectSection) {
                        $ol_im = self::imagecreatefrom($GLOBALS['BACK_PATH'] . 'typo3/sysext/backend/Resources/Public/Images/Overlay/overlay_sub5.gif');
                        if ($ol_im < 0) {
                            return $iconfile;
                        }
                        self::imagecopyresized($im, $ol_im, 0, 0, 0, 0, imagesx($ol_im), imagesy($ol_im), imagesx($ol_im), imagesy($ol_im));
                    }
                    // Create the image as file, destroy GD image and return:
                    $targetDirectory = dirname($path);
                    if (!@is_dir($targetDirectory)) {
                        GeneralUtility::mkdir($targetDirectory);
                    }
                    @self::imagemake($im, $path);
                    GraphicalFunctions::gifCompress($path, 'IM');
                    imagedestroy($im);

                    return $mainpath;
                } else {
                    return $iconfile;
                }
            } else {
                return $GLOBALS['BACK_PATH'] . 'typo3/sysext/backend/Resources/Public/Images/Overlay/default.gif';
            }
        }
    }

    /**
     * The necessity of using this function for combining two images if GD is version 2 is that
     * GD2 cannot manage to combine two indexed-color images without totally spoiling everything.
     * In class \TYPO3\CMS\Core\Imaging\GraphicalFunctions this was solved by combining the images
     * onto a first created true color image.
     * However it has turned out that this method will not work if the indexed png-files contains transparency.
     * So I had to turn my attention to ImageMagick - my 'enemy of death'.
     * And so it happened - ImageMagick is now used to combine my two indexed-color images with transparency. And that works.
     * Of course it works only if ImageMagick is able to create valid png-images - which you cannot be sure of with older versions (still 5+)
     * The only drawback is (apparently) that IM creates true-color png's. The transparency of these will not be shown by MSIE on windows at this time (although it's straight 0%/100% transparency!) and the file size may be larger.
     *
     * @param resource $destinationImage Destination image
     * @param resource $sourceImage Source image
     * @param int $destinationX Destination x-coordinate
     * @param int $destinationY Destination y-coordinate
     * @param int $sourceX Source x-coordinate
     * @param int $sourceY Source y-coordinate
     * @param int $destinationWidth Destination width
     * @param int $destinationHeight Destination height
     * @param int $sourceWidth Source width
     * @param int $sourceHeight Source height
     * @return void
     * @access private
     * @see \TYPO3\CMS\Core\Imaging\GraphicalFunctions::imagecopyresized()
     */
    public static function imagecopyresized(&$destinationImage, $sourceImage, $destinationX, $destinationY, $sourceX, $sourceY, $destinationWidth, $destinationHeight, $sourceWidth, $sourceHeight)
    {
        imagecopyresized($destinationImage, $sourceImage, $destinationX, $destinationY, $sourceX, $sourceY, $destinationWidth, $destinationHeight, $sourceWidth, $sourceHeight);
    }

    /**
     * Create new image pointer from input file (either gif/png, in case the wrong format it is converted by \TYPO3\CMS\Core\Imaging\GraphicalFunctions::readPngGif())
     *
     * @param string $file Absolute filename of the image file from which to start the icon creation.
     * @return resource|int If success, image pointer, otherwise -1
     * @access private
     * @see \TYPO3\CMS\Core\Imaging\GraphicalFunctions::readPngGif
     */
    public static function imagecreatefrom($file)
    {
        $file = GraphicalFunctions::readPngGif($file, $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']);
        if (!$file) {
            return -1;
        }

        return $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'] ? imagecreatefrompng($file) : imagecreatefromgif($file);
    }

    /**
     * Write the icon in $im pointer to $path
     *
     * @param resource $im Pointer to GDlib image resource
     * @param string $path Absolute path to the filename in which to write the icon.
     * @return void
     * @access private
     */
    public static function imagemake($im, $path)
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
            @imagepng($im, $path);
        } else {
            @imagegif($im, $path);
        }
        if (@is_file($path)) {
            GeneralUtility::fixPermissions($path);
        }
    }

    /**********************************************
     * SPRITE ICON API
     *
     * The Sprite Icon API helps you to quickly get the HTML for any icon you want
     * this is typically wrapped in a <span> tag with corresponding CSS classes that
     * will be responsible for the
     *
     * There are four ways to use this API:
     *
     * 1) for any given TCA record
     *  $spriteIconHtml = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $row);
     *
     * 2) for any given File of Folder object
     *  $spriteIconHtml = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForResource($fileOrFolderObject);
     *
     * 3) for any given file
     *  $spriteIconHtml = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('myimage.png');
     *
     * 4) for any other icon you know the name
     *  $spriteIconHtml = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open');
     *
     **********************************************/
    /**
     * This generic method is used throughout the TYPO3 Backend to show icons
     * in any variation which are not bound to any resource object (see getSpriteIconForResource)
     * or database record (see getSpriteIconForRecord)
     *
     * Generates a HTML tag with proper CSS classes. The TYPO3 skin has
     * defined these CSS classes already to have a pre-defined background image,
     * and the correct background-position to show the necessary icon.
     *
     * If no options or overlays are given, the icon will be cached in
     * $spriteIconCache.
     *
     * @param string $iconName The name of the icon to fetch
     * @param array $options An associative array with additional options and attributes for the tag. by default, the key is the name of the attribute, and the value is the parameter string that is set. However, there are some additional special reserved keywords that can be used as keys: "html" (which is the HTML that will be inside the icon HTML tag), "tagName" (which is an alternative tagName than "span"), and "class" (additional class names that will be merged with the sprite icon CSS classes)
     * @param array $overlays An associative array with the icon-name as key, and the options for this overlay as an array again (see the parameter $options again)
     * @return string The full HTML tag (usually a <span>)
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8, use IconFactory->getIcon instead
     * @access public
     */
    public static function getSpriteIcon($iconName, array $options = [], array $overlays = [])
    {
        GeneralUtility::logDeprecatedFunction();

        // First check if an icon is registered in IconRegistry and
        // return if icon is available.
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        if ($iconRegistry->isRegistered($iconName)) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            return $iconFactory->getIcon($iconName, Icon::SIZE_SMALL)->render();
        }

        // Check if icon can be cached and return cached version if present
        if (empty($options) && empty($overlays)) {
            if (isset(static::$spriteIconCache[$iconName])) {
                return static::$spriteIconCache[$iconName];
            }
            $iconIsCacheable = true;
        } else {
            $iconIsCacheable = false;
        }

        $innerHtml = isset($options['html']) ? $options['html'] : null;
        $tagName = isset($options['tagName']) ? $options['tagName'] : null;

        // Deal with the overlays
        foreach ($overlays as $overlayIconName => $overlayOptions) {
            $overlayOptions['html'] = $innerHtml;
            $overlayOptions['class'] = (isset($overlayOptions['class']) ? $overlayOptions['class'] . ' ' : '') . 't3-icon-overlay';
            $innerHtml = self::getSpriteIcon($overlayIconName, $overlayOptions);
        }

        $availableIcons = isset($GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable'])
            ? (array)$GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable']
            : [];
        if ($iconName !== 'empty-empty' && !in_array($iconName, $availableIcons, true)) {
            $iconName = 'status-status-icon-missing';
        }

        // Create the CSS class
        $options['class'] = self::getSpriteIconClasses($iconName) . (isset($options['class']) ? ' ' . $options['class'] : '');
        unset($options['html'], $options['tagName']);
        $spriteHtml = self::buildSpriteHtmlIconTag($options, $innerHtml, $tagName);

        // Store result in cache if possible
        if ($iconIsCacheable) {
            static::$spriteIconCache[$iconName] = $spriteHtml;
        }

        return $spriteHtml;
    }

    /**
     * This method is used throughout the TYPO3 Backend to show icons for a file type
     *
     * Generates a HTML tag with proper CSS classes. The TYPO3 skin has defined these CSS classes
     * already to have a pre-defined background image, and the correct background-position to show
     * the necessary icon.
     *
     * @param string $fileExtension The name of the icon to fetch, can be a file extension, full file path or one of the special keywords "folder" or "mount
     * @param array $options not used anymore
     * @return string The full HTML tag
     * @access public
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use IconFactory::getIconForFile directly
     */
    public static function getSpriteIconForFile($fileExtension, array $options = null)
    {
        GeneralUtility::logDeprecatedFunction();
        if ($options !== null) {
            GeneralUtility::deprecationLog('The parameter $options of IconUtility::getSpriteIconForFile is not used anymore');
        }
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        return $iconFactory->getIconForFileExtension($fileExtension)->render();
    }

    /**
     * Generates the spriteicon css classes name for a given path or fileExtension
     * usually called from getSpriteIconForFile or ExtJs Provider
     *
     * @param string $fileExtension FileExtension can be jpg, gif etc, but also be 'mount' or 'folder', but can also be a full path which will be resolved then
     * @return string The string of the CSS class, see \TYPO3\CMS\Backend\Utility\IconUtility::$fileSpriteIconNames
     * @access private
     */
    public static function mapFileExtensionToSpriteIconClass($fileExtension)
    {
        return self::getSpriteIconClasses(self::mapFileExtensionToSpriteIconName($fileExtension));
    }

    /**
     * Generates the spriteicon name for a given path or fileExtension
     * usually called from mapFileExtensionToSpriteIconClass and tceforms
     *
     * @param string $fileExtension FileExtension can be jpg, gif etc, but also be 'mount' or 'folder', but can also be a full path which will be resolved then
     * @return string The string of the CSS class, see \TYPO3\CMS\Backend\Utility\IconUtility::$fileSpriteIconNames
     * @access private
     */
    public static function mapFileExtensionToSpriteIconName($fileExtension)
    {
        // If the file is a whole file with name etc (mainly, if it has a "." or a "/"),
        // then it is checked whether it is a valid directory
        if (strpos($fileExtension, '.') !== false || strpos($fileExtension, '/') !== false) {
            // Check if it is a directory
            $filePath = dirname(GeneralUtility::getIndpEnv('SCRIPT_FILENAME')) . '/' . $GLOBALS['BACK_PATH'] . $fileExtension;
            $path = GeneralUtility::resolveBackPath($filePath);
            if (is_dir($path) || substr($fileExtension, -1) === '/' || substr($fileExtension, -1) === '\\') {
                $fileExtension = 'folder';
            } else {
                if (($pos = strrpos($fileExtension, '.')) !== false) {
                    $fileExtension = strtolower(substr($fileExtension, $pos + 1));
                } else {
                    $fileExtension = 'default';
                }
            }
        }
        // If the file extension is not valid
        // then use the default one
        if (!isset(self::$fileSpriteIconNames[$fileExtension])) {
            $fileExtension = 'default';
        }
        $iconName = self::$fileSpriteIconNames[$fileExtension];

        return $iconName;
    }

    /**
     * This method is used throughout the TYPO3 Backend to show icons for a DB record
     *
     * Generates a HTML tag with proper CSS classes. The TYPO3 skin has defined these CSS classes
     * already to have a pre-defined background image, and the correct background-position to show
     * the necessary icon.
     *
     * @param string $table The TCA table name
     * @param array $row The DB record of the TCA table
     * @param array $options not used anymore
     * @return string The full HTML tag (usually a <span>)
     * @access public
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public static function getSpriteIconForRecord($table, array $row, array $options = null)
    {
        GeneralUtility::logDeprecatedFunction();
        if ($options !== null) {
            GeneralUtility::deprecationLog('The parameter $options of IconUtility::getSpriteIconForRecord is not used anymore');
        }
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        return $iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render();
    }

    /**
     * This method is used throughout the TYPO3 Backend to show icons for files and folders
     *
     * The method takes care of the translation of file extension to proper icon and for folders
     * it will return the icon depending on the role of the folder.
     *
     * If the given resource is a folder there are some additional options that can be used:
     *  - mount-root => TRUE (to indicate this is the root of a mount)
     *  - folder-open => TRUE (to indicate that the folder is opened in the file tree)
     *
     * There is a hook in place to manipulate the icon name and overlays.
     *
     * @param \TYPO3\CMS\Core\Resource\ResourceInterface $resource
     * @param array $options An associative array with additional options and attributes for the tag. See self::getSpriteIcon()
     * @param array $overlays not used anymore
     * @return string
     * @throws \UnexpectedValueException
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public static function getSpriteIconForResource(\TYPO3\CMS\Core\Resource\ResourceInterface $resource, array $options = [], array $overlays = null)
    {
        GeneralUtility::logDeprecatedFunction();
        if ($overlays !== null) {
            GeneralUtility::deprecationLog('The parameter $overlays of IconUtility::getSpriteIconForResource is not used anymore');
        }
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        return $iconFactory->getIconForResource($resource, Icon::SIZE_SMALL, null, $options)->render();
    }

    /**
     * this helper functions looks up the column that is used for the type of
     * the chosen TCA table. And then fetches the corresponding class
     * based on the chosen iconsprite class in this TCA
     * The TCA looks up
     * - [ctrl][typeicon_column]
     * -
     * This method solely takes care of the type of this record, not any
     * statuses, used for overlays.
     * You should not use this directly besides if you need classes for ExtJS iconCls.
     *
     * see ext:core/Configuration/TCA/pages.php for an example with the TCA table "pages"
     *
     * @param string $table The TCA table
     * @param array $row The selected record
     * @return string The CSS class for the sprite icon of that DB record
     * @access private
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public static function mapRecordTypeToSpriteIconClass($table, array $row)
    {
        GeneralUtility::logDeprecatedFunction();
        return self::getSpriteIconClasses(self::mapRecordTypeToSpriteIconName($table, $row));
    }

    /**
     * this helper functions looks up the column that is used for the type of
     * the chosen TCA table. And then fetches the corresponding iconname
     * based on the chosen iconsprite class in this TCA
     * The TCA looks up
     * - [ctrl][typeicon_column]
     * -
     * This method solely takes care of the type of this record, not any
     * statuses, used for overlays.
     * You should not use this directly besides if you need it in tceforms/core classes
     *
     * see ext:core/Configuration/TCA/pages.php for an example with the TCA table "pages"
     *
     * @param string $table The TCA table
     * @param array $row The selected record
     * @return string The CSS class for the sprite icon of that DB record
     * @access private
     */
    public static function mapRecordTypeToSpriteIconName($table, array $row)
    {
        $recordType = [];
        $ref = null;
        if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_column'])) {
            $column = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
            if (isset($row[$column])) {
                $recordType[1] = $row[$column];
            } else {
                $recordType[1] = 'default';
            }
            // Workaround to give nav_hide pages a complete different icon
            // Although it's not a separate doctype
            // and to give root-pages an own icon
            if ($table === 'pages') {
                if ($row['nav_hide']) {
                    $recordType[2] = $recordType[1] . '-hideinmenu';
                }
                if ($row['is_siteroot']) {
                    $recordType[3] = $recordType[1] . '-root';
                }
                if ($row['module']) {
                    $recordType[4] = 'contains-' . $row['module'];
                }
                if ((int)$row['content_from_pid'] > 0) {
                    $recordType[4] = (int)$row['nav_hide'] === 0 ? 'page-contentFromPid' : 'page-contentFromPid-hideinmenu';
                }
            }
            if (is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])) {
                foreach ($recordType as $key => $type) {
                    if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type])) {
                        $recordType[$key] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type];
                    } else {
                        unset($recordType[$key]);
                    }
                }
                $recordType[0] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'];
                if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['mask'])) {
                    $recordType[5] = str_replace('###TYPE###', $row[$column], $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['mask']);
                }
                if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['userFunc'])) {
                    $parameters = ['row' => $row];
                    $recordType[6] = GeneralUtility::callUserFunction($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['userFunc'], $parameters, $ref);
                }
            } else {
                foreach ($recordType as &$type) {
                    $type = 'tcarecords-' . $table . '-' . $type;
                }
                unset($type);
                $recordType[0] = 'tcarecords-' . $table . '-default';
            }
        } else {
            if (is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])) {
                $recordType[0] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'];
            } else {
                $recordType[0] = 'tcarecords-' . $table . '-default';
            }
        }
        krsort($recordType);
        if (is_array($GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable'])) {
            foreach ($recordType as $iconName) {
                if (in_array($iconName, $GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable'])) {
                    return $iconName;
                }
            }
        }

        return 'status-status-icon-missing';
    }

    /**
     * generic method to create the final CSS classes based on the sprite icon name
     * with the base class and splits the name into parts
     * is usually called by the methods that are responsible for fetching the names
     * out of the file name, or the record type
     *
     * @param string $iconName Iconname like 'actions-document-new'
     * @return string A list of all CSS classes needed for the HTML tag
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public static function getSpriteIconClasses($iconName)
    {
        GeneralUtility::logDeprecatedFunction();
        $cssClasses = ($baseCssClass = 't3-icon');
        $parts = explode('-', $iconName);
        if (count($parts) > 1) {
            // Will be something like "t3-icon-actions"
            $cssClasses .= ' ' . ($baseCssClass . '-' . $parts[0]);
            // Will be something like "t3-icon-actions-document"
            $cssClasses .= ' ' . ($baseCssClass . '-' . $parts[0] . '-' . $parts[1]);
            // Will be something like "t3-icon-document-new"
            $cssClasses .= ' ' . ($baseCssClass . '-' . substr($iconName, (strlen($parts[0]) + 1)));
        }

        return $cssClasses;
    }

    /**
     * low level function that generates the HTML tag for the sprite icon
     * is usually called by the three API classes (getSpriteIcon, getSpriteIconForFile, getSpriteIconForRecord)
     * it does not care about classes or anything else, but just plainly builds the HTML tag
     *
     * @param array $tagAttributes An associative array of additional tagAttributes for the HTML tag
     * @param string $innerHtml The content within the tag, a "&nbsp;" by default
     * @param string $tagName The name of the HTML element that should be used (span by default)
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     * @return string The sprite html icon tag
     */
    protected static function buildSpriteHtmlIconTag(array $tagAttributes, $innerHtml = null, $tagName = null)
    {
        GeneralUtility::logDeprecatedFunction();

        $innerHtml = $innerHtml === null ? ' ' : $innerHtml;
        $tagName = $tagName === null ? 'span' : $tagName;
        $attributes = '';
        foreach ($tagAttributes as $attribute => $value) {
            $attributes .= ' ' . htmlspecialchars($attribute) . '="' . htmlspecialchars($value) . '"';
        }

        return '<' . $tagName . $attributes . '>' . $innerHtml . '</' . $tagName . '>';
    }
}
