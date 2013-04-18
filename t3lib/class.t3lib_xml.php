<?php
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
 * XML class, Used to create XML output from input rows.
 * Doesn't contain a lot of advanced features - pretty straight forward, practical stuff
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @deprecated since TYPO3 6.0. It will be removed with TYPO3 6.2, please use other xml helper solutions instead.
 */
class t3lib_xml {

	// Top element name
	public $topLevelName = 'typo3_test';

	// Contains a list of fields for each table which should be presented in the XML output
	public $XML_recFields = array();

	public $XMLIndent = 0;

	public $Icode = '';

	public $XMLdebug = 0;

	// If set, all fields from records are rendered no matter their content. If not set, only 'true' (that is '' or zero) fields make it to the document.
	public $includeNonEmptyValues = 0;

	public $lines = array();

	/**
	 * Constructor, setting topLevelName to the input var
	 *
	 * @param string $topLevelName Top Level Name
	 * @return void
	 */
	public function __construct($topLevelName) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('Class t3lib_xml is deprecated since TYPO3 6.0. It will be removed with TYPO3 6.2, please use other xml helper solutions instead.');
		$this->topLevelName = $topLevelName;
	}

	/**
	 * When outputting a input record in XML only fields listed in $this->XML_recFields for the current table will be rendered.
	 *
	 * @param string $table Table name
	 * @param string $list Commalist of fields names from the table, $table, which is supposed to be rendered in the XML output. If a field is not in this list, it is not rendered.
	 * @return void
	 */
	public function setRecFields($table, $list) {
		$this->XML_recFields[$table] = $list;
	}

	/**
	 * Returns the result of the XML rendering, basically this is imploding the internal ->lines array with linebreaks.
	 *
	 * @return string
	 */
	public function getResult() {
		$content = implode(LF, $this->lines);
		return $this->output($content);
	}

	/**
	 * Initialize WML (WAP) document with <?xml + <!DOCTYPE header tags and setting ->topLevelName as the first level.
	 *
	 * @return void
	 */
	public function WAPHeader() {
		$this->lines[] = '<?xml version="1.0"?>';
		$this->lines[] = '<!DOCTYPE ' . $this->topLevelName . ' PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">';
		$this->newLevel($this->topLevelName, 1);
	}

	/**
	 * Initialize "anonymous" XML document with <?xml + <!DOCTYPE header tags and setting ->topLevelName as the first level.
	 * Encoding is set to UTF-8!
	 *
	 * @return void
	 */
	public function renderHeader() {
		$this->lines[] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$this->lines[] = '<!DOCTYPE ' . $this->topLevelName . '>';
		$this->newLevel($this->topLevelName, 1);
	}

	/**
	 * Sets the footer (of ->topLevelName)
	 *
	 * @return void
	 */
	public function renderFooter() {
		$this->newLevel($this->topLevelName, 0);
	}

	/**
	 * Indents/Outdents a new level named, $name
	 *
	 * @param string $name The name of the new element for this level
	 * @param boolean $beginEndFlag If FALSE, then this function call will *end* the level, otherwise create it.
	 * @param array $params Array of attributes in key/value pairs which will be added to the element (tag), $name
	 * @return void
	 */
	public function newLevel($name, $beginEndFlag = 0, $params = array()) {
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
	 * @param string The XML content to output
	 * @return string Output
	 */
	public function output($content) {
		if ($this->XMLdebug) {
			return '<pre>' . htmlspecialchars($content) . '</pre>
			<hr /><font color="red">Size: ' . strlen($content) . '</font>';
		} else {
			return $content;
		}
	}

	/**
	 * Increments/Decrements Indentation counter, ->XMLIndent
	 * Sets and returns ->Icode variable which is a line prefix consisting of a number of tab-chars corresponding to the indent-levels of the current position (->XMLindent)
	 *
	 * @param boolean $b If TRUE the XMLIndent var is increased, otherwise decreased
	 * @return string ->Icode - the prefix string with TAB-chars.
	 */
	public function indent($b) {
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
	 * @param string $table Tablename
	 * @param boolean|\mysqli_result|object $res MySQLi result object / DBAL object
	 * @return void
	 */
	public function renderRecords($table, $res) {
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->addRecord($table, $row);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * Adds record, $row, from table, $table, to the internal array of XML-lines
	 *
	 * @param string $table Table name
	 * @param array $row The row to add to XML structure from the table name
	 * @return void
	 */
	public function addRecord($table, $row) {
		$this->lines[] = $this->Icode . '<' . $table . ' uid="' . $row['uid'] . '">';
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
	 * @param string $table Table name
	 * @param array $row Row from table to add.
	 * @return void
	 * @access private
	 */
	public function getRowInXML($table, $row) {
		$fields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->XML_recFields[$table], 1);
		foreach ($fields as $field) {
			if ($row[$field] || $this->includeNonEmptyValues) {
				$this->lines[] = $this->Icode . $this->fieldWrap($field, $this->substNewline($this->utf8(htmlspecialchars($row[$field]))));
			}
		}
	}

	/**
	 * UTF-8 encodes the input content (from ISO-8859-1!)
	 *
	 * @param string $content String content to UTF-8 encode
	 * @return string Encoded content.
	 */
	public function utf8($content) {
		return utf8_encode($content);
	}

	/**
	 * Substitutes LF characters with a '<newline/>' tag.
	 *
	 * @param string $string Input value
	 * @return string Processed input value
	 */
	public function substNewline($string) {
		return str_replace(LF, '<newline/>', $string);
	}

	/**
	 * Wraps the value in tags with element name, $field.
	 *
	 * @param string $field Fieldname from a record - will be the element name
	 * @param string $value Value from the field - will be wrapped in the elements.
	 * @return string The wrapped string.
	 */
	public function fieldWrap($field, $value) {
		return '<' . $field . '>' . $value . '</' . $field . '>';
	}

	/**
	 * Creates the BACK button for WAP documents
	 *
	 * @return void
	 */
	public function WAPback() {
		$this->newLevel('template', 1);
		$this->newLevel('do', 1, array('type' => 'accept', 'label' => 'Back'));
		$this->addLine('<prev/>');
		$this->newLevel('do');
		$this->newLevel('template');
	}

	/**
	 * Add a line to the internal XML structure (automatically prefixed with ->Icode.
	 *
	 * @param string Line to add to the $this->lines array
	 * @return void
	 */
	public function addLine($str) {
		$this->lines[] = $this->Icode . $str;
	}

}

?>