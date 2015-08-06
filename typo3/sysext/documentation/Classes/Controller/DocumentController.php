<?php
namespace TYPO3\CMS\Documentation\Controller;

/*
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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main controller of the Documentation module.
 */
class DocumentController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\CMS\Documentation\Domain\Repository\DocumentRepository
	 */
	protected $documentRepository;

	/**
	 * @var \TYPO3\CMS\Documentation\Service\DocumentationService
	 */
	protected $documentationService;

	/**
	 * @var \TYPO3\CMS\Documentation\Utility\LanguageUtility
	 */
	protected $languageUtility;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @param \TYPO3\CMS\Documentation\Domain\Repository\DocumentRepository $documentRepository
	 */
	public function injectDocumentRepository(\TYPO3\CMS\Documentation\Domain\Repository\DocumentRepository $documentRepository) {
		$this->documentRepository = $documentRepository;
	}

	/**
	 * @param \TYPO3\CMS\Documentation\Service\DocumentationService $documentationService
	 */
	public function injectDocumentationService(\TYPO3\CMS\Documentation\Service\DocumentationService $documentationService) {
		$this->documentationService = $documentationService;
	}

	/**
	 * @param \TYPO3\CMS\Documentation\Utility\LanguageUtility $languageUtility
	 */
	public function injectLanguageUtility(\TYPO3\CMS\Documentation\Utility\LanguageUtility $languageUtility) {
		$this->languageUtility = $languageUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
	 */
	public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
	}

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
		if (!empty($hideDocuments)) {
			$documents = array_diff_key($documents, array_flip($hideDocuments));
		}
		$showDocuments = $this->getBackendUser()->getTSConfigVal('mod.help_DocumentationDocumentation.documents.show');
		$showDocuments = GeneralUtility::trimExplode(',', $showDocuments, TRUE);
		if (!empty($showDocuments)) {
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

		$documents = $this->emitAfterInitializeDocumentsSignal($language, $documents);

		return $documents;
	}

	/**
	 * Emits a signal after the documents are initialized
	 *
	 * @param string $language
	 * @param \TYPO3\CMS\Documentation\Domain\Model\Document[] $documents
	 * @return \TYPO3\CMS\Documentation\Domain\Model\Document[]
	 */
	protected function emitAfterInitializeDocumentsSignal($language, array $documents) {
		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			'afterInitializeDocuments',
			array(
				$language,
				&$documents,
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
				$this->addFlashMessage(
					\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
						'downloadSucceeded',
						'documentation'
					),
					'',
					FlashMessage::OK
				);
			} else {
				$this->addFlashMessage(
					\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
						'downloadFailedNoArchive',
						'documentation'
					),
					\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
						'downloadFailed',
						'documentation'
					),
					FlashMessage::ERROR
				);
			}
		} catch (\Exception $e) {
			$this->addFlashMessage(
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
				FlashMessage::ERROR
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
