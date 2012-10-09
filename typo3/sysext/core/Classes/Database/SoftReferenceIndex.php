<?php
namespace TYPO3\CMS\Core\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2003-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Soft Reference processing class
 * "Soft References" are references to database elements, files, email addresses, URls etc.
 * which are found in-text in content. The <link [page_id]> tag from typical bodytext fields
 * are an example of this.
 * This class contains generic parsers for the most well-known types
 * which are default for most TYPO3 installations. Soft References can also be userdefined.
 * The Soft Reference parsers are used by the system to find these references and process them accordingly in import/export actions and copy operations.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Example of usage
 * Soft References:
 * if ($conf['softref'] && strlen($value))	{	// Check if a TCA configured field has softreferences defined (see TYPO3 Core API document)
 * $softRefs = \TYPO3\CMS\Backend\Utility\BackendUtility::explodeSoftRefParserList($conf['softref']);		// Explode the list of softreferences/parameters
 * foreach($softRefs as $spKey => $spParams)	{	// Traverse soft references
 * $softRefObj = &\TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj($spKey);	// create / get object
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
 * - 'images' : HTML <img> tags for RTE images / images from fileadmin/
 * - 'typolink' : references to page id or file, possibly with anchor/target, possibly commaseparated list.
 * - 'typolink_tag' : As typolink, but searching for <link> tag to encapsulate it.
 * - 'TSconfig' processing (filerefs? Domains? what do we know...)
 * - 'TStemplate' : freetext references to "fileadmin/" files.
 * - 'email' : Email highlight
 * - 'url' : URL highlights (with a scheme)
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class SoftReferenceIndex {

	// External configuration
	/**
	 * @todo Define visibility
	 */
	public $fileAdminDir = '';

	// Internal:
	/**
	 * @todo Define visibility
	 */
	public $tokenID_basePrefix = '';

	/**
	 * Class construct to set global variable
	 *
	 */
	public function __construct() {
		$this->fileAdminDir = !empty($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']) ? rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') : 'fileadmin';
	}

	/**
	 * Main function through which all processing happens
	 *
	 * @param string $table Database table name
	 * @param string $field Field name for which processing occurs
	 * @param integer $uid UID of the record
	 * @param string $content The content/value of the field
	 * @param string $spKey The softlink parser key. This is only interesting if more than one parser is grouped in the same class. That is the case with this parser.
	 * @param array $spParams Parameters of the softlink parser. Basically this is the content inside optional []-brackets after the softref keys. Parameters are exploded by ";
	 * @param string $structurePath If running from inside a FlexForm structure, this is the path of the tag.
	 * @return array Result array on positive matches, see description above. Otherwise FALSE
	 * @todo Define visibility
	 */
	public function findRef($table, $field, $uid, $content, $spKey, $spParams, $structurePath = '') {
		$retVal = FALSE;
		$this->tokenID_basePrefix = $table . ':' . $uid . ':' . $field . ':' . $structurePath . ':' . $spKey;
		switch ($spKey) {
		case 'notify':
			// Simple notification
			$resultArray = array(
				'elements' => array(
					array(
						'matchString' => $content
					)
				)
			);
			$retVal = $resultArray;
			break;
		case 'substitute':
			$tokenID = $this->makeTokenID();
			$resultArray = array(
				'content' => '{softref:' . $tokenID . '}',
				'elements' => array(
					array(
						'matchString' => $content,
						'subst' => array(
							'type' => 'string',
							'tokenID' => $tokenID,
							'tokenValue' => $content
						)
					)
				)
			);
			$retVal = $resultArray;
			break;
		case 'images':
			$retVal = $this->findRef_images($content, $spParams);
			break;
		case 'typolink':
			$retVal = $this->findRef_typolink($content, $spParams);
			break;
		case 'typolink_tag':
			$retVal = $this->findRef_typolink_tag($content, $spParams);
			break;
		case 'ext_fileref':
			$retVal = $this->findRef_extension_fileref($content, $spParams);
			break;
		case 'TStemplate':
			$retVal = $this->findRef_TStemplate($content, $spParams);
			break;
		case 'TSconfig':
			$retVal = $this->findRef_TSconfig($content, $spParams);
			break;
		case 'email':
			$retVal = $this->findRef_email($content, $spParams);
			break;
		case 'url':
			$retVal = $this->findRef_url($content, $spParams);
			break;
		default:
			$retVal = FALSE;
			break;
		}
		return $retVal;
	}

	/**
	 * Finding image tags in the content.
	 * All images that are not from external URLs will be returned with an info text
	 * Will only return files in fileadmin/ and files in uploads/ folders which are prefixed with "RTEmagic[C|P]_" for substitution
	 * Any "clear.gif" images are ignored.
	 *
	 * @param string $content The input content to analyse
	 * @param array $spParams Parameters set for the softref parser key in TCA/columns
	 * @return array Result array on positive matches, see description above. Otherwise FALSE
	 * @todo Define visibility
	 */
	public function findRef_images($content, $spParams) {
		// Start HTML parser and split content by image tag:
		$htmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
		$splitContent = $htmlParser->splitTags('img', $content);
		$elements = array();
		// Traverse splitted parts:
		foreach ($splitContent as $k => $v) {
			if ($k % 2) {
				// Get file reference:
				$attribs = $htmlParser->get_tag_attributes($v);
				$srcRef = \TYPO3\CMS\Core\Utility\GeneralUtility::htmlspecialchars_decode($attribs[0]['src']);
				$pI = pathinfo($srcRef);
				// If it looks like a local image, continue. Otherwise ignore it.
				$absPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(PATH_site . $srcRef);
				if (!$pI['scheme'] && !$pI['query'] && $absPath && $srcRef !== 'clear.gif') {
					// Initialize the element entry with info text here:
					$tokenID = $this->makeTokenID($k);
					$elements[$k] = array();
					$elements[$k]['matchString'] = $v;
					// If the image seems to be from fileadmin/ folder or an RTE image, then proceed to set up substitution token:
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($srcRef, $this->fileAdminDir . '/') || \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($srcRef, 'uploads/') && preg_match('/^RTEmagicC_/', basename($srcRef))) {
						// Token and substitute value:
						// Make sure the value we work on is found and will get substituted in the content (Very important that the src-value is not DeHSC'ed)
						if (strstr($splitContent[$k], $attribs[0]['src'])) {
							// Substitute value with token (this is not be an exact method if the value is in there twice, but we assume it will not)
							$splitContent[$k] = str_replace($attribs[0]['src'], '{softref:' . $tokenID . '}', $splitContent[$k]);
							$elements[$k]['subst'] = array(
								'type' => 'file',
								'relFileName' => $srcRef,
								'tokenID' => $tokenID,
								'tokenValue' => $attribs[0]['src']
							);
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
		if (count($elements)) {
			$resultArray = array(
				'content' => implode('', $splitContent),
				'elements' => $elements
			);
			return $resultArray;
		}
	}

	/**
	 * TypoLink value processing.
	 * Will process input value as a TypoLink value.
	 *
	 * @param string $content The input content to analyse
	 * @param array $spParams Parameters set for the softref parser key in TCA/columns. value "linkList" will split the string by comma before processing.
	 * @return array Result array on positive matches, see description above. Otherwise FALSE
	 * @see tslib_content::typolink(), getTypoLinkParts()
	 * @todo Define visibility
	 */
	public function findRef_typolink($content, $spParams) {
		// First, split the input string by a comma if the "linkList" parameter is set.
		// An example: the link field for images in content elements of type "textpic" or "image". This field CAN be configured to define a link per image, separated by comma.
		if (is_array($spParams) && in_array('linkList', $spParams)) {
			// Preserving whitespace on purpose.
			$linkElement = explode(',', $content);
		} else {
			// If only one element, just set in this array to make it easy below.
			$linkElement = array($content);
		}
		// Traverse the links now:
		$elements = array();
		foreach ($linkElement as $k => $typolinkValue) {
			$tLP = $this->getTypoLinkParts($typolinkValue);
			$linkElement[$k] = $this->setTypoLinkPartsElement($tLP, $elements, $typolinkValue, $k);
		}
		// Return output:
		if (count($elements)) {
			$resultArray = array(
				'content' => implode(',', $linkElement),
				'elements' => $elements
			);
			return $resultArray;
		}
	}

	/**
	 * TypoLink tag processing.
	 * Will search for <link ...> tags in the content string and process any found.
	 *
	 * @param string $content The input content to analyse
	 * @param array $spParams Parameters set for the softref parser key in TCA/columns
	 * @return array Result array on positive matches, see description above. Otherwise FALSE
	 * @see tslib_content::typolink(), getTypoLinkParts()
	 * @todo Define visibility
	 */
	public function findRef_typolink_tag($content, $spParams) {
		// Parse string for special TYPO3 <link> tag:
		$htmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
		$linkTags = $htmlParser->splitTags('link', $content);
		// Traverse result:
		$elements = array();
		foreach ($linkTags as $k => $foundValue) {
			if ($k % 2) {
				$typolinkValue = preg_replace('/<LINK[[:space:]]+/i', '', substr($foundValue, 0, -1));
				$tLP = $this->getTypoLinkParts($typolinkValue);
				$linkTags[$k] = '<LINK ' . $this->setTypoLinkPartsElement($tLP, $elements, $typolinkValue, $k) . '>';
			}
		}
		// Return output:
		if (count($elements)) {
			$resultArray = array(
				'content' => implode('', $linkTags),
				'elements' => $elements
			);
			return $resultArray;
		}
	}

	/**
	 * Processing the content expected from a TypoScript template
	 * This content includes references to files in fileadmin/ folders and file references in HTML tags like <img src="">, <a href=""> and <form action="">
	 *
	 * @param string $content The input content to analyse
	 * @param array $spParams Parameters set for the softref parser key in TCA/columns
	 * @return array Result array on positive matches, see description above. Otherwise FALSE
	 * @todo Define visibility
	 */
	public function findRef_TStemplate($content, $spParams) {
		$elements = array();
		// First, try to find images and links:
		$htmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
		$splitContent = $htmlParser->splitTags('img,a,form', $content);
		// Traverse splitted parts:
		foreach ($splitContent as $k => $v) {
			if ($k % 2) {
				$attribs = $htmlParser->get_tag_attributes($v);
				$attributeName = '';
				switch ($htmlParser->getFirstTagName($v)) {
				case 'img':
					$attributeName = 'src';
					break;
				case 'a':
					$attributeName = 'href';
					break;
				case 'form':
					$attributeName = 'action';
					break;
				}
				// Get file reference:
				if (isset($attribs[0][$attributeName])) {
					$srcRef = \TYPO3\CMS\Core\Utility\GeneralUtility::htmlspecialchars_decode($attribs[0][$attributeName]);
					// Set entry:
					$tokenID = $this->makeTokenID($k);
					$elements[$k] = array();
					$elements[$k]['matchString'] = $v;
					// OK, if it looks like a local file from fileadmin/, include it:
					$pI = pathinfo($srcRef);
					$absPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(PATH_site . $srcRef);
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($srcRef, $this->fileAdminDir . '/') && !$pI['query'] && $absPath) {
						// Token and substitute value:
						// Very important that the src-value is not DeHSC'ed
						if (strstr($splitContent[$k], $attribs[0][$attributeName])) {
							$splitContent[$k] = str_replace($attribs[0][$attributeName], '{softref:' . $tokenID . '}', $splitContent[$k]);
							$elements[$k]['subst'] = array(
								'type' => 'file',
								'relFileName' => $srcRef,
								'tokenID' => $tokenID,
								'tokenValue' => $attribs[0][$attributeName]
							);
							if (!@is_file($absPath)) {
								$elements[$k]['error'] = 'File does not exist!';
							}
						} else {
							$elements[$k]['error'] = 'Could not substitute attribute (' . $attributeName . ') value with token!';
						}
					}
				}
			}
		}
		$content = implode('', $splitContent);
		// Process free fileadmin/ references as well:
		$content = $this->fileadminReferences($content, $elements);
		// Return output:
		if (count($elements)) {
			$resultArray = array(
				'content' => $content,
				'elements' => $elements
			);
			return $resultArray;
		}
	}

	/**
	 * Processes possible references inside of Page and User TSconfig fields.
	 * Currently this only includes file references to fileadmin/ but in fact there are currently no properties that supports such references.
	 *
	 * @param string $content The input content to analyse
	 * @param array $spParams Parameters set for the softref parser key in TCA/columns
	 * @return array Result array on positive matches, see description above. Otherwise FALSE
	 * @todo Define visibility
	 */
	public function findRef_TSconfig($content, $spParams) {
		$elements = array();
		// Process free fileadmin/ references from TSconfig
		$content = $this->fileadminReferences($content, $elements);
		// Return output:
		if (count($elements)) {
			$resultArray = array(
				'content' => $content,
				'elements' => $elements
			);
			return $resultArray;
		}
	}

	/**
	 * Finding email addresses in content and making them substitutable.
	 *
	 * @param string $content The input content to analyse
	 * @param array $spParams Parameters set for the softref parser key in TCA/columns
	 * @return array Result array on positive matches, see description above. Otherwise FALSE
	 * @todo Define visibility
	 */
	public function findRef_email($content, $spParams) {
		$resultArray = array();
		// Email:
		$parts = preg_split('/([^[:alnum:]]+)([A-Za-z0-9\\._-]+[@][A-Za-z0-9\\._-]+[\\.].[A-Za-z0-9]+)/', ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE);
		foreach ($parts as $idx => $value) {
			if ($idx % 3 == 2) {
				$tokenID = $this->makeTokenID($idx);
				$elements[$idx] = array();
				$elements[$idx]['matchString'] = $value;
				if (is_array($spParams) && in_array('subst', $spParams)) {
					$parts[$idx] = '{softref:' . $tokenID . '}';
					$elements[$idx]['subst'] = array(
						'type' => 'string',
						'tokenID' => $tokenID,
						'tokenValue' => $value
					);
				}
			}
		}
		// Return output:
		if (count($elements)) {
			$resultArray = array(
				'content' => substr(implode('', $parts), 1, -1),
				'elements' => $elements
			);
			return $resultArray;
		}
	}

	/**
	 * Finding URLs in content
	 *
	 * @param string $content The input content to analyse
	 * @param array $spParams Parameters set for the softref parser key in TCA/columns
	 * @return array Result array on positive matches, see description above. Otherwise FALSE
	 * @todo Define visibility
	 */
	public function findRef_url($content, $spParams) {
		$resultArray = array();
		// Fileadmin files:
		$parts = preg_split('/([^[:alnum:]"\']+)((http|ftp):\\/\\/[^[:space:]"\'<>]*)([[:space:]])/', ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE);
		foreach ($parts as $idx => $value) {
			if ($idx % 5 == 3) {
				unset($parts[$idx]);
			}
			if ($idx % 5 == 2) {
				$tokenID = $this->makeTokenID($idx);
				$elements[$idx] = array();
				$elements[$idx]['matchString'] = $value;
				if (is_array($spParams) && in_array('subst', $spParams)) {
					$parts[$idx] = '{softref:' . $tokenID . '}';
					$elements[$idx]['subst'] = array(
						'type' => 'string',
						'tokenID' => $tokenID,
						'tokenValue' => $value
					);
				}
			}
		}
		// Return output:
		if (count($elements)) {
			$resultArray = array(
				'content' => substr(implode('', $parts), 1, -1),
				'elements' => $elements
			);
			return $resultArray;
		}
	}

	/**
	 * Finding reference to files from extensions in content, but only to notify about their existence. No substitution
	 *
	 * @param string $content The input content to analyse
	 * @param array $spParams Parameters set for the softref parser key in TCA/columns
	 * @return array Result array on positive matches, see description above. Otherwise FALSE
	 * @todo Define visibility
	 */
	public function findRef_extension_fileref($content, $spParams) {
		$resultArray = array();
		// Fileadmin files:
		$parts = preg_split('/([^[:alnum:]"\']+)(EXT:[[:alnum:]_]+\\/[^[:space:]"\',]*)/', ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE);
		foreach ($parts as $idx => $value) {
			if ($idx % 3 == 2) {
				$tokenID = $this->makeTokenID($idx);
				$elements[$idx] = array();
				$elements[$idx]['matchString'] = $value;
			}
		}
		// Return output:
		if (count($elements)) {
			$resultArray = array(
				'content' => substr(implode('', $parts), 1, -1),
				'elements' => $elements
			);
			return $resultArray;
		}
	}

	/*************************
	 *
	 * Helper functions
	 *
	 *************************/
	/**
	 * Searches the content for a reference to a file in "fileadmin/".
	 * When a match is found it will get substituted with a token.
	 *
	 * @param string $content Input content to analyse
	 * @param array $elements Element array to be modified with new entries. Passed by reference.
	 * @return string Output content, possibly with tokens inserted.
	 * @todo Define visibility
	 */
	public function fileadminReferences($content, &$elements) {
		// Fileadmin files are found
		$parts = preg_split('/([^[:alnum:]]+)(' . preg_quote($this->fileAdminDir, '/') . '\\/[^[:space:]"\'<>]*)/', ' ' . $content . ' ', 10000, PREG_SPLIT_DELIM_CAPTURE);
		// Traverse files:
		foreach ($parts as $idx => $value) {
			if ($idx % 3 == 2) {
				// when file is found, set up an entry for the element:
				$tokenID = $this->makeTokenID('fileadminReferences:' . $idx);
				$elements['fileadminReferences.' . $idx] = array();
				$elements['fileadminReferences.' . $idx]['matchString'] = $value;
				$elements['fileadminReferences.' . $idx]['subst'] = array(
					'type' => 'file',
					'relFileName' => $value,
					'tokenID' => $tokenID,
					'tokenValue' => $value
				);
				$parts[$idx] = '{softref:' . $tokenID . '}';
				// Check if the file actually exists:
				$absPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(PATH_site . $value);
				if (!@is_file($absPath)) {
					$elements['fileadminReferences.' . $idx]['error'] = 'File does not exist!';
				}
			}
		}
		// Implode the content again, removing prefixed and trailing white space:
		return substr(implode('', $parts), 1, -1);
	}

	/**
	 * Analyse content as a TypoLink value and return an array with properties.
	 * TypoLinks format is: <link [typolink] [browser target] [css class]>. See tslib_content::typolink()
	 * The syntax of the [typolink] part is: [typolink] = [page id or alias][,[type value]][#[anchor, if integer = tt_content uid]]
	 * The extraction is based on how tslib_content::typolink() behaves.
	 *
	 * @param string $typolinkValue TypoLink value.
	 * @return array Array with the properties of the input link specified. The key "LINK_TYPE" will reveal the type. If that is blank it could not be determined.
	 * @see tslib_content::typolink(), setTypoLinkPartsElement()
	 * @todo Define visibility
	 */
	public function getTypoLinkParts($typolinkValue) {
		$finalTagParts = array();
		// Split by space into link / target / class
		list($link_param, $browserTarget, $cssClass) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $typolinkValue, 1);
		if (strlen($browserTarget)) {
			$finalTagParts['target'] = $browserTarget;
		}
		if (strlen($cssClass)) {
			$finalTagParts['class'] = $cssClass;
		}
		// Parse URL:
		$pU = @parse_url($link_param);
		// Detecting the kind of reference:
		if (strstr($link_param, '@') && !$pU['scheme']) {
			// If it's a mail address:
			$link_param = preg_replace('/^mailto:/i', '', $link_param);
			$finalTagParts['LINK_TYPE'] = 'mailto';
			$finalTagParts['url'] = trim($link_param);
		} else {
			// Check for FAL link-handler keyword
			list ($linkHandlerKeyword, $linkHandlerValue) = explode(':', trim($link_param), 2);
			if ($linkHandlerKeyword === 'file') {
				$finalTagParts['LINK_TYPE'] = 'file';
				$finalTagParts['identifier'] = trim($link_param);
			} else {
				$isLocalFile = 0;
				$fileChar = intval(strpos($link_param, '/'));
				$urlChar = intval(strpos($link_param, '.'));

					// Detects if a file is found in site-root and if so it will be treated like a normal file.
				list($rootFileDat) = explode('?', rawurldecode($link_param));
				$containsSlash = strstr($rootFileDat, '/');
				$rFD_fI = pathinfo($rootFileDat);
				if (trim($rootFileDat) && !$containsSlash && (@is_file(PATH_site . $rootFileDat) || \TYPO3\CMS\Core\Utility\GeneralUtility::inList('php,html,htm', strtolower($rFD_fI['extension'])))) {
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

					if (strlen($link_params_parts[1])) {
						$finalTagParts['anchor'] = trim($link_params_parts[1]);
					}

					// Splitting the parameter by ',' and if the array counts more than 1 element it's a id/type/? pair
					$pairParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $link_param);
					if (count($pairParts) > 1) {
						$link_param = $pairParts[0];
						$finalTagParts['type'] = $pairParts[1]; // Overruling 'type'
					}

					// Checking if the id-parameter is an alias.
					if (strlen($link_param)) {
						if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($link_param)) {
							$finalTagParts['alias'] = $link_param;
							$link_param = $this->getPageIdFromAlias($link_param);
						}

						$finalTagParts['page_id'] = intval($link_param);
					}
				}
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
	 * @param integer $idx Index value of the found element - user to make unique but stable tokenID
	 * @return string The input content, possibly containing tokens now according to the added substitution entries in $elements
	 * @see getTypoLinkParts()
	 * @todo Define visibility
	 */
	public function setTypoLinkPartsElement($tLP, &$elements, $content, $idx) {
		// Initialize, set basic values. In any case a link will be shown
		$tokenID = $this->makeTokenID('setTypoLinkPartsElement:' . $idx);
		$elements[$tokenID . ':' . $idx] = array();
		$elements[$tokenID . ':' . $idx]['matchString'] = $content;
		// Based on link type, maybe do more:
		switch ((string) $tLP['LINK_TYPE']) {
		case 'mailto':

		case 'url':
			// Mail addresses and URLs can be substituted manually:
			$elements[$tokenID . ':' . $idx]['subst'] = array(
				'type' => 'string',
				'tokenID' => $tokenID,
				'tokenValue' => $tLP['url']
			);
			// Output content will be the token instead:
			$content = '{softref:' . $tokenID . '}';
			break;
		case 'file':
				// Process files referenced by their FAL uid
			if ($tLP['identifier']) {
				list ($linkHandlerKeyword, $linkHandlerValue) = explode(':', trim($tLP['identifier']), 2);
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($linkHandlerValue)) {
						// Token and substitute value
					$elements[$tokenID . ':' . $idx]['subst'] = array(
						'type' => 'db',
						'recordRef' => 'sys_file:' . $linkHandlerValue,
						'tokenID' => $tokenID,
						'tokenValue' => $tLP['identifier'],
					);
						// Output content will be the token instead:
					$content = '{softref:' . $tokenID . '}';
				} else {
						// This is a link to a folder...
					return $content;
				}

			// Process files found in fileadmin directory:
			} elseif (!$tLP['query']) {
				// We will not process files which has a query added to it. That will look like a script we don't want to move.
				// File must be inside fileadmin/
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($tLP['filepath'], $this->fileAdminDir . '/')) {
					// Set up the basic token and token value for the relative file:
					$elements[$tokenID . ':' . $idx]['subst'] = array(
						'type' => 'file',
						'relFileName' => $tLP['filepath'],
						'tokenID' => $tokenID,
						'tokenValue' => $tLP['filepath']
					);
					// Depending on whether the file exists or not we will set the
					$absPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(PATH_site . $tLP['filepath']);
					if (!@is_file($absPath)) {
						$elements[$tokenID . ':' . $idx]['error'] = 'File does not exist!';
					}
					// Output content will be the token instead
					$content = '{softref:' . $tokenID . '}';
				} else {
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
				$elements[$tokenID . ':' . $idx]['subst'] = array(
					'type' => 'db',
					'recordRef' => 'pages:' . $tLP['page_id'],
					'tokenID' => $tokenID,
					'tokenValue' => $tLP['alias'] ? $tLP['alias'] : $tLP['page_id']
				);
			}
			// Add type if applicable
			if (strlen($tLP['type'])) {
				$content .= ',' . $tLP['type'];
			}
			// Add anchor if applicable
			if (strlen($tLP['anchor'])) {
				// Anchor is assumed to point to a content elements:
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($tLP['anchor'])) {
					// Initialize a new entry because we have a new relation:
					$newTokenID = $this->makeTokenID('setTypoLinkPartsElement:anchor:' . $idx);
					$elements[$newTokenID . ':' . $idx] = array();
					$elements[$newTokenID . ':' . $idx]['matchString'] = 'Anchor Content Element: ' . $tLP['anchor'];
					$content .= '#{softref:' . $newTokenID . '}';
					$elements[$newTokenID . ':' . $idx]['subst'] = array(
						'type' => 'db',
						'recordRef' => 'tt_content:' . $tLP['anchor'],
						'tokenID' => $newTokenID,
						'tokenValue' => $tLP['anchor']
					);
				} else {
					// Anchor is a hardcoded string
					$content .= '#' . $tLP['type'];
				}
			}
			break;
		default:
			$elements[$tokenID . ':' . $idx]['error'] = 'Couldn\\t decide typolink mode.';
			return $content;
			break;
		}
		// Finally, for all entries that was rebuild with tokens, add target and class in the end:
		if (strlen($content) && strlen($tLP['target'])) {
			$content .= ' ' . $tLP['target'];
			if (strlen($tLP['class'])) {
				$content .= ' ' . $tLP['class'];
			}
		}
		// Return rebuilt typolink value:
		return $content;
	}

	/**
	 * Look up and return page uid for alias
	 *
	 * @param integer $link_param Page alias string value
	 * @return integer Page uid corresponding to alias value.
	 * @todo Define visibility
	 */
	public function getPageIdFromAlias($link_param) {
		$pRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('pages', 'alias', $link_param);
		return $pRec[0]['uid'];
	}

	/**
	 * Make Token ID for input index.
	 *
	 * @param string $index Suffix value.
	 * @return string Token ID
	 * @todo Define visibility
	 */
	public function makeTokenID($index = '') {
		return md5($this->tokenID_basePrefix . ':' . $index);
	}

}


?>
