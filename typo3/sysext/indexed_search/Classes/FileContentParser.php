<?php

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

namespace TYPO3\CMS\IndexedSearch;

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * External standard parsers for indexed_search
 * MUST RETURN utf-8 content!
 * @internal will be removed, in favor of unified Content Extractor API.
 */
class FileContentParser
{
    /**
     * This value is also overridden from config.
     * zero: whole PDF file is indexed in one. positive value: Indicates number of pages at a time, eg. "5" would means 1-5,6-10,....
     * Negative integer would indicate (abs value) number of groups. Eg "3" groups of 10 pages would be 1-4,5-8,9-10
     *
     * @var int
     */
    public $pdf_mode = -20;

    /**
     * @var array
     */
    public $app = [];

    /**
     * @var array
     */
    public $ext2itemtype_map = [];

    /**
     * @var array
     */
    public $supportedExtensions = [];

    /**
     * @var \TYPO3\CMS\IndexedSearch\Indexer
     */
    public $pObj;

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageService|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $langObject;

    /**
     * @var string|null Backup for setLocaleForServerFileSystem()
     */
    protected $lastLocale;

    /**
     * Constructs this external parsers object
     */
    public function __construct()
    {
        // Set the language object to be used accordant to current application type
        $this->langObject = ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() ? $GLOBALS['TSFE'] : $GLOBALS['LANG'];
    }

