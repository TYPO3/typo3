<?php

declare(strict_types=1);

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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\AllowedMethodsTrait;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Controller for extension listings: local extensions, TER, various detail views.
 * indexAction() is the default list overview Main module -> Extensions.
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ListController extends AbstractController
{
    use AllowedMethodsTrait;

    public function __construct(
        protected readonly PageRenderer $pageRenderer,
        protected readonly ExtensionRepository $extensionRepository,
        protected readonly ListUtility $listUtility,
        protected readonly DependencyUtility $dependencyUtility,
        protected readonly IconFactory $iconFactory,
        protected readonly RemoteRegistry $remoteRegistry,
        protected readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    /**
     * Add general things needed for all actions.
     */
    protected function initializeAction(): void
    {
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:extensionmanager/Resources/Private/Language/locallang.xlf');
        $this->settings['offlineMode'] = (bool)$this->extensionConfiguration->get('extensionmanager', 'offlineMode');
    }

    /**
     * List extensions present in the system.
     */
    protected function indexAction(): ResponseInterface
    {
        $moduleData = $this->request->getAttribute('moduleData');
        if ($this->request->hasArgument('filter')
            && is_string($this->request->getArgument('filter'))
        ) {
            $filter = $this->request->getArgument('filter');
            $moduleData->set('filter', $filter);
            $this->getBackendUserAuthentication()->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        } else {
            $filter = (string)$moduleData->get('filter');
        }
        $this->addComposerModeNotification();
        $isComposerMode = Environment::isComposerMode();
        $availableAndInstalledExtensions = $this->enrichExtensionsWithViewInformation(
            $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation($filter),
            $isComposerMode
        );
        ksort($availableAndInstalledExtensions);
        $view = $this->initializeModuleTemplate($this->request);
        $view = $this->registerDocHeaderButtons($view);
        $view->assignMultiple([
            'extensions' => $availableAndInstalledExtensions,
            'isComposerMode' => $isComposerMode,
            'typeFilter' => $filter ?: 'All',
            // Sort extension by update state. This is only automatically set for non-composer
            // mode and only takes effect if at least one extension can be updated.
            'sortByUpdate' => $this->extensionsWithUpdate($availableAndInstalledExtensions) !== [] && !$isComposerMode,
        ]);
        $this->handleTriggerArguments($view);
        return $view->renderResponse('List/Index');
    }

    /**
     * List unresolved dependency errors with the possibility to bypass the dependency check.
     */
    protected function unresolvedDependenciesAction(string $extensionKey, array $returnAction): ResponseInterface
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');

        $availableExtensions = $this->listUtility->getAvailableExtensions();
        if (isset($availableExtensions[$extensionKey])) {
            $extensionArray = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation(
                [
                    $extensionKey => $availableExtensions[$extensionKey],
                ]
            );
            $extension = Extension::createFromExtensionArray($extensionArray[$extensionKey]);
        } else {
            throw new ExtensionManagerException('Extension ' . $extensionKey . ' is not available', 1402421007);
        }
        $this->dependencyUtility->checkDependencies($extension);
        $view = $this->initializeModuleTemplate($this->request);
        $view->assignMultiple([
            'extension' => $extension,
            'returnAction' => $returnAction,
            'unresolvedDependencies' => $this->dependencyUtility->getDependencyErrors(),
        ]);
        return $view->renderResponse('List/UnresolvedDependencies');
    }

    /**
     * Show extensions from TER, either all extensions or depending on a search param.
     */
    protected function terAction(string $search = '', int $currentPage = 1): ResponseInterface
    {
        $this->addComposerModeNotification();
        $search = trim($search);
        if (!empty($search)) {
            $extensions = $this->extensionRepository->findByTitleOrAuthorNameOrExtensionKey($search);
            $paginator = new ArrayPaginator($extensions, $currentPage);
            $tableId = 'terSearchTable';
        } else {
            $extensions = $this->extensionRepository->findAll();

            $paginator = new ArrayPaginator($extensions, $currentPage);
            $tableId = 'terTable';
        }
        $pagination = new SimplePagination($paginator);
        $availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensions($this->listUtility->getAvailableExtensions());
        $view = $this->initializeModuleTemplate($this->request);
        $view = $this->registerDocHeaderButtons($view);
        $view->assignMultiple([
            'extensions' => $extensions,
            'paginator' => $paginator,
            'pagination' => $pagination,
            'search' => $search,
            'availableAndInstalled' => $availableAndInstalledExtensions,
            'actionName' => 'ter',
            'tableId' => $tableId,
        ]);
        return $view->renderResponse('List/Ter');
    }

    /**
     * List available distributions.
     */
    protected function distributionsAction(bool $showUnsuitableDistributions = false): ResponseInterface
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/extensionmanager/distribution-image.js');
        $this->addComposerModeNotification();
        $importExportInstalled = ExtensionManagementUtility::isLoaded('impexp');
        $view = $this->initializeModuleTemplate($this->request);
        if ($importExportInstalled) {
            try {
                foreach ($this->remoteRegistry->getListableRemotes() as $remote) {
                    $remote->getAvailablePackages();
                }
            } catch (ExtensionManagerException $e) {
                $this->addFlashMessage($e->getMessage(), (string)$e->getCode(), ContextualFeedbackSeverity::ERROR);
            }
            $officialDistributions = $this->extensionRepository->findAllOfficialDistributions($showUnsuitableDistributions);
            $communityDistributions = $this->extensionRepository->findAllCommunityDistributions($showUnsuitableDistributions);
            $view->assign('officialDistributions', $officialDistributions);
            $view->assign('communityDistributions', $communityDistributions);
        }
        $view->assign('enableDistributionsView', $importExportInstalled);
        $view->assign('showUnsuitableDistributions', $showUnsuitableDistributions);
        return $view->renderResponse('List/Distributions');
    }

    /**
     * Show all versions of a specific extension.
     */
    protected function showAllVersionsAction(string $extensionKey): ResponseInterface
    {
        $currentVersion = $this->extensionRepository->findOneByCurrentVersionByExtensionKey($extensionKey);
        $extensions = $this->extensionRepository->findByExtensionKeyOrderedByVersion($extensionKey);
        $view = $this->initializeModuleTemplate($this->request);
        $view = $this->registerDocHeaderButtons($view);
        $view->assignMultiple([
            'extensionKey' => $extensionKey,
            'currentVersion' => $currentVersion,
            'extensions' => $extensions,
        ]);
        return $view->renderResponse('List/ShowAllVersions');
    }

    /**
     * Registers doc-header icons and drop-down.
     */
    protected function registerDocHeaderButtons(ModuleTemplate $view): ModuleTemplate
    {
        if (Environment::isComposerMode()) {
            return $view;
        }
        if ($this->actionMethodName === 'showAllVersionsAction') {
            $action = $this->request->hasArgument('returnTo') ? $this->request->getArgument('returnTo') : 'ter';
            $uri = $this->uriBuilder->reset()->uriFor(in_array($action, ['index', 'ter'], true) ? $action : 'ter', [], 'List');
            $title = $this->translate('extConfTemplate.backToList');
            $icon = $this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL);
            $classes = '';
        } else {
            $uri = $this->uriBuilder->reset()->uriFor('form', [], 'UploadExtensionFile');
            $title = $this->translate('extensionList.uploadExtension');
            $icon = $this->iconFactory->getIcon('actions-edit-upload', IconSize::SMALL);
            $classes = 't3js-upload';
        }
        $button = $this->componentFactory->createLinkButton()
            ->setHref($uri)
            ->setTitle($title)
            ->setShowLabelText(true)
            ->setClasses($classes)
            ->setIcon($icon);
        $view->addButtonToButtonBar($button);
        return $view;
    }

    /**
     * Add an information about composer mode.
     */
    protected function addComposerModeNotification(): void
    {
        if (Environment::isComposerMode()) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'composerStrictMode.message',
                    'extensionmanager'
                ) ?? '',
                LocalizationUtility::translate(
                    'composerMode.title',
                    'extensionmanager'
                ) ?? '',
                ContextualFeedbackSeverity::INFO
            );
        }
    }

    protected function enrichExtensionsWithViewInformation(array $availableAndInstalledExtensions, bool $isComposerMode): array
    {
        $isOfflineMode = (bool)($this->settings['offlineMode'] ?? false);
        foreach ($availableAndInstalledExtensions as &$extension) {
            $extension['updateIsBlocked'] = $isComposerMode || $isOfflineMode || ($extension['state'] ?? '') === 'excludeFromUpdates';
            $extension['sortUpdate'] = 2;
            if ($extension['updateAvailable'] ?? false) {
                $extension['sortUpdate'] = (int)$extension['updateIsBlocked'];
            }
        }
        return $availableAndInstalledExtensions;
    }

    protected function extensionsWithUpdate(array $availableAndInstalledExtensions): array
    {
        return array_filter($availableAndInstalledExtensions, static function ($extension) {
            return $extension['updateAvailable'] ?? false;
        });
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
