<?php
namespace TYPO3\CMS\Core\Html;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * Class for parsing HTML for the Rich Text Editor. (also called transformations)
 */
class RteHtmlParser extends \TYPO3\CMS\Core\Html\HtmlParser
{
    /**
     * @var string
     */
    public $blockElementList = 'PRE,UL,OL,H1,H2,H3,H4,H5,H6,ADDRESS,DL,DD,HEADER,SECTION,FOOTER,NAV,ARTICLE,ASIDE';

    /**
     * Set this to the pid of the record manipulated by the class.
     *
     * @var int
     */
    public $recPid = 0;

    /**
     * Element reference [table]:[field], eg. "tt_content:bodytext"
     *
     * @var string
     */
    public $elRef = '';

    /**
     * Relative path
     *
     * @var string
     */
    public $relPath = '';

    /**
     * Relative back-path
     *
     * @var string
     */
    public $relBackPath = '';

    /**
     * Current Page TSConfig
     *
     * @var array
     */
    public $tsConfig = [];

    /**
     * Set to the TSconfig options coming from Page TSconfig
     *
     * @var array
     */
    public $procOptions = [];

    /**
     * Run-away brake for recursive calls.
     *
     * @var int
     */
    public $TS_transform_db_safecounter = 100;

    /**
     * Parameters from TCA types configuration related to the RTE
     *
     * @var string
     */
    public $rte_p = '';

    /**
     * Data caching for processing function
     *
     * @var array
     */
    public $getKeepTags_cache = [];

    /**
     * Storage of the allowed CSS class names in the RTE
     *
     * @var array
     */
    public $allowedClasses = [];

    /**
     * Set to tags to preserve from Page TSconfig configuration
     *
     * @var string
     */
    public $preserveTags = '';

    /**
     * Initialize, setting element reference and record PID
     *
     * @param string $elRef Element reference, eg "tt_content:bodytext
     * @param int $recPid PID of the record (page id)
     * @return void
     */
    public function init($elRef = '', $recPid = 0)
    {
        $this->recPid = $recPid;
        $this->elRef = $elRef;
    }

    /**
     * Setting the ->relPath and ->relBackPath to proper values so absolute references to links and images can be converted to relative dittos.
     * This is used when editing files with the RTE
     *
     * @param string $path The relative path from PATH_site to the place where the file being edited is. Eg. "fileadmin/static".
     * @return void There is no output, it is set in internal variables. With the above example of "fileadmin/static" as input this will yield ->relPath to be "fileadmin/static/" and ->relBackPath to be "../../
     * @TODO: Check if relPath and relBackPath are used for anything useful after removal of "static file edit" with #63818
     */
    public function setRelPath($path)
    {
        $path = trim($path);
        $path = preg_replace('/^\\//', '', $path);
        $path = preg_replace('/\\/$/', '', $path);
        if ($path) {
            $this->relPath = $path;
            $this->relBackPath = '';
            $partsC = count(explode('/', $this->relPath));
            for ($a = 0; $a < $partsC; $a++) {
                $this->relBackPath .= '../';
            }
            $this->relPath .= '/';
        }
    }

