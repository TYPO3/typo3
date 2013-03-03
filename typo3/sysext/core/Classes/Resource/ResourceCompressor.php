<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Gebert <steffen@steffen-gebert.de>
 *  (c) 2011-2013 Kai Vogel <kai.vogel@speedprogs.de>
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

use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Compressor
 * This merges and compresses CSS and JavaScript files of the TYPO3 Backend.
 *
 * @author 	Steffen Gebert <steffen@steffen-gebert.de>
 */
class ResourceCompressor {

	protected $targetDirectory = 'typo3temp/compressor/';

	protected $relativePath = '';

	protected $rootPath = '';

	protected $backPath = '';

	// gzipped versions are only created if $TYPO3_CONF_VARS[TYPO3_MODE]['compressionLevel'] is set
	protected $createGzipped = FALSE;

	// default compression level is -1
	protected $gzipCompressionLevel = -1;

	protected $htaccessTemplate = '<FilesMatch "\\.(js|css)(\\.gzip)?$">
	<IfModule mod_expires.c>
		ExpiresActive on
		ExpiresDefault "access plus 7 days"
	</IfModule>
	FileETag MTime Size
</FilesMatch>';

	/**
	 * Constructor
	 */
	public function __construct() {
		// we check for existence of our targetDirectory
		if (!is_dir((PATH_site . $this->targetDirectory))) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir(PATH_site . $this->targetDirectory);
		}
		// if enabled, we check whether we should auto-create the .htaccess file
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess']) {
			// check whether .htaccess exists
			$htaccessPath = PATH_site . $this->targetDirectory . '.htaccess';
			if (!file_exists($htaccessPath)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($htaccessPath, $this->htaccessTemplate);
			}
		}
		// decide whether we should create gzipped versions or not
		$compressionLevel = $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['compressionLevel'];
		// we need zlib for gzencode()
		if (extension_loaded('zlib') && $compressionLevel) {
			$this->createGzipped = TRUE;
			// $compressionLevel can also be TRUE
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($compressionLevel)) {
				$this->gzipCompressionLevel = intval($compressionLevel);
			}
		}
		$this->setInitialPaths();
	}

	/**
	 * Sets initial values for paths.
	 *
	 * @return void
	 */
	public function setInitialPaths() {
		$this->setInitialRelativePath();
		$this->setInitialRootPath();
		$this->setInitialBackPath();
	}

	/**
	 * Sets relative back path
	 *
	 * @return void
	 */
	protected function setInitialBackPath() {
		$backPath = TYPO3_MODE === 'BE' ? $GLOBALS['BACK_PATH'] : '';
		$this->setBackPath($backPath);
	}

	/**
	 * Sets absolute path to working directory
	 *
	 * @return void
	 */
	protected function setInitialRootPath() {
		$rootPath = TYPO3_MODE === 'BE' ? PATH_typo3 : PATH_site;
		$this->setRootPath($rootPath);
	}

	/**
	 * Sets relative path to PATH_site
	 *
	 * @return void
	 */
	protected function setInitialRelativePath() {
		$relativePath = TYPO3_MODE === 'BE' ? $GLOBALS['BACK_PATH'] . '../' : '';
		$this->setRelativePath($relativePath);
	}

	/**
	 * Sets relative path to PATH_site
	 *
	 * @param string $relativePath Relative path to site root
	 * @return void
	 */
	public function setRelativePath($relativePath) {
		if (is_string($relativePath)) {
			$this->relativePath = $relativePath;
		}
	}

	/**
	 * Sets absolute path to working directory
	 *
	 * @param string $rootPath Absolute path
	 * @return void
	 */
	public function setRootPath($rootPath) {
		if (is_string($rootPath)) {
			$this->rootPath = $rootPath;
		}
	}

	/**
	 * Sets relative back path
	 *
	 * @param string $backPath Back path
	 * @return void
	 */
	public function setBackPath($backPath) {
		if (is_string($backPath)) {
			$this->backPath = $backPath;
		}
	}

	/**
	 * Concatenates the Stylesheet files
	 *
	 * Options:
	 * baseDirectories If set, only include files below one of the base directories
	 *
	 * @param array $cssFiles CSS files to process
	 * @param array $options Additional options
	 * @return array CSS files
	 */
	public function concatenateCssFiles(array $cssFiles, array $options = array()) {
		$filesToInclude = array();
		foreach ($cssFiles as $key => $fileOptions) {
			// no concatenation allowed for this file, so continue
			if (!empty($fileOptions['excludeFromConcatenation'])) {
				continue;
			}
			// we remove BACK_PATH from $filename, so make it relative to root path
			$filenameFromMainDir = $this->getFilenameFromMainDir($fileOptions['file']);
			// if $options['baseDirectories'] set, we only include files below these directories
			if ((!isset($options['baseDirectories']) || $this->checkBaseDirectory($filenameFromMainDir, array_merge($options['baseDirectories'], array($this->targetDirectory)))) && $fileOptions['media'] === 'all') {
				$filesToInclude[] = $filenameFromMainDir;
				// remove the file from the incoming file array
				unset($cssFiles[$key]);
			}
		}
		if (count($filesToInclude)) {
			$targetFile = $this->createMergedCssFile($filesToInclude);
			$targetFileRelative = $this->relativePath . $targetFile;
			$concatenatedOptions = array(
				'file' => $targetFileRelative,
				'rel' => 'stylesheet',
				'media' => 'all',
				'compress' => TRUE
			);
			// place the merged stylesheet on top of the stylesheets
			$cssFiles = array_merge(array($targetFileRelative => $concatenatedOptions), $cssFiles);
		}
		return $cssFiles;
	}

	/**
	 * Concatenates the JavaScript files
	 *
	 * @param array $jsFiles JavaScript files to process
	 * @return array JS files
	 */
	public function concatenateJsFiles(array $jsFiles) {
		$filesToInclude = array();
		foreach ($jsFiles as $key => $fileOptions) {
			// invalid section found or no concatenation allowed, so continue
			if (empty($fileOptions['section']) || !empty($fileOptions['excludeFromConcatenation'])) {
				continue;
			}
			// we remove BACK_PATH from $filename, so make it relative to root path
			$filesToInclude[$fileOptions['section']][] = $this->getFilenameFromMainDir($fileOptions['file']);
			// remove the file from the incoming file array
			unset($jsFiles[$key]);
		}
		if (!empty($filesToInclude)) {
			foreach ($filesToInclude as $section => $files) {
				$targetFile = $this->createMergedJsFile($files);
				$targetFileRelative = $this->relativePath . $targetFile;
				$concatenatedOptions = array(
					'file' => $targetFileRelative,
					'type' => 'text/javascript',
					'section' => $section,
					'compress' => TRUE,
					'forceOnTop' => FALSE,
					'allWrap' => ''
				);
				// place the merged javascript on top of the JS files
				$jsFiles = array_merge(array($targetFileRelative => $concatenatedOptions), $jsFiles);
			}
		}
		return $jsFiles;
	}

	/**
	 * Creates a merged CSS file
	 *
	 * @param array $filesToInclude Files which should be merged, paths relative to root path
	 * @return mixed Filename of the merged file
	 */
	protected function createMergedCssFile(array $filesToInclude) {
		return $this->createMergedFile($filesToInclude, 'css');
	}

	/**
	 * Creates a merged JS file
	 *
	 * @param array $filesToInclude Files which should be merged, paths relative to root path
	 * @return mixed Filename of the merged file
	 */
	protected function createMergedJsFile(array $filesToInclude) {
		return $this->createMergedFile($filesToInclude, 'js');
	}

	/**
	 * Creates a merged file with given file type
	 *
	 * @param array $filesToInclude Files which should be merged, paths relative to root path
	 * @param string $type File type
	 *
	 * @throws \InvalidArgumentException
	 * @return mixed Filename of the merged file
	 */
	protected function createMergedFile(array $filesToInclude, $type = 'css') {
		// Get file type
		$type = strtolower(trim($type, '. '));
		if (empty($type)) {
			throw new \InvalidArgumentException('Error in TYPO3\\CMS\\Core\\Resource\\ResourceCompressor: No valid file type given for merged file', 1308957498);
		}
		// we add up the filenames, filemtimes and filsizes to later build a checksum over
		// it and include it in the temporary file name
		$unique = '';
		foreach ($filesToInclude as $key => $filename) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($filename)) {
				// check if it is possibly a local file with fully qualified URL
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isOnCurrentHost($filename) &&
					\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr(
						$filename,
						\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL')
					)
				) {
					// attempt to turn it into a local file path
					$localFilename = substr($filename, strlen(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL')));
					if (@is_file(\TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($this->rootPath . $localFilename))) {
						$filesToInclude[$key] = $localFilename;
					} else {
						$filesToInclude[$key] = $this->retrieveExternalFile($filename);
					}
				} else {
					$filesToInclude[$key] = $this->retrieveExternalFile($filename);
				}
				$filename = $filesToInclude[$key];
			}
			$filepath = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($this->rootPath . $filename);
			$unique .= $filename . filemtime($filepath) . filesize($filepath);
		}
		$targetFile = $this->targetDirectory . 'merged-' . md5($unique) . '.' . $type;
		// if the file doesn't already exist, we create it
		if (!file_exists((PATH_site . $targetFile))) {
			$concatenated = '';
			// concatenate all the files together
			foreach ($filesToInclude as $filename) {
				$contents = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($this->rootPath . $filename));
				// only fix paths if files aren't already in typo3temp (already processed)
				if ($type === 'css' && !\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($filename, $this->targetDirectory)) {
					$contents = $this->cssFixRelativeUrlPaths($contents, PathUtility::dirname($filename) . '/');
				}
				$concatenated .= LF . $contents;
			}
			// move @charset, @import and @namespace statements to top of new file
			if ($type === 'css') {
				$concatenated = $this->cssFixStatements($concatenated);
			}
			\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(PATH_site . $targetFile, $concatenated);
		}
		return $targetFile;
	}

	/**
	 * Compress multiple css files
	 *
	 * @param array $cssFiles The files to compress (array key = filename), relative to requested page
	 * @return array The CSS files after compression (array key = new filename), relative to requested page
	 */
	public function compressCssFiles(array $cssFiles) {
		$filesAfterCompression = array();
		foreach ($cssFiles as $key => $fileOptions) {
			// if compression is enabled
			if ($fileOptions['compress']) {
				$filename = $this->compressCssFile($fileOptions['file']);
				$fileOptions['file'] = $filename;
				$filesAfterCompression[$filename] = $fileOptions;
			} else {
				$filesAfterCompression[$key] = $fileOptions;
			}
		}
		return $filesAfterCompression;
	}

	/**
	 * Compresses a CSS file
	 *
	 * Options:
	 * baseDirectories If set, only include files below one of the base directories
	 *
	 * removes comments and whitespaces
	 * Adopted from http://drupal.org/files/issues/minify_css.php__1.txt
	 *
	 * @param string $filename Source filename, relative to requested page
	 * @return string Compressed filename, relative to requested page
	 */
	public function compressCssFile($filename) {
		// generate the unique name of the file
		$filenameAbsolute = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($this->rootPath . $this->getFilenameFromMainDir($filename));
		$unique = $filenameAbsolute . filemtime($filenameAbsolute) . filesize($filenameAbsolute);
		$pathinfo = PathUtility::pathinfo($filename);
		$targetFile = $this->targetDirectory . $pathinfo['filename'] . '-' . md5($unique) . '.css';
		// only create it, if it doesn't exist, yet
		if (!file_exists((PATH_site . $targetFile)) || $this->createGzipped && !file_exists((PATH_site . $targetFile . '.gzip'))) {
			$contents = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($filenameAbsolute);
			// Perform some safe CSS optimizations.
			$contents = str_replace(CR, '', $contents);
			// Strip any and all carriage returns.
			// Match and process strings, comments and everything else, one chunk at a time.
			// To understand this regex, read: "Mastering Regular Expressions 3rd Edition" chapter 6.
			$contents = preg_replace_callback('%
				# One-regex-to-rule-them-all! - version: 20100220_0100
				# Group 1: Match a double quoted string.
				("[^"\\\\]*+(?:\\\\.[^"\\\\]*+)*+") |  # or...
				# Group 2: Match a single quoted string.
				(\'[^\'\\\\]*+(?:\\\\.[^\'\\\\]*+)*+\') |  # or...
				# Group 3: Match a regular non-MacIE5-hack comment.
				(/\\*[^\\\\*]*+\\*++(?:[^\\\\*/][^\\\\*]*+\\*++)*+/) |  # or...
				# Group 4: Match a MacIE5-type1 comment.
				(/\\*(?:[^*\\\\]*+\\**+(?!/))*+\\\\[^*]*+\\*++(?:[^*/][^*]*+\\*++)*+/(?<!\\\\\\*/)) |  # or...
				# Group 5: Match a MacIE5-type2 comment.
				(/\\*[^*]*\\*+(?:[^/*][^*]*\\*+)*/(?<=\\\\\\*/))  # folllowed by...
				# Group 6: Match everything up to final closing regular comment
				([^/]*+(?:(?!\\*)/[^/]*+)*?)
				# Group 7: Match final closing regular comment
				(/\\*[^/]++(?:(?<!\\*)/(?!\\*)[^/]*+)*+/(?<=(?<!\\\\)\\*/)) |  # or...
				# Group 8: Match regular non-string, non-comment text.
				([^"\'/]*+(?:(?!/\\*)/[^"\'/]*+)*+)
				%Ssx', array('self', 'compressCssPregCallback'), $contents);
			// Do it!
			$contents = preg_replace('/^\\s++/', '', $contents);
			// Strip leading whitespace.
			$contents = preg_replace('/[ \\t]*+\\n\\s*+/S', '
', $contents);
			// Consolidate multi-lines space.
			$contents = preg_replace('/(?<!\\s)\\s*+$/S', '
', $contents);
			// Ensure file ends in newline.
			// we have to fix relative paths, if we aren't working on a file in our target directory
			if (strpos($filename, $this->targetDirectory) === FALSE) {
				$filenameRelativeToMainDir = substr($filename, strlen($this->backPath));
				$contents = $this->cssFixRelativeUrlPaths($contents, PathUtility::dirname($filenameRelativeToMainDir) . '/');
			}
			$this->writeFileAndCompressed($targetFile, $contents);
		}
		return $this->relativePath . $this->returnFileReference($targetFile);
	}

	/**
	 * Callback function for preg_replace
	 *
	 * @see compressCssFile
	 * @param array $matches
	 * @return string the compressed string
	 */
	static public function compressCssPregCallback($matches) {
		if ($matches[1]) {
			// Group 1: Double quoted string.
			return $matches[1];
		} elseif ($matches[2]) {
			// Group 2: Single quoted string.
			return $matches[2];
		} elseif ($matches[3]) {
			// Group 3: Regular non-MacIE5-hack comment.
			return '
';
		} elseif ($matches[4]) {
			// Group 4: MacIE5-hack-type-1 comment.
			return '
/*\\T1*/
';
		} elseif ($matches[5]) {
			// Group 5,6,7: MacIE5-hack-type-2 comment
			$matches[6] = preg_replace('/\\s++([+>{};,)])/S', '$1', $matches[6]);
			// Clean pre-punctuation.
			$matches[6] = preg_replace('/([+>{}:;,(])\\s++/S', '$1', $matches[6]);
			// Clean post-punctuation.
			$matches[6] = preg_replace('/;?\\}/S', '}
', $matches[6]);
			// Add a touch of formatting.
			return '
/*T2\\*/' . $matches[6] . '
/*T2E*/
';
		} elseif (isset($matches[8])) {
			// Group 8: Non-string, non-comment. Safe to clean whitespace here.
			$matches[8] = preg_replace('/^\\s++/', '', $matches[8]);
			// Strip all leading whitespace.
			$matches[8] = preg_replace('/\\s++$/', '', $matches[8]);
			// Strip all trailing whitespace.
			$matches[8] = preg_replace('/\\s{2,}+/', ' ', $matches[8]);
			// Consolidate multiple whitespace.
			$matches[8] = preg_replace('/\\s++([+>{};,)])/S', '$1', $matches[8]);
			// Clean pre-punctuation.
			$matches[8] = preg_replace('/([+>{}:;,(])\\s++/S', '$1', $matches[8]);
			// Clean post-punctuation.
			$matches[8] = preg_replace('/;?\\}/S', '}
', $matches[8]);
			// Add a touch of formatting.
			return $matches[8];
		}
		return $matches[0] . '
/* ERROR! Unexpected _proccess_css_minify() parameter */
';
	}

	/**
	 * Compress multiple javascript files
	 *
	 * @param array $jsFiles The files to compress (array key = filename), relative to requested page
	 * @return array The js files after compression (array key = new filename), relative to requested page
	 */
	public function compressJsFiles(array $jsFiles) {
		$filesAfterCompression = array();
		foreach ($jsFiles as $key => $fileOptions) {
			// if compression is enabled
			if ($fileOptions['compress']) {
				$fileOptions['file'] = $this->compressJsFile($fileOptions['file']);
			}
			$filesAfterCompression[$key] = $fileOptions;
		}
		return $filesAfterCompression;
	}

	/**
	 * Compresses a javascript file
	 *
	 * @param string $filename Source filename, relative to requested page
	 * @return string Filename of the compressed file, relative to requested page
	 */
	public function compressJsFile($filename) {
		// generate the unique name of the file
		$filenameAbsolute = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($this->rootPath . $this->getFilenameFromMainDir($filename));
		$unique = $filenameAbsolute . filemtime($filenameAbsolute) . filesize($filenameAbsolute);
		$pathinfo = PathUtility::pathinfo($filename);
		$targetFile = $this->targetDirectory . $pathinfo['filename'] . '-' . md5($unique) . '.js';
		// only create it, if it doesn't exist, yet
		if (!file_exists((PATH_site . $targetFile)) || $this->createGzipped && !file_exists((PATH_site . $targetFile . '.gzip'))) {
			$contents = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($filenameAbsolute);
			$this->writeFileAndCompressed($targetFile, $contents);
		}
		return $this->relativePath . $this->returnFileReference($targetFile);
	}

	/**
	 * Finds the relative path to a file, relative to the root path.
	 *
	 * @param string $filename the name of the file
	 * @return string the path to the file relative to the root path
	 */
	protected function getFilenameFromMainDir($filename) {
		// if BACK_PATH is empty return $filename
		if (empty($this->backPath)) {
			return $filename;
		}
		// if the file exists in the root path, just return the $filename
		if (strpos($filename, $this->backPath) === 0) {
			$file = str_replace($this->backPath, '', $filename);
			if (is_file($this->rootPath . $file)) {
				return $file;
			}
		}
		// if the file is from a special TYPO3 internal directory, add the missing typo3/ prefix
		if (is_file(realpath(PATH_site . TYPO3_mainDir . $filename))) {
			$filename = TYPO3_mainDir . $filename;
		}
		// build the file path relatively to the PATH_site
		$backPath = str_replace(TYPO3_mainDir, '', $this->backPath);
		$file = str_replace($backPath, '', $filename);
		if (substr($file, 0, 3) === '../') {
			$file = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath(PATH_typo3 . $file);
		} else {
			$file = PATH_site . $file;
		}
		// check if the file exists, and if so, return the path relative to TYPO3_mainDir
		if (is_file($file)) {
			$mainDirDepth = substr_count(TYPO3_mainDir, '/');
			return str_repeat('../', $mainDirDepth) . str_replace(PATH_site, '', $file);
		}
		// none of above conditions were met, fallback to default behaviour
		return substr($filename, strlen($this->backPath));
	}

	/**
	 * Decides whether a file comes from one of the baseDirectories
	 *
	 * @param string $filename Filename
	 * @param array $baseDirectories Base directories
	 * @return boolean File belongs to a base directory or not
	 */
	protected function checkBaseDirectory($filename, array $baseDirectories) {
		foreach ($baseDirectories as $baseDirectory) {
			// check, if $filename starts with base directory
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($filename, $baseDirectory)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Fixes the relative paths inside of url() references in CSS files
	 *
	 * @param string $contents Data to process
	 * @param string $oldDir Directory of the original file, relative to TYPO3_mainDir
	 * @return string Processed data
	 */
	protected function cssFixRelativeUrlPaths($contents, $oldDir) {
		$mainDir = TYPO3_MODE === 'BE' ? TYPO3_mainDir : '';
		$newDir = '../../' . $mainDir . $oldDir;
		// Replace "url()" paths
		if (stripos($contents, 'url') !== FALSE) {
			$regex = '/url(\\(\\s*["\']?(?!\\/)([^"\']+)["\']?\\s*\\))/iU';
			$contents = $this->findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, '(\'|\')');
		}
		// Replace "@import" paths
		if (stripos($contents, '@import') !== FALSE) {
			$regex = '/@import\\s*(["\']?(?!\\/)([^"\']+)["\']?)/i';
			$contents = $this->findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, '"|"');
		}
		return $contents;
	}

	/**
	 * Finds and replaces all URLs by using a given regex
	 *
	 * @param string $contents Data to process
	 * @param string $regex Regex used to find URLs in content
	 * @param string $newDir Path to prepend to the original file
	 * @param string $wrap Wrap around replaced values
	 * @return string Processed data
	 */
	protected function findAndReplaceUrlPathsByRegex($contents, $regex, $newDir, $wrap = '|') {
		$matches = array();
		$replacements = array();
		$wrap = explode('|', $wrap);
		preg_match_all($regex, $contents, $matches);
		foreach ($matches[2] as $matchCount => $match) {
			// remove '," or white-spaces around
			$match = trim($match, '\'" ');
			// we must not rewrite paths containing ":" or "url(", e.g. data URIs (see RFC 2397)
			if (strpos($match, ':') === FALSE && !preg_match('/url\\s*\\(/i', $match)) {
				$newPath = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($newDir . $match);
				$replacements[$matches[1][$matchCount]] = $wrap[0] . $newPath . $wrap[1];
			}
		}
		// replace URL paths in content
		if (!empty($replacements)) {
			$contents = str_replace(array_keys($replacements), array_values($replacements), $contents);
		}
		return $contents;
	}

	/**
	 * Moves @charset, @import and @namespace statements to the top of
	 * the content, because they must occur before all other CSS rules
	 *
	 * @param string $contents Data to process
	 * @return string Processed data
	 */
	protected function cssFixStatements($contents) {
		$matches = array();
		$comment = LF . '/* moved by compressor */' . LF;
		// nothing to do, so just return contents
		if (stripos($contents, '@charset') === FALSE && stripos($contents, '@import') === FALSE && stripos($contents, '@namespace') === FALSE) {
			return $contents;
		}
		$regex = '/@(charset|import|namespace)\\s*(url)?\\s*\\(?\\s*["\']?[^"\']+["\']?\\s*\\)?.*;/i';
		preg_match_all($regex, $contents, $matches);
		if (!empty($matches[0])) {
			// remove existing statements
			$contents = str_replace($matches[0], '', $contents);
			// add statements to the top of contents in the order they occur in original file
			$contents = $comment . implode($comment, $matches[0]) . LF . $contents;
		}
		return $contents;
	}

	/**
	 * Writes $contents into file $filename together with a gzipped version into $filename.gz
	 *
	 * @param string $filename Target filename
	 * @param string $contents File contents
	 * @return void
	 */
	protected function writeFileAndCompressed($filename, $contents) {
		// write uncompressed file
		\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(PATH_site . $filename, $contents);
		if ($this->createGzipped) {
			// create compressed version
			\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(PATH_site . $filename . '.gzip', gzencode($contents, $this->gzipCompressionLevel));
		}
	}

	/**
	 * Decides whether a client can deal with gzipped content or not and returns the according file name,
	 * based on HTTP_ACCEPT_ENCODING
	 *
	 * @param string $filename File name
	 * @return string $filename suffixed with '.gzip' or not - dependent on HTTP_ACCEPT_ENCODING
	 */
	protected function returnFileReference($filename) {
		// if the client accepts gzip and we can create gzipped files, we give him compressed versions
		if ($this->createGzipped && strpos(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_ACCEPT_ENCODING'), 'gzip') !== FALSE) {
			return $filename . '.gzip';
		} else {
			return $filename;
		}
	}

	/**
	 * Retrieves an external file and stores it locally.
	 *
	 * @param string $url
	 * @return string Temporary local filename for the externally-retrieved file
	 */
	protected function retrieveExternalFile($url) {
		$externalContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url);
		$filename = $this->targetDirectory . 'external-' . md5($url);
		// write only if file does not exist and md5 of the content is not the same as fetched one
		if (!file_exists(PATH_site . $filename) &&
				(md5($externalContent) !== md5(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(PATH_site . $filename)))
		) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(PATH_site . $filename, $externalContent);
		}
		return $filename;
	}

}


?>