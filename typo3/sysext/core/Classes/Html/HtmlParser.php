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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Functions for parsing HTML.
 * You are encouraged to use this class in your own applications
 */
class HtmlParser
{
    /**
     * @var array
     */
    protected $caseShift_cache = [];

    // Void elements that do not have closing tags, as defined by HTML5, except link element
    const VOID_ELEMENTS = 'area|base|br|col|command|embed|hr|img|input|keygen|meta|param|source|track|wbr';

    /************************************
     *
     * Parsing HTML code
     *
     ************************************/
    /**
     * Returns an array with the $content divided by tag-blocks specified with the list of tags, $tag
     * Even numbers in the array are outside the blocks, Odd numbers are block-content.
     * Use ->removeFirstAndLastTag() to process the content if needed.
     *
     * @param string $tag List of tags, comma separated.
     * @param string $content HTML-content
     * @param bool $eliminateExtraEndTags If set, excessive end tags are ignored - you should probably set this in most cases.
     * @return array Even numbers in the array are outside the blocks, Odd numbers are block-content.
     * @see splitTags()
     * @see removeFirstAndLastTag()
     */
    public function splitIntoBlock($tag, $content, $eliminateExtraEndTags = false)
    {
        $tags = array_unique(GeneralUtility::trimExplode(',', $tag, true));
        array_walk($tags, static function (&$tag) {
            $tag = preg_quote($tag, '/');
        });
        $regexStr = '/\\<\\/?(' . implode('|', $tags) . ')(\\s*\\>|\\s[^\\>]*\\>)/si';
        $parts = preg_split($regexStr, $content);
        if (empty($parts)) {
            return [];
        }
        $newParts = [];
        $pointer = strlen($parts[0]);
        $buffer = $parts[0];
        $nested = 0;
        reset($parts);
        // We skip the first element in foreach loop
        $partsSliced = array_slice($parts, 1, null, true);
        foreach ($partsSliced as $v) {
            $isEndTag = substr($content, $pointer, 2) === '</';
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
     * @param int $level Indent level
     * @return string Processed content
     * @see splitIntoBlock()
     */
    public function splitIntoBlockRecursiveProc($tag, $content, &$procObj, $callBackContent, $callBackTags, $level = 0)
    {
        $parts = $this->splitIntoBlock($tag, $content, true);
        foreach ($parts as $k => $v) {
            if ($k % 2) {
                $firstTagName = $this->getFirstTagName($v, true);
                $tagsArray = [];
                $tagsArray['tag_start'] = $this->getFirstTag($v);
                $tagsArray['tag_end'] = '</' . $firstTagName . '>';
                $tagsArray['tag_name'] = strtolower($firstTagName);
                $tagsArray['content'] = $this->splitIntoBlockRecursiveProc($tag, $this->removeFirstAndLastTag($v), $procObj, $callBackContent, $callBackTags, $level + 1);
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
     * Use ->removeFirstAndLastTag() to process the content if needed.
     *
     * @param string $tag List of tags
     * @param string $content HTML-content
     * @return array Even numbers in the array are outside the blocks, Odd numbers are block-content.
     * @see splitIntoBlock()
     * @see removeFirstAndLastTag()
     */
    public function splitTags($tag, $content)
    {
        $tags = GeneralUtility::trimExplode(',', $tag, true);
        array_walk($tags, static function (&$tag) {
            $tag = preg_quote($tag, '/');
        });
        $regexStr = '/\\<(' . implode('|', $tags) . ')(\\s[^>]*)?\\/?>/si';
        $parts = preg_split($regexStr, $content);
        if (empty($parts)) {
            return [];
        }
        $pointer = strlen($parts[0]);
        $newParts = [];
        $newParts[] = $parts[0];
        reset($parts);
        // We skip the first element in foreach loop
        $partsSliced = array_slice($parts, 1, null, true);
        foreach ($partsSliced as $v) {
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
     * Removes the first and last tag in the string
     * Anything before the first and after the last tags respectively is also removed
     *
     * @param string $str String to process
     * @return string
     */
    public function removeFirstAndLastTag($str)
    {
        $parser = SimpleParser::fromString($str);
        $first = $parser->getFirstNode(SimpleNode::TYPE_ELEMENT);
        $last = $parser->getLastNode(SimpleNode::TYPE_ELEMENT);
        if ($first === null || $first === $last) {
            return '';
        }
        $sequence = array_slice(
            $parser->getNodes(),
            $first->getIndex() + 1,
            $last->getIndex() - $first->getIndex() - 1
        );
        return implode('', array_map('strval', $sequence));
    }

    /**
     * Returns the first tag in $str
     * Actually everything from the beginning of the $str is returned, so you better make sure the tag is the first thing...
     *
     * @param string $str HTML string with tags
     * @return string
     */
    public function getFirstTag($str)
    {
        $parser = SimpleParser::fromString($str);
        $first = $parser->getFirstNode(SimpleNode::TYPE_ELEMENT);
        if ($first === null) {
            return '';
        }
        $sequence = array_slice(
            $parser->getNodes(),
            0,
            $first->getIndex() + 1
        );
        return implode('', array_map('strval', $sequence));
    }

    /**
     * Returns the NAME of the first tag in $str
     *
     * @param string $str HTML tag (The element name MUST be separated from the attributes by a space character! Just *whitespace* will not do)
     * @param bool $preserveCase If set, then the tag is NOT converted to uppercase by case is preserved.
     * @return string Tag name in upper case
     * @see getFirstTag()
     */
    public function getFirstTagName($str, $preserveCase = false)
    {
        $parser = SimpleParser::fromString($str);
        $elements = $parser->getNodes(SimpleNode::TYPE_ELEMENT);
        foreach ($elements as $element) {
            $name = $element->getElementName();
            if ($name === null) {
                continue;
            }
            return $preserveCase ? $name : strtoupper($name);
        }
        return '';
    }

    /**
     * Returns an array with all attributes as keys. Attributes are only lowercase a-z
     * If an attribute is empty (shorthand), then the value for the key is empty. You can check if it existed with isset()
     *
     * Compared to the method in GeneralUtility::get_tag_attributes this method also returns meta data about each
     * attribute, e.g. if it is a shorthand attribute, and what the quotation is. Also, since all attribute keys
     * are lower-cased, the meta information contains the original attribute name.
     *
     * @param string $tag Tag: $tag is either a whole tag (eg '<TAG OPTION ATTRIB=VALUE>') or the parameterlist (ex ' OPTION ATTRIB=VALUE>')
     * @param bool $deHSC If set, the attribute values are de-htmlspecialchar'ed. Should actually always be set!
     * @return array array(Tag attributes,Attribute meta-data)
     */
    public function get_tag_attributes($tag, $deHSC = false)
    {
        [$components, $metaC] = $this->split_tag_attributes($tag);
        // Attribute name is stored here
        $name = '';
        $valuemode = false;
        $attributes = [];
        $attributesMeta = [];
        if (is_array($components)) {
            foreach ($components as $key => $val) {
                // Only if $name is set (if there is an attribute, that waits for a value), that valuemode is enabled. This ensures that the attribute is assigned it's value
                if ($val !== '=') {
                    if ($valuemode) {
                        if ($name) {
                            $attributes[$name] = $deHSC ? htmlspecialchars_decode($val) : $val;
                            $attributesMeta[$name]['dashType'] = $metaC[$key];
                            $name = '';
                        }
                    } else {
                        if ($namekey = preg_replace('/[^[:alnum:]_\\:\\-]/', '', $val) ?? '') {
                            $name = strtolower((string)$namekey);
                            $attributesMeta[$name] = [];
                            $attributesMeta[$name]['origTag'] = $namekey;
                            $attributes[$name] = '';
                        }
                    }
                    $valuemode = false;
                } else {
                    $valuemode = true;
                }
            }
            return [$attributes, $attributesMeta];
        }
        return [null, null];
    }

    /**
     * Returns an array with the 'components' from an attribute list.
     * The result is normally analyzed by get_tag_attributes
     * Removes tag-name if found.
     *
     * The difference between this method and the one in GeneralUtility is that this method actually determines
     * more information on the attribute, e.g. if the value is enclosed by a " or ' character.
     * That's why this method returns two arrays, the "components" and the "meta-information" of the "components".
     *
     * @param string $tag The tag or attributes
     * @return array
     * @internal
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::split_tag_attributes()
     */
    public function split_tag_attributes($tag)
    {
        $matches = [];
        if (preg_match('/(\\<[^\\s]+\\s+)?(.*?)\\s*(\\>)?$/s', $tag, $matches) !== 1) {
            return [[], []];
        }
        $tag_tmp = $matches[2];
        $metaValue = [];
        $value = [];
        $matches = [];
        if (preg_match_all('/("[^"]*"|\'[^\']*\'|[^\\s"\'\\=]+|\\=)/s', $tag_tmp, $matches) > 0) {
            foreach ($matches[1] as $part) {
                $firstChar = $part[0];
                if ($firstChar === '"' || $firstChar === '\'') {
                    $metaValue[] = $firstChar;
                    $value[] = substr($part, 1, -1);
                } else {
                    $metaValue[] = '';
                    $value[] = $part;
                }
            }
        }
        return [$value, $metaValue];
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
     * 'rmTagIfNoAttrib' => '',	Boolean. If set, then the tag is removed if no attributes happened to be there.
     * 'nesting' => '',	Boolean/'global'. If set TRUE, then this tag must have starting and ending tags in the correct order. Any tags not in this order will be discarded. Thus '</B><B><I></B></I></B>' will be converted to '<B><I></B></I>'. Is the value 'global' then true nesting in relation to other tags marked for 'global' nesting control is preserved. This means that if <B> and <I> are set for global nesting then this string '</B><B><I></B></I></B>' is converted to '<B></B>'
     * )
     *
     * @param string $content Is the HTML-content being processed. This is also the result being returned.
     * @param array $tags Is an array where each key is a tagname in lowercase. Only tags present as keys in this array are preserved. The value of the key can be an array with a vast number of options to configure.
     * @param mixed $keepAll Boolean/'protect', if set, then all tags are kept regardless of tags present as keys in $tags-array. If 'protect' then the preserved tags have their <> converted to &lt; and &gt;
     * @param int $hSC Values -1,0,1,2: Set to zero= disabled, set to 1 then the content BETWEEN tags is htmlspecialchar()'ed, set to -1 its the opposite and set to 2 the content will be HSC'ed BUT with preservation for real entities (eg. "&amp;" or "&#234;")
     * @param array $addConfig Configuration array send along as $conf to the internal functions
     * @return string Processed HTML content
     */
    public function HTMLcleaner($content, $tags = [], $keepAll = 0, $hSC = 0, $addConfig = [])
    {
        $newContent = [];
        $tokArr = explode('<', $content);
        $newContent[] = $this->bidir_htmlspecialchars(current($tokArr), $hSC);
        // We skip the first element in foreach loop
        $tokArrSliced = array_slice($tokArr, 1, null, true);
        $c = 1;
        $tagRegister = [];
        $tagStack = [];
        $inComment = false;
        $inCdata = false;
        $skipTag = false;
        foreach ($tokArrSliced as $tok) {
            if ($inComment) {
                if (($eocPos = strpos($tok, '-->')) === false) {
                    // End of comment is not found in the token. Go further until end of comment is found in other tokens.
                    $newContent[$c++] = '<' . $tok;
                    continue;
                }
                // Comment ends in the middle of the token: add comment and proceed with rest of the token
                $newContent[$c++] = '<' . substr($tok, 0, $eocPos + 3);
                $tok = substr($tok, $eocPos + 3);
                $inComment = false;
                $skipTag = true;
            } elseif ($inCdata) {
                if (($eocPos = strpos($tok, '/*]]>*/')) === false) {
                    // End of comment is not found in the token. Go further until end of comment is found in other tokens.
                    $newContent[$c++] = '<' . $tok;
                    continue;
                }
                // Comment ends in the middle of the token: add comment and proceed with rest of the token
                $newContent[$c++] = '<' . substr($tok, 0, $eocPos + 10);
                $tok = substr($tok, $eocPos + 10);
                $inCdata = false;
                $skipTag = true;
            } elseif (strpos($tok, '!--') === 0) {
                if (($eocPos = strpos($tok, '-->')) === false) {
                    // Comment started in this token but it does end in the same token. Set a flag to skip till the end of comment
                    $newContent[$c++] = '<' . $tok;
                    $inComment = true;
                    continue;
                }
                // Start and end of comment are both in the current token. Add comment and proceed with rest of the token
                $newContent[$c++] = '<' . substr($tok, 0, $eocPos + 3);
                $tok = substr($tok, $eocPos + 3);
                $skipTag = true;
            } elseif (strpos($tok, '![CDATA[*/') === 0) {
                if (($eocPos = strpos($tok, '/*]]>*/')) === false) {
                    // Comment started in this token but it does end in the same token. Set a flag to skip till the end of comment
                    $newContent[$c++] = '<' . $tok;
                    $inCdata = true;
                    continue;
                }
                // Start and end of comment are both in the current token. Add comment and proceed with rest of the token
                $newContent[$c++] = '<' . substr($tok, 0, $eocPos + 10);
                $tok = substr($tok, $eocPos + 10);
                $skipTag = true;
            }
            $firstChar = $tok[0] ?? null;
            // It is a tag... (first char is a-z0-9 or /) (fixed 19/01 2004). This also avoids triggering on <?xml..> and <!DOCTYPE..>
            if (!$skipTag && preg_match('/[[:alnum:]\\/]/', (string)$firstChar) === 1) {
                $tagEnd = strpos($tok, '>');
                // If there is and end-bracket...	tagEnd can't be 0 as the first character can't be a >
                if ($tagEnd) {
                    $endTag = $firstChar === '/' ? 1 : 0;
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
                                if (isset($tags[$tagName]['overrideAttribs']) && (string)$tags[$tagName]['overrideAttribs'] !== '') {
                                    $tagParts[1] = $tags[$tagName]['overrideAttribs'];
                                }
                                // Allowed tags
                                if (isset($tags[$tagName]['allowedAttribs']) && (string)$tags[$tagName]['allowedAttribs'] !== '') {
                                    // No attribs allowed
                                    if ((string)$tags[$tagName]['allowedAttribs'] === '0') {
                                        $tagParts[1] = '';
                                    } elseif (isset($tagParts[1]) && trim($tagParts[1])) {
                                        $tagAttrib = $this->get_tag_attributes($tagParts[1]);
                                        $tagParts[1] = '';
                                        $newTagAttrib = [];
                                        $tList = (array)(
                                            $tags[$tagName]['_allowedAttribs']
                                            ?? GeneralUtility::trimExplode(',', strtolower($tags[$tagName]['allowedAttribs']), true)
                                        );
                                        foreach ($tList as $allowTag) {
                                            if (isset($tagAttrib[0][$allowTag])) {
                                                $newTagAttrib[$allowTag] = $tagAttrib[0][$allowTag];
                                            }
                                        }

                                        $tagParts[1] = $this->compileTagAttribs($newTagAttrib, $tagAttrib[1]);
                                    }
                                }
                                // Fixed attrib values
                                if (isset($tags[$tagName]['fixAttrib']) && is_array($tags[$tagName]['fixAttrib'])) {
                                    $tagAttrib = $this->get_tag_attributes($tagParts[1] ?? '');
                                    $tagParts[1] = '';
                                    foreach ($tags[$tagName]['fixAttrib'] as $attr => $params) {
                                        if (isset($params['set']) && $params['set'] !== '') {
                                            $tagAttrib[0][$attr] = $params['set'];
                                        }
                                        if (!empty($params['unset'])) {
                                            unset($tagAttrib[0][$attr]);
                                        }
                                        if (!empty($params['default']) && !isset($tagAttrib[0][$attr])) {
                                            $tagAttrib[0][$attr] = $params['default'];
                                        }
                                        if (($params['always'] ?? false) || isset($tagAttrib[0][$attr])) {
                                            if ($params['trim'] ?? false) {
                                                $tagAttrib[0][$attr] = trim($tagAttrib[0][$attr]);
                                            }
                                            if ($params['intval'] ?? false) {
                                                $tagAttrib[0][$attr] = (int)$tagAttrib[0][$attr];
                                            }
                                            if ($params['lower'] ?? false) {
                                                $tagAttrib[0][$attr] = strtolower($tagAttrib[0][$attr]);
                                            }
                                            if ($params['upper'] ?? false) {
                                                $tagAttrib[0][$attr] = strtoupper($tagAttrib[0][$attr]);
                                            }
                                            if ($params['range'] ?? false) {
                                                if (isset($params['range'][1])) {
                                                    $tagAttrib[0][$attr] = MathUtility::forceIntegerInRange($tagAttrib[0][$attr], (int)$params['range'][0], (int)$params['range'][1]);
                                                } else {
                                                    $tagAttrib[0][$attr] = MathUtility::forceIntegerInRange($tagAttrib[0][$attr], (int)$params['range'][0]);
                                                }
                                            }
                                            if (isset($params['list']) && is_array($params['list'])) {
                                                // For the class attribute, remove from the attribute value any class not in the list
                                                // Classes are case sensitive
                                                if ($attr === 'class') {
                                                    $newClasses = [];
                                                    $classes = GeneralUtility::trimExplode(' ', $tagAttrib[0][$attr] ?? '', true);
                                                    foreach ($classes as $class) {
                                                        if (in_array($class, $params['list'])) {
                                                            $newClasses[] = $class;
                                                        }
                                                    }
                                                    if (!empty($newClasses)) {
                                                        $tagAttrib[0][$attr] = implode(' ', $newClasses);
                                                    } else {
                                                        $tagAttrib[0][$attr] = $params['list'][0];
                                                    }
                                                } else {
                                                    if (!in_array($this->caseShift($tagAttrib[0][$attr] ?? '', $params['casesensitiveComp'] ?? false), (array)$this->caseShift($params['list'], $params['casesensitiveComp'], $tagName))) {
                                                        $tagAttrib[0][$attr] = $params['list'][0];
                                                    }
                                                }
                                            }
                                            if (
                                                (($params['removeIfFalse'] ?? false) && $params['removeIfFalse'] !== 'blank' && !$tagAttrib[0][$attr])
                                                || (($params['removeIfFalse'] ?? false) && $params['removeIfFalse'] === 'blank' && (string)$tagAttrib[0][$attr] === '')
                                            ) {
                                                unset($tagAttrib[0][$attr]);
                                            }
                                            if (
                                                (string)($params['removeIfEquals'] ?? '') !== ''
                                                && $this->caseShift($tagAttrib[0][$attr], (bool)($params['casesensitiveComp'] ?? false)) === $this->caseShift($params['removeIfEquals'], (bool)($params['casesensitiveComp'] ?? false))
                                            ) {
                                                unset($tagAttrib[0][$attr]);
                                            }
                                            if ($params['prefixLocalAnchors'] ?? false) {
                                                if ($tagAttrib[0][$attr][0] === '#') {
                                                    if ($params['prefixLocalAnchors'] == 2) {
                                                        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                                                        $prefix = $contentObjectRenderer->getUrlToCurrentLocation();
                                                    } else {
                                                        $prefix = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
                                                    }
                                                    $tagAttrib[0][$attr] = $prefix . $tagAttrib[0][$attr];
                                                }
                                            }
                                            if ($params['prefixRelPathWith'] ?? false) {
                                                $urlParts = parse_url($tagAttrib[0][$attr]);
                                                if (!$urlParts['scheme'] && $urlParts['path'][0] !== '/') {
                                                    // If it is NOT an absolute URL (by http: or starting "/")
                                                    $tagAttrib[0][$attr] = $params['prefixRelPathWith'] . $tagAttrib[0][$attr];
                                                }
                                            }
                                            if ($params['userFunc'] ?? false) {
                                                if (is_array($params['userFunc.'])) {
                                                    $params['userFunc.']['attributeValue'] = $tagAttrib[0][$attr];
                                                } else {
                                                    $params['userFunc.'] = $tagAttrib[0][$attr];
                                                }
                                                $tagAttrib[0][$attr] = GeneralUtility::callUserFunction($params['userFunc'], $params['userFunc.'], $this);
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
                            if (!empty($tags[$tagName]['protect'])) {
                                $lt = '&lt;';
                                $gt = '&gt;';
                            } else {
                                $lt = '<';
                                $gt = '>';
                            }
                            // Remapping tag name?
                            if (!empty($tags[$tagName]['remap'])) {
                                $tagParts[0] = $tags[$tagName]['remap'];
                            }
                            // rmTagIfNoAttrib
                            if ($endTag || empty($tags[$tagName]['rmTagIfNoAttrib']) || trim($tagParts[1] ?? '')) {
                                $setTag = true;
                                // Remove this closing tag if $tagName was among $TSconfig['removeTags']
                                if ($endTag
                                    && isset($tags[$tagName]['allowedAttribs']) && $tags[$tagName]['allowedAttribs'] === 0
                                    && isset($tags[$tagName]['rmTagIfNoAttrib']) && $tags[$tagName]['rmTagIfNoAttrib'] === 1
                                ) {
                                    $setTag = false;
                                }
                                if (isset($tags[$tagName]['nesting'])) {
                                    if (!isset($tagRegister[$tagName])) {
                                        $tagRegister[$tagName] = [];
                                    }
                                    if ($endTag) {
                                        $correctTag = true;
                                        if ($tags[$tagName]['nesting'] === 'global') {
                                            $lastEl = end($tagStack);
                                            if ($tagName !== $lastEl) {
                                                if (in_array($tagName, $tagStack, true)) {
                                                    while (!empty($tagStack) && $tagName !== $lastEl) {
                                                        $elPos = end($tagRegister[$lastEl]);
                                                        unset($newContent[$elPos]);
                                                        array_pop($tagRegister[$lastEl]);
                                                        array_pop($tagStack);
                                                        $lastEl = end($tagStack);
                                                    }
                                                } else {
                                                    // In this case the
                                                    $correctTag = false;
                                                }
                                            }
                                        }
                                        if (empty($tagRegister[$tagName]) || !$correctTag) {
                                            $setTag = false;
                                        } else {
                                            array_pop($tagRegister[$tagName]);
                                            if ($tags[$tagName]['nesting'] === 'global') {
                                                array_pop($tagStack);
                                            }
                                        }
                                    } else {
                                        $tagRegister[$tagName][] = $c;
                                        if ($tags[$tagName]['nesting'] === 'global') {
                                            $tagStack[] = $tagName;
                                        }
                                    }
                                }
                                if ($setTag) {
                                    // Setting the tag
                                    $newContent[$c++] = $lt . ($endTag ? '/' : '') . trim($tagParts[0] . ' ' . ($tagParts[1] ?? '')) . ($emptyTag ? ' /' : '') . $gt;
                                }
                            }
                        } else {
                            $newContent[$c++] = '<' . ($endTag ? '/' : '') . $tagContent . '>';
                        }
                    } elseif ($keepAll) {
                        // This is if the tag was not defined in the array for processing:
                        if ($keepAll === 'protect') {
                            $lt = '&lt;';
                            $gt = '&gt;';
                        } else {
                            $lt = '<';
                            $gt = '>';
                        }
                        $newContent[$c++] = $lt . ($endTag ? '/' : '') . $tagContent . $gt;
                    }
                    $newContent[$c++] = $this->bidir_htmlspecialchars(substr($tok, $tagEnd + 1), $hSC);
                } else {
                    $newContent[$c++] = $this->bidir_htmlspecialchars('<' . $tok, $hSC);
                }
            } else {
                $newContent[$c++] = $this->bidir_htmlspecialchars(($skipTag ? '' : '<') . $tok, $hSC);
                // It was not a tag anyways
                $skipTag = false;
            }
        }
        // Unsetting tags:
        foreach ($tagRegister as $tag => $positions) {
            foreach ($positions as $pKey) {
                unset($newContent[$pKey]);
            }
        }
        $newContent = implode('', $newContent);
        $newContent = $this->stripEmptyTagsIfConfigured($newContent, $addConfig);
        return $newContent;
    }

    /**
     * Converts htmlspecialchars forth ($dir=1) AND back ($dir=-1)
     *
     * @param string $value Input value
     * @param int $dir Direction: forth ($dir=1, dir=2 for preserving entities) AND back ($dir=-1)
     * @return string Output value
     */
    public function bidir_htmlspecialchars($value, $dir)
    {
        switch ((int)$dir) {
            case 1:
                return htmlspecialchars($value);
            case 2:
                return htmlspecialchars($value, ENT_COMPAT, 'UTF-8', false);
            case -1:
                return htmlspecialchars_decode($value);
            default:
                return $value;
        }
    }

    /**
     * Prefixes the relative paths of hrefs/src/action in the tags [td,table,body,img,input,form,link,script,a]
     * in the $content with the $main_prefix or and alternative given by $alternatives
     *
     * @param string $main_prefix Prefix string
     * @param string $content HTML content
     * @param array $alternatives Array with alternative prefixes for certain of the tags. key=>value pairs where the keys are the tag element names in uppercase
     * @param string $suffix Suffix string (put after the resource).
     * @return string Processed HTML content
     */
    public function prefixResourcePath($main_prefix, $content, $alternatives = [], $suffix = '')
    {
        $parts = $this->splitTags('embed,td,table,body,img,input,form,link,script,a,param,source', $content);
        foreach ($parts as $k => $v) {
            if ($k % 2) {
                $params = $this->get_tag_attributes($v);
                // Detect tag-ending so that it is re-applied correctly.
                $tagEnd = substr($v, -2) === '/>' ? ' />' : '>';
                // The 'name' of the first tag
                $firstTagName = $this->getFirstTagName($v);
                $prefixedRelPath = false;
                $prefix = $alternatives[strtoupper($firstTagName)] ?? $main_prefix;
                switch (strtolower($firstTagName)) {
                    case 'td':
                    case 'body':
                    case 'table':
                        if (isset($params[0]['background'])) {
                            $params[0]['background'] = $this->prefixRelPath($prefix, $params[0]['background'], $suffix);
                            $prefixedRelPath = true;
                        }
                        break;
                    case 'img':
                    case 'input':
                    case 'script':
                    case 'embed':
                        if (isset($params[0]['src'])) {
                            $params[0]['src'] = $this->prefixRelPath($prefix, $params[0]['src'], $suffix);
                            $prefixedRelPath = true;
                        }
                        break;
                    case 'link':
                    case 'a':
                        if (isset($params[0]['href'])) {
                            $params[0]['href'] = $this->prefixRelPath($prefix, $params[0]['href'], $suffix);
                            $prefixedRelPath = true;
                        }
                        break;
                    case 'form':
                        if (isset($params[0]['action'])) {
                            $params[0]['action'] = $this->prefixRelPath($prefix, $params[0]['action'], $suffix);
                            $prefixedRelPath = true;
                        }
                        break;
                    case 'param':
                        if (isset($params[0]['name']) && $params[0]['name'] === 'movie' && isset($params[0]['value'])) {
                            $params[0]['value'] = $this->prefixRelPath($prefix, $params[0]['value'], $suffix);
                            $prefixedRelPath = true;
                        }
                        break;
                    case 'source':
                        if (isset($params[0]['srcset'])) {
                            $srcsetImagePaths = GeneralUtility::trimExplode(',', $params[0]['srcset']);
                            for ($i = 0; $i < count($srcsetImagePaths); $i++) {
                                $srcsetImagePaths[$i] = $this->prefixRelPath($prefix, $srcsetImagePaths[$i], $suffix);
                            }
                            $params[0]['srcset'] = implode(', ', $srcsetImagePaths);
                            $prefixedRelPath = true;
                        }
                        break;
                }
                if ($prefixedRelPath) {
                    $tagParts = preg_split('/\\s+/s', $v, 2);
                    $tagParts[1] = $this->compileTagAttribs($params[0], $params[1]);
                    $parts[$k] = '<' . trim(strtolower($firstTagName) . ' ' . $tagParts[1]) . $tagEnd;
                }
            }
        }
        $content = implode('', $parts);
        // Fix <style> section:
        $prefix = $alternatives['style'] ?? $main_prefix;
        if ((string)$prefix !== '') {
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
     * @internal
     */
    public function prefixRelPath($prefix, $srcVal, $suffix = '')
    {
        // Only prefix if it's not an absolute URL or
        // only a link to a section within the page.
        if ($srcVal[0] !== '/' && $srcVal[0] !== '#') {
            $urlParts = parse_url($srcVal);
            // Only prefix URLs without a scheme
            if (!isset($urlParts['scheme'])) {
                $srcVal = $prefix . $srcVal . $suffix;
            }
        }
        return $srcVal;
    }

    /**
     * Internal function for case shifting of a string or whole array
     *
     * @param mixed $str Input string/array
     * @param bool $caseSensitiveComparison If this value is FALSE, the string is returned in uppercase
     * @param string $cacheKey Key string used for internal caching of the results. Could be an MD5 hash of the serialized version of the input $str if that is an array.
     * @return array|string Output string, processed
     * @internal
     */
    public function caseShift($str, $caseSensitiveComparison, $cacheKey = '')
    {
        if ($caseSensitiveComparison) {
            return $str;
        }
        if (is_array($str)) {
            // Fetch from runlevel cache
            if ($cacheKey && isset($this->caseShift_cache[$cacheKey])) {
                $str = $this->caseShift_cache[$cacheKey];
            } else {
                array_walk($str, static function (&$value) {
                    $value = strtoupper($value);
                });
                if ($cacheKey) {
                    $this->caseShift_cache[$cacheKey] = $str;
                }
            }
        } else {
            $str = strtoupper($str);
        }
        return $str;
    }

    /**
     * Compiling an array with tag attributes into a string
     *
     * @param array $tagAttrib Tag attributes
     * @param array $meta Meta information about these attributes (like if they were quoted)
     * @return string Imploded attributes, eg: 'attribute="value" attrib2="value2"'
     * @internal
     */
    public function compileTagAttribs($tagAttrib, $meta = [])
    {
        $accu = [];
        foreach ($tagAttrib as $k => $v) {
            $attr = $meta[$k]['origTag'] ?? $k;
            if (strcmp($v, '') || isset($meta[$k]['dashType'])) {
                $dash = $meta[$k]['dashType'] ?? (MathUtility::canBeInterpretedAsInteger($v) ? '' : '"');
                $attr .= '=' . $dash . $v . $dash;
            }
            $accu[] = $attr;
        }
        return implode(' ', $accu);
    }

    /**
     * Converts TSconfig into an array for the HTMLcleaner function.
     *
     * @param array $TSconfig TSconfig for HTMLcleaner
     * @param array $keepTags Array of tags to keep (?)
     * @return array
     * @internal
     */
    public function HTMLparserConfig($TSconfig, $keepTags = [])
    {
        // Allow tags (base list, merged with incoming array)
        $alTags = array_flip(GeneralUtility::trimExplode(',', strtolower($TSconfig['allowTags'] ?? ''), true));
        $keepTags = array_merge($alTags, $keepTags);
        // Set config properties.
        if (isset($TSconfig['tags.']) && is_array($TSconfig['tags.'])) {
            foreach ($TSconfig['tags.'] as $key => $tagC) {
                if (!is_array($tagC) && $key == strtolower($key)) {
                    if ((string)$tagC === '0') {
                        unset($keepTags[$key]);
                    }
                    if ((string)$tagC === '1' && !isset($keepTags[$key])) {
                        $keepTags[$key] = 1;
                    }
                }
            }
            foreach ($TSconfig['tags.'] as $key => $tagC) {
                if (is_array($tagC) && $key == strtolower($key)) {
                    $key = substr($key, 0, -1);
                    if (!is_array($keepTags[$key] ?? null)) {
                        $keepTags[$key] = [];
                    }
                    if (isset($tagC['fixAttrib.']) && is_array($tagC['fixAttrib.'])) {
                        foreach ($tagC['fixAttrib.'] as $atName => $atConfig) {
                            if (is_array($atConfig)) {
                                $atName = substr($atName, 0, -1);
                                if (!is_array($keepTags[$key]['fixAttrib'][$atName] ?? null)) {
                                    $keepTags[$key]['fixAttrib'][$atName] = [];
                                }
                                $keepTags[$key]['fixAttrib'][$atName] = array_merge($keepTags[$key]['fixAttrib'][$atName], $atConfig);
                                if ((string)($keepTags[$key]['fixAttrib'][$atName]['range'] ?? '') !== '') {
                                    $keepTags[$key]['fixAttrib'][$atName]['range'] = GeneralUtility::trimExplode(',', $keepTags[$key]['fixAttrib'][$atName]['range']);
                                }
                                if ((string)($keepTags[$key]['fixAttrib'][$atName]['list'] ?? '') !== '') {
                                    $keepTags[$key]['fixAttrib'][$atName]['list'] = GeneralUtility::trimExplode(',', $keepTags[$key]['fixAttrib'][$atName]['list']);
                                }
                            }
                        }
                    }
                    unset($tagC['fixAttrib.'], $tagC['fixAttrib']);
                    if (!empty($tagC['rmTagIfNoAttrib']) && empty($tagC['nesting'])) {
                        $tagC['nesting'] = 1;
                    }
                    $keepTags[$key] = array_merge($keepTags[$key], $tagC);
                }
            }
        }
        // LocalNesting
        if (!empty($TSconfig['localNesting'])) {
            $lN = GeneralUtility::trimExplode(',', strtolower($TSconfig['localNesting']), true);
            foreach ($lN as $tn) {
                if (isset($keepTags[$tn])) {
                    if (!is_array($keepTags[$tn])) {
                        $keepTags[$tn] = [];
                    }
                    $keepTags[$tn]['nesting'] = 1;
                }
            }
        }
        if (!empty($TSconfig['globalNesting'])) {
            $lN = GeneralUtility::trimExplode(',', strtolower($TSconfig['globalNesting']), true);
            foreach ($lN as $tn) {
                if (isset($keepTags[$tn])) {
                    if (!is_array($keepTags[$tn])) {
                        $keepTags[$tn] = [];
                    }
                    $keepTags[$tn]['nesting'] = 'global';
                }
            }
        }
        if (!empty($TSconfig['rmTagIfNoAttrib'])) {
            $lN = GeneralUtility::trimExplode(',', strtolower($TSconfig['rmTagIfNoAttrib']), true);
            foreach ($lN as $tn) {
                if (isset($keepTags[$tn])) {
                    if (!is_array($keepTags[$tn])) {
                        $keepTags[$tn] = [];
                    }
                    $keepTags[$tn]['rmTagIfNoAttrib'] = 1;
                    if (empty($keepTags[$tn]['nesting'])) {
                        $keepTags[$tn]['nesting'] = 1;
                    }
                }
            }
        }
        if (!empty($TSconfig['noAttrib'])) {
            $lN = GeneralUtility::trimExplode(',', strtolower($TSconfig['noAttrib']), true);
            foreach ($lN as $tn) {
                if (isset($keepTags[$tn])) {
                    if (!is_array($keepTags[$tn])) {
                        $keepTags[$tn] = [];
                    }
                    $keepTags[$tn]['allowedAttribs'] = 0;
                }
            }
        }
        if (!empty($TSconfig['removeTags'])) {
            $lN = GeneralUtility::trimExplode(',', strtolower($TSconfig['removeTags']), true);
            foreach ($lN as $tn) {
                $keepTags[$tn] = [];
                $keepTags[$tn]['allowedAttribs'] = 0;
                $keepTags[$tn]['rmTagIfNoAttrib'] = 1;
            }
        }
        // Create additional configuration:
        $addConfig = [];
        if (isset($TSconfig['stripEmptyTags'])) {
            $addConfig['stripEmptyTags'] = $TSconfig['stripEmptyTags'];
            if (isset($TSconfig['stripEmptyTags.'])) {
                $addConfig['stripEmptyTags.'] = $TSconfig['stripEmptyTags.'];
            }
        }
        return [
            $keepTags,
            '' . ($TSconfig['keepNonMatchedTags'] ?? ''),
            (int)($TSconfig['htmlSpecialChars'] ?? 0),
            $addConfig,
        ];
    }

    /**
     * Strips empty tags from HTML.
     *
     * @param string $content The content to be stripped of empty tags
     * @param string $tagList The comma separated list of tags to be stripped.
     *                        If empty, all empty tags will be stripped
     * @param bool $treatNonBreakingSpaceAsEmpty If TRUE tags containing only &nbsp; entities will be treated as empty.
     * @param bool $keepTags If true, the provided tags will be kept instead of stripped.
     * @return string the stripped content
     */
    public function stripEmptyTags($content, $tagList = '', $treatNonBreakingSpaceAsEmpty = false, $keepTags = false)
    {
        if (!empty($tagList)) {
            $tagRegEx = implode('|', GeneralUtility::trimExplode(',', $tagList, true));
            if ($keepTags) {
                $tagRegEx = '(?!' . $tagRegEx . ')[^ >]+';
            }
        } else {
            $tagRegEx = '[^ >]+'; // all characters until you reach a > or space;
        }
        $count = 1;
        $nbspRegex = $treatNonBreakingSpaceAsEmpty ? '|(&nbsp;)' : '';
        $finalRegex = sprintf('/<(%s)[^>]*>( %s)*<\/\\1[^>]*>/i', $tagRegEx, $nbspRegex);
        while ($count !== 0) {
            $content = preg_replace($finalRegex, '', $content, -1, $count) ?? $content;
        }
        return $content;
    }

    /**
     * Strips the configured empty tags from the HMTL code.
     *
     * @param string $value
     * @param array $configuration
     * @return string
     */
    protected function stripEmptyTagsIfConfigured($value, $configuration)
    {
        if (empty($configuration['stripEmptyTags'])) {
            return $value;
        }

        $tags = null;
        $keepTags = false;
        if (!empty($configuration['stripEmptyTags.']['keepTags'])) {
            $tags = $configuration['stripEmptyTags.']['keepTags'];
            $keepTags = true;
        } elseif (!empty($configuration['stripEmptyTags.']['tags'])) {
            $tags = $configuration['stripEmptyTags.']['tags'];
        }

        $treatNonBreakingSpaceAsEmpty = !empty($configuration['stripEmptyTags.']['treatNonBreakingSpaceAsEmpty']);

        return $this->stripEmptyTags($value, $tags, $treatNonBreakingSpaceAsEmpty, $keepTags);
    }
}
