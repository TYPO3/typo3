<?php
namespace TYPO3\CMS\Core\Database;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * Soft Reference processing class
 * "Soft References" are references to database elements, files, email addresses, URls etc.
 * which are found in-text in content. The <link [page_id]> tag from typical bodytext fields
 * are an example of this.
 * This class contains generic parsers for the most well-known types
 * which are default for most TYPO3 installations. Soft References can also be userdefined.
 * The Soft Reference parsers are used by the system to find these references and process them accordingly in import/export actions and copy operations.
 *
 * Example of usage
 * Soft References:
 * if ($conf['softref'] && (strong)$value !== ''))	{	// Check if a TCA configured field has softreferences defined (see TYPO3 Core API document)
 * $softRefs = \TYPO3\CMS\Backend\Utility\BackendUtility::explodeSoftRefParserList($conf['softref']);		// Explode the list of softreferences/parameters
 * if ($softRefs !== FALSE) { // If there are soft references
 * foreach($softRefs as $spKey => $spParams)	{	// Traverse soft references
 * $softRefObj = \TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj($spKey);	// create / get object
 * if (is_object($softRefObj))	{	// If there was an object returned...:
 * $resultArray = $softRefObj->findRef($table, $field, $uid, $softRefValue, $spKey, $spParams);	// Do processing
 *
 * Result Array:
 * The Result array should contain two keys: "content" and "elements".
 * "content" is a string containing the input content but possibly with tokens inside.
 * Tokens are strings like {softref:[tokenID]} which is a placeholder for a value extracted by a softref parser
 * For each token there MUST be an entry in the "elements" key which has a "subst" key defining the tokenID and the tokenValue. See below.
 * "elements" is an array where the keys are insignificant, but the values are arrays with these keys:
 * "matchString" => The value of the match. This is only for informational purposes to show what was found.
 * "error"	=> An error message can be set here, like "file not found" etc.
 * "subst" => array(	// If this array is found there MUST be a token in the output content as well!
 * "tokenID" => The tokenID string corresponding to the token in output content, {softref:[tokenID]}. This is typically an md5 hash of a string defining uniquely the position of the element.
 * "tokenValue" => The value that the token substitutes in the text. Basically, if this value is inserted instead of the token the content should match what was inputted originally.
 * "type" => file / db / string	= the type of substitution. "file" means it is a relative file [automatically mapped], "db" means a database record reference [automatically mapped], "string" means it is manually modified string content (eg. an email address)
 * "relFileName" => (for "file" type): Relative filename. May not necessarily exist. This could be noticed in the error key.
 * "recordRef" => (for "db" type) : Reference to DB record on the form [table]:[uid]. May not necessarily exist.
 * "title" => Title of element (for backend information)
 * "description" => Description of element (for backend information)
 * )
 */
/**
 * Class for processing of the default soft reference types for CMS:
 *
 * - 'substitute' : A full field value targeted for manual substitution (for import /export features)
 * - 'notify' : Just report if a value is found, nothing more.
 * - 'images' : HTML <img> tags for RTE images
 * - 'typolink' : references to page id or file, possibly with anchor/target, possibly commaseparated list.
 * - 'typolink_tag' : As typolink, but searching for <link> tag to encapsulate it.
 * - 'email' : Email highlight
 * - 'url' : URL highlights (with a scheme)
 */
class SoftReferenceIndex
{
    /**
     * @var string
     */
    public $tokenID_basePrefix = '';

    /**
     * Main function through which all processing happens
     *
     * @param string $table Database table name
     * @param string $field Field name for which processing occurs
     * @param int $uid UID of the record
     * @param string $content The content/value of the field
     * @param string $spKey The softlink parser key. This is only interesting if more than one parser is grouped in the same class. That is the case with this parser.
     * @param array $spParams Parameters of the softlink parser. Basically this is the content inside optional []-brackets after the softref keys. Parameters are exploded by ";
     * @param string $structurePath If running from inside a FlexForm structure, this is the path of the tag.
     * @return array|bool Result array on positive matches, see description above. Otherwise FALSE
     */
    public function findRef($table, $field, $uid, $content, $spKey, $spParams, $structurePath = '')
    {
        $retVal = false;
        $this->tokenID_basePrefix = $table . ':' . $uid . ':' . $field . ':' . $structurePath . ':' . $spKey;
        switch ($spKey) {
            case 'notify':
                // Simple notification
                $resultArray = [
                    'elements' => [
                        [
                            'matchString' => $content
                        ]
                    ]
                ];
                $retVal = $resultArray;
                break;
            case 'substitute':
                $tokenID = $this->makeTokenID();
                $resultArray = [
                    'content' => '{softref:' . $tokenID . '}',
                    'elements' => [
                        [
                            'matchString' => $content,
                            'subst' => [
                                'type' => 'string',
                                'tokenID' => $tokenID,
                                'tokenValue' => $content
                            ]
                        ]
                    ]
                ];
                $retVal = $resultArray;
                break;
            case 'images':
                $retVal = $this->findRef_images($content);
                break;
            case 'typolink':
                $retVal = $this->findRef_typolink($content, $spParams);
                break;
            case 'typolink_tag':
                $retVal = $this->findRef_typolink_tag($content);
                break;
            case 'ext_fileref':
                $retVal = $this->findRef_extension_fileref($content);
                break;
            case 'email':
                $retVal = $this->findRef_email($content, $spParams);
                break;
            case 'url':
                $retVal = $this->findRef_url($content, $spParams);
                break;
            default:
                $retVal = false;
        }
        return $retVal;
    }

    /**
     * Finding image tags in the content.
     * All images that are not from external URLs will be returned with an info text
     * Will only return files in uploads/ folders which are prefixed with "RTEmagic[C|P]_" for substitution
     * Any "clear.gif" images are ignored.
     *
     * @param string $content The input content to analyze
     * @return array Result array on positive matches, see description above. Otherwise FALSE
     */
    public function findRef_images($content)
    {
        // Start HTML parser and split content by image tag:
        $htmlParser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
        $splitContent = $htmlParser->splitTags('img', $content);
        $elements = [];
        // Traverse splitted parts:
        foreach ($splitContent as $k => $v) {
            if ($k % 2) {
                // Get file reference:
                $attribs = $htmlParser->get_tag_attributes($v);
                $srcRef = htmlspecialchars_decode($attribs[0]['src']);
                $pI = pathinfo($srcRef);
                // If it looks like a local image, continue. Otherwise ignore it.
                $absPath = GeneralUtility::getFileAbsFileName(Environment::getPublicPath() . '/' . $srcRef);
                if (!$pI['scheme'] && !$pI['query'] && $absPath && $srcRef !== 'clear.gif') {
                    // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
                    // Initialize the element entry with info text here:
                    $tokenID = $this->makeTokenID($k);
                    $elements[$k] = [];
                    $elements[$k]['matchString'] = $v;
                    // If the image seems to be an RTE image, then proceed to set up substitution token:
                    if (GeneralUtility::isFirstPartOfStr($srcRef, 'uploads/') && preg_match('/^RTEmagicC_/', PathUtility::basename($srcRef))) {
                        // Token and substitute value:
                        // Make sure the value we work on is found and will get substituted in the content (Very important that the src-value is not DeHSC'ed)
                        if (strstr($splitContent[$k], $attribs[0]['src'])) {
                            // Substitute value with token (this is not be an exact method if the value is in there twice, but we assume it will not)
                            $splitContent[$k] = str_replace($attribs[0]['src'], '{softref:' . $tokenID . '}', $splitContent[$k]);
                            $elements[$k]['subst'] = [
                                'type' => 'file',
                                'relFileName' => $srcRef,
                                'tokenID' => $tokenID,
                                'tokenValue' => $attribs[0]['src']
                            ];
                            // Finally, notice if the file does not exist.
                            if (!@is_file($absPath)) {
                                $elements[$k]['error'] = 'File does not exist!';
                            }
                        } else {
                            $elements[$k]['error'] = 'Could not substitute image source with token!';
                        }
                    }
                }
            }
        }
        // Return result:
        if (!empty($elements)) {
            $resultArray = [
                'content' => implode('', $splitContent),
                'elements' => $elements
            ];
            return $resultArray;
        }
    }

    /**
     * TypoLink value processing.
     * Will process input value as a TypoLink value.
     *
     * @param string $content The input content to analyze
     * @param array $spParams Parameters set for the softref parser key in TCA/columns. value "linkList" will split the string by comma before processing.
     * @return array Result array on positive matches, see description above. Otherwise FALSE
     * @see \TYPO3\CMS\Frontend\ContentObject::typolink(), getTypoLinkParts()
     */
    public function findRef_typolink($content, $spParams)
    {
        // First, split the input string by a comma if the "linkList" parameter is set.
        // An example: the link field for images in content elements of type "textpic" or "image". This field CAN be configured to define a link per image, separated by comma.
        if (is_array($spParams) && in_array('linkList', $spParams)) {
            // Preserving whitespace on purpose.
            $linkElement = explode(',', $content);
        } else {
            // If only one element, just set in this array to make it easy below.
            $linkElement = [$content];
        }
        // Traverse the links now:
        $elements = [];
        foreach ($linkElement as $k => $typolinkValue) {
            $tLP = $this->getTypoLinkParts($typolinkValue);
            $linkElement[$k] = $this->setTypoLinkPartsElement($tLP, $elements, $typolinkValue, $k);
        }
        // Return output:
        if (!empty($elements)) {
            $resultArray = [
                'content' => implode(',', $linkElement),
                'elements' => $elements
            ];
            return $resultArray;
        }
    }

    /**
     * TypoLink tag processing.
     * Will search for <link ...> and <a> tags in the content string and process any found.
     *
     * @param string $content The input content to analyze
     * @return array Result array on positive matches, see description above. Otherwise FALSE
     * @see \TYPO3\CMS\Frontend\ContentObject::typolink(), getTypoLinkParts()
     */
    public function findRef_typolink_tag($content)
    {
        // Parse string for special TYPO3 <link> tag:
        $htmlParser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        $linkTags = $htmlParser->splitTags('a', $content);
        // Traverse result:
        $elements = [];
        foreach ($linkTags as $key => $foundValue) {
            if ($key % 2) {
                if (preg_match('/href="([^"]+)"/', $foundValue, $matches)) {
                    try {
                        $linkDetails = $linkService->resolve($matches[1]);
                        if ($linkDetails['type'] === LinkService::TYPE_FILE && preg_match('/file\?uid=(\d+)/', $matches[1], $fileIdMatch)) {
                            $token = $this->makeTokenID($key);
                            $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $linkTags[$key]);
                            $elements[$key]['subst'] = [
                                'type' => 'db',
                                'recordRef' => 'sys_file:' . $fileIdMatch[1],
                                'tokenID' => $token,
                                'tokenValue' => 'file:' . ($linkDetails['file'] instanceof File ? $linkDetails['file']->getUid() : $fileIdMatch[1])
                            ];
                        } elseif ($linkDetails['type'] === LinkService::TYPE_PAGE && preg_match('/page\?uid=(\d+)#?(\d+)?/', $matches[1], $pageAndAnchorMatches)) {
                            $token = $this->makeTokenID($key);
                            $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $linkTags[$key]);
                            $elements[$key]['subst'] = [
                                'type' => 'db',
                                'recordRef' => 'pages:' . $linkDetails['pageuid'] . (isset($pageAndAnchorMatches[2]) ? '#c' . $pageAndAnchorMatches[2] : ''),
                                'tokenID' => $token,
                                'tokenValue' => $linkDetails['pageuid'] . (isset($pageAndAnchorMatches[2]) ? '#c' . $pageAndAnchorMatches[2] : '')
                            ];
                        } elseif ($linkDetails['type'] === LinkService::TYPE_URL) {
                            $token = $this->makeTokenID($key);
                            $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $linkTags[$key]);
                            $elements[$key]['subst'] = [
                                'type' => 'external',
                                'tokenID' => $token,
                                'tokenValue' => $linkDetails['url']
                            ];
                        }
                    } catch (\Exception $e) {
                        // skip invalid links
                    }
                } else {
                    // keep the legacy code for now
                    $typolinkValue = preg_replace('/<LINK[[:space:]]+/i', '', substr($foundValue, 0, -1));
                    $tLP = $this->getTypoLinkParts($typolinkValue);
                    $linkTags[$k] = '<LINK ' . $this->setTypoLinkPartsElement($tLP, $elements, $typolinkValue, $k) . '>';
                }
            }
        }
        // Return output:
        if (!empty($elements)) {
            $resultArray = [
                'content' => implode('', $linkTags),
                'elements' => $elements
            ];
            return $resultArray;
        }
    }

    /**
     * Finding email addresses in content and making them substitutable.
     *
     * @param string $content The input content to analyze
     * @param array $spParams Parameters set for the softref parser key in TCA/columns
     * @return array Result array on positive matches, see description above. Otherwise FALSE
     */
    public function findRef_email($content, $spParams)
    {
        // Email:
        $parts = preg_split('/([^[:alnum:]]+)([A-Za-z0-9\\._-]+[@][A-Za-z0-9\\._-]+[\\.].[A-Za-z0-9]+)/', ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $idx => $value) {
            if ($idx % 3 == 2) {
                $tokenID = $this->makeTokenID($idx);
                $elements[$idx] = [];
                $elements[$idx]['matchString'] = $value;
                if (is_array($spParams) && in_array('subst', $spParams)) {
                    $parts[$idx] = '{softref:' . $tokenID . '}';
                    $elements[$idx]['subst'] = [
                        'type' => 'string',
                        'tokenID' => $tokenID,
                        'tokenValue' => $value
                    ];
                }
            }
        }
        // Return output:
        if (!empty($elements)) {
            $resultArray = [
                'content' => substr(implode('', $parts), 1, -1),
                'elements' => $elements
            ];
            return $resultArray;
        }
    }

    /**
     * Finding URLs in content
     *
     * @param string $content The input content to analyze
     * @param array $spParams Parameters set for the softref parser key in TCA/columns
     * @return array Result array on positive matches, see description above. Otherwise FALSE
     */
    public function findRef_url($content, $spParams)
    {
        // URLs
        $parts = preg_split('/([^[:alnum:]"\']+)((https?|ftp):\\/\\/[^[:space:]"\'<>]*)([[:space:]])/', ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $idx => $value) {
            if ($idx % 5 == 3) {
                unset($parts[$idx]);
            }
            if ($idx % 5 == 2) {
                $tokenID = $this->makeTokenID($idx);
                $elements[$idx] = [];
                $elements[$idx]['matchString'] = $value;
                if (is_array($spParams) && in_array('subst', $spParams)) {
                    $parts[$idx] = '{softref:' . $tokenID . '}';
                    $elements[$idx]['subst'] = [
                        'type' => 'string',
                        'tokenID' => $tokenID,
                        'tokenValue' => $value
                    ];
                }
            }
        }
        // Return output:
        if (!empty($elements)) {
            $resultArray = [
                'content' => substr(implode('', $parts), 1, -1),
                'elements' => $elements
            ];
            return $resultArray;
        }
    }

    /**
     * Finding reference to files from extensions in content, but only to notify about their existence. No substitution
     *
     * @param string $content The input content to analyze
     * @return array Result array on positive matches, see description above. Otherwise FALSE
     */
    public function findRef_extension_fileref($content)
    {
        // Files starting with EXT:
        $parts = preg_split('/([^[:alnum:]"\']+)(EXT:[[:alnum:]_]+\\/[^[:space:]"\',]*)/', ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $idx => $value) {
            if ($idx % 3 == 2) {
                $this->makeTokenID($idx);
                $elements[$idx] = [];
                $elements[$idx]['matchString'] = $value;
            }
        }
        // Return output:
        if (!empty($elements)) {
            $resultArray = [
                'content' => substr(implode('', $parts), 1, -1),
                'elements' => $elements
            ];
            return $resultArray;
        }
    }

    /*************************
     *
     * Helper functions
     *
     *************************/

    /**
     * Analyze content as a TypoLink value and return an array with properties.
     * TypoLinks format is: <link [typolink] [browser target] [css class] [title attribute] [additionalParams]>.
     * See TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typolink()
     * The syntax of the [typolink] part is: [typolink] = [page id or alias][,[type value]][#[anchor, if integer = tt_content uid]]
     * The extraction is based on how \TYPO3\CMS\Frontend\ContentObject::typolink() behaves.
     *
     * @param string $typolinkValue TypoLink value.
     * @return array Array with the properties of the input link specified. The key "LINK_TYPE" will reveal the type. If that is blank it could not be determined.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typolink(), setTypoLinkPartsElement()
     */
    public function getTypoLinkParts($typolinkValue)
    {
        $finalTagParts = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($typolinkValue);

        $link_param = $finalTagParts['url'];
        // we define various keys below, "url" might be misleading
        unset($finalTagParts['url']);

        if (stripos(rawurldecode(trim($link_param)), 'phar://') === 0) {
            throw new \RuntimeException(
                'phar scheme not allowed as soft reference target',
                1530030672
            );
        }

        // Parse URL:
        $pU = @parse_url($link_param);

        // If it's a mail address:
        if (strstr($link_param, '@') && !$pU['scheme']) {
            $link_param = preg_replace('/^mailto:/i', '', $link_param);
            $finalTagParts['LINK_TYPE'] = 'mailto';
            $finalTagParts['url'] = trim($link_param);
            return $finalTagParts;
        }

        if ($pU['scheme'] === 't3' && $pU['host'] === LinkService::TYPE_RECORD) {
            $finalTagParts['LINK_TYPE'] = LinkService::TYPE_RECORD;
            $finalTagParts['url'] = $link_param;
        }

        list($linkHandlerKeyword, $linkHandlerValue) = explode(':', trim($link_param), 2);

        // Dispatch available signal slots.
        $linkHandlerFound = false;
        list($linkHandlerFound, $finalTagParts) = $this->emitGetTypoLinkParts($linkHandlerFound, $finalTagParts, $linkHandlerKeyword, $linkHandlerValue);
        if ($linkHandlerFound) {
            return $finalTagParts;
        }

        // Check for FAL link-handler keyword
        if ($linkHandlerKeyword === 'file') {
            $finalTagParts['LINK_TYPE'] = 'file';
            $finalTagParts['identifier'] = trim($link_param);
            return $finalTagParts;
        }

        $isLocalFile = 0;
        $fileChar = (int)strpos($link_param, '/');
        $urlChar = (int)strpos($link_param, '.');

        // Detects if a file is found in site-root and if so it will be treated like a normal file.
        list($rootFileDat) = explode('?', rawurldecode($link_param));
        $containsSlash = strstr($rootFileDat, '/');
        $rFD_fI = pathinfo($rootFileDat);
        $fileExtension = strtolower($rFD_fI['extension']);
        if (!$containsSlash && trim($rootFileDat) && (@is_file(Environment::getPublicPath() . '/' . $rootFileDat) || $fileExtension === 'php' || $fileExtension === 'html' || $fileExtension === 'htm')) {
            $isLocalFile = 1;
        } elseif ($containsSlash) {
            // Adding this so realurl directories are linked right (non-existing).
            $isLocalFile = 2;
        }
        if ($pU['scheme'] || ($isLocalFile != 1 && $urlChar && (!$containsSlash || $urlChar < $fileChar))) { // url (external): If doubleSlash or if a '.' comes before a '/'.
            $finalTagParts['LINK_TYPE'] = 'url';
            $finalTagParts['url'] = $link_param;
        } elseif ($containsSlash || $isLocalFile) { // file (internal)
            $splitLinkParam = explode('?', $link_param);
            if (file_exists(rawurldecode($splitLinkParam[0])) || $isLocalFile) {
                $finalTagParts['LINK_TYPE'] = 'file';
                $finalTagParts['filepath'] = rawurldecode($splitLinkParam[0]);
                $finalTagParts['query'] = $splitLinkParam[1];
            }
        } else {
            // integer or alias (alias is without slashes or periods or commas, that is
            // 'nospace,alphanum_x,lower,unique' according to definition in $GLOBALS['TCA']!)
            $finalTagParts['LINK_TYPE'] = 'page';

            $link_params_parts = explode('#', $link_param);
            // Link-data del
            $link_param = trim($link_params_parts[0]);

            if ((string)$link_params_parts[1] !== '') {
                $finalTagParts['anchor'] = trim($link_params_parts[1]);
            }

            // Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/? pair
            $pairParts = GeneralUtility::trimExplode(',', $link_param);
            if (count($pairParts) > 1) {
                $link_param = $pairParts[0];
                $finalTagParts['type'] = $pairParts[1]; // Overruling 'type'
            }

            // Checking if the id-parameter is an alias.
            if ((string)$link_param !== '') {
                if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($link_param)) {
                    $finalTagParts['alias'] = $link_param;
                    $link_param = $this->getPageIdFromAlias($link_param);
                }

                $finalTagParts['page_id'] = (int)$link_param;
            }
        }

        return $finalTagParts;
    }

    /**
     * Recompile a TypoLink value from the array of properties made with getTypoLinkParts() into an elements array
     *
     * @param array $tLP TypoLink properties
     * @param array $elements Array of elements to be modified with substitution / information entries.
     * @param string $content The content to process.
     * @param int $idx Index value of the found element - user to make unique but stable tokenID
     * @return string The input content, possibly containing tokens now according to the added substitution entries in $elements
     * @see getTypoLinkParts()
     */
    public function setTypoLinkPartsElement($tLP, &$elements, $content, $idx)
    {
        // Initialize, set basic values. In any case a link will be shown
        $tokenID = $this->makeTokenID('setTypoLinkPartsElement:' . $idx);
        $elements[$tokenID . ':' . $idx] = [];
        $elements[$tokenID . ':' . $idx]['matchString'] = $content;
        // Based on link type, maybe do more:
        switch ((string)$tLP['LINK_TYPE']) {
            case 'mailto':

            case 'url':
                // Mail addresses and URLs can be substituted manually:
                $elements[$tokenID . ':' . $idx]['subst'] = [
                    'type' => 'string',
                    'tokenID' => $tokenID,
                    'tokenValue' => $tLP['url']
                ];
                // Output content will be the token instead:
                $content = '{softref:' . $tokenID . '}';
                break;
            case 'file':
                // Process files referenced by their FAL uid
                if ($tLP['identifier']) {
                    list($linkHandlerKeyword, $linkHandlerValue) = explode(':', trim($tLP['identifier']), 2);
                    if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($linkHandlerValue)) {
                        // Token and substitute value
                        $elements[$tokenID . ':' . $idx]['subst'] = [
                            'type' => 'db',
                            'recordRef' => 'sys_file:' . $linkHandlerValue,
                            'tokenID' => $tokenID,
                            'tokenValue' => $tLP['identifier'],
                        ];
                        // Output content will be the token instead:
                        $content = '{softref:' . $tokenID . '}';
                    } else {
                        // This is a link to a folder...
                        return $content;
                    }
                } else {
                    return $content;
                }
                break;
            case 'page':
                // Rebuild page reference typolink part:
                $content = '';
                // Set page id:
                if ($tLP['page_id']) {
                    $content .= '{softref:' . $tokenID . '}';
                    $elements[$tokenID . ':' . $idx]['subst'] = [
                        'type' => 'db',
                        'recordRef' => 'pages:' . $tLP['page_id'],
                        'tokenID' => $tokenID,
                        'tokenValue' => $tLP['alias'] ? $tLP['alias'] : $tLP['page_id']
                    ];
                }
                // Add type if applicable
                if ((string)$tLP['type'] !== '') {
                    $content .= ',' . $tLP['type'];
                }
                // Add anchor if applicable
                if ((string)$tLP['anchor'] !== '') {
                    // Anchor is assumed to point to a content elements:
                    if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($tLP['anchor'])) {
                        // Initialize a new entry because we have a new relation:
                        $newTokenID = $this->makeTokenID('setTypoLinkPartsElement:anchor:' . $idx);
                        $elements[$newTokenID . ':' . $idx] = [];
                        $elements[$newTokenID . ':' . $idx]['matchString'] = 'Anchor Content Element: ' . $tLP['anchor'];
                        $content .= '#{softref:' . $newTokenID . '}';
                        $elements[$newTokenID . ':' . $idx]['subst'] = [
                            'type' => 'db',
                            'recordRef' => 'tt_content:' . $tLP['anchor'],
                            'tokenID' => $newTokenID,
                            'tokenValue' => $tLP['anchor']
                        ];
                    } else {
                        // Anchor is a hardcoded string
                        $content .= '#' . $tLP['type'];
                    }
                }
                break;
            case LinkService::TYPE_RECORD:
                $elements[$tokenID . ':' . $idx]['subst'] = [
                    'type' => 'db',
                    'recordRef' => $tLP['table'] . ':' . $tLP['uid'],
                    'tokenID' => $tokenID,
                    'tokenValue' => $content,
                ];

                $content = '{softref:' . $tokenID . '}';
                break;
            default:
                $linkHandlerFound = false;
                list($linkHandlerFound, $tLP, $content, $newElements) = $this->emitSetTypoLinkPartsElement($linkHandlerFound, $tLP, $content, $elements, $idx, $tokenID);
                // We need to merge the array, otherwise we would loose the reference.
                \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($elements, $newElements);

                if (!$linkHandlerFound) {
                    $elements[$tokenID . ':' . $idx]['error'] = 'Couldn\'t decide typolink mode.';
                    return $content;
                }
        }
        // Finally, for all entries that was rebuild with tokens, add target, class, title and additionalParams in the end:
        $tLP['url'] = $content;
        $content = GeneralUtility::makeInstance(TypoLinkCodecService::class)->encode($tLP);

        // Return rebuilt typolink value:
        return $content;
    }

    /**
     * Look up and return page uid for alias
     *
     * @param string $link_param Page alias string value
     * @return int Page uid corresponding to alias value.
     */
    public function getPageIdFromAlias($link_param)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        $pageUid = $queryBuilder->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('alias', $queryBuilder->createNamedParameter($link_param, \PDO::PARAM_STR))
            )
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn(0);

        return (int)$pageUid;
    }

    /**
     * Make Token ID for input index.
     *
     * @param string $index Suffix value.
     * @return string Token ID
     */
    public function makeTokenID($index = '')
    {
        return md5($this->tokenID_basePrefix . ':' . $index);
    }

    /**
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    }

    /**
     * @param bool $linkHandlerFound
     * @param array $finalTagParts
     * @param string $linkHandlerKeyword
     * @param string $linkHandlerValue
     * @return array
     */
    protected function emitGetTypoLinkParts($linkHandlerFound, $finalTagParts, $linkHandlerKeyword, $linkHandlerValue)
    {
        return $this->getSignalSlotDispatcher()->dispatch(static::class, 'getTypoLinkParts', [$linkHandlerFound, $finalTagParts, $linkHandlerKeyword, $linkHandlerValue]);
    }

    /**
     * @param bool $linkHandlerFound
     * @param array $tLP
     * @param string $content
     * @param array $elements
     * @param int $idx
     * @param string $tokenID
     * @return array
     */
    protected function emitSetTypoLinkPartsElement($linkHandlerFound, $tLP, $content, $elements, $idx, $tokenID)
    {
        return $this->getSignalSlotDispatcher()->dispatch(static::class, 'setTypoLinkPartsElement', [$linkHandlerFound, $tLP, $content, $elements, $idx, $tokenID, $this]);
    }
}
