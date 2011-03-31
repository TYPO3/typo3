<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Kai Vogel <kai.vogel@speedprogs.de>
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
 * Recycler garbage collection task
 *
 * This task finds all "_recycler_" folders below fileadmin and
 * empties them to free some space in filesystem.
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 * @package TYPO3
 * @subpackage scheduler
 */
class tx_scheduler_RecyclerGarbageCollection extends tx_scheduler_Task {

	/**
	 * Difference between now and last modification time to cleanup,
	 * folders, set by additional field provider.
	 *
	 * @var int Difference between now and last modification time
	 */
	public $numberOfDays = 0;

	/**
	 * Recycler directory name
	 *
	 * @var string Recycler directory name
	 */
	protected $recyclerDirectory = '_recycler_';


	/**
	 * Cleanup recycled files, called by scheduler.
	 *
	 * @return void
	 */
	public function execute() {
		$seconds   = (60 * 60 * 24 * (int) $this->numberOfDays);
		$timestamp = ($GLOBALS['SIM_EXEC_TIME'] - $seconds);

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
	 * old ones
	 *
	 * @param string $directory Path to the directory
	 * @param integer $timestamp Timestamp of the last file modification
	 * @return boolean TRUE if success
	 */
	protected function cleanupRecycledFiles($directory, $timestamp) {
		$directory = t3lib_div::getFileAbsFileName($directory);
		$timestamp = (int) $timestamp;

			// Check if given directory exists
		if (!(@is_dir($directory))) {
			throw new InvalidArgumentException('Given directory "' . $directory . '" does not exist', 1301614535);
		}

			// Unlink old files in directory
		$directoryContent = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
		foreach ($directoryContent as $fileName => $file) {
				// Skip other directories
			$filePath = $file->getPath();
			if (substr($filePath, strrpos($filePath, '/') + 1) !== $this->recyclerDirectory) {
				continue;
			}

				// Remove file if last modified before given timestamp
			if ($file->isFile() && $timestamp > $file->getMTime()) {
				if (!(@unlink($fileName))) {
					throw new InvalidArgumentException('Could not remove file "' . $fileName . '"', 1301614537);
				}
			}
		}

		return TRUE;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_recyclergarbagecollection.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_recyclergarbagecollection.php']);
}

?>