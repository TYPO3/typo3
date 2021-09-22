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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render list of tables and link to element browser,
 * typically used with type=group and internal_type=db.
 */
class TableList extends AbstractNode
{
    /**
     * Render table buttons
     *
     * @return array
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
                $label = $languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
                $icon = $iconFactory->getIconForRecord($tableName, [], Icon::SIZE_SMALL)->render();
                if ((bool)($config['fieldControl']['elementBrowser']['disabled'] ?? false)) {
                    $allowedTablesHtml[] = '<span class="tablelist-item-nolink">';
                    $allowedTablesHtml[] =  $icon;
                    $allowedTablesHtml[] =  htmlspecialchars($label);
                    $allowedTablesHtml[] = '</span>';
                } else {
                    $allowedTablesHtml[] = '<a href="#" class="btn btn-default t3js-element-browser" data-mode="db" data-params="' . htmlspecialchars($itemName . '|||' . $tableName) . '">';
                    $allowedTablesHtml[] =  $icon;
                    $allowedTablesHtml[] =  htmlspecialchars($label);
                    $allowedTablesHtml[] = '</a>';
                }
            }
        }

        $html = [];
        $html[] = '<div class="help-block">';
        $html[] =   implode(LF, $allowedTablesHtml);
        $html[] = '</div>';

        $result['html'] = implode(LF, $html);
        return $result;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
