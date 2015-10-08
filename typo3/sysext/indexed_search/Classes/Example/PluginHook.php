<?php
namespace TYPO3\CMS\IndexedSearch\Example;

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

/**
 * Index search frontend example hook
 */
/**
 * Index search frontend - EXAMPLE hook for alternative searching / display etc.
 * Hooks are configured in ext_localconf.php as key => hook-reference pairs in $TYPO3_CONF_VARS['EXTCONF']['indexed_search']['pi1_hooks']. See example in ext_localconf.php for "indexed_search"
 * Each hook must have an entry, the key must match the hook-key in class.tx_indexed_search.php and generally the key equals the function name in the hook object (a convension used)
 */
class PluginHook
{
    /**
     * Is set to a reference to the parent object, "pi1/class.indexedsearch.php"
     *
     * @var \TYPO3\CMS\IndexedSearch\Controller\SearchFormController
     */
    public $pObj;

    /**
     * EXAMPLE of how you can post process the initialized values in the frontend plugin.
     * The example reverses the order of elements in the ranking selector box. You can modify other values like this or add / remove items.
     *
     * This hook is activated by this key / value pair in ext_localconf.php
     * 'initialize_postProc' => \TYPO3\CMS\IndexedSearch\Example\PluginHook::class,
     *
     * @return void
     */
    public function initialize_postProc()
    {
        $this->pObj->optValues['order'] = array_reverse($this->pObj->optValues['order']);
    }

    /**
     * Providing an alternative search algorithm!
     *
     * @param array $sWArr Array of search words
     * @return array Array of first row, result rows, count
     */
    public function getResultRows($sWArr)
    {
    }

    /**
     * Example of how the content displayed in the result rows can be post processed before rendered into HTML.
     * This example simply shows how the description field is wrapped in italics and the path is hidden by setting it blank.
     *
     * @param array $tmplContent Template Content (generated from result row) being processed.
     * @param array $row Result row
     * @param bool $headerOnly If set, the result row is a sub-row.
     * @return array Template Content returned.
     */
    public function prepareResultRowTemplateData_postProc($tmplContent, $row, $headerOnly)
    {
        $tmplContent['description'] = '<em>' . $tmplContent['description'] . '</em>';
        $tmplContent['path'] = '';
        return $tmplContent;
    }
}