    /**
     * Evaluate the environment for editing a staticFileEdit file.
     * Called for almost all fields being saved in the database. Is called without
     * an instance of \TYPO3\CMS\Core\Html\RteHtmlParser::evalWriteFile()
     *
     * @param array $pArr Parameters for the current field as found in types-config
     * @param array $currentRecord Current record we are editing.
     * @return mixed On success an array with various information is returned, otherwise a string with an error message
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public static function evalWriteFile($pArr, $currentRecord)
    {
        GeneralUtility::logDeprecatedFunction();
    }

    /**********************************************
     *
     * Main function
     *
     **********************************************/
    /**
     * Transform value for RTE based on specConf in the direction specified by $direction (rte/db)
     * This is the main function called from tcemain and transfer data classes
     *
     * @param string Input value
     * @param array Special configuration for a field; This is coming from the types-configuration of the field in the TCA. In the types-configuration you can setup features for the field rendering and in particular the RTE takes al its major configuration options from there!
     * @param string Direction of the transformation. Two keywords are allowed; "db" or "rte". If "db" it means the transformation will clean up content coming from the Rich Text Editor and goes into the database. The other direction, "rte", is of course when content is coming from database and must be transformed to fit the RTE.
     * @param array Parsed TypoScript content configuring the RTE, probably coming from Page TSconfig.
     * @return string Output value
     */
    public function RTE_transform($value, $specConf, $direction = 'rte', $thisConfig = [])
    {
        // Init:
        $this->tsConfig = $thisConfig;
        $this->procOptions = (array)$thisConfig['proc.'];
        $this->preserveTags = strtoupper(implode(',', GeneralUtility::trimExplode(',', $this->procOptions['preserveTags'])));
        // dynamic configuration of blockElementList
        if ($this->procOptions['blockElementList']) {
            $this->blockElementList = $this->procOptions['blockElementList'];
        }
        // Get parameters for rte_transformation:
        $p = ($this->rte_p = BackendUtility::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']));
        // Setting modes:
        if ((string)$this->procOptions['overruleMode'] !== '') {
            $modes = array_unique(GeneralUtility::trimExplode(',', $this->procOptions['overruleMode']));
        } else {
            $modes = array_unique(GeneralUtility::trimExplode('-', $p['mode']));
        }
        $revmodes = array_flip($modes);
        // Find special modes and extract them:
        if (isset($revmodes['ts'])) {
            $modes[$revmodes['ts']] = 'ts_transform,ts_preserve,ts_images,ts_links';
        }
        // Find special modes and extract them:
        if (isset($revmodes['ts_css'])) {
            $modes[$revmodes['ts_css']] = 'css_transform,ts_images,ts_links';
        }
        // Make list unique
        $modes = array_unique(GeneralUtility::trimExplode(',', implode(',', $modes), true));
        // Reverse order if direction is "rte"
        if ($direction == 'rte') {
            $modes = array_reverse($modes);
        }
        // Getting additional HTML cleaner configuration. These are applied either before or after the main transformation is done and is thus totally independent processing options you can set up:
        $entry_HTMLparser = $this->procOptions['entryHTMLparser_' . $direction] ? $this->HTMLparserConfig($this->procOptions['entryHTMLparser_' . $direction . '.']) : '';
        $exit_HTMLparser = $this->procOptions['exitHTMLparser_' . $direction] ? $this->HTMLparserConfig($this->procOptions['exitHTMLparser_' . $direction . '.']) : '';
        // Line breaks of content is unified into char-10 only (removing char 13)
        if (!$this->procOptions['disableUnifyLineBreaks']) {
            $value = str_replace(CRLF, LF, $value);
        }
        // In an entry-cleaner was configured, pass value through the HTMLcleaner with that:
        if (is_array($entry_HTMLparser)) {
            $value = $this->HTMLcleaner($value, $entry_HTMLparser[0], $entry_HTMLparser[1], $entry_HTMLparser[2], $entry_HTMLparser[3]);
        }
        // Traverse modes:
        foreach ($modes as $cmd) {
            // ->DB
            if ($direction == 'db') {
                // Checking for user defined transformation:
                if ($_classRef = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation'][$cmd]) {
                    $_procObj = GeneralUtility::getUserObj($_classRef);
                    $_procObj->pObj = $this;
                    $_procObj->transformationKey = $cmd;
                    $value = $_procObj->transform_db($value, $this);
                } else {
                    // ... else use defaults:
                    switch ($cmd) {
                        case 'ts_images':
                            $value = $this->TS_images_db($value);
                            break;
                        case 'ts_reglinks':
                            $value = $this->TS_reglinks($value, 'db');
                            break;
                        case 'ts_links':
                            $value = $this->TS_links_db($value);
                            break;
                        case 'ts_preserve':
                            $value = $this->TS_preserve_db($value);
                            break;
                        case 'ts_transform':

                        case 'css_transform':
                            $this->allowedClasses = GeneralUtility::trimExplode(',', $this->procOptions['allowedClasses'], true);
                            // CR has a very disturbing effect, so just remove all CR and rely on LF
                            $value = str_replace(CR, '', $value);
                            // Transform empty paragraphs into spacing paragraphs
                            $value = str_replace('<p></p>', '<p>&nbsp;</p>', $value);
                            // Double any trailing spacing paragraph so that it does not get removed by divideIntoLines()
                            $value = preg_replace('/<p>&nbsp;<\/p>$/', '<p>&nbsp;</p>' . '<p>&nbsp;</p>', $value);
                            $value = $this->TS_transform_db($value, $cmd == 'css_transform');
                            break;
                        case 'ts_strip':
                            $value = $this->TS_strip_db($value);
                            break;
                        default:
                            // Do nothing
                    }
                }
            }
            // ->RTE
            if ($direction == 'rte') {
                // Checking for user defined transformation:
                if ($_classRef = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation'][$cmd]) {
                    $_procObj = GeneralUtility::getUserObj($_classRef);
                    $_procObj->pObj = $this;
                    $value = $_procObj->transform_rte($value, $this);
                } else {
                    // ... else use defaults:
                    switch ($cmd) {
                        case 'ts_images':
                            $value = $this->TS_images_rte($value);
                            break;
                        case 'ts_reglinks':
                            $value = $this->TS_reglinks($value, 'rte');
                            break;
                        case 'ts_links':
                            $value = $this->TS_links_rte($value);
                            break;
                        case 'ts_preserve':
                            $value = $this->TS_preserve_rte($value);
                            break;
                        case 'ts_transform':

                        case 'css_transform':
                            // Has a very disturbing effect, so just remove all '13' - depend on '10'
                            $value = str_replace(CR, '', $value);
                            $value = $this->TS_transform_rte($value, $cmd == 'css_transform');
                            break;
                        default:
                            // Do nothing
                    }
                }
            }
        }
        // In an exit-cleaner was configured, pass value through the HTMLcleaner with that:
        if (is_array($exit_HTMLparser)) {
            $value = $this->HTMLcleaner($value, $exit_HTMLparser[0], $exit_HTMLparser[1], $exit_HTMLparser[2], $exit_HTMLparser[3]);
        }
        // Final clean up of linebreaks:
        if (!$this->procOptions['disableUnifyLineBreaks']) {
            // Make sure no \r\n sequences has entered in the meantime...
            $value = str_replace(CRLF, LF, $value);
            // ... and then change all \n into \r\n
            $value = str_replace(LF, CRLF, $value);
        }
        // Return value:
        return $value;
    }

    /************************************
     *
     * Specific RTE TRANSFORMATION functions
     *
     *************************************/
    /**
     * Transformation handler: 'ts_images' / direction: "db"
     * Processing images inserted in the RTE.
     * This is used when content goes from the RTE to the database.
     * Images inserted in the RTE has an absolute URL applied to the src attribute. This URL is converted to a relative URL
     * If it turns out that the URL is from another website than the current the image is read from that external URL and moved to the local server.
     * Also "magic" images are processed here.
     *
     * @param string $value The content from RTE going to Database
     * @return string Processed content
     */
    public function TS_images_db($value)
    {
        // Split content by <img> tags and traverse the resulting array for processing:
        $imgSplit = $this->splitTags('img', $value);
        if (count($imgSplit) > 1) {
            $siteUrl = $this->siteUrl();
            $sitePath = str_replace(GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'), '', $siteUrl);
            /** @var $resourceFactory Resource\ResourceFactory */
            $resourceFactory = Resource\ResourceFactory::getInstance();
            /** @var $magicImageService Resource\Service\MagicImageService */
            $magicImageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Service\MagicImageService::class);
            $magicImageService->setMagicImageMaximumDimensions($this->tsConfig);
            foreach ($imgSplit as $k => $v) {
                // Image found, do processing:
                if ($k % 2) {
                    // Get attributes
                    $attribArray = $this->get_tag_attributes_classic($v, 1);
                    // It's always an absolute URL coming from the RTE into the Database.
                    $absoluteUrl = trim($attribArray['src']);
                    // Make path absolute if it is relative and we have a site path which is not '/'
                    $pI = pathinfo($absoluteUrl);
                    if ($sitePath && !$pI['scheme'] && GeneralUtility::isFirstPartOfStr($absoluteUrl, $sitePath)) {
                        // If site is in a subpath (eg. /~user_jim/) this path needs to be removed because it will be added with $siteUrl
                        $absoluteUrl = substr($absoluteUrl, strlen($sitePath));
                        $absoluteUrl = $siteUrl . $absoluteUrl;
                    }
                    // Image dimensions set in the img tag, if any
                    $imgTagDimensions = $this->getWHFromAttribs($attribArray);
                    if ($imgTagDimensions[0]) {
                        $attribArray['width'] = $imgTagDimensions[0];
                    }
                    if ($imgTagDimensions[1]) {
                        $attribArray['height'] = $imgTagDimensions[1];
                    }
                    $originalImageFile = null;
                    if ($attribArray['data-htmlarea-file-uid']) {
                        // An original image file uid is available
                        try {
                            /** @var $originalImageFile Resource\File */
                            $originalImageFile = $resourceFactory->getFileObject(intval($attribArray['data-htmlarea-file-uid']));
                        } catch (Resource\Exception\FileDoesNotExistException $fileDoesNotExistException) {
                            // Log the fact the file could not be retrieved.
                            $message = sprintf('Could not find file with uid "%s"', $attribArray['data-htmlarea-file-uid']);
                            $this->getLogger()->error($message);
                        }
                    }
                    if ($originalImageFile instanceof Resource\File) {
                        // Public url of local file is relative to the site url, absolute otherwise
                        if ($absoluteUrl == $originalImageFile->getPublicUrl() || $absoluteUrl == $siteUrl . $originalImageFile->getPublicUrl()) {
                            // This is a plain image, i.e. reference to the original image
                            if ($this->procOptions['plainImageMode']) {
                                // "plain image mode" is configured
                                // Find the dimensions of the original image
                                $imageInfo = [
                                    $originalImageFile->getProperty('width'),
                                    $originalImageFile->getProperty('height')
                                ];
                                if (!$imageInfo[0] || !$imageInfo[1]) {
                                    $filePath = $originalImageFile->getForLocalProcessing(false);
                                    $imageInfo = @getimagesize($filePath);
                                }
                                $attribArray = $this->applyPlainImageModeSettings($imageInfo, $attribArray);
                            }
                        } else {
                            // Magic image case: get a processed file with the requested configuration
                            $imageConfiguration = [
                                'width' => $imgTagDimensions[0],
                                'height' => $imgTagDimensions[1]
                            ];
                            $magicImage = $magicImageService->createMagicImage($originalImageFile, $imageConfiguration);
                            $attribArray['width'] = $magicImage->getProperty('width');
                            $attribArray['height'] = $magicImage->getProperty('height');
                            $attribArray['src'] = $magicImage->getPublicUrl();
                        }
                    } elseif (!GeneralUtility::isFirstPartOfStr($absoluteUrl, $siteUrl) && !$this->procOptions['dontFetchExtPictures'] && TYPO3_MODE === 'BE') {
                        // External image from another URL: in that case, fetch image, unless the feature is disabled or we are not in backend mode
                        // Fetch the external image
                        $externalFile = $this->getUrl($absoluteUrl);
                        if ($externalFile) {
                            $pU = parse_url($absoluteUrl);
                            $pI = pathinfo($pU['path']);
                            $extension = strtolower($pI['extension']);
                            if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'gif' || $extension === 'png') {
                                $fileName = GeneralUtility::shortMD5($absoluteUrl) . '.' . $pI['extension'];
                                // We insert this image into the user default upload folder
                                list($table, $field) = explode(':', $this->elRef);
                                $folder = $GLOBALS['BE_USER']->getDefaultUploadFolder($this->recPid, $table, $field);
                                $fileObject = $folder->createFile($fileName)->setContents($externalFile);
                                $imageConfiguration = [
                                    'width' => $attribArray['width'],
                                    'height' => $attribArray['height']
                                ];
                                $magicImage = $magicImageService->createMagicImage($fileObject, $imageConfiguration);
                                $attribArray['width'] = $magicImage->getProperty('width');
                                $attribArray['height'] = $magicImage->getProperty('height');
                                $attribArray['data-htmlarea-file-uid'] = $fileObject->getUid();
                                $attribArray['src'] = $magicImage->getPublicUrl();
                            }
                        }
                    } elseif (GeneralUtility::isFirstPartOfStr($absoluteUrl, $siteUrl)) {
                        // Finally, check image as local file (siteURL equals the one of the image)
                        // Image has no data-htmlarea-file-uid attribute
                        // Relative path, rawurldecoded for special characters.
                        $path = rawurldecode(substr($absoluteUrl, strlen($siteUrl)));
                        // Absolute filepath, locked to relative path of this project
                        $filepath = GeneralUtility::getFileAbsFileName($path);
                        // Check file existence (in relative directory to this installation!)
                        if ($filepath && @is_file($filepath)) {
                            // Treat it as a plain image
                            if ($this->procOptions['plainImageMode']) {
                                // If "plain image mode" has been configured
                                // Find the original dimensions of the image
                                $imageInfo = @getimagesize($filepath);
                                $attribArray = $this->applyPlainImageModeSettings($imageInfo, $attribArray);
                            }
                            // Let's try to find a file uid for this image
                            try {
                                $fileOrFolderObject = $resourceFactory->retrieveFileOrFolderObject($path);
                                if ($fileOrFolderObject instanceof Resource\FileInterface) {
                                    $fileIdentifier = $fileOrFolderObject->getIdentifier();
                                    $fileObject = $fileOrFolderObject->getStorage()->getFile($fileIdentifier);
                                    // @todo if the retrieved file is a processed file, get the original file...
                                    $attribArray['data-htmlarea-file-uid'] = $fileObject->getUid();
                                }
                            } catch (Resource\Exception\ResourceDoesNotExistException $resourceDoesNotExistException) {
                                // Nothing to be done if file/folder not found
                            }
                        }
                    }
                    // Remove width and height from style attribute
                    $attribArray['style'] = preg_replace('/(?:^|[^-])(\\s*(?:width|height)\\s*:[^;]*(?:$|;))/si', '', $attribArray['style']);
                    // Must have alt attribute
                    if (!isset($attribArray['alt'])) {
                        $attribArray['alt'] = '';
                    }
                    // Convert absolute to relative url
                    if (GeneralUtility::isFirstPartOfStr($attribArray['src'], $siteUrl)) {
                        $attribArray['src'] = $this->relBackPath . substr($attribArray['src'], strlen($siteUrl));
                    }
                    $imgSplit[$k] = '<img ' . GeneralUtility::implodeAttributes($attribArray, 1, 1) . ' />';
                }
            }
        }
        return implode('', $imgSplit);
    }

