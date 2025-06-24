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
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract class with helper methods for single 3rd level Template module controllers.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
abstract class AbstractTemplateModuleController
{
    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ConnectionPool $connectionPool;
    protected SiteFinder $siteFinder;
    private DataHandler $dataHandler;

    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }

    public function injectUriBuilder(UriBuilder $uriBuilder)
    {
        $this->uriBuilder = $uriBuilder;
    }

    public function injectConnectionPool(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    public function injectDataHandler(DataHandler $dataHandler)
    {
        $this->dataHandler = $dataHandler;
    }

    public function injectSiteFinder(SiteFinder $siteFinder)
    {
        $this->siteFinder = $siteFinder;
    }

    /**
     * Action shared by info/modify ond constant editor to create a new "extension template"
     */
    protected function createExtensionTemplateAction(ServerRequestInterface $request, string $redirectTarget): ResponseInterface
    {
        $pageUid = (int)($request->getQueryParams()['id'] ?? 0);
        if ($pageUid === 0) {
            throw new \RuntimeException('No proper page uid given', 1661333864);
        }
        $recordData['sys_template']['NEW'] = [
            'pid' => $pageUid,
            'title' => '+ext',
        ];
        $this->dataHandler->start($recordData, []);
        $this->dataHandler->process_datamap();
        return new RedirectResponse($this->uriBuilder->buildUriFromRoute($redirectTarget, ['id' => $pageUid]));
    }

    /**
     * Action shared by info/modify ond constant editor to create a new "site template"
     */
    protected function createNewWebsiteTemplateAction(ServerRequestInterface $request, string $redirectTarget): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $pageUid = (int)($request->getQueryParams()['id'] ?? 0);
        if ($pageUid === 0) {
            throw new \RuntimeException('No proper page uid given', 1661333863);
        }
        $recordData['sys_template']['NEW'] = [
            'pid' => $pageUid,
            'title' => $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang.xlf:noRecordFound.createRootTypoScriptRecord.title.placeholder'),
            'sorting' => 0,
            'root' => 1,
            'clear' => 3,
            'config' => "\n"
                . "# Default PAGE object:\n"
                . "page = PAGE\n"
                . "page.10 = TEXT\n"
                . "page.10.value = HELLO WORLD!\n",
        ];
        $this->dataHandler->start($recordData, []);
        $this->dataHandler->process_datamap();
        return new RedirectResponse($this->uriBuilder->buildUriFromRoute($redirectTarget, ['id' => $pageUid]));
    }

    protected function addPreviewButtonToDocHeader(ModuleTemplate $view, array $pageRecord): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        $previewUriBuilder = PreviewUriBuilder::create($pageRecord);
        if ($previewUriBuilder->isPreviewable()) {
            $previewDataAttributes = $previewUriBuilder
                ->withRootLine(BackendUtility::BEgetRootLine($pageRecord['uid']))
                ->buildDispatcherDataAttributes();
            $viewButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setDataAttributes($previewDataAttributes ?? [])
                ->setDisabled(!$previewDataAttributes)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->iconFactory->getIcon('actions-view-page', IconSize::SMALL))
                ->setShowLabelText(true);
            $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 99);
        }
    }

    /**
     * Get the closest page row that has a template up in rootline
     */
    protected function getClosestAncestorPageWithTemplateRecord(int $pageId): array
    {
        $rootLine = BackendUtility::BEgetRootLine($pageId);
        foreach ($rootLine as $rootlineNode) {
            if ($this->getFirstTemplateRecordOnPage((int)$rootlineNode['uid'])) {
                return $rootlineNode;
            }
        }
        return [];
    }

    protected function getScopedRootline(SiteInterface $site, array $fullRootLine): array
    {
        if (!$site instanceof Site) {
            return $fullRootLine;
        }
        if (!$site->isTypoScriptRoot()) {
            return $fullRootLine;
        }
        $rootLineUntilSite = [];
        foreach ($fullRootLine as $index => $rootlinePage) {
            $rootlinePageId = (int)($rootlinePage['uid'] ?? 0);
            $rootLineUntilSite[$index] = $rootlinePage;
            if ($rootlinePageId === $site->getRootPageId()) {
                break;
            }
        }
        return $rootLineUntilSite;
    }

    /**
     * Get an array of all template records on a page.
     */
    protected function getAllTemplateRecordsOnPage(int $pageId): array
    {
        if (!$pageId) {
            return [];
        }

        $templateRecords = [];

        try {
            $site = $this->siteFinder->getSiteByRootPageId($pageId);
            if ($site->isTypoScriptRoot()) {
                $typoScript = $site->getTypoScript();
                $templateRecords[] = [
                    'type' => 'site',
                    'pid' => $pageId,
                    'constants' => $typoScript?->constants ?? '',
                    'config' => $typoScript?->setup ?? '',
                    'root' => 1,
                    'clear' => 1,
                    'sorting' => -1,
                    'uid' => -1,
                    'site' => $site,
                    'title' => $site->getConfiguration()['websiteTitle'] ?? '',
                ];
            }
        } catch (SiteNotFoundException) {
            // ignore
        }

        $result = $this->getTemplateQueryBuilder($pageId)->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $templateRecords[] = [...$row, 'type' => 'sys_template'];
        }
        return $templateRecords;
    }

    /**
     * Get a single sys_template record attached to a single page.
     * If multiple template records are on this page, the first (order by sorting)
     * record will be returned, unless a specific template uid is specified via $templateUid
     *
     * @param int $pageId The pid to select sys_template records from
     * @param int $templateUid Optional template uid
     * @return array<string,mixed>|false Returns the template record or false if none was found
     */
    protected function getFirstTemplateRecordOnPage(int $pageId, int $templateUid = 0): array|false
    {
        if (empty($pageId)) {
            return false;
        }
        $queryBuilder = $this->getTemplateQueryBuilder($pageId)->setMaxResults(1);
        if ($templateUid) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($templateUid, Connection::PARAM_INT))
            );
        }
        return $queryBuilder->executeQuery()->fetchAssociative();
    }

    /**
     * Helper method to prepare the query builder for getting sys_template records from a given pid.
     */
    protected function getTemplateQueryBuilder(int $pid): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder->select('*')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT))
            )
            ->orderBy($GLOBALS['TCA']['sys_template']['ctrl']['sortby']);
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