    /**
     * Initialize external parser for parsing content.
     *
     * @param string $extension File extension
     * @return bool Returns TRUE if extension is supported/enabled, otherwise FALSE.
     */
    public function initParser($extension)
    {
        // Then read indexer-config and set if appropriate:
        $indexerConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('indexed_search');
        // If windows, apply extension to tool name:
        $exe = Environment::isWindows() ? '.exe' : '';
        // lg
        $extOK = false;
        $mainExtension = '';
        // Ignore extensions
        $ignoreExtensions = GeneralUtility::trimExplode(',', strtolower($indexerConfig['ignoreExtensions']), true);
        if (in_array($extension, $ignoreExtensions)) {
            $this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:ignoreExtensions'), $extension), LogLevel::WARNING);
            return false;
        }
        // Switch on file extension:
        switch ($extension) {
            case 'pdf':
                // PDF
                if ($indexerConfig['pdftools']) {
                    $pdfPath = rtrim($indexerConfig['pdftools'], '/') . '/';
                    if (@is_file($pdfPath . 'pdftotext' . $exe) && @is_file($pdfPath . 'pdfinfo' . $exe)) {
                        $this->app['pdfinfo'] = $pdfPath . 'pdfinfo' . $exe;
                        $this->app['pdftotext'] = $pdfPath . 'pdftotext' . $exe;
                        // PDF mode:
                        $this->pdf_mode = MathUtility::forceIntegerInRange($indexerConfig['pdf_mode'], -100, 100);
                        $extOK = true;
                    } else {
                        $this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:pdfToolsNotFound'), $pdfPath), LogLevel::ERROR);
                    }
                } else {
                    $this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:pdfToolsDisabled'), LogLevel::NOTICE);
                }
                break;
            case 'doc':
                // Catdoc
                if ($indexerConfig['catdoc']) {
                    $catdocPath = rtrim($indexerConfig['catdoc'], '/') . '/';
                    if (@is_file($catdocPath . 'catdoc' . $exe)) {
                        $this->app['catdoc'] = $catdocPath . 'catdoc' . $exe;
                        $extOK = true;
                    } else {
                        $this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:catdocNotFound'), $catdocPath), LogLevel::ERROR);
                    }
                } else {
                    $this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:catdocDisabled'), LogLevel::NOTICE);
                }
                break;
            case 'pps':
            case 'ppt':
                // MS PowerPoint
                // ppthtml
                if ($indexerConfig['ppthtml']) {
                    $ppthtmlPath = rtrim($indexerConfig['ppthtml'], '/') . '/';
                    if (@is_file($ppthtmlPath . 'ppthtml' . $exe)) {
                        $this->app['ppthtml'] = $ppthtmlPath . 'ppthtml' . $exe;
                        $extOK = true;
                    } else {
                        $this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:ppthtmlNotFound'), $ppthtmlPath), LogLevel::ERROR);
                    }
                } else {
                    $this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:ppthtmlDisabled'), LogLevel::NOTICE);
                }
                break;
            case 'xls':
                // MS Excel
                // Xlhtml
                if ($indexerConfig['xlhtml']) {
                    $xlhtmlPath = rtrim($indexerConfig['xlhtml'], '/') . '/';
                    if (@is_file($xlhtmlPath . 'xlhtml' . $exe)) {
                        $this->app['xlhtml'] = $xlhtmlPath . 'xlhtml' . $exe;
                        $extOK = true;
                    } else {
                        $this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:xlhtmlNotFound'), $xlhtmlPath), LogLevel::ERROR);
                    }
                } else {
                    $this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:xlhtmlDisabled'), LogLevel::NOTICE);
                }
                break;
            case 'docx':    // Microsoft Word >= 2007
            case 'dotx':
            case 'pptx':    // Microsoft PowerPoint >= 2007
            case 'ppsx':
            case 'potx':
            case 'xlsx':    // Microsoft Excel >= 2007
            case 'xltx':
                if ($indexerConfig['unzip']) {
                    $unzipPath = rtrim($indexerConfig['unzip'], '/') . '/';
                    if (@is_file($unzipPath . 'unzip' . $exe)) {
                        $this->app['unzip'] = $unzipPath . 'unzip' . $exe;
                        $extOK = true;
                    } else {
                        $this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:unzipNotFound'), $unzipPath), LogLevel::ERROR);
                    }
                } else {
                    $this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:unzipDisabled'), LogLevel::NOTICE);
                }
                break;
            case 'sxc':
            case 'sxi':
            case 'sxw':
            case 'ods':
            case 'odp':
            case 'odt':
                // Oasis OpenDocument Text
                if ($indexerConfig['unzip']) {
                    $unzipPath = rtrim($indexerConfig['unzip'], '/') . '/';
                    if (@is_file($unzipPath . 'unzip' . $exe)) {
                        $this->app['unzip'] = $unzipPath . 'unzip' . $exe;
                        $extOK = true;
                    } else {
                        $this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:unzipNotFound'), $unzipPath), LogLevel::ERROR);
                    }
                } else {
                    $this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:unzipDisabled'), LogLevel::NOTICE);
                }
                break;
            case 'rtf':
                // Catdoc
                if ($indexerConfig['unrtf']) {
                    $unrtfPath = rtrim($indexerConfig['unrtf'], '/') . '/';
                    if (@is_file($unrtfPath . 'unrtf' . $exe)) {
                        $this->app['unrtf'] = $unrtfPath . 'unrtf' . $exe;
                        $extOK = true;
                    } else {
                        $this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:unrtfNotFound'), $unrtfPath), LogLevel::ERROR);
                    }
                } else {
                    $this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:unrtfDisabled'), LogLevel::NOTICE);
                }
                break;
            case 'txt':
            case 'csv':
            case 'xml':
            case 'tif':
                // PHP EXIF
                $extOK = true;
                break;
            case 'html':
            case 'htm':
                // PHP strip-tags()
                $extOK = true;
                $mainExtension = 'html';
                // making "html" the common "item_type"
                break;
            case 'jpg':
            case 'jpeg':
                // PHP EXIF
                $extOK = true;
                $mainExtension = 'jpeg';
                // making "jpeg" the common item_type
                break;
        }
        // If extension was OK:
        if ($extOK) {
            $this->supportedExtensions[$extension] = true;
            $this->ext2itemtype_map[$extension] = $mainExtension ?: $extension;
            return true;
        }
        return false;
    }