    /**
     * Transformation handler: 'ts_images' / direction: "rte"
     * Processing images from database content going into the RTE.
     * Processing includes converting the src attribute to an absolute URL.
     *
     * @param string $value Content input
     * @return string Content output
     */
    public function TS_images_rte($value)
    {
        // Split content by <img> tags and traverse the resulting array for processing:
        $imgSplit = $this->splitTags('img', $value);
        if (count($imgSplit) > 1) {
            $siteUrl = $this->siteUrl();
            $sitePath = str_replace(GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'), '', $siteUrl);
            foreach ($imgSplit as $k => $v) {
                // Image found
                if ($k % 2) {
                    // Get the attributes of the img tag
                    $attribArray = $this->get_tag_attributes_classic($v, 1);
                    $absoluteUrl = trim($attribArray['src']);
                    // Transform the src attribute into an absolute url, if it not already
                    if (strtolower(substr($absoluteUrl, 0, 4)) !== 'http') {
                        $attribArray['src'] = substr($attribArray['src'], strlen($this->relBackPath));
                        // If site is in a subpath (eg. /~user_jim/) this path needs to be removed because it will be added with $siteUrl
                        $attribArray['src'] = preg_replace('#^' . preg_quote($sitePath, '#') . '#', '', $attribArray['src']);
                        $attribArray['src'] = $siteUrl . $attribArray['src'];
                    }
                    // Must have alt attribute
                    if (!isset($attribArray['alt'])) {
                        $attribArray['alt'] = '';
                    }
                    $imgSplit[$k] = '<img ' . GeneralUtility::implodeAttributes($attribArray, 1, 1) . ' />';
                }
            }
        }
        // Return processed content:
        return implode('', $imgSplit);
    }

    /**
     * Transformation handler: 'ts_reglinks' / direction: "db"+"rte" depending on $direction variable.
     * Converting <A>-tags to/from abs/rel
     *
     * @param string $value Content input
     * @param string $direction Direction of conversion; "rte" (from database to RTE) or "db" (from RTE to database)
     * @return string Content output
     */
    public function TS_reglinks($value, $direction)
    {
        $retVal = '';
        switch ($direction) {
            case 'rte':
                $retVal = $this->TS_AtagToAbs($value, 1);
                break;
            case 'db':
                $siteURL = $this->siteUrl();
                $blockSplit = $this->splitIntoBlock('A', $value);
                foreach ($blockSplit as $k => $v) {
                    // Block
                    if ($k % 2) {
                        $attribArray = $this->get_tag_attributes_classic($this->getFirstTag($v), 1);
                        // If the url is local, remove url-prefix
                        if ($siteURL && substr($attribArray['href'], 0, strlen($siteURL)) == $siteURL) {
                            $attribArray['href'] = $this->relBackPath . substr($attribArray['href'], strlen($siteURL));
                        }
                        $bTag = '<a ' . GeneralUtility::implodeAttributes($attribArray, 1) . '>';
                        $eTag = '</a>';
                        $blockSplit[$k] = $bTag . $this->TS_reglinks($this->removeFirstAndLastTag($blockSplit[$k]), $direction) . $eTag;
                    }
                }
                $retVal = implode('', $blockSplit);
                break;
        }
        return $retVal;
    }

    /**
     * Transformation handler: 'ts_links' / direction: "db"
     * Converting <A>-tags to <link tags>
     *
     * @param string $value Content input
     * @return string Content output
     * @see TS_links_rte()
     */
    public function TS_links_db($value)
    {
        $conf = [];
        // Split content into <a> tag blocks and process:
        $blockSplit = $this->splitIntoBlock('A', $value);
        foreach ($blockSplit as $k => $v) {
            // If an A-tag was found:
            if ($k % 2) {
                $attribArray = $this->get_tag_attributes_classic($this->getFirstTag($v), 1);
                $info = $this->urlInfoForLinkTags($attribArray['href']);
                // Check options:
                $attribArray_copy = $attribArray;
                unset($attribArray_copy['href']);
                unset($attribArray_copy['target']);
                unset($attribArray_copy['class']);
                unset($attribArray_copy['title']);
                unset($attribArray_copy['data-htmlarea-external']);
                // Unset "rteerror" and "style" attributes if "rteerror" is set!
                if ($attribArray_copy['rteerror']) {
                    unset($attribArray_copy['style']);
                    unset($attribArray_copy['rteerror']);
                }
                // Remove additional parameters
                if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['removeParams_PostProc']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['removeParams_PostProc'])) {
                    $parameters = [
                        'conf' => &$conf,
                        'aTagParams' => &$attribArray_copy
                    ];
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['removeParams_PostProc'] as $objRef) {
                        $processor = GeneralUtility::getUserObj($objRef);
                        $attribArray_copy = $processor->removeParams($parameters, $this);
                    }
                }
                // Only if href, target, class and tile are the only attributes, we can alter the link!
                if (empty($attribArray_copy)) {
                    // Quoting class and title attributes if they contain spaces
                    $attribArray['class'] = preg_match('/ /', $attribArray['class']) ? '"' . $attribArray['class'] . '"' : $attribArray['class'];
                    $attribArray['title'] = preg_match('/ /', $attribArray['title']) ? '"' . $attribArray['title'] . '"' : $attribArray['title'];
                    // Creating the TYPO3 pseudo-tag "<LINK>" for the link (includes href/url, target and class attributes):
                    // If data-htmlarea-external attribute is set, keep the href unchanged
                    if ($attribArray['data-htmlarea-external']) {
                        $href = $attribArray['href'];
                    } else {
                        $href = $info['url'] . ($info['query'] ? ',0,' . $info['query'] : '');
                    }
                    $typoLink = GeneralUtility::makeInstance(TypoLinkCodecService::class)->encode(['url' => $href, 'target' => $attribArray['target'], 'class' => trim($attribArray['class'], '"'), 'title' => trim($attribArray['title'], '"'), 'additionalParams' => '']);
                    $bTag = '<link ' . $typoLink . '>';
                    $eTag = '</link>';
                    // Modify parameters
                    if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksDb_PostProc']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksDb_PostProc'])) {
                        $parameters = [
                            'conf' => &$conf,
                            'currentBlock' => $v,
                            'url' => $href,
                            'attributes' => $attribArray
                        ];
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksDb_PostProc'] as $objRef) {
                            $processor = GeneralUtility::getUserObj($objRef);
                            $blockSplit[$k] = $processor->modifyParamsLinksDb($parameters, $this);
                        }
                    } else {
                        $blockSplit[$k] = $bTag . $this->TS_links_db($this->removeFirstAndLastTag($blockSplit[$k])) . $eTag;
                    }
                } else {
                    // ... otherwise store the link as a-tag.
                    // Unsetting 'rtekeep' attribute if that had been set.
                    unset($attribArray['rtekeep']);
                    if (!$attribArray['data-htmlarea-external']) {
                        $siteURL = $this->siteUrl();
                        // If the url is local, remove url-prefix
                        if ($siteURL && substr($attribArray['href'], 0, strlen($siteURL)) == $siteURL) {
                            $attribArray['href'] = $this->relBackPath . substr($attribArray['href'], strlen($siteURL));
                        }
                        // Check for FAL link-handler keyword
                        list($linkHandlerKeyword, $linkHandlerValue) = explode(':', $attribArray['href'], 2);
                        if ($linkHandlerKeyword === '?file') {
                            try {
                                $fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject(rawurldecode($linkHandlerValue));
                                if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FileInterface || $fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
                                    $attribArray['href'] = $fileOrFolderObject->getPublicUrl();
                                }
                            } catch (\TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException $resourceDoesNotExistException) {
                                // The indentifier inserted in the RTE is already gone...
                            }
                        }
                    }
                    unset($attribArray['data-htmlarea-external']);
                    $bTag = '<a ' . GeneralUtility::implodeAttributes($attribArray, 1) . '>';
                    $eTag = '</a>';
                    $blockSplit[$k] = $bTag . $this->TS_links_db($this->removeFirstAndLastTag($blockSplit[$k])) . $eTag;
                }
            }
        }
        return implode('', $blockSplit);
    }

    /**
     * Transformation handler: 'ts_links' / direction: "rte"
     * Converting <link tags> to <A>-tags
     *
     * @param string $value Content input
     * @return string Content output
     * @see TS_links_rte()
     */
    public function TS_links_rte($value)
    {
        $conf = [];
        $value = $this->TS_AtagToAbs($value);
        // Split content by the TYPO3 pseudo tag "<link>":
        $blockSplit = $this->splitIntoBlock('link', $value, 1);
        $siteUrl = $this->siteUrl();
        foreach ($blockSplit as $k => $v) {
            $error = '';
            $external = false;
            // Block
            if ($k % 2) {
                // split away the first "<link" part
                $typolink = explode(' ', substr($this->getFirstTag($v), 0, -1), 2)[1];
                $tagCode = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($typolink);

                $link_param = $tagCode['url'];
                // Parsing the typolink data. This parsing is roughly done like in \TYPO3\CMS\Frontend\ContentObject->typoLink()
                // Parse URL:
                $pU = parse_url($link_param);
                if (strstr($link_param, '@') && (!$pU['scheme'] || $pU['scheme'] == 'mailto')) {
                    // mailadr
                    $href = 'mailto:' . preg_replace('/^mailto:/i', '', $link_param);
                } elseif ($link_param[0] === '#') {
                    // check if anchor
                    $href = $siteUrl . $link_param;
                } else {
                    // Check for FAL link-handler keyword:
                    list($linkHandlerKeyword, $linkHandlerValue) = explode(':', trim($link_param), 2);
                    if ($linkHandlerKeyword === 'file' && !StringUtility::beginsWith($link_param, 'file://')) {
                        $href = $siteUrl . '?' . $linkHandlerKeyword . ':' . rawurlencode($linkHandlerValue);
                    } else {
                        $fileChar = (int)strpos($link_param, '/');
                        $urlChar = (int)strpos($link_param, '.');
                        // Detects if a file is found in site-root.
                        list($rootFileDat) = explode('?', $link_param);
                        $rFD_fI = pathinfo($rootFileDat);
                        $fileExtension = strtolower($rFD_fI['extension']);
                        if (strpos($link_param, '/') === false && trim($rootFileDat) && (@is_file(PATH_site . $rootFileDat) || $fileExtension === 'php' || $fileExtension === 'html' || $fileExtension === 'htm')) {
                            $href = $siteUrl . $link_param;
                        } elseif (
                            (
                                $pU['scheme']
                                && !isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][$pU['scheme']])
                            )
                            || $urlChar && (!$fileChar || $urlChar < $fileChar)
                        ) {
                            // url (external): if has scheme or if a '.' comes before a '/'.
                            $href = $link_param;
                            if (!$pU['scheme']) {
                                $href = 'http://' . $href;
                            }
                            $external = true;
                        } elseif ($fileChar) {
                            // It is an internal file or folder
                            // Try to transform the href into a FAL reference
                            try {
                                $fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($link_param);
                            } catch (\TYPO3\CMS\Core\Resource\Exception $exception) {
                                // Nothing to be done if file/folder not found or path invalid
                                $fileOrFolderObject = null;
                            }
                            if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
                                // It's a folder
                                $folderIdentifier = $fileOrFolderObject->getIdentifier();
                                $href = $siteUrl . '?file:' . rawurlencode($folderIdentifier);
                            } elseif ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
                                // It's a file
                                $fileIdentifier = $fileOrFolderObject->getIdentifier();
                                $fileObject = $fileOrFolderObject->getStorage()->getFile($fileIdentifier);
                                $href = $siteUrl . '?file:' . $fileObject->getUid();
                            } else {
                                $href = $siteUrl . $link_param;
                            }
                        } else {
                            // integer or alias (alias is without slashes or periods or commas, that is 'nospace,alphanum_x,lower,unique' according to tables.php!!)
                            // Splitting the parameter by ',' and if the array counts more than 1 element it's an id/type/parameters triplet
                            $pairParts = GeneralUtility::trimExplode(',', $link_param, true);
                            $idPart = $pairParts[0];
                            $link_params_parts = explode('#', $idPart);
                            $idPart = trim($link_params_parts[0]);
                            $sectionMark = trim($link_params_parts[1]);
                            if ((string)$idPart === '') {
                                $idPart = $this->recPid;
                            }
                            // If no id or alias is given, set it to class record pid
                            // Checking if the id-parameter is an alias.
                            if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($idPart)) {
                                list($idPartR) = BackendUtility::getRecordsByField('pages', 'alias', $idPart);
                                $idPart = (int)$idPartR['uid'];
                            }
                            $page = BackendUtility::getRecord('pages', $idPart);
                            if (is_array($page)) {
                                // Page must exist...
                                $href = $siteUrl . '?id=' . $idPart . ($pairParts[2] ? $pairParts[2] : '') . ($sectionMark ? '#' . $sectionMark : '');
                            } elseif (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][array_shift(explode(':', $link_param))])) {
                                $href = $link_param;
                            } else {
                                $href = $siteUrl . '?id=' . $link_param;
                                $error = 'No page found: ' . $idPart;
                            }
                        }
                    }
                }
                // Setting the A-tag:
                $bTag = '<a href="' . htmlspecialchars($href) . '"'
                    . ($tagCode['target'] ? ' target="' . htmlspecialchars($tagCode['target']) . '"' : '')
                    . ($tagCode['class'] ? ' class="' . htmlspecialchars($tagCode['class']) . '"' : '')
                    . ($tagCode['title'] ? ' title="' . htmlspecialchars($tagCode['title']) . '"' : '')
                    . ($external ? ' data-htmlarea-external="1"' : '')
                    . ($error ? ' rteerror="' . htmlspecialchars($error) . '" style="background-color: yellow; border:2px red solid; color: black;"' : '') . '>';
                $eTag = '</a>';
                // Modify parameters
                if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc'])) {
                    $parameters = [
                        'conf' => &$conf,
                        'currentBlock' => $v,
                        'url' => $href,
                        'tagCode' => $tagCode,
                        'external' => $external,
                        'error' => $error
                    ];
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc'] as $objRef) {
                        $processor = GeneralUtility::getUserObj($objRef);
                        $blockSplit[$k] = $processor->modifyParamsLinksRte($parameters, $this);
                    }
                } else {
                    $blockSplit[$k] = $bTag . $this->TS_links_rte($this->removeFirstAndLastTag($blockSplit[$k])) . $eTag;
                }
            }
        }
        // Return content:
        return implode('', $blockSplit);
    }

    /**
     * Preserve special tags
     *
     * @param string $value Content input
     * @return string Content output
     */
    public function TS_preserve_db($value)
    {
        if (!$this->preserveTags) {
            return $value;
        }
        // Splitting into blocks for processing (span-tags are used for special tags)
        $blockSplit = $this->splitIntoBlock('span', $value);
        foreach ($blockSplit as $k => $v) {
            // Block
            if ($k % 2) {
                $attribArray = $this->get_tag_attributes_classic($this->getFirstTag($v));
                if ($attribArray['specialtag']) {
                    $theTag = rawurldecode($attribArray['specialtag']);
                    $theTagName = $this->getFirstTagName($theTag);
                    $blockSplit[$k] = $theTag . $this->removeFirstAndLastTag($blockSplit[$k]) . '</' . $theTagName . '>';
                }
            }
        }
        return implode('', $blockSplit);
    }

    /**
     * Preserve special tags
     *
     * @param string $value Content input
     * @return string Content output
     */
    public function TS_preserve_rte($value)
    {
        if (!$this->preserveTags) {
            return $value;
        }
        $blockSplit = $this->splitIntoBlock($this->preserveTags, $value);
        foreach ($blockSplit as $k => $v) {
            // Block
            if ($k % 2) {
                $blockSplit[$k] = '<span specialtag="' . rawurlencode($this->getFirstTag($v)) . '">' . $this->removeFirstAndLastTag($blockSplit[$k]) . '</span>';
            }
        }
        return implode('', $blockSplit);
    }

    /**
     * Transformation handler: 'ts_transform' + 'css_transform' / direction: "db"
     * Cleaning (->db) for standard content elements (ts)
     *
     * @param string $value Content input
     * @param bool $css If TRUE, the transformation was "css_transform", otherwise "ts_transform
     * @return string Content output
     * @see TS_transform_rte()
     */
    public function TS_transform_db($value, $css = false)
    {
        // Safety... so forever loops are avoided (they should not occur, but an error would potentially do this...)
        $this->TS_transform_db_safecounter--;
        if ($this->TS_transform_db_safecounter < 0) {
            return $value;
        }
        // Split the content from RTE by the occurrence of these blocks:
        $blockSplit = $this->splitIntoBlock('TABLE,BLOCKQUOTE,' . ($this->procOptions['preserveDIVSections'] ? 'DIV,' : '') . $this->blockElementList, $value);
        $cc = 0;
        $aC = count($blockSplit);
        // Avoid superfluous linebreaks by transform_db after ending headListTag
        while ($aC && trim($blockSplit[$aC - 1]) === '') {
            unset($blockSplit[$aC - 1]);
            $aC = count($blockSplit);
        }
        // Traverse the blocks
        foreach ($blockSplit as $k => $v) {
            $cc++;
            $lastBR = $cc == $aC ? '' : LF;
            if ($k % 2) {
                // Inside block:
                // Init:
                $tag = $this->getFirstTag($v);
                $tagName = strtolower($this->getFirstTagName($v));
                // Process based on the tag:
                switch ($tagName) {
                    case 'blockquote':

                    case 'dd':

                    case 'div':

                    case 'header':

                    case 'section':

                    case 'footer':

                    case 'nav':

                    case 'article':

                    case 'aside':
                        $blockSplit[$k] = $tag . $this->TS_transform_db($this->removeFirstAndLastTag($blockSplit[$k]), $css) . '</' . $tagName . '>' . $lastBR;
                        break;
                    case 'ol':

                    case 'ul':
                        if ($css) {
                            $blockSplit[$k] = preg_replace(('/[' . LF . CR . ']+/'), ' ', $this->transformStyledATags($blockSplit[$k])) . $lastBR;
                        }
                        break;
                    case 'table':
                        // Tables are NOT allowed in any form (unless preserveTables is set or CSS is the mode)
                        if (!$this->procOptions['preserveTables'] && !$css) {
                            $blockSplit[$k] = $this->TS_transform_db($this->removeTables($blockSplit[$k]));
                        } else {
                            $blockSplit[$k] = preg_replace(('/[' . LF . CR . ']+/'), ' ', $this->transformStyledATags($blockSplit[$k])) . $lastBR;
                        }
                        break;
                    case 'h1':

                    case 'h2':

                    case 'h3':

                    case 'h4':

                    case 'h5':

                    case 'h6':
                        if (!$css) {
                            $attribArray = $this->get_tag_attributes_classic($tag);
                            // Processing inner content here:
                            $innerContent = $this->HTMLcleaner_db($this->removeFirstAndLastTag($blockSplit[$k]));
                            $blockSplit[$k] = '<' . $tagName . ($attribArray['align'] ? ' align="' . htmlspecialchars($attribArray['align']) . '"' : '') . ($attribArray['class'] ? ' class="' . htmlspecialchars($attribArray['class']) . '"' : '') . '>' . $innerContent . '</' . $tagName . '>' . $lastBR;
                        } else {
                            // Eliminate true linebreaks inside Hx tags
                            $blockSplit[$k] = preg_replace(('/[' . LF . CR . ']+/'), ' ', $this->transformStyledATags($blockSplit[$k])) . $lastBR;
                        }
                        break;
                    case 'pre':
                        break;
                    default:
                        // Eliminate true linebreaks inside other headlist tags
                        $blockSplit[$k] = preg_replace(('/[' . LF . CR . ']+/'), ' ', $this->transformStyledATags($blockSplit[$k])) . $lastBR;
                }
            } else {
                // NON-block:
                if (trim($blockSplit[$k]) !== '') {
                    $blockSplit[$k] = preg_replace('/<hr\\/>/', '<hr />', $blockSplit[$k]);
                    // Remove linebreaks preceding hr tags
                    $blockSplit[$k] = preg_replace('/[' . LF . CR . ']+<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/', '<$1$2/>', $blockSplit[$k]);
                    // Remove linebreaks following hr tags
                    $blockSplit[$k] = preg_replace('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>[' . LF . CR . ']+/', '<$1$2/>', $blockSplit[$k]);
                    // Replace other linebreaks with space
                    $blockSplit[$k] = preg_replace('/[' . LF . CR . ']+/', ' ', $blockSplit[$k]);
                    $blockSplit[$k] = $this->divideIntoLines($blockSplit[$k]) . $lastBR;
                    $blockSplit[$k] = $this->transformStyledATags($blockSplit[$k]);
                } else {
                    unset($blockSplit[$k]);
                }
            }
        }
        $this->TS_transform_db_safecounter++;
        return implode('', $blockSplit);
    }

    /**
     * Wraps a-tags that contain a style attribute with a span-tag
     *
     * @param string $value Content input
     * @return string Content output
     */
    public function transformStyledATags($value)
    {
        $blockSplit = $this->splitIntoBlock('A', $value);
        foreach ($blockSplit as $k => $v) {
            // If an A-tag was found
            if ($k % 2) {
                $attribArray = $this->get_tag_attributes_classic($this->getFirstTag($v), 1);
                // If "style" attribute is set and rteerror is not set!
                if ($attribArray['style'] && !$attribArray['rteerror']) {
                    $attribArray_copy['style'] = $attribArray['style'];
                    unset($attribArray['style']);
                    $bTag = '<span ' . GeneralUtility::implodeAttributes($attribArray_copy, 1) . '><a ' . GeneralUtility::implodeAttributes($attribArray, 1) . '>';
                    $eTag = '</a></span>';
                    $blockSplit[$k] = $bTag . $this->removeFirstAndLastTag($blockSplit[$k]) . $eTag;
                }
            }
        }
        return implode('', $blockSplit);
    }

    /**
     * Transformation handler: 'ts_transform' + 'css_transform' / direction: "rte"
     * Set (->rte) for standard content elements (ts)
     *
     * @param string Content input
     * @param bool If TRUE, the transformation was "css_transform", otherwise "ts_transform
     * @return string Content output
     * @see TS_transform_db()
     */
    public function TS_transform_rte($value, $css = 0)
    {
        // Split the content from database by the occurrence of the block elements
        $blockElementList = 'TABLE,BLOCKQUOTE,' . ($this->procOptions['preserveDIVSections'] ? 'DIV,' : '') . $this->blockElementList;
        $blockSplit = $this->splitIntoBlock($blockElementList, $value);
        // Traverse the blocks
        foreach ($blockSplit as $k => $v) {
            if ($k % 2) {
                // Inside one of the blocks:
                // Init:
                $tag = $this->getFirstTag($v);
                $tagName = strtolower($this->getFirstTagName($v));
                $attribArray = $this->get_tag_attributes_classic($tag);
                // Based on tagname, we do transformations:
                switch ($tagName) {
                    case 'blockquote':

                    case 'dd':

                    case 'div':

                    case 'header':

                    case 'section':

                    case 'footer':

                    case 'nav':

                    case 'article':

                    case 'aside':
                        $blockSplit[$k] = $tag . $this->TS_transform_rte($this->removeFirstAndLastTag($blockSplit[$k]), $css) . '</' . $tagName . '>';
                        break;
                }
                $blockSplit[$k + 1] = preg_replace('/^[ ]*' . LF . '/', '', $blockSplit[$k + 1]);
            } else {
                // NON-block:
                $nextFTN = $this->getFirstTagName($blockSplit[$k + 1]);
                $onlyLineBreaks = (preg_match('/^[ ]*' . LF . '+[ ]*$/', $blockSplit[$k]) == 1);
                // If the line is followed by a block or is the last line:
                if (GeneralUtility::inList($blockElementList, $nextFTN) || !isset($blockSplit[$k + 1])) {
                    // If the line contains more than just linebreaks, reduce the number of trailing linebreaks by 1
                    if (!$onlyLineBreaks) {
                        $blockSplit[$k] = preg_replace('/(' . LF . '*)' . LF . '[ ]*$/', '$1', $blockSplit[$k]);
                    } else {
                        // If the line contains only linebreaks, remove the leading linebreak
                        $blockSplit[$k] = preg_replace('/^[ ]*' . LF . '/', '', $blockSplit[$k]);
                    }
                }
                // If $blockSplit[$k] is blank then unset the line, unless the line only contained linebreaks
                if ((string)$blockSplit[$k] === '' && !$onlyLineBreaks) {
                    unset($blockSplit[$k]);
                } else {
                    $blockSplit[$k] = $this->setDivTags($blockSplit[$k], $this->procOptions['useDIVasParagraphTagForRTE'] ? 'div' : 'p');
                }
            }
        }
        return implode(LF, $blockSplit);
    }

    /**
     * Transformation handler: 'ts_strip' / direction: "db"
     * Removing all non-allowed tags
     *
     * @param string $value Content input
     * @return string Content output
     */
    public function TS_strip_db($value)
    {
        $value = strip_tags($value, '<' . implode('><', explode(',', 'b,i,u,a,img,br,div,center,pre,font,hr,sub,sup,p,strong,em,li,ul,ol,blockquote')) . '>');
        return $value;
    }

    /***************************************************************
     *
     * Generic RTE transformation, analysis and helper functions
     *
     **************************************************************/
    /**
     * Reads the file or url $url and returns the content
     *
     * @param string $url Filepath/URL to read
     * @return string The content from the resource given as input.
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl()
     */
    public function getUrl($url)
    {
        return GeneralUtility::getUrl($url);
    }

    /**
     * Function for cleaning content going into the database.
     * Content is cleaned eg. by removing unallowed HTML and ds-HSC content
     * It is basically calling HTMLcleaner from the parent class with some preset configuration specifically set up for cleaning content going from the RTE into the db
     *
     * @param string $content Content to clean up
     * @param string $tagList Comma list of tags to specifically allow. Default comes from getKeepTags and is
     * @return string Clean content
     * @see getKeepTags()
     */
    public function HTMLcleaner_db($content, $tagList = '')
    {
        if (!$tagList) {
            $keepTags = $this->getKeepTags('db');
        } else {
            $keepTags = $this->getKeepTags('db', $tagList);
        }
        // Default: remove unknown tags.
        $kUknown = $this->procOptions['dontRemoveUnknownTags_db'] ? 1 : 0;
        // Default: re-convert literals to characters (that is &lt; to <)
        $hSC = $this->procOptions['dontUndoHSC_db'] ? 0 : -1;
        // Create additional configuration in order to honor the setting RTE.default.proc.HTMLparser_db.xhtml_cleaning=1
        $addConfig = [];
        if (is_array($this->procOptions['HTMLparser_db.']) && $this->procOptions['HTMLparser_db.']['xhtml_cleaning'] || is_array($this->procOptions['entryHTMLparser_db.']) && $this->procOptions['entryHTMLparser_db.']['xhtml_cleaning'] || is_array($this->procOptions['exitHTMLparser_db.']) && $this->procOptions['exitHTMLparser_db.']['xhtml_cleaning']) {
            $addConfig['xhtml'] = 1;
        }
        return $this->HTMLcleaner($content, $keepTags, $kUknown, $hSC, $addConfig);
    }

    /**
     * Creates an array of configuration for the HTMLcleaner function based on whether content go TO or FROM the Rich Text Editor ($direction)
     * Unless "tagList" is given, the function will cache the configuration for next time processing goes on. (In this class that is the case only if we are processing a bulletlist)
     *
     * @param string $direction The direction of the content being processed by the output configuration; "db" (content going into the database FROM the rte) or "rte" (content going into the form)
     * @param string $tagList Comma list of tags to keep (overriding default which is to keep all + take notice of internal configuration)
     * @return array Configuration array
     * @see HTMLcleaner_db()
     */
    public function getKeepTags($direction = 'rte', $tagList = '')
    {
        if (!is_array($this->getKeepTags_cache[$direction]) || $tagList) {
            // Setting up allowed tags:
            // If the $tagList input var is set, this will take precedence
            if ((string)$tagList !== '') {
                $keepTags = array_flip(GeneralUtility::trimExplode(',', $tagList, true));
            } else {
                // Default is to get allowed/denied tags from internal array of processing options:
                // Construct default list of tags to keep:
                $typoScript_list = 'b,i,u,a,img,br,div,center,pre,font,hr,sub,sup,p,strong,em,li,ul,ol,blockquote,strike,span';
                $keepTags = array_flip(GeneralUtility::trimExplode(',', $typoScript_list . ',' . strtolower($this->procOptions['allowTags']), true));
                // For tags to deny, remove them from $keepTags array:
                $denyTags = GeneralUtility::trimExplode(',', $this->procOptions['denyTags'], true);
                foreach ($denyTags as $dKe) {
                    unset($keepTags[$dKe]);
                }
            }
            // Based on the direction of content, set further options:
            switch ($direction) {
                case 'rte':
                    if (!isset($this->procOptions['transformBoldAndItalicTags']) || $this->procOptions['transformBoldAndItalicTags']) {
                        // Transform bold/italics tags to strong/em
                        if (isset($keepTags['b'])) {
                            $keepTags['b'] = ['remap' => 'STRONG'];
                        }
                        if (isset($keepTags['i'])) {
                            $keepTags['i'] = ['remap' => 'EM'];
                        }
                    }
                    // Transforming keepTags array so it can be understood by the HTMLcleaner function. This basically converts the format of the array from TypoScript (having .'s) to plain multi-dimensional array.
                    list($keepTags) = $this->HTMLparserConfig($this->procOptions['HTMLparser_rte.'], $keepTags);
                    break;
                case 'db':
                    if (!isset($this->procOptions['transformBoldAndItalicTags']) || $this->procOptions['transformBoldAndItalicTags']) {
                        // Transform strong/em back to bold/italics:
                        if (isset($keepTags['strong'])) {
                            $keepTags['strong'] = ['remap' => 'b'];
                        }
                        if (isset($keepTags['em'])) {
                            $keepTags['em'] = ['remap' => 'i'];
                        }
                    }
                    // Setting up span tags if they are allowed:
                    if (isset($keepTags['span'])) {
                        $classes = array_merge([''], $this->allowedClasses);
                        $keepTags['span'] = [
                            'allowedAttribs' => 'id,class,style,title,lang,xml:lang,dir,itemscope,itemtype,itemprop',
                            'fixAttrib' => [
                                'class' => [
                                    'list' => $classes,
                                    'removeIfFalse' => 1
                                ]
                            ],
                            'rmTagIfNoAttrib' => 1
                        ];
                        if (!$this->procOptions['allowedClasses']) {
                            unset($keepTags['span']['fixAttrib']['class']['list']);
                        }
                    }
                    // Setting up font tags if they are allowed:
                    if (isset($keepTags['font'])) {
                        $colors = array_merge([''], GeneralUtility::trimExplode(',', $this->procOptions['allowedFontColors'], true));
                        $keepTags['font'] = [
                            'allowedAttribs' => 'face,color,size',
                            'fixAttrib' => [
                                'face' => [
                                    'removeIfFalse' => 1
                                ],
                                'color' => [
                                    'removeIfFalse' => 1,
                                    'list' => $colors
                                ],
                                'size' => [
                                    'removeIfFalse' => 1
                                ]
                            ],
                            'rmTagIfNoAttrib' => 1
                        ];
                        if (!$this->procOptions['allowedFontColors']) {
                            unset($keepTags['font']['fixAttrib']['color']['list']);
                        }
                    }
                    // Setting further options, getting them from the processiong options:
                    $TSc = $this->procOptions['HTMLparser_db.'];
                    if (!$TSc['globalNesting']) {
                        $TSc['globalNesting'] = 'b,i,u,a,center,font,sub,sup,strong,em,strike,span';
                    }
                    if (!$TSc['noAttrib']) {
                        $TSc['noAttrib'] = 'b,i,u,br,center,hr,sub,sup,strong,em,li,ul,ol,blockquote,strike';
                    }
                    // Transforming the array from TypoScript to regular array:
                    list($keepTags) = $this->HTMLparserConfig($TSc, $keepTags);
                    break;
            }
            // Caching (internally, in object memory) the result unless tagList is set:
            if (!$tagList) {
                $this->getKeepTags_cache[$direction] = $keepTags;
            } else {
                return $keepTags;
            }
        }
        // Return result:
        return $this->getKeepTags_cache[$direction];
    }

    /**
     * This resolves the $value into parts based on <div></div>-sections and <p>-sections and <br />-tags. These are returned as lines separated by LF.
     * This point is to resolve the HTML-code returned from RTE into ordinary lines so it's 'human-readable'
     * The function ->setDivTags does the opposite.
     * This function processes content to go into the database.
     *
     * @param string $value Value to process.
     * @param int $count Recursion brake. Decremented on each recursion down to zero. Default is 5 (which equals the allowed nesting levels of p/div tags).
     * @param bool $returnArray If TRUE, an array with the lines is returned, otherwise a string of the processed input value.
     * @return string Processed input value.
     * @see setDivTags()
     */
    public function divideIntoLines($value, $count = 5, $returnArray = false)
    {
        // Setting configuration for processing:
        $allowTagsOutside = GeneralUtility::trimExplode(',', strtolower($this->procOptions['allowTagsOutside'] ? 'hr,' . $this->procOptions['allowTagsOutside'] : 'hr,img'), true);
        $remapParagraphTag = strtoupper($this->procOptions['remapParagraphTag']);
        $divSplit = $this->splitIntoBlock('div,p', $value, 1);
        // Setting the third param to 1 will eliminate false end-tags. Maybe this is a good thing to do...?
        if ($this->procOptions['keepPDIVattribs']) {
            $keepAttribListArr = GeneralUtility::trimExplode(',', strtolower($this->procOptions['keepPDIVattribs']), true);
        } else {
            $keepAttribListArr = [];
        }
        // Returns plainly the value if there was no div/p sections in it
        if (count($divSplit) <= 1 || $count <= 0) {
            // Wrap hr tags with LF's
            $newValue = preg_replace('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/i', LF . '<$1$2/>' . LF, $value);
            $newValue = preg_replace('/' . LF . LF . '/i', LF, $newValue);
            $newValue = preg_replace('/(^' . LF . ')|(' . LF . '$)/i', '', $newValue);
            return $newValue;
        }
        // Traverse the splitted sections:
        foreach ($divSplit as $k => $v) {
            if ($k % 2) {
                // Inside
                $v = $this->removeFirstAndLastTag($v);
                // Fetching 'sub-lines' - which will explode any further p/div nesting...
                $subLines = $this->divideIntoLines($v, $count - 1, 1);
                // So, if there happend to be sub-nesting of p/div, this is written directly as the new content of THIS section. (This would be considered 'an error')
                if (is_array($subLines)) {
                } else {
                    //... but if NO subsection was found, we process it as a TRUE line without erronous content:
                    $subLines = [$subLines];
                    // process break-tags, if configured for. Simply, the breaktags will here be treated like if each was a line of content...
                    if (!$this->procOptions['dontConvBRtoParagraph']) {
                        $subLines = preg_split('/<br[[:space:]]*[\\/]?>/i', $v);
                    }
                    // Traverse sublines (there is typically one, except if <br/> has been converted to lines as well!)
                    foreach ($subLines as $sk => $value) {
                        // Clear up the subline for DB.
                        $subLines[$sk] = $this->HTMLcleaner_db($subLines[$sk]);
                        // Get first tag, attributes etc:
                        $fTag = $this->getFirstTag($divSplit[$k]);
                        $tagName = strtolower($this->getFirstTagName($divSplit[$k]));
                        $attribs = $this->get_tag_attributes($fTag);
                        // Keep attributes (lowercase)
                        $newAttribs = [];
                        if (!empty($keepAttribListArr)) {
                            foreach ($keepAttribListArr as $keepA) {
                                if (isset($attribs[0][$keepA])) {
                                    $newAttribs[$keepA] = $attribs[0][$keepA];
                                }
                            }
                        }
                        // ALIGN attribute:
                        if (!$this->procOptions['skipAlign'] && trim($attribs[0]['align']) !== '' && strtolower($attribs[0]['align']) != 'left') {
                            // Set to value, but not 'left'
                            $newAttribs['align'] = strtolower($attribs[0]['align']);
                        }
                        // CLASS attribute:
                        // Set to whatever value
                        if (!$this->procOptions['skipClass'] && trim($attribs[0]['class']) !== '') {
                            if (empty($this->allowedClasses) || in_array($attribs[0]['class'], $this->allowedClasses)) {
                                $newAttribs['class'] = $attribs[0]['class'];
                            } else {
                                $classes = GeneralUtility::trimExplode(' ', $attribs[0]['class'], true);
                                $newClasses = [];
                                foreach ($classes as $class) {
                                    if (in_array($class, $this->allowedClasses)) {
                                        $newClasses[] = $class;
                                    }
                                }
                                if (!empty($newClasses)) {
                                    $newAttribs['class'] = implode(' ', $newClasses);
                                }
                            }
                        }
                        // Remove any line break char (10 or 13)
                        $subLines[$sk] = preg_replace('/' . LF . '|' . CR . '/', '', $subLines[$sk]);
                        // If there are any attributes or if we are supposed to remap the tag, then do so:
                        if (!empty($newAttribs) && $remapParagraphTag !== '1') {
                            if ($remapParagraphTag === 'P') {
                                $tagName = 'p';
                            }
                            if ($remapParagraphTag === 'DIV') {
                                $tagName = 'div';
                            }
                            $subLines[$sk] = '<' . trim($tagName . ' ' . $this->compileTagAttribs($newAttribs)) . '>' . $subLines[$sk] . '</' . $tagName . '>';
                        }
                    }
                }
                // Add the processed line(s)
                $divSplit[$k] = implode(LF, $subLines);
                // If it turns out the line is just blank (containing a &nbsp; possibly) then just make it pure blank.
                // But, prevent filtering of lines that are blank in sense above, but whose tags contain attributes.
                // Those attributes should have been filtered before; if they are still there they must be considered as possible content.
                if (trim(strip_tags($divSplit[$k])) == '&nbsp;' && !preg_match('/\\<(img)(\\s[^>]*)?\\/?>/si', $divSplit[$k]) && !preg_match('/\\<([^>]*)?( align| class| style| id| title| dir| lang| xml:lang)([^>]*)?>/si', trim($divSplit[$k]))) {
                    $divSplit[$k] = '';
                }
            } else {
                // outside div:
                // Remove positions which are outside div/p tags and without content
                $divSplit[$k] = trim(strip_tags($divSplit[$k], '<' . implode('><', $allowTagsOutside) . '>'));
                // Wrap hr tags with LF's
                $divSplit[$k] = preg_replace('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/i', LF . '<$1$2/>' . LF, $divSplit[$k]);
                $divSplit[$k] = preg_replace('/' . LF . LF . '/i', LF, $divSplit[$k]);
                $divSplit[$k] = preg_replace('/(^' . LF . ')|(' . LF . '$)/i', '', $divSplit[$k]);
                if ((string)$divSplit[$k] === '') {
                    unset($divSplit[$k]);
                }
            }
        }
        // Return value:
        return $returnArray ? $divSplit : implode(LF, $divSplit);
    }

    /**
     * Converts all lines into <div></div>/<p></p>-sections (unless the line is a div-section already)
     * For processing of content going FROM database TO RTE.
     *
     * @param string $value Value to convert
     * @param string $dT Tag to wrap with. Either "p" or "div" should it be. Lowercase preferably.
     * @return string Processed value.
     * @see divideIntoLines()
     */
    public function setDivTags($value, $dT = 'p')
    {
        // First, setting configuration for the HTMLcleaner function. This will process each line between the <div>/<p> section on their way to the RTE
        $keepTags = $this->getKeepTags('rte');
        // Default: remove unknown tags.
        $kUknown = $this->procOptions['dontProtectUnknownTags_rte'] ? 0 : 'protect';
        // Default: re-convert literals to characters (that is &lt; to <)
        $hSC = $this->procOptions['dontHSC_rte'] ? 0 : 1;
        $convNBSP = !$this->procOptions['dontConvAmpInNBSP_rte'] ? 1 : 0;
        // Divide the content into lines, based on LF:
        $parts = explode(LF, $value);
        foreach ($parts as $k => $v) {
            // Processing of line content:
            // If the line is blank, set it to &nbsp;
            if (trim($parts[$k]) === '') {
                $parts[$k] = '&nbsp;';
            } else {
                // Clean the line content:
                $parts[$k] = $this->HTMLcleaner($parts[$k], $keepTags, $kUknown, $hSC);
                if ($convNBSP) {
                    $parts[$k] = str_replace('&amp;nbsp;', '&nbsp;', $parts[$k]);
                }
            }
            // Wrapping the line in <$dT> if not already wrapped and does not contain an hr tag
            if (!preg_match('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/i', $parts[$k])) {
                $testStr = strtolower(trim($parts[$k]));
                if (substr($testStr, 0, 4) != '<div' || substr($testStr, -6) != '</div>') {
                    if (substr($testStr, 0, 2) != '<p' || substr($testStr, -4) != '</p>') {
                        // Only set p-tags if there is not already div or p tags:
                        $parts[$k] = '<' . $dT . '>' . $parts[$k] . '</' . $dT . '>';
                    }
                }
            }
        }
        // Implode result:
        return implode(LF, $parts);
    }

    /**
     * Returns SiteURL based on thisScript.
     *
     * @return string Value of GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()
     */
    public function siteUrl()
    {
        return GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
    }

    /**
     * Remove all tables from incoming code
     * The function is trying to to this is some more or less respectfull way. The approach is to resolve each table cells content and implode it all by <br /> chars. Thus at least the content is preserved in some way.
     *
     * @param string $value Input value
     * @param string $breakChar Break character to use for linebreaks.
     * @return string Output value
     */
    public function removeTables($value, $breakChar = '<br />')
    {
        // Splitting value into table blocks:
        $tableSplit = $this->splitIntoBlock('table', $value);
        // Traverse blocks of tables:
        foreach ($tableSplit as $k => $v) {
            if ($k % 2) {
                $tableSplit[$k] = '';
                $rowSplit = $this->splitIntoBlock('tr', $v);
                foreach ($rowSplit as $k2 => $v2) {
                    if ($k2 % 2) {
                        $cellSplit = $this->getAllParts($this->splitIntoBlock('td', $v2), 1, 0);
                        foreach ($cellSplit as $k3 => $v3) {
                            $tableSplit[$k] .= $v3 . $breakChar;
                        }
                    }
                }
            }
        }
        // Implode it all again:
        return implode($breakChar, $tableSplit);
    }

    /**
     * Default tag mapping for TS
     *
     * @param string $code Input code to process
     * @param string $direction Direction To databsae (db) or from database to RTE (rte)
     * @return string Processed value
     */
    public function defaultTStagMapping($code, $direction = 'rte')
    {
        if ($direction == 'db') {
            $code = $this->mapTags($code, [
                // Map tags
                'strong' => 'b',
                'em' => 'i'
            ]);
        }
        if ($direction == 'rte') {
            $code = $this->mapTags($code, [
                // Map tags
                'b' => 'strong',
                'i' => 'em'
            ]);
        }
        return $code;
    }

    /**
     * Finds width and height from attrib-array
     * If the width and height is found in the style-attribute, use that!
     *
     * @param array $attribArray Array of attributes from tag in which to search. More specifically the content of the key "style" is used to extract "width:xxx / height:xxx" information
     * @return array Integer w/h in key 0/1. Zero is returned if not found.
     */
    public function getWHFromAttribs($attribArray)
    {
        $style = trim($attribArray['style']);
        if ($style) {
            $regex = '[[:space:]]*:[[:space:]]*([0-9]*)[[:space:]]*px';
            // Width
            $reg = [];
            preg_match('/width' . $regex . '/i', $style, $reg);
            $w = (int)$reg[1];
            // Height
            preg_match('/height' . $regex . '/i', $style, $reg);
            $h = (int)$reg[1];
        }
        if (!$w) {
            $w = $attribArray['width'];
        }
        if (!$h) {
            $h = $attribArray['height'];
        }
        return [(int)$w, (int)$h];
    }

    /**
     * Parse <A>-tag href and return status of email,external,file or page
     *
     * @param string $url URL to analyse.
     * @return array Information in an array about the URL
     */
    public function urlInfoForLinkTags($url)
    {
        $info = [];
        $url = trim($url);
        if (substr(strtolower($url), 0, 7) == 'mailto:') {
            $info['url'] = trim(substr($url, 7));
            $info['type'] = 'email';
        } elseif (strpos($url, '?file:') !== false) {
            $info['type'] = 'file';
            $info['url'] = rawurldecode(substr($url, strpos($url, '?file:') + 1));
        } else {
            $curURL = $this->siteUrl();
            $urlLength = strlen($url);
            for ($a = 0; $a < $urlLength; $a++) {
                if ($url[$a] != $curURL[$a]) {
                    break;
                }
            }
            $info['relScriptPath'] = substr($curURL, $a);
            $info['relUrl'] = substr($url, $a);
            $info['url'] = $url;
            $info['type'] = 'ext';
            $siteUrl_parts = parse_url($url);
            $curUrl_parts = parse_url($curURL);
            // Hosts should match
            if ($siteUrl_parts['host'] == $curUrl_parts['host'] && (!$info['relScriptPath'] || defined('TYPO3_mainDir') && substr($info['relScriptPath'], 0, strlen(TYPO3_mainDir)) == TYPO3_mainDir)) {
                // If the script path seems to match or is empty (FE-EDIT)
                // New processing order 100502
                $uP = parse_url($info['relUrl']);
                if ($info['relUrl'] === '#' . $siteUrl_parts['fragment']) {
                    $info['url'] = $info['relUrl'];
                    $info['type'] = 'anchor';
                } elseif (!trim($uP['path']) || $uP['path'] === 'index.php') {
                    // URL is a page (id parameter)
                    $pp = preg_split('/^id=/', $uP['query']);
                    $pp[1] = preg_replace('/&id=[^&]*/', '', $pp[1]);
                    $parameters = explode('&', $pp[1]);
                    $id = array_shift($parameters);
                    if ($id) {
                        $info['pageid'] = $id;
                        $info['cElement'] = $uP['fragment'];
                        $info['url'] = $id . ($info['cElement'] ? '#' . $info['cElement'] : '');
                        $info['type'] = 'page';
                        $info['query'] = $parameters[0] ? '&' . implode('&', $parameters) : '';
                    }
                } else {
                    $info['url'] = $info['relUrl'];
                    $info['type'] = 'file';
                }
            } else {
                unset($info['relScriptPath']);
                unset($info['relUrl']);
            }
        }
        return $info;
    }

    /**
     * Converting <A>-tags to absolute URLs (+ setting rtekeep attribute)
     *
     * @param string $value Content input
     * @param bool $dontSetRTEKEEP If TRUE, then the "rtekeep" attribute will not be set.
     * @return string Content output
     */
    public function TS_AtagToAbs($value, $dontSetRTEKEEP = false)
    {
        $blockSplit = $this->splitIntoBlock('A', $value);
        foreach ($blockSplit as $k => $v) {
            // Block
            if ($k % 2) {
                $attribArray = $this->get_tag_attributes_classic($this->getFirstTag($v), 1);
                // Checking if there is a scheme, and if not, prepend the current url.
                // ONLY do this if href has content - the <a> tag COULD be an anchor and if so, it should be preserved...
                if ($attribArray['href'] !== '') {
                    $uP = parse_url(strtolower($attribArray['href']));
                    if (!$uP['scheme']) {
                        $attribArray['href'] = $this->siteUrl() . substr($attribArray['href'], strlen($this->relBackPath));
                    } elseif ($uP['scheme'] != 'mailto') {
                        $attribArray['data-htmlarea-external'] = 1;
                    }
                } else {
                    $attribArray['rtekeep'] = 1;
                }
                if (!$dontSetRTEKEEP) {
                    $attribArray['rtekeep'] = 1;
                }
                $bTag = '<a ' . GeneralUtility::implodeAttributes($attribArray, 1) . '>';
                $eTag = '</a>';
                $blockSplit[$k] = $bTag . $this->TS_AtagToAbs($this->removeFirstAndLastTag($blockSplit[$k])) . $eTag;
            }
        }
        return implode('', $blockSplit);
    }

    /**
     * Apply plain image settings to the dimensions of the image
     *
     * @param array $imageInfo: info array of the image
     * @param array $attribArray: array of attributes of an image tag
     *
     * @return array a modified attributes array
     */
    protected function applyPlainImageModeSettings($imageInfo, $attribArray)
    {
        if ($this->procOptions['plainImageMode']) {
            // Perform corrections to aspect ratio based on configuration
            switch ((string)$this->procOptions['plainImageMode']) {
                case 'lockDimensions':
                    $attribArray['width'] = $imageInfo[0];
                    $attribArray['height'] = $imageInfo[1];
                    break;
                case 'lockRatioWhenSmaller':
                    if ($attribArray['width'] > $imageInfo[0]) {
                        $attribArray['width'] = $imageInfo[0];
                    }
                case 'lockRatio':
                    if ($imageInfo[0] > 0) {
                        $attribArray['height'] = round($attribArray['width'] * ($imageInfo[1] / $imageInfo[0]));
                    }
                    break;
            }
        }
        return $attribArray;
    }

    /**
    * @return \TYPO3\CMS\Core\Log\Logger
    */
    protected function getLogger()
    {
        /** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
        $logManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class);

        return $logManager->getLogger(get_class($this));
    }
}
