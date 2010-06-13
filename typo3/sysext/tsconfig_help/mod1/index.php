<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Stephane Schitter <stephane.schitter@free.fr>
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


// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
require_once($BACK_PATH.'mod/tools/em/class.em_unzip.php');

$LANG->includeLLFile('EXT:tsconfig_help/mod1/locallang.xml');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
// DEFAULT initialization of a module [END]


/**
 * Module 'TypoScript Help' for the 'tsconfig_help' extension.
 *
 * @author	Stephane Schitter <stephane.schitter@free.fr>
 * @package	TYPO3
 * @subpackage	tx_tsconfighelp
 */
class tx_tsconfighelp_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $objStringsPerExtension = array(); // This is used to count how many times the same obj_string appears in each extension manual
	var $allObjStrings = array(); // This is used to count how many times the same obj_string appears across all extensions

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		parent::init();
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = array(
			'function' => array(
				'1' => $LANG->getLL('display')
			)
		);

		if($GLOBALS['BE_USER']->user['admin'])	{
			$this->MOD_MENU['function']['2'] = $LANG->getLL('rebuild');
		}

		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		$access = $BE_USER->check('modules', 'help_txtsconfighelpM1');

			// Draw the header.
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/tsconfig_help.html');

		if ($access || $BE_USER->user['admin'])	{

			$this->doc->form = '<form action="" method="post">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
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

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'],-50);

			$this->content .= $this->doc->header($LANG->getLL('title'));
			$this->content .= $this->doc->spacer(5);

			// Render content:
			$this->moduleContent();

			$this->content .= $this->doc->spacer(10);

			$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']);
		} else {
			$this->content .= $this->doc->header($LANG->getLL('title'));
			$markers['FUNC_MENU'] = '';
		}
			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage($LANG->getLL('title'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{
		global $TCA, $LANG, $BACK_PATH, $BE_USER;

		$buttons = array(
			'csh' => '',
			'shortcut' => '',
		);
			// CSH
		//$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

		if (($this->id && is_array($this->pageinfo)) || ($BE_USER->user['admin'] && !$this->id)) {
				// Shortcut
			if ($BE_USER->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
			}
		}
		return $buttons;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		global $BACK_PATH, $TYPO3_LOADED_EXT, $LANG;

		switch ((string)$this->MOD_SETTINGS['function'])	{
			case 1:
				$content = '<div align="left"><strong>'.$LANG->getLL('referenceExplanation').'</strong></div>';
				$content .= '<p>'.$LANG->getLL('referenceExplanationDetailed').'</p><br />';
				$this->content .= $this->doc->section($LANG->getLL('displayReferences'),$content,0,1);
				$this->content .= '<a href="#" onclick="vHWin=window.open(\''.$BACK_PATH.'wizard_tsconfig.php?mode=tsref&amp;P[formName]=editForm\',\'popUp\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;" title="TSref reference">'.t3lib_iconWorks::getSpriteIcon('actions-system-typoscript-documentation-open').'TSREF</a><br />';
				$this->content .= '<a href="#" onclick="vHWin=window.open(\''.$BACK_PATH.'wizard_tsconfig.php?mode=beuser&amp;P[formName]=editForm\',\'popUp\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;" title="TSref reference">'.t3lib_iconWorks::getSpriteIcon('actions-system-typoscript-documentation-open').'USER TSCONFIG</a><br />';
				$this->content .= '<a href="#" onclick="vHWin=window.open(\''.$BACK_PATH.'wizard_tsconfig.php?mode=page&amp;P[formName]=editForm\',\'popUp\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;" title="TSref reference">'.t3lib_iconWorks::getSpriteIcon('actions-system-typoscript-documentation-open').'PAGE TSCONFIG</a><br />';
			break;

			case 2:
				if ($GLOBALS['BE_USER']->user['admin'])	{
					if (t3lib_div::_GP('_rebuild'))	{
							// remove all data from the database
						$this->purgeSQLContents();

							// get all loaded extension keys
						$extArray = $TYPO3_LOADED_EXT;

						$content = '<div align="left"><strong>'.$LANG->getLL('loadedTSfrom').'</strong></div><br />';

							// parse the extension names only (no need for all details from the TYPO3_LOADED_EXT table
						foreach ($extArray as $extName => $dummy)	{
								// check that the extension is really loaded (which should always be the case)
							if (t3lib_extMgm::isLoaded($extName))	{
									// extract the content.xml from the manual.sxw ZIP file
								$manual = $this->getZIPFileContents(t3lib_extMgm::extPath($extName).'doc/manual.sxw', 'content.xml');

									// check if the manual file actually exists and if the content.xml could be loaded
								if ($manual != '')	{
										// if the manual file exists, proceed with the load into the SQL database
									$content .= '<p>Extension '.$extName.'...';

										// run the extraction processing and import the data into SQL. Return the number of TS tables found in the open office document
									$number = $this->loadExtensionManual($extName, $manual);

										// print a status message with a link to the openoffice manual
									$content .= $number.' '.$LANG->getLL('sections').' (<a href="'.t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir.t3lib_extMgm::extRelPath($extName).'doc/manual.sxw">manual</a>)</p>';
								}
							} else	{
									// this should never happen!
								die ("Fatal error : loaded extension not actually loaded? Please file a bug report at http://bugs.typo3.org!");
							}
						}

						$this->content .= $this->doc->section($LANG->getLL('rebuildTS'),$content.'<br />',0,1);

							// Issue warnings about duplicate or empty obj_strings, if any
							// An obj_string should be unique. It should appear in only one extension manual and then only once
							// If the sum of all occurrences of a given obj_string is more than one, issue a list of duplicate entries as a warning
						$duplicateWarnings = '';
						$emptyWarnings = '';
						foreach ($this->objStringsPerExtension as $obj_string => $extensions)	{
							if (empty($obj_string))	{
								$emptyWarnings = '<p class="typo3-red">'.$LANG->getLL('warning_manualsWithoutMarkers');
								foreach ($extensions as $extensionKey => $counter)	{
									$emptyWarnings .= ' '.$extensionKey.' ('.$counter.')<br />';
								}
								$emptyWarnings .= '</p><br />';
							} else {
								if (array_sum($extensions) > 1)	{
									$duplicateWarnings .= $obj_string.':';
									foreach ($extensions as $extensionKey => $counter)	{
										$duplicateWarnings .= ' '.$extensionKey.' ('.$counter.')';
									}
									$duplicateWarnings .= '<br />';
								}
							}
						}
						$warnings = $emptyWarnings;
						if (!empty($duplicateWarnings))	{
							$warnings .= '<p class="typo3-red">'.$LANG->getLL('warning_duplicateMarkers').'<br />'.$duplicateWarnings.'</p>';
						}
						if (!empty($warnings))	{
							$this->content .= $this->doc->section($LANG->getLL('updateWarnings'),'<div>'.$warnings.'</div>',0,1);
						}
					}

					$content = '<p>'.$LANG->getLL('rebuildExplanation').'</p><br />';
					$content .= $LANG->getLL('rebuild').' <input type="submit" name="_rebuild" value="Rebuild" /><br />';
					$this->content .= $this->doc->section($LANG->getLL('rebuildTS'),$content,0,1);
				} else {
					$this->content .= '<p>'.$LANG->getLL('adminAccessOnly').'</p><br />';
				}


			break;
		}
	}

	/**
	 * Returns the contents of a specific file within the ZIP
	 *
	 * @return	string	contents
	 */
	function getZIPFileContents($ZIPfile, $filename)	{
		if (file_exists($ZIPfile))	{
				// Unzipping SXW file, getting filelist:
			$tempPath = PATH_site.'typo3temp/tx_tsconfighelp_ziptemp/';
			t3lib_div::mkdir($tempPath);

			$this->unzip($ZIPfile, $tempPath);
			$output = t3lib_div::getURL($tempPath.$filename);

			$cmd = 'rm -r "'.$tempPath.'"';
			exec($cmd);

			return $output;
		}
	}

	/**
	 * Unzips a zip file in the given path.
	 * Uses the Extension Manager unzip functions.
	 *
	 *
	 * @param string $file		Full path to zip file
	 * @param string $path		Path to change to before extracting
	 * @return boolean	True on success, false in failure
	 */
	function unzip($file, $path)	{
			// we use the unzip class of the Extension Manager here
		$unzip = t3lib_div::makeInstance('em_unzip', $file);
		$ret = $unzip->extract(array('add_path'=>$path));
		return (is_array($ret));
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
	 * @param	array		The XML values array. The XML index is not necessary in this function.
	 * @return	array		Array that contains the different styles with their parent (required to recognise "Table Contents"-type styles), and their style (bold/italic)
	 */
	function parseStyles($vals)	{
		$currentStyleName = '';
		$style = array ();

		foreach ($vals as $node)	{
			switch ($node['type'])	{
				case 'open':
					switch ($node['tag'])	{
						case 'STYLE:STYLE':
							$currentStyleName = $node['attributes']['STYLE:NAME'];

							if (array_key_exists('STYLE:PARENT-STYLE-NAME',$node['attributes']))	{
								$parentStyleName = $node['attributes']['STYLE:PARENT-STYLE-NAME'];
								$style[$currentStyleName]['parents'][] = $parentStyleName; // keep trace of parents in the style array
							} else {
								$parentStyleName = ''; // this style has no parent, therefore clean the variable to avoid side effects with next use of that variable
							}

							if (array_key_exists($parentStyleName, $style))	{ // the style parent is already documented in the array
								$style[$currentStyleName] = $style[$parentStyleName]; // inherit parent style
							}
						break;
					}
				break;

				case 'complete':
					switch ($node['tag'])	{
						case 'STYLE:PROPERTIES':
							if (is_array($node['attributes']) && array_key_exists('FO:FONT-WEIGHT',$node['attributes']))	{
								$style[$currentStyleName]['font-weight'] = $node['attributes']['FO:FONT-WEIGHT'];	// bold for example
							}
							if (is_array($node['attributes']) && array_key_exists('FO:FONT-STYLE',$node['attributes']))	{
								$style[$currentStyleName]['font-style'] = $node['attributes']['FO:FONT-STYLE'];	// italic for example
							}
						break;
					}
				break;

				case 'close':
					switch ($node['tag'])	{
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
	 * @param	string		Name of the child style that we want to get properties for
	 * @param	string		Name of the parent style that we want to compare the child against
	 * @return	boolean		true if the child and parent are linked together. false otherwise.
	 */
	function isStyleChildOf($child, $parent) {
		global $Styles;

		if (!strcmp($child, $parent))	{ // the child is actually the same as the parent. They are obviously linked together
			return TRUE;
		}

		if (is_array($Styles[$child])  // the child is a documented style
		 && array_key_exists('parents',$Styles[$child])  // it has some parents
		 && (array_search($parent, $Styles[$child]['parents']) !== FALSE))	{ // and the parent appears amongst its ancestors
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
	 * @param	array		The XML values array
	 * @param	array		The XML index array
	 * @param	integer		This is a reference to the index in the array where we should be starting the search
	 * @return	array		Array of the table start index and table end index where TS is defined. table start is FALSE if there are no more TS entries in the document (consider it similar to an EOF reached status).
	 */
	function nextTSDefinitionTable($vals, $index, &$id)	{
			// browse the table where we left off last time
		while ($id < count ($vals))	{
			$node = $vals[$id];
			if (!strcmp($node['type'], 'close') && !strcmp($node['tag'], 'TABLE:TABLE'))	{ // check if next entry is a candidate
				$nextNode = $vals[$id+1];
				if (!strcmp($nextNode['tag'], 'TEXT:P') && $this->isStyleChildOf($nextNode['attributes']['TEXT:STYLE-NAME'], 'Table Contents'))	{
						// we found a good entry
					$closeIndex = array_search($id, $index['TABLE:TABLE']);	// find the ID in the list of table items

					$tableStart = $index['TABLE:TABLE'][$closeIndex-1]; // find the matching start of the table in the $vals array

					return array($tableStart, $id++);
				}
			}
			$id = $id+1;
		}
		return array(FALSE, 0); // marks the end of the input, no more table to find. WARNING: needs to be tested with === FALSE
	}

	/**
	 * Converts an Open Office like style (font-weight:bold for example) into an HTML style (b is for bold). This function uses the global
	 * $Styles defined through the parseStyles function
	 *
	 * @param	array		an array containing the [attributes][style] items in the OO format
	 * @return	array		an array where the items are all the HTML styles to apply to closely match the input OO-like styles
	 */
	function styleTags($node)	{
		global $Styles;

		$styleName = $node['attributes']['TEXT:STYLE-NAME'];
		switch ($Styles[$styleName]['font-weight'])	{
			case 'bold':
				$styleTags[] = 'b';
			break;
		}
		switch ($Styles[$styleName]['font-style'])	{
			case 'italic':
				$styleTags[] = 'i';
			break;
		}
		if (!strcmp($styleName,'Table Contents/PRE'))	{
			//$styleTags[]='pre'; // unused yet, but could be <pre> in the future - this is for inline code in the manuals
		}
		return $styleTags;
	}

	/**
	 * Converts an array containing style strings (for example ['b','i']) into their HTML equivalents
	 *
	 * @param	array		an array containing all the style tags
	 * @param	string		either '' or '/' depending on whether the style definition is to open or close the style
	 * @return	string		the sequence of tags to open or close the style, for example <strong><i>
	 */
	function styleHTML($style, $char)	{
		$string = '';
		if (count ($style) > 0)	{
			foreach ($style as $tag)	{
				$string .= '<'.$char.$tag.'>';
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
	 * @param	string		Text that will need to be transformed according to the HSC and other rules
	 * @return	string		Transformed text that can now be freely serialized or exported to HTML
	 */
	function HSCtext($text) {
		global $LANG;

		if (strcmp($text,''))	{ // there is some content in the text field
			$cleantext = stripslashes(htmlspecialchars($text, ENT_QUOTES)); // stripslashes required as it could confuse unserialize
			return $LANG->csConvObj->utf8_to_entities($cleantext, $LANG->charSet);
		} else { // there is no text, it's empty
			return '&nbsp;';
		}
	}

	/**
	 * This function parses a Table from an Open Office document, in an XML format, and extracts the information. It will therefore crawl the
	 * XML tree and aggregate all the accepted contents into an array with the table contents.
	 *
	 * This function needs to extract the following information from the TABLE:
	 *   property => (column 1)
	 *   datatype => (column 2)
	 *   description => (column 3)
	 *   default => (column 4)
	 *   column_count => number of columns found in table. Usually 4, but for spanned columns, it would be less (1 for example)
	 *   is_propertyTable => ??? (almost always equal to 1)
	 *
	 * @param	array		This is the input XML data that is to be parsed
	 * @param	integer		The starting ID in the XML array to parse the data from
	 * @param	integer		The ending ID in the XML array to stop parsing data
	 * @return	array		An array with the contents of the different columns extracted from the input data
	 */
	function parseTable($vals, $start, $end) {
		$sectionHeader = 0;
		$sectionRow = 0;
		$sectionCell = 0;
		$sectionP = 0;

		$newLineRequired = ''; // this variable will either be empty (no newline required) or '\n' (newline required)
		$textStyle = array (); // this will be the list of tag styles to apply to the text

		$currentRow = 0;
		$currentCell = 0;

		$rowID = 0;
		$cellID = 0;  // also gets reset at every top-level row

		$table = array(); // will contain the results of the function

		$id = $start;
		while ($id < $end)	{
			$node = $vals[$id];

			// sanity check
			if ($sectionHeader < 0)	die ('Malformed XML (header-rows)'.LF);
			if ($sectionRow < 0)	die ('Malformed XML (row)'.LF);
			if ($sectionCell < 0)	die ('Malformed XML (cell)'.LF);
			if ($sectionP < 0)		die ('Malformed XML (P)'.LF);

			switch ($node['type'])	{
				case 'open':
					switch ($node['tag'])	{
						case 'TABLE:TABLE-HEADER-ROWS':
							$sectionHeader++;
						break;

						case 'TABLE:TABLE-ROW':
							if (!$sectionHeader)	{ // skip section header, we only look at the *contents* of the table
								$sectionRow++;
								if ($sectionRow == 1)	{ // make sure we are within a top-level row
									$rowID++;
									$cellID = 0;
								}
							}
						break;

						case 'TABLE:TABLE-CELL':
							if (!$sectionHeader)	{ // skip section header, we only look at the *contents* of the table
								$sectionCell++;
								if ($sectionCell == 1)	{ // make sure we are within a top-level cell
									$cellID++;
									$newLineRequired = ''; // no newline required after this
								}
							}
						break;

						case 'TEXT:P':
							if ($sectionCell)	{ // make sure we are in a cell
								$sectionP++;
								$table[$rowID-1][$cellID-1] .= $this->styleHTML($this->styleTags($node),'') . $newLineRequired.$this->HSCtext($node['value']);
								$newLineRequired = ''; // no newline required after this
								$latestTEXTPopen = $node;
							}
						break;
					}
				break;

				case 'complete':
					switch ($node['tag'])	{
						case 'TEXT:P':
							if ($sectionCell)	{ // make sure we are in a cell
								$table[$rowID-1][$cellID-1] .= $this->styleHTML($this->styleTags($node),'') . $newLineRequired.$this->HSCtext($node['value']).$this->styleHTML($this->styleTags($node),'/');
								$newLineRequired = '<br>'; // after a paragraph, require a new-line
							}
						break;

						case 'TEXT:SPAN':
							if ($sectionCell)	{ // make sure we are in a cell
								$table[$rowID-1][$cellID-1] .= $this->styleHTML($this->styleTags($node),'').$newLineRequired.$this->HSCtext($node['value']).$this->styleHTML($this->styleTags($node),'/');
								$newLineRequired = ''; // no newline required after this
							}
						break;

						case 'TEXT:S':
							if ($sectionCell)	{ // make sure we are in a cell
								for ($i=0; $i<$node['attributes']['TEXT:C']; $i++)	{
									$table[$rowID-1][$cellID-1] .= '&nbsp;';
								}
								$newLineRequired = ''; // no newline required after this
							}
						break;
					}
				break;

				case 'cdata':
					switch ($node['tag'])	{
						case 'TEXT:P':
							if ($sectionCell)	{ // make sure we are in a cell
								$table[$rowID-1][$cellID-1] .= $this->styleHTML($this->styleTags($node),'') . $newLineRequired.$this->HSCtext($node['value']).$this->styleHTML($this->styleTags($node),'/');
								$newLineRequired = ''; // no newline required after this
							}
						break;
					}
				break;

				case 'close':
					switch ($node['tag'])	{
						case 'TABLE:TABLE-HEADER-ROWS':
							$sectionHeader--;
						break;

						case 'TABLE:TABLE-ROW':
							if (!$sectionHeader)	{ // skip section header, we only look at the *contents* of the table
								$sectionRow--;
							}
						break;

						case 'TABLE:TABLE-CELL':
							if (!$sectionHeader)	{ // skip section header, we only look at the *contents* of the table
								$sectionCell--;
							}
						break;

						case 'TEXT:P':
							$sectionP--;
							$newLineRequired = '<br>'; // after a paragraph, require a new-line
							$table[$rowID-1][$cellID-1] .= $this->styleHTML($this->styleTags($latestTEXTPopen),'/');
						break;
					}
				break;
			}
			$id = $id+1;
		}
		return $table;
	}

	/**
	 * Load the contents of the table into the SQL database
	 *
	 * @param	string		Name of the extension to load the documentation for. This is used to make the unique hash in the database
	 * @param	array		Contents of the documentation table
	 * @param	string		Name of the table from the source document (name at the bottom of the table in OpenOffice)
	 * @return	boolean		TRUE on success and FALSE on failure from the INSERT database query
	 */
	function dumpIntoSQL($extension, $table, $tableName)	{
		global $uid;

		foreach ($table as $row)	{
			$tempArray = array();
			$tempArray['property'] = $row[0];

			$tempArray['datatype'] = count($row)==2 ? '&nbsp;':$row[1];	// in the case there are only 2 columns, the second one is the description !
			$tempArray['description'] = count($row)==2 ? $row[1]:$row[2];  // in the case there are only 2 columns, the second one is the description !
			$tempArray['default'] = $row[3];
			$tempArray['column_count'] = count($row);
			$tempArray['is_propertyTable'] = 1;
			$tsHelpArray['rows'][] = $tempArray;
		}
		$appdata = serialize($tsHelpArray);
		$obj_string = trim($tableName, '[]');

		if (isset($this->objStringsPerExtension[$obj_string]))	{
			if (isset($this->objStringsPerExtension[$obj_string][$extension]))	{
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
		if (isset($this->allObjStrings[$obj_string]))	{
			$this->allObjStrings[$obj_string]++;
			$obj_string .= ' ('.$this->allObjStrings[$obj_string].')';
		} else {
			$this->allObjStrings[$obj_string] = 0;
		}
		$md5hash = md5($obj_string);
		$description = ''; // unused
		$guide = hexdec(substr(md5($extension),6,6));  // try to find a way to uniquely identify the source extension and place the identified into the "guide" column
		$title = ''; // unused

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
	 * @param	string		Name of the extension for which to delete all the data in the database. If empty, all database will be cleaned
	 * @return	void
	 */
	function purgeSQLContents($extension='')	{
		$guide = hexdec(substr(md5($extension), 6, 6));
		if ($extension != '')	{
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('static_tsconfig_help', 'guide='.$guide);
		} else {
			$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('static_tsconfig_help');
		}
	}

	/**
	 * This is the main function of the loading process. It will first parse the input data and load it into an XML array. It will then find all
	 * the styles associated with the contents so that later on we can distinguish bold and italic characters for example. It then parses the XML
	 * array to find all the TS-like description tables and parses them before loading them into the SQL database.
	 *
	 * @param	string		Name of the extension to load manual from
	 * @param	string		Input data from the manual.sxw in a string form. One large string with the whole OO manual document.
	 * @return	integer		Number of individual tables found in the document and loaded into the SQL database
	 */
	function loadExtensionManual($extension, $contents)	{
		global $Styles;

			// read the contents into an XML array
		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $contents, $vals, $index);

		xml_parser_free($parser);

			// parse styles from the manual for future rendering
		$Styles = $this->parseStyles($vals);

		$id = 0;
		$tableNumber = 0;
		do {
			list($tableStart, $tableEnd) = $this->nextTSDefinitionTable($vals, $index, $id);
			if ($tableStart !== FALSE)	{
					// The title of the table can either be self-contained in a single complete entry
				if (!strcmp($vals[$id]['type'], 'complete'))	{
					$title = $vals[$id]['value'];
				} else { // or it can be spread across a number of spans or similar
					$watchTag = $vals[$id]['tag'];
					$title = '';
					while (strcmp($vals[$id]['tag'], $watchTag) || strcmp($vals[$id]['type'], 'close'))	{
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


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsconfig_help/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsconfig_help/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_tsconfighelp_module1');
$SOBE->init();

// Include files?
foreach ($SOBE->include_once as $INC_FILE) include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
