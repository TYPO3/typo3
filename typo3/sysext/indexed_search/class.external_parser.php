<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	Olivier Simah <noname_paris@yahoo.fr>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   75: class tx_indexed_search_extparse
 *   94:     function initParser($extension)
 *  214:     function softInit($extension)
 *  247:     function searchTypeMediaTitle($extension)
 *  323:     function isMultiplePageExtension($extension)
 *
 *              SECTION: Reading documents (for parsing)
 *  354:     function readFileContent($ext,$absFile,$cPKey)
 *  521:     function fileContentParts($ext,$absFile)
 *  560:     function splitPdfInfo($pdfInfoArray)
 *  579:     function removeEndJunk($string)
 *
 *              SECTION: Backend analyzer
 *  606:     function getIcon($extension)
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */










/**
 * External standard parsers for indexed_search
 * MUST RETURN utf-8 content!
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_indexedsearch
 */
class tx_indexed_search_extparse {

		// This value is also overridden from config.
	var $pdf_mode = -20;	// zero: whole PDF file is indexed in one. positive value: Indicates number of pages at a time, eg. "5" would means 1-5,6-10,.... Negative integer would indicate (abs value) number of groups. Eg "3" groups of 10 pages would be 1-4,5-8,9-10

		// This array is configured in initialization:
	var $app = array();
	var $ext2itemtype_map = array();
	var $supportedExtensions = array();

	var $pObj;		// Reference to parent object (indexer class)


	/**
	 * Initialize external parser for parsing content.
	 *
	 * @param	string		File extension
	 * @return	boolean		Returns true if extension is supported/enabled, otherwise false.
	 */
	function initParser($extension)	{

			// Then read indexer-config and set if appropriate:
		$indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);

			// If windows, apply extension to tool name:
		$exe = (TYPO3_OS == 'WIN') ? '.exe' : ''; // lg
		$extOK = FALSE;
		$mainExtension = '';

			// Ignore extensions
		$ignoreExtensions = t3lib_div::trimExplode(',', strtolower($indexerConfig['ignoreExtensions']),1);
		if (in_array($extension, $ignoreExtensions))	{
			$this->pObj->log_setTSlogMessage('Extension "'.$extension.'" was set to be ignored.',1);
			return FALSE;
		}