    /**
     * Initialize external parser for backend modules
     * Doesn't evaluate if parser is configured right - more like returning POSSIBLE supported extensions (for showing icons etc) in backend and frontend plugin
     *
     * @param string $extension File extension to initialize for.
     * @return bool Returns TRUE if the extension is supported and enabled, otherwise FALSE.
     */
    public function softInit($extension)
    {
        switch ($extension) {
            case 'pdf':
            case 'doc':
            case 'docx':
            case 'dotx':
            case 'pps':
            case 'ppsx':
            case 'ppt':
            case 'pptx':
            case 'potx':
            case 'xls':
            case 'xlsx':
            case 'xltx':
            case 'sxc':
            case 'sxi':
            case 'sxw':
            case 'ods':
            case 'odp':
            case 'odt':
            case 'rtf':
            case 'txt':
            case 'html':
            case 'htm':
            case 'csv':
            case 'xml':
            case 'jpg':
            case 'jpeg':
            case 'tif':
                // TIF images (EXIF comment)
                return true;
        }
        return false;
    }

    /**
     * Return title of entry in media type selector box.
     *
     * @param string $extension File extension
     * @return string|false String with label value of entry in media type search selector box (frontend plugin).
     */
    public function searchTypeMediaTitle($extension)
    {
        // Read indexer-config
        $indexerConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('indexed_search');
        // Ignore extensions
        $ignoreExtensions = GeneralUtility::trimExplode(',', strtolower($indexerConfig['ignoreExtensions']), true);
        if (in_array($extension, $ignoreExtensions)) {
            return false;
        }
        // Switch on file extension:
        switch ($extension) {
            case 'pdf':
                // PDF
                if ($indexerConfig['pdftools']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.PDF'), $extension);
                }
                break;
            case 'doc':
                // Catdoc
                if ($indexerConfig['catdoc']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.DOC'), $extension);
                }
                break;
            case 'pps':
            case 'ppt':
                // MS PowerPoint
                // ppthtml
                if ($indexerConfig['ppthtml']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.PP'), $extension);
                }
                break;
            case 'xls':
                // MS Excel
                // Xlhtml
                if ($indexerConfig['xlhtml']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.XLS'), $extension);
                }
                break;
            case 'docx':
            case 'dotx':
                // Microsoft Word >= 2007
                if ($indexerConfig['unzip']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.DOC'), $extension);
                }
                break;
            case 'pptx':    // Microsoft PowerPoint >= 2007
            case 'ppsx':
            case 'potx':
                if ($indexerConfig['unzip']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.PP'), $extension);
                }
                break;
            case 'xlsx':    // Microsoft Excel >= 2007
            case 'xltx':
                if ($indexerConfig['unzip']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.XLS'), $extension);
                }
                break;
            case 'sxc':
                // Open Office Calc.
                if ($indexerConfig['unzip']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.SXC'), $extension);
                }
                break;
            case 'sxi':
                // Open Office Impress
                if ($indexerConfig['unzip']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.SXI'), $extension);
                }
                break;
            case 'sxw':
                // Open Office Writer
                if ($indexerConfig['unzip']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.SXW'), $extension);
                }
                break;
            case 'ods':
                // Oasis OpenDocument Spreadsheet
                if ($indexerConfig['unzip']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.ODS'), $extension);
                }
                break;
            case 'odp':
                // Oasis OpenDocument Presentation
                if ($indexerConfig['unzip']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.ODP'), $extension);
                }
                break;
            case 'odt':
                // Oasis OpenDocument Text
                if ($indexerConfig['unzip']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.ODT'), $extension);
                }
                break;
            case 'rtf':
                // Catdoc
                if ($indexerConfig['unrtf']) {
                    return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.RTF'), $extension);
                }
                break;
            case 'jpeg':
            case 'jpg':
            case 'tif':
                // PHP EXIF
                return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.images'), $extension);
            case 'html':
            case 'htm':
                // PHP strip-tags()
                return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.HTML'), $extension);
            case 'txt':
                // Raw text
                return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.TXT'), $extension);
            case 'csv':
                // Raw text
                return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.CSV'), $extension);
            case 'xml':
                // PHP strip-tags()
                return sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:extension.XML'), $extension);
            default:
                // Do nothing
        }
        return '';
    }

