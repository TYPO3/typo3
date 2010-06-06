<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Gebert (steffen@steffen-gebert.de)
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
 * Compressor
 * This merges and compresses CSS and JavaScript files of the TYPO3 Backend.
 *
 * @author	Steffen Gebert <steffen@steffen-gebert.de>
 * @package TYPO3
 * @subpackage t3lib
 * $Id$
 */
class t3lib_Compressor {

	protected $targetDirectory = 'typo3temp/compressor/';

		// gzipped versions are only created if $TYPO3_CONF_VARS[TYPO3_MODE]['compressionLevel'] is set
	protected $createGzipped = FALSE;
		// default compression level is -1
	protected $gzipCompressionLevel = -1;

	/**
	 * Constructor
	 */
	public function __construct() {

			// we check for existance of our targetDirectory
		if (!is_dir(PATH_site . $this->targetDirectory)) {
			t3lib_div::mkdir(PATH_site . $this->targetDirectory);
		}

			// decide whether we should create gzipped versions or not
		$compressionLevel = $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['compressionLevel'];
			// we need zlib for gzencode()
		if (extension_loaded('zlib') && $compressionLevel) {
			$this->createGzipped = TRUE;
				// $compressionLevel can also be TRUE
			if (t3lib_div::testInt($compressionLevel)) {
				$this->gzipCompressionLevel = $compressionLevel;
			}
		}
	}

	/**
	 * Concatenates the cssFiles
	 *
	 * Options:
	 *   baseDirectories		If set, only include files below one of the base directories
	 *
	 * @param	array	$cssFiles		CSS files to process
	 * @param	array	$options		Additional options
	 * @return	array	CSS files
	 */
	public function concatenateCssFiles(array $cssFiles, $options = array()) {

		$filesToInclude = array();
		foreach ($cssFiles as $filename => $fileOptions) {
				// we remove BACK_PATH from $filename, so make it relative to TYPO3_mainDir
			$filenameFromMainDir = substr($filename, strlen($GLOBALS['BACK_PATH']));
				// if $options['baseDirectories'] set, we only include files below these directories
			if ((!isset($options['baseDirectories'])
				|| $this->checkBaseDirectory($filenameFromMainDir, array_merge($options['baseDirectories'], array($this->targetDirectory))))
				&& ($fileOptions['media'] === 'all')
			) {

				$filesToInclude[] = $filenameFromMainDir;
					// remove the file from the incoming file array
				unset($cssFiles[$filename]);
			}
		}

		if (count($filesToInclude)) {
			$targetFile = $this->createMergedCssFile($filesToInclude);
			$concatenatedOptions = array(
					'rel'		=> 'stylesheet',
					'media'		=> 'all',
					'compress'	=> TRUE,
			);
			$targetFileRelative = $GLOBALS['BACK_PATH'] . '../' . $targetFile;
				// place the merged stylesheet on top of the stylesheets
			$cssFiles = array_merge(array($targetFileRelative => $concatenatedOptions), $cssFiles);
		}
		return $cssFiles;
	}

	/**
	 * Creates a merged CSS file
	 *
	 * @param	array	$filesToInclude		Files which should be merged, paths relative to TYPO3_mainDir
	 * @return	mixed	Filename of the merged file
	 */
	protected function createMergedCssFile(array $filesToInclude) {
			// we add up the filenames, filemtimes and filsizes to later build a checksum over
			// it and include it in the temporary file name
		$unique = '';

		foreach ($filesToInclude as $filename) {
			$filepath = t3lib_div::resolveBackPath(PATH_typo3 . $filename);
			$unique .= $filename . filemtime($filepath) . filesize($filepath);
		}
		$targetFile = $this->targetDirectory . 'merged-'. md5($unique) . '.css';

			// if the file doesn't already exist, we create it
		if (!file_exists(PATH_site . $targetFile)) {
			$concatenated = '';
				// concatenate all the files together
			foreach ($filesToInclude as $filename) {
				$contents = t3lib_div::getUrl(t3lib_div::resolveBackPath(PATH_typo3 . $filename));
					// only fix paths if files aren't already in typo3temp (already processed)
				if (!t3lib_div::isFirstPartOfStr($filename, $this->targetDirectory)) {
					$concatenated .= $this->cssFixRelativeUrlPaths($contents, dirname($filename) . '/');
				} else {
					$concatenated .= $contents;
				}
			}
			t3lib_div::writeFile(PATH_site . $targetFile, $concatenated);
		}
		return $targetFile;
	}

