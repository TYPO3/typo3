<?php
namespace TYPO3\CMS\Documentation\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Andrea Schmuttermair <spam@schmutt.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main controller of the Documentation module.
 *
 * @author Andrea Schmuttermair <spam@schmutt.de>
 */
class DocumentController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * documentRepository
	 *
	 * @var \TYPO3\CMS\Documentation\Domain\Repository\DocumentRepository
	 * @inject
	 */
	protected $documentRepository;

	/**
	 * @var \TYPO3\CMS\Documentation\Service\DocumentationService
	 * @inject
	 */
	protected $documentationService;

	/**
	 * languageUtility
	 *
	 * @var \TYPO3\CMS\Documentation\Utility\LanguageUtility
	 * @inject
	 */
	protected $languageUtility;

	/**
	 * Signal Slot dispatcher
	 *
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * Lists the available documents.
	 *
	 * @return void
	 */
	public function listAction() {
		$documents = $this->getDocuments();

		// Filter documents to be shown for current user
		$hideDocuments = $this->getBackendUser()->getTSConfigVal('mod.help_DocumentationDocumentation.documents.hide');
		$hideDocuments = GeneralUtility::trimExplode(',', $hideDocuments, TRUE);
		if (count($hideDocuments) > 0) {
			$documents = array_diff_key($documents, array_flip($hideDocuments));
		}
		$showDocuments = $this->getBackendUser()->getTSConfigVal('mod.help_DocumentationDocumentation.documents.show');
		$showDocuments = GeneralUtility::trimExplode(',', $showDocuments, TRUE);
		if (count($showDocuments) > 0) {
			$documents = array_intersect_key($documents, array_flip($showDocuments));
		}

		$this->view->assign('documents', $documents);
	}

	/**
	 * Returns available documents.
	 *
	 * @return \TYPO3\CMS\Documentation\Domain\Model\Document[]
	 * @api
	 */
	public function getDocuments() {
		$language = $this->languageUtility->getDocumentationLanguage();
		$documents = $this->documentRepository->findByLanguage($language);

		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			'afterInitializeDocuments',
			array(
				'language'  => $language,
				'documents' => &$documents,
			)
		);

		return $documents;
	}

	/**
	 * Shows documents to be downloaded/fetched from a remote location.
	 *
	 * @return void
	 */
	public function downloadAction() {
		// This action is reserved for admin users. Redirect to default view if not.
		if (!$this->getBackendUser()->isAdmin()) {
			$this->redirect('list');
		}

		// Retrieve the list of official documents
		$documents = $this->documentationService->getOfficialDocuments();

		// Merge with the list of local extensions
		$extensions = $this->documentationService->getLocalExtensions();
		$allDocuments = array_merge($documents, $extensions);

		$this->view->assign('documents', $allDocuments);
	}

	/**
	 * Fetches a document from a remote URL.
	 *
	 * @param string $url
	 * @param string $key
	 * @param string $version
	 * @return void
	 */
	public function fetchAction($url, $key, $version = NULL) {
		// This action is reserved for admin users. Redirect to default view if not.
		if (!$this->getBackendUser()->isAdmin()) {
			$this->redirect('list');
		}

		$language = $this->languageUtility->getDocumentationLanguage();
		try {
			$result = $this->documentationService->fetchNearestDocument($url, $key, $version ?: 'latest', $language);

			if ($result) {
				$this->controllerContext->getFlashMessageQueue()->enqueue(
					GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
							'downloadSucceeded',
							'documentation'
						),
						'',
						\TYPO3\CMS\Core\Messaging\AbstractMessage::OK,
						TRUE
					)
				);
			} else {
				$this->controllerContext->getFlashMessageQueue()->enqueue(
					GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
							'downloadFailedNoArchive',
							'documentation'
						),
						\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
							'downloadFailed',
							'documentation'
						),
						\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
						TRUE
					)
				);
			}
		} catch (\Exception $e) {
			$this->controllerContext->getFlashMessageQueue()->enqueue(
				GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
						'downloadFailedDetails',
						'documentation',
						array(
							$key,
							$e->getMessage(),
							$e->getCode()
						)
					),
					\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
						'downloadFailed',
						'documentation'
					),
					\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
					TRUE
				)
			);
		}
		$this->redirect('download');
	}

	/**
	 * Get backend user
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

}
