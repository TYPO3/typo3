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

namespace TYPO3\CMS\Tstemplate\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;

/**
 * This class displays the Info/Modify screen of the Web > Template module
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class InfoModifyController extends AbstractTemplateModuleController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $moduleData = $request->getAttribute('moduleData');
        if ($moduleData->cleanUp([])) {
            $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }

        $pageId = (int)($request->getQueryParams()['id'] ?? 0);
        if ($pageId === 0) {
            // Redirect to template record overview if on page 0.
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }
        $pageRecord = BackendUtility::readPageAccess($pageId, '1=1') ?: [];

        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageId);
        if ($moduleData->clean('templatesOnPage', array_column($allTemplatesOnPage, 'uid') ?: [0])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $selectedTemplateRecord = (int)$moduleData->get('templatesOnPage');
        $templateRow = $this->getFirstTemplateRecordOnPage($pageId, $selectedTemplateRecord);

        $saveId = 0;
        if ($templateRow) {
            $saveId = empty($templateRow['_ORIG_uid']) ? $templateRow['uid'] : $templateRow['_ORIG_uid'];
        }
        $newId = $this->createTemplateIfRequested($request, $pageId, (int)$saveId);
        if ($newId) {
            // Redirect to created template.
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_overview', ['id' => $pageId, 'templatesOnPage' => $newId]));
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addPreviewButtonToDocHeader($view, $pageId, (int)$pageRecord['doktype']);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageId);
        $this->addNewButtonToDocHeader($view, $currentModuleIdentifier, $pageId);
        $view->makeDocHeaderModuleMenu(['id' => $pageId]);
        $view->assignMultiple([
            'moduleIdentifier' => $currentModuleIdentifier,
            'pageId' => $pageId,
            'previousPage' => $this->getClosestAncestorPageWithTemplateRecord($pageId),
            'templateRecord' => $templateRow,
            'manyTemplatesMenu' => BackendUtility::getFuncMenu($pageId, 'templatesOnPage', $moduleData->get('templatesOnPage'), array_column($allTemplatesOnPage, 'title', 'uid')),
            'numberOfConstantsLines' => trim((string)($templateRow['constants'] ?? '')) ? count(explode(LF, (string)$templateRow['constants'])) : 0,
            'numberOfSetupLines' => trim((string)($templateRow['config'] ?? '')) ? count(explode(LF, (string)$templateRow['config'])) : 0,
        ]);
        return $view->renderResponse('InfoModify');
    }

    protected function addNewButtonToDocHeader(ModuleTemplate $view, string $moduleIdentifier, int $pageId): void
    {
        $languageService = $this->getLanguageService();
        if ($pageId) {
            $urlParameters = [
                'id' => $pageId,
                'template' => 'all',
                'createExtension' => 'new',
            ];
            $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
            $newButton = $buttonBar->makeLinkButton()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute($moduleIdentifier, $urlParameters))
                ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.pagetitle'))
                ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
            $buttonBar->addButton($newButton);
        }
    }
}