			// Switch on file extension:
		switch($extension)	{
			case 'pdf':
					// PDF
				if ($indexerConfig['pdftools'])	{
					$pdfPath = ereg_replace("\/$",'',$indexerConfig['pdftools']).'/';
					if (ini_get('safe_mode') || (@is_file($pdfPath.'pdftotext'.$exe) && @is_file($pdfPath.'pdfinfo'.$exe)))	{
						$this->app['pdfinfo'] = $pdfPath.'pdfinfo'.$exe;
						$this->app['pdftotext'] = $pdfPath.'pdftotext'.$exe;
							// PDF mode:
						$this->pdf_mode = t3lib_div::intInRange($indexerConfig['pdf_mode'],-100,100);
						$extOK = TRUE;
					} else $this->pObj->log_setTSlogMessage("PDF tools was not found in paths '".$pdfPath."pdftotext' and/or '".$pdfPath."pdfinfo'",3);
				} else $this->pObj->log_setTSlogMessage('PDF tools disabled',1);
			break;
			case 'doc':
					// Catdoc
				if ($indexerConfig['catdoc'])	{
					$catdocPath = ereg_replace("\/$",'',$indexerConfig['catdoc']).'/';
					if (ini_get('safe_mode') || @is_file($catdocPath.'catdoc'.$exe))	{
						$this->app['catdoc'] = $catdocPath.'catdoc'.$exe;
						$extOK = TRUE;
					} else $this->pObj->log_setTSlogMessage("'catdoc' tool for reading Word-files was not found in path '".$catdocPath."catdoc'",3);
				} else $this->pObj->log_setTSlogMessage('catdoc tools (Word-files) disabled',1);
			break;
			case 'pps':		// MS PowerPoint(?)
			case 'ppt':		// MS PowerPoint
					// ppthtml
				if ($indexerConfig['ppthtml'])	{
					$ppthtmlPath = ereg_replace('\/$','',$indexerConfig['ppthtml']).'/';
					if (ini_get('safe_mode') || @is_file($ppthtmlPath.'ppthtml'.$exe)){
						$this->app['ppthtml'] = $ppthtmlPath.'ppthtml'.$exe;
						$extOK = TRUE;
					} else $this->pObj->log_setTSlogMessage("'ppthtml' tool for reading Powerpoint-files was not found in path '".$ppthtmlPath."ppthtml'",3);
				} else $this->pObj->log_setTSlogMessage('ppthtml tools (Powerpoint-files) disabled',1);
			break;
			case 'xls':		// MS Excel
					// Xlhtml
				if ($indexerConfig['xlhtml'])	{
					$xlhtmlPath = ereg_replace('\/$','',$indexerConfig['xlhtml']).'/';
					if (ini_get('safe_mode') || @is_file($xlhtmlPath.'xlhtml'.$exe)){
						$this->app['xlhtml'] = $xlhtmlPath.'xlhtml'.$exe;
						$extOK = TRUE;
					} else $this->pObj->log_setTSlogMessage("'xlhtml' tool for reading Excel-files was not found in path '".$xlhtmlPath."xlhtml'",3);
				} else $this->pObj->log_setTSlogMessage('xlhtml tools (Excel-files) disabled',1);
			break;
			case 'sxc':		// Open Office Calc.
			case 'sxi':		// Open Office Impress
			case 'sxw':		// Open Office Writer
			case 'ods':		// Oasis OpenDocument Spreadsheet
			case 'odp':		// Oasis OpenDocument Presentation
			case 'odt':		// Oasis OpenDocument Text
				if ($indexerConfig['unzip'])	{
					$unzipPath = preg_replace('/\/$/','',$indexerConfig['unzip']).'/';
					if (ini_get('safe_mode') || @is_file($unzipPath.'unzip'.$exe))	{
						$this->app['unzip'] = $unzipPath.'unzip'.$exe;
						$extOK = TRUE;
					} else $this->pObj->log_setTSlogMessage("'unzip' tool for reading OpenOffice.org-files was not found in path '".$unzipPath."unzip'",3);
				} else $this->pObj->log_setTSlogMessage('unzip tool (OpenOffice.org-files) disabled',1);
			break;
			case 'rtf':
					// Catdoc
				if ($indexerConfig['unrtf'])	{
					$unrtfPath = ereg_replace("\/$",'',$indexerConfig['unrtf']).'/';
					if (ini_get('safe_mode') || @is_file($unrtfPath.'unrtf'.$exe))	{
						$this->app['unrtf'] = $unrtfPath.'unrtf'.$exe;
						$extOK = TRUE;
					} else $this->pObj->log_setTSlogMessage("'unrtf' tool for reading RTF-files was not found in path '".$unrtfPath."unrtf'",3);
				} else $this->pObj->log_setTSlogMessage('unrtf tool (RTF-files) disabled',1);
			break;
			case 'txt':		// Raw text
			case 'csv':		// Raw text
			case 'xml':		// PHP strip-tags()
			case 'tif':		// PHP EXIF
				$extOK = TRUE;
			break;
			case 'html':	// PHP strip-tags()
			case 'htm':		// PHP strip-tags()
				$extOK = TRUE;
				$mainExtension = 'html';	// making "html" the common "item_type"
			break;
			case 'jpg':		// PHP EXIF
			case 'jpeg':	// PHP EXIF
				$extOK = TRUE;
				$mainExtension = 'jpeg';	// making "jpeg" the common item_type
			break;
		}

