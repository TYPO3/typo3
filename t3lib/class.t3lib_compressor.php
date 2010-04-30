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
 * This class can currently merge CSS files of the TYPO3 Backend.
 *
 * @author	Steffen Gebert <steffen@steffen-gebert.de>
 * @package TYPO3
 * @subpackage t3lib
 * $Id$
 */
class t3lib_compressor {

	protected $skinStylesheetDirectories = array();
	protected $targetDirectory = 'typo3temp/compressor/';

	/**
	 * Constructor
	 */
	public function __construct() {

			// we check for existance of our targetDirectory
		if (!is_dir(PATH_site . $this->targetDirectory)) {
			t3lib_div::mkdir(PATH_site . $this->targetDirectory);
		}
	}

	/**
	 * Concatenates the cssFiles
	 *
	 * Options:
	 *   baseDirectories		If set, only include files below one of the base directories
	 *
	 * @param	array	$cssFiles		CSS files added to the PageRenderer
	 * @param	array	$options		Additional options
	 * @return	array	CSS files
	 */
	public function concatenateCssFiles(array $cssFiles, $options = array()) {
		$filesToInclude = array();
		foreach ($cssFiles as $filename => $fileOptions) {
				// we remove BACK_PATH from $filename, so make it relative to TYPO3_mainDir
			$filenameFromMainDir = substr($filename, strlen($GLOBALS['BACK_PATH']));
				// if $options['baseDirectories'] set, we only include files below these directroies
			if (!isset($options['baseDirectories']) || $this->checkBaseDirectory($filenameFromMainDir, $options['baseDirectories'])) {
				$filesToInclude[] = $filenameFromMainDir;
					// remove the file from the incoming file array
				unset($cssFiles[$filename]);
			}
		}

		if (count($filesToInclude)) {
			$targetFile = $this->createMergedCssFile($filesToInclude);
			$concatenatedOptions = array(
					'rel'		  => 'stylesheet',
					'media'		=> 'all',
					'title'		=> 'Merged TYPO3 Backend Stylesheets',
					'compress'   => TRUE,
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
			$unique .= $filename . filemtime($GLOBALS['BACK_PATH'] . $filename) . filesize($GLOBALS['BACK_PATH'] . $filename);
		}
		$targetFile = $this->targetDirectory . TYPO3_MODE . '-'. md5($unique) . '.css';

			// if the file doesn't already exist, we create it
		if (!file_exists($targetFile)) {
			$concatenated = '';
				// concatenate all the files together
			foreach ($filesToInclude as $filename) {
				$contents = file_get_contents($GLOBALS['BACK_PATH'] . $filename);
				$concatenated .= $this->cssFixRelativeUrlPaths($contents, dirname($filename) . '/', $this->targetDirectory);
			}

			if (strlen($concatenated)) {
				t3lib_div::writeFile(PATH_site . $targetFile, $concatenated);
			}
		}
		return $targetFile;
	}

	/**
	 * Decides whether a CSS file comes from one of the baseDirectories
	 *
	 * @param	string	$filename		Filename
	 * @return	boolean		File belongs to a skin or not
	 */
	protected function checkBaseDirectory($filename, array $baseDirectories) {
		foreach ($baseDirectories as $baseDirectory) {
				// check, if $filename starts with $skinStylesheetDirectory (it's position 0, not FALSE!)
			if (strpos($filename, $baseDirectory) === 0) {
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
	 * @param	string	$newDir			Directory of the resulting file
	 * @return	string	Processed data
	 */
	protected function cssFixRelativeUrlPaths($contents, $oldDir, $newDir) {
		$matches = array();
		preg_match_all('/url[\s]*\([\'\"]?(.*)[\'\"]?\)/iU', $contents, $matches);
		foreach ($matches[1] as $match) {
				// remove '," or white-spaces around
			$match = preg_replace('/[\"\'\s]/', '', $match);

			$newPath = t3lib_div::resolveBackPath('../../' . TYPO3_mainDir . $oldDir . $match);
			$contents = str_replace($match, $newPath, $contents);
		}
		return $contents;
	}
}

?>