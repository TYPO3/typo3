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

namespace TYPO3\CMS\Backend\Form\FieldInformation;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This field information node is used for the pages backend_layout
 * field to inform about a possible backend layout, inherited form
 * a parent page.
 *
 * @internal
 */
class BackendLayoutFromParentPage extends AbstractNode
{
    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        if ($this->data['tableName'] !== 'pages' || $this->data['fieldName'] !== 'backend_layout') {
            throw new \RuntimeException(
                'The backendLayoutFromParentPage field information can only be used for the backend_layout field of the pages table.',
                1622109821
            );
        }

        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];

        // In case the backend_layout field of the current page is not empty, no backend layout will be inherited.
        if (!empty($parameterArray['itemFormElValue'])) {
            return $resultArray;
        }

        $backendLayoutInformation = '';
        $languageService = $this->getLanguageService();

        if ($this->data['command'] === 'new') {
            // In case we deal with a new record, we try to find a possible inherited backend layout in
            // the rootline. Since there might be further actions, e.g. DataHandler hooks, the actually
            // resolved backend layout can only be determined, once the record is saved. For now we just
            // inform about the backend layout, which will most likely be used.
            foreach ($this->data['rootline'] as $page) {
                if (!empty($page['backend_layout_next_level']) && ($page['uid'] ?? false)) {
                    $backendLayoutInformation = sprintf(
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:formEngine.pages.backendLayout.information.inheritFromParentPage'),
                        $this->getFieldValueLabel($parameterArray['fieldConf'], $page['backend_layout_next_level'])
                    );
                    break;
                }
            }
        } else {
            // Get the resolved backend layout for the current page.
            $backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
            $backendLayout = $backendLayoutView->getBackendLayoutForPage(
                (int)($this->data['databaseRow']['uid'] ?? $this->data['effectivePid'] ?? 0)
            );
            if ($backendLayout !== null) {
                $backendLayoutInformation = sprintf(
                    $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:formEngine.pages.backendLayout.information.inheritedFromParentPage'),
                    $languageService->sL($backendLayout->getTitle())
                );
            }
        }

        if ($backendLayoutInformation !== '') {
            $resultArray['html'] = '<p class="text-muted">' . htmlspecialchars($backendLayoutInformation) . '</p>';
        }

        return $resultArray;
    }

    protected function getFieldValueLabel(array $fieldConfiguration, string $fieldValue): string
    {
        foreach ($fieldConfiguration['config']['items'] as $item) {
            if (($item[1] ?? '') === $fieldValue && !empty($item[0])) {
                return $item[0];
            }
        }

        $invalidValue = sprintf(
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
            $fieldValue
        );

        return '[ ' . $invalidValue . ' ]';
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
