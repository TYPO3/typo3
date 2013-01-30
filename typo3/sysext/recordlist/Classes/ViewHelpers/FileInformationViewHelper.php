<?php
namespace TYPO3\CMS\Recordlist\ViewHelpers;


use TYPO3\CMS\Core\Resource\File;

class FileInformationViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {
	/**
	 * @param integer $number
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return string
	 */
	public function render($number, File $file) {
		return json_encode(
			array("file_" . $number => array(
				"type" => "file",
				"table" => "sys_file",
				"uid" => (int)$file->getUid(),
				"fileName" => $file->getName(),
				"filePath" => $file->getUid(),
				"fileExt" => $file->getExtension(),
				"fileIcon" => "", // TODO
			))
		);
	}
}