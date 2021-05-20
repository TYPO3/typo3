<?php

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

namespace TYPO3\CMS\Extensionmanager\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Controller for extension listings (TER or local extensions)
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ListController extends AbstractModuleController
{
    /**
     * @var ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var ListUtility
     */
    protected $listUtility;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var DependencyUtility
     */
    protected $dependencyUtility;

    /**
     * @param ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @param ListUtility $listUtility
     */
    public function injectListUtility(ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    /**
     * @param PageRenderer $pageRenderer
     */
    public function injectPageRenderer(PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * @param DependencyUtility $dependencyUtility
     */
    public function injectDependencyUtility(DependencyUtility $dependencyUtility)
    {
        $this->dependencyUtility = $dependencyUtility;
    }

    /**
     * Add the needed JavaScript files for all actions
     */
    public function initializeAction()
    {
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:extensionmanager/Resources/Private/Language/locallang.xlf');
        $isAutomaticInstallationEnabled = (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('extensionmanager', 'offlineMode');
        if ($isAutomaticInstallationEnabled) {
            $this->settings['offlineMode'] = true;
        }
    }

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
            $this->generateMenu();
            $this->registerDocheaderButtons();
        }
    }

    /**
     * Adds an information about composer mode
     */
    protected function addComposerModeNotification()
    {
        if (Environment::isComposerMode()) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'composerMode.message',
                    'extensionmanager'
                ) ?? '',
                LocalizationUtility::translate(
                    'composerMode.title',
                    'extensionmanager'
                ) ?? '',
                FlashMessage::INFO
            );
        }
    }

    /**
     * Shows list of extensions present in the system
     */
    public function indexAction(): ResponseInterface
    {
        if ($this->request->hasArgument('filter') && is_string($this->request->getArgument('filter'))) {
            $filter = $this->request->getArgument('filter');
            $this->saveBackendUserFilter($filter);
        } else {
            $filter = $this->getBackendUserFilter();
        }

        $this->addComposerModeNotification();
        $availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation($filter);
        ksort($availableAndInstalledExtensions);
        $this->view->assignMultiple(
            [
                'extensions' => $availableAndInstalledExtensions,
                'isComposerMode' => Environment::isComposerMode(),
                'typeFilter' => $filter ?: 'All'
            ]
        );
        $this->handleTriggerArguments();

        return $this->htmlResponse();
    }

    /**
     * Shows a list of unresolved dependency errors with the possibility to bypass the dependency check
     *
     * @param string $extensionKey
     * @return ResponseInterface
     */
    public function unresolvedDependenciesAction($extensionKey): ResponseInterface
    {
        $availableExtensions = $this->listUtility->getAvailableExtensions();
        if (isset($availableExtensions[$extensionKey])) {
            $extensionArray = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation(
                [
                    $extensionKey => $availableExtensions[$extensionKey]
                ]
            );
            $extension = Extension::createFromExtensionArray($extensionArray[$extensionKey]);
        } else {
            throw new ExtensionManagerException('Extension ' . $extensionKey . ' is not available', 1402421007);
        }
        $this->dependencyUtility->checkDependencies($extension);
        $this->view->assign('extension', $extension);
        $this->view->assign('unresolvedDependencies', $this->dependencyUtility->getDependencyErrors());

        return $this->htmlResponse();
    }

    /**
     * Shows extensions from TER
     * Either all extensions or depending on a search param
     *
     * @param string $search
     * @param int $currentPage
     * @return ResponseInterface
     */
    public function terAction($search = '', int $currentPage = 1): ResponseInterface
    {
        $this->addComposerModeNotification();
        $search = trim($search);
        if (!empty($search)) {
            $extensions = $this->extensionRepository->findByTitleOrAuthorNameOrExtensionKey($search);
            $paginator = new ArrayPaginator($extensions, $currentPage);
            $tableId = 'terSearchTable';
        } else {
            /** @var QueryResultInterface $extensions */
            $extensions = $this->extensionRepository->findAll();
            $paginator = new QueryResultPaginator($extensions, $currentPage);
            $tableId = 'terTable';
        }
        $pagination = new SimplePagination($paginator);
        $availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensions($this->listUtility->getAvailableExtensions());
        $this->view->assignMultiple([
            'extensions' => $extensions,
            'paginator' => $paginator,
            'pagination' => $pagination,
            'search' => $search,
            'availableAndInstalled' => $availableAndInstalledExtensions,
            'actionName' => 'ter',
            'tableId' => $tableId,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Action for listing all possible distributions
     *
     * @param bool $showUnsuitableDistributions
     * @return ResponseInterface
     */
    public function distributionsAction($showUnsuitableDistributions = false): ResponseInterface
    {
        $this->addComposerModeNotification();
        $importExportInstalled = ExtensionManagementUtility::isLoaded('impexp');
        if ($importExportInstalled) {
            try {
                $remoteRegistry = GeneralUtility::makeInstance(RemoteRegistry::class);
                foreach ($remoteRegistry->getListableRemotes() as $remote) {
                    $remote->getAvailablePackages();
                }
            } catch (ExtensionManagerException $e) {
                $this->addFlashMessage($e->getMessage(), $e->getCode(), FlashMessage::ERROR);
            }

            $officialDistributions = $this->extensionRepository->findAllOfficialDistributions($showUnsuitableDistributions);
            $communityDistributions = $this->extensionRepository->findAllCommunityDistributions($showUnsuitableDistributions);

            $this->view->assign('officialDistributions', $officialDistributions);
            $this->view->assign('communityDistributions', $communityDistributions);
        }
        $this->view->assign('enableDistributionsView', $importExportInstalled);
        $this->view->assign('showUnsuitableDistributions', $showUnsuitableDistributions);

        return $this->htmlResponse();
    }

    /**
     * Shows all versions of a specific extension
     *
     * @param string $extensionKey
     * @return ResponseInterface
     */
    public function showAllVersionsAction($extensionKey): ResponseInterface
    {
        $currentVersion = $this->extensionRepository->findOneByCurrentVersionByExtensionKey($extensionKey);
        $extensions = $this->extensionRepository->findByExtensionKeyOrderedByVersion($extensionKey);

        $this->view->assignMultiple(
            [
                'extensionKey' => $extensionKey,
                'currentVersion' => $currentVersion,
                'extensions' => $extensions
            ]
        );

        return $this->htmlResponse();
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        if (Environment::isComposerMode()) {
            return;
        }

        if (!in_array($this->actionMethodName, ['indexAction', 'terAction', 'showAllVersionsAction'], true)) {
            return;
        }

        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        if ($this->actionMethodName === 'showAllVersionsAction') {
            $uri = $this->uriBuilder->reset()->uriFor('ter', [], 'List');
            $title = $this->translate('extConfTemplate.backToList');
            $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL);
            $classes = '';
        } else {
            $uri = $this->uriBuilder->reset()->uriFor('form', [], 'UploadExtensionFile');
            $title = $this->translate('extensionList.uploadExtension');
            $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-edit-upload', Icon::SIZE_SMALL);
            $classes = 't3js-upload';
        }
        $button = $buttonBar->makeLinkButton()
            ->setHref($uri)
            ->setTitle($title)
            ->setClasses($classes)
            ->setIcon($icon);
        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT);
    }

    protected function getBackendUserFilter(): string
    {
        return (string)($this->getBackendUserAuthentication()->getModuleData('ExtensionManager')['filter'] ?? '');
    }

    protected function saveBackendUserFilter(string $filter): void
    {
        $this->getBackendUserAuthentication()->pushModuleData('ExtensionManager', ['filter' => $filter]);
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
