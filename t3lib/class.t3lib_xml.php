<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains class for creating XML output from records
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   86: class t3lib_xml
 *  102:	 function t3lib_xml($topLevelName)
 *  113:	 function setRecFields($table,$list)
 *  122:	 function getResult()
 *  132:	 function WAPHeader()
 *  144:	 function renderHeader()
 *  155:	 function renderFooter()
 *  167:	 function newLevel($name,$beginEndFlag=0,$params=array())
 *  192:	 function output($content)
 *  208:	 function indent($b)
 *  224:	 function renderRecords($table,$res)
 *  237:	 function addRecord($table,$row)
 *  255:	 function getRowInXML($table,$row)
 *  271:	 function utf8($content)
 *  281:	 function substNewline($string)
 *  292:	 function fieldWrap($field,$value)
 *  301:	 function WAPback()
 *  315:	 function addLine($str)
 *
 * TOTAL FUNCTIONS: 17
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * XML class, Used to create XML output from input rows.
 * Doesn't contain a lot of advanced features - pretty straight forward, practical stuff
 * You are encouraged to use this class in your own applications.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see user_xmlversion, user_wapversion
 */
class t3lib_xml {
	var $topLevelName = 'typo3_test'; // Top element name
	var $XML_recFields = array(); // Contains a list of fields for each table which should be presented in the XML output

	var $XMLIndent = 0;
	var $Icode = '';
	var $XMLdebug = 0;
	var $includeNonEmptyValues = 0; // if set, all fields from records are rendered no matter their content. If not set, only 'true' (that is '' or zero) fields make it to the document.
	var $lines = array();

	/**
	 * Constructor, setting topLevelName to the input var
	 *
	 * @param	string		Top Level Name
	 * @return	void
	 */
	function t3lib_xml($topLevelName) {
		$this->topLevelName = $topLevelName;
	}

	/**
	 * When outputting a input record in XML only fields listed in $this->XML_recFields for the current table will be rendered.
	 *
	 * @param	string		Table name
	 * @param	string		Commalist of fields names from the table, $table, which is supposed to be rendered in the XML output. If a field is not in this list, it is not rendered.
	 * @return	void
	 */
	function setRecFields($table, $list) {
		$this->XML_recFields[$table] = $list;
	}

	/**
	 * Returns the result of the XML rendering, basically this is imploding the internal ->lines array with linebreaks.
	 *
	 * @return	string
	 */
	function getResult() {
		$content = implode(LF, $this->lines);
		return $this->output($content);
	}

	/**
	 * Initialize WML (WAP) document with <?xml + <!DOCTYPE header tags and setting ->topLevelName as the first level.
	 *
	 * @return	void
	 */
	function WAPHeader() {
		$this->lines[] = '<?xml version="1.0"?>';
		$this->lines[] = '<!DOCTYPE ' . $this->topLevelName . ' PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">';
		$this->newLevel($this->topLevelName, 1);
	}

	/**
	 * Initialize "anonymous" XML document with <?xml + <!DOCTYPE header tags and setting ->topLevelName as the first level.
	 * Encoding is set to UTF-8!
	 *
	 * @return	void
	 */
	function renderHeader() {
		$this->lines[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$this->lines[] = '<!DOCTYPE ' . $this->topLevelName . '>';
		$this->newLevel($this->topLevelName, 1);
	}

	/**
	 * Sets the footer (of ->topLevelName)
	 *
	 * @return	void
	 */
	function renderFooter() {
		$this->newLevel($this->topLevelName, 0);
	}

	/**
	 * Indents/Outdents a new level named, $name
	 *
	 * @param	string		The name of the new element for this level
	 * @param	boolean		If false, then this function call will *end* the level, otherwise create it.
	 * @param	array		Array of attributes in key/value pairs which will be added to the element (tag), $name
	 * @return	void
	 */
	function newLevel($name, $beginEndFlag = 0, $params = array()) {
		if ($beginEndFlag) {
			$pList = '';
			if (count($params)) {
				$par = array();
				foreach ($params as $key => $val) {
					$par[] = $key . '="' . htmlspecialchars($val) . '"';
				}
				$pList = ' ' . implode(' ', $par);
			}
			$this->lines[] = $this->Icode . '<' . $name . $pList . '>';
			$this->indent(1);
		} else {
			$this->indent(0);
			$this->lines[] = $this->Icode . '</' . $name . '>';
		}
	}

	/**
	 * Function that will return the content from string $content. If the internal ->XMLdebug flag is set the content returned will be formatted in <pre>-tags
	 *
	 * @param	string		The XML content to output
	 * @return	string		Output
	 */
	function output($content) {
		if ($this->XMLdebug) {
			return '<pre>' . htmlspecialchars($content) . '</pre>
			<hr /><font color="red">Size: ' . strlen($content) . '</font>';
		} else {
			return $content;
		}
	}

	/**
	 * Increments/Decrements Indentation counter, ->XMLIndent
	 * Sets and returns ->Icode variable which is a line prefix consisting of a number of tab-chars corresponding to the indent-levels of the current posision (->XMLindent)
	 *
	 * @param	boolean		If true the XMLIndent var is increased, otherwise decreased
	 * @return	string		->Icode - the prefix string with TAB-chars.
	 */
	function indent($b) {
		if ($b) {
			$this->XMLIndent++;
		} else {
			$this->XMLIndent--;
		}
		$this->Icode = '';
		for ($a = 0; $a < $this->XMLIndent; $a++) {
			$this->Icode .= TAB;
		}
		return $this->Icode;
	}

	/**
	 * Takes a SQL result for $table and traverses it, adding rows
	 *
	 * @param	string		Tablename
	 * @param	pointer		SQL resource pointer, should be reset
	 * @return	void
	 */
	function renderRecords($table, $res) {
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->addRecord($table, $row);
		}
	}

