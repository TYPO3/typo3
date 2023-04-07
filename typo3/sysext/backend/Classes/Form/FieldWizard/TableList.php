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

namespace TYPO3\CMS\Backend\Form\FieldWizard;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render list of tables and link to element browser,
 * typically used with type=group and internal_type=db.
 */
class TableList extends AbstractNode
{
    /**
     * Render table buttons
     */
    public function render(): array
    {
        $languageService = $this->getLanguageService();
        $result = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $itemName = $parameterArray['itemFormElName'];

        if (empty($config['allowed']) || !is_string($config['allowed'])) {
            // No handling if the field has no, or funny "allowed" settings.
            return $result;
        }

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $allowed = GeneralUtility::trimExplode(',', $config['allowed'], true);
        $allowedTablesHtml = [];
        foreach ($allowed as $tableName) {
            if ($tableName === '*') {
                $label = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.allTables');
                $allowedTablesHtml[] = '<span>';
                $allowedTablesHtml[] =  htmlspecialchars($label);
                $allowedTablesHtml[] = '</span>';
            } else {
                $label = $languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title'] ?? '');
                $icon = $iconFactory->getIconForRecord($tableName, [], Icon::SIZE_SMALL)->render();
                if ((bool)($config['fieldControl']['elementBrowser']['disabled'] ?? false)) {
                    $allowedTablesHtml[] = '<span class="tablelist-item-nolink">';
                    $allowedTablesHtml[] =  $icon;
                    $allowedTablesHtml[] =  htmlspecialchars($label);
                    $allowedTablesHtml[] = '</span>';
                } else {
                    // Initialize attributes
                    $attributes = [
                        'class' => 'btn btn-default t3js-element-browser',
                        'data-mode' => 'db',
                        'data-params' => $itemName . '|||' . $tableName,
                    ];

                    // Add the entry point - if found
                    $attributes = $this->addEntryPoint($tableName, $config, $attributes);

                    $allowedTablesHtml[] = '<button ' . GeneralUtility::implodeAttributes($attributes, true) . '>';
                    $allowedTablesHtml[] =  $icon;
                    $allowedTablesHtml[] =  htmlspecialchars($label);
                    $allowedTablesHtml[] = '</button>';
                }
            }
        }

        $html = [];
        $html[] = '<div class="form-text">';
        $html[] =   implode(LF, $allowedTablesHtml);
        $html[] = '</div>';

        $result['html'] = implode(LF, $html);
        return $result;
    }

    /**
     * Try to resolve a configured default entry point - page / folder
     * to be expanded - and add it to the attributes if found.
     */
    protected function addEntryPoint(string $tableName, array $fieldConfig, array $attributes): array
    {
        if (!isset($fieldConfig['elementBrowserEntryPoints']) || !is_array($fieldConfig['elementBrowserEntryPoints'])) {
            // Early return in case no entry points are defined
            return $attributes;
        }

        // Fetch the configured value (which might be a marker) - falls back to _default
        $entryPoint = (string)($fieldConfig['elementBrowserEntryPoints'][$tableName] ?? $fieldConfig['elementBrowserEntryPoints']['_default'] ?? '');

        if ($entryPoint === '') {
            // In case no entry point exists for the given table and also no default is defined, return
            return $attributes;
        }

        // Check and resolve possible marker
        if (str_starts_with($entryPoint, '###') && str_ends_with($entryPoint, '###')) {
            if ($entryPoint === '###CURRENT_PID###') {
                // Use the current pid
                $entryPoint = (string)$this->data['effectivePid'];
            } elseif ($entryPoint === '###SITEROOT###' && ($this->data['site'] ?? null) instanceof Site) {
                // Use the root page id from the current site
                $entryPoint = (string)$this->data['site']->getRootPageId();
            } else {
                // Check for special TSconfig marker
                $TSconfig = BackendUtility::getTCEFORM_TSconfig($this->data['tableName'], ['pid' => $this->data['effectivePid']]);
                $keyword = substr($entryPoint, 3, -3);
                if (str_starts_with($keyword, 'PAGE_TSCONFIG_')) {
                    $entryPoint = (string)($TSconfig[$this->data['fieldName']][$keyword] ?? '');
                } else {
                    $entryPoint = (string)($TSconfig['_' . $keyword] ?? '');
                }
            }
        }

        // Add the entry point to the attribute - if resolved
        if ($entryPoint !== '') {
            $attributes['data-entry-point'] = $entryPoint;
        }

        return $attributes;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
