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
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * Class for parsing HTML for the Rich Text Editor. (also called transformations)
 *
 * Concerning line breaks:
 * Regardless if LF (Unix-style) or CRLF (Windows) was put in, the HtmlParser works with LFs and migrates all
 * line breaks to LFs internally, however when all transformations are done, all LFs are transformed to CRLFs.
 * This means: RteHtmlParser always returns CRLFs to be maximum compatible with all formats.
 */
class RteHtmlParser extends HtmlParser
{
    /**
     * List of elements that are not wrapped into a "p" tag while doing the transformation.
     * @var string
     */
    public $blockElementList = 'DIV,TABLE,BLOCKQUOTE,PRE,UL,OL,H1,H2,H3,H4,H5,H6,ADDRESS,DL,DD,HEADER,SECTION,FOOTER,NAV,ARTICLE,ASIDE';

    /**
     * List of all tags that are allowed by default
     * @var string
     */
    protected $defaultAllowedTagsList = 'b,i,u,a,img,br,div,center,pre,font,hr,sub,sup,p,strong,em,li,ul,ol,blockquote,strike,span';

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
     * A list of HTML attributes for <p> tags. Because <p> tags are wrapped currently in a special handling,
     * they have a special place for configuration via 'proc.keepPDIVattribs'
     *
     * @var array
     */
    protected $allowedAttributesForParagraphTags = [
        'class',
        'align',
        'id',
        'title',
        'dir',
        'lang',
        'xml:lang',
        'itemscope',
        'itemtype',
        'itemprop'
    ];

    /**
     * Any tags that are allowed outside of <p> sections - usually similar to the block elements
     * plus some special tags like <hr> and <img> (if images are allowed).
     * Completely overrideable via 'proc.allowTagsOutside'
     *
     * @var array
     */
    protected $allowedTagsOutsideOfParagraphs = [
        'address',
        'article',
        'aside',
        'blockquote',
        'div',
        'footer',
        'header',
        'hr',
        'nav',
        'section'
    ];

    /**
     * Initialize, setting element reference and record PID
     *
     * @param string $elRef Element reference, eg "tt_content:bodytext
     * @param int $recPid PID of the record (page id)
     */
    public function init($elRef = '', $recPid = 0)
    {
        $this->recPid = $recPid;
        $this->elRef = $elRef;
    }

