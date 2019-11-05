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
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
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
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
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
                            $elements[$key]['matchString'] = $linkTags[$key];
                            $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $linkTags[$key]);
                            $elements[$key]['subst'] = [
                                'type' => 'db',
                                'recordRef' => 'sys_file:' . $fileIdMatch[1],
                                'tokenID' => $token,
                                'tokenValue' => 'file:' . ($linkDetails['file'] instanceof File ? $linkDetails['file']->getUid() : $fileIdMatch[1])
                            ];
                        } elseif ($linkDetails['type'] === LinkService::TYPE_PAGE && preg_match('/page\?uid=(\d+)#?(\d+)?/', $matches[1], $pageAndAnchorMatches)) {
                            $token = $this->makeTokenID($key);
                            $content = '{softref:' . $token . '}';
                            $elements[$key]['matchString'] = $linkTags[$key];
                            $elements[$key]['subst'] = [
                                'type' => 'db',
                                'recordRef' => 'pages:' . $linkDetails['pageuid'],
                                'tokenID' => $token,
                                'tokenValue' => $linkDetails['pageuid']
                            ];
                            if (isset($pageAndAnchorMatches[2]) && $pageAndAnchorMatches[2] !== '') {
                                // Anchor is assumed to point to a content elements:
                                if (MathUtility::canBeInterpretedAsInteger($pageAndAnchorMatches[2])) {
                                    // Initialize a new entry because we have a new relation:
                                    $newTokenID = $this->makeTokenID('setTypoLinkPartsElement:anchor:' . $key);
                                    $elements[$newTokenID . ':' . $key] = [];
                                    $elements[$newTokenID . ':' . $key]['matchString'] = 'Anchor Content Element: ' . $pageAndAnchorMatches[2];
                                    $content .= '#{softref:' . $newTokenID . '}';
                                    $elements[$newTokenID . ':' . $key]['subst'] = [
                                        'type' => 'db',
                                        'recordRef' => 'tt_content:' . $pageAndAnchorMatches[2],
                                        'tokenID' => $newTokenID,
                                        'tokenValue' => $pageAndAnchorMatches[2]
                                    ];
                                } else {
                                    // Anchor is a hardcoded string
                                    $content .= '#' . $pageAndAnchorMatches[2];
                                }
                            }
                            $linkTags[$key] = str_replace($matches[1], $content, $linkTags[$key]);
                        } elseif ($linkDetails['type'] === LinkService::TYPE_URL) {
                            $token = $this->makeTokenID($key);
                            $elements[$key]['matchString'] = $linkTags[$key];
                            $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $linkTags[$key]);
                            $elements[$key]['subst'] = [
                                'type' => 'external',
                                'tokenID' => $token,
                                'tokenValue' => $linkDetails['url']
                            ];
                        } elseif ($linkDetails['type'] === LinkService::TYPE_EMAIL) {
                            $token = $this->makeTokenID($key);
                            $elements[$key]['matchString'] = $linkTags[$key];
                            $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $linkTags[$key]);
                            $elements[$key]['subst'] = [
                                'type' => 'string',
                                'tokenID' => $token,
                                'tokenValue' => $linkDetails['email']
                            ];
                        }
                    } catch (\Exception $e) {
                        // skip invalid links
                    }
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
     * @return array Array with the properties of the input link specified. The key "type" will reveal the type. If that is blank it could not be determined.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typolink()
     * @see setTypoLinkPartsElement()
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

        $linkService = GeneralUtility::makeInstance(LinkService::class);
        try {
            $linkData = $linkService->resolve($link_param);
            switch ($linkData['type']) {
                case LinkService::TYPE_RECORD:
                    $finalTagParts['table'] = $linkData['identifier'];
                    $finalTagParts['uid'] = $linkData['uid'];
                    break;
                case LinkService::TYPE_PAGE:
                    $linkData['pageuid'] = (int)$linkData['pageuid'];
                    if (isset($linkData['pagetype'])) {
                        $linkData['pagetype'] = (int)$linkData['pagetype'];
                    }
                    if (isset($linkData['fragment'])) {
                        $finalTagParts['anchor'] = $linkData['fragment'];
                    }
                    break;
                case LinkService::TYPE_FILE:
                case LinkService::TYPE_UNKNOWN:
                    if (isset($linkData['file'])) {
                        $finalTagParts['type'] = LinkService::TYPE_FILE;
                        $linkData['file'] = $linkData['file'] instanceof FileInterface ? $linkData['file']->getUid() : $linkData['file'];
                    } else {
                        $pU = parse_url($link_param);
                        parse_str($pU['query'] ?? '', $query);
                        if (isset($query['uid'])) {
                            $finalTagParts['type'] = LinkService::TYPE_FILE;
                            $finalTagParts['file'] = (int)$query['uid'];
                        }
                    }
                    break;
            }
            return array_merge($finalTagParts, $linkData);
        } catch (UnknownLinkHandlerException $e) {
            // Cannot handle anything
            return $finalTagParts;
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
        switch ((string)$tLP['type']) {
            case LinkService::TYPE_EMAIL:
                // Mail addresses can be substituted manually:
                $elements[$tokenID . ':' . $idx]['subst'] = [
                    'type' => 'string',
                    'tokenID' => $tokenID,
                    'tokenValue' => $tLP['email']
                ];
                // Output content will be the token instead:
                $content = '{softref:' . $tokenID . '}';
                break;
            case LinkService::TYPE_URL:
                // URLs can be substituted manually
                $elements[$tokenID . ':' . $idx]['subst'] = [
                    'type' => 'external',
                    'tokenID' => $tokenID,
                    'tokenValue' => $tLP['url']
                ];
                // Output content will be the token instead:
                $content = '{softref:' . $tokenID . '}';
                break;
            case LinkService::TYPE_FOLDER:
                // This is a link to a folder...
                unset($elements[$tokenID . ':' . $idx]);
                return $content;
            case LinkService::TYPE_FILE:
                // Process files referenced by their FAL uid
                if (isset($tLP['file'])) {
                    $fileId = $tLP['file'] instanceof FileInterface ? $tLP['file']->getUid() : $tLP['file'];
                    // Token and substitute value
                    $elements[$tokenID . ':' . $idx]['subst'] = [
                        'type' => 'db',
                        'recordRef' => 'sys_file:' . $fileId,
                        'tokenID' => $tokenID,
                        'tokenValue' => 'file:' . $fileId,
                    ];
                    // Output content will be the token instead:
                    $content = '{softref:' . $tokenID . '}';
                } elseif ($tLP['identifier']) {
                    list($linkHandlerKeyword, $linkHandlerValue) = explode(':', trim($tLP['identifier']), 2);
                    if (MathUtility::canBeInterpretedAsInteger($linkHandlerValue)) {
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
            case LinkService::TYPE_PAGE:
                // Rebuild page reference typolink part:
                $content = '';
                // Set page id:
                if ($tLP['pageuid']) {
                    $content .= '{softref:' . $tokenID . '}';
                    $elements[$tokenID . ':' . $idx]['subst'] = [
                        'type' => 'db',
                        'recordRef' => 'pages:' . $tLP['pageuid'],
                        'tokenID' => $tokenID,
                        'tokenValue' => !empty($tLP['pagealias']) ? $tLP['pagealias'] : $tLP['pageuid']
                    ];
                }
                // Add type if applicable
                if ((string)($tLP['pagetype'] ?? '') !== '') {
                    $content .= ',' . $tLP['pagetype'];
                }
                // Add anchor if applicable
                if ((string)($tLP['anchor'] ?? '') !== '') {
                    // Anchor is assumed to point to a content elements:
                    if (MathUtility::canBeInterpretedAsInteger($tLP['anchor'])) {
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
                        $content .= '#' . $tLP['anchor'];
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
                ArrayUtility::mergeRecursiveWithOverrule($elements, $newElements);

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