			// If extension was OK:
		if ($extOK)	{
			$this->supportedExtensions[$extension] = TRUE;
			$this->ext2itemtype_map[$extension] = $mainExtension ? $mainExtension : $extension;
			return TRUE;
		}
	}

	/**
	 * Initialize external parser for backend modules
	 * Doesn't evaluate if parser is configured right - more like returning POSSIBLE supported extensions (for showing icons etc) in backend and frontend plugin
	 *
	 * @param	string		File extension to initialize for.
	 * @return	boolean		Returns true if the extension is supported and enabled, otherwise false.
	 */
	function softInit($extension)	{
		switch($extension)	{
			case 'pdf':		// PDF
			case 'doc':		// MS Word files
			case 'pps':		// MS PowerPoint
			case 'ppt':		// MS PowerPoint
			case 'xls':		// MS Excel
			case 'sxc':		// Open Office Calc.
			case 'sxi':		// Open Office Impress
			case 'sxw':		// Open Office Writer
			case 'ods':		// Oasis OpenDocument Spreadsheet
			case 'odp':		// Oasis OpenDocument Presentation
			case 'odt':		// Oasis OpenDocument Text
			case 'rtf':		// RTF documents
			case 'txt':		// ASCII Text documents
			case 'html':	// HTML
			case 'htm':		// HTML
			case 'csv':		// Comma Separated Values
			case 'xml':		// Generic XML
			case 'jpg':		// Jpeg images (EXIF comment)
			case 'jpeg':	// Jpeg images (EXIF comment)
			case 'tif':		// TIF images (EXIF comment)
				return TRUE;
			break;
		}
	}

	/**
	 * Return title of entry in media type selector box.
	 *
	 * @param	string		File extension
	 * @return	string		String with label value of entry in media type search selector box (frontend plugin).
	 */
	function searchTypeMediaTitle($extension)	{

			// Read indexer-config
		$indexerConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['indexed_search']);

			// Ignore extensions
		$ignoreExtensions = t3lib_div::trimExplode(',', strtolower($indexerConfig['ignoreExtensions']),1);
		if (in_array($extension, $ignoreExtensions))	{
			return FALSE;
		}

			// Switch on file extension:
		switch($extension)	{
			case 'pdf':
					// PDF
				if ($indexerConfig['pdftools'])	{
					return 'PDF';
				}
			break;
			case 'doc':
					// Catdoc
				if ($indexerConfig['catdoc'])	{
					return 'MS Word';
				}
			break;
			case 'pps':		// MS PowerPoint(?)
			case 'ppt':		// MS PowerPoint
					// ppthtml
				if ($indexerConfig['ppthtml'])	{
					return 'MS Powerpoint';
				}
			break;
			case 'xls':		// MS Excel
					// Xlhtml
				if ($indexerConfig['xlhtml'])	{
					return 'MS Excel';
				}
			break;
			case 'sxc':		// Open Office Calc.
			case 'sxi':		// Open Office Impress
			case 'sxw':		// Open Office Writer
			case 'ods':		// Oasis OpenDocument Spreadsheet
			case 'odp':		// Oasis OpenDocument Presentation
			case 'odt':		// Oasis OpenDocument Text
				if ($indexerConfig['unzip'])	{
					return 'Open Office';
				}
			break;
			case 'rtf':
					// Catdoc
				if ($indexerConfig['unrtf'])	{
					return 'RTF';
				}
			break;
			case 'html':	// PHP strip-tags()
			case 'jpeg':	// PHP EXIF
			case 'txt':		// Raw text
			case 'csv':		// Raw text
			case 'xml':		// PHP strip-tags()
			case 'tif':		// PHP EXIF
				return strtoupper($extension);
			break;
				// NO entry (duplicates or blank):
			case 'htm':		// PHP strip-tags()
			case 'jpg':		// PHP EXIF
			default:
			break;
		}
	}

	/**
	 * Returns true if the input extension (item_type) is a potentially a multi-page extension
	 *
	 * @param	string		Extension / item_type string
	 * @return	boolean		Return true if multi-page
	 */
	function isMultiplePageExtension($extension)	{
			// Switch on file extension:
		switch((string)$extension)	{
			case 'pdf':
				return TRUE;
			break;
		}
	}









	/************************
	 *
	 * Reading documents (for parsing)
	 *
	 ************************/

	/**
	 * Reads the content of an external file being indexed.
	 *
	 * @param	string		File extension, eg. "pdf", "doc" etc.
	 * @param	string		Absolute filename of file (must exist and be validated OK before calling function)
	 * @param	string		Pointer to section (zero for all other than PDF which will have an indication of pages into which the document should be splitted.)
	 * @return	array		Standard content array (title, description, keywords, body keys)
	 */
	function readFileContent($ext,$absFile,$cPKey)	{
		unset($contentArr);

			// Return immediately if initialization didn't set support up:
		if (!$this->supportedExtensions[$ext])	return FALSE;

			// Switch by file extension
		switch ($ext)	{
			case 'pdf':
				if ($this->app['pdfinfo'])	{
						// Getting pdf-info:
					$cmd = $this->app['pdfinfo'] . ' ' . escapeshellarg($absFile);
					exec($cmd,$res);
					$pdfInfo = $this->splitPdfInfo($res);
					unset($res);
					if (intval($pdfInfo['pages']))	{
						list($low,$high) = explode('-',$cPKey);

							// Get pdf content:
						$tempFileName = t3lib_div::tempnam('Typo3_indexer');		// Create temporary name
						@unlink ($tempFileName);	// Delete if exists, just to be safe.
						$cmd = $this->app['pdftotext'] . ' -f ' . $low . ' -l ' . $high . ' -enc UTF-8 -q ' . escapeshellarg($absFile) . ' ' . $tempFileName;
						exec($cmd);
						if (@is_file($tempFileName))	{
							$content = t3lib_div::getUrl($tempFileName);
							unlink($tempFileName);
						} else {
							$this->pObj->log_setTSlogMessage('PDFtoText Failed on this document: '.$absFile.". Maybe the PDF file is locked for printing or encrypted.",2);
						}
						if (strlen($content))	{
							$contentArr = $this->pObj->splitRegularContent($this->removeEndJunk($content));
						}
					}
				}
			break;
			case 'doc':
				if ($this->app['catdoc'])	{
					$cmd = $this->app['catdoc'] . ' -d utf-8 ' . escapeshellarg($absFile);
					exec($cmd,$res);
					$content = implode(chr(10),$res);
					unset($res);
					$contentArr = $this->pObj->splitRegularContent($this->removeEndJunk($content));
				}
			break;
			case 'pps':
			case 'ppt':
				if ($this->app['ppthtml'])	{
					$cmd = $this->app['ppthtml'] . ' ' . escapeshellarg($absFile);
					exec($cmd,$res);
					$content = implode(chr(10),$res);
					unset($res);
					$content = $this->pObj->convertHTMLToUtf8($content);
					$contentArr = $this->pObj->splitHTMLContent($this->removeEndJunk($content));
					$contentArr['title'] = basename($absFile);	// Make sure the title doesn't expose the absolute path!
				}
			break;
			case 'xls':
				if ($this->app['xlhtml'])	{
					$cmd = $this->app['xlhtml'] . ' -nc -te ' . escapeshellarg($absFile);
					exec($cmd,$res);
					$content = implode(chr(10),$res);
					unset($res);
					$content = $this->pObj->convertHTMLToUtf8($content);
					$contentArr = $this->pObj->splitHTMLContent($this->removeEndJunk($content));
					$contentArr['title'] = basename($absFile);	// Make sure the title doesn't expose the absolute path!
				}
			break;
			case 'sxi':
			case 'sxc':
			case 'sxw':
			case 'ods':
			case 'odp':
			case 'odt':
				if ($this->app['unzip'])	{
						// Read content.xml:
					$cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' content.xml';
					exec($cmd,$res);
					$content_xml = implode(chr(10),$res);
					unset($res);

						// Read meta.xml:
					$cmd = $this->app['unzip'] . ' -p ' . escapeshellarg($absFile) . ' meta.xml';
					exec($cmd, $res);
					$meta_xml = implode(chr(10),$res);
					unset($res);

					$utf8_content = trim(strip_tags(str_replace('<',' <',$content_xml)));
					$contentArr = $this->pObj->splitRegularContent($utf8_content);
					$contentArr['title'] = basename($absFile);	// Make sure the title doesn't expose the absolute path!

						// Meta information
					$metaContent = t3lib_div::xml2tree($meta_xml);
					$metaContent = $metaContent['office:document-meta'][0]['ch']['office:meta'][0]['ch'];
					if (is_array($metaContent))	{
						$contentArr['title'] = $metaContent['dc:title'][0]['values'][0] ? $metaContent['dc:title'][0]['values'][0] : $contentArr['title'];
						$contentArr['description'] = $metaContent['dc:subject'][0]['values'][0].' '.$metaContent['dc:description'][0]['values'][0];

							// Keywords collected:
						if (is_array($metaContent['meta:keywords'][0]['ch']['meta:keyword']))	{
							foreach ($metaContent['meta:keywords'][0]['ch']['meta:keyword'] as $kwDat)	{
								$contentArr['keywords'].= $kwDat['values'][0].' ';
							}
						}
					}
				}
			break;
			case 'rtf':
				if ($this->app['unrtf'])	{
					$cmd = $this->app['unrtf'] . ' ' . escapeshellarg($absFile);
					exec($cmd,$res);
					$fileContent = implode(chr(10),$res);
					unset($res);
					$fileContent = $this->pObj->convertHTMLToUtf8($fileContent);
					$contentArr = $this->pObj->splitHTMLContent($fileContent);
				}
			break;
			case 'txt':
			case 'csv':		// Raw text
				$content = t3lib_div::getUrl($absFile);
					// TODO: Auto-registration of charset???? -> utf-8 (Current assuming western europe...)
				$content = $this->pObj->convertHTMLToUtf8($content, 'iso-8859-1');
				$contentArr = $this->pObj->splitRegularContent($content);
				$contentArr['title'] = basename($absFile);	// Make sure the title doesn't expose the absolute path!
			break;
			case 'html':
			case 'htm':
				$fileContent = t3lib_div::getUrl($absFile);
				$fileContent = $this->pObj->convertHTMLToUtf8($fileContent);
				$contentArr = $this->pObj->splitHTMLContent($fileContent);
			break;
			case 'xml':		// PHP strip-tags()
				$fileContent = t3lib_div::getUrl($absFile);

					// Finding charset:
				eregi('^[[:space:]]*<\?xml[^>]+encoding[[:space:]]*=[[:space:]]*["\'][[:space:]]*([[:alnum:]_-]+)[[:space:]]*["\']',substr($fileContent,0,200),$reg);
				$charset = $reg[1] ? $this->pObj->csObj->parse_charset($reg[1]) : 'utf-8';

					// Converting content:
				$fileContent = $this->pObj->convertHTMLToUtf8(strip_tags(str_replace('<',' <',$fileContent)), $charset);
				$contentArr = $this->pObj->splitRegularContent($fileContent);
				$contentArr['title'] = basename($absFile);	// Make sure the title doesn't expose the absolute path!
			break;
			case 'jpg':		// PHP EXIF
			case 'jpeg':	// PHP EXIF
			case 'tif':		// PHP EXIF
				if (function_exists('exif_read_data'))	{
					$exif = exif_read_data($absFile, 'IFD0');
				} else {
					$exif = FALSE;
				}

				if ($exif)	{
					$comment = trim($exif['COMMENT'][0].' '.$exif['ImageDescription']);	// The comments in JPEG files are utf-8, while in Tif files they are 7-bit ascii.
				} else {
					$comment = '';
				}
				$contentArr = $this->pObj->splitRegularContent($comment);
				$contentArr['title'] = basename($absFile);	// Make sure the title doesn't expose the absolute path!
			break;
			default:
				return false;
			break;
		}
			// If no title (and why should there be...) then the file-name is set as title. This will raise the hits considerably if the search matches the document name.
		if (is_array($contentArr) && !$contentArr['title'])	{
			$contentArr['title'] = str_replace('_',' ',basename($absFile));	// Substituting "_" for " " because many filenames may have this instead of a space char.
		}

		return $contentArr;
	}

	/**
	 * Creates an array with pointers to divisions of document.
	 * ONLY for PDF files at this point. All other types will have an array with a single element with the value "0" (zero) coming back.
	 *
	 * @param	string		File extension
	 * @param	string		Absolute filename (must exist and be validated OK before calling function)
	 * @return	array		Array of pointers to sections that the document should be divided into
	 */
	function fileContentParts($ext,$absFile)	{
		$cParts = array(0);
		switch ($ext)	{
			case 'pdf':
					// Getting pdf-info:
				$cmd = $this->app['pdfinfo'] . ' ' . escapeshellarg($absFile);
				exec($cmd,$res);
				$pdfInfo = $this->splitPdfInfo($res);
				unset($res);

				if (intval($pdfInfo['pages']))	{
					$cParts = array();

						// Calculate mode
					if ($this->pdf_mode>0)	{
						$iter = ceil($pdfInfo['pages']/$this->pdf_mode);
					} else {
						$iter = t3lib_div::intInRange(abs($this->pdf_mode),1,$pdfInfo['pages']);
					}

						// Traverse and create intervals.
					for ($a=0;$a<$iter;$a++)	{
						$low = floor($a*($pdfInfo['pages']/$iter))+1;
						$high = floor(($a+1)*($pdfInfo['pages']/$iter));
						$cParts[] = $low.'-'.$high;
					}
				}
			break;
		}
		return $cParts;
	}

	/**
	 * Analysing PDF info into a useable format.
	 *
	 * @param	array		Array of PDF content, coming from the pdfinfo tool
	 * @return	array		Result array
	 * @access private
	 * @see fileContentParts()
	 */
	function splitPdfInfo($pdfInfoArray)	{
		$res = array();
		if (is_array($pdfInfoArray))	{
			foreach($pdfInfoArray as $line)	{
				$parts = explode(':',$line,2);
				if (count($parts)>1 && trim($parts[0]))	{
					$res[strtolower(trim($parts[0]))] = trim($parts[1]);
				}
			}
		}
		return $res;
	}

	/**
	 * Removes some strange char(12) characters and line breaks that then to occur in the end of the string from external files.
	 *
	 * @param	string		String to clean up
	 * @return	string		String
	 */
	function removeEndJunk($string)	{
		return trim(ereg_replace('['.chr(10).chr(12).']*$','',$string));
	}












	/************************
	 *
	 * Backend analyzer
	 *
	 ************************/

	/**
	 * Return icon for file extension
	 *
	 * @param	string		File extension, lowercase.
	 * @return	string		Relative file reference, resolvable by t3lib_div::getFileAbsFileName()
	 */
	function getIcon($extension)	{
		if ($extension=='htm')	$extension = 'html';
		if ($extension=='jpeg')	$extension = 'jpg';
		return 'EXT:indexed_search/pi/res/'.$extension.'.gif';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.external_parser.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.external_parser.php']);
}
?>