    /**********************************************
     *
     * Main function
     *
     **********************************************/
    /**
     * Transform value for RTE based on specConf in the direction specified by $direction (rte/db)
     * This is the main function called from DataHandler and transfer data classes
     *
     * @param string $value Input value
     * @param array $specConf deprecated old "defaultExtras" parsed as array
     * @param string $direction Direction of the transformation. Two keywords are allowed; "db" or "rte". If "db" it means the transformation will clean up content coming from the Rich Text Editor and goes into the database. The other direction, "rte", is of course when content is coming from database and must be transformed to fit the RTE.
     * @param array $thisConfig Parsed TypoScript content configuring the RTE, probably coming from Page TSconfig.
     * @return string Output value
     */
    public function RTE_transform($value, $specConf = [], $direction = 'rte', $thisConfig = [])
    {
        $this->tsConfig = $thisConfig;
        $this->procOptions = (array)$thisConfig['proc.'];
        if (isset($this->procOptions['allowedClasses.'])) {
            $this->allowedClasses = (array)$this->procOptions['allowedClasses.'];
        } else {
            $this->allowedClasses = GeneralUtility::trimExplode(',', $this->procOptions['allowedClasses'], true);
        }

        // Dynamic configuration of blockElementList
        if ($this->procOptions['blockElementList']) {
            $this->blockElementList = $this->procOptions['blockElementList'];
        }

        // Define which attributes are allowed on <p> tags
        if (isset($this->procOptions['allowAttributes.'])) {
            $this->allowedAttributesForParagraphTags = $this->procOptions['allowAttributes.'];
        } elseif (isset($this->procOptions['keepPDIVattribs'])) {
            $this->allowedAttributesForParagraphTags = GeneralUtility::trimExplode(',', strtolower($this->procOptions['keepPDIVattribs']), true);
        }
        // Override tags which are allowed outside of <p> tags
        if (isset($this->procOptions['allowTagsOutside'])) {
            if (!isset($this->procOptions['allowTagsOutside.'])) {
                $this->allowedTagsOutsideOfParagraphs = GeneralUtility::trimExplode(',', strtolower($this->procOptions['allowTagsOutside']), true);
            } else {
                $this->allowedTagsOutsideOfParagraphs = (array)$this->procOptions['allowTagsOutside.'];
            }
        }

        // Setting modes / transformations to be called
        if ((string)$this->procOptions['overruleMode'] !== '') {
            $modes = GeneralUtility::trimExplode(',', $this->procOptions['overruleMode']);
        } elseif (!empty($this->procOptions['mode'])) {
            $modes = [$this->procOptions['mode']];
        } else {
            // Get parameters for rte_transformation:
            // @deprecated since TYPO3 v8, will be removed in TYPO3 v9 - the else{} part can be removed in v9
            GeneralUtility::deprecationLog(
                'Argument 2 of RteHtmlParser::RTE_transform() is deprecated. Transformations should be given in $thisConfig[\'proc.\'][\'overruleMode\']'
            );
            $specialFieldConfiguration = BackendUtility::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
            $modes = GeneralUtility::trimExplode('-', $specialFieldConfiguration['mode']);
        }
        $modes = $this->resolveAppliedTransformationModes($direction, $modes);

        $value = $this->streamlineLineBreaksForProcessing($value);

        // If an entry HTML cleaner was configured, pass the content through the HTMLcleaner
        $value = $this->runHtmlParserIfConfigured($value, 'entryHTMLparser_' . $direction);

        // Traverse modes
        foreach ($modes as $cmd) {
            if ($direction === 'db') {
                // Checking for user defined transformation:
                if ($_classRef = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation'][$cmd]) {
                    $_procObj = GeneralUtility::getUserObj($_classRef);
                    $_procObj->pObj = $this;
                    $_procObj->transformationKey = $cmd;
                    $value = $_procObj->transform_db($value, $this);
                } else {
                    // ... else use defaults:
                    switch ($cmd) {
                        case 'detectbrokenlinks':
                            $value = $this->removeBrokenLinkMarkers($value);
                            break;
                        case 'ts_images':
                            $value = $this->TS_images_db($value);
                            break;
                        case 'ts_links':
                            $value = $this->TS_links_db($value);
                            break;
                        case 'css_transform':
                            // Transform empty paragraphs into spacing paragraphs
                            $value = str_replace('<p></p>', '<p>&nbsp;</p>', $value);
                            // Double any trailing spacing paragraph so that it does not get removed by divideIntoLines()
                            $value = preg_replace('/<p>&nbsp;<\/p>$/', '<p>&nbsp;</p>' . '<p>&nbsp;</p>', $value);
                            $value = $this->TS_transform_db($value);
                            break;
                        default:
                            // Do nothing
                    }
                }
            } elseif ($direction === 'rte') {
                // Checking for user defined transformation:
                if ($_classRef = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation'][$cmd]) {
                    $_procObj = GeneralUtility::getUserObj($_classRef);
                    $_procObj->pObj = $this;
                    $value = $_procObj->transform_rte($value, $this);
                } else {
                    // ... else use defaults:
                    switch ($cmd) {
                        case 'detectbrokenlinks':
                            $value = $this->markBrokenLinks($value);
                            break;
                        case 'ts_images':
                            $value = $this->TS_images_rte($value);
                            break;
                        case 'ts_links':
                            $value = $this->TS_links_rte($value);
                            break;
                        case 'css_transform':
                            $value = $this->TS_transform_rte($value);
                            break;
                        default:
                            // Do nothing
                    }
                }
            }
        }

        // If an exit HTML cleaner was configured, pass the content through the HTMLcleaner
        $value = $this->runHtmlParserIfConfigured($value, 'exitHTMLparser_' . $direction);

        // Final clean up of linebreaks
        $value = $this->streamlineLineBreaksAfterProcessing($value);

        return $value;
    }

    /**
     * Ensures what transformation modes should be executed, and that they are only executed once.
     *
     * @param string $direction
     * @param array $modes
     * @return array the resolved transformation modes
     */
    protected function resolveAppliedTransformationModes(string $direction, array $modes)
    {
        $modeList = implode(',', $modes);

        // Replace the shortcut "default" with all custom modes
        $modeList = str_replace('default', 'detectbrokenlinks,css_transform,ts_images,ts_links', $modeList);
        // Replace the shortcut "ts_css" with all custom modes
        // @deprecated since TYPO3 v8, will be removed in TYPO3 v9 - NEXT line can be removed in v9
        $modeList = str_replace('ts_css', 'detectbrokenlinks,css_transform,ts_images,ts_links', $modeList);

        // Make list unique
        $modes = array_unique(GeneralUtility::trimExplode(',', $modeList, true));
        // Reverse order if direction is "rte"
        if ($direction === 'rte') {
            $modes = array_reverse($modes);
        }

        return $modes;
    }

