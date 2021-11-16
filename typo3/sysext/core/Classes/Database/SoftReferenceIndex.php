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

namespace TYPO3\CMS\Core\Database;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\Event\AppendLinkHandlerElementsEvent;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
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
class SoftReferenceIndex implements SingletonInterface
{
    /**
     * @var string
     */
    public $tokenID_basePrefix = '';

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var int
     */
    private $referenceUid = 0;

    /**
     * @var string
     */
    private $referenceTable = '';

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

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
     * @return array|bool|null Result array on positive matches, see description above. Otherwise FALSE or null
     */
    public function findRef($table, $field, $uid, $content, $spKey, $spParams, $structurePath = '')
    {
        $this->referenceUid = $uid;
        $this->referenceTable = $table;
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
        $this->referenceUid = 0;
        $this->referenceTable = '';
        return $retVal;
    }

    /**
     * TypoLink value processing.
     * Will process input value as a TypoLink value.
     *
     * @param string $content The input content to analyze
     * @param array $spParams Parameters set for the softref parser key in TCA/columns. value "linkList" will split the string by comma before processing.
     * @return array|null Result array on positive matches, see description above. Otherwise null
     * @see \TYPO3\CMS\Frontend\ContentObject::typolink()
     * @see getTypoLinkParts()
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

        return null;
    }

    /**
     * TypoLink tag processing.
     * Will search for <link ...> and <a> tags in the content string and process any found.
     *
     * @param string $content The input content to analyze
     * @return array|null Result array on positive matches, see description above. Otherwise null
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typolink()
     * @see getTypoLinkParts()
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
                        } elseif ($linkDetails['type'] === LinkService::TYPE_TELEPHONE) {
                            $token = $this->makeTokenID($key);
                            $elements[$key]['matchString'] = $linkTags[$key];
                            $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $linkTags[$key]);
                            $elements[$key]['subst'] = [
                                'type' => 'string',
                                'tokenID' => $token,
                                'tokenValue' => $linkDetails['telephone']
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

        return null;
    }

    /**
     * Finding email addresses in content and making them substitutable.
     *
     * @param string $content The input content to analyze
     * @param array $spParams Parameters set for the softref parser key in TCA/columns
     * @return array|null Result array on positive matches, see description above. Otherwise null
     */
    public function findRef_email($content, $spParams)
    {
        $elements = [];
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

        return null;
    }

    /**
     * Finding URLs in content
     *
     * @param string $content The input content to analyze
     * @param array $spParams Parameters set for the softref parser key in TCA/columns
     * @return array|null Result array on positive matches, see description above. Otherwise null
     */
    public function findRef_url($content, $spParams)
    {
        $elements = [];
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

        return null;
    }

    /**
     * Finding reference to files from extensions in content, but only to notify about their existence. No substitution
     *
     * @param string $content The input content to analyze
     * @return array|null Result array on positive matches, see description above. Otherwise null
     */
    public function findRef_extension_fileref($content)
    {
        $elements = [];
        // Files starting with EXT:
        $parts = preg_split('/([^[:alnum:]"\']+)(EXT:[[:alnum:]_]+\\/[^[:space:]"\',]*)/', ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        foreach ($parts as $idx => $value) {
            if ($idx % 3 == 2) {
                $this->makeTokenID((string)$idx);
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

        return null;
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
     * The syntax of the [typolink] part is: [typolink] = [page id][,[type value]][#[anchor, if integer = tt_content uid]]
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
                    $referencePageId = $this->referenceTable === 'pages'
                        ? $this->referenceUid
                        : (int)(BackendUtility::getRecord($this->referenceTable, $this->referenceUid)['pid'] ?? 0);
                    if ($referencePageId) {
                        $pageTsConfig = BackendUtility::getPagesTSconfig($referencePageId);
                        $table = $pageTsConfig['TCEMAIN.']['linkHandler.'][$linkData['identifier'] . '.']['configuration.']['table'] ?? $linkData['identifier'];
                    } else {
                        // Backwards compatibility for the old behaviour, where the identifier was saved as the table.
                        $table = $linkData['identifier'];
                    }
                    $finalTagParts['table'] = $table;
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
            case LinkService::TYPE_TELEPHONE:
                // phone number can be substituted manually:
                $elements[$tokenID . ':' . $idx]['subst'] = [
                    'type' => 'string',
                    'tokenID' => $tokenID,
                    'tokenValue' => $tLP['telephone']
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
                    [$linkHandlerKeyword, $linkHandlerValue] = explode(':', trim($tLP['identifier']), 2);
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
                        'tokenValue' => $tLP['pageuid']
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
                $event = new AppendLinkHandlerElementsEvent($tLP, $content, $elements, $idx, $tokenID);
                $this->eventDispatcher->dispatch($event);

                $elements = $event->getElements();
                $tLP = $event->getLinkParts();
                $content = $event->getContent();

                if (!$event->isResolved()) {
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
     * Make Token ID for input index.
     *
     * @param string $index Suffix value.
     * @return string Token ID
     */
    public function makeTokenID($index = '')
    {
        return md5($this->tokenID_basePrefix . ':' . $index);
    }
}
