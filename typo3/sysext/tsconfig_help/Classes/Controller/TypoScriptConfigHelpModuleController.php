<?php
namespace TYPO3\CMS\TsconfigHelp\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Stephane Schitter <stephane.schitter@free.fr>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
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
 * Module 'TypoScript Help' for the 'tsconfig_help' extension.
 *
 * @author Stephane Schitter <stephane.schitter@free.fr>
 */
class TypoScriptConfigHelpModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * @todo Define visibility
	 */
	public $pageinfo;

	// This is used to count how many times the same obj_string appears in each extension manual
	/**
	 * @todo Define visibility
	 */
	public $objStringsPerExtension = array();

	// This is used to count how many times the same obj_string appears across all extensions
	/**
	 * @todo Define visibility
	 */
	public $allObjStrings = array();

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function menuConfig() {
		$this->MOD_MENU = array(
			'function' => array(
				'1' => $GLOBALS['LANG']->getLL('display')
			)
		);
		if ($GLOBALS['BE_USER']->user['admin']) {
			$this->MOD_MENU['function']['2'] = $GLOBALS['LANG']->getLL('rebuild');
		}
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Access check!
		$access = $GLOBALS['BE_USER']->check('modules', 'help_txtsconfighelpM1');
		// Draw the header.
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/tsconfig_help.html');
		if ($access || $GLOBALS['BE_USER']->user['admin']) {
			$this->doc->form = '<form action="" method="post">';
			// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL) {
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode = '
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
			// Render content:
			$this->moduleContent();
			$this->content .= $this->doc->spacer(10);
			$markers['FUNC_MENU'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		} else {
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
			$markers['FUNC_MENU'] = '';
		}
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;
		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render($GLOBALS['LANG']->getLL('title'), $this->content);
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'shortcut' => ''
		);
		if ($this->id && is_array($this->pageinfo) || $GLOBALS['BE_USER']->user['admin'] && !$this->id) {
			// Shortcut
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
			}
		}
		return $buttons;
	}

	/**
	 * Generates the module content
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function moduleContent() {
		switch ((string) $this->MOD_SETTINGS['function']) {
		case 1:
			$content = '<div align="left"><strong>' . $GLOBALS['LANG']->getLL('referenceExplanation') . '</strong></div>';
			$content .= '<p>' . $GLOBALS['LANG']->getLL('referenceExplanationDetailed') . '</p><br />';
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('displayReferences'), $content, 0, 1);
			$this->content .= '<a href="#" onclick="vHWin=window.open(\'' . $GLOBALS['BACK_PATH'] . 'wizard_tsconfig.php?mode=tsref&amp;P[formName]=editForm\',\'popUp\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;" title="TSref reference">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-typoscript-documentation-open') . 'TSREF</a><br />';
			$this->content .= '<a href="#" onclick="vHWin=window.open(\'' . $GLOBALS['BACK_PATH'] . 'wizard_tsconfig.php?mode=beuser&amp;P[formName]=editForm\',\'popUp\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;" title="TSref reference">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-typoscript-documentation-open') . 'USER TSCONFIG</a><br />';
			$this->content .= '<a href="#" onclick="vHWin=window.open(\'' . $GLOBALS['BACK_PATH'] . 'wizard_tsconfig.php?mode=page&amp;P[formName]=editForm\',\'popUp\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;" title="TSref reference">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-typoscript-documentation-open') . 'PAGE TSCONFIG</a><br />';
			break;
		case 2:
			if ($GLOBALS['BE_USER']->user['admin']) {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_rebuild')) {
					// remove all data from the database
					$this->purgeSQLContents();
					// get all loaded extension keys
					$extArray = $GLOBALS['TYPO3_LOADED_EXT'];
					$content = '<div align="left"><strong>' . $GLOBALS['LANG']->getLL('loadedTSfrom') . '</strong></div><br />';
					// parse the extension names only (no need for all details from the TYPO3_LOADED_EXT table
					foreach ($extArray as $extName => $dummy) {
						// check that the extension is really loaded (which should always be the case)
						if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extName)) {
							// extract the content.xml from the manual.sxw ZIP file
							$manual = $this->getZIPFileContents(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extName) . 'doc/manual.sxw', 'content.xml');
							// check if the manual file actually exists and if the content.xml could be loaded
							if ($manual != '') {
								// if the manual file exists, proceed with the load into the SQL database
								$content .= '<p>Extension ' . $extName . '...';
								// run the extraction processing and import the data into SQL. Return the number of TS tables found in the open office document
								$number = $this->loadExtensionManual($extName, $manual);
								// print a status message with a link to the openoffice manual
								$content .= $number . ' ' . $GLOBALS['LANG']->getLL('sections') . ' (<a href="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($extName) . 'doc/manual.sxw">manual</a>)</p>';
							}
						} else {
							// This should never happen!
							die('Fatal error : loaded extension not actually loaded? Please file a bug report at http://forge.typo3.org!');
						}
					}
					$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('rebuildTS'), $content . '<br />', 0, 1);
					// Issue warnings about duplicate or empty obj_strings, if any
					// An obj_string should be unique. It should appear in only one extension manual and then only once
					// If the sum of all occurrences of a given obj_string is more than one, issue a list of duplicate entries as a warning
					$duplicateWarnings = '';
					$emptyWarnings = '';
					foreach ($this->objStringsPerExtension as $obj_string => $extensions) {
						if (empty($obj_string)) {
							$emptyWarnings = '<p class="typo3-red">' . $GLOBALS['LANG']->getLL('warning_manualsWithoutMarkers');
							foreach ($extensions as $extensionKey => $counter) {
								$emptyWarnings .= ' ' . $extensionKey . ' (' . $counter . ')<br />';
							}
							$emptyWarnings .= '</p><br />';
						} else {
							if (array_sum($extensions) > 1) {
								$duplicateWarnings .= $obj_string . ':';
								foreach ($extensions as $extensionKey => $counter) {
									$duplicateWarnings .= ' ' . $extensionKey . ' (' . $counter . ')';
								}
								$duplicateWarnings .= '<br />';
							}
						}
					}
					$warnings = $emptyWarnings;
					if (!empty($duplicateWarnings)) {
						$warnings .= '<p class="typo3-red">' . $GLOBALS['LANG']->getLL('warning_duplicateMarkers') . '<br />' . $duplicateWarnings . '</p>';
					}
					if (!empty($warnings)) {
						$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('updateWarnings'), '<div>' . $warnings . '</div>', 0, 1);
					}
				}
				$content = '<p>' . $GLOBALS['LANG']->getLL('rebuildExplanation') . '</p><br />';
				$content .= $GLOBALS['LANG']->getLL('rebuild') . ' <input type="submit" name="_rebuild" value="Rebuild" /><br />';
				$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('rebuildTS'), $content, 0, 1);
			} else {
				$this->content .= '<p>' . $GLOBALS['LANG']->getLL('adminAccessOnly') . '</p><br />';
			}
			break;
		}
	}

	/**
	 * Returns the contents of a specific file within the ZIP
	 *
	 * @return string Contents
	 * @todo Define visibility
	 */
	public function getZIPFileContents($ZIPfile, $filename) {
		if (file_exists($ZIPfile)) {
			// Unzipping SXW file, getting filelist:
			$tempPath = PATH_site . 'typo3temp/tx_tsconfighelp_ziptemp/';
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($tempPath);
			$this->unzip($ZIPfile, $tempPath);
			$output = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($tempPath . $filename);
			$cmd = 'rm -r "' . $tempPath . '"';
			\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
			return $output;
		}
	}

	/**
	 * Unzips a zip file in the given path.
	 * Uses the Extension Manager unzip functions.
	 *
	 * @param string $file Full path to zip file
	 * @param string $path Path to change to before extracting
	 * @return boolean TRUE on success, FALSE in failure
	 * @todo Define visibility
	 */
	public function unzip($file, $path) {
		// We use the unzip class of the Extension Manager here
		// TODO: move unzip class to core
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('em')) {
			// Em is not loaded, so include the unzip class
			\TYPO3\CMS\Core\Utility\GeneralUtility::requireOnce(PATH_typo3 . 'sysext/em/classes/tools/class.tx_em_tools_unzip.php');
		}
		$unzip = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_em_Tools_Unzip', $file);
		$ret = $unzip->extract(array('add_path' => $path));
		return is_array($ret);
	}

	/**
	 * Parses the whole XML file in order to understand the Styles structure. This function is mostly looking at the styles
	 * that create bold or italic characters in the document, as these will later on need to be translated to <i> and <strong> tags
	 * This function takes into account the hierarchy of the styles, as created by OpenOffice. This means that if a style has
	 * a parant, this function will make it inherit the styles of the parent. Therefore bold and italic styles are propagated
	 * to children as well.
	 *
	 * This function assumes the STYLE definitions are not nested. If they are then, then "close" type will need to be used
	 * more carefully, and a depth counter will need to be implemented.
	 *
	 * @param array $vals The XML values array. The XML index is not necessary in this function.
	 * @return array Array that contains the different styles with their parent (required to recognise "Table Contents"-type styles), and their style (bold/italic)
	 * @todo Define visibility
	 */
	public function parseStyles($vals) {
		$currentStyleName = '';
		$style = array();
		foreach ($vals as $node) {
			switch ($node['type']) {
				case 'open':
					switch ($node['tag']) {
						case 'STYLE:STYLE':
							$currentStyleName = $node['attributes']['STYLE:NAME'];
							if (array_key_exists('STYLE:PARENT-STYLE-NAME', $node['attributes'])) {
								$parentStyleName = $node['attributes']['STYLE:PARENT-STYLE-NAME'];
								// Keep trace of parents in the style array
								$style[$currentStyleName]['parents'][] = $parentStyleName;
							} else {
								// This style has no parent, therefore clean the variable to avoid side effects with next use of that variable
								$parentStyleName = '';
							}
							// The style parent is already documented in the array
							if (array_key_exists($parentStyleName, $style)) {
								// Inherit parent style
								$style[$currentStyleName] = $style[$parentStyleName];
							}
							break;
					}
					break;
				case 'complete':
					switch ($node['tag']) {
						case 'STYLE:PROPERTIES':
							if (is_array($node['attributes']) && array_key_exists('FO:FONT-WEIGHT', $node['attributes'])) {
								$style[$currentStyleName]['font-weight'] = $node['attributes']['FO:FONT-WEIGHT'];
							}
							if (is_array($node['attributes']) && array_key_exists('FO:FONT-STYLE', $node['attributes'])) {
								$style[$currentStyleName]['font-style'] = $node['attributes']['FO:FONT-STYLE'];
							}
							break;
					}
					break;
				case 'close':
					switch ($node['tag']) {
						case 'STYLE:STYLE':
							$currentStyleName = '';
							break;
						case 'STYLE:PROPERTIES':
							break;
					}
					break;
			}
		}
		return $style;
	}

	/**
	 * Checks if the style is a child of a specified parent. This is useful for example to check if a specific style that has
	 * a generic name ("P8" for example) is a child of the "Table Contents" style. It would not only inherit its style (bold/
	 * italic) but also its properties like being part of a Table.
	 *
	 * This function references the global $Styles variables which must have been created previously with parseStyles()
	 *
	 * @param string $child Name of the child style that we want to get properties for
	 * @param string $parent Name of the parent style that we want to compare the child against
	 * @return boolean TRUE if the child and parent are linked together. FALSE otherwise.
	 * @todo Define visibility
	 */
	public function isStyleChildOf($child, $parent) {
		global $Styles;
		// The child is actually the same as the parent. They are obviously linked together
		if (!strcmp($child, $parent)) {
			return TRUE;
		}
		if (is_array($Styles[$child]) && array_key_exists('parents', $Styles[$child]) && array_search($parent, $Styles[$child]['parents']) !== FALSE) {
			// and the parent appears amongst its ancestors
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Find the table description that we want, then find a TABLE:TABLE close, immediately followed by a TEXT:P which has a
	 * style which is a child of a "Table Contents", then look up the index to find where the TABLE begins, and start browsing
	 * from there (returns these start and end indexes).
	 *
	 * This function only finds the next TS definition table. In order to find all TS definition tables from the document, the
	 * function needs to be called several times, starting where it left off last time. The third parameter is the index that
	 * is used to indicate where to start, and which is modified when the function returns to indicate where we left off.
	 *
	 * This function uses the unusual index XML array in addition to the values, this is necessary to find where in the XML
	 * tree a TABLE starts once we found where it ends.
	 *
	 * @param array $vals The XML values array
	 * @param array $index The XML index array
	 * @param integer $id This is a reference to the index in the array where we should be starting the search
	 * @return array Array of the table start index and table end index where TS is defined. table start is FALSE if there are no more TS entries in the document (consider it similar to an EOF reached status).
	 * @todo Define visibility
	 */
	public function nextTSDefinitionTable($vals, $index, &$id) {
		// Browse the table where we left off last time
		while ($id < count($vals)) {
			$node = $vals[$id];
			// check if next entry is a candidate
			if (!strcmp($node['type'], 'close') && !strcmp($node['tag'], 'TABLE:TABLE')) {
				$nextNode = $vals[$id + 1];
				if (!strcmp($nextNode['tag'], 'TEXT:P') && $this->isStyleChildOf($nextNode['attributes']['TEXT:STYLE-NAME'], 'Table Contents')) {
					// We found a good entry
					// Find the ID in the list of table items
					$closeIndex = array_search($id, $index['TABLE:TABLE']);
					// Find the matching start of the table in the $vals array
					$tableStart = $index['TABLE:TABLE'][$closeIndex - 1];
					return array($tableStart, $id++);
				}
			}
			$id = $id + 1;
		}
		// Marks the end of the input, no more table to find. WARNING: needs to be tested with === FALSE
		return array(FALSE, 0);
	}

	/**
	 * Converts an Open Office like style (font-weight:bold for example) into an HTML style (b is for bold). This function uses the global
	 * $Styles defined through the parseStyles function
	 *
	 * @param array $node An array containing the [attributes][style] items in the OO format
	 * @return array An array where the items are all the HTML styles to apply to closely match the input OO-like styles
	 * @todo Define visibility
	 */
	public function styleTags($node) {
		global $Styles;
		$styleName = $node['attributes']['TEXT:STYLE-NAME'];
		switch ($Styles[$styleName]['font-weight']) {
			case 'bold':
				$styleTags[] = 'b';
				break;
		}
		switch ($Styles[$styleName]['font-style']) {
			case 'italic':
				$styleTags[] = 'i';
				break;
		}
		return $styleTags;
	}

	/**
	 * Converts an array containing style strings (for example ['b','i']) into their HTML equivalents
	 *
	 * @param array $style An array containing all the style tags
	 * @param string $char Either '' or '/' depending on whether the style definition is to open or close the style
	 * @return string The sequence of tags to open or close the style, for example <strong><i>
	 * @todo Define visibility
	 */
	public function styleHTML($style, $char) {
		$string = '';
		if (count($style) > 0) {
			foreach ($style as $tag) {
				$string .= '<' . $char . $tag . '>';
			}
		}
		return $string;
	}

	/**
	 * This function does a little more than just HSC'ing the text passed to it. It does a general cleaning of the input:
	 * htmlspecialchars() : general cleaning for the HTML display, including single quotes transformation
	 * stripslashes() : otherwise the backslashes will cause an issue with a future unserialize of the data
	 * &nbsp if empty : if the input is empty, we return a &nbsp; string so that in the HTML output something will be 	displayed
	 * utf8 to entities cleaning : in some SXW docs we can find UTF8 characters that need to be converted to be displayed on screen
	 *
	 * @param string $text Text that will need to be transformed according to the HSC and other rules
	 * @return string Transformed text that can now be freely serialized or exported to HTML
	 * @todo Define visibility
	 */
	public function HSCtext($text) {
		global $LANG;
		// Rhere is some content in the text field
		if (strcmp($text, '')) {
			// Stripslashes required as it could confuse unserialize
			$cleantext = stripslashes(htmlspecialchars($text, ENT_QUOTES));
			return $LANG->csConvObj->utf8_to_entities($cleantext, $LANG->charSet);
		} else {
			// there is no text, it's empty
			return '&nbsp;';
		}
	}

	/**
	 * This function parses a Table from an Open Office document, in an XML format, and extracts the information. It will therefore crawl the
	 * XML tree and aggregate all the accepted contents into an array with the table contents.
	 *
	 * This function needs to extract the following information from the TABLE:
	 * property => (column 1)
	 * datatype => (column 2)
	 * description => (column 3)
	 * default => (column 4)
	 * column_count => number of columns found in table. Usually 4, but for spanned columns, it would be less (1 for example)
	 * is_propertyTable => ??? (almost always equal to 1)
	 *
	 * @param array $vals This is the input XML data that is to be parsed
	 * @param integer $start The starting ID in the XML array to parse the data from
	 * @param integer $end The ending ID in the XML array to stop parsing data
	 * @return array An array with the contents of the different columns extracted from the input data
	 * @todo Define visibility
	 */
	public function parseTable($vals, $start, $end) {
		$sectionHeader = 0;
		$sectionRow = 0;
		$sectionCell = 0;
		$sectionP = 0;
		// This variable will either be empty (no newline required) or '\n' (newline required)
		$newLineRequired = '';
		// This will be the list of tag styles to apply to the text
		$textStyle = array();
		$currentRow = 0;
		$currentCell = 0;
		$rowID = 0;
		// Also gets reset at every top-level row
		$cellID = 0;
		// Will contain the results of the function
		$table = array();
		$id = $start;
		while ($id < $end) {
			$node = $vals[$id];
			// Sanity check
			if ($sectionHeader < 0) {
				die('Malformed XML (header-rows)' . LF);
			}
			if ($sectionRow < 0) {
				die('Malformed XML (row)' . LF);
			}
			if ($sectionCell < 0) {
				die('Malformed XML (cell)' . LF);
			}
			if ($sectionP < 0) {
				die('Malformed XML (P)' . LF);
			}
			switch ($node['type']) {
				case 'open':
					switch ($node['tag']) {
						case 'TABLE:TABLE-HEADER-ROWS':
							$sectionHeader++;
							break;
						case 'TABLE:TABLE-ROW':
							// Skip section header, we only look at the *contents* of the table
							if (!$sectionHeader) {
								$sectionRow++;
								// Make sure we are within a top-level row
								if ($sectionRow == 1) {
									$rowID++;
									$cellID = 0;
								}
							}
							break;
						case 'TABLE:TABLE-CELL':
							// Skip section header, we only look at the *contents* of the table
							if (!$sectionHeader) {
								$sectionCell++;
								// Make sure we are within a top-level cell
								if ($sectionCell == 1) {
									$cellID++;
									// No newline required after this
									$newLineRequired = '';
								}
							}
							break;
						case 'TEXT:P':
							// Make sure we are in a cell
							if ($sectionCell) {
								$sectionP++;
								$table[$rowID - 1][$cellID - 1] .= $this->styleHTML($this->styleTags($node), '') . $newLineRequired . $this->HSCtext($node['value']);
								// No newline required after this
								$newLineRequired = '';
								$latestTEXTPopen = $node;
							}
							break;
					}
					break;
				case 'complete':
					switch ($node['tag']) {
						case 'TEXT:P':
							// make sure we are in a cell
							if ($sectionCell) {
								$table[$rowID - 1][$cellID - 1] .= $this->styleHTML($this->styleTags($node), '') . $newLineRequired . $this->HSCtext($node['value']) . $this->styleHTML($this->styleTags($node), '/');
								$newLineRequired = '<br>';
							}
							break;
						case 'TEXT:SPAN':
							// make sure we are in a cell
							if ($sectionCell) {
								$table[$rowID - 1][$cellID - 1] .= $this->styleHTML($this->styleTags($node), '') . $newLineRequired . $this->HSCtext($node['value']) . $this->styleHTML($this->styleTags($node), '/');
								$newLineRequired = '';
							}
							break;
						case 'TEXT:S':
							// make sure we are in a cell
							if ($sectionCell) {
								for ($i = 0; $i < $node['attributes']['TEXT:C']; $i++) {
									$table[$rowID - 1][$cellID - 1] .= '&nbsp;';
								}
								// no newline required after this
								$newLineRequired = '';
							}
							break;
					}
					break;
				case 'cdata':
					switch ($node['tag']) {
						case 'TEXT:P':
							// make sure we are in a cell
							if ($sectionCell) {
								$table[$rowID - 1][$cellID - 1] .= $this->styleHTML($this->styleTags($node), '') . $newLineRequired . $this->HSCtext($node['value']) . $this->styleHTML($this->styleTags($node), '/');
								// no newline required after this
								$newLineRequired = '';
							}
							break;
					}
					break;
				case 'close':
					switch ($node['tag']) {
						case 'TABLE:TABLE-HEADER-ROWS':
							$sectionHeader--;
							break;
						case 'TABLE:TABLE-ROW':
							// skip section header, we only look at the *contents* of the table
							if (!$sectionHeader) {
								$sectionRow--;
							}
							break;
						case 'TABLE:TABLE-CELL':
							// skip section header, we only look at the *contents* of the table
							if (!$sectionHeader) {
								$sectionCell--;
							}
							break;
						case 'TEXT:P':
							$sectionP--;
							// after a paragraph, require a new-line
							$newLineRequired = '<br>';
							$table[$rowID - 1][$cellID - 1] .= $this->styleHTML($this->styleTags($latestTEXTPopen), '/');
							break;
					}
					break;
			}
			$id = $id + 1;
		}
		return $table;
	}

	/**
	 * Load the contents of the table into the SQL database
	 *
	 * @param string $extension Name of the extension to load the documentation for. This is used to make the unique hash in the database
	 * @param array $table Contents of the documentation table
	 * @param string $tableName Name of the table from the source document (name at the bottom of the table in OpenOffice)
	 * @return boolean TRUE on success and FALSE on failure from the INSERT database query
	 * @todo Define visibility
	 */
	public function dumpIntoSQL($extension, $table, $tableName) {
		global $uid;
		foreach ($table as $row) {
			$tempArray = array();
			$tempArray['property'] = $row[0];
			// in the case there are only 2 columns, the second one is the description !
			$tempArray['datatype'] = count($row) == 2 ? '&nbsp;' : $row[1];
			// in the case there are only 2 columns, the second one is the description !
			$tempArray['description'] = count($row) == 2 ? $row[1] : $row[2];
			$tempArray['default'] = $row[3];
			$tempArray['column_count'] = count($row);
			$tempArray['is_propertyTable'] = 1;
			$tsHelpArray['rows'][] = $tempArray;
		}
		$appdata = serialize($tsHelpArray);
		$obj_string = trim($tableName, '[]');
		if (isset($this->objStringsPerExtension[$obj_string])) {
			if (isset($this->objStringsPerExtension[$obj_string][$extension])) {
				$this->objStringsPerExtension[$obj_string][$extension]++;
			} else {
				$this->objStringsPerExtension[$obj_string][$extension] = 1;
			}
		} else {
			$this->objStringsPerExtension[$obj_string] = array();
			$this->objStringsPerExtension[$obj_string][$extension] = 1;
		}
		// If the obj_string was already encountered increase its counter. If not initialise it as 0
		// The counter (when bigger than 0) is appended to the obj_string to make it unique
		// This way the tables do not overwrite each other in the online help
		if (isset($this->allObjStrings[$obj_string])) {
			$this->allObjStrings[$obj_string]++;
			$obj_string .= ' (' . $this->allObjStrings[$obj_string] . ')';
		} else {
			$this->allObjStrings[$obj_string] = 0;
		}
		$md5hash = md5($obj_string);
		// unused
		$description = '';
		// try to find a way to uniquely identify the source extension and place the identified into the "guide" column
		$guide = hexdec(substr(md5($extension), 6, 6));
		// unused
		$title = '';
		$insertFields = array(
			'guide' => $guide,
			'md5hash' => $md5hash,
			'description' => $description,
			'obj_string' => $obj_string,
			'appdata' => $appdata,
			'title' => $title
		);
		return $GLOBALS['TYPO3_DB']->exec_INSERTquery('static_tsconfig_help', $insertFields);
	}

	/**
	 * Purges the existing contents for TypoScript help in the database. This ensures that several runs of the import process will not push
	 * duplicate information in the database, but that we clean it first before adding new contents.
	 *
	 * @param string $extension Name of the extension for which to delete all the data in the database. If empty, all database will be cleaned
	 * @return void
	 * @todo Define visibility
	 */
	public function purgeSQLContents($extension = '') {
		$guide = hexdec(substr(md5($extension), 6, 6));
		if ($extension != '') {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('static_tsconfig_help', 'guide=' . $guide);
		} else {
			$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('static_tsconfig_help');
		}
	}

	/**
	 * This is the main function of the loading process. It will first parse the input data and load it into an XML array. It will then find all
	 * the styles associated with the contents so that later on we can distinguish bold and italic characters for example. It then parses the XML
	 * array to find all the TS-like description tables and parses them before loading them into the SQL database.
	 *
	 * @param string $extension Name of the extension to load manual from
	 * @param string $contents Input data from the manual.sxw in a string form. One large string with the whole OO manual document.
	 * @return integer Number of individual tables found in the document and loaded into the SQL database
	 * @todo Define visibility
	 */
	public function loadExtensionManual($extension, $contents) {
		global $Styles;
		// Read the contents into an XML array
		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $contents, $vals, $index);
		xml_parser_free($parser);
		// Parse styles from the manual for future rendering
		$Styles = $this->parseStyles($vals);
		$id = 0;
		$tableNumber = 0;
		do {
			list($tableStart, $tableEnd) = $this->nextTSDefinitionTable($vals, $index, $id);
			if ($tableStart !== FALSE) {
				// The title of the table can either be self-contained in a single complete entry
				if (!strcmp($vals[$id]['type'], 'complete')) {
					$title = $vals[$id]['value'];
				} else {
					// or it can be spread across a number of spans or similar
					$watchTag = $vals[$id]['tag'];
					$title = '';
					while (strcmp($vals[$id]['tag'], $watchTag) || strcmp($vals[$id]['type'], 'close')) {
						$title .= $vals[$id++]['value'];
					}
				}
				$tableContents = $this->parseTable($vals, $tableStart, $tableEnd);
				$this->dumpIntoSQL($extension, $tableContents, $title);
				$tableNumber++;
			}
		} while ($tableStart !== FALSE);
		return $tableNumber;
	}

}

?>