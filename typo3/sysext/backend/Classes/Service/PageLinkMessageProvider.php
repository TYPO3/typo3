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

namespace TYPO3\CMS\Backend\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\LinkHandling\PageTypeLinkResolver;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal To be used in the Content > Page module and not part of public API.
 */
readonly class PageLinkMessageProvider
{
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected PageTypeLinkResolver $pageTypeLinkResolver,
        protected RecordFactory $recordFactory,
        protected UriBuilder $uriBuilder,
        protected TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Generates the messages that are displayed in the Content > Page module
     * to show the target of a page of type link.
     *
     * @param array $pageRecord the database row of the page of type link
     * @return array{
     *      message: string,
     *      state: ContextualFeedbackSeverity
     *  }
     */
    public function generateMessagesForPageTypeLink(array $pageRecord, ServerRequestInterface $request): array
    {
        $languageService = $this->getLanguageService();
        $linkInfo = $this->pageTypeLinkResolver->resolveTypolinkParts($pageRecord);
        if (($linkInfo['url'] ?? '') === '') {
            // No link is set
            return $this->getMessageForMissingLink($request, $languageService);
        }
        return match (($linkInfo['type'] ?? '')) {
            // The current page links to another page
            'page' => $this->getMessageForPageTypeLinkToPage($linkInfo, $request, $languageService),
            // The current page links to another record
            'record' => $this->getMessageForPageTypeLinkToRecord($linkInfo, $request, $languageService),
            default => $this->getMessageBasedOnPageRecordAndUnhandledLinkTypes($pageRecord, $linkInfo, $request, $languageService),
        };
    }

    protected function generateFrontendLink(string $pageLink, LanguageService $languageService, string $type): array
    {
        $pageLink = htmlspecialchars($pageLink);
        $pageLinkHtml = sprintf('<a href="%s" target="_blank" rel="noreferrer">%s</a>', $pageLink, $pageLink);
        $linkTypeLabel = $languageService->translate($type, 'backend.links') ?? $languageService->translate('destination', 'backend.links');
        return [
            $pageLinkHtml,
            $linkTypeLabel,
        ];
    }

    /**
     * Generates a link to the page module containing the content element and
     * displays information about the content element.
     */
    protected function generateLinkToContentElement(int $fragment, ServerRequestInterface $request, string $linkedPage): string
    {
        $languageService = $this->getLanguageService();
        $record = $this->findRecord('tt_content', $fragment);
        if ($record === null) {
            return '';
        }
        $params = [
            'edit' => [$record->getMainType() => [$record->getUid() => 'edit']],
            'module' => '',
            'returnUrl' => (string)$request->getAttribute('normalizedParams')->getRequestUri(),
        ];
        $uri = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $params);
        $recordLabel = htmlspecialchars($record->get('header'));

        if (empty($recordLabel)) {
            $recordLabel = '<em>[' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')) . ']</em>';
        }
        $linkToContentElement = sprintf('<a href="%s">%s</a>', htmlspecialchars($uri), $recordLabel);
        return sprintf(
            $languageService->translate('link_destination_content_element', 'backend.pages.messages'),
            $languageService->translate('link', 'core.db.pages.doktype'),
            $linkToContentElement,
            $linkedPage
        );
    }

    /**
     * @return array{
     *     message: string,
     *     state: ContextualFeedbackSeverity,
     * }
     */
    protected function getMessageForMissingLink(ServerRequestInterface $request, LanguageService $languageService): array
    {
        // No link is set
        return [
            'message' => sprintf(
                $languageService->translate('link_missing', 'backend.pages.messages'),
                $languageService->translate('link', 'core.db.pages'),
                $languageService->translate('link', 'core.db.pages.doktype'),
            ),
            'state' => ContextualFeedbackSeverity::ERROR,
        ];
    }

    /**
     * @param array $linkInfo
     * @return array{
     *     message: string,
     *     state: ContextualFeedbackSeverity,
     * }
     */
    protected function getMessageForPageTypeLinkToPage(
        array $linkInfo,
        ServerRequestInterface $request,
        LanguageService $languageService,
    ): array {
        $backendUser = $this->getBackendUser();
        if (($linkInfo['pageuid'] ?? '') === 'current') {
            return [
                'message' => $this->generateLinkToCurrent($linkInfo, $request, $languageService),
                'state' => ContextualFeedbackSeverity::INFO,
            ];
        }
        $linkToPid = $this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $linkInfo['pageuid']]);
        $path = BackendUtility::getRecordPath($linkInfo['pageuid'], $backendUser->getPagePermsClause(Permission::PAGE_SHOW), 1000);
        $linkedPath = '<a href="' . htmlspecialchars((string)$linkToPid) . '">' . htmlspecialchars($path) . '</a>';
        if (MathUtility::canBeInterpretedAsInteger($linkInfo['fragment'] ?? false)) {
            return [
                'message' => $this->generateLinkToContentElement((int)$linkInfo['fragment'], $request, $linkedPath),
                'state' => ContextualFeedbackSeverity::INFO,
            ];
        }
        $message = sprintf(
            $languageService->translate('link_destination', 'backend.pages.messages'),
            $languageService->translate('link', 'core.db.pages.doktype'),
            $languageService->translate('page', 'backend.links'),
            $linkedPath
        );
        if ($linkInfo['additionalParams'] ?? false) {
            $message .= ' ' . sprintf(
                $languageService->translate('link_destination_additional_parameters', 'backend.pages.messages'),
                htmlspecialchars($linkInfo['additionalParams']),
            );
        }
        return [
            'message' => $message,
            'state' => ContextualFeedbackSeverity::INFO,
        ];
    }

    /**
     * Generates the backend message for link pages linking to current request page,
     * with or without appending parameters.
     */
    protected function generateLinkToCurrent(array $linkInfo, ServerRequestInterface $request, LanguageService $languageService): string
    {
        $queryParameters = $linkInfo['url'] ?? '';
        if ($queryParameters === '') {
            return sprintf(
                $languageService->translate('link_current', 'backend.pages.messages'),
                $languageService->translate('link', 'core.db.pages.doktype'),
            );
        }
        return sprintf(
            $languageService->translate('link_current_with_added_queryparameters', 'backend.pages.messages'),
            $languageService->translate('link', 'core.db.pages.doktype'),
            $queryParameters,
        );
    }

    /**
     * @param array $pageRecord the database row of the page of type link
     * @param array $linkInfo
     * @return array{
     *     message: string,
     *     state: ContextualFeedbackSeverity,
     * }
     */
    protected function getMessageBasedOnPageRecordAndUnhandledLinkTypes(
        array $pageRecord,
        array $linkInfo,
        ServerRequestInterface $request,
        LanguageService $languageService,
    ): array {
        $pageLink = $this->pageTypeLinkResolver->resolvePageLinkUrl($pageRecord, $request);
        if ($pageLink === '') {
            // The link cannot be resolved
            return [
                'message' => sprintf(
                    $languageService->translate('link_invalid', 'backend.pages.messages'),
                    $languageService->translate('link', 'core.db.pages'),
                    htmlspecialchars($pageRecord['link'] ?? ''),
                ),
                'state' => ContextualFeedbackSeverity::ERROR,
            ];
        }
        // Display the frontend link destination, for example external URL or link to a file or email address
        [$pageLinkHtml, $linkTypeLabel] = $this->generateFrontendLink($pageLink, $languageService, $linkInfo['type']);
        return [
            'message' => sprintf(
                $languageService->translate('link_destination', 'backend.pages.messages'),
                $languageService->translate('link', 'core.db.pages.doktype'),
                $linkTypeLabel,
                $pageLinkHtml
            ),
            'state' => ContextualFeedbackSeverity::INFO,
        ];
    }

    /**
     * @param array $linkInfo
     * @return array{
     *     message: string,
     *     state: ContextualFeedbackSeverity,
     * }
     */
    protected function getMessageForPageTypeLinkToRecord(
        array $linkInfo,
        ServerRequestInterface $request,
        LanguageService $languageService,
    ): array {
        $pageTsConfig = BackendUtility::getPagesTSconfig($request->getQueryParams()['id'] ?? 0);
        $linkConfig = $pageTsConfig['TCEMAIN.']['linkHandler.'][$linkInfo['identifier'] . '.'] ?? [];
        if ($linkConfig === []) {
            return [
                'message' => 'No page TSconfig definition found for link of type ' . $linkInfo['identifier'] . '. Expected TCEMAIN.linkHandler.' . $linkInfo['identifier'],
                'state' => ContextualFeedbackSeverity::ERROR,
            ];
        }
        $record = $this->findRecord($linkConfig['configuration.']['table'], (int)$linkInfo['uid']);
        if ($record === null) {
            return [
                'message' => 'This page links to record with uid ' . $linkInfo['uid'] . ' in table ' . $linkConfig['configuration.']['table'] . ' which could not be resolved.',
                'state' => ContextualFeedbackSeverity::ERROR,
            ];
        }
        $params = [
            'edit' => [$linkConfig['configuration.']['table'] => [$linkInfo['uid'] => 'edit']],
            'module' => 'records',
            'returnUrl' => '',
        ];
        $linkToPid = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $params);
        $schema = $this->tcaSchemaFactory->get($linkConfig['configuration.']['table']);
        $label = '[No title]';
        if ($schema->hasCapability(TcaSchemaCapability::Label)) {
            $label = $record->get($schema->getCapability(TcaSchemaCapability::Label)->getPrimaryFieldName()) ?? $label;
        }
        $linkedPath = '<a href="' . htmlspecialchars($linkToPid) . '">' . $label . '</a>';
        $message = sprintf(
            $languageService->translate('link_destination_record', 'backend.pages.messages'),
            $languageService->translate('link', 'core.db.pages.doktype'),
            $languageService->sL($schema->getTitle()),
            $linkedPath,
        );
        if ($linkInfo['additionalParams'] ?? false) {
            $message .= ' ' . sprintf(
                $languageService->translate('link_destination_additional_parameters', 'backend.pages.messages'),
                htmlspecialchars($linkInfo['additionalParams']),
            );
        }
        return [
            'message' => $message,
            'state' => ContextualFeedbackSeverity::INFO,
        ];
    }

    protected function findRecord(string $table, int $uid): ?RecordInterface
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            );
        $row = $queryBuilder->executeQuery()->fetchAssociative();
        if ($row === false) {
            return null;
        }
        return $this->recordFactory->createFromDatabaseRow($table, $row);
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
