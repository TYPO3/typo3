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

namespace TYPO3\CMS\Backend;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 backend user authentication in the Frontend rendering.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class FrontendBackendUserAuthentication extends BackendUserAuthentication
{
    /**
     * Form field with login name.
     *
     * @var string
     */
    public $formfield_uname = '';

    /**
     * Form field with password.
     *
     * @var string
     */
    public $formfield_uident = '';

    /**
     * Formfield_status should be set to "". The value this->formfield_status is set to empty in order to
     * disable login-attempts to the backend account through this script
     *
     * @var string
     */
    public $formfield_status = '';

    /**
     * Decides if the writelog() function is called at login and logout.
     *
     * @var bool
     */
    public $writeStdLog = false;

    /**
     * If the writelog() functions is called if a login-attempt has be tried without success.
     *
     * @var bool
     */
    public $writeAttemptLog = false;

    /**
     * Implementing the access checks that the TYPO3 CMS bootstrap script does before a user is ever logged in.
     * Used in the frontend.
     *
     * @param bool|null $proceedIfNoUserIsLoggedIn
     * @return bool Returns TRUE if access is OK
     */
    public function backendCheckLogin($proceedIfNoUserIsLoggedIn = null)
    {
        if (empty($this->user['uid'])) {
            return false;
        }
        // Check Hardcoded lock on BE
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] < 0) {
            return false;
        }
        return $this->isUserAllowedToLogin();
    }

    /**
     * Edit Access
     */
    /**
     * Checks whether the user has access to edit the language for the
     * requested record.
     *
     * @param string $table The name of the table.
     * @param array $currentRecord The record.
     * @return bool
     */
    public function allowedToEditLanguage($table, array $currentRecord): bool
    {
        // If no access right to record languages, return immediately
        /** @var LanguageAspect $languageAspect */
        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
        if ($table === 'pages') {
            $languageId = $languageAspect->getId();
        } elseif ($table === 'tt_content') {
            $languageId = $languageAspect->getContentId();
        } elseif ($GLOBALS['TCA'][$table]['ctrl']['languageField']) {
            $languageId = $currentRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']];
        } else {
            $languageId = -1;
        }
        return $this->checkLanguageAccess($languageId);
    }

    /**
     * Checks whether the user is allowed to edit the requested table.
     *
     * @param string $table The name of the table.
     * @param array $dataArray The data array.
     * @param array $conf The configuration array for the edit panel.
     * @param bool $checkEditAccessInternals Boolean indicating whether recordEditAccessInternals should not be checked. Defaults
     * @return bool
     */
    public function allowedToEdit(string $table, array $dataArray, array $conf, bool $checkEditAccessInternals): bool
    {
        // Unless permissions specifically allow it, editing is not allowed.
        $mayEdit = false;
        if ($checkEditAccessInternals) {
            $editAccessInternals = $this->recordEditAccessInternals($table, $dataArray, false, false);
        } else {
            $editAccessInternals = true;
        }
        if ($editAccessInternals) {
            $restrictEditingToRecordsOfCurrentPid = !empty($conf['onlyCurrentPid'] ?? false);
            if ($this->isAdmin()) {
                $mayEdit = true;
            } elseif ($table === 'pages') {
                if ($this->doesUserHaveAccess($dataArray, Permission::PAGE_EDIT)) {
                    $mayEdit = true;
                }
            } else {
                $pageOfEditableRecord = BackendUtility::getRecord('pages', $dataArray['pid']);
                if (is_array($pageOfEditableRecord) && $this->doesUserHaveAccess($pageOfEditableRecord, Permission::CONTENT_EDIT) && !$restrictEditingToRecordsOfCurrentPid) {
                    $mayEdit = true;
                }
            }
            // Check the permission of the "pid" that should be accessed, if not disabled.
            if (!$restrictEditingToRecordsOfCurrentPid || $dataArray['pid'] == $GLOBALS['TSFE']->id) {
                // Permissions
                if ($table === 'pages') {
                    $allow = $this->getAllowedEditActions($table, $conf, $dataArray['pid']);
                    // Can only display editbox if there are options in the menu
                    if (!empty($allow)) {
                        $mayEdit = true;
                    }
                } else {
                    $perms = new Permission($this->calcPerms($GLOBALS['TSFE']->page));
                    $types = GeneralUtility::trimExplode(',', strtolower($conf['allow']), true);
                    $allow = array_flip($types);
                    $mayEdit = !empty($allow) && $perms->editContentPermissionIsGranted();
                }
            }
        }
        return $mayEdit;
    }

    /**
     * Takes an array of generally allowed actions and filters that list based on page and content permissions.
     *
     * @param string $table The name of the table.
     * @param array $conf The configuration array.
     * @param int $pid The PID where editing will occur.
     * @return array
     */
    public function getAllowedEditActions($table, array $conf, $pid): array
    {
        $types = GeneralUtility::trimExplode(',', strtolower($conf['allow']), true);
        $allow = array_flip($types);
        if (!$conf['onlyCurrentPid'] || $pid == $GLOBALS['TSFE']->id) {
            // Permissions
            $types = GeneralUtility::trimExplode(',', strtolower($conf['allow']), true);
            $allow = array_flip($types);
            $perms = new Permission($this->calcPerms($GLOBALS['TSFE']->page));
            if ($table === 'pages') {
                // Rootpage
                if (count($GLOBALS['TSFE']->config['rootLine']) === 1) {
                    unset($allow['move']);
                    unset($allow['hide']);
                    unset($allow['delete']);
                }
                if (!$perms->editPagePermissionIsGranted() || !$this->checkLanguageAccess(0)) {
                    unset($allow['edit']);
                    unset($allow['move']);
                    unset($allow['hide']);
                }
                if (!$perms->deletePagePermissionIsGranted()) {
                    unset($allow['delete']);
                }
                if (!$perms->createPagePermissionIsGranted()) {
                    unset($allow['new']);
                }
            }
        }
        return $allow;
    }
}
