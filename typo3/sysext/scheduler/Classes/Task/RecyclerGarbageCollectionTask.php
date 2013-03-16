<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Recycler folder garbage collection task
 *
 * This task finds all "_recycler_" folders below fileadmin and
 * deletes all file in them that where not change for more than
 * given number of days.
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class RecyclerGarbageCollectionTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Elapsed period since last modification before a file will
	 * be deleted in a recycler directory.
	 *
	 * @var int Number of days before cleaning up files
	 */
	public $numberOfDays = 0;

	/**
	 * Name of the recycler directories below the fileadmin dir.
	 *
	 * @var string Recycler directory name
	 */
	protected $recyclerDirectory = '_recycler_';

	/**
	 * Cleanup recycled files, called by scheduler.
	 *
	 * @return boolean TRUE if task run was successful
	 */
	public function execute() {
		// There is no file ctime on windows, so this task disables itself if OS = win
		if (TYPO3_OS == 'WIN') {
			throw new \BadMethodCallException('This task is not reliable for Windows OS', 1308270454);
		}
		$seconds = 60 * 60 * 24 * (int) $this->numberOfDays;
		$timestamp = $GLOBALS['EXEC_TIME'] - $seconds;
		// Get fileadmin directory
		$directory = PATH_site . 'fileadmin/';
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'])) {
			$directory = PATH_site . trim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']);
		}
		// Execute cleanup
		return $this->cleanupRecycledFiles($directory, $timestamp);
	}

	/**
	 * Gets a list of all files in a directory recursively and removes
	 * old ones.
	 *
	 * @throws \RuntimeException If folders are not found or files can not be deleted
	 * @param string $directory Path to the directory
	 * @param integer $timestamp Timestamp of the last file modification
	 * @return boolean TRUE if success
	 */
	protected function cleanupRecycledFiles($directory, $timestamp) {
		$directory = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($directory);
		$timestamp = (int) $timestamp;
		// Check if given directory exists
		if (!@is_dir($directory)) {
			throw new \RuntimeException('Given directory "' . $directory . '" does not exist', 1301614535);
		}
		// Find all _recycler_ directories
		$directoryContent = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
		foreach ($directoryContent as $fileName => $file) {
			// Skip directories and files without recycler directory in absolute path
			$filePath = $file->getPath();
			if (substr($filePath, strrpos($filePath, '/') + 1) !== $this->recyclerDirectory) {
				continue;
			}
			// Remove files from _recycler_ that where moved to this folder for more than 'number of days'
			if ($file->isFile() && $timestamp > $file->getCTime()) {
				if (!@unlink($fileName)) {
					throw new \RuntimeException('Could not remove file "' . $fileName . '"', 1301614537);
				}
			}
		}
		return TRUE;
	}

}


?>