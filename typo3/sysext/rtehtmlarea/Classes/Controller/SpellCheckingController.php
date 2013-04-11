<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2003-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Spell checking plugin 'tx_rtehtmlarea_pi1' for the htmlArea RTE extension.
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class SpellCheckingController {

	/**
	 * @var \TYPO3\CMS\Core\Charset\CharsetConverter
	 */
	protected $csConvObj;

	// The extension key
	/**
	 * @todo Define visibility
	 */
	public $extKey = 'rtehtmlarea';

	/**
	 * @todo Define visibility
	 */
	public $siteUrl;

	/**
	 * @todo Define visibility
	 */
	public $charset = 'utf-8';

	/**
	 * @todo Define visibility
	 */
	public $parserCharset = 'utf-8';

	/**
	 * @todo Define visibility
	 */
	public $defaultAspellEncoding = 'utf-8';

	/**
	 * @todo Define visibility
	 */
	public $aspellEncoding;

	/**
	 * @todo Define visibility
	 */
	public $result;

	/**
	 * @todo Define visibility
	 */
	public $text;

	/**
	 * @todo Define visibility
	 */
	public $misspelled = array();

	/**
	 * @todo Define visibility
	 */
	public $suggestedWords;

	/**
	 * @todo Define visibility
	 */
	public $wordCount = 0;

	/**
	 * @todo Define visibility
	 */
	public $suggestionCount = 0;

	/**
	 * @todo Define visibility
	 */
	public $suggestedWordCount = 0;

	/**
	 * @todo Define visibility
	 */
	public $pspell_link;

	/**
	 * @todo Define visibility
	 */
	public $pspellMode = 'normal';

	/**
	 * @todo Define visibility
	 */
	public $dictionary;

	/**
	 * @todo Define visibility
	 */
	public $AspellDirectory;

	/**
	 * @todo Define visibility
	 */
	public $pspell_is_available;

	/**
	 * @todo Define visibility
	 */
	public $forceCommandMode = 0;

	/**
	 * @todo Define visibility
	 */
	public $filePrefix = 'rtehtmlarea_';

	// Pre-FAL backward compatibility
	protected $uploadFolder = 'uploads/tx_rtehtmlarea/';

	// Path to main dictionary
	protected $mainDictionaryPath;

	// Path to personal dictionary
	protected $personalDictionaryPath;

	/**
	 * @todo Define visibility
	 */
	public $xmlCharacterData = '';

	/**
	 * Main class of Spell Checker plugin for Typo3 CMS
	 *
	 * @return 	string		content produced by the plugin
	 * @todo Define visibility
	 */
	public function main() {
		$this->csConvObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
		// Setting start time
		$time_start = microtime(TRUE);
		$this->pspell_is_available = in_array('pspell', get_loaded_extensions());
		$this->AspellDirectory = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['plugins']['SpellChecker']['AspellDirectory']) ? trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['plugins']['SpellChecker']['AspellDirectory']) : '/usr/bin/aspell';
		// Setting command mode if requested and available
		$this->forceCommandMode = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['plugins']['SpellChecker']['forceCommandMode']) ? trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['plugins']['SpellChecker']['forceCommandMode']) : 0;
		if (!$this->pspell_is_available || $this->forceCommandMode) {
			$AspellVersionString = explode('Aspell', shell_exec($this->AspellDirectory . ' -v'));
			$AspellVersion = substr($AspellVersionString[1], 0, 4);
			if (doubleval($AspellVersion) < doubleval('0.5') && (!$this->pspell_is_available || $this->forceCommandMode)) {
				echo 'Configuration problem: Aspell version ' . $AspellVersion . ' too old. Spell checking cannot be performed in command mode.';
			}
			$this->defaultAspellEncoding = trim(shell_exec($this->AspellDirectory . ' config encoding'));
		}
		// Setting the list of dictionaries
		$dictionaryList = shell_exec($this->AspellDirectory . ' dump dicts');
		$dictionaryList = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $dictionaryList, 1));
		$dictionaryArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $dictionaryList, 1);
		$restrictToDictionaries = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('restrictToDictionaries');
		if ($restrictToDictionaries) {
			$dictionaryArray = array_intersect($dictionaryArray, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $restrictToDictionaries, 1));
		}
		if (!count($dictionaryArray)) {
			$dictionaryArray[] = 'en';
		}
		$this->dictionary = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('dictionary');
		$defaultDictionary = $this->dictionary;
		if (!$defaultDictionary || !in_array($defaultDictionary, $dictionaryArray)) {
			$defaultDictionary = 'en';
		}
		uasort($dictionaryArray, 'strcoll');
		$dictionaryList = implode(',', $dictionaryArray);
		// Setting the dictionary
		if (empty($this->dictionary) || !in_array($this->dictionary, $dictionaryArray)) {
			$this->dictionary = 'en';
		}
		// Setting the pspell suggestion mode
		$this->pspellMode = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('pspell_mode') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('pspell_mode') : $this->pspellMode;
		// Now sanitize $this->pspellMode
		$this->pspellMode = \TYPO3\CMS\Core\Utility\GeneralUtility::inList('ultra,fast,normal,bad-spellers', $this->pspellMode) ? $this->pspellMode : 'normal';
		switch ($this->pspellMode) {
		case 'ultra':

		case 'fast':
			$pspellModeFlag = PSPELL_FAST;
			break;
		case 'bad-spellers':
			$pspellModeFlag = PSPELL_BAD_SPELLERS;
			break;
		case 'normal':

		default:
			$pspellModeFlag = PSPELL_NORMAL;
			break;
		}
		// Setting the charset
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('pspell_charset')) {
			$this->charset = trim(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('pspell_charset'));
		}
		if (strtolower($this->charset) == 'iso-8859-1') {
			$this->parserCharset = strtolower($this->charset);
		}
		// In some configurations, Aspell uses 'iso8859-1' instead of 'iso-8859-1'
		$this->aspellEncoding = $this->parserCharset;
		if ($this->parserCharset == 'iso-8859-1' && strstr($this->defaultAspellEncoding, '8859-1')) {
			$this->aspellEncoding = $this->defaultAspellEncoding;
		}
		// However, we are going to work only in the parser charset
		if ($this->pspell_is_available && !$this->forceCommandMode) {
			$this->pspell_link = pspell_new($this->dictionary, '', '', $this->parserCharset, $pspellModeFlag);
		}
		// Setting the path to main dictionary
		$this->setMainDictionaryPath();
		// Setting the path to user personal dictionary, if any
		$this->setPersonalDictionaryPath();
		$this->fixPersonalDictionaryCharacterSet();
		$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('cmd');
		if ($cmd == 'learn') {
			// Only availble for BE_USERS, die silently if someone has gotten here by accident
			if (TYPO3_MODE !== 'BE' || !is_object($GLOBALS['BE_USER'])) {
				die('');
			}
			// Updating the personal word list
			$to_p_dict = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('to_p_dict');
			$to_p_dict = $to_p_dict ? $to_p_dict : array();
			$to_r_list = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('to_r_list');
			$to_r_list = $to_r_list ? $to_r_list : array();
			header('Content-Type: text/plain; charset=' . strtoupper($this->parserCharset));
			header('Pragma: no-cache');
			if ($to_p_dict || $to_r_list) {
				$tmpFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam($this->filePrefix);
				$filehandle = fopen($tmpFileName, 'wb');
				if ($filehandle) {
					// Get the character set of the main dictionary
					// We need to convert the input into the character set of the main dictionary
					$mainDictionaryCharacterSet = $this->getMainDictionaryCharacterSet();
					// Write the personal words addition commands to the temporary file
					foreach ($to_p_dict as $personal_word) {
						$cmd = '&' . $this->csConvObj->conv($personal_word, $this->parserCharset, $mainDictionaryCharacterSet) . LF;
						fwrite($filehandle, $cmd, strlen($cmd));
					}
					// Write the replacent pairs addition commands to the temporary file
					foreach ($to_r_list as $replace_pair) {
						$cmd = '$$ra ' . $this->csConvObj->conv($replace_pair[0], $this->parserCharset, $mainDictionaryCharacterSet) . ' , ' . $this->csConvObj->conv($replace_pair[1], $this->parserCharset, $mainDictionaryCharacterSet) . LF;
						fwrite($filehandle, $cmd, strlen($cmd));
					}
					$cmd = '#' . LF;
					$result = fwrite($filehandle, $cmd, strlen($cmd));
					if ($result === FALSE) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('SpellChecker tempfile write error: ' . $tmpFileName, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					} else {
						// Assemble the Aspell command
						$aspellCommand = ((TYPO3_OS === 'WIN') ? 'type ' : 'cat ') . escapeshellarg($tmpFileName) . ' | '
							. $this->AspellDirectory
							. ' -a --mode=none'
							. ($this->personalDictionaryPath ? ' --home-dir=' . escapeshellarg($this->personalDictionaryPath) : '')
							. ' --lang=' . escapeshellarg($this->dictionary)
							. ' --encoding=' . escapeshellarg($mainDictionaryCharacterSet)
							. ' 2>&1';
						$aspellResult = shell_exec($aspellCommand);
						// Close and delete the temporary file
						fclose($filehandle);
						\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($tmpFileName);
					}
				} else {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('SpellChecker tempfile open error: ' . $tmpFileName, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
				}
			}
			flush();
			die;
		} else {
			// Check spelling content
			// Initialize output
			$this->result = '<?xml version="1.0" encoding="' . $this->parserCharset . '"?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . substr($this->dictionary, 0, 2) . '" lang="' . substr($this->dictionary, 0, 2) . '">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=' . $this->parserCharset . '" />
<link rel="stylesheet" type="text/css" media="all" href="' . (TYPO3_MODE == 'BE' ? '../' : '') . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . '/htmlarea/plugins/SpellChecker/spell-check-style.css" />
<script type="text/javascript">
/*<![CDATA[*/
<!--
';
			// Getting the input content
			$content = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('content');
			// Parsing the input HTML
			$parser = xml_parser_create(strtoupper($this->parserCharset));
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_object($parser, $this);
			if (!xml_set_element_handler($parser, 'startHandler', 'endHandler')) {
				echo 'Bad xml handler setting';
			}
			if (!xml_set_character_data_handler($parser, 'collectDataHandler')) {
				echo 'Bad xml handler setting';
			}
			if (!xml_set_default_handler($parser, 'defaultHandler')) {
				echo 'Bad xml handler setting';
			}
			if (!xml_parse($parser, ('<?xml version="1.0" encoding="' . $this->parserCharset . '"?><spellchecker> ' . preg_replace(('/&nbsp;/' . ($this->parserCharset == 'utf-8' ? 'u' : '')), ' ', $content) . ' </spellchecker>'))) {
				echo 'Bad parsing';
			}
			if (xml_get_error_code($parser)) {
				throw new \UnexpectedException('Line ' . xml_get_current_line_number($parser) . ': ' . xml_error_string(xml_get_error_code($parser)), 1294585788);
			}
			xml_parser_free($parser);
			if ($this->pspell_is_available && !$this->forceCommandMode) {
				pspell_clear_session($this->pspell_link);
			}
			$this->result .= 'var suggestedWords = {' . $this->suggestedWords . '};
var dictionaries = "' . $dictionaryList . '";
var selectedDictionary = "' . $this->dictionary . '";
';
			// Calculating parsing and spell checkting time
			$time = number_format(microtime(TRUE) - $time_start, 2, ',', ' ');
			// Insert spellcheck info
			$this->result .= 'var spellcheckInfo = { "Total words":"' . $this->wordCount . '","Misspelled words":"' . sizeof($this->misspelled) . '","Total suggestions":"' . $this->suggestionCount . '","Total words suggested":"' . $this->suggestedWordCount . '","Spelling checked in":"' . $time . '" };
// -->
/*]]>*/
</script>
</head>
';
			$this->result .= '<body onload="window.parent.RTEarea[\'' . \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('editorId') . '\'].editor.getPlugin(\'SpellChecker\').spellCheckComplete();">';
			$this->result .= preg_replace('/' . preg_quote('<?xml') . '.*' . preg_quote('?>') . '[' . preg_quote((LF . CR . chr(32))) . ']*/' . ($this->parserCharset == 'utf-8' ? 'u' : ''), '', $this->text);
			$this->result .= '<div style="display: none;">' . $dictionaries . '</div>';
			// Closing
			$this->result .= '
</body></html>';
			// Outputting
			header('Content-Type: text/html; charset=' . strtoupper($this->parserCharset));
			echo $this->result;
		}
	}

	/**
	 * Sets the path to the main dictionary
	 *
	 * @return string path to the main dictionary
	 */
	protected function setMainDictionaryPath() {
		$this->mainDictionaryPath = '';
		$aspellCommand = $this->AspellDirectory . ' config dict-dir';
		$aspellResult = shell_exec($aspellCommand);
		if ($aspellResult) {
			$this->mainDictionaryPath = trim($aspellResult);
		}
		if (!$aspellResult || !$this->mainDictionaryPath) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('SpellChecker main dictionary path retrieval error: ' . $aspellCommand, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}
		return $this->mainDictionaryPath;
	}

	/**
	 * Gets the character set the main dictionary
	 *
	 * @return string character set the main dictionary
	 */
	protected function getMainDictionaryCharacterSet() {
		$characterSet = '';
		if ($this->mainDictionaryPath) {
			// Keep only the first part of the dictionary name
			$mainDictionary = preg_split('/[-_]/', $this->dictionary, 2);
			// Read the options of the dictionary
			$dictionaryFileName = $this->mainDictionaryPath . '/' . $mainDictionary[0] . '.dat';
			$dictionaryHandle = fopen($dictionaryFileName, 'rb');
			if (!$dictionaryHandle) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('SpellChecker main dictionary open error: ' . $dictionaryFileName, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			} else {
				$dictionaryContent = fread($dictionaryHandle, 500);
				if ($dictionaryContent === FALSE) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('SpellChecker main dictionary read error: ' . $dictionaryFileName, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
				} else {
					fclose($dictionaryHandle);
					// Get the line that contains the character set option
					$dictionaryContent = preg_split('/charset\s*/', $dictionaryContent, 2);
					if ($dictionaryContent[1]) {
						// Isolate the character set
						$dictionaryContent = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $dictionaryContent[1]);
						$characterSet = $dictionaryContent[0];
						// Fix Aspell character set oddity (i.e. iso8859-1)
						$characterSet = str_replace('iso', 'iso-', $characterSet);
						$characterSet = str_replace('--', '-', $characterSet);
					}
					if (!$characterSet) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('SpellChecker main dictionary character set retrieval error: ' . $dictionaryContent[1], $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					}
				}
			}
		}
		return $characterSet;
	}

	/**
	 * Sets the path to the personal dictionary
	 *
	 * @return string path to the personal dictionary
	 */
	protected function setPersonalDictionaryPath() {
		$this->personalDictionaryPath = '';
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('enablePersonalDicts') == 'true' && TYPO3_MODE == 'BE' && is_object($GLOBALS['BE_USER'])) {
			if ($GLOBALS['BE_USER']->user['uid']) {
				$personalDictionaryFolderName = 'BE_' . $GLOBALS['BE_USER']->user['uid'];
				// Check for pre-FAL personal dictionary folder
				try {
					$personalDictionaryFolder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier(PATH_site . $this->uploadFolder . $personalDictionaryFolderName);
				} catch (\Exception $e) {
					$personalDictionaryFolder = FALSE;
				}
				// The personal dictionary folder is created in the user's default upload folder and named BE_(uid)_personaldictionary
				if (!$personalDictionaryFolder) {
					$personalDictionaryFolderName .= '_personaldictionary';
					$backendUserDefaultFolder = $GLOBALS['BE_USER']->getDefaultUploadFolder();
					if ($backendUserDefaultFolder->hasFolder($personalDictionaryFolderName)) {
						$personalDictionaryFolder = $backendUserDefaultFolder->getSubfolder($personalDictionaryFolderName);
					} else {
						$personalDictionaryFolder = $backendUserDefaultFolder->createFolder($personalDictionaryFolderName);
					}
				}
				$this->personalDictionaryPath = PATH_site . rtrim($personalDictionaryFolder->getPublicUrl(), '/');
			}
		}
		return $this->personalDictionaryPath;
	}

	/**
	 * Ensures that the personal dictionary is utf-8 encoded
	 *
	 * @return void
	 */
	protected function fixPersonalDictionaryCharacterSet() {
		if ($this->personalDictionaryPath) {
			// Fix the options of the personl word list and of the replacement pairs files
			// Aspell creates such files only for the main dictionary
			$fileNames = array();
			$mainDictionary = preg_split('/[-_]/', $this->dictionary, 2);
			$fileNames[0] = $this->personalDictionaryPath . '/' . '.aspell.' . $mainDictionary[0] . '.pws';
			$fileNames[1] = $this->personalDictionaryPath . '/' . '.aspell.' . $mainDictionary[0] . '.prepl';
			foreach ($fileNames as $fileName) {
				if (file_exists($fileName)) {
					$fileContent = file_get_contents($fileName);
					if ($fileContent === FALSE) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('SpellChecker personal word list read error: ' . $fileName, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					} else {
						$fileContent = explode(LF, $fileContent);
						if (strpos($fileContent[0], 'utf-8') === FALSE) {
							$fileContent[0] .= ' utf-8';
							$fileContent = implode(LF, $fileContent);
							$result = file_put_contents($fileName, $fileContent);
							if ($result === FALSE) {
								\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('SpellChecker personal word list write error: ' . $fileName, $this->extKey, \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
							}
						}
					}
				}
				$fileContent = implode(LF, $fileContent);
				file_put_contents($fileName, $fileContent);
			}
		}
	}

	/**
	 * @todo Define visibility
	 */
	public function startHandler($xml_parser, $tag, $attributes) {
		if (strlen($this->xmlCharacterData)) {
			$this->spellCheckHandler($xml_parser, $this->xmlCharacterData);
			$this->xmlCharacterData = '';
		}
		switch ($tag) {
			case 'spellchecker':
				break;
			case 'br':

			case 'BR':

			case 'img':

			case 'IMG':

			case 'hr':

			case 'HR':

			case 'area':

			case 'AREA':
				$this->text .= '<' . $this->csConvObj->conv_case($this->parserCharset, $tag, 'toLower') . ' ';
				foreach ($attributes as $key => $val) {
					$this->text .= $key . '="' . $val . '" ';
				}
				$this->text .= ' />';
				break;
			default:
				$this->text .= '<' . $this->csConvObj->conv_case($this->parserCharset, $tag, 'toLower') . ' ';
				foreach ($attributes as $key => $val) {
					$this->text .= $key . '="' . $val . '" ';
				}
				$this->text .= '>';
				break;
		}
	}

	/**
	 * @todo Define visibility
	 */
	public function endHandler($xml_parser, $tag) {
		if (strlen($this->xmlCharacterData)) {
			$this->spellCheckHandler($xml_parser, $this->xmlCharacterData);
			$this->xmlCharacterData = '';
		}
		switch ($tag) {
			case 'spellchecker':
				break;
			case 'br':

			case 'BR':

			case 'img':

			case 'IMG':

			case 'hr':

			case 'HR':

			case 'input':

			case 'INPUT':

			case 'area':

			case 'AREA':
				break;
			default:
				$this->text .= '</' . $tag . '>';
				break;
		}
	}

	/**
	 * @todo Define visibility
	 */
	public function spellCheckHandler($xml_parser, $string) {
		$incurrent = array();
		$stringText = $string;
		$words = preg_split($this->parserCharset == 'utf-8' ? '/\\P{L}+/u' : '/\\W+/', $stringText);
		while (list(, $word) = each($words)) {
			$word = preg_replace('/ /' . ($this->parserCharset == 'utf-8' ? 'u' : ''), '', $word);
			if ($word && !is_numeric($word)) {
				if ($this->pspell_is_available && !$this->forceCommandMode) {
					if (!pspell_check($this->pspell_link, $word)) {
						if (!in_array($word, $this->misspelled)) {
							if (sizeof($this->misspelled) != 0) {
								$this->suggestedWords .= ',';
							}
							$suggest = array();
							$suggest = pspell_suggest($this->pspell_link, $word);
							if (sizeof($suggest) != 0) {
								$this->suggestionCount++;
								$this->suggestedWordCount += sizeof($suggest);
							}
							$this->suggestedWords .= '"' . $word . '":"' . implode(',', $suggest) . '"';
							$this->misspelled[] = $word;
							unset($suggest);
						}
						if (!in_array($word, $incurrent)) {
							$stringText = preg_replace('/\\b' . $word . '\\b/' . ($this->parserCharset == 'utf-8' ? 'u' : ''), '<span class="htmlarea-spellcheck-error">' . $word . '</span>', $stringText);
							$incurrent[] = $word;
						}
					}
				} else {
					$tmpFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam($this->filePrefix);
					if (!($filehandle = fopen($tmpFileName, 'wb'))) {
						echo 'SpellChecker tempfile open error';
					}
					if (!fwrite($filehandle, $word)) {
						echo 'SpellChecker tempfile write error';
					}
					if (!fclose($filehandle)) {
						echo 'SpellChecker tempfile close error';
					}
					$catCommand = TYPO3_OS == 'WIN' ? 'type' : 'cat';
					$AspellCommand = $catCommand . ' ' . escapeshellarg($tmpFileName) . ' | ' . $this->AspellDirectory . ' -a check --mode=none --sug-mode=' . escapeshellarg($this->pspellMode) . ($this->personalDictionaryPath ? ' --home-dir=' . escapeshellarg($this->personalDictionaryPath) : '') . ' --lang=' . escapeshellarg($this->dictionary) . ' --encoding=' . escapeshellarg($this->aspellEncoding) . ' 2>&1';
					$AspellAnswer = shell_exec($AspellCommand);
					$AspellResultLines = array();
					$AspellResultLines = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $AspellAnswer, 1);
					if (substr($AspellResultLines[0], 0, 6) == 'Error:') {
						echo '{' . $AspellAnswer . '}';
					}
					\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($tmpFileName);
					if (substr($AspellResultLines['1'], 0, 1) != '*') {
						if (!in_array($word, $this->misspelled)) {
							if (sizeof($this->misspelled) != 0) {
								$this->suggestedWords .= ',';
							}
							$suggest = array();
							$suggestions = array();
							if (substr($AspellResultLines['1'], 0, 1) == '&') {
								$suggestions = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $AspellResultLines['1'], 1);
								$suggest = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $suggestions['1'], 1);
							}
							if (sizeof($suggest) != 0) {
								$this->suggestionCount++;
								$this->suggestedWordCount += sizeof($suggest);
							}
							$this->suggestedWords .= '"' . $word . '":"' . implode(',', $suggest) . '"';
							$this->misspelled[] = $word;
							unset($suggest);
							unset($suggestions);
						}
						if (!in_array($word, $incurrent)) {
							$stringText = preg_replace('/\\b' . $word . '\\b/' . ($this->parserCharset == 'utf-8' ? 'u' : ''), '<span class="htmlarea-spellcheck-error">' . $word . '</span>', $stringText);
							$incurrent[] = $word;
						}
					}
					unset($AspellResultLines);
				}
				$this->wordCount++;
			}
		}
		$this->text .= $stringText;
		unset($incurrent);
	}

	/**
	 * @todo Define visibility
	 */
	public function collectDataHandler($xml_parser, $string) {
		$this->xmlCharacterData .= $string;
	}

	/**
	 * @todo Define visibility
	 */
	public function defaultHandler($xml_parser, $string) {
		$this->text .= $string;
	}

}


?>