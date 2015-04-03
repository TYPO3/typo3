<?php
namespace TYPO3\CMS\Core\Resource\Processing;

/**
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
	 * @return array|NULL
	 */
	public function process(TaskInterface $task) {
		$targetFile = $task->getTargetFile();
		$sourceFile = $task->getSourceFile();

			// Merge custom configuration with default configuration
		$configuration = array_merge(array('width' => 64, 'height' => 64), $task->getConfiguration());
		$configuration['width'] = Utility\MathUtility::forceIntegerInRange($configuration['width'], 1);
		$configuration['height'] = Utility\MathUtility::forceIntegerInRange($configuration['height'], 1);

		// Only scale down when new dimensions are smaller then existing image
		if ($configuration['width'] > $sourceFile->getProperty('width')
			&& $configuration['height'] > $sourceFile->getProperty('height')) {
			return NULL;
		}

		$originalFileName = $sourceFile->getForLocalProcessing(FALSE);

			// Create a temporaryFile
		$temporaryFileName = Utility\GeneralUtility::tempnam('preview_', '.' . $task->getTargetFileExtension());
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
