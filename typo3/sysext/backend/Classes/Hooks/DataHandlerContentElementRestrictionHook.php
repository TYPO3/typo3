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

namespace TYPO3\CMS\Backend\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Handle content element CType restrictions based on backend layout (via "allowedContentTypes" and
 * "disallowedContentTypes" backend layout column configuration). The hook removes the item from the
 * job list if not allowed.
 */
#[Autoconfigure(public: true)]
readonly class DataHandlerContentElementRestrictionHook
{
    public function __construct(
        private BackendLayoutView $backendLayoutView,
    ) {}

    public function processCmdmap_beforeStart(DataHandler $dataHandler): void
    {
        $cmdmap = $dataHandler->cmdmap;
        if (empty($cmdmap['tt_content']) || $dataHandler->bypassAccessCheckForRecords) {
            return;
        }
        foreach ($cmdmap['tt_content'] as $id => $incomingFieldArray) {
            foreach ($incomingFieldArray as $command => $value) {
                if (!in_array($command, ['copy', 'move'], true)) {
                    continue;
                }
                $currentRecord = BackendUtility::getRecord('tt_content', $id);
                if (empty($currentRecord['CType'] ?? '')) {
                    continue;
                }
                if (is_array($value) && !empty($value['action']) && $value['action'] === 'paste' && isset($value['update']['colPos'])) {
                    // Moving / pasting to a new colPos on a potentially different page
                    $pageId = (int)$value['target'];
                    $colPos = (int)$value['update']['colPos'];
                } else {
                    $pageId = (int)$value;
                    $colPos = (int)$currentRecord['colPos'];
                }
                if ($pageId < 0) {
                    $targetRecord = BackendUtility::getRecord('tt_content', abs($pageId));
                    $pageId = (int)$targetRecord['pid'];
                    $colPos = (int)$targetRecord['colPos'];
                }
                $backendLayout = $this->backendLayoutView->getBackendLayoutForPage($pageId);
                $columnConfiguration = $this->backendLayoutView->getColPosConfigurationForPage($backendLayout, $colPos, $pageId);
                $allowedContentElementsInTargetColPos = GeneralUtility::trimExplode(',', $columnConfiguration['allowedContentTypes'] ?? '', true);
                $disallowedContentElementsInTargetColPos = GeneralUtility::trimExplode(',', $columnConfiguration['disallowedContentTypes'] ?? '', true);
                if ((!empty($allowedContentElementsInTargetColPos) && !in_array($currentRecord['CType'], $allowedContentElementsInTargetColPos, true))
                    || (!empty($disallowedContentElementsInTargetColPos) && in_array($currentRecord['CType'], $disallowedContentElementsInTargetColPos, true))
                ) {
                    // Not allowed to move or copy to target. Unset this command and create a log entry which may be turned into a notification when called by BE.
                    unset($dataHandler->cmdmap['tt_content'][$id]);
                    $dataHandler->log('tt_content', $id, 1, null, 1, 'The command "%s" for record "tt_content:%s" with CType "%s" to colPos "%s" couldn\'t be executed due to disallowed value(s).', null, [$command, $id, $currentRecord['CType'], $colPos]);
                }
            }
        }
    }

    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        $datamap = $dataHandler->datamap;
        if (empty($datamap['tt_content']) || $dataHandler->bypassAccessCheckForRecords) {
            return;
        }
        foreach ($datamap['tt_content'] as $id => $incomingFieldArray) {
            if (MathUtility::canBeInterpretedAsInteger($id)) {
                $record = BackendUtility::getRecord('tt_content', $id);
                if (!is_array($record)) {
                    // Skip this if the record could not be determined for whatever reason
                    continue;
                }
                $recordData = array_merge($record, $incomingFieldArray);
            } else {
                $recordData = array_merge($dataHandler->defaultValues['tt_content'] ?? [], $incomingFieldArray);
            }
            if (empty($recordData['CType']) || !array_key_exists('colPos', $recordData)) {
                // No idea what happened here, but we stop with this record if there is no CType or colPos
                continue;
            }
            $pageId = (int)$recordData['pid'];
            if ($pageId < 0) {
                $previousRecord = BackendUtility::getRecord('tt_content', abs($pageId), 'pid');
                if ($previousRecord === null) {
                    // Broken target data. Stop here and let DH handle this mess.
                    continue;
                }
                $pageId = (int)$previousRecord['pid'];
            }
            $colPos = (int)$recordData['colPos'];
            $backendLayout = $this->backendLayoutView->getBackendLayoutForPage($pageId);
            $columnConfiguration = $this->backendLayoutView->getColPosConfigurationForPage($backendLayout, $colPos, $pageId);
            $allowedContentElementsInTargetColPos = GeneralUtility::trimExplode(',', $columnConfiguration['allowedContentTypes'] ?? '', true);
            $disallowedContentElementsInTargetColPos = GeneralUtility::trimExplode(',', $columnConfiguration['disallowedContentTypes'] ?? '', true);
            if ((!empty($allowedContentElementsInTargetColPos) && !in_array($recordData['CType'], $allowedContentElementsInTargetColPos, true))
                || (!empty($disallowedContentElementsInTargetColPos) && in_array($recordData['CType'], $disallowedContentElementsInTargetColPos, true))
            ) {
                // Not allowed to create in this colPos on this page. Unset this command and create a log entry which may be turned into a notification when called by BE.
                unset($dataHandler->datamap['tt_content'][$id]);
                $dataHandler->log('tt_content', $id, 1, null, 1, 'The record "tt_content:%s" with CType "%s" in colPos "%s" couldn\'t be saved due to disallowed value(s).', null, [$id, $recordData['CType'], $colPos]);
            }
        }
    }
}
