<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Spell checking plugin 'tx_rtehtmlarea_pi1' for the htmlArea RTE extension.
 */
class SpellCheckingController
{
    /**
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    protected $csConvObj;

    // The extension key
    /**
     * @var string
     */
    public $extKey = 'rtehtmlarea';

    /**
     * @var string
     */
    public $siteUrl;

    /**
     * @var string
     */
    public $charset = 'utf-8';

    /**
     * @var string
     */
    public $parserCharset = 'utf-8';

    /**
     * @var string
     */
    public $defaultAspellEncoding = 'utf-8';

    /**
     * @var string
     */
    public $aspellEncoding;

    /**
     * @var string
     */
    public $result;

    /**
     * @var string
     */
    public $text;

    /**
     * @var array
     */
    public $misspelled = [];

    /**
     * @var array
     */
    public $suggestedWords;

    /**
     * @var int
     */
    public $wordCount = 0;

    /**
     * @var int
     */
    public $suggestionCount = 0;

    /**
     * @var int
     */
    public $suggestedWordCount = 0;

    /**
     * @var int
     */
    public $pspell_link;

    /**
     * @var string
     */
    public $pspellMode = 'normal';

    /**
     * @var string
     */
    public $dictionary;

    /**
     * @var string
     */
    public $AspellDirectory;

    /**
     * @var bool
     */
    public $pspell_is_available;

    /**
     * @var bool
     */
    public $forceCommandMode = 0;

    /**
     * @var string
     */
    public $filePrefix = 'rtehtmlarea_';

    // Pre-FAL backward compatibility
    protected $uploadFolder = 'uploads/tx_rtehtmlarea/';

    // Path to main dictionary
    protected $mainDictionaryPath;

    // Path to personal dictionary
    protected $personalDictionaryPath;

    /**
     * @var string
     */
    public $xmlCharacterData = '';

    /**
     * AJAX entry point
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \UnexpectedValueException
     */
    public function main(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->processRequest($request, $response);
    }

