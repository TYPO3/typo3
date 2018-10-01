<?php
namespace TYPO3\CMS\Extensionmanager\Report;

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

/**
 * Extension status reports
 * @internal This class is a specific EXT:reports implementation and is not part of the Public TYPO3 API.
 */
class ExtensionStatus implements \TYPO3\CMS\Reports\StatusProviderInterface
{
    /**
     * @var string
     */
    protected $ok = '';

    /**
     * @var string
     */
    protected $upToDate = '';

    /**
     * @var string
     */
    protected $error = '';

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
     */
    protected $repositoryRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
     */
    protected $listUtility;

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected $languageService;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->repositoryRepository = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository::class);
        $this->listUtility = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\ListUtility::class);
        $this->languageService = $this->objectManager->get(\TYPO3\CMS\Core\Localization\LanguageService::class);
        $this->languageService->includeLLFile('EXT:extensionmanager/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Determines extension manager status
     *
     * @return array List of statuses
     */
    public function getStatus()
    {
        $status = [];
        $status['mainRepositoryStatus'] = $this->getMainRepositoryStatus();

        $extensionStatus = $this->getSecurityStatusOfExtensions();
        $status['extensionsSecurityStatusInstalled'] = $extensionStatus->loaded ?? [];
        $status['extensionsSecurityStatusNotInstalled'] = $extensionStatus->existing ?? [];
        $status['extensionsOutdatedStatusInstalled'] = $extensionStatus->loadedoutdated ?? [];
        $status['extensionsOutdatedStatusNotInstalled'] = $extensionStatus->existingoutdated ?? [];

        return $status;
    }

    /**
     * Check main repository status: existence, has extensions, last update younger than 7 days
     *
     * @return \TYPO3\CMS\Reports\Status
     */
    protected function getMainRepositoryStatus()
    {
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Repository $mainRepository */
        $mainRepository = $this->repositoryRepository->findOneTypo3OrgRepository();

        if ($mainRepository === null) {
            $value = $this->languageService->getLL('report.status.mainRepository.notFound.value');
            $message = $this->languageService->getLL('report.status.mainRepository.notFound.message');
            $severity = \TYPO3\CMS\Reports\Status::ERROR;
        } elseif ($mainRepository->getLastUpdate()->getTimestamp() < $GLOBALS['EXEC_TIME'] - 24 * 60 * 60 * 7) {
            $value = $this->languageService->getLL('report.status.mainRepository.notUpToDate.value');
            $message = $this->languageService->getLL('report.status.mainRepository.notUpToDate.message');
            $severity = \TYPO3\CMS\Reports\Status::NOTICE;
        } else {
            $value = $this->languageService->getLL('report.status.mainRepository.upToDate.value');
            $message = '';
            $severity = \TYPO3\CMS\Reports\Status::OK;
        }

        /** @var \TYPO3\CMS\Reports\Status $status */
        $status = $this->objectManager->get(
            \TYPO3\CMS\Reports\Status::class,
            $this->languageService->getLL('report.status.mainRepository.title'),
            $value,
            $message,
            $severity
        );

        return $status;
    }

    /**
     * Get security status of loaded and installed extensions
     *
     * @return \stdClass with properties 'loaded' and 'existing' containing a TYPO3\CMS\Reports\Report\Status\Status object
     */
    protected function getSecurityStatusOfExtensions()
    {
        $extensionInformation = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $loadedInsecure = [];
        $existingInsecure = [];
        $loadedOutdated = [];
        $existingOutdated = [];
        foreach ($extensionInformation as $extensionKey => $information) {
            if (
                array_key_exists('terObject', $information)
                && $information['terObject'] instanceof \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
            ) {
                /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $terObject */
                $terObject = $information['terObject'];
                $insecureStatus = $terObject->getReviewState();
                if ($insecureStatus === -1) {
                    if (
                        array_key_exists('installed', $information)
                        && $information['installed'] === true
                    ) {
                        $loadedInsecure[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    } else {
                        $existingInsecure[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    }
                } elseif ($insecureStatus === -2) {
                    if (
                        array_key_exists('installed', $information)
                        && $information['installed'] === true
                    ) {
                        $loadedOutdated[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    } else {
                        $existingOutdated[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    }
                }
            }
        }

        $result = new \stdClass();

        if (empty($loadedInsecure)) {
            $value = $this->languageService->getLL('report.status.loadedExtensions.noInsecureExtensionLoaded.value');
            $message = '';
            $severity = \TYPO3\CMS\Reports\Status::OK;
        } else {
            $value = sprintf(
                $this->languageService->getLL('report.status.loadedExtensions.insecureExtensionLoaded.value'),
                count($loadedInsecure)
            );
            $extensionList = [];
            foreach ($loadedInsecure as $insecureExtension) {
                $extensionList[] = sprintf(
                    $this->languageService->getLL('report.status.loadedExtensions.insecureExtensionLoaded.message.extension'),
                    $insecureExtension['extensionKey'],
                    $insecureExtension['version']
                );
            }
            $message = sprintf(
                $this->languageService->getLL('report.status.loadedExtensions.insecureExtensionLoaded.message'),
                implode('', $extensionList)
            );
            $severity = \TYPO3\CMS\Reports\Status::ERROR;
        }
        $result->loaded = $this->objectManager->get(
            \TYPO3\CMS\Reports\Status::class,
            $this->languageService->getLL('report.status.loadedExtensions.title'),
            $value,
            $message,
            $severity
        );

        if (empty($existingInsecure)) {
            $value = $this->languageService->getLL('report.status.existingExtensions.noInsecureExtensionExists.value');
            $message = '';
            $severity = \TYPO3\CMS\Reports\Status::OK;
        } else {
            $value = sprintf(
                $this->languageService->getLL('report.status.existingExtensions.insecureExtensionExists.value'),
                count($existingInsecure)
            );
            $extensionList = [];
            foreach ($existingInsecure as $insecureExtension) {
                $extensionList[] = sprintf(
                    $this->languageService->getLL('report.status.existingExtensions.insecureExtensionExists.message.extension'),
                    $insecureExtension['extensionKey'],
                    $insecureExtension['version']
                );
            }
            $message = sprintf(
                $this->languageService->getLL('report.status.existingExtensions.insecureExtensionExists.message'),
                implode('', $extensionList)
            );
            $severity = \TYPO3\CMS\Reports\Status::WARNING;
        }
        $result->existing = $this->objectManager->get(
            \TYPO3\CMS\Reports\Status::class,
            $this->languageService->getLL('report.status.existingExtensions.title'),
            $value,
            $message,
            $severity
        );

        if (empty($loadedOutdated)) {
            $value = $this->languageService->getLL('report.status.loadedOutdatedExtensions.noOutdatedExtensionLoaded.value');
            $message = '';
            $severity = \TYPO3\CMS\Reports\Status::OK;
        } else {
            $value = sprintf(
                $this->languageService->getLL('report.status.loadedOutdatedExtensions.outdatedExtensionLoaded.value'),
                count($loadedOutdated)
            );
            $extensionList = [];
            foreach ($loadedOutdated as $outdatedExtension) {
                $extensionList[] = sprintf(
                    $this->languageService->getLL('report.status.loadedOutdatedExtensions.outdatedExtensionLoaded.message.extension'),
                    $outdatedExtension['extensionKey'],
                    $outdatedExtension['version']
                );
            }
            $message = sprintf(
                $this->languageService->getLL('report.status.loadedOutdatedExtensions.outdatedExtensionLoaded.message'),
                implode('', $extensionList)
            );
            $severity = \TYPO3\CMS\Reports\Status::WARNING;
        }
        $result->loadedoutdated = $this->objectManager->get(
            \TYPO3\CMS\Reports\Status::class,
            $this->languageService->getLL('report.status.loadedOutdatedExtensions.title'),
            $value,
            $message,
            $severity
        );

        if (empty($existingOutdated)) {
            $value = $this->languageService->getLL('report.status.existingOutdatedExtensions.noOutdatedExtensionExists.value');
            $message = '';
            $severity = \TYPO3\CMS\Reports\Status::OK;
        } else {
            $value = sprintf(
                $this->languageService->getLL('report.status.existingOutdatedExtensions.outdatedExtensionExists.value'),
                count($existingOutdated)
            );
            $extensionList = [];
            foreach ($existingOutdated as $outdatedExtension) {
                $extensionList[] = sprintf(
                    $this->languageService->getLL('report.status.existingOutdatedExtensions.outdatedExtensionExists.message.extension'),
                    $outdatedExtension['extensionKey'],
                    $outdatedExtension['version']
                );
            }
            $message = sprintf(
                $this->languageService->getLL('report.status.existingOutdatedExtensions.outdatedExtensionExists.message'),
                implode('', $extensionList)
            );
            $severity = \TYPO3\CMS\Reports\Status::WARNING;
        }
        $result->existingoutdated = $this->objectManager->get(
            \TYPO3\CMS\Reports\Status::class,
            $this->languageService->getLL('report.status.existingOutdatedExtensions.title'),
            $value,
            $message,
            $severity
        );

        return $result;
    }
}
