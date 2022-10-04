<?php

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

namespace TYPO3\CMS\Core\Html;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Html\Event\BrokenLinkAnalysisEvent;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\HtmlSanitizer\Builder\BuilderInterface;

/**
 * Class for parsing HTML for the Rich Text Editor. (also called transformations)
 *
 * Concerning line breaks:
 * Regardless if LF (Unix-style) or CRLF (Windows) was put in, the HtmlParser works with LFs and migrates all
 * line breaks to LFs internally, however when all transformations are done, all LFs are transformed to CRLFs.
 * This means: RteHtmlParser always returns CRLFs to be maximum compatible with all formats.
 */
class RteHtmlParser extends HtmlParser implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * List of elements that are not wrapped into a "p" tag while doing the transformation.
     * @var string
     */
    protected $blockElementList = 'DIV,TABLE,BLOCKQUOTE,PRE,UL,OL,H1,H2,H3,H4,H5,H6,ADDRESS,DL,DD,HEADER,SECTION,FOOTER,NAV,ARTICLE,ASIDE';

    /**
     * List of all tags that are allowed by default
     * @var string
     */
    protected $defaultAllowedTagsList = 'b,i,u,a,img,br,div,center,pre,font,hr,sub,sup,p,strong,em,li,ul,ol,blockquote,strike,span,abbr,acronym,dfn';

    /**
     * Set to the TSconfig options coming from Page TSconfig
     *
     * @var array
     */
    protected $procOptions = [];

    /**
     * Run-away brake for recursive calls.
     *
     * @var int
     */
    protected $TS_transform_db_safecounter = 100;

    /**
     * Data caching for processing function
     *
     * @var array
     */
    protected $getKeepTags_cache = [];

    /**
     * Storage of the allowed CSS class names in the RTE
     *
     * @var array
     */
    protected $allowedClasses = [];

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
        'itemprop',
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
        'section',
    ];

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Sanitize and streamline given options (usually from RichTextConfiguration results "proc."
     * and set them to the respective properties.
     *
     * @param array $processingConfiguration
     */
    protected function setProcessingConfiguration(array $processingConfiguration): void
    {
        $this->procOptions = $processingConfiguration;
        if (isset($this->procOptions['allowedClasses.'])) {
            $this->allowedClasses = (array)$this->procOptions['allowedClasses.'];
        } else {
            $this->allowedClasses = GeneralUtility::trimExplode(',', $this->procOptions['allowedClasses'] ?? '', true);
        }

        // Dynamic configuration of blockElementList
        if (!empty($this->procOptions['blockElementList'])) {
            $this->blockElementList = $this->procOptions['blockElementList'];
        }

        // Define which attributes are allowed on <p> tags
        if (isset($this->procOptions['allowAttributes.'])) {
            $this->allowedAttributesForParagraphTags = $this->procOptions['allowAttributes.'];
        }
        // Override tags which are allowed outside of <p> tags
        if (isset($this->procOptions['allowTagsOutside'])) {
            if (!isset($this->procOptions['allowTagsOutside.'])) {
                $this->allowedTagsOutsideOfParagraphs = GeneralUtility::trimExplode(',', strtolower($this->procOptions['allowTagsOutside']), true);
            } else {
                $this->allowedTagsOutsideOfParagraphs = (array)$this->procOptions['allowTagsOutside.'];
            }
        }
    }

    /**
     * Main entry point for transforming RTE content in the database so the Rich Text Editor can deal with
     * e.g. links.
     *
     * @param string $value
     * @param array $processingConfiguration
     * @return string
     */
    public function transformTextForRichTextEditor(string $value, array $processingConfiguration): string
    {
        $this->setProcessingConfiguration($processingConfiguration);
        $modes = $this->resolveAppliedTransformationModes('rte');
        $value = $this->streamlineLineBreaksForProcessing($value);
        // If an entry HTML cleaner was configured, pass the content through the HTMLcleaner
        $value = $this->runHtmlParserIfConfigured($value, 'entryHTMLparser_rte');
        // Traverse modes
        foreach ($modes as $cmd) {
            // Checking for user defined transformation:
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation'][$cmd])) {
                trigger_error(
                    'The hook "t3lib/class.t3lib_parsehtml_proc.php->transformation"' .
                    ' will be removed in TYPO3 v12. ',
                    E_USER_DEPRECATED
                );
                $_procObj = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation'][$cmd]);
                $_procObj->pObj = $this;
                $value = $_procObj->transform_rte($value, $this);
            } else {
                // ... else use defaults:
                switch ($cmd) {
                    case 'detectbrokenlinks':
                        $value = $this->markBrokenLinks($value);
                        break;
                    case 'css_transform':
                        $value = $this->TS_transform_rte($value);
                        break;
                    default:
                        // Do nothing
                }
            }
        }
        // If an exit HTML cleaner was configured, pass the content through the HTMLcleaner
        $value = $this->runHtmlParserIfConfigured($value, 'exitHTMLparser_rte');
        // Final clean up of linebreaks
        $value = $this->streamlineLineBreaksAfterProcessing($value);
        return $value;
    }

    /**
     * Called to process HTML content before it is stored in the database.
     *
     * @param string $value
     * @param array $processingConfiguration
     * @return string
     */
    public function transformTextForPersistence(string $value, array $processingConfiguration): string
    {
        $this->setProcessingConfiguration($processingConfiguration);
        $modes = $this->resolveAppliedTransformationModes('db');
        $value = $this->streamlineLineBreaksForProcessing($value);
        // If an entry HTML cleaner was configured, pass the content through the HTMLcleaner
        $value = $this->runHtmlParserIfConfigured($value, 'entryHTMLparser_db');
        // Traverse modes
        foreach ($modes as $cmd) {
            // Checking for user defined transformation:
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation'][$cmd])) {
                trigger_error(
                    'The hook "t3lib/class.t3lib_parsehtml_proc.php->transformation"' .
                    ' will be removed in TYPO3 v12. ',
                    E_USER_DEPRECATED
                );
                $_procObj = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation'][$cmd]);
                $_procObj->pObj = $this;
                $_procObj->transformationKey = $cmd;
                $value = $_procObj->transform_db($value, $this);
            } else {
                // ... else use defaults:
                switch ($cmd) {
                    case 'detectbrokenlinks':
                        $value = $this->removeBrokenLinkMarkers($value);
                        break;
                    case 'ts_links':
                        $value = $this->TS_links_db($value);
                        break;
                    case 'css_transform':
                        // Transform empty paragraphs into spacing paragraphs
                        $value = str_replace('<p></p>', '<p>&nbsp;</p>', $value);
                        // Double any trailing spacing paragraph so that it does not get removed by divideIntoLines()
                        $value = preg_replace('/<p>&nbsp;<\/p>$/', '<p>&nbsp;</p><p>&nbsp;</p>', $value) ?? $value;
                        $value = $this->TS_transform_db($value);
                        break;
                    default:
                        // Do nothing
                }
            }
        }
        // process markup with HTML Sanitizer
        $value = $this->htmlSanitize($value, $this->procOptions['HTMLparser_db.'] ?? []);
        // If an exit HTML cleaner was configured, pass the content through the HTMLcleaner
        $value = $this->runHtmlParserIfConfigured($value, 'exitHTMLparser_db');
        // Final clean up of linebreaks
        $value = $this->streamlineLineBreaksAfterProcessing($value);
        return $value;
    }

    /**
     * Ensures what transformation modes should be executed, and that they are only executed once.
     *
     * @param string $direction
     * @return array the resolved transformation modes
     */
    protected function resolveAppliedTransformationModes(string $direction): array
    {
        // Setting modes / transformations to be called
        if ((string)($this->procOptions['overruleMode'] ?? '') !== '') {
            $modes = GeneralUtility::trimExplode(',', $this->procOptions['overruleMode']);
        } else {
            $modes = [$this->procOptions['mode']];
        }

        $modeList = implode(',', $modes);

        // Replace the shortcut "default" with all custom modes
        $modeList = str_replace('default', 'detectbrokenlinks,css_transform,ts_links', $modeList);

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
        if (!empty($this->procOptions[$configurationDirective])) {
            [$keepTags, $keepNonMatchedTags, $hscMode, $additionalConfiguration] = $this->HTMLparserConfig($this->procOptions[$configurationDirective . '.']);
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
     * Transformation handler: 'ts_links' / direction: "db"
     * Processing anchor tags, and resolves them correctly again via the LinkService syntax
     *
     * Splits content into <a> tag blocks and processes each tag, and allows hooks to actually render
     * the result.
     *
     * @param string $value Content input
     * @return string Content output
     */
    protected function TS_links_db($value)
    {
        $blockSplit = $this->splitIntoBlock('A', $value);
        foreach ($blockSplit as $k => $v) {
            if ($k % 2) {
                [$tagAttributes] = $this->get_tag_attributes($this->getFirstTag($v), true);

                // Anchors would not have an href attribute
                if (!isset($tagAttributes['href'])) {
                    continue;
                }
                $linkService = GeneralUtility::makeInstance(LinkService::class);
                // Store the link as <a> tag as default by TYPO3, with the link service syntax
                try {
                    $linkInformation = $linkService->resolve($tagAttributes['href'] ?? '');
                    $tagAttributes['href'] = $linkService->asString($linkInformation);
                } catch (UnknownLinkHandlerException $e) {
                    $tagAttributes['href'] = $linkInformation['href'] ?? $tagAttributes['href'];
                }

                $blockSplit[$k] = '<a ' . GeneralUtility::implodeAttributes($tagAttributes, true) . '>'
                    . $this->TS_links_db($this->removeFirstAndLastTag($blockSplit[$k])) . '</a>';
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
    protected function TS_transform_db($value)
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
                        $blockSplit[$k] = preg_replace('/[' . LF . ']+/', ' ', $blockSplit[$k]);
                }
            } else {
                // NON-block:
                if (trim($blockSplit[$k]) !== '') {
                    $string = $blockSplit[$k];
                    $string = preg_replace('#<([a-z]+)/>#', '<$1 />', $string);
                    // Remove linebreaks preceding hr tags
                    $string = preg_replace('/[' . LF . ']+<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/', '<$1$2/>', $string) ?? '';
                    // Remove linebreaks following hr tags
                    $string = preg_replace('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>[' . LF . ']+/', '<$1$2/>', $string) ?? '';
                    // Replace other linebreaks with space
                    $string = preg_replace('/[' . LF . ']+/', ' ', $string);
                    // process allowed/removed tags
                    $string = $this->HTMLcleaner(
                        (string)$string,
                        $this->getKeepTags('db'),
                        $this->procOptions['HTMLparser_db.']['keepNonMatchedTags'] ?? '',
                        (int)($this->procOptions['HTMLparser_db.']['htmlSpecialChars'] ?? 0)
                    );
                    $blockSplit[$k] = (string)$this->divideIntoLines($string);
                } else {
                    unset($blockSplit[$k]);
                }
            }
        }
        $this->TS_transform_db_safecounter++;
        return implode(LF, $blockSplit);
    }

    /**
     * Transformation handler: css_transform / direction: "rte"
     * Set (->rte) for standard content elements (ts)
     *
     * @param string $value Content input
     * @return string Content output
     * @see TS_transform_db()
     */
    protected function TS_transform_rte($value)
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
                $nextFTN = $this->getFirstTagName($blockSplit[$k + 1] ?? '');
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
    protected function HTMLcleaner_db($content)
    {
        $keepTags = $this->getKeepTags('db');
        return $this->HTMLcleaner($content, $keepTags, false);
    }

    /**
     * Creates an array of configuration for the HTMLcleaner function based on whether content
     * go TO or FROM the Rich Text Editor ($direction)
     *
     * @param string $direction The direction of the content being processed by the output configuration; "db" (content going into the database FROM the rte) or "rte" (content going into the form)
     * @return array Configuration array
     * @see HTMLcleaner_db()
     */
    protected function getKeepTags($direction = 'rte')
    {
        if (!isset($this->getKeepTags_cache[$direction]) || !is_array($this->getKeepTags_cache[$direction])) {
            // Setting up allowed tags:
            // Default is to get allowed/denied tags from internal array of processing options:
            // Construct default list of tags to keep:
            if (isset($this->procOptions['allowTags.']) && is_array($this->procOptions['allowTags.'])) {
                $keepTags = implode(',', $this->procOptions['allowTags.']);
            } else {
                $keepTags = $this->procOptions['allowTags'] ?? '';
            }
            $keepTags = array_flip(GeneralUtility::trimExplode(',', $this->defaultAllowedTagsList . ',' . strtolower($keepTags), true));
            // For tags to deny, remove them from $keepTags array:
            $denyTags = GeneralUtility::trimExplode(',', $this->procOptions['denyTags'] ?? '', true);
            foreach ($denyTags as $dKe) {
                unset($keepTags[$dKe]);
            }
            // Based on the direction of content, set further options:
            switch ($direction) {
                case 'rte':
                    // Transforming keepTags array so it can be understood by the HTMLcleaner function.
                    // This basically converts the format of the array from TypoScript (having dots) to plain multi-dimensional array.
                    [$keepTags] = $this->HTMLparserConfig($this->procOptions['HTMLparser_rte.'] ?? [], $keepTags);
                    break;
                case 'db':
                    // Setting up span tags if they are allowed:
                    if (isset($keepTags['span'])) {
                        $keepTags['span'] = [
                            'allowedAttribs' => 'id,class,style,title,lang,xml:lang,dir,itemscope,itemtype,itemprop',
                            'fixAttrib' => [
                                'class' => [
                                    'removeIfFalse' => 1,
                                ],
                            ],
                            'rmTagIfNoAttrib' => 1,
                        ];
                        if (!empty($this->allowedClasses)) {
                            $keepTags['span']['fixAttrib']['class']['list'] = $this->allowedClasses;
                        }
                    }
                    // Setting further options, getting them from the processing options
                    $TSc = $this->procOptions['HTMLparser_db.'] ?? [];
                    if (empty($TSc['globalNesting'])) {
                        $TSc['globalNesting'] = 'b,i,u,a,center,font,sub,sup,strong,em,strike,span';
                    }
                    if (empty($TSc['noAttrib'])) {
                        $TSc['noAttrib'] = 'b,i,u,br,center,hr,sub,sup,strong,em,li,ul,ol,blockquote,strike';
                    }
                    // Transforming the array from TypoScript to regular array:
                    [$keepTags] = $this->HTMLparserConfig($TSc, $keepTags);
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
    protected function divideIntoLines($value, $count = 5, $returnArray = false)
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
    protected function setDivTags($value)
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
            $partFirstTagName = strtolower($this->getFirstTagName($parts[$k] ?? ''));
            // Wrapping the line in <p> tags if not already wrapped and does not contain an hr tag and is not allowed outside of paragraphs.
            if (!in_array($partFirstTagName, $this->allowedTagsOutsideOfParagraphs, true) && !preg_match('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/i', $partFirstTagName)) {
                $testStr = strtolower(trim($parts[$k]));
                if (strpos($testStr, '<div') !== 0 || substr($testStr, -6) !== '</div>') {
                    if (strpos($testStr, '<p') !== 0 || substr($testStr, -4) !== '</p>') {
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
            [$tagAttributes] = $this->get_tag_attributes($fTag);
            // Make sure the tag attributes only contain the ones that are defined to be allowed
            $tagAttributes = array_intersect_key($tagAttributes, array_flip($this->allowedAttributesForParagraphTags));

            // Only allow classes that are whitelisted in $this->allowedClasses
            if (isset($tagAttributes['class']) && trim($tagAttributes['class']) !== '' && !empty($this->allowedClasses) && !in_array($tagAttributes['class'], $this->allowedClasses, true)) {
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
        $content = preg_replace('/<(hr)(\\s[^>\\/]*)?[[:space:]]*\\/?>/i', LF . '<$1$2/>' . LF, $content) ?? $content;
        $content = str_replace(LF . LF, LF, $content);
        $content = preg_replace('/(^' . LF . ')|(' . LF . '$)/i', '', $content) ?? $content;
        return $content;
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
            [$attributes] = $this->get_tag_attributes($this->getFirstTag($value), true);
            if (empty($attributes['href'])) {
                continue;
            }

            try {
                $hrefInformation = $linkService->resolve($attributes['href']);

                $brokenLinkAnalysis = new BrokenLinkAnalysisEvent($hrefInformation['type'], $hrefInformation);
                $this->eventDispatcher->dispatch($brokenLinkAnalysis);
                if ($brokenLinkAnalysis->isBrokenLink()) {
                    $attributes['data-rte-error'] = $brokenLinkAnalysis->getReason();
                }
            } catch (InsufficientFolderAccessPermissionsException $e) {
                // do nothing if user doesn't have access to the file/folder
            } catch (UnknownLinkHandlerException $e) {
                $attributes['data-rte-error'] = $e->getMessage();
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
            [$attributes] = $this->get_tag_attributes($this->getFirstTag($value), true);
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

    protected function htmlSanitize(string $content, array $configuration): string
    {
        $features = GeneralUtility::makeInstance(Features::class);
        // either `htmlSanitize = null` or `htmlSanitize = false`
        // or feature flag `security.backend.htmlSanitizeRte` is disabled
        if (array_key_exists('htmlSanitize', $configuration) && empty($configuration['htmlSanitize'])
            || !$features->isFeatureEnabled('security.backend.htmlSanitizeRte')
        ) {
            return $content;
        }

        $build = $configuration['htmlSanitize.']['build'] ?? 'default';
        if (class_exists($build) && is_a($build, BuilderInterface::class, true)) {
            $builder = GeneralUtility::makeInstance($build);
        } else {
            $factory = GeneralUtility::makeInstance(SanitizerBuilderFactory::class);
            $builder = $factory->build($build);
        }
        $sanitizer = $builder->build();
        $initiator = GeneralUtility::makeInstance(SanitizerInitiator::class, get_class($this));
        return $sanitizer->sanitize($content, $initiator);
    }
}
