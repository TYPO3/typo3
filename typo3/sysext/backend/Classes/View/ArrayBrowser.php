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

namespace TYPO3\CMS\Backend\View;

use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for displaying an array as a tree which can collapse / expand.
 *
 * See the extension 'lowlevel' / config (Backend module 'Tools > Configuration')
 * @internal just a helper class for internal usage.
 */
class ArrayBrowser
{
    /**
     * @var bool
     */
    public $expAll = false;

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
     * If set, the values are truncated with "..." appended if longer than a certain
     * length.
     *
     * @var bool
     */
    public $regexMode = false;

    /**
     * If set, search for string with regex, otherwise stristr()
     *
     * @var bool
     */
    public $searchKeysToo = true;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * If null then there are no links set.
     *
     * @var Route|null
     */
    protected $route;

    public function __construct(Route $route = null)
    {
        $this->route = $route;
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    /**
     * Set var name here if you want links to the variable name.
     *
     * Make browsable tree
     * Before calling this function you may want to set some of the internal vars like
     * depthKeys and regexMode.
     *
     * @param array $array The array to display
     * @param string $positionKey Key-position id. Build up during recursive calls - [key1].[key2].[key3] - and so on.
     * @return string HTML for the tree
     */
    public function tree($array, $positionKey)
    {
        $output = '<ul class="list-tree text-monospace">';
        if ($positionKey) {
            $positionKey .= '.';
        }
        foreach ($array as $key => $value) {
            $key = (string)$key;
            $depth = $positionKey . $key;
            if (is_object($value) && !$value instanceof \Traversable) {
                $value = (array)$value;
            }
            $isArray = is_iterable($value);
            $isResult = (bool)($this->searchKeys[$depth] ?? false);
            $isExpanded = $isArray && (!empty($this->depthKeys[$depth]) || $this->expAll);
            $output .= '<li' . ($isResult ? ' class="active"' : '') . '>';
            $output .= '<span class="list-tree-group">';
            if ($isArray && !$this->expAll && $this->route) {
                $goto = 'a' . substr(md5($depth), 0, 6);
                $output .= '<a class="list-tree-control' . ($isExpanded ? ' list-tree-control-open' : ' list-tree-control-closed') . '" id="' . $goto . '" href="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute($this->route->getOption('_identifier'), ['node' => [rawurldecode($depth) => $isExpanded ? 0 : 1]]) . '#' . $goto) . '"><i class="fa"></i></a> ';
            }
            $output .= '<span class="list-tree-label">' . htmlspecialchars((string)$key) . '</span>';
            if (!$isArray) {
                $output .= ' = <span class="list-tree-value">' . htmlspecialchars((string)$value) . '</span>';
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
            $depth_in .= '.';
        }
        foreach ($keyArr as $key => $value) {
            $depth = $depth_in . $key;
            $deeper = is_array($keyArr[$key]);
            if ($this->regexMode) {
                if (
                    is_scalar($keyArr[$key]) && preg_match('/' . $searchString . '/', (string)$keyArr[$key])
                    || $this->searchKeysToo && preg_match('/' . $searchString . '/', (string)$key)
                ) {
                    $this->searchKeys[$depth] = 1;
                }
            } else {
                if (
                    is_scalar($keyArr[$key]) && stripos((string)$keyArr[$key], $searchString) !== false
                    || $this->searchKeysToo && stripos((string)$key, $searchString) !== false
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
            $theKeyParts = explode('.', (string)$theK);
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