	/**
	 * Compress multiple css files
	 *
	 * @param array $cssFiles	The files to compress (array key = filename), relative to requested page
	 * @return array 			The CSS files after compression (array key = new filename), relative to requested page
	 */
	public function compressCssFiles(array $cssFiles) {
		$filesAfterCompression = array();
		foreach ($cssFiles as $filename => $fileOptions) {
				// we remove BACK_PATH from $filename, so make it relative to TYPO3_mainDir
			$filenameFromMainDir = substr($filename, strlen($GLOBALS['BACK_PATH']));
				// if compression is enabled
			if ($fileOptions['compress']) {
				$filesAfterCompression[$this->compressCssFile($filename)] = $fileOptions;
			} else {
				$filesAfterCompression[$filename] = $fileOptions;
			}
		}
		return $filesAfterCompression;
	}

	/**
	 * Compresses a CSS file
	 *
	 * Options:
	 *   baseDirectories		If set, only include files below one of the base directories
	 *
	 * removes comments and whitespaces
	 * Adopted from http://drupal.org/files/issues/minify_css.php__1.txt
	 *
	 * @param	string	$filename		Source filename, relative to requested page
	 * @return	string		Compressed filename, relative to requested page
	 */
	public function compressCssFile($filename) {
			// generate the unique name of the file
		$filenameAbsolute = t3lib_div::resolveBackPath(PATH_typo3 . substr($filename, strlen($GLOBALS['BACK_PATH'])));
		$unique = $filenameAbsolute . filemtime($filenameAbsolute) . filesize($filenameAbsolute);

		$pathinfo = pathinfo($filename);
		$targetFile = $this->targetDirectory . $pathinfo['filename'] . '-' . md5($unique) . '.css';
			// only create it, if it doesn't exist, yet
		if (!file_exists(PATH_site . $targetFile) || ($this->createGzipped && !file_exists(PATH_site . $targetFile . '.gzip'))) {
			$contents = t3lib_div::getUrl($filenameAbsolute);
				// Perform some safe CSS optimizations.
			$contents = str_replace("\r", '', $contents);  // Strip any and all carriage returns.
				// Match and process strings, comments and everything else, one chunk at a time.
				// To understand this regex, read: "Mastering Regular Expressions 3rd Edition" chapter 6.
			$contents = preg_replace_callback('%
				# One-regex-to-rule-them-all! - version: 20100220_0100
				# Group 1: Match a double quoted string.
				("[^"\\\\]*+(?:\\\\.[^"\\\\]*+)*+") |  # or...
				# Group 2: Match a single quoted string.
				(\'[^\'\\\\]*+(?:\\\\.[^\'\\\\]*+)*+\') |  # or...
				# Group 3: Match a regular non-MacIE5-hack comment.
				(/\*[^\\\\*]*+\*++(?:[^\\\\*/][^\\\\*]*+\*++)*+/) |  # or...
				# Group 4: Match a MacIE5-type1 comment.
				(/\*(?:[^*\\\\]*+\**+(?!/))*+\\\\[^*]*+\*++(?:[^*/][^*]*+\*++)*+/(?<!\\\\\*/)) |  # or...
				# Group 5: Match a MacIE5-type2 comment.
				(/\*[^*]*\*+(?:[^/*][^*]*\*+)*/(?<=\\\\\*/))  # folllowed by...
				# Group 6: Match everything up to final closing regular comment
				([^/]*+(?:(?!\*)/[^/]*+)*?)
				# Group 7: Match final closing regular comment
				(/\*[^/]++(?:(?<!\*)/(?!\*)[^/]*+)*+/(?<=(?<!\\\\)\*/)) |  # or...
				# Group 8: Match regular non-string, non-comment text.
				([^"\'/]*+(?:(?!/\*)/[^"\'/]*+)*+)
				%Ssx', array('self','compressCssPregCallback'), $contents); // Do it!
			$contents = preg_replace('/^\s++/', '', $contents);				 // Strip leading whitespace.
			$contents = preg_replace('/[ \t]*+\n\s*+/S', "\n", $contents);  // Consolidate multi-lines space.
			$contents = preg_replace('/(?<!\s)\s*+$/S', "\n", $contents);   // Ensure file ends in newline.
				// we have to fix relative paths, if we aren't working on a file in our target directory
			if (!is_int(strpos($filename, $this->targetDirectory))) {
				$filenameRelativeToMainDir = substr($filename, strlen($GLOBALS['BACK_PATH']));
				$contents = $this->cssFixRelativeUrlPaths($contents, dirname($filenameRelativeToMainDir) . '/');
			}
			$this->writeFileAndCompressed($targetFile, $contents);
		}

		return $GLOBALS['BACK_PATH'] . '../' . $this->returnFileReference($targetFile);
	}

	/**
	 * Callback function for preg_replace
	 *
	 * @see compressCssFile
	 * @param array $matches
	 * @return string the compressed string
	 */
	public static function compressCssPregCallback($matches) {
		if ($matches[1]) {		// Group 1: Double quoted string.
			return $matches[1];   // Return the string unmodified.
		} elseif ($matches[2]) {  // Group 2: Single quoted string.
			return $matches[2];   // Return the string unmodified.
		} elseif ($matches[3]) {  // Group 3: Regular non-MacIE5-hack comment.
			return "\n";				// Return single space.
		} elseif ($matches[4]) {  // Group 4: MacIE5-hack-type-1 comment.
			return "\n/*\\T1*/\n";  // Return minimal MacIE5-hack-type-1 comment.
		}
		elseif ($matches[5]) {  // Group 5,6,7: MacIE5-hack-type-2 comment
			$matches[6] = preg_replace('/\s++([+>{};,)])/S', '$1', $matches[6]);  // Clean pre-punctuation.
			$matches[6] = preg_replace('/([+>{}:;,(])\s++/S', '$1', $matches[6]); // Clean post-punctuation.
			$matches[6] = preg_replace('/;?\}/S', "}\n", $matches[6]);				// Add a touch of formatting.
			return "\n/*T2\\*/" . $matches[6] . "\n/*T2E*/\n"; // Minify and reassemble composite type2 comment.
		} elseif (isset($matches[8])) { // Group 8: Non-string, non-comment. Safe to clean whitespace here.
			$matches[8] = preg_replace('/^\s++/', '', $matches[8]);				   // Strip all leading whitespace.
			$matches[8] = preg_replace('/\s++$/', '', $matches[8]);				   // Strip all trailing whitespace.
			$matches[8] = preg_replace('/\s{2,}+/', ' ', $matches[8]);				// Consolidate multiple whitespace.
			$matches[8] = preg_replace('/\s++([+>{};,)])/S', '$1', $matches[8]);  // Clean pre-punctuation.
			$matches[8] = preg_replace('/([+>{}:;,(])\s++/S', '$1', $matches[8]); // Clean post-punctuation.
			$matches[8] = preg_replace('/;?\}/S', "}\n", $matches[8]);				// Add a touch of formatting.
			return $matches[8];
		}
		return $matches[0] . "\n/* ERROR! Unexpected _proccess_css_minify() parameter */\n"; // never get here
	}

	/**
	 * Compress multiple javascript files
	 *
	 * @param	array	$jsFiles		The files to compress (array key = filename), relative to requested page
	 * @return	array		The js files after compression (array key = new filename), relative to requested page
		*/
	public function compressJsFiles(array $jsFiles) {
		$filesAfterCompression = array();
		foreach ($jsFiles as $filename => $fileOptions) {
				// we remove BACK_PATH from $filename, so make it relative to TYPO3_mainDir
			$filenameFromMainDir = substr($filename, strlen($GLOBALS['BACK_PATH']));
				// if compression is enabled
			if ($fileOptions['compress']) {
				$filesAfterCompression[$this->compressJsFile($filename)] = $fileOptions;
			} else {
				$filesAfterCompression[$filename] = $fileOptions;
			}
		}
		return $filesAfterCompression;
	}

	/**
	 * Compresses a javascript file
	 *
	 * Options:
	 *   baseDirectories		If set, only include files below one of the base directories
	 *
	 * @param	string	$filename		Source filename, relative to requested page
	 * @return	string		Filename of the compressed file, relative to requested page
	 */
	public function compressJsFile($filename) {
			// generate the unique name of the file
		$filenameAbsolute = t3lib_div::resolveBackPath(PATH_typo3 . substr($filename, strlen($GLOBALS['BACK_PATH'])));
		$unique = $filenameAbsolute . filemtime($filenameAbsolute) . filesize($filenameAbsolute);

		$pathinfo = pathinfo($filename);
		$targetFile = $this->targetDirectory . $pathinfo['filename'] . '-' . md5($unique) . '.js';
			// only create it, if it doesn't exist, yet
		if (!file_exists(PATH_site . $targetFile) || ($this->createGzipped && !file_exists(PATH_site . $targetFile . '.gzip'))) {
			$contents = t3lib_div::getUrl($filenameAbsolute);
			$this->writeFileAndCompressed($targetFile, $contents);
		}
		return $GLOBALS['BACK_PATH'] . '../' . $this->returnFileReference($targetFile);
	}

	/**
	 * Decides whether a CSS file comes from one of the baseDirectories
	 *
	 * @param	string	$filename		Filename
	 * @return	boolean		File belongs to a skin or not
	 */
	protected function checkBaseDirectory($filename, array $baseDirectories) {
		foreach ($baseDirectories as $baseDirectory) {
				// check, if $filename starts with $skinStylesheetDirectory
			if (t3lib_div::isFirstPartOfStr($filename, $baseDirectory)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Fixes the relative paths inside of url() references in CSS files
	 *
	 * @param	string	$contents		Data to process
	 * @param	string	$oldDir			Directory of the originial file, relative to TYPO3_mainDir
	 * @return	string	Processed data
	 */
	protected function cssFixRelativeUrlPaths($contents, $oldDir) {
		$matches = array();

		preg_match_all('/url(\(\s*["\']?([^"\']+)["\']?\s*\))/iU', $contents, $matches);
		foreach ($matches[2] as $matchCount => $match) {
				// remove '," or white-spaces around
			$match = preg_replace('/[\"\'\s]/', '', $match);

				// we must not rewrite paths containing ":", e.g. data URIs (see RFC 2397)
			if (strpos($match, ':') === FALSE) {
				$newPath = t3lib_div::resolveBackPath('../../' . TYPO3_mainDir . $oldDir . $match);
				$contents = str_replace($matches[1][$matchCount], '(\'' . $newPath  . '\')', $contents);
			}
		}
		return $contents;
	}

	/**
	 * Writes $contents into file $filename together with a gzipped version into $filename.gz
	 *
	 * @param	string	$filename		Target filename
	 * @param	strings	$contents		File contents
	 * @return	void
	 */
	protected function writeFileAndCompressed($filename, $contents) {
			// write uncompressed file
		t3lib_div::writeFile(PATH_site . $filename, $contents);

		if ($this->createGzipped) {
				// create compressed version
			$compressionLevel = -1;
			if (isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['compressionLevel']) && is_numeric($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['compressionLevel'])) {
				$compressionLevel = intval($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['compressionLevel']);
			}
			t3lib_div::writeFile(PATH_site . $filename . '.gzip', gzencode($contents, $compressionLevel));
		}
	}

	/**
	 * Decides whether a client can deal with gzipped content or not and returns the according file name,
	 * based on HTTP_ACCEPT_ENCODING
	 *
	 * @param	string	$filename		File name
	 * @return	string		$filename suffixed with '.gzip' or not - dependent on HTTP_ACCEPT_ENCODING
	 */
	protected function returnFileReference($filename) {
			// if the client accepts gzip and we can create gzipped files, we give him compressed versions
		if ($this->createGzipped && strpos(t3lib_div::getIndpEnv('HTTP_ACCEPT_ENCODING'), 'gzip') !== FALSE) {
			return $filename . '.gzip';
		} else {
			return $filename;
		}
	}
}
?>
