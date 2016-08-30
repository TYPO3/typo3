<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

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
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * Table Operations extension for htmlArea RTE
 */
class TableOperations extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'TableOperations';

    /**
     * The comma-separated list of names of prerequisite plugins
     *
     * @var string
     */
    protected $requiredPlugins = 'TYPO3Color,BlockStyle';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'table, toggleborders, tableproperties, tablerestyle, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit,
		columnproperties, columninsertbefore, columninsertafter, columndelete, columnsplit,
		cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'table' => 'InsertTable',
        'toggleborders' => 'TO-toggle-borders',
        'tableproperties' => 'TO-table-prop',
        'tablerestyle' => 'TO-table-restyle',
        'rowproperties' => 'TO-row-prop',
        'rowinsertabove' => 'TO-row-insert-above',
        'rowinsertunder' => 'TO-row-insert-under',
        'rowdelete' => 'TO-row-delete',
        'rowsplit' => 'TO-row-split',
        'columnproperties' => 'TO-col-prop',
        'columninsertbefore' => 'TO-col-insert-before',
        'columninsertafter' => 'TO-col-insert-after',
        'columndelete' => 'TO-col-delete',
        'columnsplit' => 'TO-col-split',
        'cellproperties' => 'TO-cell-prop',
        'cellinsertbefore' => 'TO-cell-insert-before',
        'cellinsertafter' => 'TO-cell-insert-after',
        'celldelete' => 'TO-cell-delete',
        'cellsplit' => 'TO-cell-split',
        'cellmerge' => 'TO-cell-merge'
    ];

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        $available = parent::main($configuration);
        if ($this->configuration['client']['browser'] === 'opera') {
            $this->configuration['thisConfig']['hideTableOperationsInToolbar'] = 0;
        }
        return $available;
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins, in this case, JS configuration of block elements
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        if (in_array('table', $this->toolbar)) {
            // Combining fieldset disablers as a list
            $disabledFieldsets = ['Alignment', 'Borders', 'Color', 'Description', 'Layout', 'RowGroup', 'Spacing', 'Style'];
            foreach ($disabledFieldsets as $index => $fieldset) {
                if (!trim($this->configuration['thisConfig']['disable' . $fieldset . 'FieldsetInTableOperations'])) {
                    unset($disabledFieldsets[$index]);
                }
            }
            $disabledFieldsets = strtolower(implode(',', $disabledFieldsets));
            // Dialogue fieldsets removal configuration
            if ($disabledFieldsets) {
                $dialogues = ['table', 'tableproperties', 'rowproperties', 'columnproperties', 'cellproperties'];
                foreach ($dialogues as $dialogue) {
                    if (in_array($dialogue, $this->toolbar)) {
                        if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$dialogue . '.'])) {
                            $jsArray[] = 'RTEarea[editornumber].buttons.' . $dialogue . ' = new Object();';
                            $jsArray[] = 'RTEarea[editornumber].buttons.' . $dialogue . '.removeFieldsets = "' . $disabledFieldsets . '";';
                        } elseif ($this->configuration['thisConfig']['buttons.'][$dialogue . '.']['removeFieldsets']) {
                            $jsArray[] = 'RTEarea[editornumber].buttons.' . $dialogue . '.removeFieldsets += ",' . $disabledFieldsets . '";';
                        } else {
                            $jsArray[] = 'RTEarea[editornumber].buttons.' . $dialogue . '.removeFieldsets = ",' . $disabledFieldsets . '";';
                        }
                    }
                }
            }
            $jsArray[] = 'RTEarea[editornumber].hideTableOperationsInToolbar = ' . (trim($this->configuration['thisConfig']['hideTableOperationsInToolbar']) ? 'true' : 'false') . ';';
        }
        return implode(LF, $jsArray);
    }

    /**
     * Return an updated array of toolbar enabled buttons
     *
     * @param array $show: array of toolbar elements that will be enabled, unless modified here
     * @return array toolbar button array, possibly updated
     */
    public function applyToolbarConstraints($show)
    {
        // We will not allow any table operations button if the table button is not enabled
        if (!in_array('table', $show)) {
            return array_diff($show, GeneralUtility::trimExplode(',', $this->pluginButtons));
        } else {
            return $show;
        }
    }
}
