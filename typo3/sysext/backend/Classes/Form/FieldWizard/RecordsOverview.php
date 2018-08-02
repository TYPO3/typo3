<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\FieldWizard;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render details of selected records,
 * typically used with type=group and internal_type=db.
 */
class RecordsOverview extends AbstractNode
{
    /**
     * Render table with record details
     *
     * @return array
     */
    public function render(): array
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        $result = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $selectedItems = $parameterArray['itemFormElValue'];
        $maxTitleLength = $backendUser->uc['titleLen'];

        if (!isset($config['internal_type']) || $config['internal_type'] !== 'db') {
            // Table list makes sense on db only
            return $result;
        }

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
            $recordsOverviewHtml[] = '<tr>';
            $recordsOverviewHtml[] =    '<td class="col-icon">';
            $recordsOverviewHtml[] =        $linkedIcon;
            $recordsOverviewHtml[] =    '</td>';
            $recordsOverviewHtml[] =    '<td class="col-title">';
            $recordsOverviewHtml[] =        $linkedTitle;
            $recordsOverviewHtml[] =        '<span class="text-muted">';
            $recordsOverviewHtml[] =            ' [' . $selectedItem['uid'] . ']';
            $recordsOverviewHtml[] =        '</span>';
            $recordsOverviewHtml[] =    '</td>';
            $recordsOverviewHtml[] = '</tr>';
        }

        $html = [];
        if (!empty($recordsOverviewHtml)) {
            $html[] = '<div class="table-fit">';
            $html[] =   '<table class="table table-white">';
            $html[] =       '<tbody>';
            $html[] =           implode(LF, $recordsOverviewHtml);
            $html[] =       '</tbody>';
            $html[] =   '</table>';
            $html[] = '</div>';
        }

        $result['html'] = implode(LF, $html);
        return $result;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
