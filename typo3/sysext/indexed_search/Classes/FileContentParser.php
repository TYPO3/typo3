<?php
namespace TYPO3\CMS\IndexedSearch;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2001-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * External standard parsers for indexed_search
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor 	Olivier Simah <noname_paris@yahoo.fr>
 */
/**
 * External standard parsers for indexed_search
 * MUST RETURN utf-8 content!
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class FileContentParser {

	// This value is also overridden from config.
	/**
	 * @todo Define visibility
	 */
	public $pdf_mode = -20;

	// zero: whole PDF file is indexed in one. positive value: Indicates number of pages at a time, eg. "5" would means 1-5,6-10,.... Negative integer would indicate (abs value) number of groups. Eg "3" groups of 10 pages would be 1-4,5-8,9-10
	// This array is configured in initialization:
	/**
	 * @todo Define visibility
	 */
	public $app = array();

	/**
	 * @todo Define visibility
	 */
	public $ext2itemtype_map = array();

	/**
	 * @todo Define visibility
	 */
	public $supportedExtensions = array();

	/**
	 * @todo Define visibility
	 */
	public $pObj;

	// Reference to parent object (indexer class)
	protected $langObject;

	// Reference to LANG-Object
	/**
	 * Constructs this external parsers object
	 */
	public function __construct() {
		// Set the language object to be used accordant to current TYPO3_MODE:
		$this->langObject = TYPO3_MODE == 'FE' ? $GLOBALS['TSFE'] : $GLOBALS['LANG'];
	}

	/**
	 * Initialize external parser for parsing content.
	 *
	 * @param 	string		File extension
	 * @return 	boolean		Returns TRUE if extension is supported/enabled, otherwise FALSE.
	 * @todo Define visibility
	 */
	public function initParser($extension) {
		// Then read indexer-config and set if appropriate:
		$indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
		// If windows, apply extension to tool name:
		$exe = TYPO3_OS == 'WIN' ? '.exe' : '';
		// lg
		$extOK = FALSE;
		$mainExtension = '';
		// Ignore extensions
		$ignoreExtensions = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($indexerConfig['ignoreExtensions']), 1);
		if (in_array($extension, $ignoreExtensions)) {
			$this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:ignoreExtensions'), $extension), 1);
			return FALSE;
		}
		// Switch on file extension:
		switch ($extension) {
		case 'pdf':
			// PDF
			if ($indexerConfig['pdftools']) {
				$pdfPath = rtrim($indexerConfig['pdftools'], '/') . '/';
				if (@is_file(($pdfPath . 'pdftotext' . $exe)) && @is_file(($pdfPath . 'pdfinfo' . $exe))) {
					$this->app['pdfinfo'] = $pdfPath . 'pdfinfo' . $exe;
					$this->app['pdftotext'] = $pdfPath . 'pdftotext' . $exe;
					// PDF mode:
					$this->pdf_mode = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($indexerConfig['pdf_mode'], -100, 100);
					$extOK = TRUE;
				} else {
					$this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:pdfToolsNotFound'), $pdfPath), 3);
				}
			} else {
				$this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/locallang.xml:pdfToolsDisabled'), 1);
			}
			break;
		case 'doc':
			// Catdoc
			if ($indexerConfig['catdoc']) {
				$catdocPath = rtrim($indexerConfig['catdoc'], '/') . '/';
				if (@is_file(($catdocPath . 'catdoc' . $exe))) {
					$this->app['catdoc'] = $catdocPath . 'catdoc' . $exe;
					$extOK = TRUE;
				} else {
					$this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:catdocNotFound'), $catdocPath), 3);
				}
			} else {
				$this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/locallang.xml:catdocDisabled'), 1);
			}
			break;
		case 'pps':

		case 'ppt':
			// MS PowerPoint
			// ppthtml
			if ($indexerConfig['ppthtml']) {
				$ppthtmlPath = rtrim($indexerConfig['ppthtml'], '/') . '/';
				if (@is_file(($ppthtmlPath . 'ppthtml' . $exe))) {
					$this->app['ppthtml'] = $ppthtmlPath . 'ppthtml' . $exe;
					$extOK = TRUE;
				} else {
					$this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:ppthtmlNotFound'), $ppthtmlPath), 3);
				}
			} else {
				$this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/locallang.xml:ppthtmlDisabled'), 1);
			}
			break;
		case 'xls':
			// MS Excel
			// Xlhtml
			if ($indexerConfig['xlhtml']) {
				$xlhtmlPath = rtrim($indexerConfig['xlhtml'], '/') . '/';
				if (@is_file(($xlhtmlPath . 'xlhtml' . $exe))) {
					$this->app['xlhtml'] = $xlhtmlPath . 'xlhtml' . $exe;
					$extOK = TRUE;
				} else {
					$this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:xlhtmlNotFound'), $xlhtmlPath), 3);
				}
			} else {
				$this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/locallang.xml:xlhtmlDisabled'), 1);
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
				if (@is_file(($unzipPath . 'unzip' . $exe))) {
					$this->app['unzip'] = $unzipPath . 'unzip' . $exe;
					$extOK = TRUE;
				} else {
					$this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:unzipNotFound'), $unzipPath), 3);
				}
			} else {
				$this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/locallang.xml:unzipDisabled'), 1);
			}
			break;
		case 'rtf':
			// Catdoc
			if ($indexerConfig['unrtf']) {
				$unrtfPath = rtrim($indexerConfig['unrtf'], '/') . '/';
				if (@is_file(($unrtfPath . 'unrtf' . $exe))) {
					$this->app['unrtf'] = $unrtfPath . 'unrtf' . $exe;
					$extOK = TRUE;
				} else {
					$this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:unrtfNotFound'), $unrtfPath), 3);
				}
			} else {
				$this->pObj->log_setTSlogMessage($this->sL('LLL:EXT:indexed_search/locallang.xml:unrtfDisabled'), 1);
			}
			break;
		case 'txt':

		case 'csv':

		case 'xml':

		case 'tif':
			// PHP EXIF
			$extOK = TRUE;
			break;
		case 'html':

		case 'htm':
			// PHP strip-tags()
			$extOK = TRUE;
			$mainExtension = 'html';
			// making "html" the common "item_type"
			break;
		case 'jpg':

		case 'jpeg':
			// PHP EXIF
			$extOK = TRUE;
			$mainExtension = 'jpeg';
			// making "jpeg" the common item_type
			break;
		}
		// If extension was OK:
		if ($extOK) {
			$this->supportedExtensions[$extension] = TRUE;
			$this->ext2itemtype_map[$extension] = $mainExtension ? $mainExtension : $extension;
			return TRUE;
		}
	}

	/**
	 * Initialize external parser for backend modules
	 * Doesn't evaluate if parser is configured right - more like returning POSSIBLE supported extensions (for showing icons etc) in backend and frontend plugin
	 *
	 * @param 	string		File extension to initialize for.
	 * @return 	boolean		Returns TRUE if the extension is supported and enabled, otherwise FALSE.
	 * @todo Define visibility
	 */
	public function softInit($extension) {
		switch ($extension) {
		case 'pdf':

		case 'doc':

		case 'pps':

		case 'ppt':

		case 'xls':

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
			return TRUE;
			break;
		}
	}

	/**
	 * Return title of entry in media type selector box.
	 *
	 * @param 	string		File extension
	 * @return 	string		String with label value of entry in media type search selector box (frontend plugin).
	 * @todo Define visibility
	 */
	public function searchTypeMediaTitle($extension) {
		// Read indexer-config
		$indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);
		// Ignore extensions
		$ignoreExtensions = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($indexerConfig['ignoreExtensions']), 1);
		if (in_array($extension, $ignoreExtensions)) {
			return FALSE;
		}
		// Switch on file extension:
		switch ($extension) {
		case 'pdf':
			// PDF
			if ($indexerConfig['pdftools']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.PDF'), $extension);
			}
			break;
		case 'doc':
			// Catdoc
			if ($indexerConfig['catdoc']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.DOC'), $extension);
			}
			break;
		case 'pps':

		case 'ppt':
			// MS PowerPoint
			// ppthtml
			if ($indexerConfig['ppthtml']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.PP'), $extension);
			}
			break;
		case 'xls':
			// MS Excel
			// Xlhtml
			if ($indexerConfig['xlhtml']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.XLS'), $extension);
			}
			break;
		case 'sxc':
			// Open Office Calc.
			if ($indexerConfig['unzip']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.SXC'), $extension);
			}
			break;
		case 'sxi':
			// Open Office Impress
			if ($indexerConfig['unzip']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.SXI'), $extension);
			}
			break;
		case 'sxw':
			// Open Office Writer
			if ($indexerConfig['unzip']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.SXW'), $extension);
			}
			break;
		case 'ods':
			// Oasis OpenDocument Spreadsheet
			if ($indexerConfig['unzip']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.ODS'), $extension);
			}
			break;
		case 'odp':
			// Oasis OpenDocument Presentation
			if ($indexerConfig['unzip']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.ODP'), $extension);
			}
			break;
		case 'odt':
			// Oasis OpenDocument Text
			if ($indexerConfig['unzip']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.ODT'), $extension);
			}
			break;
		case 'rtf':
			// Catdoc
			if ($indexerConfig['unrtf']) {
				return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.RTF'), $extension);
			}
			break;
		case 'jpeg':

		case 'tif':
			// PHP EXIF
			return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.Images'), $extension);
			break;
		case 'html':
			// PHP strip-tags()
			return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.HTML'), $extension);
			break;
		case 'txt':
			// Raw text
			return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.TXT'), $extension);
			break;
		case 'csv':
			// Raw text
			return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.CSV'), $extension);
			break;
		case 'xml':
			// PHP strip-tags()
			return sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:extension.XML'), $extension);
			break;
		case 'htm':

		case 'jpg':

		default:
			break;
		}
	}

	/**
	 * Returns TRUE if the input extension (item_type) is a potentially a multi-page extension
	 *
	 * @param 	string		Extension / item_type string
	 * @return 	boolean		Return TRUE if multi-page
	 * @todo Define visibility
	 */
	public function isMultiplePageExtension($extension) {
		// Switch on file extension:
		switch ((string) $extension) {
		case 'pdf':
			return TRUE;
			break;
		}
	}

	/**
	 * Wraps the "splitLabel function" of the language object.
	 *
	 * @param 	string		$reference: Reference/key of the label
	 * @param 	boolean		$useHtmlSpecialChar: Convert special chars to HTML entities (default: FALSE)
	 * @return 	string		The label of the reference/key to be fetched
	 */
	protected function sL($reference, $useHtmlSpecialChar = FALSE) {
		return $this->langObject->sL($reference, $useHtmlSpecialChar);
	}

	/************************
	 *
	 * Reading documents (for parsing)
	 *
	 ************************/
	/**
	 * Reads the content of an external file being indexed.
	 *
	 * @param 	string		File extension, eg. "pdf", "doc" etc.
	 * @param 	string		Absolute filename of file (must exist and be validated OK before calling function)
	 * @param 	string		Pointer to section (zero for all other than PDF which will have an indication of pages into which the document should be splitted.)
	 * @return 	array		Standard content array (title, description, keywords, body keys)
	 * @todo Define visibility
	 */
	public function readFileContent($ext, $absFile, $cPKey) {
		unset($contentArr);
		// Return immediately if initialization didn't set support up:
		if (!$this->supportedExtensions[$ext]) {
			return FALSE;
		}
		// Switch by file extension
		switch ($ext) {
		case 'pdf':
			if ($this->app['pdfinfo']) {
				// Getting pdf-info:
				$cmd = $this->app['pdfinfo'] . ' ' . escapeshellarg($absFile);
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $res);
				$pdfInfo = $this->splitPdfInfo($res);
				unset($res);
				if (intval($pdfInfo['pages'])) {
					list($low, $high) = explode('-', $cPKey);
					// Get pdf content:
					$tempFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('Typo3_indexer');
					// Create temporary name
					@unlink($tempFileName);
					// Delete if exists, just to be safe.
					$cmd = $this->app['pdftotext'] . ' -f ' . $low . ' -l ' . $high . ' -enc UTF-8 -q ' . escapeshellarg($absFile) . ' ' . $tempFileName;
					\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
					if (@is_file($tempFileName)) {
						$content = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($tempFileName);
						unlink($tempFileName);
					} else {
						$this->pObj->log_setTSlogMessage(sprintf($this->sL('LLL:EXT:indexed_search/locallang.xml:pdfToolsFailed'), $absFile), 2);
					}
					if (strlen($content)) {
						$contentArr = $this->pObj->splitRegularContent($this->removeEndJunk($content));
					}
				}
			}
			break;
		case 'doc':
			if ($this->app['catdoc']) {
				$cmd = $this->app['catdoc'] . ' -d utf-8 ' . escapeshellarg($absFile);
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $res);
				$content = implode(LF, $res);
				unset($res);
				$contentArr = $this->pObj->splitRegularContent($this->removeEndJunk($content));
			}
			break;
		case 'pps':

		case 'ppt':
			if ($this->app['ppthtml']) {
				$cmd = $this->app['ppthtml'] . ' ' . escapeshellarg($absFile);
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $res);
				$content = implode(LF, $res);
				unset($res);
				$content = $this->pObj->convertHTMLToUtf8($content);
				$contentArr = $this->pObj->splitHTMLContent($this->removeEndJunk($content));
				$contentArr['title'] = basename($absFile);
			}
			break;
		case 'xls':
			if ($this->app['xlhtml']) {
				$cmd = $this->app['xlhtml'] . ' -nc -te ' . escapeshellarg($absFile);
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $res);
				$content = implode(LF, $res);
				unset($res);
				$content = $this->pObj->convertHTMLToUtf8($content);
				$contentArr = $this->pObj->splitHTMLContent($this->removeEndJunk($content));
				$contentArr['title'] = basename($absFile);
			}
			break;
		case 'sxi':

		case 'sxc':

		case 'sxw':

		case 'ods':

		case 'odp':

		case 'odt':
			if ($this->app['unzip']) {
				// Read content.xml:
				$cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' content.xml';
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $res);
				$content_xml = implode(LF, $res);
				unset($res);
				// Read meta.xml:
				$cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' meta.xml';
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $res);
				$meta_xml = implode(LF, $res);
				unset($res);
				$utf8_content = trim(strip_tags(str_replace('<', ' <', $content_xml)));
				$contentArr = $this->pObj->splitRegularContent($utf8_content);
				$contentArr['title'] = basename($absFile);
				// Make sure the title doesn't expose the absolute path!
				// Meta information
				$metaContent = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2tree($meta_xml);
				$metaContent = $metaContent['office:document-meta'][0]['ch']['office:meta'][0]['ch'];
				if (is_array($metaContent)) {
					$contentArr['title'] = $metaContent['dc:title'][0]['values'][0] ? $metaContent['dc:title'][0]['values'][0] : $contentArr['title'];
					$contentArr['description'] = $metaContent['dc:subject'][0]['values'][0] . ' ' . $metaContent['dc:description'][0]['values'][0];
					// Keywords collected:
					if (is_array($metaContent['meta:keywords'][0]['ch']['meta:keyword'])) {
						foreach ($metaContent['meta:keywords'][0]['ch']['meta:keyword'] as $kwDat) {
							$contentArr['keywords'] .= $kwDat['values'][0] . ' ';
						}
					}
				}
			}
			break;
		case 'rtf':
			if ($this->app['unrtf']) {
				$cmd = $this->app['unrtf'] . ' ' . escapeshellarg($absFile);
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $res);
				$fileContent = implode(LF, $res);
				unset($res);
				$fileContent = $this->pObj->convertHTMLToUtf8($fileContent);
				$contentArr = $this->pObj->splitHTMLContent($fileContent);
			}
			break;
		case 'txt':

		case 'csv':
			// Raw text
			$content = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($absFile);
			// TODO: Implement auto detection of charset (currently assuming utf-8)
			$contentCharset = 'utf-8';
			$content = $this->pObj->convertHTMLToUtf8($content, $contentCharset);
			$contentArr = $this->pObj->splitRegularContent($content);
			$contentArr['title'] = basename($absFile);
			// Make sure the title doesn't expose the absolute path!
			break;
		case 'html':

		case 'htm':
			$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($absFile);
			$fileContent = $this->pObj->convertHTMLToUtf8($fileContent);
			$contentArr = $this->pObj->splitHTMLContent($fileContent);
			break;
		case 'xml':
			// PHP strip-tags()
			$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($absFile);
			// Finding charset:
			preg_match('/^[[:space:]]*<\\?xml[^>]+encoding[[:space:]]*=[[:space:]]*["\'][[:space:]]*([[:alnum:]_-]+)[[:space:]]*["\']/i', substr($fileContent, 0, 200), $reg);
			$charset = $reg[1] ? $this->pObj->csObj->parse_charset($reg[1]) : 'utf-8';
			// Converting content:
			$fileContent = $this->pObj->convertHTMLToUtf8(strip_tags(str_replace('<', ' <', $fileContent)), $charset);
			$contentArr = $this->pObj->splitRegularContent($fileContent);
			$contentArr['title'] = basename($absFile);
			// Make sure the title doesn't expose the absolute path!
			break;
		case 'jpg':

		case 'jpeg':

		case 'tif':
			// PHP EXIF
			if (function_exists('exif_read_data')) {
				$exif = exif_read_data($absFile, 'IFD0');
			} else {
				$exif = FALSE;
			}
			if ($exif) {
				$comment = trim($exif['COMMENT'][0] . ' ' . $exif['ImageDescription']);
			} else {
				$comment = '';
			}
			$contentArr = $this->pObj->splitRegularContent($comment);
			$contentArr['title'] = basename($absFile);
			// Make sure the title doesn't expose the absolute path!
			break;
		default:
			return FALSE;
			break;
		}
		// If no title (and why should there be...) then the file-name is set as title. This will raise the hits considerably if the search matches the document name.
		if (is_array($contentArr) && !$contentArr['title']) {
			// Substituting "_" for " " because many filenames may have this instead of a space char.
			$contentArr['title'] = str_replace('_', ' ', basename($absFile));
		}
		return $contentArr;
	}

	/**
	 * Creates an array with pointers to divisions of document.
	 * ONLY for PDF files at this point. All other types will have an array with a single element with the value "0" (zero) coming back.
	 *
	 * @param 	string		File extension
	 * @param 	string		Absolute filename (must exist and be validated OK before calling function)
	 * @return 	array		Array of pointers to sections that the document should be divided into
	 * @todo Define visibility
	 */
	public function fileContentParts($ext, $absFile) {
		$cParts = array(0);
		switch ($ext) {
		case 'pdf':
			// Getting pdf-info:
			$cmd = $this->app['pdfinfo'] . ' ' . escapeshellarg($absFile);
			\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $res);
			$pdfInfo = $this->splitPdfInfo($res);
			unset($res);
			if (intval($pdfInfo['pages'])) {
				$cParts = array();
				// Calculate mode
				if ($this->pdf_mode > 0) {
					$iter = ceil($pdfInfo['pages'] / $this->pdf_mode);
				} else {
					$iter = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(abs($this->pdf_mode), 1, $pdfInfo['pages']);
				}
				// Traverse and create intervals.
				for ($a = 0; $a < $iter; $a++) {
					$low = floor($a * ($pdfInfo['pages'] / $iter)) + 1;
					$high = floor(($a + 1) * ($pdfInfo['pages'] / $iter));
					$cParts[] = $low . '-' . $high;
				}
			}
			break;
		}
		return $cParts;
	}

	/**
	 * Analysing PDF info into a useable format.
	 *
	 * @param 	array		Array of PDF content, coming from the pdfinfo tool
	 * @return 	array		Result array
	 * @access private
	 * @see fileContentParts()
	 * @todo Define visibility
	 */
	public function splitPdfInfo($pdfInfoArray) {
		$res = array();
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
	 * @param 	string		String to clean up
	 * @return 	string		String
	 * @todo Define visibility
	 */
	public function removeEndJunk($string) {
		return trim(preg_replace('/[' . LF . chr(12) . ']*$/', '', $string));
	}

	/************************
	 *
	 * Backend analyzer
	 *
	 ************************/
	/**
	 * Return icon for file extension
	 *
	 * @param 	string		File extension, lowercase.
	 * @return 	string		Relative file reference, resolvable by \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName()
	 * @todo Define visibility
	 */
	public function getIcon($extension) {
		if ($extension == 'htm') {
			$extension = 'html';
		}
		if ($extension == 'jpeg') {
			$extension = 'jpg';
		}
		return 'EXT:indexed_search/pi/res/' . $extension . '.gif';
	}

}


?>