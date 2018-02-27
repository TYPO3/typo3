<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Workspaces\Hook;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Befunc service
 */
class BackendUtilityHook implements SingletonInterface
{
    /**
     * Gets a singleton instance of this object.
     *
     * @return BackendUtilityHook
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(__CLASS__);
    }

    /**
     * Hooks into the \TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick and redirects to the workspace preview
     * only if we're in a workspace and if the frontend-preview is disabled.
     *
     * @param int $pageUid
     * @param string $backPath
     * @param array $rootLine
     * @param string $anchorSection
     * @param string $viewScript
     * @param string $additionalGetVars
     * @param bool $switchFocus
     */
    public function preProcess(&$pageUid, $backPath, $rootLine, $anchorSection, &$viewScript, $additionalGetVars, $switchFocus)
    {
        if ($GLOBALS['BE_USER']->workspace !== 0) {
            $viewScript = GeneralUtility::makeInstance(WorkspaceService::class)->generateWorkspaceSplittedPreviewLink($pageUid);
        }
    }

    /**
     * Use that hook to show an info message in case someone starts editing
     * a staged element
     *
     * @param array $params
     * @return bool
     */
    public function makeEditForm_accessCheck($params)
    {
        if ($GLOBALS['BE_USER']->workspace !== 0 && $GLOBALS['TCA'][$params['table']]['ctrl']['versioningWS']) {
            $record = BackendUtility::getRecordWSOL($params['table'], $params['uid']);
            if (abs($record['t3ver_stage']) > StagesService::STAGE_EDIT_ID) {
                $stages = GeneralUtility::makeInstance(StagesService::class);
                $stageName = $stages->getStageTitle($record['t3ver_stage']);
                $editingName = $stages->getStageTitle(StagesService::STAGE_EDIT_ID);
                $message = $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:info.elementAlreadyModified');
                $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, sprintf($message, $stageName, $editingName), '', FlashMessage::INFO, true);
                /** @var $flashMessageService FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }
        return $params['hasAccess'];
    }
}
