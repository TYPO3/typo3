<?php
/* **************************************************************
*  Copyright notice
*
*  (c) 2006 Karsten Dambekalns <karsten@typo3.org>
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
 * XML handling class for the TYPO3 Extension Manager.
 *
 * It contains methods for handling the XML files involved with the EM,
 * such as the list of extension mirrors and the list of available extensions.
 *
 * @author Karsten Dambekalns <karsten@typo3.org>
 * @package TYPO3
 * @subpackage EM
 */
class SC_mod_tools_em_xmlhandler {
	/**
	 * Holds the parsed XML from extensions.xml.gz
	 * @see parseExtensionsXML()
	 *
	 * @var array
	 */
	var $emObj;
	var $extXMLResult = array();
	var $extensionsXML = array();
	var $reviewStates = null;
	var $useUnsupported = false;
	var $useObsolete = false;

	/**
	 * Reduces the entries in $this->extensionsXML to the latest version per extension and removes entries not matching the search parameter
	 *
	 * @param	string		$search The list of extensions is reduced to entries matching this. If empty, the full list is returned.
	 * @param	boolean		$latest If true, only the latest version is kept in the list
	 * @return	[type]		...
	 */
	function searchExtensionsXML($search, $owner='') {
		if(!count($this->extensionsXML)) $this->loadExtensionsXML();

		reset($this->extensionsXML);
		while (list($extkey, $data) = each($this->extensionsXML)) {

				// Unset extension key in installed keys array (for tracking)
			if(isset($this->emObj->inst_keys[$extkey])) unset($this->emObj->inst_keys[$extkey]);

			if(strlen($search) && !stristr($extkey,$search)) {
				unset($this->extensionsXML[$extkey]);
				continue;
			}

			if(strlen($owner) && !$this->checkOwner($extkey, $owner)) {
				unset($this->extensionsXML[$extkey]);
				continue;
			}

			if(!strlen($owner)) {
				$this->checkReviewState($this->extensionsXML[$extkey]['versions']); // if showing only own extensions, never hide unreviewed
			}
			$this->removeObsolete($this->extensionsXML[$extkey]['versions']);

			uksort($data['versions'], array($this->emObj, 'versionDifference')); // needed? or will the extensions always be sorted in the XML anyway? Robert?

			if(!count($this->extensionsXML[$extkey]['versions'])) {
				unset($this->extensionsXML[$extkey]);
			}
		}
	}

	/**
	 * Checks whether at least one of the extension versions is owned by the given username
	 *
	 * @param	string		$extkey
	 * @param	string		$owner
	 * @return	boolean
	 */
	function checkOwner($extkey, $owner) {
		foreach($this->extensionsXML[$extkey]['versions'] as $ext) {
			if($ext['ownerusername'] == $owner) return true;
		}
		return false;
	}

	/**
	 * Loads the pre-parsed extension list
	 *
	 * @return	boolean		true on success, false on error
	 */
	function loadExtensionsXML() {
		if(is_file(PATH_site.'typo3temp/extensions.bin')) {
			$this->extensionsXML = unserialize(gzuncompress(t3lib_div::getURL(PATH_site.'typo3temp/extensions.bin')));
			return true;
		} else {
			$this->extensionsXML = array();
			return false;
		}
	}

