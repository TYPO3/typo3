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

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Documentation\Domain\Repository\DocumentRepository;
use TYPO3\CMS\Documentation\Service\DocumentationService;
use TYPO3\CMS\Documentation\Utility\LanguageUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Main controller of the Documentation module.
 */
class DocumentController extends ActionController
{
    /**
     * @var DocumentRepository
     */
    protected $documentRepository;

    /**
     * @var DocumentationService
     */
    protected $documentationService;

    /**
     * @var LanguageUtility
     */
    protected $languageUtility;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Backend Template Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            /** @var BackendTemplateView $view */
            parent::initializeView($view);
            $view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
            $uriBuilder = $this->objectManager->get(UriBuilder::class);
            $uriBuilder->setRequest($this->request);

            $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Documentation/Main');
            $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $menu->setIdentifier('DocumentationModuleMenu');

            $isListActive = $this->request->getControllerActionName() === 'list' ? true : false;
            $uri = $uriBuilder->reset()->uriFor('list', [], 'Document');
            $listMenuItem = $menu->makeMenuItem()
                ->setTitle($this->getLanguageService()
                    ->sL('LLL:EXT:documentation/Resources/Private/Language/locallang.xlf:showDocumentation'))
                ->setHref($uri)
                ->setActive($isListActive);
            $menu->addMenuItem($listMenuItem);

            if ($this->getBackendUser()->isAdmin()) {
                $isDownloadActive = $this->request->getControllerActionName() ===
                'download' ? true : false;
                $uri =
                    $uriBuilder->reset()->uriFor('download', [], 'Document');
                $downloadMenuItem = $menu->makeMenuItem()
                    ->setTitle($this->getLanguageService()
                        ->sL('LLL:EXT:documentation/Resources/Private/Language/locallang.xlf:downloadDocumentation'))
                    ->setHref($uri)
                    ->setActive($isDownloadActive);
                $menu->addMenuItem($downloadMenuItem);
            }

            $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
            $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        }
    }

    /**
     * @param DocumentRepository $documentRepository
     */
    public function injectDocumentRepository(DocumentRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * @param DocumentationService $documentationService
     */
    public function injectDocumentationService(DocumentationService $documentationService)
    {
        $this->documentationService = $documentationService;
    }

    /**
     * @param LanguageUtility $languageUtility
     */
    public function injectLanguageUtility(LanguageUtility $languageUtility)
    {
        $this->languageUtility = $languageUtility;
    }

    /**
     * @param Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Lists the available documents.
     *
     * @return void
     */
    public function listAction()
    {
        $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);

        $documents = $this->getDocuments();

        // Filter documents to be shown for current user
        $hideDocuments = $this->getBackendUser()->getTSConfigVal('mod.help_DocumentationDocumentation.documents.hide');
        $hideDocuments = GeneralUtility::trimExplode(',', $hideDocuments, true);
        if (!empty($hideDocuments)) {
            $documents = array_diff_key($documents, array_flip($hideDocuments));
        }
        $showDocuments = $this->getBackendUser()->getTSConfigVal('mod.help_DocumentationDocumentation.documents.show');
        $showDocuments = GeneralUtility::trimExplode(',', $showDocuments, true);
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
    public function getDocuments()
    {
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
    protected function emitAfterInitializeDocumentsSignal($language, array $documents)
    {
        $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            'afterInitializeDocuments',
            [
                $language,
                &$documents,
            ]
        );
        return $documents;
    }

    /**
     * Shows documents to be downloaded/fetched from a remote location.
     *
     * @return void
     */
    public function downloadAction()
    {
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
    public function fetchAction($url, $key, $version = null)
    {
        // This action is reserved for admin users. Redirect to default view if not.
        if (!$this->getBackendUser()->isAdmin()) {
            $this->redirect('list');
        }

        $language = $this->languageUtility->getDocumentationLanguage();
        try {
            $result = $this->documentationService->fetchNearestDocument($url, $key, $version ?: 'latest', $language);
            if ($result) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'downloadSucceeded',
                        'documentation'
                    ),
                    '',
                    FlashMessage::OK
                );
            } else {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'downloadFailedNoArchive',
                        'documentation'
                    ),
                    LocalizationUtility::translate(
                        'downloadFailed',
                        'documentation'
                    ),
                    FlashMessage::ERROR
                );
            }
        } catch (\Exception $e) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'downloadFailedDetails',
                    'documentation',
                    [
                        $key,
                        $e->getMessage(),
                        $e->getCode()
                    ]
                ),
                LocalizationUtility::translate(
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
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
