<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility;
use TYPO3\CMS\Extensionmanager\Utility\Repository\Helper;

/**
 * Controller for extension listings (TER or local extensions)
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ListController extends AbstractModuleController
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
     */
    protected $listUtility;

    /**
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility
     */
    protected $dependencyUtility;

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
     */
    public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    /**
     * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
     */
    public function injectPageRenderer(\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer)
    {
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility
     */
    public function injectDependencyUtility(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility)
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
                ),
                LocalizationUtility::translate(
                    'composerMode.title',
                    'extensionmanager'
                ),
                FlashMessage::INFO
            );
        }
    }

    /**
     * Shows list of extensions present in the system
     */
    public function indexAction()
    {
        $this->addComposerModeNotification();
        $availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        ksort($availableAndInstalledExtensions);
        $this->view->assignMultiple(
            [
                'extensions' => $availableAndInstalledExtensions,
                'isComposerMode' => Environment::isComposerMode(),
            ]
        );
        $this->handleTriggerArguments();
    }

    /**
     * Shows a list of unresolved dependency errors with the possibility to bypass the dependency check
     *
     * @param string $extensionKey
     * @throws ExtensionManagerException
     */
    public function unresolvedDependenciesAction($extensionKey)
    {
        $availableExtensions = $this->listUtility->getAvailableExtensions();
        if (isset($availableExtensions[$extensionKey])) {
            $extensionArray = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation(
                [
                    $extensionKey => $availableExtensions[$extensionKey]
                ]
            );
            /** @var ExtensionModelUtility $extensionModelUtility */
            $extensionModelUtility = $this->objectManager->get(ExtensionModelUtility::class);
            $extension = $extensionModelUtility->mapExtensionArrayToModel($extensionArray[$extensionKey]);
        } else {
            throw new ExtensionManagerException('Extension ' . $extensionKey . ' is not available', 1402421007);
        }
        $this->dependencyUtility->checkDependencies($extension);
        $this->view->assign('extension', $extension);
        $this->view->assign('unresolvedDependencies', $this->dependencyUtility->getDependencyErrors());
    }

    /**
     * Shows extensions from TER
     * Either all extensions or depending on a search param
     *
     * @param string $search
     */
    public function terAction($search = '')
    {
        $this->addComposerModeNotification();
        $search = trim($search);
        if (!empty($search)) {
            $extensions = $this->extensionRepository->findByTitleOrAuthorNameOrExtensionKey($search);
        } else {
            $extensions = $this->extensionRepository->findAll();
        }
        $availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensions($this->listUtility->getAvailableExtensions());
        $this->view->assign('extensions', $extensions)
                ->assign('search', $search)
                ->assign('availableAndInstalled', $availableAndInstalledExtensions);
    }

    /**
     * Action for listing all possible distributions
     *
     * @param bool $showUnsuitableDistributions
     */
    public function distributionsAction($showUnsuitableDistributions = false)
    {
        $this->addComposerModeNotification();
        $importExportInstalled = ExtensionManagementUtility::isLoaded('impexp');
        if ($importExportInstalled) {
            try {
                /** @var Helper $repositoryHelper */
                $repositoryHelper = $this->objectManager->get(Helper::class);
                // Check if a TER update has been done at all, if not, fetch it directly
                // Repository needs an update, but not because of the extension hash has changed
                $isExtListUpdateNecessary = $repositoryHelper->isExtListUpdateNecessary();
                if ($isExtListUpdateNecessary > 0 && ($isExtListUpdateNecessary & $repositoryHelper::PROBLEM_EXTENSION_HASH_CHANGED) === 0) {
                    $repositoryHelper->updateExtList();
                }
            } catch (ExtensionManagerException $e) {
                $this->addFlashMessage($e->getMessage(), $e->getCode(), FlashMessage::ERROR);
            }

            $officialDistributions = $this->extensionRepository->findAllOfficialDistributions();
            $communityDistributions = $this->extensionRepository->findAllCommunityDistributions();

            $officialDistributions = $this->dependencyUtility->filterYoungestVersionOfExtensionList($officialDistributions->toArray(), $showUnsuitableDistributions);
            $communityDistributions = $this->dependencyUtility->filterYoungestVersionOfExtensionList($communityDistributions->toArray(), $showUnsuitableDistributions);

            $this->view->assign('officialDistributions', $officialDistributions);
            $this->view->assign('communityDistributions', $communityDistributions);
        }
        $this->view->assign('enableDistributionsView', $importExportInstalled);
        $this->view->assign('showUnsuitableDistributions', $showUnsuitableDistributions);
    }

    /**
     * Shows all versions of a specific extension
     *
     * @param string $extensionKey
     */
    public function showAllVersionsAction($extensionKey)
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
        $uriBuilder = $this->controllerContext->getUriBuilder();

        if ($this->actionMethodName === 'showAllVersionsAction') {
            $uri = $uriBuilder->reset()->uriFor('ter', [], 'List');
            $title = $this->translate('extConfTemplate.backToList');
            $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL);
            $classes = '';
        } else {
            $uri = $uriBuilder->reset()->uriFor('form', [], 'UploadExtensionFile');
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
}
