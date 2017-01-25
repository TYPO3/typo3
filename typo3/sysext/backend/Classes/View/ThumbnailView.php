<?php
namespace TYPO3\CMS\Backend\View;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class for generating a thumbnail from the input parameters given to the script
 *
 * Input GET var, &file: relative or absolute reference to an imagefile. WILL be validated against PATH_site / lockRootPath
 * Input GET var, &size: integer-values defining size of thumbnail, format '[int]' or '[int]x[int]'
 *
 * Relative paths MUST BE the first two characters ONLY: eg: '../dir/file.gif', otherwise it is expect to be absolute
 * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8, use the corresponding Resource objects and Processing functionality
 */
class ThumbnailView
{
    /**
     * The output directory of temporary files in PATH_site
     *
     * @var string
     */
    public $outdir = 'typo3temp/';

    /**
     * @var string
     */
    public $output = '';

    /**
     * @var string
     */
    public $sizeDefault = '64x64';

    /**
     * Coming from $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
     *
     * @var string
     */
    public $imageList;

    /**
     * will hold the file Object
     *
     * @var \TYPO3\CMS\Core\Resource\File
     */
    public $image;

    /**
     * Holds the input size (GET: size)
     *
     * @var string
     */
    public $size;

    /**
     * Last modification time of the supplied file
     *
     * @var int
     */
    public $mTime = 0;

    /**
     * Initialize; reading parameters with GPvar and checking file path
     *
     * @return void
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     */
    public function init()
    {
        GeneralUtility::deprecationLog('The class ThumbnailView is deprecated since TYPO3 CMS 7 and will be removed with TYPO3 CMS 8, use the corresponding Resource objects and Processing functionality');
        // Setting GPvars:
        // Only needed for MD5 sum calculation of backwards-compatibility uploads/ files thumbnails.
        $size = GeneralUtility::_GP('size');
        $filePathOrCombinedFileIdentifier = rawurldecode(GeneralUtility::_GP('file'));
        $md5sum = GeneralUtility::_GP('md5sum');
        // Image extension list is set:
        // valid extensions. OBS: No spaces in the list, all lowercase...
        $this->imageList = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
        // Check if we got a combined file identifier of the form storageUid:fileIdentifer.
        // We need to distinguish it from absolute Windows paths by cbecking for an integer as first part.
        $parts = GeneralUtility::trimExplode(':', $filePathOrCombinedFileIdentifier);
        // Best case: we get a sys_file UID
        if (!empty($filePathOrCombinedFileIdentifier) && MathUtility::canBeInterpretedAsInteger($filePathOrCombinedFileIdentifier)) {
            /** @var File $filePathOrCombinedFileIdentifier */
            $fileObject = ResourceFactory::getInstance()->getFileObject($filePathOrCombinedFileIdentifier);
        } elseif (count($parts) <= 1 || !MathUtility::canBeInterpretedAsInteger($parts[0])) {
            // @todo Historically, the input parameter could also be an absolute path. This should be supported again to stay compatible.
            // We assume the FilePath to be a relative file path (as in backwards compatibility mode)
            $relativeFilePath = $filePathOrCombinedFileIdentifier;
            // The incoming relative path is relative to the typo3/ directory, but we need it relative to PATH_site. This is corrected here:
            if (substr($relativeFilePath, 0, 3) == '../') {
                $relativeFilePath = substr($relativeFilePath, 3);
            } else {
                $relativeFilePath = 'typo3/' . $relativeFilePath;
            }
            $relativeFilePath = ltrim($relativeFilePath, '/');
            $mTime = 0;
            // Checking for backpath and double slashes + the thumbnail can be made from files which are in the PATH_site OR the lockRootPath only!
            if (GeneralUtility::isAllowedAbsPath(PATH_site . $relativeFilePath)) {
                $mTime = filemtime(PATH_site . $relativeFilePath);
            }
            if (strstr($relativeFilePath, '../') !== false) {
                // Maybe this could be relaxed to not throw an error as long as the path is still within PATH_site
                $this->errorGif('File path', 'must not contain', '"../"');
            }
            if ($relativeFilePath && file_exists(PATH_site . $relativeFilePath)) {
                // Check file extension:
                $reg = [];
                if (preg_match('/(.*)\\.([^\\.]*$)/', $relativeFilePath, $reg)) {
                    $ext = strtolower($reg[2]);
                    $ext = $ext == 'jpeg' ? 'jpg' : $ext;
                    if (!GeneralUtility::inList($this->imageList, $ext)) {
                        $this->errorGif('Not imagefile!', $ext, basename($relativeFilePath));
                    }
                } else {
                    $this->errorGif('Not imagefile!', 'No ext!', basename($relativeFilePath));
                }
            } else {
                $this->errorGif('Input file not found.', 'not found in thumbs.php', basename($relativeFilePath));
            }
            // Do an MD5 check to prevent viewing of images without permission
            $OK = false;
            if ($mTime) {
                // Always use the absolute path for this check!
                $check = basename($relativeFilePath) . ':' . $mTime . ':' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
                if (GeneralUtility::shortMD5($check) === (string)$md5sum) {
                    $OK = true;
                }
            }
            $combinedIdentifier = '0:' . $relativeFilePath;
        } else {
            $combinedIdentifier = $filePathOrCombinedFileIdentifier;
            $OK = false;
        }
        if (empty($fileObject)) {
            $fileObject = ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier($combinedIdentifier);
        }
        if (empty($OK)) {
            $OK = $fileObject !== null && $fileObject->checkActionPermission('read') && $fileObject->calculateChecksum() == $md5sum;
        }
        if ($OK) {
            $this->image = $fileObject;
            $this->size = $size;
        } else {
            // Hide the path to the document root;
            throw new \RuntimeException('TYPO3 Fatal Error: The requested image does not exist and/or MD5 checksum did not match. If the target file exists and its file name contains special characters, the setting of $TYPO3_CONF_VARS[SYS][systemLocale] might be wrong.', 1270853950);
        }
    }

