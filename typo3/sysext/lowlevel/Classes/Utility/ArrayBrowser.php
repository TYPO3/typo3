<?php
namespace TYPO3\CMS\Lowlevel\Utility;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class for displaying an array as a tree
 * See the extension 'lowlevel' /config (Backend module 'Tools > Configuration')
 */
class ArrayBrowser
{
    /**
     * @var bool
     */
    public $expAll = false;

    /**
     * If set, will expand all (depthKeys is obsolete then) (and no links are applied)
     *
     * @var bool
     */
    public $dontLinkVar = false;

    /**
     * If set, the variable keys are not linked.
     *
     * @var array
     */
    public $depthKeys = [];

    /**
     * Array defining which keys to expand. Typically set from outside from some session
     * variable - otherwise the array will collapse.
     *
     * @var array
     */
    public $searchKeys = [];

    /**
     * After calling the getSearchKeys function this array is populated with the
     * key-positions in the array which contains values matching the search.
     *
     * @var int
     */
    public $fixedLgd = 1;

    /**
     * If set, the values are truncated with "..." appended if longer than a certain
     * length.
     *
     * @var int
     */
    public $regexMode = 0;

    /**
     * If set, search for string with regex, otherwise stristr()
     *
     * @var bool
     */
    public $searchKeysToo = false;

    /**
     * If set, array keys are subject to the search too.
     *
     * @var string
     */
    public $varName = '';

    /**
     * Set var name here if you want links to the variable name.
     *
     * Make browseable tree
     * Before calling this function you may want to set some of the internal vars like
     * depthKeys, regexMode and fixedLgd.
     *
     * @param array $array The array to display
     * @param string $positionKey Key-position id. Build up during recursive calls - [key1].[key2].[key3] - an so on.
     * @param string $depthData is deprecated since TYPO3 CMS 7, and will be removed with CMS 8
     * @return string HTML for the tree
     */
    public function tree($array, $positionKey, $depthData = null)
    {
        if ($depthData) {
            GeneralUtility::deprecationLog('ArrayBrowser::tree parameter $depthData is deprecated since TYPO3 CMS 7 and is not used anymore. Please remove the parameter.');
        }
        $output = '<ul class="list-tree text-monospace">';
        if ($positionKey) {
            $positionKey = $positionKey . '.';
        }
        foreach ($array as $key => $value) {
            $depth = $positionKey . $key;
            if (is_object($value) && !$value instanceof \Traversable) {
                $value = (array)$value;
            }
            $isArray = is_array($value) || $value instanceof \Traversable;
            $isResult = (bool)$this->searchKeys[$depth];
            $isExpanded = $isArray && ($this->depthKeys[$depth] || $this->expAll);
            $output .= '<li' . ($isResult ? ' class="active"' : '') . '>';
            if ($isArray && !$this->expAll) {
                $goto = 'a' . substr(md5($depth), 0, 6);
                $output .= '<a class="list-tree-control' . ($isExpanded ? ' list-tree-control-open' : ' list-tree-control-closed') . '" id="' . $goto . '" href="' . htmlspecialchars((BackendUtility::getModuleUrl(GeneralUtility::_GP('M')) . '&node[' . $depth . ']=' . ($isExpanded ? 0 : 1) . '#' . $goto)) . '"><i class="fa"></i></a> ';
            }
            $output .= '<span class="list-tree-group">';
            $output .= $this->wrapArrayKey($key, $depth, !$isArray ? $value : '');
            if (!$isArray) {
                $output .= ' = <span class="list-tree-value">' . $this->wrapValue($value) . '</span>';
            }
            $output .= '</span>';
            if ($isExpanded) {
                $output .= $this->tree(
                    $value,
                    $depth
                );
            }
            $output .= '</li>';
        }
        $output .= '</ul>';
        return $output;
    }

    /**
     * Wrapping the value in bold tags etc.
     *
     * @param string $theValue The title string
     * @return string Title string, htmlspecialchars()'ed
     */
    public function wrapValue($theValue)
    {
        $wrappedValue = '';
        if ((string)$theValue !== '') {
            $wrappedValue = htmlspecialchars($theValue);
        }
        return $wrappedValue;
    }

    /**
     * Wrapping the value in bold tags etc.
     *
     * @param string $label The title string
     * @param string $depth Depth path
     * @param string $theValue The value for the array entry.
     * @return string Title string, htmlspecialchars()'ed
     */
    public function wrapArrayKey($label, $depth, $theValue)
    {
        // Protect label:
        $label = htmlspecialchars($label);

        // If varname is set:
        if ($this->varName && !$this->dontLinkVar) {
            $variableName = $this->varName
                . '[\'' . str_replace('.', '\'][\'', $depth) . '\'] = '
                . (!MathUtility::canBeInterpretedAsInteger($theValue) ? '\''
                . addslashes($theValue) . '\'' : $theValue) . '; ';
            $label = '<a class="list-tree-label" href="'
                . htmlspecialchars((BackendUtility::getModuleUrl(GeneralUtility::_GP('M'))
                . '&varname=' . urlencode($variableName)))
                . '#varname">' . $label . '</a>';
        }
        return '<span class="list-tree-label">' . $label . '</span>';
    }

    /**
     * Creates an array with "depthKeys" which will expand the array to show the search results
     *
     * @param array $keyArr The array to search for the value
     * @param string $depth_in Depth string - blank for first call (will build up during recursive calling creating
     *                         an id of the position: [key1].[key2].[key3]
     * @param string $searchString The string to search for
     * @param array $keyArray Key array, for first call pass empty array
     * @return array
     */
    public function getSearchKeys($keyArr, $depth_in, $searchString, $keyArray)
    {
        if ($depth_in) {
            $depth_in = $depth_in . '.';
        }
        foreach ($keyArr as $key => $value) {
            $depth = $depth_in . $key;
            $deeper = is_array($keyArr[$key]);
            if ($this->regexMode) {
                if (
                    is_scalar($keyArr[$key]) && preg_match('/' . $searchString . '/', $keyArr[$key])
                    || $this->searchKeysToo && preg_match('/' . $searchString . '/', $key)
                ) {
                    $this->searchKeys[$depth] = 1;
                }
            } else {
                if (
                    is_scalar($keyArr[$key]) && stristr($keyArr[$key], $searchString)
                    || $this->searchKeysToo && stristr($key, $searchString)
                ) {
                    $this->searchKeys[$depth] = 1;
                }
            }
            if ($deeper) {
                $cS = count($this->searchKeys);
                $keyArray = $this->getSearchKeys($keyArr[$key], $depth, $searchString, $keyArray);
                if ($cS != count($this->searchKeys)) {
                    $keyArray[$depth] = 1;
                }
            }
        }
        return $keyArray;
    }

    /**
     * Function modifying the depthKey array
     *
     * @param array $arr Array with instructions to open/close nodes.
     * @param array $settings Input depth_key array
     * @return array Output depth_key array with entries added/removed based on $arr
     */
    public function depthKeys($arr, $settings)
    {
        $tsbrArray = [];
        foreach ($arr as $theK => $theV) {
            $theKeyParts = explode('.', $theK);
            $depth = '';
            $c = count($theKeyParts);
            $a = 0;
            foreach ($theKeyParts as $p) {
                $a++;
                $depth .= ($depth ? '.' : '') . $p;
                $tsbrArray[$depth] = $c == $a ? $theV : 1;
            }
        }
        // Modify settings
        foreach ($tsbrArray as $theK => $theV) {
            if ($theV) {
                $settings[$theK] = 1;
            } else {
                unset($settings[$theK]);
            }
        }
        return $settings;
    }
}
