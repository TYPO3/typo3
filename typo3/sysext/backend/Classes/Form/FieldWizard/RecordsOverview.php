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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render details of selected records,
 * typically used with type='group'.
 */
class RecordsOverview extends AbstractNode
{
    /**
     * Render table with record details
     */
    public function render(): array
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        $result = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $selectedItems = $parameterArray['itemFormElValue'];
        $maxTitleLength = (int)$backendUser->uc['titleLen'];

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $recordsOverviewHtml = [];
        foreach ($selectedItems as $selectedItem) {
            $title = (string)$selectedItem['title'];
            if (empty($title)) {
                $title = '[' . $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title') . ']';
            }
            $shortenedTitle = GeneralUtility::fixed_lgd_cs($title, $maxTitleLength);
            $linkedIcon = BackendUtility::wrapClickMenuOnIcon(
                $iconFactory->getIconForRecord($selectedItem['table'], $selectedItem['row'], Icon::SIZE_SMALL)->render(),
                $selectedItem['table'],
                $selectedItem['uid']
            );
            $linkedTitle = BackendUtility::wrapClickMenuOnIcon(
                htmlspecialchars($shortenedTitle),
                $selectedItem['table'],
                $selectedItem['uid']
            );
            $pathToContainingPage = BackendUtility::getRecordPath($selectedItem['row']['pid'], $this->getBackendUserAuthentication()->getPagePermsClause(Permission::PAGE_SHOW), 0);

            $recordsOverviewHtml[] = '<tr>';
            $recordsOverviewHtml[] =    '<td class="col-icon">';
            $recordsOverviewHtml[] =        $linkedIcon;
            $recordsOverviewHtml[] =    '</td>';
            $recordsOverviewHtml[] =    '<td class="col-title">';
            $recordsOverviewHtml[] =        $linkedTitle;
            $recordsOverviewHtml[] =        '<span class="text-body-secondary">';
            $recordsOverviewHtml[] =            ' [' . $selectedItem['uid'] . ']';
            $recordsOverviewHtml[] =            ' ' . htmlspecialchars($pathToContainingPage);
            $recordsOverviewHtml[] =        '</span>';
            $recordsOverviewHtml[] =    '</td>';
            $recordsOverviewHtml[] = '</tr>';
        }

        $html = [];
        if (!empty($recordsOverviewHtml)) {
            $html[] = '<div class="table-fit mt-1 mb-0">';
            $html[] =   '<table class="table">';
            $html[] =       '<tbody>';
            $html[] =           implode(LF, $recordsOverviewHtml);
            $html[] =       '</tbody>';
            $html[] =   '</table>';
            $html[] = '</div>';
        }

        $result['html'] = implode(LF, $html);
        return $result;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
