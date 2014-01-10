<?php
namespace TYPO3\CMS\Impexp\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Susanne Moog <susanne.moog@typo3.org>
 *  (c) 2013 Oliver Hader <oliver.hader@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Utility for import / export
 * Can be used for API access for simple importing of files
 *
 */
class ImportExportUtility {

	/**
	 * Import a T3D file directly
	 *
	 * @param string $file The full absolute path to the file
	 * @param int $pid The pid under which the t3d file should be imported
	 * @throws \ErrorException
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function importT3DFile($file, $pid) {
		$importResponse = array();
		if (!is_string($file)) {
			throw new \InvalidArgumentException('Input parameter $file has to be of type string', 1377625645);
		}
		if (!is_int($pid)) {
			throw new \InvalidArgumentException('Input parameter $int has to be of type integer', 1377625646);
		}
		/** @var $import \TYPO3\CMS\Impexp\ImportExport */
		$import = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\ImportExport');
		$import->init(0, 'import');

		if ($file && @is_file($file)) {
			if ($import->loadFile($file, 1)) {
				// Import to root page:
				$import->importData($pid);
				// Get id of container page:
				$newPages = $import->import_mapId['pages'];
				reset($newPages);
				$importResponse = current($newPages);
			}
		}

		// Check for errors during the import process:
		if (empty($importResponse) && $errors = $import->printErrorLog()) {
			throw new \ErrorException($errors, 1377625537);
		} else {
			return $importResponse;
		}
	}
}
