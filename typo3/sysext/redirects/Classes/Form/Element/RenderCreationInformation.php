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

namespace TYPO3\CMS\Redirects\Form\Element;

use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This is a concrete implementation only, and not part of TYPO3 Core API.
 */
final class RenderCreationInformation extends AbstractFormElement
{
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $databaseRow = $this->data['databaseRow'] ?? [];
        if ($this->data['command'] !== 'edit') {
            // Created on / by can only be displayed on edit - new records are obviously not created yet
            return [];
        }
        $userId = (int)($databaseRow['createdby'] ?? 0);
        $timestamp = (int)($databaseRow['createdon'] ?? 0);
        $backendUser = BackendUtility::getRecord('be_users', (int)($databaseRow['createdby'] ?? 0));
        $avatarHtml = '';
        if (!empty($backendUser)) {
            $avatar = GeneralUtility::makeInstance(Avatar::class);
            $avatarHtml = $avatar->render($backendUser, 32, true);
        }
        $realName = (string)($backendUser['realName'] ?? '');
        $userName = (string)($backendUser['username'] ?? '');
        if ($realName !== '') {
            $userHtml = '<strong>' . htmlspecialchars($realName) . '</strong> <span class="text-variant">(' . htmlspecialchars($userName) . ')</span>';
        } elseif ($userName !== '') {
            $userHtml = '<strong>' . htmlspecialchars($userName) . '</strong>';
        } elseif ($userId > 0) {
            $userHtml = '<strong><i>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_show_rechis.xlf:userNotFound')) . '</i></strong>';
        } else {
            $userHtml = '<strong><i>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:not_tracked')) . '</i></strong>';
        }

        $html = [];
        $html[] = '<span class="form-label">';
        $html[] =   htmlspecialchars($this->data['parameterArray']['fieldConf']['label'] ?? '');
        $html[] = '</span>';
        $html[] = '<div class="form-control-wrap">';
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =     '<div class="form-wizards-item-element">';
        $html[] =       '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =         '<div class="d-flex gap-3 mt-1">';
        $html[] =           $avatarHtml;
        $html[] =           '<div class="formengine-field-wrapper">';
        $html[] =             '<p class="m-0">' . $userHtml . '</p>';
        if ($timestamp > 0) {
            $html[] =               htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang.xlf:created_on')) . ' ';
            $html[] =               htmlspecialchars(BackendUtility::datetime($timestamp));
        }
        $html[] =           '</div>';
        $html[] =         '</div>';
        $html[] =       '</div>';
        $html[] =     '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';
        $resultArray['html'] = implode(LF, $html);

        return $resultArray;
    }
}
