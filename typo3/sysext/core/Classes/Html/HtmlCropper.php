<?php

declare(strict_types=1);

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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class HtmlCropper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Implements "cropHTML" which is a modified "substr" function allowing to limit a string length to a certain number
     * of chars (from either start or end of string) and having a pre/postfix applied if the string really was cropped.
     *
     * @param string $content The string to perform the operation on
     * @param int $numberOfChars Max number of chars of the string. Negative value means cropping from end of string.
     * @param string $replacementForEllipsis  The pre/postfix string to apply if cropping occurs.
     * @param bool $cropToSpace If true then crop will be applied at nearest space.
     * @return string The processed input value.
     */
    public function crop(string $content, int $numberOfChars, string $replacementForEllipsis, bool $cropToSpace): string
    {
        $cropFromRight = $numberOfChars < 0;
        $absChars = abs($numberOfChars);
        $replacementForEllipsis = trim($replacementForEllipsis);
        // Split $content into an array(even items in the array are outside the tags, odd numbers are tag-blocks).
        $tags = 'a|abbr|address|area|article|aside|audio|b|bdi|bdo|blockquote|body|br|button|caption|cite|code|col|colgroup|data|datalist|dd|del|dfn|div|dl|dt|em|embed|fieldset|figcaption|figure|font|footer|form|h1|h2|h3|h4|h5|h6|header|hr|i|iframe|img|input|ins|kbd|keygen|label|legend|li|link|main|map|mark|meter|nav|object|ol|optgroup|option|output|p|param|pre|progress|q|rb|rp|rt|rtc|ruby|s|samp|section|select|small|source|span|strong|sub|sup|table|tbody|td|textarea|tfoot|th|thead|time|tr|track|u|ul|ut|var|video|wbr';
        $tagsRegEx = '
			(
				(?:
					<!--.*?-->					# a comment
					|
					<canvas[^>]*>.*?</canvas>   # a canvas tag
					|
					<script[^>]*>.*?</script>   # a script tag
					|
					<noscript[^>]*>.*?</noscript> # a noscript tag
					|
					<template[^>]*>.*?</template> # a template tag
				)
				|
				</?(?:' . $tags . ')+			# opening tag (\'<tag\') or closing tag (\'</tag\')
				(?:
					(?:
						(?:
							\\s+\\w[\\w-]*		# EITHER spaces, followed by attribute names
							(?:
								\\s*=?\\s*		# equals
								(?>
									".*?"		# attribute values in double-quotes
									|
									\'.*?\'		# attribute values in single-quotes
									|
									[^\'">\\s]+	# plain attribute values
								)
							)?
						)
						|						# OR a single dash (for TYPO3 link tag)
						(?:
							\\s+-
						)
					)+\\s*
					|							# OR only spaces
					\\s*
				)
				/?>								# closing the tag with \'>\' or \'/>\'
			)';
        $splittedContent = preg_split('%' . $tagsRegEx . '%xs', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($splittedContent === false) {
            $this->logger->debug('Unable to split "{content}" into tags.', ['content' => $content]);
            $splittedContent = [];
        }

        // Reverse array if we are cropping from right.
        if ($cropFromRight) {
            $splittedContent = array_reverse($splittedContent);
        }
        // Crop the text (chars of tag-blocks are not counted).
        $strLen = 0;
        // This is the offset of the content item which was cropped.
        $croppedOffset = null;
        $countSplittedContent = count($splittedContent);
        for ($offset = 0; $offset < $countSplittedContent; $offset++) {
            if ($offset % 2 === 0) {
                $fullTempContent = $splittedContent[$offset];
                $thisStrLen = mb_strlen(html_entity_decode($fullTempContent, ENT_COMPAT, 'UTF-8'), 'utf-8');
                if ($strLen + $thisStrLen > $absChars) {
                    $tempProcessedContent = '';
                    $croppedOffset = $offset;
                    $cropPosition = $absChars - $strLen;
                    // The snippet "&[^&\s;]{2,8};" in the RegEx below represents entities.
                    $entityPattern = '/&[^&\\s;]{2,8};/';
                    preg_match_all($entityPattern, $fullTempContent, $matches);
                    $entityMatches = $matches[0];

                    // If we have found any html entities, these should be counted as 1 character.
                    // Strategy is to replace all found entities with an arbitrary character ($)
                    // and use this new string to count offsets.
                    if (($entityMatches ?? []) !== []) {
                        $escapedContent = str_replace('$', ' ', $fullTempContent);
                        $replacedContent = preg_replace($entityPattern, '$', $escapedContent, -1, $count);
                        $croppedContent = !$cropFromRight ? mb_substr($replacedContent, 0, $cropPosition) : mb_substr($replacedContent, $numberOfChars, $cropPosition);

                        // In case of negative offsets, we need to reverse everything.
                        // Because the string is cropped from behind, the entities
                        // have to be replaced in reverse, too.
                        if ($cropFromRight) {
                            $croppedContent = strrev($croppedContent);
                            $entityMatches = array_reverse($entityMatches);
                        }
                        foreach ($entityMatches as $entity) {
                            $croppedContent = preg_replace('/\$/', $entity, $croppedContent, 1);
                        }
                        $cropPosition = mb_strlen($croppedContent);
                    }

                    // Main cropping. Note the +1 and -1. These are there to be able to
                    // check for space characters later on.
                    $fullTempContent = !$cropFromRight ? mb_substr($fullTempContent, 0, $cropPosition + 1) : mb_substr($fullTempContent, -$cropPosition - 1);

                    // Crop to space means, we ensure to crop before (or after) a space.
                    // If there are no spaces, this option has no effect.
                    $cropToSpaceApplied = false;
                    if ($cropToSpace) {
                        $exploded = explode(' ', $fullTempContent);
                        if (count($exploded) > 1) {
                            if (!$cropFromRight && $exploded[count($exploded) - 1] !== ' ') {
                                array_pop($exploded);
                                $cropToSpaceApplied = true;
                            } elseif ($exploded[0] !== ' ') {
                                array_shift($exploded);
                                $cropToSpaceApplied = true;
                            }
                        }
                        $fullTempContent = implode(' ', $exploded);
                    }

                    // Only remove the extra character again, if crop2space did not apply anything.
                    if (!$cropToSpaceApplied) {
                        $fullTempContent = !$cropFromRight ? mb_substr($fullTempContent, 0, -1) : mb_substr($fullTempContent, 1);
                    }

                    $splittedContent[$offset] = $fullTempContent;
                    break;
                }
                $strLen += $thisStrLen;
            }
        }
        // Close cropped tags.
        $closingTags = [];
        if ($croppedOffset !== null) {
            $openingTagRegEx = '#^<(\\w+)(?:\\s|>)#';
            $closingTagRegEx = '#^</(\\w+)(?:\\s|>)#';
            for ($offset = $croppedOffset - 1; $offset >= 0; $offset = $offset - 2) {
                if (substr($splittedContent[$offset], -2) === '/>') {
                    // Ignore empty element tags (e.g. <br />).
                    continue;
                }
                preg_match($numberOfChars < 0 ? $closingTagRegEx : $openingTagRegEx, $splittedContent[$offset], $matches);
                $tagName = $matches[1] ?? null;
                if ($tagName !== null) {
                    // Seek for the closing (or opening) tag.
                    $countSplittedContent = count($splittedContent);
                    for ($seekingOffset = $offset + 2; $seekingOffset < $countSplittedContent; $seekingOffset = $seekingOffset + 2) {
                        preg_match($numberOfChars < 0 ? $openingTagRegEx : $closingTagRegEx, $splittedContent[$seekingOffset], $matches);
                        $seekingTagName = $matches[1] ?? null;
                        if ($tagName === $seekingTagName) {
                            // We found a matching tag.
                            // Add closing tag only if it occurs after the cropped content item.
                            if ($seekingOffset > $croppedOffset) {
                                $closingTags[] = $splittedContent[$seekingOffset];
                            }
                            break;
                        }
                    }
                }
            }
            // Drop the cropped items of the content array. The $closingTags will be added later on again.
            array_splice($splittedContent, $croppedOffset + 1);
        }
        $splittedContent = array_merge($splittedContent, [
            $croppedOffset !== null ? $replacementForEllipsis : '',
        ], $closingTags);
        // Reverse array once again if we are cropping from the end.
        if ($numberOfChars < 0) {
            $splittedContent = array_reverse($splittedContent);
        }
        return implode('', $splittedContent);
    }
}