    /**
     * Main class of Spell Checker plugin
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->csConvObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
        // Setting start time
        $time_start = microtime(true);
        $this->pspell_is_available = in_array('pspell', get_loaded_extensions());
        $this->AspellDirectory = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['plugins']['SpellChecker']['AspellDirectory']) ?: '/usr/bin/aspell';
        // Setting command mode if requested and available
        $this->forceCommandMode = trim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['plugins']['SpellChecker']['forceCommandMode']) ?: 0;
        if (!$this->pspell_is_available || $this->forceCommandMode) {
            $AspellVersionString = explode('Aspell', shell_exec($this->AspellDirectory . ' -v'));
            $AspellVersion = substr($AspellVersionString[1], 0, 4);
            if (floatval($AspellVersion) < floatval('0.5') && (!$this->pspell_is_available || $this->forceCommandMode)) {
                echo 'Configuration problem: Aspell version ' . $AspellVersion . ' too old. Spell checking cannot be performed in command mode.';
            }
            $this->defaultAspellEncoding = trim(shell_exec($this->AspellDirectory . ' config encoding'));
        }
        // Setting the list of dictionaries
        $dictionaryList = shell_exec($this->AspellDirectory . ' dump dicts');
        $dictionaryList = implode(',', GeneralUtility::trimExplode(LF, $dictionaryList, true));
        $dictionaryArray = GeneralUtility::trimExplode(',', $dictionaryList, true);
        $restrictToDictionaries = GeneralUtility::_POST('restrictToDictionaries');
        if ($restrictToDictionaries) {
            $dictionaryArray = array_intersect($dictionaryArray, GeneralUtility::trimExplode(',', $restrictToDictionaries, 1));
        }
        if (empty($dictionaryArray)) {
            $dictionaryArray[] = 'en';
        }
        $this->dictionary = GeneralUtility::_POST('dictionary');
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
        $this->pspellMode = GeneralUtility::_POST('pspell_mode') ? GeneralUtility::_POST('pspell_mode') : $this->pspellMode;
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
                // sanitize $this->pspellMode
                $this->pspellMode = 'normal';
        }
        // Setting the charset
        if (GeneralUtility::_POST('pspell_charset')) {
            $this->charset = trim(GeneralUtility::_POST('pspell_charset'));
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
        $cmd = GeneralUtility::_POST('cmd');
        if ($cmd == 'learn') {
            // Only availble for BE_USERS, die silently if someone has gotten here by accident
            if (TYPO3_MODE !== 'BE' || !is_object($GLOBALS['BE_USER'])) {
                die('');
            }
            // Updating the personal word list
            $to_p_dict = GeneralUtility::_POST('to_p_dict');
            $to_p_dict = $to_p_dict ? $to_p_dict : [];
            $to_r_list = GeneralUtility::_POST('to_r_list');
            $to_r_list = $to_r_list ? $to_r_list : [];
            header('Content-Type: text/plain; charset=' . strtoupper($this->parserCharset));
            header('Pragma: no-cache');
            if ($to_p_dict || $to_r_list) {
                $tmpFileName = GeneralUtility::tempnam($this->filePrefix);
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
                    if ($result === false) {
                        GeneralUtility::sysLog('SpellChecker tempfile write error: ' . $tmpFileName, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
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
                        GeneralUtility::unlink_tempfile($tmpFileName);
                    }
                } else {
                    GeneralUtility::sysLog('SpellChecker tempfile open error: ' . $tmpFileName, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
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
<link rel="stylesheet" type="text/css" media="all" href="' . (TYPO3_MODE == 'BE' ? '../' : '') . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . '/Resources/Public/Css/Skin/Plugins/spell-checker-iframe.css" />
<script type="text/javascript">
/*<![CDATA[*/
<!--
';
            // Getting the input content
            $content = GeneralUtility::_POST('content');
            // Parsing the input HTML
            $parser = xml_parser_create(strtoupper($this->parserCharset));
            // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
            $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
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
                throw new \UnexpectedValueException('Line ' . xml_get_current_line_number($parser) . ': ' . xml_error_string(xml_get_error_code($parser)), 1294585788);
            }
            libxml_disable_entity_loader($previousValueOfEntityLoader);
            xml_parser_free($parser);
            if ($this->pspell_is_available && !$this->forceCommandMode) {
                pspell_clear_session($this->pspell_link);
            }
            $this->result .= 'var suggestedWords = {' . $this->suggestedWords . '};
var dictionaries = "' . $dictionaryList . '";
var selectedDictionary = "' . $this->dictionary . '";
';
            // Calculating parsing and spell checkting time
            $time = number_format(microtime(true) - $time_start, 2, ',', ' ');
            // Insert spellcheck info
            $this->result .= 'var spellcheckInfo = { "Total words":"' . $this->wordCount . '","Misspelled words":"' . count($this->misspelled) . '","Total suggestions":"' . $this->suggestionCount . '","Total words suggested":"' . $this->suggestedWordCount . '","Spelling checked in":"' . $time . '" };
// -->
/*]]>*/
</script>
</head>
';
            $this->result .= '<body onload="window.parent.RTEarea[' . GeneralUtility::quoteJSvalue(GeneralUtility::_POST('editorId')) . '].editor.getPlugin(\'SpellChecker\').spellCheckComplete();">';
            $this->result .= preg_replace('/' . preg_quote('<?xml') . '.*' . preg_quote('?>') . '[' . preg_quote((LF . CR . chr(32))) . ']*/' . ($this->parserCharset == 'utf-8' ? 'u' : ''), '', $this->text);
            $this->result .= '<div style="display: none;">' . $dictionaries . '</div>';
            // Closing
            $this->result .= '
