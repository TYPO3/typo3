<?php
/* **************************************************************
*  Copyright notice
*
*  (c) 2006-2010 Karsten Dambekalns <karsten@typo3.org>
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
	 * Enxtension Manager module
	 *
	 * @var SC_mod_tools_em_index
	 */
	var $emObj;

	/**
	 * Holds the parsed XML from extensions.xml.gz
	 * @see parseExtensionsXML()
	 *
	 * @var array
	 */
	var $extXMLResult = array();
	var $extensionsXML = array();
	var $reviewStates = null;
	var $useObsolete = false;

	/**
	 * Reduces the entries in $this->extensionsXML to the latest version per extension and removes entries not matching the search parameter
	 *
	 * @param	string		$search	    The list of extensions is reduced to entries matching this. If empty, the full list is returned.
	 * @param	string		$owner	    If set only extensions of that user are fetched
	 * @param	string		$order	    A field to order the result by
	 * @param	boolean		$allExt	    If set also unreviewed and obsolete extensions are shown
	 * @param	boolean		$allVer	    If set returns all version of an extension, otherwise only the last
	 * @param	integer		$offset	    Offset to return result from (goes into LIMIT clause)
	 * @param	integer		$limit	    Maximum number of entries to return (goes into LIMIT clause)
	 * @param	boolean		$exactMatch If set search is done for exact matches of extension keys only
	 * @return	void
	 */
	function searchExtensionsXML($search, $owner='', $order='', $allExt=false, $allVer=false, $offset=0, $limit=500, $exactMatch=false)	{
		$where = '1=1';
		if ($search && $exactMatch)	{
			$where.= ' AND extkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($search, 'cache_extensions');
		} elseif ($search) {
			$quotedSearch = $GLOBALS['TYPO3_DB']->quoteStr(
				$GLOBALS['TYPO3_DB']->escapeStrForLike($search, 'cache_extensions'),
				'cache_extensions'
			);
			$where .= ' AND (extkey LIKE \'%' . $quotedSearch . '%\' OR title LIKE \'%' . $quotedSearch . '%\')';

		}
		if ($owner)	{
			$where.= ' AND ownerusername='.$GLOBALS['TYPO3_DB']->fullQuoteStr($owner, 'cache_extensions');
		}

			// Show extensions without a review or that have passed a review, but not insecure extensions
		$where .= ' AND reviewstate >= 0';

		if (!$this->useObsolete)	{
				// 5 == obsolete
			$where.= ' AND state != 5';
		}
		switch ($order)	{
			case 'author_company':
				$forder = 'authorname, authorcompany';
			break;
			case 'state':
				$forder = 'state';
			break;
			case 'cat':
			default:
				$forder = 'category';
			break;
		}
		$order = $forder.', title';
		if (!$allVer)	{
			$where .= ' AND lastversion > 0';
		}
		$this->catArr = array();
		$idx = 0;
		foreach ($this->emObj->defaultCategories['cat'] as $catKey => $tmp)	{
			$this->catArr[$idx] = $catKey;
			$idx++;
		}
		$this->stateArr = array();
		$idx = 0;
		foreach ($this->emObj->states as $state => $tmp)	{
			$this->stateArr[$idx] = $state;
			$idx++;
		}

			// Fetch count
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cache_extensions', $where);
		$this->matchingCount = $count;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'cache_extensions', $where, '', $order, $offset.','.$limit);
		$this->extensionsXML = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$row['category'] = $this->catArr[$row['category']];
			$row['state'] = $this->stateArr[$row['state']];

			if (!is_array($this->extensionsXML[$row['extkey']]))	{
				$this->extensionsXML[$row['extkey']] = array();
				$this->extensionsXML[$row['extkey']]['downloadcounter'] = $row['alldownloadcounter'];
			}
			if (!is_array($this->extensionsXML[$row['extkey']]['versions']))	{
				$this->extensionsXML[$row['extkey']]['versions'] = array();
 			}
			$row['dependencies'] = unserialize($row['dependencies']);
			$this->extensionsXML[$row['extkey']]['versions'][$row['version']] = $row;
 		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
 	}

	/**
	 * Reduces the entries in $this->extensionsXML to the latest version per extension and removes entries not matching the search parameter
	 * The extension key has to be a valid one as search is done for exact matches only.
	 *
	 * @param	string		$search	The list of extensions is reduced to entries with exactely this extension key. If empty, the full list is returned.
	 * @param	string		$owner	If set only extensions of that user are fetched
	 * @param	string		$order	A field to order the result by
	 * @param	boolean		$allExt	If set also unreviewed and obsolete extensions are shown
	 * @param	boolean		$allVer	If set returns all version of an extension, otherwise only the last
	 * @param	integer		$offset	Offset to return result from (goes into LIMIT clause)
	 * @param	integer		$limit	Maximum number of entries to return (goes into LIMIT clause)
	 * @return	void
	 */
	function searchExtensionsXMLExact($search, $owner='', $order='', $allExt=false, $allVer=false, $offset=0, $limit=500)	{
		$this->searchExtensionsXML($search, $owner, $order, $allExt, $allVer, $offset, $limit, true);
 	}

	function countExtensions() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('extkey', 'cache_extensions', '1=1', 'extkey');
		$cnt = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $cnt;
 	}

	/**
	 * Loads the pre-parsed extension list
	 *
	 * @return	boolean		true on success, false on error
	 */
	function loadExtensionsXML() {
		$this->searchExtensionsXML('', '', '', true);
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

		foreach ($extensions as $version => $data) {
			if($data['state']=='obsolete')
			unset($extensions[$version]);
		}
	}

	/**
	 * Returns the reviewstate of a specific extension-key/version
	 *
	 * @param	string		$extKey
	 * @param	string		$version: ...
	 * @return	integer		Review state, if none is set 0 is returned as default.
	 */
	function getReviewState($extKey, $version) {
		$where = 'extkey='.$GLOBALS['TYPO3_DB']->fullQuoteStr($extKey, 'cache_extensions').' AND version='.$GLOBALS['TYPO3_DB']->fullQuoteStr($version, 'cache_extensions');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('reviewstate', 'cache_extensions', $where);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row['reviewstate'];
 		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return 0;
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
	function parseExtensionsXML($filename) {

		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
		xml_set_element_handler($parser, array(&$this,'startElement'), array(&$this,'endElement'));
		xml_set_character_data_handler($parser, array(&$this,'characterData'));

		$fp = gzopen($filename, 'rb');
		if (!$fp)	{
			$content.= 'Error opening XML extension file "'.$filename.'"';
			return $content;
		}
		$string = gzread($fp, 0xffff);	// Read 64KB

		$this->revCatArr = array();
		$idx = 0;
		foreach ($this->emObj->defaultCategories['cat'] as $catKey => $tmp)	{
			$this->revCatArr[$catKey] = $idx++;
		}

		$this->revStateArr = array();
		$idx = 0;
		foreach ($this->emObj->states as $state => $tmp)	{
			$this->revStateArr[$state] = $idx++;
		}

		$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('cache_extensions');

		$extcount = 0;
		@ini_set('pcre.backtrack_limit', 500000);
		do	{
			if (preg_match('/.*(<extension\s+extensionkey="[^"]+">.*<\/extension>)/suU', $string, $match))	{
				// Parse content:
				if (!xml_parse($parser, $match[0], 0)) {
					$content.= 'Error in XML parser while decoding extensions XML file. Line '.xml_get_current_line_number($parser).': '.xml_error_string(xml_get_error_code($parser));
					$error = true;
					break;
				}
				$this->storeXMLResult();
				$this->extXMLResult = array();
				$extcount++;
				$string = substr($string, strlen($match[0]));
			} elseif(function_exists('preg_last_error') && preg_last_error())	{
				$errorcodes = array(
					0 => 'PREG_NO_ERROR',
					1 => 'PREG_INTERNAL_ERROR',
					2 => 'PREG_BACKTRACK_LIMIT_ERROR',
					3 => 'PREG_RECURSION_LIMIT_ERROR',
					4 => 'PREG_BAD_UTF8_ERROR'
				);
				$content.= 'Error in regular expression matching, code: '.$errorcodes[preg_last_error()].'<br />See <a href="http://www.php.net/manual/en/function.preg-last-error.php" target="_blank">http://www.php.net/manual/en/function.preg-last-error.php</a>';
				$error = true;
				break;
			} else	{
				if(gzeof($fp)) break; // Nothing more can be read
				$string .= gzread($fp, 0xffff);	// Read another 64KB
			}
		} while (true);

		xml_parser_free($parser);
		gzclose($fp);

		if(!$error) {
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				sprintf($GLOBALS['LANG']->getLL('ext_import_extlist_updated'), $extcount),
				$GLOBALS['LANG']->getLL('ext_import_extlist_updated_header')
			);
			$content .= $flashMessage->render();
		}

		return $content;
	}

 	function storeXMLResult()	{
		foreach ($this->extXMLResult as $extkey => $extArr)	{
			$max = -1;
			$maxrev = -1;
			$last = '';
			$lastrev = '';
			$usecat = '';
			$usetitle = '';
			$usestate = '';
			$useauthorcompany = '';
			$useauthorname = '';
			$verArr = array();
			foreach ($extArr['versions'] as $version => $vArr)	{
				$iv = $this->emObj->makeVersion($version, 'int');
				if ($vArr['title']&&!$usetitle)	{
					$usetitle = $vArr['title'];
				}
				if ($vArr['state']&&!$usestate)	{
					$usestate = $vArr['state'];
				}
				if ($vArr['authorcompany']&&!$useauthorcompany)	{
					$useauthorcompany = $vArr['authorcompany'];
				}
				if ($vArr['authorname']&&!$useauthorname)	{
					$useauthorname = $vArr['authorname'];
				}
				$verArr[$version] = $iv;
				if ($iv>$max)	{
					$max = $iv;
					$last = $version;
					if ($vArr['title'])	{
						$usetitle = $vArr['title'];
					}
					if ($vArr['state'])	{
						$usestate = $vArr['state'];
					}
					if ($vArr['authorcompany'])	{
						$useauthorcompany = $vArr['authorcompany'];
					}
					if ($vArr['authorname'])	{
						$useauthorname = $vArr['authorname'];
					}
					$usecat = $vArr['category'];
				}
				if ($vArr['reviewstate'] && ($iv>$maxrev))	{
					$maxrev = $iv;
					$lastrev = $version;
				}
			}
			if (!strlen($usecat))	{
				$usecat = 4;		// Extensions without a category end up in "misc"
			} else	{
				if (isset($this->revCatArr[$usecat]))	{
					$usecat = $this->revCatArr[$usecat];
				} else	{
					$usecat = 4;		// Extensions without a category end up in "misc"
				}
			}
			if (isset($this->revStateArr[$usestate]))	{
				$usestate = $this->revCatArr[$usestate];
			} else	{
				$usestate = 999;		// Extensions without a category end up in "misc"
			}
			foreach ($extArr['versions'] as $version => $vArr)	{
				$vArr['version'] = $version;
				$vArr['intversion'] = $verArr[$version];
				$vArr['extkey'] = $extkey;
				$vArr['alldownloadcounter'] = $extArr['downloadcounter'];
				$vArr['dependencies'] = serialize($vArr['dependencies']);
				$vArr['category'] = $usecat;
				$vArr['title'] = $usetitle;
				if ($version==$last)	{
					$vArr['lastversion'] = 1;
				}
				if ($version==$lastrev)	{
					$vArr['lastreviewedversion'] = 1;
				}
				$vArr['state'] = isset($this->revStateArr[$vArr['state']])?$this->revStateArr[$vArr['state']]:$usestate;	// 999 = not set category
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_extensions', $vArr);
			}
		}
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

		$preg_result = array();
		preg_match('/^[[:space:]]*<\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"/',substr($string,0,200),$preg_result);
		$theCharset = $preg_result[1] ? $preg_result[1] : ($TYPO3_CONF_VARS['BE']['forceCharset'] ? $TYPO3_CONF_VARS['BE']['forceCharset'] : 'iso-8859-1');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $theCharset);  // us-ascii / utf-8 / iso-8859-1

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