    /**
     * Create the thumbnail
     * Will exit before return if all is well.
     *
     * @return void
     */
    public function main()
    {
        // Clean output buffer to ensure no extraneous output exists
        ob_clean();
        // If file exists, we make a thumbnail of the file.
        if (is_object($this->image)) {
            // Check file extension:
            if ($this->image->getExtension() == 'ttf') {
                // Make font preview... (will not return)
                $this->fontGif($this->image);
            } elseif ($this->image->getType() != File::FILETYPE_IMAGE && !GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $this->image->getExtension())) {
                $this->errorGif('Not imagefile!', 'No ext!', $this->image->getName());
            }
            // ... so we passed the extension test meaning that we are going to make a thumbnail here:
            // default
            if (!$this->size) {
                $this->size = $this->sizeDefault;
            }
            // I added extra check, so that the size input option could not be fooled to pass other values.
            // That means the value is exploded, evaluated to an integer and the imploded to [value]x[value].
            // Furthermore you can specify: size=340 and it'll be translated to 340x340.
            // explodes the input size (and if no "x" is found this will add size again so it is the same for both dimensions)
            $sizeParts = explode('x', $this->size . 'x' . $this->size);
            // Cleaning it up, only two parameters now.
            $sizeParts = [MathUtility::forceIntegerInRange($sizeParts[0], 1, 1000), MathUtility::forceIntegerInRange($sizeParts[1], 1, 1000)];
            // Imploding the cleaned size-value back to the internal variable
            $this->size = implode('x', $sizeParts);
            // Getting max value
            $sizeMax = max($sizeParts);
            // Init
            $outpath = PATH_site . $this->outdir;
            // Should be - ? 'png' : 'gif' - , but doesn't work (ImageMagick prob.?)
            // René: png work for me
            $thmMode = MathUtility::forceIntegerInRange($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails_png'], 0);
            $outext = $this->image->getExtension() != 'jpg' || $thmMode & Permission::PAGE_EDIT ? ($thmMode & 1 ? 'png' : 'gif') : 'jpg';
            $outfile = 'tmb_' . substr(md5(($this->image->getName() . $this->mtime . $this->size)), 0, 10) . '.' . $outext;
            $this->output = $outpath . $outfile;
            if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
                // If thumbnail does not exist, we generate it
                if (!file_exists($this->output)) {
                    $parameters = '-sample ' . $this->size . ' ' . CommandUtility::escapeShellArgument($this->image->getForLocalProcessing(false)) . '[0] ' . CommandUtility::escapeShellArgument($this->output);
                    $cmd = GeneralUtility::imageMagickCommand('convert', $parameters);
                    \TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
                    if (!file_exists($this->output)) {
                        $this->errorGif('No thumb', 'generated!', $this->image->getName());
                    } else {
                        GeneralUtility::fixPermissions($this->output);
                    }
                }
                // The thumbnail is read and output to the browser
                if ($fd = @fopen($this->output, 'rb')) {
                    $fileModificationTime = filemtime($this->output);
                    header('Content-Type: image/' . ($outext === 'jpg' ? 'jpeg' : $outext));
                    header('Last-Modified: ' . date('r', $fileModificationTime));
                    header('ETag: ' . md5($this->output) . '-' . $fileModificationTime);
                    // Expiration time is chosen arbitrary to 1 month
                    header('Expires: ' . date('r', ($fileModificationTime + 30 * 24 * 60 * 60)));
                    fpassthru($fd);
                    fclose($fd);
                } else {
                    $this->errorGif('Read problem!', '', $this->output);
                }
            } else {
                die;
            }
        } else {
            $this->errorGif('No valid', 'inputfile!', basename($this->image));
        }
    }

    /***************************
     *
     * OTHER FUNCTIONS:
     *
     ***************************/
    /**
     * Creates error image based on gfx/notfound_thumb.png
     * Requires GD lib enabled, otherwise it will exit with the three textstrings outputted as text.
     * Outputs the image stream to browser and exits!
     *
     * @param string $l1 Text line 1
     * @param string $l2 Text line 2
     * @param string $l3 Text line 3
     * @return void
     */
    public function errorGif($l1, $l2, $l3)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
            throw new \RuntimeException('TYPO3 Fatal Error: No gdlib. ' . $l1 . ' ' . $l2 . ' ' . $l3, 1270853954);
        }
        // Creates the basis for the error image
        $basePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('core') . 'Resources/Public/Images/';
        if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
            header('Content-type: image/png');
            $im = imagecreatefrompng($basePath . 'NotFound.png');
        } else {
            header('Content-type: image/gif');
            $im = imagecreatefromgif($basePath . 'NotFound.gif');
        }
        // Sets background color and print color.
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        // Prints the text strings with the build-in font functions of GD
        $x = 0;
        $font = 0;
        if ($l1) {
            imagefilledrectangle($im, $x, 9, 56, 16, $white);
            imagestring($im, $font, $x, 9, $l1, $black);
        }
        if ($l2) {
            imagefilledrectangle($im, $x, 19, 56, 26, $white);
            imagestring($im, $font, $x, 19, $l2, $black);
        }
        if ($l3) {
            imagefilledrectangle($im, $x, 29, 56, 36, $white);
            imagestring($im, $font, $x, 29, substr($l3, -14), $black);
        }
        // Outputting the image stream and exit
        if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
            imagepng($im);
        } else {
            imagegif($im);
        }
        imagedestroy($im);
        die;
    }

    /**
     * Creates a font-preview thumbnail.
     * This means a PNG/GIF file with the text "AaBbCc...." set with the font-file given as input and in various sizes to show how the font looks
     * Requires GD lib enabled.
     * Outputs the image stream to browser and exits!
     *
     * @param string $font The filepath to the font file (absolute, probably)
     * @return void
     */
    public function fontGif($font)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
            throw new \RuntimeException('TYPO3 Fatal Error: No gdlib.', 1270853953);
        }
        // Create image and set background color to white.
        $im = imagecreate(250, 76);
        $white = imagecolorallocate($im, 255, 255, 255);
        $col = imagecolorallocate($im, 0, 0, 0);
        // The test string and offset in x-axis.
        $string = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZzÆæØøÅåÄäÖöÜüß';
        $x = 13;
        // Print (with non-ttf font) the size displayed
        imagestring($im, 1, 0, 2, '10', $col);
        imagestring($im, 1, 0, 15, '12', $col);
        imagestring($im, 1, 0, 30, '14', $col);
        imagestring($im, 1, 0, 47, '18', $col);
        imagestring($im, 1, 0, 68, '24', $col);
        // Print with ttf-font the test string
        imagettftext($im, GeneralUtility::freetypeDpiComp(10), 0, $x, 8, $col, $font, $string);
        imagettftext($im, GeneralUtility::freetypeDpiComp(12), 0, $x, 21, $col, $font, $string);
        imagettftext($im, GeneralUtility::freetypeDpiComp(14), 0, $x, 36, $col, $font, $string);
        imagettftext($im, GeneralUtility::freetypeDpiComp(18), 0, $x, 53, $col, $font, $string);
        imagettftext($im, GeneralUtility::freetypeDpiComp(24), 0, $x, 74, $col, $font, $string);
        // Output PNG or GIF based on $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']
        if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
            header('Content-type: image/png');
            imagepng($im);
        } else {
            header('Content-type: image/gif');
            imagegif($im);
        }
        imagedestroy($im);
        die;
    }
}