    /**
     * Runs the HTML parser if it is configured
     * Getting additional HTML cleaner configuration. These are applied either before or after the main transformation
     * is done and thus totally independent processing options you can set up.
     *
     * This is only possible via TSconfig (procOptions) currently.
     *
     * @param string $content
     * @param string $configurationDirective used to look up in the procOptions if enabled, and then fetch the
     * @return string the processed content
     */
    protected function runHtmlParserIfConfigured($content, $configurationDirective)
    {
        if ($this->procOptions[$configurationDirective]) {
            list($keepTags, $keepNonMatchedTags, $hscMode, $additionalConfiguration) = $this->HTMLparserConfig($this->procOptions[$configurationDirective . '.']);
            $content = $this->HTMLcleaner($content, $keepTags, $keepNonMatchedTags, $hscMode, $additionalConfiguration);
        }
        return $content;
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
            $siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            $sitePath = str_replace(GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'), '', $siteUrl);
            /** @var $resourceFactory Resource\ResourceFactory */
            $resourceFactory = Resource\ResourceFactory::getInstance();
            /** @var $magicImageService Resource\Service\MagicImageService */
            $magicImageService = GeneralUtility::makeInstance(Resource\Service\MagicImageService::class);
            $magicImageService->setMagicImageMaximumDimensions($this->tsConfig);
            foreach ($imgSplit as $k => $v) {
                // Image found, do processing:
                if ($k % 2) {
                    // Get attributes
                    list($attribArray) = $this->get_tag_attributes($v, true);
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
                            $originalImageFile = $resourceFactory->getFileObject((int)$attribArray['data-htmlarea-file-uid']);
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
                                    $imageInfoObject = GeneralUtility::makeInstance(ImageInfo::class, $filePath);
                                    $imageInfo = [
                                        $imageInfoObject->getWidth(),
                                        $imageInfoObject->getHeight()
                                    ];
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
                        $externalFile = GeneralUtility::getUrl($absoluteUrl);
                        if ($externalFile) {
                            $pU = parse_url($absoluteUrl);
                            $pI = pathinfo($pU['path']);
                            $extension = strtolower($pI['extension']);
                            if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'gif' || $extension === 'png') {
                                $fileName = GeneralUtility::shortMD5($absoluteUrl) . '.' . $pI['extension'];
                                // We insert this image into the user default upload folder
                                list($table, $field) = explode(':', $this->elRef);
                                /** @var Resource\Folder $folder */
                                $folder = $GLOBALS['BE_USER']->getDefaultUploadFolder($this->recPid, $table, $field);
                                /** @var Resource\File $fileObject */
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
                                $imageInfoObject = GeneralUtility::makeInstance(ImageInfo::class, $filepath);
                                $imageInfo = [
                                    $imageInfoObject->getWidth(),
                                    $imageInfoObject->getHeight()
                                ];
                                $attribArray = $this->applyPlainImageModeSettings($imageInfo, $attribArray);
                            }
                            // Let's try to find a file uid for this image
                            try {
                                $fileOrFolderObject = $resourceFactory->retrieveFileOrFolderObject($path);
                                if ($fileOrFolderObject instanceof Resource\FileInterface) {
                                    $fileIdentifier = $fileOrFolderObject->getIdentifier();
                                    /** @var Resource\AbstractFile $fileObject */
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
                        $attribArray['src'] = substr($attribArray['src'], strlen($siteUrl));
                    }
                    $imgSplit[$k] = '<img ' . GeneralUtility::implodeAttributes($attribArray, true, true) . ' />';
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
            $siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            $sitePath = str_replace(GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'), '', $siteUrl);
            foreach ($imgSplit as $k => $v) {
                // Image found
                if ($k % 2) {
                    // Get the attributes of the img tag
                    list($attribArray) = $this->get_tag_attributes($v, true);
                    $absoluteUrl = trim($attribArray['src']);
                    // Transform the src attribute into an absolute url, if it not already
                    if (strtolower(substr($absoluteUrl, 0, 4)) !== 'http') {
                        // If site is in a subpath (eg. /~user_jim/) this path needs to be removed because it will be added with $siteUrl
                        $attribArray['src'] = preg_replace('#^' . preg_quote($sitePath, '#') . '#', '', $attribArray['src']);
                        $attribArray['src'] = $siteUrl . $attribArray['src'];
                    }
                    // Must have alt attribute
                    if (!isset($attribArray['alt'])) {
                        $attribArray['alt'] = '';
                    }
                    $imgSplit[$k] = '<img ' . GeneralUtility::implodeAttributes($attribArray, true, true) . ' />';
                }
            }
        }
        // Return processed content:
        return implode('', $imgSplit);
    }

    /**
     * Transformation handler: 'ts_links' / direction: "db"
     * Processing anchor tags, and resolves them correctly again via the LinkService syntax
     *
     * Splits content into <a> tag blocks and processes each tag, and allows hooks to actually render
     * the result.
     *
     * @param string $value Content input
     * @return string Content output
     * @see TS_links_rte()
     */
    public function TS_links_db($value)
    {
        $blockSplit = $this->splitIntoBlock('A', $value);
        foreach ($blockSplit as $k => $v) {
            if ($k % 2) {
                list($tagAttributes) = $this->get_tag_attributes($this->getFirstTag($v), true);
                $linkService = GeneralUtility::makeInstance(LinkService::class);
                $linkInformation = $linkService->resolve($tagAttributes['href'] ?? '');

                // Modify parameters, this hook should be deprecated
                if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksDb_PostProc'])
                    && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksDb_PostProc'])) {
                    $parameters = [
                        'currentBlock' => $v,
                        'linkInformation' => $linkInformation,
                        'url' => $linkInformation['href'],
                        'attributes' => $tagAttributes
                    ];
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksDb_PostProc'] as $objRef) {
                        $processor = GeneralUtility::getUserObj($objRef);
                        $blockSplit[$k] = $processor->modifyParamsLinksDb($parameters, $this);
                    }
                } else {
                    // Otherwise store the link as <a> tag as default by TYPO3, with the new link service syntax
                    $tagAttributes['href'] = $linkService->asString($linkInformation);
                    $blockSplit[$k] = '<a ' . GeneralUtility::implodeAttributes($tagAttributes, true) . '>'
                        . $this->TS_links_db($this->removeFirstAndLastTag($blockSplit[$k])) . '</a>';
                }
            }
        }
        return implode('', $blockSplit);
    }

    /**
     * Transformation handler: 'ts_links' / direction: "rte"
     * Converting TYPO3-specific <link> tags to <a> tags
     *
     * This functionality is only used to convert legacy <link> tags to the new linking syntax using <a> tags, and will
     * not be converted back to <link> tags anymore.
     *
     * @param string $value Content input
     * @return string Content output
     */
    public function TS_links_rte($value)
    {
        $value = $this->TS_AtagToAbs($value);
        // Split content by the TYPO3 pseudo tag "<link>"
        $blockSplit = $this->splitIntoBlock('link', $value, true);
        foreach ($blockSplit as $k => $v) {
            // Block
            if ($k % 2) {
                // Split away the first "<link " part
                $typoLinkData = explode(' ', substr($this->getFirstTag($v), 0, -1), 2)[1];
                $tagCode = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($typoLinkData);

                // Parsing the TypoLink data. This parsing is done like in \TYPO3\CMS\Frontend\ContentObject->typoLink()
                $linkService = GeneralUtility::makeInstance(LinkService::class);
                $linkInformation = $linkService->resolve($tagCode['url']);

                try {
                    $href = $linkService->asString($linkInformation);
                } catch (UnknownLinkHandlerException $e) {
                    $href = '';
                }

                // Modify parameters by a hook
                if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc'])) {
                    // backwards-compatibility: show an error message if the page is not found
                    $error = '';
                    if ($linkInformation['type'] === LinkService::TYPE_PAGE) {
                        $pageRecord = BackendUtility::getRecord('pages', $linkInformation['pageuid']);
                        // Page does not exist
                        if (!is_array($pageRecord)) {
                            $error = 'Page with ID ' . $linkInformation['pageuid'] . ' not found';
                        }
                    }
                    $parameters = [
                        'currentBlock' => $v,
                        'url' => $href,
                        'tagCode' => $tagCode,
                        'external' => $linkInformation['type'] === LinkService::TYPE_URL,
                        'error' => $error
                    ];
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc'] as $objRef) {
                        $processor = GeneralUtility::getUserObj($objRef);
                        $blockSplit[$k] = $processor->modifyParamsLinksRte($parameters, $this);
                    }
                } else {
                    $anchorAttributes = [
                        'href'   => $href,
                        'target' => $tagCode['target'],
                        'class'  => $tagCode['class'],
                        'title'  => $tagCode['title']
                    ];

                    // Setting the <a> tag
                    $blockSplit[$k] = '<a ' . GeneralUtility::implodeAttributes($anchorAttributes, true) . '>'
                        . $this->TS_links_rte($this->removeFirstAndLastTag($blockSplit[$k]))
                        . '</a>';
                }
            }
        }
        return implode('', $blockSplit);
    }

