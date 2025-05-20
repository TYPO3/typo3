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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Special data provider for replacing a database field with the value of
 * the default record in case "l10n_display" is set to "defaultAsReadonly".
 */
class SystemMaintainerAsReadonly implements FormDataProviderInterface
{
    public function __construct(
        private readonly FlashMessageService $flashMessageService,
    ) {}

    /**
     * Check each field for being an overlay, having l10n_display set to defaultAsReadonly
     * and whether the field exists in the default language row. If so, the current
     * database value will be replaced by the one from the default language row.
     */
    public function addData(array $result): array
    {
        if ($result['tableName'] !== 'be_users' || $result['command'] !== 'edit') {
            return $result;
        }

        $id = (int)$result['vanillaUid'];
        $systemMaintainers = array_map(intval(...), $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? []);
        $isCurrentUserSystemMaintainer = $this->getBackendUser()->isSystemMaintainer();
        $isTargetUserInSystemMaintainerList = in_array($id, $systemMaintainers, true);
        if (!$isCurrentUserSystemMaintainer && $isTargetUserInSystemMaintainerList) {
            $message = $this->getLanguageService()->sL(
                'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:formEngine.beUser.information.adminCanNotChangeSystemMaintainer'
            );
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                '',
                ContextualFeedbackSeverity::INFO
            );
            $this->flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);

            foreach ($result['processedTca']['columns'] as &$fieldConfig) {
                $fieldConfig['config']['readOnly'] = true;
            }
        }
        return $result;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
