<?php
namespace TYPO3\CMS\Core\Resource\Processing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Wolf <andreas.wolf@typo3.org>
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

use \TYPO3\CMS\Core\Resource, \TYPO3\CMS\Core\Utility;

/**
 * Helper for creating local image previews using TYPO3s image processing classes.
 */
class LocalPreviewHelper {
	/**
	 * @var LocalImageProcessor
	 */
	protected $processor;

	/**
	 * @param LocalImageProcessor $processor
	 */
	public function __construct(LocalImageProcessor $processor) {
		$this->processor = $processor;
	}

	/**
	 * This method actually does the processing of files locally
	 *
	 * takes the original file (on remote storages this will be fetched from the remote server)
	 * does the IM magic on the local server by creating a temporary typo3temp/ file
	 * copies the typo3temp/ file to the processing folder of the target storage
	 * removes the typo3temp/ file
	 *
	 * @param TaskInterface $task
	 * @return array
	 */
	public function process(TaskInterface $task) {
		$targetFile = $task->getTargetFile();
		$sourceFile = $task->getSourceFile();

			// Merge custom configuration with default configuration
		$configuration = array_merge(array('width' => 64, 'height' => 64), $task->getConfiguration());
		$configuration['width'] = Utility\MathUtility::forceIntegerInRange($configuration['width'], 1);
		$configuration['height'] = Utility\MathUtility::forceIntegerInRange($configuration['height'], 1);

		$originalFileName = $sourceFile->getForLocalProcessing(FALSE);

			// Create the thumb filename in typo3temp/preview_....jpg
		$temporaryFileName = Utility\GeneralUtility::tempnam('preview_') . '.' . $task->getTargetFileExtension();
			// Check file extension
		if ($sourceFile->getType() != Resource\File::FILETYPE_IMAGE &&
			!Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $sourceFile->getExtension())) {
				// Create a default image
			$this->processor->getTemporaryImageWithText($temporaryFileName, 'Not imagefile!', 'No ext!', $sourceFile->getName());
		} else {
				// Create the temporary file
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
				$parameters = '-sample ' . $configuration['width'] . 'x' . $configuration['height'] . ' '
					. $this->processor->wrapFileName($originalFileName) . '[0] ' . $this->processor->wrapFileName($temporaryFileName);

				$cmd = Utility\GeneralUtility::imageMagickCommand('convert', $parameters) . ' 2>&1';
				Utility\CommandUtility::exec($cmd);

				if (!file_exists($temporaryFileName)) {
						// Create a error gif
					$this->processor->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', $sourceFile->getName());
				}
			}
		}

		return array(
			'filePath' => $temporaryFileName,
		);
	}
}

?>