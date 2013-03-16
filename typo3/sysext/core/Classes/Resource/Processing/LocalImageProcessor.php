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
 * Processes Local Images files
 */
class LocalImageProcessor implements ProcessorInterface {

	/**
	 * @var \TYPO3\CMS\Core\Log\Logger
	 */
	protected $logger;

	/**
	 * Constructor
	 */
	public function __construct() {
		/** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
		$logManager = Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager');
		$this->logger = $logManager->getLogger(__CLASS__);
	}

	/**
	 * Returns TRUE if this processor can process the given task.
	 *
	 * @param TaskInterface $task
	 * @return boolean
	 */
	public function canProcessTask(TaskInterface $task) {
		$canProcessTask = $task->getType() === 'Image';
		$canProcessTask = $canProcessTask & in_array($task->getName(), array('Preview', 'CropScaleMask'));
		return $canProcessTask;
	}

	/**
	 * Processes the given task.
	 *
	 * @param TaskInterface $task
	 * @throws \InvalidArgumentException
	 */
	public function processTask(TaskInterface $task) {
		if (!$this->canProcessTask($task)) {
			throw new \InvalidArgumentException('Cannot process task of type "' . $task->getType() . '.' . $task->getName() . '"', 1350570621);
		}
		$helper = $this->getHelperByTaskName($task->getName());
		try {
			$result = $helper->process($task);
			if ($result === NULL) {
				$task->setExecuted(TRUE);
				$task->getTargetFile()->setUsesOriginalFile();
			} elseif (file_exists($result['filePath'])) {
				$task->setExecuted(TRUE);
				$graphicalFunctions = $this->getGraphicalFunctionsObject();
				$imageDimensions = $graphicalFunctions->getImageDimensions($result['filePath']);

				$task->getTargetFile()->setName($task->getTargetFileName());
				$task->getTargetFile()->updateProperties(
					array('width' => $imageDimensions[0], 'height' => $imageDimensions[1], 'size' => filesize($result['filePath']), 'checksum' => $task->getConfigurationChecksum())
				);
				$task->getTargetFile()->updateWithLocalFile($result['filePath']);
			} else {
				// Seems we have no valid processing result
				$task->setExecuted(FALSE);
			}
		} catch (\Exception $e) {
			$task->setExecuted(FALSE);
		}
	}

	/**
	 * @param string $taskName
	 * @return LocalCropScaleMaskHelper|LocalPreviewHelper
	 * @throws \InvalidArgumentException
	 */
	protected function getHelperByTaskName($taskName) {
		switch ($taskName) {
			case 'Preview':
				$helper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper', $this);
			break;
			case 'CropScaleMask':
				$helper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper', $this);
			break;
			default:
				throw new \InvalidArgumentException('Cannot find helper for task name: "' . $taskName . '"', 1353401352);
		}

		return $helper;
	}

	/**
	 * Escapes a file name so it can safely be used on the command line.
	 *
	 * @param string $inputName filename to safeguard, must not be empty
	 * @return string $inputName escaped as needed
	 *
	 * @internal Don't use this method from outside the LocalImageProcessor!
	 */
	public function wrapFileName($inputName) {
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
			$currentLocale = setlocale(LC_CTYPE, 0);
			setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
			$escapedInputName = escapeshellarg($inputName);
			setlocale(LC_CTYPE, $currentLocale);
		} else {
			$escapedInputName = escapeshellarg($inputName);
		}
		return $escapedInputName;
	}

	/**
	 * Creates error image based on gfx/notfound_thumb.png
	 * Requires GD lib enabled, otherwise it will exit with the three
	 * textstrings outputted as text. Outputs the image stream to browser and exits!
	 *
	 * @param string $filename Name of the file
	 * @param string $textline1 Text line 1
	 * @param string $textline2 Text line 2
	 * @param string $textline3 Text line 3
	 * @return void
	 * @throws \RuntimeException
	 *
	 * @internal Don't use this method from outside the LocalImageProcessor!
	 */
	public function getTemporaryImageWithText($filename, $textline1, $textline2, $textline3) {
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
			throw new \RuntimeException('TYPO3 Fatal Error: No gdlib. ' . $textline1 . ' ' . $textline2 . ' ' . $textline3, 1270853952);
		}
		// Creates the basis for the error image
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
			$im = imagecreatefrompng(PATH_typo3 . 'gfx/notfound_thumb.png');
		} else {
			$im = imagecreatefromgif(PATH_typo3 . 'gfx/notfound_thumb.gif');
		}
		// Sets background color and print color.
		$white = imageColorAllocate($im, 255, 255, 255);
		$black = imageColorAllocate($im, 0, 0, 0);
		// Prints the text strings with the build-in font functions of GD
		$x = 0;
		$font = 0;
		if ($textline1) {
			imagefilledrectangle($im, $x, 9, 56, 16, $white);
			imageString($im, $font, $x, 9, $textline1, $black);
		}
		if ($textline2) {
			imagefilledrectangle($im, $x, 19, 56, 26, $white);
			imageString($im, $font, $x, 19, $textline2, $black);
		}
		if ($textline3) {
			imagefilledrectangle($im, $x, 29, 56, 36, $white);
			imageString($im, $font, $x, 29, substr($textline3, -14), $black);
		}
		// Outputting the image stream and exit
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
			imagePng($im, $filename);
		} else {
			imageGif($im, $filename);
		}
	}

	/**
	 * @return \TYPO3\CMS\Core\Imaging\GraphicalFunctions
	 */
	protected function getGraphicalFunctionsObject() {
		static $graphicalFunctionsObject = NULL;

		if ($graphicalFunctionsObject === NULL) {
			$graphicalFunctionsObject = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
		}

		return $graphicalFunctionsObject;
	}
}

?>