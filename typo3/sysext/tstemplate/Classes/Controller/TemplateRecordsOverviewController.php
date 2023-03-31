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
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Overview of all sys_template records from site root
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class TemplateRecordsOverviewController extends AbstractTemplateModuleController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $pageUid = (int)($request->getQueryParams()['id'] ?? 0);
        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        if ($pageUid > 0 && empty($pageRecord)) {
            // Redirect to records overview of page 0 if page could not be determined.
            // Edge case if page has been removed meanwhile.
            BackendUtility::setUpdateSignal('updatePageTree');
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }

        $moduleData = $request->getAttribute('moduleData');
        if ($moduleData->cleanUp([])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $result = $queryBuilder
            ->select('uid', 'pid', 'title', 'root', 'hidden', 'starttime', 'endtime')
            ->from('sys_template')
            ->orderBy('sys_template.pid')
            ->addOrderBy('sys_template.sorting')
            ->executeQuery();
        $pagesWithTemplates = [];
        while ($record = $result->fetchAssociative()) {
            $additionalFieldsForRootline = ['sorting', 'shortcut'];
            $rootline = array_reverse(BackendUtility::BEgetRootLine($record['pid'], '', true, $additionalFieldsForRootline));
            if ($rootline !== []) {
                $pagesWithTemplates = $this->setInPageArray($pagesWithTemplates, $rootline, $record);
            }
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($this->getLanguageService()->sL($currentModule->getTitle()), '');
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageUid);
        if ($pageUid !== 0) {
            $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        }
        $view->assign('pageTree', $pagesWithTemplates);
        return $view->renderResponse('TemplateRecordsOverview');
    }

    /**
     * Recursively add template row in pages tree array by given pages rootline to prepare tree rendering.
     * @param non-empty-array $rootline
     */
    private function setInPageArray(array $pages, array $rootline, array $row): array
    {
        if (!$rootline[0]['uid']) {
            // Skip 'root'
            array_shift($rootline);
        }
        $currentRootlineElement = current($rootline);
        if (empty($pages[$currentRootlineElement['uid']])) {
            // Page not in tree yet. Add it.
            $pages[$currentRootlineElement['uid']] = $currentRootlineElement;
        }
        array_shift($rootline);
        if (empty($rootline)) {
            // Last rootline element: Add template row
            $pages[$currentRootlineElement['uid']]['_templates'][] = $row;
        } else {
            // Recurse into sub array
            $pages[$currentRootlineElement['uid']]['_nodes'] ??= [];
            $pages[$currentRootlineElement['uid']]['_nodes'] = $this->setInPageArray($pages[$currentRootlineElement['uid']]['_nodes'], $rootline, $row);
        }
        // Tree node sorting by pages sorting field
        uasort($pages, static fn ($a, $b) => $a['sorting'] - $b['sorting']);
        return $pages;
    }

    private function addShortcutButtonToDocHeader(ModuleTemplate $view, string $moduleIdentifier, array $pageInfo, int $pageUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutTitle = sprintf(
            '%s: %s [%d]',
            $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_records_overview.xlf:typoscriptRecords.title'),
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
