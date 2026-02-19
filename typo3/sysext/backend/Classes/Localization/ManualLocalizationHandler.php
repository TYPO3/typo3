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

namespace TYPO3\CMS\Backend\Localization;

use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Backend\Localization\Finisher\NoopLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\Finisher\RedirectLocalizationFinisher;
use TYPO3\CMS\Backend\Localization\Finisher\ReloadLocalizationFinisher;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Manual localization handler
 *
 * Handles manual localization through the wizard interface, supporting both
 * copy and translate operations based on the selected mode
 *
 * @internal
 */
class ManualLocalizationHandler implements LocalizationHandlerInterface
{
    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly LocalizationRepository $localizationRepository,
    ) {}

    public function getIdentifier(): string
    {
        return 'manual';
    }

    public function getLabel(): string
    {
        return 'backend.wizards.localization:handler.manual.label';
    }

    public function getDescription(): string
    {
        return 'backend.wizards.localization:handler.manual.description';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-translate';
    }

    public function isAvailable(LocalizationInstructions $instructions): bool
    {
        // Manual localization handler is always available
        // It supports all modes, record types, and language combinations
        return true;
    }

    public function processLocalization(LocalizationInstructions $instructions): LocalizationResult
    {
        // Handle pages with optional content selection
        if ($instructions->mainRecordType === 'pages') {
            return $this->processPageLocalization($instructions->mode, $instructions->recordUid, $instructions->targetLanguageId, $instructions->additionalData);
        }

        // Handle single record localization for other record types
        return $this->processSingleRecordLocalization($instructions->mode, $instructions->mainRecordType, $instructions->recordUid, $instructions->targetLanguageId);
    }

    /**
     * Process single record localization (non-page records)
     */
    protected function processSingleRecordLocalization(
        LocalizationMode $mode,
        string $type,
        int $uid,
        int $targetLanguage
    ): LocalizationResult {
        // Validate that the record exists
        $record = BackendUtility::getRecord($type, $uid);
        if (!$record) {
            return LocalizationResult::error(
                errors: [
                    sprintf(
                        $this->getLanguageService()->sL('backend.wizards.localization:error.recordNotFound'),
                        $uid,
                        $type
                    ),
                ]
            );
        }

        // Check if translation already exists
        $existingTranslation = $this->localizationRepository->getRecordTranslation($type, $uid, $targetLanguage);
        if ($existingTranslation !== null) {
            // Translation already exists, return success with no-op finisher
            return LocalizationResult::success(
                finisher: (new NoopLocalizationFinisher())
            );
        }

        $cmd = [
            $type => [
                $uid => [
                    $mode->getDataHandlerCommand() => $targetLanguage,
                ],
            ],
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        if ($dataHandler->errorLog !== []) {
            return LocalizationResult::error(errors: $dataHandler->errorLog);
        }

        // Get the newly created record UID from DataHandler's copy mapping
        $newUid = $dataHandler->copyMappingArray_merged[$type][$uid] ?? null;

        // If no UID was found in copy mapping, try to find the translated record
        if ($newUid === null) {
            $translation = $this->localizationRepository->getRecordTranslation($type, $uid, $targetLanguage);
            $newUid = $translation?->getUid();
        }

        // Generate redirect finisher or use reload finisher as fallback
        $redirectUrl = $newUid !== null ? $this->generateRedirectUrl($type, $newUid, $targetLanguage) : null;

        return LocalizationResult::success(
            finisher: $redirectUrl !== null
                ? new RedirectLocalizationFinisher($redirectUrl)
                : new ReloadLocalizationFinisher()
        );
    }

    /**
     * Process page localization including selected content elements
     */
    protected function processPageLocalization(
        LocalizationMode $mode,
        int $pageUid,
        int $targetLanguage,
        array $additionalData
    ): LocalizationResult {
        // Get selected content elements from additionalData
        $selectedContent = $additionalData['selectedRecordUids'] ?? [];

        $cmd = [];

        // Step 1: Check if page translation already exists
        $pageTranslation = $this->localizationRepository->getPageTranslations($pageUid, [$targetLanguage], $this->getBackendUser()->workspace);
        if ($pageTranslation === []) {
            // Page translation doesn't exist - create it
            // Always use 'localize' command for pages (even for copy mode)
            // as we need to create a proper page translation/overlay
            $cmd['pages'] = [
                $pageUid => [
                    'localize' => $targetLanguage,
                ],
            ];
        }

        // Step 2: Add selected content elements to the command
        if (!empty($selectedContent)) {
            $cmd['tt_content'] = [];
            foreach ($selectedContent as $contentUid) {
                $cmd['tt_content'][(int)$contentUid] = [
                    $mode->getDataHandlerCommand() => $targetLanguage,
                ];
            }
        }

        // If no commands were built (page already exists, no content selected),
        // still return success with a no-op finisher
        if (empty($cmd)) {
            // Use no-op finisher to indicate nothing was done, but offer to reload

            return LocalizationResult::success(
                finisher: (new NoopLocalizationFinisher())
            );
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        if ($dataHandler->errorLog !== []) {
            return LocalizationResult::error($dataHandler->errorLog);
        }

        // Generate redirect finisher to the page layout in the target language or use reload finisher as fallback
        $redirectUrl = $this->generateRedirectUrl('pages', $pageUid, $targetLanguage);
        return LocalizationResult::success(
            finisher: $redirectUrl !== null
                ? new RedirectLocalizationFinisher($redirectUrl)
                : new ReloadLocalizationFinisher()
        );
    }

    /**
     * Generate redirect URL based on record type
     */
    protected function generateRedirectUrl(string $type, int $uid, int $targetLanguage): ?string
    {
        if ($type === 'pages') {
            // Redirect to page layout module with the target language
            return (string)$this->uriBuilder->buildUriFromRoute('web_layout', [
                'id' => $uid,
                'languages' => [$targetLanguage],
            ]);
        }

        // For other record types, redirect to the edit form of the translated record
        $record = BackendUtility::getRecord($type, $uid);
        if ($record && isset($record['pid'])) {
            $returnUrl = null;

            if ($type === 'sys_file_metadata') {
                // Get the file from the metadata record and build return URL to filelist module
                try {
                    $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject((int)$record['file']);
                    $parentFolder = $file->getParentFolder();
                    $returnUrl = (string)$this->uriBuilder->buildUriFromRoute(
                        'media_management',
                        ['id' => $parentFolder->getCombinedIdentifier()]
                    );
                } catch (\Exception) {
                    // File not found or inaccessible, fall back to default return URL
                }
            }

            if ($returnUrl === null) {
                $returnUrl = (string)$this->uriBuilder->buildUriFromRoute(
                    'web_layout',
                    [
                        'id' => $record['pid'],
                        'languages' => [$targetLanguage],
                    ]
                );
            }

            // Redirect to edit form for the newly created record
            return (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $type => [
                        $uid => 'edit',
                    ],
                ],
                'returnUrl' => $returnUrl,
            ]);
        }

        return null;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
