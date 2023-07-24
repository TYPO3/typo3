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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Controller for actions related to the TER download of an extension
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class DownloadController extends AbstractController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    /**
     * @var JsonView
     */
    protected $view;

    public function __construct(
        protected readonly ExtensionRepository $extensionRepository,
        protected readonly ExtensionManagementService $managementService,
        protected readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    /**
     * Check extension dependencies
     */
    public function checkDependenciesAction(Extension $extension): ResponseInterface
    {
        $message = '';
        $title = '';
        $hasDependencies = false;
        $hasErrors = false;
        $dependencyTypes = null;
        $configuration = [
            'value' => [
                'dependencies' => [],
            ],
        ];
        $isAutomaticInstallationEnabled = (bool)$this->extensionConfiguration->get('extensionmanager', 'automaticInstallation');
        if (!$isAutomaticInstallationEnabled) {
            // if automatic installation is deactivated, no dependency check is needed (download only)
            $action = 'installExtensionWithoutSystemDependencyCheck';
        } else {
            $action = 'installFromTer';
            try {
                $dependencyTypes = $this->managementService->getAndResolveDependencies($extension);
                if (!empty($dependencyTypes)) {
                    $hasDependencies = true;
                    $message = '<p>' . $this->translate('downloadExtension.dependencies.headline') . '</p>';
                    foreach ($dependencyTypes as $dependencyType => $dependencies) {
                        $extensions = '';
                        foreach ($dependencies as $extensionKey => $dependency) {
                            if (!isset($configuration['value']['dependencies'][$dependencyType])) {
                                $configuration['value']['dependencies'][$dependencyType] = [];
                            }
                            $configuration['value']['dependencies'][$dependencyType][$extensionKey] = [
                                '_exclude' => [
                                    'categoryIndexFromStringOrNumber',
                                ],
                            ];
                            $extensions .= $this->translate(
                                'downloadExtension.dependencies.extensionWithVersion',
                                [
                                    $extensionKey, $dependency->getVersion(),
                                ]
                            ) . '<br />';
                        }
                        $message .= $this->translate(
                            'downloadExtension.dependencies.typeHeadline',
                            [
                                $this->translate('downloadExtension.dependencyType.' . $dependencyType),
                                $extensions,
                            ]
                        );
                    }
                    $title = $this->translate('downloadExtension.dependencies.resolveAutomatically');
                }
            } catch (\Exception $e) {
                $hasErrors = true;
                $title = $this->translate('downloadExtension.dependencies.errorTitle');
                $message = $e->getMessage();
            }
        }

        $url = $this->uriBuilder->uriFor(
            $action,
            ['extension' => $extension->getUid(), 'format' => 'json'],
            'Download'
        );
        $this->view->setConfiguration($configuration);
        $this->view->assign('value', [
            'dependencies' => $dependencyTypes,
            'url' => $url,
            'message' => $message,
            'hasErrors' => $hasErrors,
            'hasDependencies' => $hasDependencies,
            'title' => $title,
        ]);

        return $this->jsonResponse();
    }

    /**
     * Defines which view object should be used for the installFromTer action
     */
    protected function initializeInstallFromTerAction()
    {
        // @todo: Switch to JsonView
        $this->defaultViewObjectName = TemplateView::class;
    }

    /**
     * Install an extension from TER action
     */
    public function installFromTerAction(Extension $extension, string $downloadPath = 'Local'): ResponseInterface
    {
        [$result, $errorMessages] = $this->installFromTer($extension, $downloadPath);
        $isAutomaticInstallationEnabled = (bool)$this->extensionConfiguration->get('extensionmanager', 'automaticInstallation');
        $this->view->assignMultiple([
            'result'  => $result,
            'extension' => $extension,
            'installationTypeLanguageKey' => $isAutomaticInstallationEnabled ? '' : '.downloadOnly',
            'unresolvedDependencies' => $errorMessages,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Check extension dependencies with special dependencies
     */
    public function installExtensionWithoutSystemDependencyCheckAction(Extension $extension): ResponseInterface
    {
        $this->managementService->setSkipDependencyCheck(true);
        return (new ForwardResponse('installFromTer'))->withArguments(['extension' => $extension, 'downloadPath' => 'Local']);
    }

    /**
     * Action for installing a distribution -
     * redirects directly to configuration after installing
     */
    public function installDistributionAction(Extension $extension): ResponseInterface
    {
        if (!ExtensionManagementUtility::isLoaded('impexp')) {
            return (new ForwardResponse('distributions'))->withControllerName('List');
        }
        [$result] = $this->installFromTer($extension);
        if (!$result) {
            return $this->redirect(
                'unresolvedDependencies',
                'List',
                null,
                [
                    'extensionKey' => $extension->getExtensionKey(),
                    'returnAction' => ['controller' => 'List', 'action' => 'distributions'],
                ]
            );
        }
        // FlashMessage that extension is installed
        $this->addFlashMessage(
            LocalizationUtility::translate(
                'distribution.welcome.message',
                'extensionmanager',
                [$extension->getExtensionKey()]
            ) ?? '',
            LocalizationUtility::translate('distribution.welcome.headline', 'extensionmanager') ?? ''
        );

        // Redirect to show action
        return $this->redirect(
            'show',
            'Distribution',
            null,
            ['extension' => $extension]
        );
    }

    /**
     * Update an extension. Makes no sanity check but directly searches highest
     * available version from TER and updates. Update check is done by the list
     * already. This method should only be called if we are sure that there is
     * an update.
     */
    protected function updateExtensionAction(): ResponseInterface
    {
        $extensionKey = $this->request->getArgument('extension');
        $version = $this->request->getArgument('version');
        $extension = $this->extensionRepository->findOneByExtensionKeyAndVersion($extensionKey, $version);
        if (!$extension instanceof Extension) {
            $extension = $this->extensionRepository->findHighestAvailableVersion($extensionKey);
        }
        $installedExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();
        try {
            if (in_array($extensionKey, $installedExtensions, true)) {
                // To resolve new dependencies the extension is installed again
                $this->managementService->installExtension($extension);
            } else {
                $this->managementService->downloadMainExtension($extension);
            }
            $this->addFlashMessage(
                $this->translate('extensionList.updateFlashMessage.body', [$extensionKey]),
                $this->translate('extensionList.updateFlashMessage.title')
            );
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', ContextualFeedbackSeverity::ERROR);
        }

        return $this->jsonResponse();
    }

    /**
     * Show update comments for extensions that can be updated.
     * Fetches update comments for all versions between the current
     * installed and the highest version.
     */
    protected function updateCommentForUpdatableVersionsAction(): ResponseInterface
    {
        $extensionKey = $this->request->getArgument('extension');
        $versionStart = $this->request->getArgument('integerVersionStart');
        $versionStop = $this->request->getArgument('integerVersionStop');
        $updateComments = [];
        /** @var Extension[] $updatableVersions */
        $updatableVersions = $this->extensionRepository->findByVersionRangeAndExtensionKeyOrderedByVersion(
            $extensionKey,
            $versionStart,
            $versionStop,
            false
        );
        $highestPossibleVersion = false;

        foreach ($updatableVersions as $updatableVersion) {
            if ($highestPossibleVersion === false) {
                $highestPossibleVersion = $updatableVersion->getVersion();
            }
            $updateComments[$updatableVersion->getVersion()] = $updatableVersion->getUpdateComment();
        }

        $this->view->assign('value', [
            'updateComments' => $updateComments,
            'url' => $this->uriBuilder->uriFor(
                'updateExtension',
                ['extension' => $extensionKey, 'version' => $highestPossibleVersion]
            ),
        ]);

        return $this->jsonResponse();
    }

    /**
     * Install an extension from TER
     * Downloads the extension, resolves dependencies and installs it
     *
     * @return array{
     *     0: array{
         *     downloaded?: array<string, Extension>,
         *     updated?: array<string, Extension>,
         *     installed?: array<string, string>,
         * }|false,
     *     1: array<string, array<int, array{code: int, message: string}>>,
     * }
     */
    protected function installFromTer(Extension $extension, string $downloadPath = 'Local'): array
    {
        $result = false;
        $errorMessages = [];
        try {
            $this->managementService->setDownloadPath($downloadPath);
            $isAutomaticInstallationEnabled = (bool)$this->extensionConfiguration->get('extensionmanager', 'automaticInstallation');
            $this->managementService->setAutomaticInstallationEnabled($isAutomaticInstallationEnabled);
            if (($result = $this->managementService->installExtension($extension)) === false) {
                $errorMessages = $this->managementService->getDependencyErrors();
            }
        } catch (ExtensionManagerException $e) {
            $errorMessages = [
                $extension->getExtensionKey() => [
                    [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                    ],
                ],
            ];
        }

        return [$result, $errorMessages];
    }
}
