<?php
namespace TYPO3\CMS\Recordlist\ViewHelpers;


use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Resource\File;

class FileIconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {
	/**
	 * @param string $fileExtension
	 * @param array|object $file
	 */
	public function render($fileExtension = '', $file = NULL) {
		if ($fileExtension != '') {
			return IconUtility::getSpriteIconForFile($fileExtension);
		} else if ($file instanceof File) {
			return IconUtility::getSpriteIconForFile($file->getExtension());
		} else if (is_array($file)) {
			return IconUtility::getSpriteIconForFile($file['extension']);
		}
	}
}