	/**
	 * Adds record, $row, from table, $table, to the internal array of XML-lines
	 *
	 * @param	string		Table name
	 * @param	array		The row to add to XML structure from the table name
	 * @return	void
	 */
	function addRecord($table, $row) {
		$this->lines[] = $this->Icode . '<' . $table . ' uid="' . $row["uid"] . '">';
		$this->indent(1);
		$this->getRowInXML($table, $row);
		$this->indent(0);
		$this->lines[] = $this->Icode . '</' . $table . '>';
	}

	/**
	 * Internal function for adding the actual content of the $row from $table to the internal structure.
	 * Notice that only fields from $table that are listed in $this->XML_recFields[$table] (set by setRecFields()) will be rendered (and in the order found in that array!)
	 * Content from the row will be htmlspecialchar()'ed, UTF-8 encoded and have LF (newlines) exchanged for '<newline/>' tags. The element name for a value equals the fieldname from the record.
	 *
	 * @param	string		Table name
	 * @param	array		Row from table to add.
	 * @return	void
	 * @access private
	 */
	function getRowInXML($table, $row) {
		$fields = t3lib_div::trimExplode(',', $this->XML_recFields[$table], 1);
		foreach ($fields as $field) {
			if ($row[$field] || $this->includeNonEmptyValues) {
				$this->lines[] = $this->Icode . $this->fieldWrap($field, $this->substNewline($this->utf8(htmlspecialchars($row[$field]))));
			}
		}
	}

	/**
	 * UTF-8 encodes the input content (from ISO-8859-1!)
	 *
	 * @param	string		String content to UTF-8 encode
	 * @return	string		Encoded content.
	 */
	function utf8($content) {
		return utf8_encode($content);
	}

	/**
	 * Substitutes LF characters with a '<newline/>' tag.
	 *
	 * @param	string		Input value
	 * @return	string		Processed input value
	 */
	function substNewline($string) {
		return str_replace(LF, '<newline/>', $string);
	}

	/**
	 * Wraps the value in tags with element name, $field.
	 *
	 * @param	string		Fieldname from a record - will be the element name
	 * @param	string		Value from the field - will be wrapped in the elements.
	 * @return	string		The wrapped string.
	 */
	function fieldWrap($field, $value) {
		return '<' . $field . '>' . $value . '</' . $field . '>';
	}

	/**
	 * Creates the BACK button for WAP documents
	 *
	 * @return	void
	 */
	function WAPback() {
		$this->newLevel('template', 1);
		$this->newLevel('do', 1, array('type' => 'accept', 'label' => 'Back'));
		$this->addLine('<prev/>');
		$this->newLevel('do');
		$this->newLevel('template');
	}

	/**
	 * Add a line to the internal XML structure (automatically prefixed with ->Icode.
	 *
	 * @param	string		Line to add to the $this->lines array
	 * @return	void
	 */
	function addLine($str) {
		$this->lines[] = $this->Icode . $str;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_xml.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_xml.php']);
}
?>