    /**
     * Returns TRUE if the input extension (item_type) is a potentially a multi-page extension
     *
     * @param string $extension Extension / item_type string
     * @return bool Return TRUE if multi-page
     */
    public function isMultiplePageExtension($extension)
    {
        // Switch on file extension:
        switch ((string)$extension) {
            case 'pdf':
                return true;
        }
        return false;
    }

    /**
     * Wraps the "splitLabel function" of the language object.
     *
     * @param string $reference Reference/key of the label
     * @return string The label of the reference/key to be fetched
     */
    protected function sL($reference)
    {
        return $this->langObject->sL($reference);
    }

    /************************
     *
     * Reading documents (for parsing)
     *
     ************************/
    /**
     * Reads the content of an external file being indexed.
     *
     * @param string $ext File extension, eg. "pdf", "doc" etc.
     * @param string $absFile Absolute filename of file (must exist and be validated OK before calling function)
     * @param string $cPKey Pointer to section (zero for all other than PDF which will have an indication of pages into which the document should be split.)
     * @return array|false|null Standard content array (title, description, keywords, body keys), false if the extension is not supported or null if nothing found
     */
    public function readFileContent($ext, $absFile, $cPKey)
    {
        $contentArr = null;
        // Return immediately if initialization didn't set support up:
        if (!$this->supportedExtensions[$ext]) {
            return false;
        }
        // Switch by file extension
        switch ($ext) {
            case 'pdf':
                if ($this->app['pdfinfo']) {
                    $this->setLocaleForServerFileSystem();
                    // Getting pdf-info:
                    $cmd = $this->app['pdfinfo'] . ' ' . escapeshellarg($absFile);
                    CommandUtility::exec($cmd, $res);
                    $pdfInfo = $this->splitPdfInfo($res);
                    unset($res);
                    if ((int)$pdfInfo['pages']) {
                        [$low, $high] = explode('-', $cPKey);
                        // Get pdf content:
                        $tempFileName = GeneralUtility::tempnam('Typo3_indexer');
                        // Create temporary name
                        @unlink($tempFileName);
                        // Delete if exists, just to be safe.
                        $cmd = $this->app['pdftotext'] . ' -f ' . $low . ' -l ' . $high . ' -enc UTF-8 -q ' . escapeshellarg($absFile) . ' ' . $tempFileName;
                        CommandUtility::exec($cmd);
                        if (@is_file($tempFileName)) {
                            $content = (string)file_get_contents($tempFileName);
                            unlink($tempFileName);
                        } else {
                            $content = '';
                            $this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang_main.xlf:pdfToolsFailed'), $absFile), LogLevel::WARNING);
                        }
                        if ((string)$content !== '') {
                            $contentArr = $this->pObj->splitRegularContent($this->removeEndJunk($content));
                        }
                    }
                    if (!empty($pdfInfo['title'])) {
                        $contentArr['title'] = $pdfInfo['title'];
                    }
                    $this->setLocaleForServerFileSystem(true);
                }
                break;
            case 'doc':
                if ($this->app['catdoc']) {
                    $this->setLocaleForServerFileSystem();
                    $cmd = $this->app['catdoc'] . ' -d utf-8 ' . escapeshellarg($absFile);
                    CommandUtility::exec($cmd, $res);
                    $content = implode(LF, $res);
                    unset($res);
                    $contentArr = $this->pObj->splitRegularContent($this->removeEndJunk($content));
                    $this->setLocaleForServerFileSystem(true);
                }
                break;
            case 'pps':
            case 'ppt':
                if ($this->app['ppthtml']) {
                    $this->setLocaleForServerFileSystem();
                    $cmd = $this->app['ppthtml'] . ' ' . escapeshellarg($absFile);
                    CommandUtility::exec($cmd, $res);
                    $content = implode(LF, $res);
                    unset($res);
                    $content = $this->pObj->convertHTMLToUtf8($content);
                    $contentArr = $this->pObj->splitHTMLContent($this->removeEndJunk($content));
                    $contentArr['title'] = PathUtility::basename($absFile);
                    $this->setLocaleForServerFileSystem(true);
                }
                break;
            case 'xls':
                if ($this->app['xlhtml']) {
                    $this->setLocaleForServerFileSystem();
                    $cmd = $this->app['xlhtml'] . ' -nc -te ' . escapeshellarg($absFile);
                    CommandUtility::exec($cmd, $res);
                    $content = implode(LF, $res);
                    unset($res);
                    $content = $this->pObj->convertHTMLToUtf8($content);
                    $contentArr = $this->pObj->splitHTMLContent($this->removeEndJunk($content));
                    $contentArr['title'] = PathUtility::basename($absFile);
                    $this->setLocaleForServerFileSystem(true);
                }
                break;
            case 'docx':
            case 'dotx':
            case 'pptx':
            case 'ppsx':
            case 'potx':
            case 'xlsx':
            case 'xltx':
                if ($this->app['unzip']) {
                    $this->setLocaleForServerFileSystem();
                    switch ($ext) {
                        case 'docx':
                        case 'dotx':
                            // Read document.xml:
                            $cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' word/document.xml';
                            break;
                        case 'ppsx':
                        case 'pptx':
                        case 'potx':
                            // Read slide1.xml:
                            $cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' ppt/slides/slide1.xml';
                            break;
                        case 'xlsx':
                        case 'xltx':
                            // Read sheet1.xml:
                            $cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' xl/worksheets/sheet1.xml';
                            break;
                        default:
                            $cmd = '';
                            break;
                    }
                    CommandUtility::exec($cmd, $res);
                    $content_xml = implode(LF, $res);
                    unset($res);
                    $utf8_content = trim(strip_tags(str_replace('<', ' <', $content_xml)));
                    $contentArr = $this->pObj->splitRegularContent($utf8_content);
                    // Make sure the title doesn't expose the absolute path!
                    $contentArr['title'] = PathUtility::basename($absFile);
                    // Meta information
                    $cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' docProps/core.xml';
                    CommandUtility::exec($cmd, $res);
                    $meta_xml = implode(LF, $res);
                    unset($res);
                    $metaContent = GeneralUtility::xml2tree($meta_xml);
                    if (is_array($metaContent)) {
                        $contentArr['title'] .= ' ' . $metaContent['cp:coreProperties'][0]['ch']['dc:title'][0]['values'][0];
                        $contentArr['description'] = $metaContent['cp:coreProperties'][0]['ch']['dc:subject'][0]['values'][0];
                        $contentArr['description'] .= ' ' . $metaContent['cp:coreProperties'][0]['ch']['dc:description'][0]['values'][0];
                        $contentArr['keywords'] = $metaContent['cp:coreProperties'][0]['ch']['cp:keywords'][0]['values'][0];
                    }
                    $this->setLocaleForServerFileSystem(true);
                }
                break;
            case 'sxi':
            case 'sxc':
            case 'sxw':
            case 'ods':
            case 'odp':
            case 'odt':
                if ($this->app['unzip']) {
                    $this->setLocaleForServerFileSystem();
                    // Read content.xml:
                    $cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' content.xml';
                    CommandUtility::exec($cmd, $res);
                    $content_xml = implode(LF, $res);
                    unset($res);
                    // Read meta.xml:
                    $cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' meta.xml';
                    CommandUtility::exec($cmd, $res);
                    $meta_xml = implode(LF, $res);
                    unset($res);
                    $utf8_content = trim(strip_tags(str_replace('<', ' <', $content_xml)));
                    $contentArr = $this->pObj->splitRegularContent($utf8_content);
                    $contentArr['title'] = PathUtility::basename($absFile);
                    // Make sure the title doesn't expose the absolute path!
                    // Meta information
                    $metaContent = GeneralUtility::xml2tree($meta_xml);
                    $metaContent = $metaContent['office:document-meta'][0]['ch']['office:meta'][0]['ch'];
                    if (is_array($metaContent)) {
                        $contentArr['title'] = $metaContent['dc:title'][0]['values'][0] ?: $contentArr['title'];
                        $contentArr['description'] = $metaContent['dc:subject'][0]['values'][0] . ' ' . $metaContent['dc:description'][0]['values'][0];
                        // Keywords collected:
                        if (is_array($metaContent['meta:keywords'][0]['ch']['meta:keyword'])) {
                            foreach ($metaContent['meta:keywords'][0]['ch']['meta:keyword'] as $kwDat) {
                                $contentArr['keywords'] .= $kwDat['values'][0] . ' ';
                            }
                        }
                    }
                    $this->setLocaleForServerFileSystem(true);
                }
                break;
            case 'rtf':
                if ($this->app['unrtf']) {
                    $this->setLocaleForServerFileSystem();
                    $cmd = $this->app['unrtf'] . ' ' . escapeshellarg($absFile);
                    CommandUtility::exec($cmd, $res);
                    $fileContent = implode(LF, $res);
                    unset($res);
                    $fileContent = $this->pObj->convertHTMLToUtf8($fileContent);
                    $contentArr = $this->pObj->splitHTMLContent($fileContent);
                    $this->setLocaleForServerFileSystem(true);
                }
                break;
            case 'txt':
            case 'csv':
                $this->setLocaleForServerFileSystem();
                // Raw text
                $content = GeneralUtility::getUrl($absFile);
                // @todo Implement auto detection of charset (currently assuming utf-8)
                $contentCharset = 'utf-8';
                $content = $this->pObj->convertHTMLToUtf8($content, $contentCharset);
                $contentArr = $this->pObj->splitRegularContent($content);
                $contentArr['title'] = PathUtility::basename($absFile);
                // Make sure the title doesn't expose the absolute path!
                $this->setLocaleForServerFileSystem(true);
                break;
            case 'html':
            case 'htm':
                $fileContent = GeneralUtility::getUrl($absFile);
                $fileContent = $this->pObj->convertHTMLToUtf8($fileContent);
                $contentArr = $this->pObj->splitHTMLContent($fileContent);
                break;
            case 'xml':
                $this->setLocaleForServerFileSystem();
                // PHP strip-tags()
                $fileContent = GeneralUtility::getUrl($absFile);
                // Finding charset:
                preg_match('/^[[:space:]]*<\\?xml[^>]+encoding[[:space:]]*=[[:space:]]*["\'][[:space:]]*([[:alnum:]_-]+)[[:space:]]*["\']/i', substr($fileContent, 0, 200), $reg);
                $charset = $reg[1] ? trim(strtolower($reg[1])) : 'utf-8';
                // Converting content:
                $fileContent = $this->pObj->convertHTMLToUtf8(strip_tags(str_replace('<', ' <', $fileContent)), $charset);
                $contentArr = $this->pObj->splitRegularContent($fileContent);
                $contentArr['title'] = PathUtility::basename($absFile);
                // Make sure the title doesn't expose the absolute path!
                $this->setLocaleForServerFileSystem(true);
                break;
            case 'jpg':
            case 'jpeg':
            case 'tif':
                $this->setLocaleForServerFileSystem();
                // PHP EXIF
                if (function_exists('exif_read_data')) {
                    $exif = @exif_read_data($absFile, 'IFD0');
                } else {
                    $exif = false;
                }
                if ($exif) {
                    $comment = trim(($exif['COMMENT'][0] ?? '') . ' ' . ($exif['ImageDescription'] ?? ''));
                } else {
                    $comment = '';
                }
                $contentArr = $this->pObj->splitRegularContent($comment);
                $contentArr['title'] = PathUtility::basename($absFile);
                // Make sure the title doesn't expose the absolute path!
                $this->setLocaleForServerFileSystem(true);
                break;
            default:
                return false;
        }
        // If no title (and why should there be...) then the file-name is set as title. This will raise the hits considerably if the search matches the document name.
        if (is_array($contentArr) && !$contentArr['title']) {
            // Substituting "_" for " " because many filenames may have this instead of a space char.
            $contentArr['title'] = str_replace('_', ' ', PathUtility::basename($absFile));
        }
        return $contentArr;
    }

