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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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
     * @param string|array $subpartContent If $subpartContent happens to be an array, it's [0] and [1] elements are wrapped around the content of the subpart (fetched by getSubpart())
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
                if (isset($wrapArr[0], $wrapArr[1])) {
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
            if (isset($wraps[0], $wraps[1])) {
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
            if (isset($wraps[0], $wraps[1])) {
                $completeMarker = $wraps[0] . $completeMarker . $wraps[1];
            }
            if (!empty($markersAndSubparts[$subpartMarker])) {
                $subpartSubstitutes[$completeMarker] = '';
                foreach ($markersAndSubparts[$subpartMarker] as $partialMarkersAndSubparts) {
                    $subpartSubstitutes[$completeMarker] .= $this->substituteMarkerAndSubpartArrayRecursive(
                        $subTemplates[$completeMarker],
                        $partialMarkersAndSubparts,
                        $wrap,
                        $uppercase,
                        $deleteUnused
                    );
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

    /**
     * Multi substitution function with caching.
     *
     * This function should be a one-stop substitution function for working
     * with HTML-template. It does not substitute by str_replace but by
     * splitting. This secures that the value inserted does not themselves
     * contain markers or subparts.
     *
     * Note that the "caching" won't cache the content of the substition,
     * but only the splitting of the template in various parts. So if you
     * want only one cache-entry per template, make sure you always pass the
     * exact same set of marker/subpart keys. Else you will be flooding the
     * user's cache table.
     *
     * This function takes three kinds of substitutions in one:
     * $markContentArray is a regular marker-array where the 'keys' are
     * substituted in $content with their values
     *
     * $subpartContentArray works exactly like markContentArray only is whole
     * subparts substituted and not only a single marker.
     *
     * $wrappedSubpartContentArray is an array of arrays with 0/1 keys where
     * the subparts pointed to by the main key is wrapped with the 0/1 value
     * alternating.
     *
     * @param string $content The content stream, typically HTML template content.
     * @param array $markContentArray Regular marker-array where the 'keys' are substituted in $content with their values
     * @param array $subpartContentArray Exactly like markContentArray only is whole subparts substituted and not only a single marker.
     * @param array $wrappedSubpartContentArray An array of arrays with 0/1 keys where the subparts pointed to by the main key is wrapped with the 0/1 value alternating.
     * @return string The output content stream
     * @see substituteSubpart(), substituteMarker(), substituteMarkerInObject(), TEMPLATE()
     */
    public function substituteMarkerArrayCached($content, array $markContentArray = null, array $subpartContentArray = null, array $wrappedSubpartContentArray = null)
    {
        $runtimeCache = $this->getRuntimeCache();
        // If not arrays then set them
        if ($markContentArray === null) {
            // Plain markers
            $markContentArray = [];
        }
        if ($subpartContentArray === null) {
            // Subparts being directly substituted
            $subpartContentArray = [];
        }
        if ($wrappedSubpartContentArray === null) {
            // Subparts being wrapped
            $wrappedSubpartContentArray = [];
        }
        // Finding keys and check hash:
        $sPkeys = array_keys($subpartContentArray);
        $wPkeys = array_keys($wrappedSubpartContentArray);
        $keysToReplace = array_merge(array_keys($markContentArray), $sPkeys, $wPkeys);
        if (empty($keysToReplace)) {
            return $content;
        }
        asort($keysToReplace);
        $storeKey = md5('substituteMarkerArrayCached_storeKey:' . serialize([$content, $keysToReplace]));
        $fromCache = $runtimeCache->get($storeKey);
        if ($fromCache) {
            $storeArr = $fromCache;
        } else {
            $cache = $this->getCache();
            $storeArrDat = $cache->get($storeKey);
            if (is_array($storeArrDat)) {
                $storeArr = $storeArrDat;
                // Setting the data in the first level cache
                $runtimeCache->set($storeKey, $storeArr);
            } else {
                // Finding subparts and substituting them with the subpart as a marker
                foreach ($sPkeys as $sPK) {
                    $content = $this->substituteSubpart($content, $sPK, $sPK);
                }
                // Finding subparts and wrapping them with markers
                foreach ($wPkeys as $wPK) {
                    $content = $this->substituteSubpart($content, $wPK, [
                        $wPK,
                        $wPK
                    ]);
                }

                $storeArr = [];
                // search all markers in the content
                $result = preg_match_all('/###([^#](?:[^#]*+|#{1,2}[^#])+)###/', $content, $markersInContent);
                if ($result !== false && !empty($markersInContent[1])) {
                    $keysToReplaceFlipped = array_flip($keysToReplace);
                    $regexKeys = [];
                    $wrappedKeys = [];
                    // Traverse keys and quote them for reg ex.
                    foreach ($markersInContent[1] as $key) {
                        if (isset($keysToReplaceFlipped['###' . $key . '###'])) {
                            $regexKeys[] = preg_quote($key, '/');
                            $wrappedKeys[] = '###' . $key . '###';
                        }
                    }
                    $regex = '/###(?:' . implode('|', $regexKeys) . ')###/';
                    $storeArr['c'] = preg_split($regex, $content); // contains all content parts around markers
                    $storeArr['k'] = $wrappedKeys; // contains all markers incl. ###
                    // Setting the data inside the second-level cache
                    $runtimeCache->set($storeKey, $storeArr);
                    // Storing the cached data permanently
                    $cache->set($storeKey, $storeArr, ['substMarkArrayCached'], 0);
                }
            }
        }
        if (!empty($storeArr['k']) && is_array($storeArr['k'])) {
            // Substitution/Merging:
            // Merging content types together, resetting
            $valueArr = array_merge($markContentArray, $subpartContentArray, $wrappedSubpartContentArray);
            $wSCA_reg = [];
            $content = '';
            // Traversing the keyList array and merging the static and dynamic content
            foreach ($storeArr['k'] as $n => $keyN) {
                // add content before marker
                $content .= $storeArr['c'][$n];
                if (!is_array($valueArr[$keyN])) {
                    // fetch marker replacement from $markContentArray or $subpartContentArray
                    $content .= $valueArr[$keyN];
                } else {
                    if (!isset($wSCA_reg[$keyN])) {
                        $wSCA_reg[$keyN] = 0;
                    }
                    // fetch marker replacement from $wrappedSubpartContentArray
                    $content .= $valueArr[$keyN][$wSCA_reg[$keyN] % 2];
                    $wSCA_reg[$keyN]++;
                }
            }
            // add remaining content
            $content .= $storeArr['c'][count($storeArr['k'])];
        }
        return $content;
    }

    /**
     * Substitute marker array in an array of values
     *
     * @param mixed $tree If string, then it just calls substituteMarkerArray. If array(and even multi-dim) then for each key/value pair the marker array will be substituted (by calling this function recursively)
     * @param array $markContentArray The array of key/value pairs being marker/content values used in the substitution. For each element in this array the function will substitute a marker in the content string/array values.
     * @return mixed The processed input variable.
     * @see substituteMarker()
     */
    public function substituteMarkerInObject(&$tree, array $markContentArray)
    {
        if (is_array($tree)) {
            foreach ($tree as $key => $value) {
                $this->substituteMarkerInObject($tree[$key], $markContentArray);
            }
        } else {
            $tree = $this->substituteMarkerArray($tree, $markContentArray);
        }
        return $tree;
    }

    /**
     * Adds elements to the input $markContentArray based on the values from
     * the fields from $fieldList found in $row
     *
     * @param array $markContentArray Array with key/values being marker-strings/substitution values.
     * @param array $row An array with keys found in the $fieldList (typically a record) which values should be moved to the $markContentArray
     * @param string $fieldList A list of fields from the $row array to add to the $markContentArray array. If empty all fields from $row will be added (unless they are integers)
     * @param bool $nl2br If set, all values added to $markContentArray will be nl2br()'ed
     * @param string $prefix Prefix string to the fieldname before it is added as a key in the $markContentArray. Notice that the keys added to the $markContentArray always start and end with "###
     * @param bool $htmlSpecialCharsValue If set, all values are passed through htmlspecialchars() - RECOMMENDED to avoid most obvious XSS and maintain XHTML compliance.
     * @param bool $respectXhtml if set, and $nl2br is set, then the new lines are added with <br /> instead of <br>
     * @return array The modified $markContentArray
     */
    public function fillInMarkerArray(array $markContentArray, array $row, $fieldList = '', $nl2br = true, $prefix = 'FIELD_', $htmlSpecialCharsValue = false, $respectXhtml = false)
    {
        if ($fieldList) {
            $fArr = GeneralUtility::trimExplode(',', $fieldList, true);
            foreach ($fArr as $field) {
                $markContentArray['###' . $prefix . $field . '###'] = $nl2br ? nl2br($row[$field], $respectXhtml) : $row[$field];
            }
        } else {
            if (is_array($row)) {
                foreach ($row as $field => $value) {
                    if (!MathUtility::canBeInterpretedAsInteger($field)) {
                        if ($htmlSpecialCharsValue) {
                            $value = htmlspecialchars($value);
                        }
                        $markContentArray['###' . $prefix . $field . '###'] = $nl2br ? nl2br($value, $respectXhtml) : $value;
                    }
                }
            }
        }
        return $markContentArray;
    }

    /**
     * Second-level cache
     *
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected function getCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
    }

    /**
     * First-level cache (runtime cache)
     *
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected function getRuntimeCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
    }
}
