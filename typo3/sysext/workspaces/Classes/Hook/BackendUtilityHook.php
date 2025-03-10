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

namespace TYPO3\CMS\Workspaces\Hook;

use TYPO3\CMS\Backend\Form\Event\ModifyEditFormUserAccessEvent;
use TYPO3\CMS\Backend\Routing\Event\BeforePagePreviewUriGeneratedEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Preview\PreviewUriBuilder;
use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
readonly class BackendUtilityHook
{
    public function __construct(
        private PreviewUriBuilder $previewUriBuilder,
        private TcaSchemaFactory $tcaSchemaFactory,
        private FlashMessageService $flashMessageService,
        private StagesService $stagesService,
    ) {}

    /**
     * Hooks into the PagePreviewUri and redirects to the workspace preview
     * only if we're in a workspace and if the frontend-preview is disabled.
     */
    #[AsEventListener('typo3-workspaces/link-modifier')]
    public function createPageUriForWorkspaceVersion(BeforePagePreviewUriGeneratedEvent $event): void
    {
        if ($this->getBackendUser()->workspace === 0) {
            return;
        }
        $uri = $this->previewUriBuilder->buildUriForWorkspaceSplitPreview($event->getPageId());
        $queryString = $uri->getQuery();
        if ($event->getAdditionalQueryParameters() !== []) {
            if ($queryString !== '') {
                $queryString .= '&';
            }
            $queryString .= http_build_query($event->getAdditionalQueryParameters(), '', '&', PHP_QUERY_RFC3986);
        }
        // Reassemble encapsulated language id as query parameter, to open workspace preview in correct non-default language
        if ($event->getLanguageId() > 0) {
            $queryString .= '&_language=' . $event->getLanguageId();
        }
        if ($queryString) {
            $uri = $uri->withQuery($queryString);
        }
        $event->setPreviewUri($uri);
    }

    /**
     * Use that hook to show an info message in case someone starts editing a staged element
     */
    #[AsEventListener('typo3-workspaces/display-editing-staged-element-information')]
    public function displayEditingStagedElementInformation(ModifyEditFormUserAccessEvent $event): void
    {
        $tableName = $event->getTableName();
        if ($this->getBackendUser()->workspace === 0 || !$this->tcaSchemaFactory->get($tableName)->isWorkspaceAware()) {
            return;
        }

        $record = BackendUtility::getRecordWSOL($tableName, (int)($event->getDatabaseRow()['uid'] ?? 0));
        if (!isset($record['t3ver_stage']) || abs($record['t3ver_stage']) <= StagesService::STAGE_EDIT_ID) {
            return;
        }

        $stageName = $this->stagesService->getStageTitle((int)$record['t3ver_stage']);
        $editingName = $this->stagesService->getStageTitle(StagesService::STAGE_EDIT_ID);
        $message = ($languageService = $this->getLanguageService()) !== null
            ? $languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:info.elementAlreadyModified')
            : 'Element is in workspace stage "%s", modifications will send it back to "%s".';

        $this->flashMessageService
            ->getMessageQueueByIdentifier()
            ->enqueue(
                GeneralUtility::makeInstance(FlashMessage::class, sprintf($message, $stageName, $editingName), '', ContextualFeedbackSeverity::INFO, true)
            );
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
