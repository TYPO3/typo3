<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Resource,
    \TYPO3\CMS\Core\Utility;

/**
 * Helpts to generate thumbnails
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
	 * copies the typo3temp/ file to the processingfolder of the target storage
	 * removes the typo3temp/ file
	 *
	 * @param Task $task
	 * @return string
	 */
	public function process(Task $task) {
		$targetFile = $task->getTargetFile();

			// Merge custom configuration with default configuration
		$configuration = array_merge(array('width' => 64, 'height' => 64), $task->getConfiguration());
		$configuration['width'] = Utility\MathUtility::forceIntegerInRange($configuration['width'], 1, 1000);
		$configuration['height'] = Utility\MathUtility::forceIntegerInRange($configuration['height'], 1, 1000);

		$originalFileName = $targetFile->getOriginalFile()->getForLocalProcessing(FALSE);

			// Create a temporary file in typo3temp/
		if ($targetFile->getOriginalFile()->getExtension() === 'jpg') {
			$targetFileExtension = '.jpg';
		} else {
			$targetFileExtension = '.png';
		}

			// Create the thumb filename in typo3temp/preview_....jpg
		$temporaryFileName = Utility\GeneralUtility::tempnam('preview_') . $targetFileExtension;
			// Check file extension
		if ($targetFile->getOriginalFile()->getType() != Resource\File::FILETYPE_IMAGE &&
			!Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $targetFile->getOriginalFile()->getExtension())) {
				// Create a default image
			$this->processor->getTemporaryImageWithText($temporaryFileName, 'Not imagefile!', 'No ext!', $targetFile->getOriginalFile()->getName());
		} else {
				// Create the temporary file
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
				$parameters = '-sample ' . $configuration['width'] . 'x' . $configuration['height'] . ' '
					. $this->processor->wrapFileName($originalFileName) . '[0] ' . $this->processor->wrapFileName($temporaryFileName);

				$cmd = Utility\GeneralUtility::imageMagickCommand('convert', $parameters);
				Utility\CommandUtility::exec($cmd);

				if (!file_exists($temporaryFileName)) {
						// Create a error gif
					$this->processor->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', $targetFile->getOriginalFile()->getName());
				}
			}
		}

		return array(
			'filePath' => $temporaryFileName,
		);
	}
}

?>