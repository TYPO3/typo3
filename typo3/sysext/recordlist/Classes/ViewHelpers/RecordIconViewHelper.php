<?php
namespace TYPO3\CMS\Recordlist\ViewHelpers;


use TYPO3\CMS\Core\Resource\File;

/**
 * View helper for displaying a record icon
 */
class RecordIconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {
	/**
	 * @param array|object $record
	 */
	public function render($record) {
		if (is_array($record)) {
			//
		} else if ($record instanceof File) {
			//
		}
	}
}

?>