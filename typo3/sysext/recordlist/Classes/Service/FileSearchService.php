<?php
namespace TYPO3\CMS\Recordlist\Service;

use TYPO3\CMS\Core\Resource\Folder;

/**
 * Class FileSearchService
 */
class FileSearchService {
	/**
	 * @param \TYPO3\CMS\Core\Resource\Folder $baseFolder
	 * @param string $searchPattern
	 * @param boolean $recursive
	 * @return array
	 */
	public function searchFiles(Folder $baseFolder, $searchPattern, $recursive = FALSE) {
		$result = $baseFolder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive);

		return $result;
	}
}

?>