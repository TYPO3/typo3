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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;

/**
 * This class displays the Info/Modify screen of the Web > Template module
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class InfoModifyController extends AbstractTemplateModuleController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $pageUid = (int)($queryParams['id'] ?? 0);
        if ($pageUid === 0) {
            // Redirect to template record overview if on page 0.
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }

        if (($parsedBody['action'] ?? '') === 'createExtensionTemplate') {
            return $this->createExtensionTemplateAction($request, 'web_typoscript_infomodify');
        }
        if (($parsedBody['action'] ?? '') === 'createNewWebsiteTemplate') {
            return $this->createNewWebsiteTemplateAction($request, 'web_typoscript_infomodify');
        }

        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageUid);
        if (empty($allTemplatesOnPage)) {
            return $this->noTemplateAction($request);
        }

        return $this->mainAction($request, $pageUid, $allTemplatesOnPage);
    }

    private function noTemplateAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $pageUid = (int)($request->getQueryParams()['id'] ?? 0);
        if ($pageUid === 0) {
            throw new \RuntimeException('No proper page uid given', 1661769346);
        }
        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        if (empty($pageRecord)) {
            // Redirect to records overview if page could not be determined.
            // Edge case if page has been removed meanwhile.
            BackendUtility::setUpdateSignal('updatePageTree');
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addPreviewButtonToDocHeader($view, $pageUid, (int)$pageRecord['doktype']);
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'pageUid' => $pageUid,
            'moduleIdentifier' => $currentModuleIdentifier,
            'previousPage' => $this->getClosestAncestorPageWithTemplateRecord($pageUid),
        ]);
        return $view->renderResponse('InfoModifyNoTemplate');
    }

    private function mainAction(ServerRequestInterface $request, int $pageUid, array $allTemplatesOnPage): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        if (empty($pageRecord)) {
            // Redirect to records overview if page could not be determined.
            // Edge case if page has been removed meanwhile.
            BackendUtility::setUpdateSignal('updatePageTree');
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }

        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $moduleData = $request->getAttribute('moduleData');

        $selectedTemplateFromModuleData = (array)$moduleData->get('selectedTemplatePerPage');
        $selectedTemplateUid = (int)($parsedBody['selectedTemplate'] ?? $queryParams['selectedTemplate'] ?? $selectedTemplateFromModuleData[$pageUid] ?? 0);
        if (!in_array($selectedTemplateUid, array_column($allTemplatesOnPage, 'uid'))) {
            $selectedTemplateUid = (int)($allTemplatesOnPage[0]['uid'] ?? 0);
        }
        if (($moduleData->get('selectedTemplatePerPage')[$pageUid] ?? 0) !== $selectedTemplateUid) {
            $selectedTemplateFromModuleData[$pageUid] = $selectedTemplateUid;
            $moduleData->set('selectedTemplatePerPage', $selectedTemplateFromModuleData);
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $currentTemplateRecord = [];
        foreach ($allTemplatesOnPage as $templateRow) {
            if ((int)$templateRow['uid'] === $selectedTemplateUid) {
                $currentTemplateRecord = $templateRow;
            }
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addPreviewButtonToDocHeader($view, $pageUid, (int)$pageRecord['doktype']);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageUid);
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'pageUid' => $pageUid,
            'previousPage' => $this->getClosestAncestorPageWithTemplateRecord($pageUid),
            'templateRecord' => $currentTemplateRecord,
            'allTemplatesOnPage' => $allTemplatesOnPage,
            'numberOfConstantsLines' => trim((string)($currentTemplateRecord['constants'] ?? '')) ? count(explode(LF, (string)$currentTemplateRecord['constants'])) : 0,
            'numberOfSetupLines' => trim((string)($currentTemplateRecord['config'] ?? '')) ? count(explode(LF, (string)$currentTemplateRecord['config'])) : 0,
        ]);
        return $view->renderResponse('InfoModifyMain');
    }

    private function addShortcutButtonToDocHeader(ModuleTemplate $view, string $moduleIdentifier, array $pageInfo, int $pageUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutTitle = sprintf(
            '%s: %s [%d]',
            $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_info.xlf:submodule.title'),
            BackendUtility::getRecordTitle('pages', $pageInfo),
            $pageUid
        );
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($moduleIdentifier)
            ->setDisplayName($shortcutTitle)
            ->setArguments(['id' => $pageUid]);
        $buttonBar->addButton($shortcutButton);
    }
}
