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
 * Contains the TYPO3 Backend Language class
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   88: class language
 *  138:     function init($lang,$altPath='')
 *  183:     function addModuleLabels($arr,$prefix)
 *  209:     function hscAndCharConv($lStr,$hsc)
 *  224:     function makeEntities($str)
 *  241:     function JScharCode($str)
 *  260:     function getLL($index,$hsc=0)
 *  278:     function getLLL($index,$LOCAL_LANG,$hsc=0)
 *  299:     function sL($input,$hsc=0)
 *  344:     function loadSingleTableDescription($table)
 *  396:     function includeLLFile($fileRef,$setGlobal=1,$mergeLocalOntoDefault=0)
 *  441:     function readLLfile($fileRef)
 *  451:     function localizedFileRef($fileRef)
 *
 * TOTAL FUNCTIONS: 12
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * Contains the TYPO3 Backend Language class
 *
 * For detailed information about how localization is handled,
 * please refer to the 'Inside TYPO3' document which descibes this.
 *
 * This class is normally instantiated as the global variable $LANG in typo3/template.php
 * It's only available in the backend and under certain circumstances in the frontend
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @see typo3/template.php, template
 */
class language {

	/**
	 * This is set to the language that is currently running for the user
	 *
	 * @var string
	 */
	public $lang = 'default';

	/**
	 * Values like the labels in the tables.php-document are split by '|'.
	 * This values defines which language is represented by which position
	 * in the resulting array after splitting a value. (NOTICE: Obsolete concept!)
	 *
	 * @var string
	 */
	public $langSplit = 'default';

	/**
	 * Default charset in backend
	 *
	 * @var string
	 */
	public $charSet = 'utf-8';

	/**
	 * Array with alternative charsets for other languages.
	 * Moved to t3lib_cs, set internally from csConvObj!
	 *
	 * @var array
	 */
	public $charSetArray = array();

	/**
	 * This is the url to the TYPO3 manual
	 *
	 * @var string
	 */
	public $typo3_help_url= 'http://typo3.org/documentation/document-library/';

	/**
	 * If TRUE, will show the key/location of labels in the backend.
	 * @var bool
	 */
	public $debugKey = FALSE;

	/**
	 * Can contain labels and image references from the backend modules.
	 * Relies on t3lib_loadmodules to initialize modules after a global instance of $LANG has been created.
	 *
	 * @var array
	 */
	public $moduleLabels = array();

	/**
	 * Internal, Points to the position of the current language key as found in constant TYPO3_languages
	 * @var int
	 */
	public $langSplitIndex = 0;

	/**
	 * Internal cache for read LL-files
	 *
	 * @var array
	 */
	public $LL_files_cache = array();

	/**
	 * Internal cache for ll-labels (filled as labels are requested)
	 *
	 * @var array
	 */
	public $LL_labels_cache = array();

	/**
	 * instance of the "t3lib_cs" class. May be used by any application.
	 *
	 * @var t3lib_cs
	 */
	public $csConvObj;

	/**
	 * instance of the parser factory
	 * @var tx_lang_Factory
	 */
	public $parserFactory;

