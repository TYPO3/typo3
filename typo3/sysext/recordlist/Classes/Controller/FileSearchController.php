<?php
namespace TYPO3\CMS\Recordlist\Controller;

use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Recordlist\Service\FileSearchService;

class FileSearchController extends ActionController {

	/**
	 * @var FileSearchService
	 */
	protected $fileSearchService;

	/**
	 * Searches for files in a given folder.
	 *
	 * @param string $filePattern The file name (pattern) that should be used for searching
	 * @param string $baseFolder The combined identifier (<storage>:<path>) of the folder to search in
	 * @param boolean $recursive If TRUE, subfolders of $baseFolder are also searched.
	 */
	public function resultsAction($filePattern, $baseFolder, $recursive = FALSE) {
		$this->fileSearchService = $this->objectManager->get('TYPO3\\CMS\\Recordlist\\Service\\FileSearchService');

		$factory = new ResourceFactory();
		$folder = $factory->getFolderObjectFromCombinedIdentifier($baseFolder);

		$filePattern = trim($filePattern);

		$folder->setFileAndFolderNameFilters(array(
			function($itemName, $itemIdentifier, $parentIdentifier, $additionalInformation) use ($filePattern) {
				$fileNameMatch = stripos($itemName, $filePattern) !== FALSE;

				$metaDataMatch = FALSE;
				GeneralUtility::loadTCA('sys_file');
				if (isset($additionalInformation['indexData']) && !empty($GLOBALS['TCA']['sys_file']['ctrl']['searchFields'])) {
					foreach(GeneralUtility::trimExplode(',', $GLOBALS['TCA']['sys_file']['ctrl']['searchFields']) as $field) {
						$metaDataMatch = $metaDataMatch || stripos($additionalInformation['indexData'][$field], $filePattern) !== FALSE;
					}
				}

				return ($fileNameMatch || $metaDataMatch) ? TRUE : -1;
			}
		));

		$result = $this->fileSearchService->searchFiles($folder, $filePattern, $recursive);

		$this->view->assign('files', $result);
	}
}