    /**
     * Transformation handler: 'css_transform' / direction: "db"
     * Cleaning (->db) for standard content elements (ts)
     *
     * @param string $value Content input
     * @return string Content output
     * @see TS_transform_rte()
     */
    public function TS_transform_db($value)
    {
        // Safety... so forever loops are avoided (they should not occur, but an error would potentially do this...)
        $this->TS_transform_db_safecounter--;
        if ($this->TS_transform_db_safecounter < 0) {
            return $value;
        }
        // Split the content from RTE by the occurrence of these blocks:
        $blockSplit = $this->splitIntoBlock($this->blockElementList, $value);

        // Avoid superfluous linebreaks by transform_db after ending headListTag
        while (count($blockSplit) > 0 && trim(end($blockSplit)) === '') {
            array_pop($blockSplit);
        }

        // Traverse the blocks
        foreach ($blockSplit as $k => $v) {
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
                        $blockSplit[$k] = $tag . $this->TS_transform_db($this->removeFirstAndLastTag($blockSplit[$k])) . '</' . $tagName . '>';
                        break;
                    case 'pre':
                        break;
                    default:
                        // usually <hx> tags and <table> tags where no other block elements are within the tags
                        // Eliminate true linebreaks inside block element tags
                        $blockSplit[$k] = preg_replace(('/[' . LF . ']+/'), ' ', $blockSplit[$k]);
                }
            } else {
                // NON-block:
                if (trim($blockSplit[$k]) !== '') {
                    $blockSplit[$k] = str_replace('<hr/>', '<hr />', $blockSplit[$k]);
                    // Remove linebreaks preceding hr tags
                    $blockSplit[$k] = preg_replace('/[' . LF . ']+<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/', '<$1$2/>', $blockSplit[$k]);
                    // Remove linebreaks following hr tags
                    $blockSplit[$k] = preg_replace('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>[' . LF . ']+/', '<$1$2/>', $blockSplit[$k]);
                    // Replace other linebreaks with space
                    $blockSplit[$k] = preg_replace('/[' . LF . ']+/', ' ', $blockSplit[$k]);
                    $blockSplit[$k] = $this->divideIntoLines($blockSplit[$k]);
                } else {
                    unset($blockSplit[$k]);
                }
            }
        }
        $this->TS_transform_db_safecounter++;
        return implode(LF, $blockSplit);
    }

    /**
     * Wraps a-tags that contain a style attribute with a span-tag
     * This is not in use anymore, but was necessary before because <a> tags are transformed into <link> tags
     * in the database, but <link> tags cannot handle style attributes. However, this is considered a
     * bad approach as it leaves an ugly <span> tag in the database, if allowedTags=span with style attributes are
     * allowed.
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
                list($attribArray) = $this->get_tag_attributes($this->getFirstTag($v), true);
                // If "style" attribute is set and rteerror is not set!
                if ($attribArray['style'] && !$attribArray['rteerror']) {
                    $attribArray_copy['style'] = $attribArray['style'];
                    unset($attribArray['style']);
                    $bTag = '<span ' . GeneralUtility::implodeAttributes($attribArray_copy, true) . '><a ' . GeneralUtility::implodeAttributes($attribArray, true) . '>';
                    $eTag = '</a></span>';
                    $blockSplit[$k] = $bTag . $this->removeFirstAndLastTag($blockSplit[$k]) . $eTag;
                }
            }
        }
        return implode('', $blockSplit);
    }

    /**
     * Transformation handler: css_transform / direction: "rte"
     * Set (->rte) for standard content elements (ts)
     *
     * @param string $value Content input
     * @return string Content output
     * @see TS_transform_db()
     */
    public function TS_transform_rte($value)
    {
        // Split the content from database by the occurrence of the block elements
        $blockSplit = $this->splitIntoBlock($this->blockElementList, $value);
        // Traverse the blocks
        foreach ($blockSplit as $k => $v) {
            if ($k % 2) {
                // Inside one of the blocks:
                // Init:
                $tag = $this->getFirstTag($v);
                $tagName = strtolower($this->getFirstTagName($v));
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
                        $blockSplit[$k] = $tag . $this->TS_transform_rte($this->removeFirstAndLastTag($blockSplit[$k])) . '</' . $tagName . '>';
                        break;
                }
                $blockSplit[$k + 1] = preg_replace('/^[ ]*' . LF . '/', '', $blockSplit[$k + 1]);
            } else {
                // NON-block:
                $nextFTN = $this->getFirstTagName($blockSplit[$k + 1]);
                $onlyLineBreaks = (preg_match('/^[ ]*' . LF . '+[ ]*$/', $blockSplit[$k]) == 1);
                // If the line is followed by a block or is the last line:
                if (GeneralUtility::inList($this->blockElementList, $nextFTN) || !isset($blockSplit[$k + 1])) {
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
                    $blockSplit[$k] = $this->setDivTags($blockSplit[$k]);
                }
            }
        }
        return implode(LF, $blockSplit);
    }

    /***************************************************************
     *
     * Generic RTE transformation, analysis and helper functions
     *
     **************************************************************/

    /**
     * Function for cleaning content going into the database.
     * Content is cleaned eg. by removing unallowed HTML and ds-HSC content
     * It is basically calling HTMLcleaner from the parent class with some preset configuration specifically set up for cleaning content going from the RTE into the db
     *
     * @param string $content Content to clean up
     * @return string Clean content
     * @see getKeepTags()
     */
    public function HTMLcleaner_db($content)
    {
        $keepTags = $this->getKeepTags('db');
        // Default: remove unknown tags.
        $keepUnknownTags = (bool)$this->procOptions['dontRemoveUnknownTags_db'];
        return $this->HTMLcleaner($content, $keepTags, $keepUnknownTags);
    }

    /**
     * Creates an array of configuration for the HTMLcleaner function based on whether content
     * go TO or FROM the Rich Text Editor ($direction)
     *
     * @param string $direction The direction of the content being processed by the output configuration; "db" (content going into the database FROM the rte) or "rte" (content going into the form)
     * @return array Configuration array
     * @see HTMLcleaner_db()
     */
    public function getKeepTags($direction = 'rte')
    {
        if (!is_array($this->getKeepTags_cache[$direction])) {
            // Setting up allowed tags:
            // Default is to get allowed/denied tags from internal array of processing options:
            // Construct default list of tags to keep:
            if (is_array($this->procOptions['allowTags.'])) {
                $keepTags = implode(',', $this->procOptions['allowTags.']);
            } else {
                $keepTags = $this->procOptions['allowTags'];
            }
            $keepTags = array_flip(GeneralUtility::trimExplode(',', $this->defaultAllowedTagsList . ',' . strtolower($keepTags), true));
            // For tags to deny, remove them from $keepTags array:
            $denyTags = GeneralUtility::trimExplode(',', $this->procOptions['denyTags'], true);
            foreach ($denyTags as $dKe) {
                unset($keepTags[$dKe]);
            }
            // Based on the direction of content, set further options:
            switch ($direction) {
                case 'rte':
                    // Transforming keepTags array so it can be understood by the HTMLcleaner function.
                    // This basically converts the format of the array from TypoScript (having dots) to plain multi-dimensional array.
                    list($keepTags) = $this->HTMLparserConfig($this->procOptions['HTMLparser_rte.'], $keepTags);
                    break;
                case 'db':
                    // Setting up span tags if they are allowed:
                    if (isset($keepTags['span'])) {
                        $keepTags['span'] = [
                            'allowedAttribs' => 'id,class,style,title,lang,xml:lang,dir,itemscope,itemtype,itemprop',
                            'fixAttrib' => [
                                'class' => [
                                    'removeIfFalse' => 1
                                ]
                            ],
                            'rmTagIfNoAttrib' => 1
                        ];
                        if (!empty($this->allowedClasses)) {
                            $keepTags['span']['fixAttrib']['class']['list'] = $this->allowedClasses;
                        }
                    }
                    // Setting further options, getting them from the processing options
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
            // Caching (internally, in object memory) the result
            $this->getKeepTags_cache[$direction] = $keepTags;
        }
        // Return result:
        return $this->getKeepTags_cache[$direction];
    }

    /**
     * This resolves the $value into parts based on <p>-sections. These are returned as lines separated by LF.
     * This point is to resolve the HTML-code returned from RTE into ordinary lines so it's 'human-readable'
     * The function ->setDivTags does the opposite.
     * This function processes content to go into the database.
     *
     * @param string $value Value to process.
     * @param int $count Recursion brake. Decremented on each recursion down to zero. Default is 5 (which equals the allowed nesting levels of p tags).
     * @param bool $returnArray If TRUE, an array with the lines is returned, otherwise a string of the processed input value.
     * @return string|array Processed input value.
     * @see setDivTags()
     */
    public function divideIntoLines($value, $count = 5, $returnArray = false)
    {
        // Setting the third param will eliminate false end-tags. Maybe this is a good thing to do...?
        $paragraphBlocks = $this->splitIntoBlock('p', $value, true);
        // Returns plainly the content if there was no p sections in it
        if (count($paragraphBlocks) <= 1 || $count <= 0) {
            return $this->sanitizeLineBreaksForContentOnly($value);
        }

        // Traverse the splitted sections
        foreach ($paragraphBlocks as $k => $v) {
            if ($k % 2) {
                // Inside a <p> section
                $v = $this->removeFirstAndLastTag($v);
                // Fetching 'sub-lines' - which will explode any further p nesting recursively
                $subLines = $this->divideIntoLines($v, $count - 1, true);
                // So, if there happened to be sub-nesting of p, this is written directly as the new content of THIS section. (This would be considered 'an error')
                if (is_array($subLines)) {
                    $paragraphBlocks[$k] = implode(LF, $subLines);
                } else {
                    //... but if NO subsection was found, we process it as a TRUE line without erroneous content:
                    $paragraphBlocks[$k] = $this->processContentWithinParagraph($subLines, $paragraphBlocks[$k]);
                }
                // If it turns out the line is just blank (containing a &nbsp; possibly) then just make it pure blank.
                // But, prevent filtering of lines that are blank in sense above, but whose tags contain attributes.
                // Those attributes should have been filtered before; if they are still there they must be considered as possible content.
                if (trim(strip_tags($paragraphBlocks[$k])) === '&nbsp;' && !preg_match('/\\<(img)(\\s[^>]*)?\\/?>/si', $paragraphBlocks[$k]) && !preg_match('/\\<([^>]*)?( align| class| style| id| title| dir| lang| xml:lang)([^>]*)?>/si', trim($paragraphBlocks[$k]))) {
                    $paragraphBlocks[$k] = '';
                }
            } else {
                // Outside a paragraph, if there is still something in there, just add a <p> tag
                // Remove positions which are outside <p> tags and without content
                $paragraphBlocks[$k] = trim(strip_tags($paragraphBlocks[$k], '<' . implode('><', $this->allowedTagsOutsideOfParagraphs) . '>'));
                $paragraphBlocks[$k] = $this->sanitizeLineBreaksForContentOnly($paragraphBlocks[$k]);
                if ((string)$paragraphBlocks[$k] === '') {
                    unset($paragraphBlocks[$k]);
                } else {
                    // add <p> tags around the content
                    $paragraphBlocks[$k] = str_replace(strip_tags($paragraphBlocks[$k]), '<p>' . strip_tags($paragraphBlocks[$k]) . '</p>', $paragraphBlocks[$k]);
                }
            }
        }
        return $returnArray ? $paragraphBlocks : implode(LF, $paragraphBlocks);
    }

    /**
     * Converts all lines into <p></p>-sections (unless the line has a p - tag already)
     * For processing of content going FROM database TO RTE.
     *
     * @param string $value Value to convert
     * @return string Processed value.
     * @see divideIntoLines()
     */
    public function setDivTags($value)
    {
        // First, setting configuration for the HTMLcleaner function. This will process each line between the <div>/<p> section on their way to the RTE
        $keepTags = $this->getKeepTags('rte');
        // Divide the content into lines
        $parts = explode(LF, $value);
        foreach ($parts as $k => $v) {
            // Processing of line content:
            // If the line is blank, set it to &nbsp;
            if (trim($parts[$k]) === '') {
                $parts[$k] = '&nbsp;';
            } else {
                // Clean the line content, keeping unknown tags (as they can be removed in the entryHTMLparser)
                $parts[$k] = $this->HTMLcleaner($parts[$k], $keepTags, 'protect');
                // convert double-encoded &nbsp; into regular &nbsp; however this could also be reversed via the exitHTMLparser
                // This was previously an option to disable called "dontConvAmpInNBSP_rte"
                $parts[$k] = str_replace('&amp;nbsp;', '&nbsp;', $parts[$k]);
            }
            // Wrapping the line in <p> tags if not already wrapped and does not contain an hr tag
            if (!preg_match('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/i', $parts[$k])) {
                $testStr = strtolower(trim($parts[$k]));
                if (substr($testStr, 0, 4) !== '<div' || substr($testStr, -6) !== '</div>') {
                    if (substr($testStr, 0, 2) !== '<p' || substr($testStr, -4) !== '</p>') {
                        // Only set p-tags if there is not already div or p tags:
                        $parts[$k] = '<p>' . $parts[$k] . '</p>';
                    }
                }
            }
        }
        // Implode result:
        return implode(LF, $parts);
    }

    /**
     * Used for transformation from RTE to DB
     *
     * Works on a single line within a <p> tag when storing into the database
     * This always adds <p> tags and validates the arguments,
     * additionally the content is cleaned up via the HTMLcleaner.
     *
     * @param string $content the content within the <p> tag
     * @param string $fullContentWithTag the whole <p> tag surrounded as well
     *
     * @return string the full <p> tag with cleaned content
     */
    protected function processContentWithinParagraph(string $content, string $fullContentWithTag)
    {
        // clean up the content
        $content = $this->HTMLcleaner_db($content);
        // Get the <p> tag, and validate the attributes
        $fTag = $this->getFirstTag($fullContentWithTag);
        // Check which attributes of the <p> tag to keep attributes
        if (!empty($this->allowedAttributesForParagraphTags)) {
            list($tagAttributes) = $this->get_tag_attributes($fTag);
            // Make sure the tag attributes only contain the ones that are defined to be allowed
            $tagAttributes = array_intersect_key($tagAttributes, array_flip($this->allowedAttributesForParagraphTags));

            // Only allow classes that are whitelisted in $this->allowedClasses
            if (trim($tagAttributes['class']) !== '' && !empty($this->allowedClasses) && !in_array($tagAttributes['class'], $this->allowedClasses, true)) {
                $classes = GeneralUtility::trimExplode(' ', $tagAttributes['class'], true);
                $classes = array_intersect($classes, $this->allowedClasses);
                if (!empty($classes)) {
                    $tagAttributes['class'] = implode(' ', $classes);
                } else {
                    unset($tagAttributes['class']);
                }
            }
        } else {
            $tagAttributes = [];
        }
        // Remove any line break
        $content = str_replace(LF, '', $content);
        // Compile the surrounding <p> tag
        $content = '<' . rtrim('p ' . $this->compileTagAttribs($tagAttributes)) . '>' . $content . '</p>';
        return $content;
    }

    /**
     * Wrap <hr> tags with LFs, and also remove double LFs, used when transforming from RTE to DB
     *
     * @param string $content
     * @return string the modified content
     */
    protected function sanitizeLineBreaksForContentOnly(string $content)
    {
        $content = preg_replace('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/i', LF . '<$1$2/>' . LF, $content);
        $content = str_replace(LF . LF, LF, $content);
        $content = preg_replace('/(^' . LF . ')|(' . LF . '$)/i', '', $content);
        return $content;
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
        $w = 0;
        $h = 0;
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
     * This functionality is not in use anymore
     *
     * @param string $url URL to analyse.
     * @return array Information in an array about the URL
     */
    public function urlInfoForLinkTags($url)
    {
        $info = [];
        $url = trim($url);
        if (substr(strtolower($url), 0, 7) === 'mailto:') {
            $info['url'] = trim(substr($url, 7));
            $info['type'] = 'email';
        } elseif (strpos($url, '?file:') !== false) {
            $info['type'] = 'file';
            $info['url'] = rawurldecode(substr($url, strpos($url, '?file:') + 1));
        } else {
            $curURL = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            $urlLength = strlen($url);
            $a = 0;
            for (; $a < $urlLength; $a++) {
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
     * @param bool $dontSetRTEKEEP If TRUE, then the "rtekeep" attribute will not be set. (not in use anymore)
     * @return string Content output
     */
    public function TS_AtagToAbs($value, $dontSetRTEKEEP = false)
    {
        $blockSplit = $this->splitIntoBlock('A', $value);
        foreach ($blockSplit as $k => $v) {
            // Block
            if ($k % 2) {
                list($attribArray) = $this->get_tag_attributes($this->getFirstTag($v), true);
                // Checking if there is a scheme, and if not, prepend the current url.
                // ONLY do this if href has content - the <a> tag COULD be an anchor and if so, it should be preserved...
                if ($attribArray['href'] !== '') {
                    $uP = parse_url(strtolower($attribArray['href']));
                    if (!$uP['scheme']) {
                        $attribArray['href'] = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $attribArray['href'];
                    }
                }
                $bTag = '<a ' . GeneralUtility::implodeAttributes($attribArray, true) . '>';
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
                    if ($imageInfo[0] > 0) {
                        $attribArray['height'] = round($attribArray['width'] * ($imageInfo[1] / $imageInfo[0]));
                    }
                    break;
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
     * Called before any processing / transformation is made
     * Removing any CRs (char 13) and only deal with LFs (char 10) internally.
     * CR has a very disturbing effect, so just remove all CR and rely on LF
     *
     * Historical note: Previously it was possible to disable this functionality via disableUnifyLineBreaks.
     *
     * @param string $content the content to process
     * @return string the modified content
     */
    protected function streamlineLineBreaksForProcessing(string $content)
    {
        return str_replace(CR, '', $content);
    }

    /**
     * Called after any processing / transformation was made
     * just before the content is returned by the RTE parser all line breaks
     * get unified to be "CRLF"s again.
     *
     * Historical note: Previously it was possible to disable this functionality via disableUnifyLineBreaks.
     *
     * @param string $content the content to process
     * @return string the modified content
     */
    protected function streamlineLineBreaksAfterProcessing(string $content)
    {
        // Make sure no \r\n sequences has entered in the meantime
        $content = $this->streamlineLineBreaksForProcessing($content);
        // ... and then change all \n into \r\n
        return str_replace(LF, CRLF, $content);
    }

    /**
     * Content Transformation from DB to RTE
     * Checks all <a> tags which reference a t3://page and checks if the page is available
     * If not, some offensive styling is added.
     *
     * @param string $content
     * @return string the modified content
     */
    protected function markBrokenLinks(string $content): string
    {
        $blocks = $this->splitIntoBlock('A', $content);
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        foreach ($blocks as $position => $value) {
            if ($position % 2 === 0) {
                continue;
            }
            list($attributes) = $this->get_tag_attributes($this->getFirstTag($value), true);
            if (empty($attributes['href'])) {
                continue;
            }
            $hrefInformation = $linkService->resolve($attributes['href']);
            if ($hrefInformation['type'] === LinkService::TYPE_PAGE && $hrefInformation['pageuid'] !== 'current') {
                $pageRecord = BackendUtility::getRecord('pages', $hrefInformation['pageuid']);
                if (!is_array($pageRecord)) {
                    // Page does not exist
                    $attributes['data-rte-error'] = 'Page with ID ' . $hrefInformation['pageuid'] . ' not found';
                    $styling = 'background-color: yellow; border:2px red solid; color: black;';
                    if (empty($attributes['style'])) {
                        $attributes['style'] = $styling;
                    } else {
                        $attributes['style'] .= ' ' . $styling;
                    }
                }
            }
            // Always rewrite the block to allow the nested calling even if a page is found
            $blocks[$position] =
                '<a ' . GeneralUtility::implodeAttributes($attributes, true, true) . '>'
                . $this->markBrokenLinks($this->removeFirstAndLastTag($blocks[$position]))
                . '</a>';
        }
        return implode('', $blocks);
    }

    /**
     * Content Transformation from RTE to DB
     * Removes link information error attributes from <a> tags that are added to broken links
     *
     * @param string $content the content to process
     * @return string the modified content
     */
    protected function removeBrokenLinkMarkers(string $content): string
    {
        $blocks = $this->splitIntoBlock('A', $content);
        foreach ($blocks as $position => $value) {
            if ($position % 2 === 0) {
                continue;
            }
            list($attributes) = $this->get_tag_attributes($this->getFirstTag($value), true);
            if (empty($attributes['href'])) {
                continue;
            }
            // Always remove the styling again (regardless of the page was found or not)
            // so the database does not contain ugly stuff
            unset($attributes['data-rte-error']);
            if (isset($attributes['style'])) {
                $attributes['style'] = trim(str_replace('background-color: yellow; border:2px red solid; color: black;', '', $attributes['style']));
                if (empty($attributes['style'])) {
                    unset($attributes['style']);
                }
            }
            $blocks[$position] =
                '<a ' . GeneralUtility::implodeAttributes($attributes, true, true) . '>'
                . $this->removeBrokenLinkMarkers($this->removeFirstAndLastTag($blocks[$position]))
                . '</a>';
        }
        return implode('', $blocks);
    }

    /**
     * Instantiates a logger
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {
        /** @var $logManager LogManager */
        $logManager = GeneralUtility::makeInstance(LogManager::class);
        return $logManager->getLogger(get_class($this));
    }
}