	/**
	 * Initializes the backend language.
	 * This is for example done in typo3/template.php with lines like these:
	 *
	 * require (PATH_typo3 . 'sysext/lang/lang.php');
	 * $LANG = t3lib_div::makeInstance('language');
	 * $LANG->init($GLOBALS['BE_USER']->uc['lang']);
	 *
	 * @throws RuntimeException
	 * @param  string $lang		The language key (two character string from backend users profile)
	 * @return void
	 */
	public function init($lang) {

			// Initialize the conversion object:
		$this->csConvObj = t3lib_div::makeInstance('t3lib_cs');
		$this->charSetArray = $this->csConvObj->charSetArray;

			// Initialize the parser factory object
		$this->parserFactory = t3lib_div::makeInstance('tx_lang_Factory');

			// Internally setting the list of TYPO3 backend languages.
		$this->langSplit = TYPO3_languages;

			// Finding the requested language in this list based
			// on the $lang key being inputted to this function.
		$ls = explode('|', $this->langSplit);

		foreach ($ls as $i => $v) {
				// Language is found. Configure it:
			if ($v == $lang) {
					// The index of the language as found in the TYPO3_languages list
				$this->langSplitIndex = $i;
					// The current language key
				$this->lang = $lang;
				if ($this->charSetArray[$this->lang]) {
						// The charset if different from the default.
					$this->charSet = $this->charSetArray[$this->lang];
				}
			}
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['lang']['debug']) {
			$this->debugKey = TRUE;
		}

			// If a forced charset is used and different from the charset otherwise used:
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] && $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] != $this->charSet) {
				// Set the forced charset:
			$this->charSet = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];

			if ($this->charSet != 'utf-8' && !$this->csConvObj->initCharset($this->charSet)) {
				throw new RuntimeException('Forced charset not found: The forced character set "'. $this->charSet . '" was not found in t3lib/csconvtbl/', 1294587487);
			}
		}
	}

	/**
	 * Gets the parser factory.
	 *
	 * @return tx_lang_Factory
	 */
	public function getParserFactory() {
		return $this->parserFactory;
	}

	/**
	 * Adds labels and image references from the backend modules to the internal moduleLabels array
	 *
	 * @param  array $arr		Array with references to module labels, keys: ['labels']['tablabel'],
	 * 							['labels']['tabdescr'], ['tabs']['tab']
	 * @param  string $prefix	Module name prefix
	 * @return void
	 * @see    t3lib_loadModules
	 */
	public function addModuleLabels($arr, $prefix) {
		if (is_array($arr)) {
			foreach ($arr as $k => $larr) {
				if (!isset($this->moduleLabels[$k])) {
					$this->moduleLabels[$k] = array();
				}
				if (is_array($larr)) {
					foreach ($larr as $l => $v) {
						$this->moduleLabels[$k][$prefix . $l] = $v;
					}
				}
			}
		}
	}

	/**
	 * Will htmlspecialchar() the input string and before that any charset conversion
	 * will also have taken place if needed (see init())
	 * Used to pipe language labels through just before they are returned.
	 *
	 * @param  string $lStr The string to process
	 * @param  boolean $hsc	If set, then the string is htmlspecialchars()'ed
	 * @return string		The processed string
	 * @see    init()
	 * @access public
	 */
	public function hscAndCharConv($lStr, $hsc) {
			// labels returned from a locallang file used to be in the language of the charset.
			// Since TYPO3 4.1 they are always in the charset of the BE.
		if ($hsc) {
			return htmlspecialchars($lStr);
		} else {
			return $lStr;
		}
	}

	/**
	 * Will convert the input strings special chars (all above 127) to entities.
	 * The string is expected to be encoded in the charset, $this->charSet
	 * This function is used to create strings that can be used in the Click Menu
	 * (Context Sensitive Menus). The reason is that the values that are dynamically
	 * written into the <div> layer is decoded as iso-8859-1 no matter what charset
	 * is used in the document otherwise (only MSIE, Mozilla is OK).
	 * So by converting we by-pass this problem.
	 *
	 * @param  string $str	Input string
	 * @return string		Output string
	 * @access	public
	 */
	public function makeEntities($str) {
			// Convert string to UTF-8:
		if ($this->charSet != 'utf-8') {
			$str = $this->csConvObj->utf8_encode($str, $this->charSet);
		}

			// Convert string back again, but using the full entity conversion:
		return $this->csConvObj->utf8_to_entities($str);
	}

	/**
	 * Converts the input string to a JavaScript function returning the same string, but charset-safe.
	 * Used for confirm and alert boxes where we must make sure that any string content
	 * does not break the script AND want to make sure the charset is preserved.
	 * Originally I used the JS function unescape() in combination with PHP function
	 * rawurlencode() in order to pass strings in a safe way. This could still be done
	 * for iso-8859-1 charsets but now I have applied the same method here for all charsets.
	 *
	 * @param	string $str		Input string, encoded with $this->charSet
	 * @return	string			Output string, a JavaScript function: "String.fromCharCode(......)"
	 * @access	public
	 */
	public function JScharCode($str) {

			// Convert string to UTF-8:
		if ($this->charSet != 'utf-8') {
			$str = $this->csConvObj->utf8_encode($str, $this->charSet);
		}

			// Convert the UTF-8 string into a array of char numbers:
		$nArr = $this->csConvObj->utf8_to_numberarray($str);

		return 'String.fromCharCode(' . implode(',', $nArr) . ')';
	}

	/**
	 * Debugs localization key.
	 *
	 * @param $value Value to debug
	 * @return string
	 */
	public function debugLL($value) {
		return ($this->debugKey ? '[' . $value . ']' : '');
	}

	/**
	 * Returns the label with key $index form the globally loaded $LOCAL_LANG array.
	 * Mostly used from modules with only one LOCAL_LANG file loaded into the global space.
	 *
	 * @param	string $index	Label key
	 * @param	boolean $hsc	If set, the return value is htmlspecialchar'ed
	 * @return	string
	 * @access	public
	 */
	public function getLL($index, $hsc = FALSE) {
		$output = '';

			// Get Local Language
		if (isset($GLOBALS['LOCAL_LANG'][$this->lang][$index][0]['target']) && $GLOBALS['LOCAL_LANG'][$this->lang][$index][0]['target'] !== '') {
			$output = $this->hscAndCharConv($GLOBALS['LOCAL_LANG'][$this->lang][$index][0]['target'], $hsc);
		} elseif (isset($GLOBALS['LOCAL_LANG']['default'][$index][0]['target']) && $GLOBALS['LOCAL_LANG']['default'][$index][0]['target'] !== '') {
			$output = $this->hscAndCharConv($GLOBALS['LOCAL_LANG']['default'][$index][0]['target'], $hsc);
		}
		return $output . $this->debugLL($index);
	}

	/**
	 * Works like ->getLL() but takes the $LOCAL_LANG array
	 * used as the second argument instead of using the global array.
	 *
	 * @param	string  $index			Label key
	 * @param	array   $localLanguage	$LOCAL_LANG array to get label key from
	 * @param	boolean	$hsc			If set, the return value is htmlspecialchar'ed
	 * @return	string
	 * @access	public
	 */
	public function getLLL($index, $localLanguage, $hsc = FALSE) {
		$output = '';

			// Get Local Language
		if (isset($localLanguage[$this->lang][$index][0]['target']) && $localLanguage[$this->lang][$index][0]['target'] !== '') {
			$output = $this->hscAndCharConv($localLanguage[$this->lang][$index][0]['target'], $hsc);
		} elseif (isset($localLanguage['default'][$index][0]['target']) && $localLanguage['default'][$index][0]['target'] !== '') {
			$output = $this->hscAndCharConv($localLanguage['default'][$index][0]['target'], $hsc);
		}
		return $output . $this->debugLL($index);
	}

	/**
	 * splitLabel function
	 * Historically labels were exploded by '|' and each part would correspond
	 * to the translation of the language found at the same 'index' in the TYPO3_languages constant.
	 * Today all translations are based on $LOCAL_LANG variables.
	 * 'language-splitted' labels can therefore refer to a local-lang file + index instead!
	 * It's highly recommended to use the 'local_lang' method
	 * (and thereby it's highly deprecated to use 'language-splitted' label strings)
	 * Refer to 'Inside TYPO3' for more details
	 *
	 * @param	string $input	Label key/reference
	 * @param	boolean	$hsc	If set, the return value is htmlspecialchar'ed
	 * @return	string
	 * @access	public
	 */
	public function sL($input, $hsc = FALSE) {
		// Using obsolete 'language-splitted' labels:
		if (strcmp(substr($input, 0, 4), 'LLL:')) {
			$t = explode('|', $input);
			$out = $t[$this->langSplitIndex] ? $t[$this->langSplitIndex] : $t[0];
			return $this->hscAndCharConv($out, $hsc);
			// LOCAL_LANG:
		} else {
				// If cached label
			if (!isset($this->LL_labels_cache[$this->lang][$input])) {
				$restStr = trim(substr($input, 4));
				$extPrfx = '';

					// ll-file refered to is found in an extension.
				if (!strcmp(substr($restStr, 0, 4), 'EXT:')) {
					$restStr = trim(substr($restStr, 4));
					$extPrfx = 'EXT:';
				}
				$parts = explode(':', $restStr);
				$parts[0] = $extPrfx . $parts[0];

					// Getting data if not cached
				if (!isset($this->LL_files_cache[$parts[0]])) {
					$this->LL_files_cache[$parts[0]] = $this->readLLfile($parts[0]);

						// If the current language is found in another file, load that as well:
					$lFileRef = $this->localizedFileRef($parts[0]);
					if ($lFileRef && is_string($this->LL_files_cache[$parts[0]][$this->lang])
							&& $this->LL_files_cache[$parts[0]][$this->lang] == 'EXT') {
						$tempLL = $this->readLLfile($lFileRef);
						$this->LL_files_cache[$parts[0]][$this->lang] = $tempLL[$this->lang];
					}

						// Overriding file?
						// @deprecated since TYPO3 4.3, remove in TYPO3 4.5, please use the generic method in
						// t3lib_div::readLLfile and the global array $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']
					if (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['XLLfile'][$parts[0]])) {
						t3lib_div::deprecationLog('Usage of $TYPO3_CONF_VARS[\'BE\'][\'XLLfile\'] is deprecated since TYPO3 4.3. Use $TYPO3_CONF_VARS[\'SYS\'][\'locallangXMLOverride\'][] to include the file ' . $fileRef . ' instead.');
						$ORarray = $this->readLLfile($GLOBALS['TYPO3_CONF_VARS']['BE']['XLLfile'][$parts[0]]);
						$this->LL_files_cache[$parts[0]] = t3lib_div::array_merge_recursive_overrule($this->LL_files_cache[$parts[0]], $ORarray);
					}
				}
				$this->LL_labels_cache[$this->lang][$input] = $this->getLLL($parts[1], $this->LL_files_cache[$parts[0]]);
			}
				// For the cached output charset conversion has already happened!
				// So perform HSC right here.
			$output = $this->LL_labels_cache[$this->lang][$input];
			if ($hsc) {
				$output = t3lib_div::deHSCentities(htmlspecialchars($output));
			}
			return $output . $this->debugLL($input);
		}
	}

	/**
	 * Loading $TCA_DESCR[$table]['columns'] with content from locallang files
	 * as defined in $TCA_DESCR[$table]['refs']
	 * $TCA_DESCR is a global var
	 *
	 * @param	string $table	Table name found as key in global array $TCA_DESCR
	 * @return	void
	 * @access	public
	 */
	public function loadSingleTableDescription($table) {

			// First the 'table' cannot already be loaded in [columns]
			// and secondly there must be a references to locallang files available in [refs]
		if (is_array($GLOBALS['TCA_DESCR'][$table])
				&& !isset($GLOBALS['TCA_DESCR'][$table]['columns'])
				&& is_array($GLOBALS['TCA_DESCR'][$table]['refs'])) {

				// Init $TCA_DESCR for $table-key
			$GLOBALS['TCA_DESCR'][$table]['columns'] = array();

				// Get local-lang for each file in $TCA_DESCR[$table]['refs'] as they are ordered.
			foreach ($GLOBALS['TCA_DESCR'][$table]['refs'] as $llfile) {
				$localLanguage = $this->includeLLFile($llfile, 0, 1);

					// Traverse all keys
				if (is_array($localLanguage['default'])) {
					foreach ($localLanguage['default'] as $lkey => $lVal) {
						$type = '';
						$fieldName = '';

							// Exploding by '.':
							// 0-n => fieldname,
							// n+1 => type from (alttitle, description, details, syntax, image_descr,image,seeAlso),
							// n+2 => special instruction, if any
						$keyParts = explode('.', $lkey);
						$keyPartsCount = count($keyParts);
							// Check if last part is special instruction
							// Only "+" is currently supported
						$specialInstruction = ($keyParts[$keyPartsCount - 1] == '+') ? TRUE : FALSE;
						if ($specialInstruction) {
							array_pop($keyParts);
						}

							// If there are more than 2 parts, get the type from the last part
							// and merge back the other parts with a dot (.)
							// Otherwise just get type and field name straightaway
						if ($keyPartsCount > 2) {
							$type = array_pop($keyParts);
							$fieldName = implode('.', $keyParts);
						} else {
							$fieldName = $keyParts[0];
							$type = $keyParts[1];
						}

							// Detecting 'hidden' labels, converting to normal fieldname
						if ($fieldName == '_') {
							$fieldName = '';
						}
						if (substr($fieldName, 0, 1) == '_') {
							$fieldName = substr($fieldName, 1);
						}

							// Append label
						if ($specialInstruction) {
							$GLOBALS['TCA_DESCR'][$table]['columns'][$fieldName][$type] .= LF . $lVal[0]['source'];
						} else {
								// Substitute label
							$GLOBALS['TCA_DESCR'][$table]['columns'][$fieldName][$type] = $lVal[0]['source'];
						}
					}
				}
			}
		}
	}

	/**
	 * Includes locallang file (and possibly additional localized version if configured for)
	 * Read language labels will be merged with $LOCAL_LANG (if $setGlobal = TRUE).
	 *
	 * @param	string $fileRef					$fileRef is a file-reference (see t3lib_div::getFileAbsFileName)
	 * @param	boolean $setGlobal				Setting in global variable $LOCAL_LANG (or returning the variable)
	 * @param	boolean	$mergeLocalOntoDefault	If $mergeLocalOntoDefault is set the local part of the $LOCAL_LANG array is merged onto the default part (if the local part exists) and the local part is unset.
	 * @return	mixed							If $setGlobal is TRUE the LL-files will set the $LOCAL_LANG in the global scope. Otherwise the $LOCAL_LANG array is returned from function
	 * @access	public
	 */
	public function includeLLFile($fileRef, $setGlobal = TRUE, $mergeLocalOntoDefault = FALSE) {

		$globalLanguage = array();

			// Get default file
		$localLanguage = $this->readLLfile($fileRef);

		if (is_array($localLanguage) && count($localLanguage)) {

				// it depends on, whether we should return the result or set it in the global $LOCAL_LANG array
			if ($setGlobal) {
				$globalLanguage = t3lib_div::array_merge_recursive_overrule((array)$GLOBALS['LOCAL_LANG'], $localLanguage);
			} else {
				$globalLanguage = $localLanguage;
			}

				// Localized addition?
			$lFileRef = $this->localizedFileRef($fileRef);
			if ($lFileRef && (string)$globalLanguage[$this->lang] == 'EXT') {
				$localLanguage = $this->readLLfile($lFileRef);
				$globalLanguage = t3lib_div::array_merge_recursive_overrule($globalLanguage, $localLanguage);
			}

				// Merge local onto default
			if ($mergeLocalOntoDefault && $this->lang !== 'default'
				&& is_array($globalLanguage[$this->lang]) && is_array($globalLanguage['default'])) {
					// array_merge can be used so far the keys are not
					// numeric - which we assume they are not...
				$globalLanguage['default'] = array_merge($globalLanguage['default'], $globalLanguage[$this->lang]);
				unset($globalLanguage[$this->lang]);
			}
		}

			// Return value if not global is set.
		if (!$setGlobal) {
			return $globalLanguage;
		} else {
			$GLOBALS['LOCAL_LANG'] = $globalLanguage;
		}
	}

	/**
	 * Includes a locallang file and returns the $LOCAL_LANG array found inside.
	 *
	 * @param	string $fileRef	Input is a file-reference (see t3lib_div::getFileAbsFileName) which, if exists, is included. That file is expected to be a 'local_lang' file containing a $LOCAL_LANG array.
	 * @return	array			Value of $LOCAL_LANG found in the included file. If that array is found it's returned. Otherwise an empty array
	 * @access	private
	 */
	protected function readLLfile($fileRef) {
		return t3lib_div::readLLfile($fileRef, $this->lang, $this->charSet);
	}

	/**
	 * Returns localized fileRef (.[langkey].php)
	 *
	 * @param	string $fileRef	Filename/path of a 'locallang.php' file
	 * @return	string			Input filename with a '.[lang-key].php' ending added if $this->lang is not 'default'
	 * @access	private
	 */
	protected function localizedFileRef($fileRef) {
		if ($this->lang != 'default' && substr($fileRef, -4) == '.php') {
			return substr($fileRef, 0, -4) . '.' . $this->lang . '.php';
		}
	}

	/**
	 * Overrides a label.
	 *
	 * @param string $index
	 * @param string $value
	 * @param boolean $overrideDefault Overrides default language
	 * @return void
	 */
	public function overrideLL($index, $value, $overrideDefault = TRUE) {
		if (isset($GLOBALS['LOCAL_LANG']) === FALSE) {
			$GLOBALS['LOCAL_LANG'] = array();
		}

		$GLOBALS['LOCAL_LANG'][$this->lang][$index][0]['target'] = $value;

		if ($overrideDefault) {
			$GLOBALS['LOCAL_LANG']['default'][$index][0]['target'] = $value;
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lang/lang.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lang/lang.php']);
}

?>