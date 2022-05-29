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

namespace TYPO3\CMS\Extensionmanager\Report;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Extension status reports
 * @internal This class is a specific EXT:reports implementation and is not part of the Public TYPO3 API.
 */
class ExtensionStatus implements StatusProviderInterface
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
     * @var RemoteRegistry
     */
    protected $remoteRegistry;

    /**
     * @var ListUtility
     */
    protected $listUtility;

    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @param RemoteRegistry|null  $remoteRegistry
     */
    public function __construct(RemoteRegistry $remoteRegistry = null)
    {
        $this->remoteRegistry = $remoteRegistry ?? GeneralUtility::makeInstance(RemoteRegistry::class);
        $this->listUtility = GeneralUtility::makeInstance(ListUtility::class);

        $this->languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
        $this->languageService->includeLLFile('EXT:extensionmanager/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Determines extension manager status
     *
     * @return Status[] List of statuses
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
     * @return Status
     */
    protected function getMainRepositoryStatus()
    {
        if (!$this->remoteRegistry->hasDefaultRemote()) {
            $value = $this->languageService->getLL('report.status.mainRepository.notFound.value');
            $message = $this->languageService->getLL('report.status.mainRepository.notFound.message');
            $severity = Status::ERROR;
        } elseif ($this->remoteRegistry->getDefaultRemote()->needsUpdate()) {
            $value = $this->languageService->getLL('report.status.mainRepository.notUpToDate.value');
            $message = $this->languageService->getLL('report.status.mainRepository.notUpToDate.message');
            $severity = Status::NOTICE;
        } else {
            $value = $this->languageService->getLL('report.status.mainRepository.upToDate.value');
            $message = '';
            $severity = Status::OK;
        }

        $status = GeneralUtility::makeInstance(
            Status::class,
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
                && $information['terObject'] instanceof Extension
            ) {
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
            $severity = Status::OK;
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
            $severity = Status::ERROR;
        }
        $result->loaded = GeneralUtility::makeInstance(
            Status::class,
            $this->languageService->getLL('report.status.loadedExtensions.title'),
            $value,
            $message,
            $severity
        );

        if (empty($existingInsecure)) {
            $value = $this->languageService->getLL('report.status.existingExtensions.noInsecureExtensionExists.value');
            $message = '';
            $severity = Status::OK;
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
            $severity = Status::WARNING;
        }
        $result->existing = GeneralUtility::makeInstance(
            Status::class,
            $this->languageService->getLL('report.status.existingExtensions.title'),
            $value,
            $message,
            $severity
        );

        if (empty($loadedOutdated)) {
            $value = $this->languageService->getLL('report.status.loadedOutdatedExtensions.noOutdatedExtensionLoaded.value');
            $message = '';
            $severity = Status::OK;
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
            $severity = Status::WARNING;
        }
        $result->loadedoutdated = GeneralUtility::makeInstance(
            Status::class,
            $this->languageService->getLL('report.status.loadedOutdatedExtensions.title'),
            $value,
            $message,
            $severity
        );

        if (empty($existingOutdated)) {
            $value = $this->languageService->getLL('report.status.existingOutdatedExtensions.noOutdatedExtensionExists.value');
            $message = '';
            $severity = Status::OK;
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
            $severity = Status::WARNING;
        }
        $result->existingoutdated = GeneralUtility::makeInstance(
            Status::class,
            $this->languageService->getLL('report.status.existingOutdatedExtensions.title'),
            $value,
            $message,
            $severity
        );

        return $result;
    }
}
