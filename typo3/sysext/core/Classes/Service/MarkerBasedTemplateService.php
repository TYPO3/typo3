<?php
namespace TYPO3\CMS\Core\Service;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper functionality for subparts and marker substitution
 * ###MYMARKER###
 */
class MarkerBasedTemplateService
{
    /**
     * Returns the first subpart encapsulated in the marker, $marker
     * (possibly present in $content as a HTML comment)
     *
     * @param string $content Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
     * @param string $marker Marker string, eg. "###CONTENT_PART###
     *
     * @return string
     */
    public function getSubpart($content, $marker)
    {
        $start = strpos($content, $marker);
        if ($start === false) {
            return '';
        }
        $start += strlen($marker);
        $stop = strpos($content, $marker, $start);
        // Q: What shall get returned if no stop marker is given
        // Everything till the end or nothing?
        if ($stop === false) {
            return '';
        }
        $content = substr($content, $start, $stop - $start);
        $matches = [];
        if (preg_match('/^([^\\<]*\\-\\-\\>)(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $content, $matches) === 1) {
            return $matches[2];
        }
        // Resetting $matches
        $matches = [];
        if (preg_match('/(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $content, $matches) === 1) {
            return $matches[1];
        }
        // Resetting $matches
        $matches = [];
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
     * @param bool $recursive If $recursive is set, the function calls itself with the content set to the remaining part of the content after the second marker. This means that proceding subparts are ALSO substituted!
     * @param bool $keepMarker If set, the marker around the subpart is not removed, but kept in the output
     *
     * @return string Processed input content
     */
    public function substituteSubpart($content, $marker, $subpartContent, $recursive = true, $keepMarker = false)
    {
        $start = strpos($content, $marker);
        if ($start === false) {
            return $content;
        }
        $startAM = $start + strlen($marker);
        $stop = strpos($content, $marker, $startAM);
        if ($stop === false) {
            return $content;
        }
        $stopAM = $stop + strlen($marker);
        $before = substr($content, 0, $start);
        $after = substr($content, $stopAM);
        $between = substr($content, $startAM, $stop - $startAM);
        if ($recursive) {
            $after = $this->substituteSubpart($after, $marker, $subpartContent, $recursive, $keepMarker);
        }
        if ($keepMarker) {
            $matches = [];
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
            $matches = [];
            if (preg_match('/^(.*)\\<\\!\\-\\-[^\\>]*$/s', $before, $matches) === 1) {
                $before = $matches[1];
            }
            if (is_array($subpartContent)) {
                $matches = [];
                if (preg_match('/^([^\\<]*\\-\\-\\>)(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $between, $matches) === 1) {
                    $between = $matches[2];
                } elseif (preg_match('/^(.*)(\\<\\!\\-\\-[^\\>]*)$/s', $between, $matches) === 1) {
                    $between = $matches[1];
                } elseif (preg_match('/^([^\\<]*\\-\\-\\>)(.*)$/s', $between, $matches) === 1) {
                    $between = $matches[2];
                }
            }
            $matches = [];
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
     *
     * @return string The processed HTML content string.
     */
    public function substituteSubpartArray($content, array $subpartsContent)
    {
        foreach ($subpartsContent as $subpartMarker => $subpartContent) {
            $content = $this->substituteSubpart($content, $subpartMarker, $subpartContent);
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
     *
     * @return string The processed HTML content string.
     * @see substituteSubpart()
     */
    public function substituteMarker($content, $marker, $markContent)
    {
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
     * @param bool $uppercase If set, all marker string substitution is done with upper-case markers.
     * @param bool $deleteUnused If set, all unused marker are deleted.
     *
     * @return string The processed output stream
     * @see substituteMarker(), substituteMarkerInObject(), TEMPLATE()
     */
    public function substituteMarkerArray($content, $markContentArray, $wrap = '', $uppercase = false, $deleteUnused = false)
    {
        if (is_array($markContentArray)) {
            $wrapArr = GeneralUtility::trimExplode('|', $wrap);
            $search = [];
            $replace = [];
            foreach ($markContentArray as $marker => $markContent) {
                if ($uppercase) {
                    // use strtr instead of strtoupper to avoid locale problems with Turkish
                    $marker = strtr($marker, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
                }
                if (!empty($wrapArr)) {
                    $marker = $wrapArr[0] . $marker . $wrapArr[1];
                }
                $search[] = $marker;
                $replace[] = $markContent;
            }
            $content = str_replace($search, $replace, $content);
            unset($search, $replace);
            if ($deleteUnused) {
                if (empty($wrap)) {
                    $wrapArr = ['###', '###'];
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
     *    '###SINGLEMARKER1###' => 'value 1',
     *    '###SUBPARTMARKER1###' => array(
     *        0 => array(
     *            '###SINGLEMARKER2###' => 'value 2',
     *        ),
     *        1 => array(
     *            '###SINGLEMARKER2###' => 'value 3',
     *        )
     *    ),
     *    '###SUBPARTMARKER2###' => array(
     *    ),
     * )
     * Subparts can be nested, so below the 'SINGLEMARKER2' it is possible to have another subpart marker with an array as the
     * value, which in its turn contains the elements of the sub-subparts.
     * Empty arrays for Subparts will cause the subtemplate to be cleared.
     *
     * @static
     *
     * @param string $content The content stream, typically HTML template content.
     * @param array $markersAndSubparts The array of single markers and subpart contents.
     * @param string $wrap A wrap value - [part1] | [part2] - for the markers before substitution.
     * @param bool $uppercase If set, all marker string substitution is done with upper-case markers.
     * @param bool $deleteUnused If set, all unused single markers are deleted.
     *
     * @return string The processed output stream
     */
    public function substituteMarkerAndSubpartArrayRecursive($content, array $markersAndSubparts, $wrap = '', $uppercase = false, $deleteUnused = false)
    {
        $wraps = GeneralUtility::trimExplode('|', $wrap);
        $singleItems = [];
        $compoundItems = [];
        // Split markers and subparts into separate arrays
        foreach ($markersAndSubparts as $markerName => $markerContent) {
            if (is_array($markerContent)) {
                $compoundItems[] = $markerName;
            } else {
                $singleItems[$markerName] = $markerContent;
            }
        }
        $subTemplates = [];
        $subpartSubstitutes = [];
        // Build a cache for the sub template
        foreach ($compoundItems as $subpartMarker) {
            if ($uppercase) {
                // Use strtr instead of strtoupper to avoid locale problems with Turkish
                $subpartMarker = strtr($subpartMarker, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            }
            if (!empty($wraps)) {
                $subpartMarker = $wraps[0] . $subpartMarker . $wraps[1];
            }
            $subTemplates[$subpartMarker] = $this->getSubpart($content, $subpartMarker);
        }
        // Replace the subpart contents recursively
        foreach ($compoundItems as $subpartMarker) {
            $completeMarker = $subpartMarker;
            if ($uppercase) {
                // use strtr instead of strtoupper to avoid locale problems with Turkish
                $completeMarker = strtr($completeMarker, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            }
            if (!empty($wraps)) {
                $completeMarker = $wraps[0] . $completeMarker . $wraps[1];
            }
            if (!empty($markersAndSubparts[$subpartMarker])) {
                foreach ($markersAndSubparts[$subpartMarker] as $partialMarkersAndSubparts) {
                    $subpartSubstitutes[$completeMarker] .= $this->substituteMarkerAndSubpartArrayRecursive($subTemplates[$completeMarker],
                        $partialMarkersAndSubparts, $wrap, $uppercase, $deleteUnused);
                }
            } else {
                $subpartSubstitutes[$completeMarker] = '';
            }
        }
        // Substitute the single markers and subparts
        $result = $this->substituteSubpartArray($content, $subpartSubstitutes);
        $result = $this->substituteMarkerArray($result, $singleItems, $wrap, $uppercase, $deleteUnused);

        return $result;
    }
}