    /**
     * Sets the locale for LC_CTYPE to $TYPO3_CONF_VARS['SYS']['systemLocale']
     * if $TYPO3_CONF_VARS['SYS']['UTF8filesystem'] is set.
     *
     * Parameter <code>$resetLocale</code> has to be FALSE and TRUE alternating for all calls.
     *
     * @staticvar string $lastLocale Stores the locale used before it is overridden by this method.
     * @param bool $resetLocale TRUE resets the locale to $lastLocale.
     * @throws \RuntimeException
     */
    protected function setLocaleForServerFileSystem($resetLocale = false)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            return;
        }

        if ($resetLocale) {
            if ($this->lastLocale == null) {
                throw new \RuntimeException('Cannot reset locale to NULL.', 1357064326);
            }
            setlocale(LC_CTYPE, $this->lastLocale);
            $this->lastLocale = null;
        } else {
            if ($this->lastLocale !== null) {
                throw new \RuntimeException('Cannot set new locale as locale has already been changed before.', 1357064437);
            }
            $this->lastLocale = setlocale(LC_CTYPE, '0') ?: null;
            setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
        }
    }

    /**
     * Creates an array with pointers to divisions of document.
     *
     * ONLY for PDF files at this point. All other types will have an array with a single element with the value "0" (zero)
     * coming back.
     *
     * @param string $ext File extension
     * @param string $absFile Absolute filename (must exist and be validated OK before calling function)
     * @return array Array of pointers to sections that the document should be divided into
     */
    public function fileContentParts($ext, $absFile)
    {
        $cParts = [0];
        switch ($ext) {
            case 'pdf':
                $this->setLocaleForServerFileSystem();
                // Getting pdf-info:
                $cmd = $this->app['pdfinfo'] . ' ' . escapeshellarg($absFile);
                CommandUtility::exec($cmd, $res);
                $pdfInfo = $this->splitPdfInfo($res);
                unset($res);
                if ((int)$pdfInfo['pages']) {
                    $cParts = [];
                    // Calculate mode
                    if ($this->pdf_mode > 0) {
                        $iter = ceil($pdfInfo['pages'] / $this->pdf_mode);
                    } else {
                        $iter = MathUtility::forceIntegerInRange(abs($this->pdf_mode), 1, $pdfInfo['pages']);
                    }
                    // Traverse and create intervals.
                    for ($a = 0; $a < $iter; $a++) {
                        $low = floor($a * ($pdfInfo['pages'] / $iter)) + 1;
                        $high = floor(($a + 1) * ($pdfInfo['pages'] / $iter));
                        $cParts[] = $low . '-' . $high;
                    }
                }
                $this->setLocaleForServerFileSystem(true);
                break;
            default:
        }
        return $cParts;
    }

    /**
     * Analysing PDF info into a usable format.
     *
     * @param array $pdfInfoArray Array of PDF content, coming from the pdfinfo tool
     * @return array Result array
     * @internal
     * @see fileContentParts()
     */
    public function splitPdfInfo($pdfInfoArray)
    {
        $res = [];
        if (is_array($pdfInfoArray)) {
            foreach ($pdfInfoArray as $line) {
                $parts = explode(':', $line, 2);
                if (count($parts) > 1 && trim($parts[0])) {
                    $res[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
            }
        }
        return $res;
    }

    /**
     * Removes some strange char(12) characters and line breaks that then to occur in the end of the string from external files.
     *
     * @param string $string String to clean up
     * @return string String
     */
    public function removeEndJunk($string)
    {
        return trim((string)preg_replace('/[' . LF . chr(12) . ']*$/', '', $string));
    }

    /************************
     *
     * Backend analyzer
     *
     ************************/
    /**
     * Return icon for file extension
     *
     * @param string $extension File extension, lowercase.
     * @return string Relative file reference, resolvable by GeneralUtility::getFileAbsFileName()
     */
    public function getIcon($extension)
    {
        if ($extension === 'htm') {
            $extension = 'html';
        } elseif ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        return 'EXT:indexed_search/Resources/Public/Icons/FileTypes/' . $extension . '.gif';
    }
}
