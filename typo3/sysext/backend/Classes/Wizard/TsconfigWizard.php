<?php
namespace TYPO3\CMS\Backend\Wizard;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Script Class for rendering the TSconfig/TypoScript property browser.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TsconfigWizard {

	// Internal, dynamic:
	/**
	 * document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	// Content accumulation for the module.
	/**
	 * @todo Define visibility
	 */
	public $content;

	// Internal, static: GPvars
	// Wizard parameters, coming from TCEforms linking to the wizard.
	/**
	 * @todo Define visibility
	 */
	public $P;

	// "page", "tsref" or "beuser"
	/**
	 * @todo Define visibility
	 */
	public $mode;

	// Pointing to an entry in static_tsconfig_help to show.
	/**
	 * @todo Define visibility
	 */
	public $show;

	// Object path - for display.
	/**
	 * @todo Define visibility
	 */
	public $objString;

	// If set, the "mixed-field" is not shown and you can select only one property at a time.
	/**
	 * @todo Define visibility
	 */
	public $onlyProperty;

	/**
	 * Initialization of the class
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Check if the tsconfig_help extension is loaded - which is mandatory for this wizard to work.
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tsconfig_help', 1);
		// Init GPvars:
		$this->P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$this->mode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mode');
		$this->show = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('show');
		$this->objString = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('objString');
		$this->onlyProperty = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('onlyProperty');
		// Preparing some JavaScript code:
		if (!$this->areFieldChangeFunctionsValid()) {
			$this->P['fieldChangeFunc'] = array();
		}
		unset($this->P['fieldChangeFunc']['alert']);
		$update = '';
		foreach ($this->P['fieldChangeFunc'] as $k => $v) {
			$update .= '
			window.opener.' . $v;
		}
		// Init the document table object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->form = '<form action="" name="editform">';
		// Adding Styles (should go into stylesheet?)
		$this->doc->inDocStylesArray[] = '
			A:link {text-decoration: bold; color: ' . $this->doc->hoverColor . ';}
			A:visited {text-decoration: bold; color: ' . $this->doc->hoverColor . ';}
			A:active {text-decoration: bold; color: ' . $this->doc->hoverColor . ';}
			A:hover {color: ' . $this->doc->bgColor2 . '}
		';
		$this->doc->JScode .= $this->doc->wrapScriptTags('
			function checkReference_name() {	// Checks if the input field containing the name exists in the document
				if (window.opener && window.opener.document && window.opener.document.' . $this->P['formName'] . ' && window.opener.document.' . $this->P['formName'] . '["' . $this->P['itemName'] . '"] ) {
					return window.opener.document.' . $this->P['formName'] . '["' . $this->P['itemName'] . '"];
				}
			}
			function checkReference_value() {	// Checks if the input field containing the value exists in the document
				if (window.opener && window.opener.document && window.opener.document.' . $this->P['formName'] . ' && window.opener.document.' . $this->P['formName'] . '["' . $this->P['itemValue'] . '"] ) {
					return window.opener.document.' . $this->P['formName'] . '["' . $this->P['itemValue'] . '"];
				}
			}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$field,value: ...
	 * @return	[type]		...
	 */
			function setValue(field,value) {
				var nameField = checkReference_name();
				var valueField = checkReference_value();
				if (nameField) {
					if (valueField)	{	// This applies to the TS Object Browser module
						nameField.value=field;
						valueField.value=value;
					} else {		// This applies to the Info/Modify module and the Page TSconfig field
						if (value) {
							nameField.value=field+"="+value+"\\n"+nameField.value;
						} else {
							nameField.value=field+"\\n"+nameField.value;
						}
					}
					' . $update . '
					window.opener.focus();
				}
				close();
			}
			function getValue() {	// This is never used. Remove it?
				var field = checkReference_name();
				if (field) {
					return field.value;
				} else {
					close();
				}
			}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$cmd,objString: ...
	 * @return	[type]		...
	 */
			function mixerField(cmd,objString) {
				var temp;
				switch(cmd) {
					case "Indent":
						temp = str_replace("\\n","\\n  ","\\n"+document.editform.mixer.value);
						document.editform.mixer.value = temp.substr(1);
					break;
					case "Outdent":
						temp = str_replace("\\n  ","\\n","\\n"+document.editform.mixer.value);
						document.editform.mixer.value = temp.substr(1);
					break;
					case "Transfer":
						setValue(document.editform.mixer.value);
					break;
					case "Wrap":
						document.editform.mixer.value=objString+" {\\n"+document.editform.mixer.value+"\\n}";
					break;
				}
			}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$match,replace,string: ...
	 * @return	[type]		...
	 */
			function str_replace(match,replace,string) {
				var input = ""+string;
				var matchStr = ""+match;
				if (!matchStr)	{return string;}
				var output = "";
				var pointer=0;
				var pos = input.indexOf(matchStr);
				while (pos!=-1) {
					output+=""+input.substr(pointer, pos-pointer)+replace;
					pointer=pos+matchStr.length;
					pos = input.indexOf(match, pos+1);
				}
				output+=""+input.substr(pointer);
				return output;
			}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$show,objString: ...
	 * @return	[type]		...
	 */
			function jump(show, objString) {
				window.location.href = "' . \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('show' => '', 'objString' => '')) . '&show="+show+"&objString="+objString;
			}
		');
		// Start the page:
		$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('tsprop'));
	}

	/**
	 * Main function, rendering the content of the TypoScript property browser, including links to online resources
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Adding module content:
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('tsprop'), $this->browseTSprop($this->mode, $this->show), 0, 1);
		// Adding link to TSref:
		if ($this->mode == 'tsref') {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('tsprop_TSref'), '
			<a href="' . TYPO3_URL_DOCUMENTATION_TSREF . '" target="_blank">' . $GLOBALS['LANG']->getLL('tsprop_TSref', 1) . '</a>
			', 0, 1);
		}
		// Adding link to admin guides etc:
		if ($this->mode == 'page' || $this->mode == 'beuser') {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('tsprop_tsconfig'), '
			<a href="' . TYPO3_URL_DOCUMENTATION_TSCONFIG . '" target="_blank">' . $GLOBALS['LANG']->getLL('tsprop_tsconfig', 1) . '</a>
			', 0, 1);
		}
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Create the content of the module:
	 *
	 * @param string $mode Object string
	 * @param integer $show Pointing to an entry in static_tsconfig_help to show.
	 * @return string HTML
	 * @todo Define visibility
	 */
	public function browseTSprop($mode, $show) {
		// Get object tree:
		$objTree = $this->getObjTree();
		// Show single element, if show is set.
		$out = '';
		if ($show) {
			// Get the entry data:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'static_tsconfig_help', 'uid=' . intval($show));
			$rec = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$table = unserialize($rec['appdata']);
			// Title:
			$obj_string = strtr($this->objString, '()', '[]');
			// Title and description:
			$out .= '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('show' => ''))) . '" class="typo3-goBack">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-back') . htmlspecialchars($obj_string) . '</a><br />';
			if ($rec['title']) {
				$out .= '<strong>' . htmlspecialchars($rec['title']) . ': </strong>';
			}
			if ($rec['description']) {
				$out .= nl2br(htmlspecialchars(trim($rec['description']))) . '<br />';
			}
			// Printing the content:
			$out .= '<br />' . $this->printTable($table, $obj_string, $objTree[($mode . '.')]);
			$out .= '<hr />';
			// Printing the "mixer-field":
			if (!$this->onlyProperty) {
				$links = array();
				$links[] = '<a href="#" onclick="mixerField(\'Indent\');return false;">' . $GLOBALS['LANG']->getLL('tsprop_mixer_indent', 1) . '</a>';
				$links[] = '<a href="#" onclick="mixerField(\'Outdent\');return false;">' . $GLOBALS['LANG']->getLL('tsprop_mixer_outdent', 1) . '</a>';
				$links[] = '<a href="#" onclick="mixerField(\'Wrap\',unescape(\'' . rawurlencode($obj_string) . '\'));return false;">' . $GLOBALS['LANG']->getLL('tsprop_mixer_wrap', 1) . '</a>';
				$links[] = '<a href="#" onclick="mixerField(\'Transfer\');return false;">' . $GLOBALS['LANG']->getLL('tsprop_mixer_transfer', 1) . '</a>';
				$out .= '<textarea rows="5" name="mixer" wrap="off"' . $this->doc->formWidthText(48, '', 'off') . ' class="fixed-font enable-tab"></textarea>';
				$out .= '<br /><strong>' . implode('&nbsp; | &nbsp;', $links) . '</strong>';
				$out .= '<hr />';
			}
		}
		// SECTION: Showing property tree:
		$tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('ext_TSparser');
		// Do not log time-performance information
		$tmpl->tt_track = 0;
		$tmpl->fixedLgd = 0;
		$tmpl->linkObjects = 0;
		$tmpl->bType = '';
		$tmpl->ext_expandAllNotes = 1;
		$tmpl->ext_noPMicons = 1;
		$tmpl->ext_noSpecialCharsOnLabels = 1;
		if (is_array($objTree[$mode . '.'])) {
			$out .= '


			<!--
				TSconfig, object tree:
			-->
				<table border="0" cellpadding="0" cellspacing="0" class="t3-tree t3-tree-config" id="typo3-objtree">
					<tr class="t3-row-header"><td>TSref</td></tr>
					<tr>
						<td nowrap="nowrap">' . $tmpl->ext_getObjTree($this->removePointerObjects($objTree[($mode . '.')]), '', '', '', '', '1') . '</td>
					</tr>
				</table>';
		}
		return $out;
	}

	/***************************
	 *
	 * Module functions
	 *
	 ***************************/
	/**
	 * Create object tree from static_tsconfig_help table
	 *
	 * @return array Object tree.
	 * @access private
	 * @todo Define visibility
	 */
	public function getObjTree() {
		$objTree = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,obj_string,title', 'static_tsconfig_help', '');
		while ($rec = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$rec['obj_string'] = $this->revertFromSpecialChars($rec['obj_string']);
			$p = explode(';', $rec['obj_string']);
			foreach ($p as $v) {
				$p2 = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $v, 1);
				$subp = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('/', $p2[1], 1);
				foreach ($subp as $v2) {
					$this->setObj($objTree, explode('.', $p2[0] . '.' . $v2), array($rec, $v2));
				}
			}
		}
		return $objTree;
	}

	/**
	 * Sets the information from a static_tsconfig_help record in the object array.
	 * Makes recursive calls.
	 *
	 * @param array $objTree Object tree array, passed by value!
	 * @param array $strArr Array of elements from object path (?)
	 * @param array $params Array with record and something else (?)
	 * @return void
	 * @access private
	 * @see getObjTree()
	 * @todo Define visibility
	 */
	public function setObj(&$objTree, $strArr, $params) {
		$key = current($strArr);
		reset($strArr);
		if (count($strArr) > 1) {
			array_shift($strArr);
			if (!isset($objTree[($key . '.')])) {
				$objTree[$key . '.'] = array();
			}
			$this->setObj($objTree[$key . '.'], $strArr, $params);
		} else {
			$objTree[$key] = $params;
			$objTree[$key]['_LINK'] = $this->doLink($params);
		}
	}

	/**
	 * Converts &gt; and &lt; to > and <
	 *
	 * @param string $str Input string
	 * @return string Output string
	 * @access private
	 * @todo Define visibility
	 */
	public function revertFromSpecialChars($str) {
		$str = str_replace('&gt;', '>', $str);
		$str = str_replace('&lt;', '<', $str);
		return $str;
	}

	/**
	 * Creates a link based on input params array:
	 *
	 * @param array $params Parameters
	 * @return string The link.
	 * @access private
	 * @todo Define visibility
	 */
	public function doLink($params) {
		$title = trim($params[0]['title']) ? trim($params[0]['title']) : '[GO]';
		$str = $this->linkToObj($title, $params[0]['uid'], $params[1]);
		return $str;
	}

	/**
	 * Remove pointer strings from an array
	 *
	 * @param array $objArray Input array
	 * @return array Modified input array
	 * @access private
	 * @todo Define visibility
	 */
	public function removePointerObjects($objArray) {
		foreach ($objArray as $k => $value) {
			if (substr(trim($k), 0, 2) == '->' && trim($k) != '->.') {
				$objArray['->.'][substr(trim($k), 2)] = $objArray[$k];
				unset($objArray[$k]);
			}
		}
		return $objArray;
	}

	/**
	 * Linking string to object by UID
	 *
	 * @param string $str String to link
	 * @param integer $uid UID of a static_tsconfig_help record.
	 * @param string $objString Title string for that record!
	 * @return string Linked string
	 * @todo Define visibility
	 */
	public function linkToObj($str, $uid, $objString = '') {
		$aOnClick = 'jump(\'' . rawurlencode($uid) . '\',\'' . rawurlencode($objString) . '\');return false;';
		return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . htmlspecialchars($str) . '</a>';
	}

	/**
	 * Creates a table of properties:
	 *
	 * @param array $table Array with properties for the current object path
	 * @param string $objString Object path
	 * @param array $objTree Object tree
	 * @return string HTML content.
	 * @todo Define visibility
	 */
	public function printTable($table, $objString, $objTree) {
		if (is_array($table['rows'])) {
			// Initialize:
			$lines = array();
			// Adding header:
			$lines[] = '
				<tr class="t3-row-header">
					<td>Property:</td>
					<td>Data type:</td>
					<td>Description:</td>
					<td>Default:</td>
				</tr>';
			// Traverse the content of "rows":
			foreach ($table['rows'] as $i => $row) {
				// Linking:
				$lP = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $row['property'], 1);
				$lP2 = array();
				foreach ($lP as $k => $lStr) {
					$lP2[$k] = $this->linkProperty($lStr, $lStr, $objString, $row['datatype']);
				}
				$linkedProperties = implode('<hr />', $lP2);
				// Data type:
				$dataType = $row['datatype'];
				// Generally "->[something]"
				$reg = array();
				preg_match('/->[[:alnum:]_]*/', $dataType, $reg);
				if ($reg[0] && is_array($objTree[$reg[0]])) {
					$dataType = str_replace($reg[0], '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('show' => $objTree[$reg[0]][0]['uid'], 'objString' => ($objString . '.' . $lP[0])))) . '">' . htmlspecialchars($reg[0]) . '</a>', $dataType);
				}
				// stdWrap
				if (!strstr($dataType, '->stdWrap') && strstr(strip_tags($dataType), 'stdWrap')) {
					// Potential problem can be that "stdWrap" is substituted inside another A-tag. So maybe we should even check if there is already a <A>-tag present and if so, not make a substitution?
					$dataType = str_replace('stdWrap', '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('show' => $objTree['->stdWrap'][0]['uid'], 'objString' => ($objString . '.' . $lP[0])))) . '">stdWrap</a>', $dataType);
				}
				$lines[] = '
					<tr class="t3-row ' . ($i % 2 ? 't3-row-even' : 't3-row-odd') . '">
						<td valign="top" class="bgColor4-20" nowrap="nowrap"><strong>' . $linkedProperties . '</strong></td>
						<td valign="top">' . nl2br(($dataType . '&nbsp;')) . '</td>
						<td valign="top">' . nl2br($row['description']) . '</td>
						<td valign="top">' . nl2br($row['default']) . '</td>
					</tr>';
			}
			// Return it all:
			return '



			<!--
				TSconfig, attribute selector:
			-->
				<table border="0" cellpadding="0" cellspacing="1" width="98%" class="t3-table" id="typo3-attributes">
					' . implode('', $lines) . '
				</table>';
		}
	}

	/**
	 * Creates a link on a property.
	 *
	 * @param string $str String to link
	 * @param string $propertyName Property value.
	 * @param string $prefix Object path prefix to value
	 * @param string $datatype Data type
	 * @return string Linked $str
	 * @todo Define visibility
	 */
	public function linkProperty($str, $propertyName, $prefix, $datatype) {
		$out = '';
		// Setting preset value:
		if (strstr($datatype, 'boolean')) {
			// preset "1" to boolean values.
			$propertyVal = '1';
		}
		// Adding mixer features; The plus icon:
		if (!$this->onlyProperty) {
			$aOnClick = 'document.editform.mixer.value=unescape(\'  ' . rawurlencode(($propertyName . '=' . $propertyVal)) . '\')+\'\\n\'+document.editform.mixer.value; return false;';
			$out .= '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-add', array('title' => $GLOBALS['LANG']->getLL('tsprop_addToList', TRUE))) . '</a>';
			$propertyName = $prefix . '.' . $propertyName;
		}
		// Wrap string:
		$aOnClick = 'setValue(unescape(\'' . rawurlencode($propertyName) . '\'), unescape(\'' . rawurlencode($propertyVal) . '\')); return false;';
		$out .= '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $str . '</a>';
		// Return link:
		return $out;
	}

	/**
	 * Determines whether submitted field change functions are valid
	 * and are coming from the system and not from an external abuse.
	 *
	 * @return boolean Whether the submitted field change functions are valid
	 */
	protected function areFieldChangeFunctionsValid() {
		return isset($this->P['fieldChangeFunc']) && is_array($this->P['fieldChangeFunc']) && isset($this->P['fieldChangeFuncHash']) && $this->P['fieldChangeFuncHash'] === \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(serialize($this->P['fieldChangeFunc']));
	}

}


?>