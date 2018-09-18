<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\FieldInformation;

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * This field information node is used in be_user admin field
 * to show some additional information if the edited user
 * is a system maintainer or becomes one if togging the admin flag.
 */
class AdminIsSystemMaintainer extends AbstractNode
{
    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @throws \RuntimeException
     */
    public function render(): array
    {
        if ($this->data['tableName'] !== 'be_users' || $this->data['fieldName'] !== 'admin') {
            throw new \RuntimeException(
                'The adminIsSystemMaintainer field information can be used for admin field in be_users table only',
                1537273550
            );
        }

        $resultArray = $this->initializeResultArray();
        if ($this->data['command'] === 'new') {
            // Early return on 'new' records - nothing we can do here
            return $resultArray;
        }

        // False if current user is not in system maintainer list or if switch to user mode is active
        $isCurrentUserSystemMaintainer = $this->getBackendUser()->isSystemMaintainer();
        $systemMaintainers = array_map('intval', $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? []);
        $isTargetUserInSystemMaintainerList = in_array((int)$this->data['vanillaUid'], $systemMaintainers, true);

        if ($isTargetUserInSystemMaintainerList) {
            $languageService = $this->getLanguageService();
            $isTargetUserAdmin = (int)$this->data['databaseRow']['admin'] === 1;
            if ($isCurrentUserSystemMaintainer) {
                if ($isTargetUserAdmin) {
                    // User is a system maintainer
                    $fieldInformationText = '<strong>' . htmlspecialchars($languageService->sL(
                            'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:formEngine.beUser.admin.information.userIsSystemMaintainer'
                        )) . '</strong>';
                } else {
                    // User is currently not an admin, but set as system maintainer (in-effective).
                    // If admin field is set to 1, the user is therefore system maintainer again.
                    $fieldInformationText = '<strong>' . htmlspecialchars($languageService->sL(
                            'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:formEngine.beUser.admin.information.userWillBecomeSystemMaintainer'
                        )) . '</strong>';
                }
            } else {
                // User is in system maintainer list, user can not change admin and password
                $fieldInformationText = '<strong>' . htmlspecialchars($languageService->sL(
                        'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:formEngine.beUser.admin.information.userAdminAndPasswordChangeNotAllowed'
                    )) . '</strong>';
            }
            $resultArray['html'] = $fieldInformationText;
        }
        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
