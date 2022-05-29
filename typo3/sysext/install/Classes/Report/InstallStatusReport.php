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

namespace TYPO3\CMS\Install\Report;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\CoreVersionService;
use TYPO3\CMS\Install\Service\Exception\RemoteFetchException;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Provides an installation status report.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class InstallStatusReport implements StatusProviderInterface
{
    protected const WRAP_FLAT = 1;
    protected const WRAP_NESTED = 2;

    protected bool $useMarkup;

    public function __construct(bool $useMarkup = true)
    {
        $this->useMarkup = $useMarkup;
    }

    /**
     * Compiles a collection of system status checks as a status report.
     *
     * @return Status[]
     */
    public function getStatus()
    {
        return [
            'FileSystem' => $this->getFileSystemStatus(),
            'RemainingUpdates' => $this->getRemainingUpdatesStatus(),
            'NewVersion' => $this->getNewVersionStatus(),
        ];
    }

    /**
     * Checks for several directories being writable.
     *
     * @return Status Indicates status of the file system
     */
    protected function getFileSystemStatus()
    {
        $languageService = $this->getLanguageService();
        $value = $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_writable');
        $message = '';
        $severity = Status::OK;
        // Requirement level
        // -1 = not required, but if it exists may be writable or not
        //  0 = not required, if it exists the dir should be writable
        //  1 = required, doesn't have to be writable
        //  2 = required, has to be writable
        $varPath = Environment::getVarPath();
        $sitePath = Environment::getPublicPath();
        $rootPath = Environment::getProjectPath();
        $checkWritable = [
            $sitePath . '/typo3temp/' => 2,
            $sitePath . '/typo3temp/assets/' => 2,
            $sitePath . '/typo3temp/assets/compressed/' => 2,
            // only needed when GraphicalFunctions is used
            $sitePath . '/typo3temp/assets/images/' => 0,
            // used in PageGenerator (inlineStyle2Temp) and Backend + Language JS files
            $sitePath . '/typo3temp/assets/css/' => 2,
            $sitePath . '/typo3temp/assets/js/' => 2,
            // fallback storage of FAL
            $sitePath . '/typo3temp/assets/_processed_/' => 0,
            $varPath => 2,
            $varPath . '/transient/' => 2,
            $varPath . '/charset/' => 2,
            $varPath . '/lock/' => 2,
            $sitePath . '/typo3conf/' => 2,
            Environment::getLabelsPath() => 0,
            $sitePath . '/' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] => -1,
            $sitePath . '/' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . '_temp_/' => 0,
        ];

        // Check for writable extension folder files in non-composer mode only
        if (!Environment::isComposerMode()) {
            $checkWritable[Environment::getExtensionsPath()] = 0;
            if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall']) {
                $checkWritable[Environment::getBackendPath() . '/ext/'] = -1;
            }
        }

        foreach ($checkWritable as $path => $requirementLevel) {
            $relPath = substr($path, strlen($rootPath) + 1);
            if (!@is_dir($path)) {
                // If the directory is missing, try to create it
                GeneralUtility::mkdir($path);
            }
            if (!@is_dir($path)) {
                if ($requirementLevel > 0) {
                    // directory is required
                    $value = $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_missingDirectory');
                    $message .= sprintf($languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_directoryDoesNotExistCouldNotCreate'), $relPath) . '<br />';
                    $severity = Status::ERROR;
                } else {
                    $message .= sprintf($languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_directoryDoesNotExist'), $relPath);
                    if ($requirementLevel == 0) {
                        $message .= ' ' . $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_directoryShouldAlsoBeWritable');
                    }
                    $message .= '<br />';
                    if ($severity < Status::WARNING) {
                        $value = $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_nonExistingDirectory');
                        $severity = Status::WARNING;
                    }
                }
            } else {
                if (!is_writable($path)) {
                    switch ($requirementLevel) {
                        case 0:
                            $message .= sprintf(
                                $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_directoryShouldBeWritable'),
                                $path
                            ) . '<br />';
                            if ($severity < Status::WARNING) {
                                $value = $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_recommendedWritableDirectory');
                                $severity = Status::WARNING;
                            }
                            break;
                        case 2:
                            $value = $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_requiredWritableDirectory');
                            $message .= sprintf(
                                $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_directoryMustBeWritable'),
                                $path
                            ) . '<br />';
                            $severity = Status::ERROR;
                            break;
                        default:
                    }
                }
            }
        }
        return GeneralUtility::makeInstance(Status::class, $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_fileSystem'), $value, $message, $severity);
    }

    /**
     * Returns all incomplete update wizards.
     *
     * Fetches all wizards that are not marked "done" in the registry and filters out
     * the ones that should not be rendered (= no upgrade required).
     *
     * @return array
     */
    protected function getIncompleteWizards(): array
    {
        $upgradeWizardsService = GeneralUtility::makeInstance(UpgradeWizardsService::class);
        $incompleteWizards = $upgradeWizardsService->getUpgradeWizardsList();
        $incompleteWizards = array_filter(
            $incompleteWizards,
            static function ($wizard) {
                return $wizard['shouldRenderWizard'];
            }
        );
        return $incompleteWizards;
    }

    /**
     * Checks if there are still updates to perform
     *
     * @return Status Represents whether the installation is completely updated yet
     */
    protected function getRemainingUpdatesStatus()
    {
        $languageService = $this->getLanguageService();
        $value = $languageService->getLL('status_updateComplete');
        $message = '';
        $severity = Status::OK;
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // check if there are update wizards left to perform
        $incompleteWizards = $this->getIncompleteWizards();
        if (count($incompleteWizards)) {
            // At least one incomplete wizard was found
            $value = $languageService->getLL('status_updateIncomplete');
            $severity = Status::WARNING;
            $url = (string)$uriBuilder->buildUriFromRoute('tools_toolsupgrade');
            $message = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.install_update'), '<a href="' . htmlspecialchars($url) . '">', '</a>');
        }

        return GeneralUtility::makeInstance(Status::class, $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_remainingUpdates'), $value, $message, $severity);
    }

    /**
     * Checks if there is a new minor TYPO3 version to update to.
     *
     * @return Status Represents whether there is a new version available online
     */
    protected function getNewVersionStatus()
    {
        $typoVersion = GeneralUtility::makeInstance(Typo3Version::class);
        $languageService = $this->getLanguageService();
        $coreVersionService = GeneralUtility::makeInstance(CoreVersionService::class);

        // No updates for development versions
        if (!$coreVersionService->isInstalledVersionAReleasedVersion()) {
            return GeneralUtility::makeInstance(Status::class, 'TYPO3', $typoVersion->getVersion(), $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_isDevelopmentVersion'), Status::NOTICE);
        }

        try {
            $versionMaintenanceWindow = $coreVersionService->getMaintenanceWindow();
        } catch (RemoteFetchException $remoteFetchException) {
            return GeneralUtility::makeInstance(
                Status::class,
                'TYPO3',
                $typoVersion->getVersion(),
                $languageService->sL(
                    'LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_remoteFetchException'
                ),
                Status::NOTICE
            );
        }

        if (!$versionMaintenanceWindow->isSupportedByCommunity() && !$versionMaintenanceWindow->isSupportedByElts()) {
            // Version is not maintained
            $message = $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_versionOutdated');
            $status = Status::ERROR;
        } else {
            $message = '';
            $status = Status::OK;

            // There is an update available
            $availableReleases = [];
            $latestRelease = $coreVersionService->getYoungestPatchRelease();
            $isCurrentVersionElts = $coreVersionService->isCurrentInstalledVersionElts();

            if ($coreVersionService->isPatchReleaseSuitableForUpdate($latestRelease)) {
                $availableReleases[] = $latestRelease;
            }

            if (!$versionMaintenanceWindow->isSupportedByCommunity()) {
                if ($latestRelease->isElts()) {
                    $latestCommunityDrivenRelease = $coreVersionService->getYoungestCommunityPatchRelease();
                    if ($coreVersionService->isPatchReleaseSuitableForUpdate($latestCommunityDrivenRelease)) {
                        $availableReleases[] = $latestCommunityDrivenRelease;
                    }
                } elseif (!$isCurrentVersionElts) {
                    // Inform user about ELTS being available soon if:
                    // - regular support ran out
                    // - the current installed version is no ELTS
                    // - no ELTS update was released, yet
                    $message = sprintf(
                        $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_elts_information'),
                        TYPO3_version,
                        '<a href="https://typo3.com/elts" target="_blank" rel="noopener">https://typo3.com/elts</a>'
                    );
                    $status = Status::WARNING;
                }
            }

            if ($availableReleases !== []) {
                $messages = [];
                $status = Status::WARNING;
                foreach ($availableReleases as $availableRelease) {
                    $versionString = $availableRelease->getVersion();
                    if ($availableRelease->isElts()) {
                        $versionString .= ' ELTS';
                    }
                    if ($coreVersionService->isUpdateSecurityRelevant($availableRelease)) {
                        $status = Status::ERROR;
                        $updateMessage = sprintf($languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_newVersionSecurityRelevant'), $versionString);
                    } else {
                        $updateMessage = sprintf($languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_newVersion'), $versionString);
                    }

                    if ($availableRelease->isElts()) {
                        if ($isCurrentVersionElts) {
                            $updateMessage .= ' ' . sprintf(
                                $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_elts_download'),
                                '<a href="https://my.typo3.org" target="_blank" rel="noopener">my.typo3.org</a>'
                            );
                        } else {
                            $updateMessage .= ' ' . sprintf(
                                $languageService->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_elts_subscribe'),
                                $coreVersionService->getInstalledVersion(),
                                '<a href="https://typo3.com/elts" target="_blank" rel="noopener">https://typo3.com/elts</a>'
                            );
                        }
                    }
                    $messages[] = $updateMessage;
                }
                $message = $this->wrapList($messages, count($messages) > 1 ? self::WRAP_NESTED : self::WRAP_FLAT);
            }
        }

        return GeneralUtility::makeInstance(Status::class, 'TYPO3', $typoVersion->getVersion(), $message, $status);
    }

    protected function wrapList(array $items, int $style): string
    {
        if (!$this->useMarkup) {
            return implode(', ', $items);
        }
        if ($style === self::WRAP_NESTED) {
            return sprintf(
                '<ul>%s</ul>',
                implode('', $this->wrapItems($items, '<li>', '</li>'))
            );
        }
        return sprintf(
            '<p>%s</p>',
            implode('', $this->wrapItems($items, '<br>', ''))
        );
    }

    protected function wrapItems(array $items, string $before, string $after): array
    {
        return array_map(
            static function (string $item) use ($before, $after): string {
                return $before . $item . $after;
            },
            array_filter($items)
        );
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
