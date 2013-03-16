<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Table Operations extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class TableOperations extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'TableOperations';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/TableOperations/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $requiresClassesConfiguration = TRUE;

	// TRUE if the registered plugin requires the PageTSConfig Classes configuration
	protected $requiredPlugins = 'TYPO3Color,BlockStyle';

	// The comma-separated list of names of prerequisite plugins
	protected $pluginButtons = 'table, toggleborders, tableproperties, tablerestyle, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit,
						columnproperties, columninsertbefore, columninsertafter, columndelete, columnsplit,
						cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge';

	protected $convertToolbarForHtmlAreaArray = array(
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
	);

	public function main($parentObject) {
		$available = parent::main($parentObject);
		if ($this->htmlAreaRTE->client['browser'] == 'opera') {
			$this->thisConfig['hideTableOperationsInToolbar'] = 0;
		}
		return $available;
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return 	string		JS configuration for registered plugins, in this case, JS configuration of block elements
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		global $TSFE, $LANG;
		$registerRTEinJavascriptString = '';
		if (in_array('table', $this->toolbar)) {
			// Combining fieldset disablers as a list
			$disabledFieldsets = array('Alignment', 'Borders', 'Color', 'Description', 'Layout', 'RowGroup', 'Spacing', 'Style');
			foreach ($disabledFieldsets as $index => $fieldset) {
				if (!trim($this->thisConfig[('disable' . $fieldset . 'FieldsetInTableOperations')])) {
					unset($disabledFieldsets[$index]);
				}
			}
			$disabledFieldsets = strtolower(implode(',', $disabledFieldsets));
			// Dialogue fieldsets removal configuration
			if ($disabledFieldsets) {
				$dialogues = array('table', 'tableproperties', 'rowproperties', 'columnproperties', 'cellproperties');
				foreach ($dialogues as $dialogue) {
					if (in_array($dialogue, $this->toolbar)) {
						if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($dialogue . '.')])) {
							$registerRTEinJavascriptString .= '
					RTEarea[' . $RTEcounter . '].buttons.' . $dialogue . ' = new Object();
					RTEarea[' . $RTEcounter . '].buttons.' . $dialogue . '.removeFieldsets = "' . $disabledFieldsets . '";';
						} elseif ($this->thisConfig['buttons.'][$dialogue . '.']['removeFieldsets']) {
							$registerRTEinJavascriptString .= '
					RTEarea[' . $RTEcounter . '].buttons.' . $dialogue . '.removeFieldsets += ",' . $disabledFieldsets . '";';
						} else {
							$registerRTEinJavascriptString .= '
					RTEarea[' . $RTEcounter . '].buttons.' . $dialogue . '.removeFieldsets = ",' . $disabledFieldsets . '";';
						}
					}
				}
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].hideTableOperationsInToolbar = ' . (trim($this->thisConfig['hideTableOperationsInToolbar']) ? 'true' : 'false') . ';';
		}
		return $registerRTEinJavascriptString;
	}

	/**
	 * Return an updated array of toolbar enabled buttons
	 *
	 * @param 	array		$show: array of toolbar elements that will be enabled, unless modified here
	 * @return 	array		toolbar button array, possibly updated
	 */
	public function applyToolbarConstraints($show) {
		// We will not allow any table operations button if the table button is not enabled
		if (!in_array('table', $show)) {
			return array_diff($show, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->pluginButtons));
		} else {
			return $show;
		}
	}

}


?>