<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Resource;

/**
 * Task which does the tt_content Image Rendering
 */
class ImageCropScaleMaskTask extends AbstractGraphicalTask {

	/**
	 * @var string
	 */
	protected $type = 'Image';

	/**
	 * @var string
	 */
	protected $name = 'CropScaleMask';

	/**
	 * @return string
	 */
	public function getTargetFileName() {
		return 'csm_' . parent::getTargetFilename();
	}

	/**
	 * Checks if the given configuration is sensible for this task, i.e. if all required parameters
	 * are given, within the boundaries and don't conflict with each other.
	 *
	 * @param array $configuration
	 * @return boolean
	 */
	protected function isValidConfiguration(array $configuration) {
		// TODO: Implement isValidConfiguration() method.
	}

	public function fileNeedsProcessing() {
		// TODO: Implement fileNeedsProcessing() method.
	}
}

?>