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

    protected const TAGS = 'a|abbr|address|area|article|aside|audio|b|bdi|bdo|blockquote|body|br|button|caption|cite|code|col|colgroup|data|datalist|dd|del|dfn|div|dl|dt|em|embed|fieldset|figcaption|figure|font|footer|form|h1|h2|h3|h4|h5|h6|header|hr|i|iframe|img|input|ins|kbd|keygen|label|legend|li|link|main|map|mark|meter|nav|object|ol|optgroup|option|output|p|param|pre|progress|q|rb|rp|rt|rtc|ruby|s|samp|section|select|small|source|span|strong|sub|sup|table|tbody|td|textarea|tfoot|th|thead|time|tr|track|u|ul|ut|var|video|wbr';

    protected const TAGS_REG_EXP = '
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
            </?(?:%s)+			# opening tag (\'<tag\') or closing tag (\'</tag\')
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

        $sections = $this->splitContentIntoSections($content, $cropFromRight);

        // Only crop text sections (chars of tag-blocks are not counted).
        $strLengthOfAllPrevTextSections = 0;

        // This is the offset of the content item which was cropped.
        $croppedOffset = null;
        $amountOfSections = count($sections);

        // For cropSectionToNextSpace we need a collection of all processed text sections
        $processedTextSectionsForCropping = [];

        for ($offset = 0; $offset < $amountOfSections; $offset++) {
            if ($this->isTextSection($offset)) {
                $contentOfCurrentSection = $sections[$offset];
                $strLengthOfCurrentSection = mb_strlen(
                    html_entity_decode($contentOfCurrentSection, ENT_COMPAT, 'UTF-8'),
                    'utf-8'
                );

                if ($strLengthOfAllPrevTextSections + $strLengthOfCurrentSection > abs($numberOfChars)) {
                    $croppedOffset = $offset;
                    $cropPosition = $this->getCropPosition(
                        $contentOfCurrentSection,
                        $numberOfChars,
                        $strLengthOfAllPrevTextSections,
                        $cropFromRight
                    );

                    // Main cropping. Note the +1 and -1. These are there to be able to
                    // check for space characters later on.
                    $contentOfCurrentSection = !$cropFromRight
                        ? mb_substr($contentOfCurrentSection, 0, $cropPosition + 1)
                        : mb_substr($contentOfCurrentSection, -$cropPosition - 1);

                    $contentOfCurrentSection = $this->cropSectionToNextSpace(
                        $contentOfCurrentSection,
                        $processedTextSectionsForCropping,
                        $cropToSpace,
                        $cropFromRight
                    );

                    $sections[$offset] = $contentOfCurrentSection;
                    break;
                }
                $strLengthOfAllPrevTextSections += $strLengthOfCurrentSection;
                if ($contentOfCurrentSection !== '') {
                    $processedTextSectionsForCropping[] = $contentOfCurrentSection;
                }
            }
        }

        $sections = $this->closeCroppedTags($sections, $croppedOffset, $numberOfChars, $replacementForEllipsis);

        // Reverse array once again if we are cropping from the end.
        if ($numberOfChars < 0) {
            $sections = array_reverse($sections);
        }

        return implode('', $sections);
    }

    /**
     * Split $content into an array(even items in the array are outside the tags, odd numbers are tag-blocks).
     */
    protected function splitContentIntoSections(string $content, bool $cropFromRight): array
    {
        $splitPattern = sprintf(
            self::TAGS_REG_EXP,
            self::TAGS
        );

        $sections = preg_split(
            '%' . $splitPattern . '%xs',
            $content,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if ($sections === false) {
            $this->logger->debug('Unable to split "{content}" into tags.', ['content' => $content]);
            $sections = [];
        }

        // Reverse array if we are cropping from right.
        if ($cropFromRight) {
            $sections = array_reverse($sections);
        }

        return $sections;
    }

    protected function getCropPosition(
        string $contentOfCurrentSection,
        int $numberOfChars,
        int $strLengthOfAllPrevTextSections,
        bool $cropFromRight
    ): int {
        $cropPosition = abs($numberOfChars) - $strLengthOfAllPrevTextSections;

        // The snippet "&[^&\s;]{2,8};" in the RegEx below represents entities.
        $entityPattern = '/&[^&\\s;]{2,8};/';
        preg_match_all($entityPattern, $contentOfCurrentSection, $matches);
        $entityMatches = $matches[0];

        // If we have found any html entities, these should be counted as 1 character.
        // Strategy is to replace all found entities with an arbitrary character ($)
        // and use this new string to count offsets.
        if ($entityMatches !== []) {
            $escapedContent = str_replace('$', ' ', $contentOfCurrentSection);
            $replacedContent = preg_replace($entityPattern, '$', $escapedContent, -1);
            $croppedContent = !$cropFromRight
                ? mb_substr($replacedContent, 0, $cropPosition)
                : mb_substr($replacedContent, $numberOfChars, $cropPosition);

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

        return $cropPosition;
    }

    protected function closeCroppedTags(
        array $sections,
        ?int $croppedOffset,
        int $numberOfChars,
        string $replacementForEllipsis
    ): array {
        $closingTags = [];
        if ($croppedOffset !== null) {
            $openingTagRegEx = '#^<(\\w+)(?:\\s|>)#';
            $closingTagRegEx = '#^</(\\w+)(?:\\s|>)#';
            for ($offset = $croppedOffset - 1; $offset >= 0; $offset = $offset - 2) {
                if (str_ends_with($sections[$offset], '/>')) {
                    // Ignore empty element tags (e.g. <br />).
                    continue;
                }

                preg_match($numberOfChars < 0 ? $closingTagRegEx : $openingTagRegEx, $sections[$offset], $matches);
                $tagName = $matches[1] ?? null;
                if ($tagName !== null) {
                    // Seek for the closing (or opening) tag.
                    $amountOfSections = count($sections);
                    for ($seekingOffset = $offset + 2; $seekingOffset < $amountOfSections; $seekingOffset = $seekingOffset + 2) {
                        preg_match($numberOfChars < 0 ? $openingTagRegEx : $closingTagRegEx, $sections[$seekingOffset], $matches);
                        $seekingTagName = $matches[1] ?? null;
                        if ($tagName === $seekingTagName) {
                            // We found a matching tag.
                            // Add closing tag only if it occurs after the cropped content item.
                            if ($seekingOffset > $croppedOffset) {
                                $closingTags[] = $sections[$seekingOffset];
                            }
                            break;
                        }
                    }
                }
            }
            // Drop the cropped items of the content array. The $closingTags will be added later on again.
            array_splice($sections, $croppedOffset + 1);
        }

        return array_merge($sections, [
            $croppedOffset !== null ? trim($replacementForEllipsis) : '',
        ], $closingTags);
    }

    protected function cropSectionToNextSpace(
        string $contentOfCurrentSection,
        array $processedTextSectionsForCropping,
        bool $cropToSpace,
        bool $cropFromRight
    ): string {
        // Crop to space means, we ensure to crop before (or after) a space.
        // If there are no spaces, this option has no effect.
        $cropToSpaceApplied = false;
        if ($cropToSpace) {
            $exploded = explode(' ', $contentOfCurrentSection);
            if (!$cropFromRight) {
                array_unshift(
                    $exploded,
                    ...$processedTextSectionsForCropping
                );
            } else {
                array_push(
                    $exploded,
                    ...$processedTextSectionsForCropping
                );
            }

            if (count($exploded) > 1) {
                if (!$cropFromRight && $exploded[count($exploded) - 1] !== ' ') {
                    array_pop($exploded);
                    $cropToSpaceApplied = true;
                } elseif ($exploded[0] !== ' ') {
                    array_shift($exploded);
                    $cropToSpaceApplied = true;
                }
            }
            $exploded = array_diff($exploded, $processedTextSectionsForCropping);
            $contentOfCurrentSection = implode(' ', $exploded);
        }

        // Only remove the extra character again, if crop2space did not apply anything.
        if (!$cropToSpaceApplied) {
            $contentOfCurrentSection = !$cropFromRight
                ? mb_substr($contentOfCurrentSection, 0, -1)
                : mb_substr($contentOfCurrentSection, 1);
        }

        return $contentOfCurrentSection;
    }

    protected function isTextSection(int $offset): bool
    {
        return $offset % 2 === 0;
    }
}
