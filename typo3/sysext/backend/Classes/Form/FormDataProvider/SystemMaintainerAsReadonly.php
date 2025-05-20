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
 * Special data provider for setting all fields of the current
 * record to "readOnly" in case a non system maintainer is editing
 * a system maintainer record.
 */
class SystemMaintainerAsReadonly implements FormDataProviderInterface
{
    public function __construct(
        private readonly FlashMessageService $flashMessageService,
    ) {}

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
