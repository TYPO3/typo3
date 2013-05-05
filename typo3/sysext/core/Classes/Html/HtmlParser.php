<?php
namespace TYPO3\CMS\Core\Html;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains class with functions for parsing HTML code.
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Functions for parsing HTML.
 * You are encouraged to use this class in your own applications
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class HtmlParser {

	protected $caseShift_cache = array();

	// Void elements that do not have closing tags, as defined by HTML5, except link element
	const VOID_ELEMENTS = 'area|base|br|col|command|embed|hr|img|input|keygen|meta|param|source|track|wbr';
	/**
	 * Returns the first subpart encapsulated in the marker, $marker
	 * (possibly present in $content as a HTML comment)
	 *
	 * @param string $content Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
	 * @param string $marker Marker string, eg. "###CONTENT_PART###
	 * @return string
	 */
	static public function getSubpart($content, $marker) {
		$start = strpos($content, $marker);
		if ($start === FALSE) {
			return '';
		}
		$start += strlen($marker);
		$stop = strpos($content, $marker, $start);
		// Q: What shall get returned if no stop marker is given
		// Everything till the end or nothing?
		if ($stop === FALSE) {
			return '';
		}
		$content = substr($content, $start, $stop - $start);
		$matches = array();
		if (preg_match('/^([^\\<]*\\-\\-\\>)(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $content, $matches) === 1) {
			return $matches[2];
		}
		// Resetting $matches
		$matches = array();
		if (preg_match('/(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $content, $matches) === 1) {
			return $matches[1];
		}
		// Resetting $matches
		$matches = array();
		if (preg_match('/^([^\\<]*\\-\\-\\>)(.*)$/s', $content, $matches) === 1) {
			return $matches[2];
		}
		return $content;
	}

	/**
	 * Substitutes a subpart in $content with the content of $subpartContent.
	 *
	 * @param string $content Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
	 * @param string $marker Marker string, eg. "###CONTENT_PART###
	 * @param array $subpartContent If $subpartContent happens to be an array, it's [0] and [1] elements are wrapped around the content of the subpart (fetched by getSubpart())
	 * @param boolean $recursive If $recursive is set, the function calls itself with the content set to the remaining part of the content after the second marker. This means that proceding subparts are ALSO substituted!
	 * @param boolean $keepMarker If set, the marker around the subpart is not removed, but kept in the output
	 * @return string Processed input content
	 */
	static public function substituteSubpart($content, $marker, $subpartContent, $recursive = TRUE, $keepMarker = FALSE) {
		$start = strpos($content, $marker);
		if ($start === FALSE) {
			return $content;
		}
		$startAM = $start + strlen($marker);
		$stop = strpos($content, $marker, $startAM);
		if ($stop === FALSE) {
			return $content;
		}
		$stopAM = $stop + strlen($marker);
		$before = substr($content, 0, $start);
		$after = substr($content, $stopAM);
		$between = substr($content, $startAM, $stop - $startAM);
		if ($recursive) {
			$after = self::substituteSubpart($after, $marker, $subpartContent, $recursive, $keepMarker);
		}
		if ($keepMarker) {
			$matches = array();
			if (preg_match('/^([^\\<]*\\-\\-\\>)(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $between, $matches) === 1) {
				$before .= $marker . $matches[1];
				$between = $matches[2];
				$after = $matches[3] . $marker . $after;
			} elseif (preg_match('/^(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $between, $matches) === 1) {
				$before .= $marker;
				$between = $matches[1];
				$after = $matches[2] . $marker . $after;
			} elseif (preg_match('/^([^\\<]*\\-\\-\\>)(.*)$/s', $between, $matches) === 1) {
				$before .= $marker . $matches[1];
				$between = $matches[2];
				$after = $marker . $after;
			} else {
				$before .= $marker;
				$after = $marker . $after;
			}
		} else {
			$matches = array();
			if (preg_match('/^(.*)\\<\\!\\-\\-[^\\>]*$/s', $before, $matches) === 1) {
				$before = $matches[1];
			}
			if (is_array($subpartContent)) {
				$matches = array();
				if (preg_match('/^([^\\<]*\\-\\-\\>)(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $between, $matches) === 1) {
					$between = $matches[2];
				} elseif (preg_match('/^(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $between, $matches) === 1) {
					$between = $matches[1];
				} elseif (preg_match('/^([^\\<]*\\-\\-\\>)(.*)$/s', $between, $matches) === 1) {
					$between = $matches[2];
				}
			}
			$matches = array();
			// resetting $matches
			if (preg_match('/^[^\\<]*\\-\\-\\>(.*)$/s', $after, $matches) === 1) {
				$after = $matches[1];
			}
		}
		if (is_array($subpartContent)) {
			$between = $subpartContent[0] . $between . $subpartContent[1];
		} else {
			$between = $subpartContent;
		}
		return $before . $between . $after;
	}

	/**
	 * Substitues multiple subparts at once
	 *
	 * @param string $content The content stream, typically HTML template content.
	 * @param array $subpartsContent The array of key/value pairs being subpart/content values used in the substitution. For each element in this array the function will substitute a subpart in the content stream with the content.
	 * @return string The processed HTML content string.
	 */
	static public function substituteSubpartArray($content, array $subpartsContent) {
		foreach ($subpartsContent as $subpartMarker => $subpartContent) {
			$content = self::substituteSubpart($content, $subpartMarker, $subpartContent);
		}
		return $content;
	}

	/**
	 * Substitutes a marker string in the input content
	 * (by a simple str_replace())
	 *
	 * @param string $content The content stream, typically HTML template content.
	 * @param string $marker The marker string, typically on the form "###[the marker string]###
	 * @param mixed $markContent The content to insert instead of the marker string found.
	 * @return string The processed HTML content string.
	 * @see substituteSubpart()
	 */
	static public function substituteMarker($content, $marker, $markContent) {
		return str_replace($marker, $markContent, $content);
	}

	/**
	 * Traverses the input $markContentArray array and for each key the marker
	 * by the same name (possibly wrapped and in upper case) will be
	 * substituted with the keys value in the array. This is very useful if you
	 * have a data-record to substitute in some content. In particular when you
	 * use the $wrap and $uppercase values to pre-process the markers. Eg. a
	 * key name like "myfield" could effectively be represented by the marker
	 * "###MYFIELD###" if the wrap value was "###|###" and the $uppercase
	 * boolean TRUE.
	 *
	 * @param string $content The content stream, typically HTML template content.
	 * @param array $markContentArray The array of key/value pairs being marker/content values used in the substitution. For each element in this array the function will substitute a marker in the content stream with the content.
	 * @param string $wrap A wrap value - [part 1] | [part 2] - for the markers before substitution
	 * @param boolean $uppercase If set, all marker string substitution is done with upper-case markers.
	 * @param boolean $deleteUnused If set, all unused marker are deleted.
	 * @return string The processed output stream
	 * @see substituteMarker(), substituteMarkerInObject(), TEMPLATE()
	 */
	static public function substituteMarkerArray($content, $markContentArray, $wrap = '', $uppercase = FALSE, $deleteUnused = FALSE) {
		if (is_array($markContentArray)) {
			$wrapArr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $wrap);
			foreach ($markContentArray as $marker => $markContent) {
				if ($uppercase) {
					// use strtr instead of strtoupper to avoid locale problems with Turkish
					$marker = strtr($marker, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
				}
				if (count($wrapArr) > 0) {
					$marker = $wrapArr[0] . $marker . $wrapArr[1];
				}
				$content = str_replace($marker, $markContent, $content);
			}
			if ($deleteUnused) {
				if (empty($wrap)) {
					$wrapArr = array('###', '###');
				}
				$content = preg_replace('/' . preg_quote($wrapArr[0], '/') . '([A-Z0-9_|\\-]*)' . preg_quote($wrapArr[1], '/') . '/is', '', $content);
			}
		}
		return $content;
	}

	/**
	 * Replaces all markers and subparts in a template with the content provided in the structured array.
	 *
	 * The array is built like the template with its markers and subparts. Keys represent the marker name and the values the
	 * content.
	 * If the value is not an array the key will be treated as a single marker.
	 * If the value is an array the key will be treated as a subpart marker.
	 * Repeated subpart contents are of course elements in the array, so every subpart value must contain an array with its
	 * markers.
	 *
	 * $markersAndSubparts = array (
	 * 	'###SINGLEMARKER1###' => 'value 1',
	 * 	'###SUBPARTMARKER1###' => array(
	 * 		0 => array(
	 * 			'###SINGLEMARKER2###' => 'value 2',
	 * 		),
	 * 		1 => array(
	 * 			'###SINGLEMARKER2###' => 'value 3',
	 * 		)
	 * 	),
	 * 	'###SUBPARTMARKER2###' => array(
	 * 	),
	 * )
	 * Subparts can be nested, so below the 'SINGLEMARKER2' it is possible to have another subpart marker with an array as the
	 * value, which in its turn contains the elements of the sub-subparts.
	 * Empty arrays for Subparts will cause the subtemplate to be cleared.
	 *
	 * @static
	 * @param string $content The content stream, typically HTML template content.
	 * @param array $markersAndSubparts The array of single markers and subpart contents.
	 * @param string $wrap A wrap value - [part1] | [part2] - for the markers before substitution.
	 * @param bool $uppercase If set, all marker string substitution is done with upper-case markers.
	 * @param bool $deleteUnused If set, all unused single markers are deleted.
	 * @return string The processed output stream
	 */
	static public function substituteMarkerAndSubpartArrayRecursive($content, array $markersAndSubparts, $wrap = '', $uppercase = FALSE, $deleteUnused = FALSE) {
		$wraps = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $wrap);
		$singleItems = array();
		$compoundItems = array();
		// Split markers and subparts into separate arrays
		foreach ($markersAndSubparts as $markerName => $markerContent) {
			if (is_array($markerContent)) {
				$compoundItems[] = $markerName;
			} else {
				$singleItems[$markerName] = $markerContent;
			}
		}
		$subTemplates = array();
		$subpartSubstitutes = array();
		// Build a cache for the sub template
		foreach ($compoundItems as $subpartMarker) {
			if ($uppercase) {
				// Use strtr instead of strtoupper to avoid locale problems with Turkish
				$subpartMarker = strtr($subpartMarker, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
			}
			if (count($wraps) > 0) {
				$subpartMarker = $wraps[0] . $subpartMarker . $wraps[1];
			}
			$subTemplates[$subpartMarker] = self::getSubpart($content, $subpartMarker);
		}
		// Replace the subpart contents recursively
		foreach ($compoundItems as $subpartMarker) {
			$completeMarker = $subpartMarker;
			if ($uppercase) {
				// use strtr instead of strtoupper to avoid locale problems with Turkish
				$completeMarker = strtr($completeMarker, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
			}
			if (count($wraps) > 0) {
				$completeMarker = $wraps[0] . $completeMarker . $wraps[1];
			}
			if (count($markersAndSubparts[$subpartMarker]) > 0) {
				foreach ($markersAndSubparts[$subpartMarker] as $partialMarkersAndSubparts) {
					$subpartSubstitutes[$completeMarker] .= self::substituteMarkerAndSubpartArrayRecursive($subTemplates[$completeMarker],
						$partialMarkersAndSubparts, $wrap, $uppercase, $deleteUnused);
				}
			} else {
				$subpartSubstitutes[$completeMarker] = '';
			}
		}
		// Substitute the single markers and subparts
		$result = self::substituteSubpartArray($content, $subpartSubstitutes);
		$result = self::substituteMarkerArray($result, $singleItems, $wrap, $uppercase, $deleteUnused);
		return $result;
	}

	/************************************
	 *
	 * Parsing HTML code
	 *
	 ************************************/
	/**
	 * Returns an array with the $content divided by tag-blocks specified with the list of tags, $tag
	 * Even numbers in the array are outside the blocks, Odd numbers are block-content.
	 * Use ->getAllParts() and ->removeFirstAndLastTag() to process the content if needed.
	 *
	 * @param string $tag List of tags, comma separated.
	 * @param string $content HTML-content
	 * @param boolean $eliminateExtraEndTags If set, excessive end tags are ignored - you should probably set this in most cases.
	 * @return array Even numbers in the array are outside the blocks, Odd numbers are block-content.
	 * @see splitTags(), getAllParts(), removeFirstAndLastTag()
	 * @todo Define visibility
	 */
	public function splitIntoBlock($tag, $content, $eliminateExtraEndTags = FALSE) {
		$tags = array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tag, 1));
		foreach ($tags as &$tag) {
			$tag = preg_quote($tag, '/');
		}
		$regexStr = '/\\<\\/?(' . implode('|', $tags) . ')(\\s*\\>|\\s[^\\>]*\\>)/si';
		$parts = preg_split($regexStr, $content);
		$newParts = array();
		$pointer = strlen($parts[0]);
		$buffer = $parts[0];
		$nested = 0;
		reset($parts);
		next($parts);
		while (list($k, $v) = each($parts)) {
			$isEndTag = substr($content, $pointer, 2) == '</' ? 1 : 0;
			$tagLen = strcspn(substr($content, $pointer), '>') + 1;
			// We meet a start-tag:
			if (!$isEndTag) {
				// Ground level:
				if (!$nested) {
					// Previous buffer stored
					$newParts[] = $buffer;
					$buffer = '';
				}
				// We are inside now!
				$nested++;
				// New buffer set and pointer increased
				$mbuffer = substr($content, $pointer, strlen($v) + $tagLen);
				$pointer += strlen($mbuffer);
				$buffer .= $mbuffer;
			} else {
				// If we meet an endtag:
				// Decrease nested-level
				$nested--;
				$eliminated = 0;
				if ($eliminateExtraEndTags && $nested < 0) {
					$nested = 0;
					$eliminated = 1;
				} else {
					// In any case, add the endtag to current buffer and increase pointer
					$buffer .= substr($content, $pointer, $tagLen);
				}
				$pointer += $tagLen;
				// if we're back on ground level, (and not by eliminating tags...
				if (!$nested && !$eliminated) {
					$newParts[] = $buffer;
					$buffer = '';
				}
				// New buffer set and pointer increased
				$mbuffer = substr($content, $pointer, strlen($v));
				$pointer += strlen($mbuffer);
				$buffer .= $mbuffer;
			}
		}
		$newParts[] = $buffer;
		return $newParts;
	}

	/**
	 * Splitting content into blocks *recursively* and processing tags/content with call back functions.
	 *
	 * @param string $tag Tag list, see splitIntoBlock()
	 * @param string $content Content, see splitIntoBlock()
	 * @param object $procObj Object where call back methods are.
	 * @param string $callBackContent Name of call back method for content; "function callBackContent($str,$level)
	 * @param string $callBackTags Name of call back method for tags; "function callBackTags($tags,$level)
	 * @param integer $level Indent level
	 * @return string Processed content
	 * @see splitIntoBlock()
	 * @todo Define visibility
	 */
	public function splitIntoBlockRecursiveProc($tag, $content, &$procObj, $callBackContent, $callBackTags, $level = 0) {
		$parts = $this->splitIntoBlock($tag, $content, TRUE);
		foreach ($parts as $k => $v) {
			if ($k % 2) {
				$firstTagName = $this->getFirstTagName($v, TRUE);
				$tagsArray = array();
				$tagsArray['tag_start'] = $this->getFirstTag($v);
				$tagsArray['tag_end'] = '</' . $firstTagName . '>';
				$tagsArray['tag_name'] = strtolower($firstTagName);
				$tagsArray['add_level'] = 1;
				$tagsArray['content'] = $this->splitIntoBlockRecursiveProc($tag, $this->removeFirstAndLastTag($v), $procObj, $callBackContent, $callBackTags, $level + $tagsArray['add_level']);
				if ($callBackTags) {
					$tagsArray = $procObj->{$callBackTags}($tagsArray, $level);
				}
				$parts[$k] = $tagsArray['tag_start'] . $tagsArray['content'] . $tagsArray['tag_end'];
			} else {
				if ($callBackContent) {
					$parts[$k] = $procObj->{$callBackContent}($parts[$k], $level);
				}
			}
		}
		return implode('', $parts);
	}

	/**
	 * Returns an array with the $content divided by tag-blocks specified with the list of tags, $tag
	 * Even numbers in the array are outside the blocks, Odd numbers are block-content.
	 * Use ->getAllParts() and ->removeFirstAndLastTag() to process the content if needed.
	 *
	 * @param string $tag List of tags
	 * @param string $content HTML-content
	 * @return array Even numbers in the array are outside the blocks, Odd numbers are block-content.
	 * @see splitIntoBlock(), getAllParts(), removeFirstAndLastTag()
	 * @todo Define visibility
	 */
	public function splitTags($tag, $content) {
		$tags = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tag, 1);
		foreach ($tags as &$tag) {
			$tag = preg_quote($tag, '/');
		}
		$regexStr = '/\\<(' . implode('|', $tags) . ')(\\s[^>]*)?\\/?>/si';
		$parts = preg_split($regexStr, $content);
		$pointer = strlen($parts[0]);
		$newParts = array();
		$newParts[] = $parts[0];
		reset($parts);
		next($parts);
		while (list($k, $v) = each($parts)) {
			$tagLen = strcspn(substr($content, $pointer), '>') + 1;
			// Set tag:
			// New buffer set and pointer increased
			$tag = substr($content, $pointer, $tagLen);
			$newParts[] = $tag;
			$pointer += strlen($tag);
			// Set content:
			$newParts[] = $v;
			$pointer += strlen($v);
		}
		return $newParts;
	}

	/**
	 * Returns an array with either tag or non-tag content of the result from ->splitIntoBlock()/->splitTags()
	 *
	 * @param array $parts Parts generated by ->splitIntoBlock() or >splitTags()
	 * @param boolean $tag_parts Whether to return the tag-parts (default,TRUE) or what was outside the tags.
	 * @param boolean $include_tag Whether to include the tags in the tag-parts (most useful for input made by ->splitIntoBlock())
	 * @return array Tag-parts/Non-tag-parts depending on input argument settings
	 * @see splitIntoBlock(), splitTags()
	 * @todo Define visibility
	 */
	public function getAllParts($parts, $tag_parts = TRUE, $include_tag = TRUE) {
		$newParts = array();
		foreach ($parts as $k => $v) {
			if (($k + ($tag_parts ? 0 : 1)) % 2) {
				if (!$include_tag) {
					$v = $this->removeFirstAndLastTag($v);
				}
				$newParts[] = $v;
			}
		}
		return $newParts;
	}

	/**
	 * Removes the first and last tag in the string
	 * Anything before the first and after the last tags respectively is also removed
	 *
	 * @param string $str String to process
	 * @return string
	 * @todo Define visibility
	 */
	public function removeFirstAndLastTag($str) {
		// End of first tag:
		$start = strpos($str, '>');
		// Begin of last tag:
		$end = strrpos($str, '<');
		// Return
		return substr($str, $start + 1, $end - $start - 1);
	}

	/**
	 * Returns the first tag in $str
	 * Actually everything from the begining of the $str is returned, so you better make sure the tag is the first thing...
	 *
	 * @param string $str HTML string with tags
	 * @return string
	 * @todo Define visibility
	 */
	public function getFirstTag($str) {
		// First:
		$endLen = strpos($str, '>') + 1;
		return substr($str, 0, $endLen);
	}

	/**
	 * Returns the NAME of the first tag in $str
	 *
	 * @param string $str HTML tag (The element name MUST be separated from the attributes by a space character! Just *whitespace* will not do)
	 * @param boolean $preserveCase If set, then the tag is NOT converted to uppercase by case is preserved.
	 * @return string Tag name in upper case
	 * @see getFirstTag()
	 * @todo Define visibility
	 */
	public function getFirstTagName($str, $preserveCase = FALSE) {
		$matches = array();
		if (preg_match('/^\\s*\\<([^\\s\\>]+)(\\s|\\>)/', $str, $matches) === 1) {
			if (!$preserveCase) {
				return strtoupper($matches[1]);
			}
			return $matches[1];
		}
		return '';
	}

	/**
	 * Returns an array with all attributes as keys. Attributes are only lowercase a-z
	 * If a attribute is empty (shorthand), then the value for the key is empty. You can check if it existed with isset()
	 *
	 * @param string $tag Tag: $tag is either a whole tag (eg '<TAG OPTION ATTRIB=VALUE>') or the parameterlist (ex ' OPTION ATTRIB=VALUE>')
	 * @param boolean $deHSC If set, the attribute values are de-htmlspecialchar'ed. Should actually always be set!
	 * @return array array(Tag attributes,Attribute meta-data)
	 * @todo Define visibility
	 */
	public function get_tag_attributes($tag, $deHSC = 0) {
		list($components, $metaC) = $this->split_tag_attributes($tag);
		// Attribute name is stored here
		$name = '';
		$valuemode = FALSE;
		$attributes = array();
		$attributesMeta = array();
		if (is_array($components)) {
			foreach ($components as $key => $val) {
				// Only if $name is set (if there is an attribute, that waits for a value), that valuemode is enabled. This ensures that the attribute is assigned it's value
				if ($val != '=') {
					if ($valuemode) {
						if ($name) {
							$attributes[$name] = $deHSC ? \TYPO3\CMS\Core\Utility\GeneralUtility::htmlspecialchars_decode($val) : $val;
							$attributesMeta[$name]['dashType'] = $metaC[$key];
							$name = '';
						}
					} else {
						if ($namekey = preg_replace('/[^[:alnum:]_\\:\\-]/', '', $val)) {
							$name = strtolower($namekey);
							$attributesMeta[$name] = array();
							$attributesMeta[$name]['origTag'] = $namekey;
							$attributes[$name] = '';
						}
					}
					$valuemode = FALSE;
				} else {
					$valuemode = TRUE;
				}
			}
			return array($attributes, $attributesMeta);
		}
	}

	/**
	 * Returns an array with the 'components' from an attribute list. The result is normally analyzed by get_tag_attributes
	 * Removes tag-name if found
	 *
	 * @param string $tag The tag or attributes
	 * @return array
	 * @access private
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::split_tag_attributes()
	 * @todo Define visibility
	 */
	public function split_tag_attributes($tag) {
		$matches = array();
		if (preg_match('/(\\<[^\\s]+\\s+)?(.*?)\\s*(\\>)?$/s', $tag, $matches) !== 1) {
			return array(array(), array());
		}
		$tag_tmp = $matches[2];
		$metaValue = array();
		$value = array();
		$matches = array();
		if (preg_match_all('/("[^"]*"|\'[^\']*\'|[^\\s"\'\\=]+|\\=)/s', $tag_tmp, $matches) > 0) {
			foreach ($matches[1] as $part) {
				$firstChar = substr($part, 0, 1);
				if ($firstChar == '"' || $firstChar == '\'') {
					$metaValue[] = $firstChar;
					$value[] = substr($part, 1, -1);
				} else {
					$metaValue[] = '';
					$value[] = $part;
				}
			}
		}
		return array($value, $metaValue);
	}

	/**
	 * Checks whether block/solo tags are found in the correct amounts in HTML content
	 * Block tags are tags which are required to have an equal amount of start and end tags, eg. "<table>...</table>"
	 * Solo tags are tags which are required to have ONLY start tags (possibly with an XHTML ending like ".../>")
	 * NOTICE: Correct XHTML might actually fail since "<br></br>" is allowed as well as "<br/>". However only the LATTER is accepted by this function (with "br" in the "solo-tag" list), the first example will result in a warning.
	 * NOTICE: Correct XHTML might actually fail since "<p/>" is allowed as well as "<p></p>". However only the LATTER is accepted by this function (with "p" in the "block-tag" list), the first example will result in an ERROR!
	 * NOTICE: Correct HTML version "something" allows eg. <p> and <li> to be NON-ended (implicitly ended by other tags). However this is NOT accepted by this function (with "p" and "li" in the block-tag list) and it will result in an ERROR!
	 *
	 * @param string $content HTML content to analyze
	 * @param string $blockTags Tag names for block tags (eg. table or div or p) in lowercase, commalist (eg. "table,div,p")
	 * @param string $soloTags Tag names for solo tags (eg. img, br or input) in lowercase, commalist ("img,br,input")
	 * @return array Analyse data.
	 * @todo Define visibility
	 */
	public function checkTagTypeCounts($content, $blockTags = 'a,b,blockquote,body,div,em,font,form,h1,h2,h3,h4,h5,h6,i,li,map,ol,option,p,pre,select,span,strong,table,td,textarea,tr,u,ul', $soloTags = 'br,hr,img,input,area') {
		$content = strtolower($content);
		$analyzedOutput = array();
		// Counts appearances of start-tags
		$analyzedOutput['counts'] = array();
		// Lists ERRORS
		$analyzedOutput['errors'] = array();
		// Lists warnings.
		$analyzedOutput['warnings'] = array();
		// Lists stats for block-tags
		$analyzedOutput['blocks'] = array();
		// Lists stats for solo-tags
		$analyzedOutput['solo'] = array();
		// Block tags, must have endings...
		$blockTags = explode(',', $blockTags);
		foreach ($blockTags as $tagName) {
			$countBegin = count(preg_split(('/\\<' . preg_quote($tagName, '/') . '(\\s|\\>)/s'), $content)) - 1;
			$countEnd = count(preg_split(('/\\<\\/' . preg_quote($tagName, '/') . '(\\s|\\>)/s'), $content)) - 1;
			$analyzedOutput['blocks'][$tagName] = array($countBegin, $countEnd, $countBegin - $countEnd);
			if ($countBegin) {
				$analyzedOutput['counts'][$tagName] = $countBegin;
			}
			if ($countBegin - $countEnd) {
				if ($countBegin - $countEnd > 0) {
					$analyzedOutput['errors'][$tagName] = 'There were more start-tags (' . $countBegin . ') than end-tags (' . $countEnd . ') for the element "' . $tagName . '". There should be an equal amount!';
				} else {
					$analyzedOutput['warnings'][$tagName] = 'There were more end-tags (' . $countEnd . ') than start-tags (' . $countBegin . ') for the element "' . $tagName . '". There should be an equal amount! However the problem is not fatal.';
				}
			}
		}
		// Solo tags, must NOT have endings...
		$soloTags = explode(',', $soloTags);
		foreach ($soloTags as $tagName) {
			$countBegin = count(preg_split(('/\\<' . preg_quote($tagName, '/') . '(\\s|\\>)/s'), $content)) - 1;
			$countEnd = count(preg_split(('/\\<\\/' . preg_quote($tagName, '/') . '(\\s|\\>)/s'), $content)) - 1;
			$analyzedOutput['solo'][$tagName] = array($countBegin, $countEnd);
			if ($countBegin) {
				$analyzedOutput['counts'][$tagName] = $countBegin;
			}
			if ($countEnd) {
				$analyzedOutput['warnings'][$tagName] = 'There were end-tags found (' . $countEnd . ') for the element "' . $tagName . '". This was not expected (although XHTML technically allows it).';
			}
		}
		return $analyzedOutput;
	}

	/*********************************
	 *
	 * Clean HTML code
	 *
	 *********************************/
	/**
	 * Function that can clean up HTML content according to configuration given in the $tags array.
	 *
	 * Initializing the $tags array to allow a list of tags (in this case <B>,<I>,<U> and <A>), set it like this:		 $tags = array_flip(explode(',','b,a,i,u'))
	 * If the value of the $tags[$tagname] entry is an array, advanced processing of the tags is initialized. These are the options:
	 *
	 * $tags[$tagname] = Array(
	 * 'overrideAttribs' => ''		If set, this string is preset as the attributes of the tag
	 * 'allowedAttribs' =>   '0' (zero) = no attributes allowed, '[commalist of attributes]' = only allowed attributes. If blank, all attributes are allowed.
	 * 'fixAttrib' => Array(
	 * '[attribute name]' => Array (
	 * 'set' => Force the attribute value to this value.
	 * 'unset' => Boolean: If set, the attribute is unset.
	 * 'default' =>	 If no attribute exists by this name, this value is set as default value (if this value is not blank)
	 * 'always' =>	 Boolean. If set, the attribute is always processed. Normally an attribute is processed only if it exists
	 * 'trim,intval,lower,upper' =>	 All booleans. If any of these keys are set, the value is passed through the respective PHP-functions.
	 * 'range' => Array ('[low limit]','[high limit, optional]')		Setting integer range.
	 * 'list' => Array ('[value1/default]','[value2]','[value3]')		Attribute must be in this list. If not, the value is set to the first element.
	 * 'removeIfFalse' =>	 Boolean/'blank'.	If set, then the attribute is removed if it is 'FALSE'. If this value is set to 'blank' then the value must be a blank string (that means a 'zero' value will not be removed)
	 * 'removeIfEquals' =>	 [value]	If the attribute value matches the value set here, then it is removed.
	 * 'casesensitiveComp' => 1	If set, then the removeIfEquals and list comparisons will be case sensitive. Otherwise not.
	 * )
	 * ),
	 * 'protect' => '',	Boolean. If set, the tag <> is converted to &lt; and &gt;
	 * 'remap' => '',		String. If set, the tagname is remapped to this tagname
	 * 'rmTagIfNoAttrib' => '',	Boolean. If set, then the tag is removed if no attributes happend to be there.
	 * 'nesting' => '',	Boolean/'global'. If set TRUE, then this tag must have starting and ending tags in the correct order. Any tags not in this order will be discarded. Thus '</B><B><I></B></I></B>' will be converted to '<B><I></B></I>'. Is the value 'global' then true nesting in relation to other tags marked for 'global' nesting control is preserved. This means that if <B> and <I> are set for global nesting then this string '</B><B><I></B></I></B>' is converted to '<B></B>'
	 * )
	 *
	 * @param string $content Is the HTML-content being processed. This is also the result being returned.
	 * @param array $tags Is an array where each key is a tagname in lowercase. Only tags present as keys in this array are preserved. The value of the key can be an array with a vast number of options to configure.
	 * @param string $keepAll Boolean/'protect', if set, then all tags are kept regardless of tags present as keys in $tags-array. If 'protect' then the preserved tags have their <> converted to &lt; and &gt;
	 * @param integer $hSC Values -1,0,1,2: Set to zero= disabled, set to 1 then the content BETWEEN tags is htmlspecialchar()'ed, set to -1 its the opposite and set to 2 the content will be HSC'ed BUT with preservation for real entities (eg. "&amp;" or "&#234;")
	 * @param array $addConfig Configuration array send along as $conf to the internal functions ->processContent() and ->processTag()
	 * @return string Processed HTML content
	 * @todo Define visibility
	 */
	public function HTMLcleaner($content, $tags = array(), $keepAll = 0, $hSC = 0, $addConfig = array()) {
		$newContent = array();
		$tokArr = explode('<', $content);
		$newContent[] = $this->processContent(current($tokArr), $hSC, $addConfig);
		next($tokArr);
		$c = 1;
		$tagRegister = array();
		$tagStack = array();
		$inComment = FALSE;
		$skipTag = FALSE;
		while (list(, $tok) = each($tokArr)) {
			if ($inComment) {
				if (($eocPos = strpos($tok, '-->')) === FALSE) {
					// End of comment is not found in the token. Go further until end of comment is found in other tokens.
					$newContent[$c++] = '<' . $tok;
					continue;
				}
				// Comment ends in the middle of the token: add comment and proceed with rest of the token
				$newContent[$c++] = '<' . substr($tok, 0, ($eocPos + 3));
				$tok = substr($tok, $eocPos + 3);
				$inComment = FALSE;
				$skipTag = TRUE;
			} elseif (substr($tok, 0, 3) == '!--') {
				if (($eocPos = strpos($tok, '-->')) === FALSE) {
					// Comment started in this token but it does end in the same token. Set a flag to skip till the end of comment
					$newContent[$c++] = '<' . $tok;
					$inComment = TRUE;
					continue;
				}
				// Start and end of comment are both in the current token. Add comment and proceed with rest of the token
				$newContent[$c++] = '<' . substr($tok, 0, ($eocPos + 3));
				$tok = substr($tok, $eocPos + 3);
				$skipTag = TRUE;
			}
			$firstChar = substr($tok, 0, 1);
			// It is a tag... (first char is a-z0-9 or /) (fixed 19/01 2004). This also avoids triggering on <?xml..> and <!DOCTYPE..>
			if (!$skipTag && preg_match('/[[:alnum:]\\/]/', $firstChar) == 1) {
				$tagEnd = strpos($tok, '>');
				// If there is and end-bracket...	tagEnd can't be 0 as the first character can't be a >
				if ($tagEnd) {
					$endTag = $firstChar == '/' ? 1 : 0;
					$tagContent = substr($tok, $endTag, $tagEnd - $endTag);
					$tagParts = preg_split('/\\s+/s', $tagContent, 2);
					$tagName = strtolower($tagParts[0]);
					$emptyTag = 0;
					if (isset($tags[$tagName])) {
						// If there is processing to do for the tag:
						if (is_array($tags[$tagName])) {
							if (preg_match('/^(' . self::VOID_ELEMENTS . ' )$/i', $tagName)) {
								$emptyTag = 1;
							}
							// If NOT an endtag, do attribute processing (added dec. 2003)
							if (!$endTag) {
								// Override attributes
								if (strcmp($tags[$tagName]['overrideAttribs'], '')) {
									$tagParts[1] = $tags[$tagName]['overrideAttribs'];
								}
								// Allowed tags
								if (strcmp($tags[$tagName]['allowedAttribs'], '')) {
									// No attribs allowed
									if (!strcmp($tags[$tagName]['allowedAttribs'], '0')) {
										$tagParts[1] = '';
									} elseif (trim($tagParts[1])) {
										$tagAttrib = $this->get_tag_attributes($tagParts[1]);
										$tagParts[1] = '';
										$newTagAttrib = array();
										if (!($tList = $tags[$tagName]['_allowedAttribs'])) {
											// Just explode attribts for tag once
											$tList = ($tags[$tagName]['_allowedAttribs'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($tags[$tagName]['allowedAttribs']), 1));
										}
										foreach ($tList as $allowTag) {
											if (isset($tagAttrib[0][$allowTag])) {
												$newTagAttrib[$allowTag] = $tagAttrib[0][$allowTag];
											}
										}
										$tagParts[1] = $this->compileTagAttribs($newTagAttrib, $tagAttrib[1]);
									}
								}
								// Fixed attrib values
								if (is_array($tags[$tagName]['fixAttrib'])) {
									$tagAttrib = $this->get_tag_attributes($tagParts[1]);
									$tagParts[1] = '';
									foreach ($tags[$tagName]['fixAttrib'] as $attr => $params) {
										if (strlen($params['set'])) {
											$tagAttrib[0][$attr] = $params['set'];
										}
										if (isset($params['unset']) && !empty($params['unset'])) {
											unset($tagAttrib[0][$attr]);
										}
										if (strcmp($params['default'], '') && !isset($tagAttrib[0][$attr])) {
											$tagAttrib[0][$attr] = $params['default'];
										}
										if ($params['always'] || isset($tagAttrib[0][$attr])) {
											if ($params['trim']) {
												$tagAttrib[0][$attr] = trim($tagAttrib[0][$attr]);
											}
											if ($params['intval']) {
												$tagAttrib[0][$attr] = intval($tagAttrib[0][$attr]);
											}
											if ($params['lower']) {
												$tagAttrib[0][$attr] = strtolower($tagAttrib[0][$attr]);
											}
											if ($params['upper']) {
												$tagAttrib[0][$attr] = strtoupper($tagAttrib[0][$attr]);
											}
											if ($params['range']) {
												if (isset($params['range'][1])) {
													$tagAttrib[0][$attr] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($tagAttrib[0][$attr], intval($params['range'][0]), intval($params['range'][1]));
												} else {
													$tagAttrib[0][$attr] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($tagAttrib[0][$attr], intval($params['range'][0]));
												}
											}
											if (is_array($params['list'])) {
												// For the class attribute, remove from the attribute value any class not in the list
												// Classes are case sensitive
												if ($attr == 'class') {
													$newClasses = array();
													$classes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $tagAttrib[0][$attr], TRUE);
													foreach ($classes as $class) {
														if (in_array($class, $params['list'])) {
															$newClasses[] = $class;
														}
													}
													if (count($newClasses)) {
														$tagAttrib[0][$attr] = implode(' ', $newClasses);
													} else {
														$tagAttrib[0][$attr] = '';
													}
												} else {
													if (!in_array($this->caseShift($tagAttrib[0][$attr], $params['casesensitiveComp']), $this->caseShift($params['list'], $params['casesensitiveComp'], $tagName))) {
														$tagAttrib[0][$attr] = $params['list'][0];
													}
												}
											}
											if ($params['removeIfFalse'] && $params['removeIfFalse'] != 'blank' && !$tagAttrib[0][$attr] || $params['removeIfFalse'] == 'blank' && !strcmp($tagAttrib[0][$attr], '')) {
												unset($tagAttrib[0][$attr]);
											}
											if (strcmp($params['removeIfEquals'], '') && !strcmp($this->caseShift($tagAttrib[0][$attr], $params['casesensitiveComp']), $this->caseShift($params['removeIfEquals'], $params['casesensitiveComp']))) {
												unset($tagAttrib[0][$attr]);
											}
											if ($params['prefixLocalAnchors']) {
												if (substr($tagAttrib[0][$attr], 0, 1) == '#') {
													$prefix = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
													$tagAttrib[0][$attr] = $prefix . $tagAttrib[0][$attr];
													if ($params['prefixLocalAnchors'] == 2 && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($prefix, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL'))) {
														$tagAttrib[0][$attr] = substr($tagAttrib[0][$attr], strlen(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL')));
													}
												}
											}
											if ($params['prefixRelPathWith']) {
												$urlParts = parse_url($tagAttrib[0][$attr]);
												if (!$urlParts['scheme'] && substr($urlParts['path'], 0, 1) != '/') {
													// If it is NOT an absolute URL (by http: or starting "/")
													$tagAttrib[0][$attr] = $params['prefixRelPathWith'] . $tagAttrib[0][$attr];
												}
											}
											if ($params['userFunc']) {
												$tagAttrib[0][$attr] = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($params['userFunc'], $tagAttrib[0][$attr], $this);
											}
										}
									}
									$tagParts[1] = $this->compileTagAttribs($tagAttrib[0], $tagAttrib[1]);
								}
							} else {
								// If endTag, remove any possible attributes:
								$tagParts[1] = '';
							}
							// Protecting the tag by converting < and > to &lt; and &gt; ??
							if ($tags[$tagName]['protect']) {
								$lt = '&lt;';
								$gt = '&gt;';
							} else {
								$lt = '<';
								$gt = '>';
							}
							// Remapping tag name?
							if ($tags[$tagName]['remap']) {
								$tagParts[0] = $tags[$tagName]['remap'];
							}
							// rmTagIfNoAttrib
							if ($endTag || trim($tagParts[1]) || !$tags[$tagName]['rmTagIfNoAttrib']) {
								$setTag = 1;
								// Remove this closing tag if $tagName was among $TSconfig['removeTags']
								if ($endTag && $tags[$tagName]['allowedAttribs'] === 0 && $tags[$tagName]['rmTagIfNoAttrib'] === 1) {
									$setTag = 0;
								}
								if ($tags[$tagName]['nesting']) {
									if (!is_array($tagRegister[$tagName])) {
										$tagRegister[$tagName] = array();
									}
									if ($endTag) {
										$correctTag = 1;
										if ($tags[$tagName]['nesting'] == 'global') {
											$lastEl = end($tagStack);
											if (strcmp($tagName, $lastEl)) {
												if (in_array($tagName, $tagStack)) {
													while (count($tagStack) && strcmp($tagName, $lastEl)) {
														$elPos = end($tagRegister[$lastEl]);
														unset($newContent[$elPos]);
														array_pop($tagRegister[$lastEl]);
														array_pop($tagStack);
														$lastEl = end($tagStack);
													}
												} else {
													// In this case the
													$correctTag = 0;
												}
											}
										}
										if (!count($tagRegister[$tagName]) || !$correctTag) {
											$setTag = 0;
										} else {
											array_pop($tagRegister[$tagName]);
											if ($tags[$tagName]['nesting'] == 'global') {
												array_pop($tagStack);
											}
										}
									} else {
										array_push($tagRegister[$tagName], $c);
										if ($tags[$tagName]['nesting'] == 'global') {
											array_push($tagStack, $tagName);
										}
									}
								}
								if ($setTag) {
									// Setting the tag
									$newContent[$c++] = $this->processTag($lt . ($endTag ? '/' : '') . trim(($tagParts[0] . ' ' . $tagParts[1])) . ($emptyTag ? ' /' : '') . $gt, $addConfig, $endTag, $lt == '&lt;');
								}
							}
						} else {
							$newContent[$c++] = $this->processTag('<' . ($endTag ? '/' : '') . $tagContent . '>', $addConfig, $endTag);
						}
					} elseif ($keepAll) {
						// This is if the tag was not defined in the array for processing:
						if (!strcmp($keepAll, 'protect')) {
							$lt = '&lt;';
							$gt = '&gt;';
						} else {
							$lt = '<';
							$gt = '>';
						}
						$newContent[$c++] = $this->processTag($lt . ($endTag ? '/' : '') . $tagContent . $gt, $addConfig, $endTag, $lt == '&lt;');
					}
					$newContent[$c++] = $this->processContent(substr($tok, $tagEnd + 1), $hSC, $addConfig);
				} else {
					$newContent[$c++] = $this->processContent('<' . $tok, $hSC, $addConfig);
				}
			} else {
				$newContent[$c++] = $this->processContent(($skipTag ? '' : '<') . $tok, $hSC, $addConfig);
				// It was not a tag anyways
				$skipTag = FALSE;
			}
		}
		// Unsetting tags:
		foreach ($tagRegister as $tag => $positions) {
			foreach ($positions as $pKey) {
				unset($newContent[$pKey]);
			}
		}
		return implode('', $newContent);
	}

	/**
	 * Converts htmlspecialchars forth ($dir=1) AND back ($dir=-1)
	 *
	 * @param string $value Input value
	 * @param integer $dir Direction: forth ($dir=1, dir=2 for preserving entities) AND back ($dir=-1)
	 * @return string Output value
	 * @todo Define visibility
	 */
	public function bidir_htmlspecialchars($value, $dir) {
		if ($dir == 1) {
			$value = htmlspecialchars($value);
		} elseif ($dir == 2) {
			$value = \TYPO3\CMS\Core\Utility\GeneralUtility::deHSCentities(htmlspecialchars($value));
		} elseif ($dir == -1) {
			$value = str_replace('&gt;', '>', $value);
			$value = str_replace('&lt;', '<', $value);
			$value = str_replace('&quot;', '"', $value);
			$value = str_replace('&amp;', '&', $value);
		}
		return $value;
	}

	/**
	 * Prefixes the relative paths of hrefs/src/action in the tags [td,table,body,img,input,form,link,script,a] in the $content with the $main_prefix or and alternative given by $alternatives
	 *
	 * @param string $main_prefix Prefix string
	 * @param string $content HTML content
	 * @param array $alternatives Array with alternative prefixes for certain of the tags. key=>value pairs where the keys are the tag element names in uppercase
	 * @param string $suffix Suffix string (put after the resource).
	 * @return string Processed HTML content
	 * @todo Define visibility
	 */
	public function prefixResourcePath($main_prefix, $content, $alternatives = array(), $suffix = '') {
		$parts = $this->splitTags('embed,td,table,body,img,input,form,link,script,a,param', $content);
		foreach ($parts as $k => $v) {
			if ($k % 2) {
				$params = $this->get_tag_attributes($v);
				// Detect tag-ending so that it is re-applied correctly.
				$tagEnd = substr($v, -2) == '/>' ? ' />' : '>';
				// The 'name' of the first tag
				$firstTagName = $this->getFirstTagName($v);
				$somethingDone = 0;
				$prefix = isset($alternatives[strtoupper($firstTagName)]) ? $alternatives[strtoupper($firstTagName)] : $main_prefix;
				switch (strtolower($firstTagName)) {
				case 'td':

				case 'body':

				case 'table':
					$src = $params[0]['background'];
					if ($src) {
						$params[0]['background'] = $this->prefixRelPath($prefix, $params[0]['background'], $suffix);
						$somethingDone = 1;
					}
					break;
				case 'img':

				case 'input':

				case 'script':

				case 'embed':
					$src = $params[0]['src'];
					if ($src) {
						$params[0]['src'] = $this->prefixRelPath($prefix, $params[0]['src'], $suffix);
						$somethingDone = 1;
					}
					break;
				case 'link':

				case 'a':
					$src = $params[0]['href'];
					if ($src) {
						$params[0]['href'] = $this->prefixRelPath($prefix, $params[0]['href'], $suffix);
						$somethingDone = 1;
					}
					break;
				case 'form':
					$src = $params[0]['action'];
					if ($src) {
						$params[0]['action'] = $this->prefixRelPath($prefix, $params[0]['action'], $suffix);
						$somethingDone = 1;
					}
					break;
				case 'param':
					$test = $params[0]['name'];
					if ($test && $test === 'movie') {
						if ($params[0]['value']) {
							$params[0]['value'] = $this->prefixRelPath($prefix, $params[0]['value'], $suffix);
							$somethingDone = 1;
						}
					}
					break;
				}
				if ($somethingDone) {
					$tagParts = preg_split('/\\s+/s', $v, 2);
					$tagParts[1] = $this->compileTagAttribs($params[0], $params[1]);
					$parts[$k] = '<' . trim((strtolower($firstTagName) . ' ' . $tagParts[1])) . $tagEnd;
				}
			}
		}
		$content = implode('', $parts);
		// Fix <style> section:
		$prefix = isset($alternatives['style']) ? $alternatives['style'] : $main_prefix;
		if (strlen($prefix)) {
			$parts = $this->splitIntoBlock('style', $content);
			foreach ($parts as $k => &$part) {
				if ($k % 2) {
					$part = preg_replace('/(url[[:space:]]*\\([[:space:]]*["\']?)([^"\')]*)(["\']?[[:space:]]*\\))/i', '\\1' . $prefix . '\\2' . $suffix . '\\3', $part);
				}
			}
			unset($part);
			$content = implode('', $parts);
		}
		return $content;
	}

	/**
	 * Internal sub-function for ->prefixResourcePath()
	 *
	 * @param string $prefix Prefix string
	 * @param string $srcVal Relative path/URL
	 * @param string $suffix Suffix string
	 * @return string Output path, prefixed if no scheme in input string
	 * @access private
	 * @todo Define visibility
	 */
	public function prefixRelPath($prefix, $srcVal, $suffix = '') {
		// Only prefix if it's not an absolute URL or
		// only a link to a section within the page.
		if (substr($srcVal, 0, 1) != '/' && substr($srcVal, 0, 1) != '#') {
			$urlParts = parse_url($srcVal);
			// Only prefix URLs without a scheme
			if (!$urlParts['scheme']) {
				$srcVal = $prefix . $srcVal . $suffix;
			}
		}
		return $srcVal;
	}

	/**
	 * Cleans up the input $value for fonttags.
	 * If keepFace,-Size and -Color is set then font-tags with an allowed property is kept. Else deleted.
	 *
	 * @param string HTML content with font-tags inside to clean up.
	 * @param boolean If set, keep "face" attribute
	 * @param boolean If set, keep "size" attribute
	 * @param boolean If set, keep "color" attribute
	 * @return string Processed HTML content
	 * @todo Define visibility
	 */
	public function cleanFontTags($value, $keepFace = 0, $keepSize = 0, $keepColor = 0) {
		// ,1 ?? - could probably be more stable if splitTags() was used since this depends on end-tags being properly set!
		$fontSplit = $this->splitIntoBlock('font', $value);
		foreach ($fontSplit as $k => $v) {
			// Font
			if ($k % 2) {
				$attribArray = $this->get_tag_attributes_classic($this->getFirstTag($v));
				$newAttribs = array();
				if ($keepFace && $attribArray['face']) {
					$newAttribs[] = 'face="' . $attribArray['face'] . '"';
				}
				if ($keepSize && $attribArray['size']) {
					$newAttribs[] = 'size="' . $attribArray['size'] . '"';
				}
				if ($keepColor && $attribArray['color']) {
					$newAttribs[] = 'color="' . $attribArray['color'] . '"';
				}
				$innerContent = $this->cleanFontTags($this->removeFirstAndLastTag($v), $keepFace, $keepSize, $keepColor);
				if (count($newAttribs)) {
					$fontSplit[$k] = '<font ' . implode(' ', $newAttribs) . '>' . $innerContent . '</font>';
				} else {
					$fontSplit[$k] = $innerContent;
				}
			}
		}
		return implode('', $fontSplit);
	}

	/**
	 * This is used to map certain tag-names into other names.
	 *
	 * @param string $value HTML content
	 * @param array $tags Array with tag key=>value pairs where key is from-tag and value is to-tag
	 * @param string $ltChar Alternative less-than char to search for (search regex string)
	 * @param string $ltChar2 Alternative less-than char to replace with (replace regex string)
	 * @return string Processed HTML content
	 * @todo Define visibility
	 */
	public function mapTags($value, $tags = array(), $ltChar = '<', $ltChar2 = '<') {
		foreach ($tags as $from => $to) {
			$value = preg_replace('/' . preg_quote($ltChar, '/') . '(\\/)?' . $from . '\\s([^\\>])*(\\/)?\\>/', $ltChar2 . '$1' . $to . ' $2$3>', $value);
		}
		return $value;
	}

	/**
	 * This converts htmlspecialchar()'ed tags (from $tagList) back to real tags. Eg. '&lt;strong&gt' would be converted back to '<strong>' if found in $tagList
	 *
	 * @param string $content HTML content
	 * @param string $tagList Tag list, separated by comma. Lowercase!
	 * @return string Processed HTML content
	 * @todo Define visibility
	 */
	public function unprotectTags($content, $tagList = '') {
		$tagsArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tagList, 1);
		$contentParts = explode('&lt;', $content);
		next($contentParts);
		// bypass the first
		while (list($k, $tok) = each($contentParts)) {
			$firstChar = substr($tok, 0, 1);
			if (strcmp(trim($firstChar), '')) {
				$subparts = explode('&gt;', $tok, 2);
				$tagEnd = strlen($subparts[0]);
				if (strlen($tok) != $tagEnd) {
					$endTag = $firstChar == '/' ? 1 : 0;
					$tagContent = substr($tok, $endTag, $tagEnd - $endTag);
					$tagParts = preg_split('/\\s+/s', $tagContent, 2);
					$tagName = strtolower($tagParts[0]);
					if (!strcmp($tagList, '') || in_array($tagName, $tagsArray)) {
						$contentParts[$k] = '<' . $subparts[0] . '>' . $subparts[1];
					} else {
						$contentParts[$k] = '&lt;' . $tok;
					}
				} else {
					$contentParts[$k] = '&lt;' . $tok;
				}
			} else {
				$contentParts[$k] = '&lt;' . $tok;
			}
		}
		return implode('', $contentParts);
	}

	/**
	 * Strips tags except the tags in the list, $tagList
	 * OBSOLETE - use PHP function strip_tags()
	 *
	 * @param string $value Value to process
	 * @param string $tagList List of tags
	 * @return string Output value
	 * @deprecated For a long time, deprecationLog added since 6.0, well be removed two versions later
	 * @todo Define visibility
	 */
	public function stripTagsExcept($value, $tagList) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$tags = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tagList, 1);
		$forthArr = array();
		$backArr = array();
		foreach ($tags as $theTag) {
			$forthArr[$theTag] = md5($theTag);
			$backArr[md5($theTag)] = $theTag;
		}
		$value = $this->mapTags($value, $forthArr, '<', '_');
		$value = strip_tags($value);
		$value = $this->mapTags($value, $backArr, '_', '<');
		return $value;
	}

	/**
	 * Internal function for case shifting of a string or whole array
	 *
	 * @param mixed $str Input string/array
	 * @param boolean $flag If $str is a string AND this boolean(caseSensitive) is FALSE, the string is returned in uppercase
	 * @param string $cacheKey Key string used for internal caching of the results. Could be an MD5 hash of the serialized version of the input $str if that is an array.
	 * @return string Output string, processed
	 * @access private
	 * @todo Define visibility
	 */
	public function caseShift($str, $flag, $cacheKey = '') {
		$cacheKey .= $flag ? 1 : 0;
		if (is_array($str)) {
			if (!$cacheKey || !isset($this->caseShift_cache[$cacheKey])) {
				foreach ($str as &$v) {
					if (!$flag) {
						$v = strtoupper($v);
					}
				}
				unset($v);
				if ($cacheKey) {
					$this->caseShift_cache[$cacheKey] = $str;
				}
			} else {
				$str = $this->caseShift_cache[$cacheKey];
			}
		} elseif (!$flag) {
			$str = strtoupper($str);
		}
		return $str;
	}

	/**
	 * Compiling an array with tag attributes into a string
	 *
	 * @param array $tagAttrib Tag attributes
	 * @param array $meta Meta information about these attributes (like if they were quoted)
	 * @param boolean $xhtmlClean If set, then the attribute names will be set in lower case, value quotes in double-quotes and the value will be htmlspecialchar()'ed
	 * @return string Imploded attributes, eg: 'attribute="value" attrib2="value2"'
	 * @access private
	 * @todo Define visibility
	 */
	public function compileTagAttribs($tagAttrib, $meta = array(), $xhtmlClean = 0) {
		$accu = array();
		foreach ($tagAttrib as $k => $v) {
			if ($xhtmlClean) {
				$attr = strtolower($k);
				if (strcmp($v, '') || isset($meta[$k]['dashType'])) {
					$attr .= '="' . htmlspecialchars($v) . '"';
				}
			} else {
				$attr = $meta[$k]['origTag'] ? $meta[$k]['origTag'] : $k;
				if (strcmp($v, '') || isset($meta[$k]['dashType'])) {
					$dash = $meta[$k]['dashType'] ? $meta[$k]['dashType'] : (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($v) ? '' : '"');
					$attr .= '=' . $dash . $v . $dash;
				}
			}
			$accu[] = $attr;
		}
		return implode(' ', $accu);
	}

	/**
	 * Get tag attributes, the classic version (which had some limitations?)
	 *
	 * @param string $tag The tag
	 * @param boolean $deHSC De-htmlspecialchar flag.
	 * @return array
	 * @access private
	 * @todo Define visibility
	 */
	public function get_tag_attributes_classic($tag, $deHSC = 0) {
		$attr = $this->get_tag_attributes($tag, $deHSC);
		return is_array($attr[0]) ? $attr[0] : array();
	}

	/**
	 * Indents input content with $number instances of $indentChar
	 *
	 * @param string $content Content string, multiple lines.
	 * @param integer $number Number of indents
	 * @param string $indentChar Indent character/string
	 * @return strin Indented code (typ. HTML)
	 * @todo Define visibility
	 */
	public function indentLines($content, $number = 1, $indentChar = TAB) {
		$preTab = str_pad('', $number * strlen($indentChar), $indentChar);
		$lines = explode(LF, str_replace(CR, '', $content));
		foreach ($lines as &$line) {
			$line = $preTab . $line;
		}
		unset($line);
		return implode(LF, $lines);
	}

	/**
	 * Converts TSconfig into an array for the HTMLcleaner function.
	 *
	 * @param array $TSconfig TSconfig for HTMLcleaner
	 * @param array $keepTags Array of tags to keep (?)
	 * @return array
	 * @access private
	 * @todo Define visibility
	 */
	public function HTMLparserConfig($TSconfig, $keepTags = array()) {
		// Allow tags (base list, merged with incoming array)
		$alTags = array_flip(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($TSconfig['allowTags']), 1));
		$keepTags = array_merge($alTags, $keepTags);
		// Set config properties.
		if (is_array($TSconfig['tags.'])) {
			foreach ($TSconfig['tags.'] as $key => $tagC) {
				if (!is_array($tagC) && $key == strtolower($key)) {
					if (!strcmp($tagC, '0')) {
						unset($keepTags[$key]);
					}
					if (!strcmp($tagC, '1') && !isset($keepTags[$key])) {
						$keepTags[$key] = 1;
					}
				}
			}
			foreach ($TSconfig['tags.'] as $key => $tagC) {
				if (is_array($tagC) && $key == strtolower($key)) {
					$key = substr($key, 0, -1);
					if (!is_array($keepTags[$key])) {
						$keepTags[$key] = array();
					}
					if (is_array($tagC['fixAttrib.'])) {
						foreach ($tagC['fixAttrib.'] as $atName => $atConfig) {
							if (is_array($atConfig)) {
								$atName = substr($atName, 0, -1);
								if (!is_array($keepTags[$key]['fixAttrib'][$atName])) {
									$keepTags[$key]['fixAttrib'][$atName] = array();
								}
								$keepTags[$key]['fixAttrib'][$atName] = array_merge($keepTags[$key]['fixAttrib'][$atName], $atConfig);
								// Candidate for \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge() if integer-keys will some day make trouble...
								if (strcmp($keepTags[$key]['fixAttrib'][$atName]['range'], '')) {
									$keepTags[$key]['fixAttrib'][$atName]['range'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $keepTags[$key]['fixAttrib'][$atName]['range']);
								}
								if (strcmp($keepTags[$key]['fixAttrib'][$atName]['list'], '')) {
									$keepTags[$key]['fixAttrib'][$atName]['list'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $keepTags[$key]['fixAttrib'][$atName]['list']);
								}
							}
						}
					}
					unset($tagC['fixAttrib.']);
					unset($tagC['fixAttrib']);
					// Candidate for \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge() if integer-keys will some day make trouble...
					$keepTags[$key] = array_merge($keepTags[$key], $tagC);
				}
			}
		}
		// LocalNesting
		if ($TSconfig['localNesting']) {
			$lN = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($TSconfig['localNesting']), 1);
			foreach ($lN as $tn) {
				if (isset($keepTags[$tn])) {
					$keepTags[$tn]['nesting'] = 1;
				}
			}
		}
		if ($TSconfig['globalNesting']) {
			$lN = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($TSconfig['globalNesting']), 1);
			foreach ($lN as $tn) {
				if (isset($keepTags[$tn])) {
					if (!is_array($keepTags[$tn])) {
						$keepTags[$tn] = array();
					}
					$keepTags[$tn]['nesting'] = 'global';
				}
			}
		}
		if ($TSconfig['rmTagIfNoAttrib']) {
			$lN = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($TSconfig['rmTagIfNoAttrib']), 1);
			foreach ($lN as $tn) {
				if (isset($keepTags[$tn])) {
					if (!is_array($keepTags[$tn])) {
						$keepTags[$tn] = array();
					}
					$keepTags[$tn]['rmTagIfNoAttrib'] = 1;
				}
			}
		}
		if ($TSconfig['noAttrib']) {
			$lN = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($TSconfig['noAttrib']), 1);
			foreach ($lN as $tn) {
				if (isset($keepTags[$tn])) {
					if (!is_array($keepTags[$tn])) {
						$keepTags[$tn] = array();
					}
					$keepTags[$tn]['allowedAttribs'] = 0;
				}
			}
		}
		if ($TSconfig['removeTags']) {
			$lN = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($TSconfig['removeTags']), 1);
			foreach ($lN as $tn) {
				$keepTags[$tn] = array();
				$keepTags[$tn]['allowedAttribs'] = 0;
				$keepTags[$tn]['rmTagIfNoAttrib'] = 1;
			}
		}
		// Create additional configuration:
		$addConfig = array();
		if ($TSconfig['xhtml_cleaning']) {
			$addConfig['xhtml'] = 1;
		}
		return array(
			$keepTags,
			'' . $TSconfig['keepNonMatchedTags'],
			intval($TSconfig['htmlSpecialChars']),
			$addConfig
		);
	}

	/**
	 * Tries to convert the content to be XHTML compliant and other stuff like that.
	 * STILL EXPERIMENTAL. See comments below.
	 *
	 * What it does NOT do (yet) according to XHTML specs.:
	 * - Wellformedness: Nesting is NOT checked
	 * - name/id attribute issue is not observed at this point.
	 * - Certain nesting of elements not allowed. Most interesting, <PRE> cannot contain img, big,small,sub,sup ...
	 * - Wrapping scripts and style element contents in CDATA - or alternatively they should have entitites converted.
	 * - Setting charsets may put some special requirements on both XML declaration/ meta-http-equiv. (C.9)
	 * - UTF-8 encoding is in fact expected by XML!!
	 * - stylesheet element and attribute names are NOT converted to lowercase
	 * - ampersands (and entities in general I think) MUST be converted to an entity reference! (&amps;). This may mean further conversion of non-tag content before output to page. May be related to the charset issue as a whole.
	 * - Minimized values not allowed: Must do this: selected="selected"
	 *
	 * What it does at this point:
	 * - All tags (frame,base,meta,link + img,br,hr,area,input) is ended with "/>" - others?
	 * - Lowercase for elements and attributes
	 * - All attributes in quotes
	 * - Add "alt" attribute to img-tags if it's not there already.
	 *
	 * @param string $content Content to clean up
	 * @return string Cleaned up content returned.
	 * @access private
	 * @todo Define visibility
	 */
	public function XHTML_clean($content) {
		$content = $this->HTMLcleaner($content, array(), 1, 0, array('xhtml' => 1));
		return $content;
	}

	/**
	 * Processing all tags themselves
	 * (Some additions by Sacha Vorbeck)
	 *
	 * @param string Tag to process
	 * @param array Configuration array passing instructions for processing. If count()==0, function will return value unprocessed. See source code for details
	 * @param boolean Is endtag, then set this.
	 * @param boolean If set, just return value straight away
	 * @return string Processed value.
	 * @access private
	 * @todo Define visibility
	 */
	public function processTag($value, $conf, $endTag, $protected = 0) {
		// Return immediately if protected or no parameters
		if ($protected || !count($conf)) {
			return $value;
		}
		// OK then, begin processing for XHTML output:
		// STILL VERY EXPERIMENTAL!!
		if ($conf['xhtml']) {
			// Endtags are just set lowercase right away
			if ($endTag) {
				$value = strtolower($value);
			} elseif (substr($value, 0, 4) != '<!--') {
				// ... and comments are ignored.
				// Finding inner value with out < >
				$inValue = substr($value, 1, substr($value, -2) == '/>' ? -2 : -1);
				// Separate attributes and tagname
				list($tagName, $tagP) = preg_split('/\\s+/s', $inValue, 2);
				$tagName = strtolower($tagName);
				// Process attributes
				$tagAttrib = $this->get_tag_attributes($tagP);
				if (!strcmp($tagName, 'img') && !isset($tagAttrib[0]['alt'])) {
					$tagAttrib[0]['alt'] = '';
				}
				// Set alt attribute for all images (not XHTML though...)
				if (!strcmp($tagName, 'script') && !isset($tagAttrib[0]['type'])) {
					$tagAttrib[0]['type'] = 'text/javascript';
				}
				// Set type attribute for all script-tags
				$outA = array();
				foreach ($tagAttrib[0] as $attrib_name => $attrib_value) {
					// Set attributes: lowercase, always in quotes, with htmlspecialchars converted.
					$outA[] = $attrib_name . '="' . $this->bidir_htmlspecialchars($attrib_value, 2) . '"';
				}
				$newTag = '<' . trim(($tagName . ' ' . implode(' ', $outA)));
				// All tags that are standalone (not wrapping, not having endtags) should be ended with '/>'
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('img,br,hr,meta,link,base,area,input,param,col', $tagName) || substr($value, -2) == '/>') {
					$newTag .= ' />';
				} else {
					$newTag .= '>';
				}
				$value = $newTag;
			}
		}
		return $value;
	}

	/**
	 * Processing content between tags for HTML_cleaner
	 *
	 * @param string $value The value
	 * @param integer $dir Direction, either -1 or +1. 0 (zero) means no change to input value.
	 * @param mixed $conf Not used, ignore.
	 * @return string The processed value.
	 * @access private
	 * @todo Define visibility
	 */
	public function processContent($value, $dir, $conf) {
		if ($dir != 0) {
			$value = $this->bidir_htmlspecialchars($value, $dir);
		}
		return $value;
	}

}


?>
