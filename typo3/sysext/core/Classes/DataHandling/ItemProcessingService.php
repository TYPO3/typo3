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

namespace TYPO3\CMS\Core\DataHandling;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides services around item processing
 */
class ItemProcessingService
{
    /**
     * Executes an itemsProcFunc if defined in TCA and returns the combined result (predefined + processed items)
     *
     * @param string $table
     * @param int $pageId
     * @param string $field
     * @param array $row
     * @param array $tcaConfig The TCA configuration of $field
     * @param array $selectedItems The items already defined in the TCA configuration
     * @return array The processed items (including the predefined items)
     */
    public function getProcessingItems($table, $pageId, $field, $row, $tcaConfig, $selectedItems)
    {
        $pageId = $table === 'pages' ? $row['uid'] : $row['pid'];
        $TSconfig = BackendUtility::getPagesTSconfig($pageId);
        $fieldTSconfig = $TSconfig['TCEFORM.'][$table . '.'][$field . '.'] ?? [];

        $params = [];
        $params['items'] = &$selectedItems;
        $params['config'] = $tcaConfig;
        $params['TSconfig'] = $fieldTSconfig['itemsProcFunc.'] ?? null;
        $params['table'] = $table;
        $params['row'] = $row;
        $params['field'] = $field;

        // The itemsProcFunc method may throw an exception.
        // If it does display an error message and return items unchanged.
        try {
            GeneralUtility::callUserFunction($tcaConfig['itemsProcFunc'], $params, $this);
        } catch (\Exception $exception) {
            $languageService = $this->getLanguageService();
            $fieldLabel = $field;
            if (isset($GLOBALS['TCA'][$table]['columns'][$field]['label'])) {
                $fieldLabel = $languageService->sL($GLOBALS['TCA'][$table]['columns'][$field]['label']);
            }
            $message = sprintf(
                $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.items_proc_func_error'),
                $fieldLabel,
                $exception->getMessage()
            );
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                '',
                FlashMessage::ERROR,
                true
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        return $selectedItems;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