</body></html>';
            // Outputting
            $response = $response->withHeader('Content-Type', 'text/html; charset=' . strtoupper($this->parserCharset));
            $response->getBody()->write($this->result);
            return $response;
        }
    }

    /**
     * Sets the path to the main dictionary
     *
     * @return string path to the main dictionary
     */
    protected function setMainDictionaryPath()
    {
        $this->mainDictionaryPath = '';
        $aspellCommand = $this->AspellDirectory . ' config dict-dir';
        $aspellResult = shell_exec($aspellCommand);
        if ($aspellResult) {
            $this->mainDictionaryPath = trim($aspellResult);
        }
        if (!$aspellResult || !$this->mainDictionaryPath) {
            GeneralUtility::sysLog('SpellChecker main dictionary path retrieval error: ' . $aspellCommand, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
        }
        return $this->mainDictionaryPath;
    }

    /**
     * Gets the character set the main dictionary
     *
     * @return string character set the main dictionary
     */
    protected function getMainDictionaryCharacterSet()
    {
        $characterSet = '';
        if ($this->mainDictionaryPath) {
            // Keep only the first part of the dictionary name
            $mainDictionary = preg_split('/[-_]/', $this->dictionary, 2);
            // Read the options of the dictionary
            $dictionaryFileName = $this->mainDictionaryPath . '/' . $mainDictionary[0] . '.dat';
            $dictionaryHandle = fopen($dictionaryFileName, 'rb');
            if (!$dictionaryHandle) {
                GeneralUtility::sysLog('SpellChecker main dictionary open error: ' . $dictionaryFileName, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
            } else {
                $dictionaryContent = fread($dictionaryHandle, 500);
                if ($dictionaryContent === false) {
                    GeneralUtility::sysLog('SpellChecker main dictionary read error: ' . $dictionaryFileName, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                } else {
                    fclose($dictionaryHandle);
                    // Get the line that contains the character set option
                    $dictionaryContent = preg_split('/charset\s*/', $dictionaryContent, 2);
                    if ($dictionaryContent[1]) {
                        // Isolate the character set
                        $dictionaryContent = GeneralUtility::trimExplode(LF, $dictionaryContent[1]);
                        // Fix Aspell character set oddity (i.e. iso8859-1)
                        $characterSet = str_replace(
                            ['iso', '--'],
                            ['iso-', '-'],
                            $dictionaryContent[0]
                        );
                    }
                    if (!$characterSet) {
                        GeneralUtility::sysLog('SpellChecker main dictionary character set retrieval error: ' . $dictionaryContent[1], $this->extKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
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
    protected function setPersonalDictionaryPath()
    {
        $this->personalDictionaryPath = '';
        if (GeneralUtility::_POST('enablePersonalDicts') == 'true' && TYPO3_MODE == 'BE' && is_object($GLOBALS['BE_USER'])) {
            if ($GLOBALS['BE_USER']->user['uid']) {
                $personalDictionaryFolderName = 'BE_' . $GLOBALS['BE_USER']->user['uid'];
                // Check for pre-FAL personal dictionary folder
                try {
                    $personalDictionaryFolder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier(PATH_site . $this->uploadFolder . $personalDictionaryFolderName);
                } catch (\Exception $e) {
                    $personalDictionaryFolder = false;
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
    protected function fixPersonalDictionaryCharacterSet()
    {
        if ($this->personalDictionaryPath) {
            // Fix the options of the personl word list and of the replacement pairs files
            // Aspell creates such files only for the main dictionary
            $fileNames = [];
            $mainDictionary = preg_split('/[-_]/', $this->dictionary, 2);
            $fileNames[0] = $this->personalDictionaryPath . '/' . '.aspell.' . $mainDictionary[0] . '.pws';
            $fileNames[1] = $this->personalDictionaryPath . '/' . '.aspell.' . $mainDictionary[0] . '.prepl';
            foreach ($fileNames as $fileName) {
                if (file_exists($fileName)) {
                    $fileContent = file_get_contents($fileName);
                    if ($fileContent === false) {
                        GeneralUtility::sysLog('SpellChecker personal word list read error: ' . $fileName, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                    } else {
                        $fileContent = explode(LF, $fileContent);
                        if (strpos($fileContent[0], 'utf-8') === false) {
                            $fileContent[0] .= ' utf-8';
                            $fileContent = implode(LF, $fileContent);
                            $result = file_put_contents($fileName, $fileContent);
                            if ($result === false) {
                                GeneralUtility::sysLog('SpellChecker personal word list write error: ' . $fileName, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_ERROR);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Handler for the opening of a tag
     */
    public function startHandler($xml_parser, $tag, $attributes)
    {
        if ((string)$this->xmlCharacterData !== '') {
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
        }
    }

    /**
     * Handler for the closing of a tag
     */
    public function endHandler($xml_parser, $tag)
    {
        if ((string)$this->xmlCharacterData !== '') {
            $this->spellCheckHandler($xml_parser, $this->xmlCharacterData);
            $this->xmlCharacterData = '';
        }
        switch ($tag) {
            case 'spellchecker':

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
        }
    }

    /**
     * Handler for the content of a tag
     */
    public function spellCheckHandler($xml_parser, $string)
    {
        $incurrent = [];
        $stringText = $string;
        $words = preg_split($this->parserCharset == 'utf-8' ? '/\\P{L}+/u' : '/\\W+/', $stringText);
        foreach ($words as $word) {
            $word = preg_replace('/ /' . ($this->parserCharset == 'utf-8' ? 'u' : ''), '', $word);
            if ($word && !is_numeric($word)) {
                if ($this->pspell_is_available && !$this->forceCommandMode) {
                    if (!pspell_check($this->pspell_link, $word)) {
                        if (!in_array($word, $this->misspelled)) {
                            if (count($this->misspelled) != 0) {
                                $this->suggestedWords .= ',';
                            }
                            $suggest = [];
                            $suggest = pspell_suggest($this->pspell_link, $word);
                            if (count($suggest) != 0) {
                                $this->suggestionCount++;
                                $this->suggestedWordCount += count($suggest);
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
                    $tmpFileName = GeneralUtility::tempnam($this->filePrefix);
                    if (!($filehandle = fopen($tmpFileName, 'wb'))) {
                        echo 'SpellChecker tempfile open error';
                    }
                    if (!fwrite($filehandle, $word)) {
                        echo 'SpellChecker tempfile write error';
                    }
                    if (!fclose($filehandle)) {
                        echo 'SpellChecker tempfile close error';
                    }
                    $catCommand = TYPO3_OS === 'WIN' ? 'type' : 'cat';
                    $AspellCommand = $catCommand . ' ' . escapeshellarg($tmpFileName) . ' | '
                        . $this->AspellDirectory
                        . ' -a check'
                        . ' --mode=none'
                        . ' --sug-mode=' . escapeshellarg($this->pspellMode)
                        . ($this->personalDictionaryPath ? ' --home-dir=' . escapeshellarg($this->personalDictionaryPath) : '')
                        . ' --lang=' . escapeshellarg($this->dictionary)
                        . ' --encoding=' . escapeshellarg($this->aspellEncoding)
                        . ' 2>&1';
                    $AspellAnswer = shell_exec($AspellCommand);
                    $AspellResultLines = [];
                    $AspellResultLines = GeneralUtility::trimExplode(LF, $AspellAnswer, true);
                    if (substr($AspellResultLines[0], 0, 6) == 'Error:') {
                        echo '{' . $AspellAnswer . '}';
                    }
                    GeneralUtility::unlink_tempfile($tmpFileName);
                    if ($AspellResultLines['1'][0] !== '*') {
                        if (!in_array($word, $this->misspelled)) {
                            if (count($this->misspelled) != 0) {
                                $this->suggestedWords .= ',';
                            }
                            $suggest = [];
                            $suggestions = [];
                            if ($AspellResultLines['1'][0] === '&') {
                                $suggestions = GeneralUtility::trimExplode(':', $AspellResultLines['1'], true);
                                $suggest = GeneralUtility::trimExplode(',', $suggestions['1'], true);
                            }
                            if (count($suggest) != 0) {
                                $this->suggestionCount++;
                                $this->suggestedWordCount += count($suggest);
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
     * Handler for collecting data within a tag
     */
    public function collectDataHandler($xml_parser, $string)
    {
        $this->xmlCharacterData .= $string;
    }

    /**
     * Default handler for the xml parser
     */
    public function defaultHandler($xml_parser, $string)
    {
        $this->text .= $string;
    }
}