	/**
	 * Loads the pre-parsed extension list
	 *
	 * @return	boolean		true on success, false on error
	 */
	function loadReviewStates() {
		if(is_file(PATH_site.'typo3temp/reviewstates.bin')) {
			$this->reviewStates = unserialize(gzuncompress(t3lib_div::getURL(PATH_site.'typo3temp/reviewstates.bin')));
			return true;
		} else {
			$this->reviewStates = array();
			return false;
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return	[type]		...
	 */
	function saveExtensionsXML() {
		t3lib_div::writeFile(PATH_site.'typo3temp/extensions.bin',gzcompress(serialize($this->extXMLResult)));
		t3lib_div::writeFile(PATH_site.'typo3temp/reviewstates.bin',gzcompress(serialize($this->reviewStates)));
	}

	/**
	 * Frees the pre-parsed extension list
	 *
	 * @return	void
	 */
	function freeExtensionsXML() {
		unset($this->extensionsXML);
		$this->extensionsXML = array();
	}

	/**
	 * Removes all extension with a certain state from the list
	 *
	 * @param	array		&$extensions	The "versions" subpart of the extension list
	 * @return	void
	 */
	function removeObsolete(&$extensions) {
		if($this->useObsolete) return;

		reset($extensions);
		while (list($version, $data) = each($extensions)) {
			if($data['state']=='obsolete')
			unset($extensions[$version]);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$extKey
	 * @param	[type]		$version: ...
	 * @return	[type]		...
	 */
	function getReviewState($extKey, $version) {
		if(!is_array($this->reviewStates)) $this->loadReviewStates();

		if(isset($this->reviewStates[$extKey])) {
			return (int)$this->reviewStates[$extKey][$version];
		} else {
			return 0;
		}
	}

	/**
	 * Removes all extension versions from $extensions that have a reviewstate<1, unless explicitly allowed
	 *
	 * @param	array		&$extensions	The "versions" subpart of the extension list
	 * @return	void
	 */
	function checkReviewState(&$extensions) {
		if($this->useUnsupported) return;

		reset($extensions);
			while (list($version, $data) = each($extensions)) {
				if($data['reviewstate']<1)
					unset($extensions[$version]);
			}
	}

	/**
	 * Removes all extension versions from the list of available extensions that have a reviewstate<1, unless explicitly allowed
	 *
	 * @return	void
	 */
	function checkReviewStateGlobal() {
		if($this->useUnsupported) return;

		reset($this->extensionsXML);
		while (list($extkey, $data) = each($this->extensionsXML)) {
			while (list($version, $vdata) = each($data['versions'])) {
				if($vdata['reviewstate']<1) unset($this->extensionsXML[$extkey]['versions'][$version]);
			}
			if(!count($this->extensionsXML[$extkey]['versions'])) unset($this->extensionsXML[$extkey]);
		}
	}


	/**
	 * ***************PARSING METHODS***********************
	 */
	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$parser
	 * @param	unknown_type		$name
	 * @param	unknown_type		$attrs
	 * @return	[type]		...
	 */
	function startElement($parser, $name, $attrs) {
		switch($name) {
			case 'extensions':
			break;
			case 'extension':
			$this->currentExt = $attrs['extensionkey'];
			break;
			case 'version':
			$this->currentVersion = $attrs['version'];
			$this->extXMLResult[$this->currentExt]['versions'][$this->currentVersion] = array();
			break;
			default:
			$this->currentTag = $name;
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$parser
	 * @param	unknown_type		$name
	 * @return	[type]		...
	 */
	function endElement($parser, $name) {
		switch($name) {
			case 'extension':
			unset($this->currentExt);
			break;
			case 'version':
			unset($this->currentVersion);
			break;
			default:
			unset($this->currentTag);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$parser
	 * @param	unknown_type		$data
	 * @return	[type]		...
	 */
	function characterData($parser, $data) {
		if(isset($this->currentTag)) {
			if(!isset($this->currentVersion) && $this->currentTag == 'downloadcounter') {
				$this->extXMLResult[$this->currentExt]['downloadcounter'] = trim($data);
			} elseif($this->currentTag == 'dependencies') {
				$data = @unserialize($data);
				if(is_array($data)) {
					$dep = array();
					foreach($data as $v) {
						$dep[$v['kind']][$v['extensionKey']] = $v['versionRange'];
					}
					$this->extXMLResult[$this->currentExt]['versions'][$this->currentVersion]['dependencies'] = $dep;
				}
			} elseif($this->currentTag == 'reviewstate') {
					$this->reviewStates[$this->currentExt][$this->currentVersion] = (int)trim($data);
					$this->extXMLResult[$this->currentExt]['versions'][$this->currentVersion]['reviewstate'] = (int)trim($data);
			} else {
				$this->extXMLResult[$this->currentExt]['versions'][$this->currentVersion][$this->currentTag] .= trim($data);
			}
		}
	}

	/**
	 * Parses content of mirrors.xml into a suitable array
	 *
	 * @param	string		XML data file to parse
	 * @return	string		HTLML output informing about result
	 */
	function parseExtensionsXML($string) {
		global $TYPO3_CONF_VARS;

		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
		xml_set_element_handler($parser, array(&$this,'startElement'), array(&$this,'endElement'));
		xml_set_character_data_handler($parser, array(&$this,'characterData'));

		if ((double)phpversion()>=5)	{
			$preg_result = array();
			preg_match('/^[[:space:]]*<\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"/',substr($string,0,200),$preg_result);
			$theCharset = $preg_result[1] ? $preg_result[1] : ($TYPO3_CONF_VARS['BE']['forceCharset'] ? $TYPO3_CONF_VARS['BE']['forceCharset'] : 'iso-8859-1');
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $theCharset);  // us-ascii / utf-8 / iso-8859-1
		}

		// Parse content:
		if (!xml_parse($parser, $string)) {
			$content.= 'Error in XML parser while decoding extensions XML file. Line '.xml_get_current_line_number($parser).': '.xml_error_string(xml_get_error_code($parser));
			$error = true;
		}
		xml_parser_free($parser);

		if(!$error) {
			$content.= '<p>The extensions list has been updated and now contains '.count($this->extXMLResult).' extension entries.</p>';
		}

		return $content;
	}

	/**
	 * Parses content of mirrors.xml into a suitable array
	 *
	 * @param	string		$string: XML data to parse
	 * @return	string		HTLML output informing about result
	 */
	function parseMirrorsXML($string) {
		global $TYPO3_CONF_VARS;

		// Create parser:
		$parser = xml_parser_create();
		$vals = array();
		$index = array();

		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);

		if ((double)phpversion()>=5)	{
			$preg_result = array();
			preg_match('/^[[:space:]]*<\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"/',substr($string,0,200),$preg_result);
			$theCharset = $preg_result[1] ? $preg_result[1] : ($TYPO3_CONF_VARS['BE']['forceCharset'] ? $TYPO3_CONF_VARS['BE']['forceCharset'] : 'iso-8859-1');
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $theCharset);  // us-ascii / utf-8 / iso-8859-1
		}

		// Parse content:
		xml_parse_into_struct($parser, $string, $vals, $index);

		// If error, return error message:
		if (xml_get_error_code($parser))	{
			$line = xml_get_current_line_number($parser);
			$error = xml_error_string(xml_get_error_code($parser));
			xml_parser_free($parser);
			return 'Error in XML parser while decoding mirrors XML file. Line '.$line.': '.$error;
		} else  {
			// Init vars:
			$stack = array(array());
			$stacktop = 0;
			$mirrornumber = 0;
			$current=array();
			$tagName = '';
			$documentTag = '';

			// Traverse the parsed XML structure:
			foreach($vals as $val) {

				// First, process the tag-name (which is used in both cases, whether "complete" or "close")
				$tagName = ($val['tag']=='mirror' && $val['type']=='open') ? '__plh' : $val['tag'];
				if (!$documentTag)	$documentTag = $tagName;

				// Setting tag-values, manage stack:
				switch($val['type'])	{
					case 'open':		// If open tag it means there is an array stored in sub-elements. Therefore increase the stackpointer and reset the accumulation array:
						$current[$tagName] = array();	// Setting blank place holder
						$stack[$stacktop++] = $current;
						$current = array();
						break;
					case 'close':	// If the tag is "close" then it is an array which is closing and we decrease the stack pointer.
					$oldCurrent = $current;
					$current = $stack[--$stacktop];
					end($current);	// Going to the end of array to get placeholder key, key($current), and fill in array next:
					if($tagName=='mirror') {
						unset($current['__plh']);
						$current[$oldCurrent['host']] = $oldCurrent;
					} else {
						$current[key($current)] = $oldCurrent;
					}
					unset($oldCurrent);
					break;
					case 'complete':	// If "complete", then it's a value. If the attribute "base64" is set, then decode the value, otherwise just set it.
					$current[$tagName] = (string)$val['value']; // Had to cast it as a string - otherwise it would be evaluate false if tested with isset()!!
					break;
				}
			}
			return $current[$tagName];
		}
	}

	/**
	 * Parses content of *-l10n.xml into a suitable array
	 *
	 * @param	string		$string: XML data to parse
	 * @return	array		Array representation of XML data
	 */
	function parseL10nXML($string) {
		// Create parser:
		$parser = xml_parser_create();
		$vals = array();
		$index = array();

		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);

		// Parse content:
		xml_parse_into_struct($parser, $string, $vals, $index);

		// If error, return error message:
		if (xml_get_error_code($parser))	{
			$line = xml_get_current_line_number($parser);
			$error = xml_error_string(xml_get_error_code($parser));
			debug($error);
			xml_parser_free($parser);
			return 'Error in XML parser while decoding l10n XML file. Line '.$line.': '.$error;
		} else  {
			// Init vars:
			$stack = array(array());
			$stacktop = 0;
			$mirrornumber = 0;
			$current=array();
			$tagName = '';
			$documentTag = '';

			// Traverse the parsed XML structure:
			foreach($vals as $val) {

				// First, process the tag-name (which is used in both cases, whether "complete" or "close")
				$tagName = ($val['tag']=='languagepack' && $val['type']=='open') ? $val['attributes']['language'] : $val['tag'];
				if (!$documentTag)	$documentTag = $tagName;

				// Setting tag-values, manage stack:
				switch($val['type'])	{
					case 'open':		// If open tag it means there is an array stored in sub-elements. Therefore increase the stackpointer and reset the accumulation array:
						$current[$tagName] = array();	// Setting blank place holder
						$stack[$stacktop++] = $current;
						$current = array();
						break;
					case 'close':	// If the tag is "close" then it is an array which is closing and we decrease the stack pointer.
					$oldCurrent = $current;
					$current = $stack[--$stacktop];
					end($current);	// Going to the end of array to get placeholder key, key($current), and fill in array next:
					$current[key($current)] = $oldCurrent;
					unset($oldCurrent);
					break;
					case 'complete':	// If "complete", then it's a value. If the attribute "base64" is set, then decode the value, otherwise just set it.
					$current[$tagName] = (string)$val['value']; // Had to cast it as a string - otherwise it would be evaluate false if tested with isset()!!
					break;
				}
			}
			return $current[$tagName];
		}
	}
}
?>