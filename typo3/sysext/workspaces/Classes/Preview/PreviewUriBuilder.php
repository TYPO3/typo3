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

namespace TYPO3\CMS\Workspaces\Preview;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder as BackendPreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Workspaces\Event\RetrievedPreviewUrlEvent;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Create links to pages when in a workspace for previewing purposes
 *
 * @internal
 */
class PreviewUriBuilder
{
    /**
     * @var array
     */
    protected $pageCache = [];

    /**
     * @var WorkspaceService
     */
    protected $workspaceService;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var int
     */
    protected $previewLinkLifetime;

    public function __construct(EventDispatcherInterface $eventDispatcher, WorkspaceService $workspaceService)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->workspaceService = $workspaceService;
        $this->previewLinkLifetime = $this->workspaceService->getPreviewLinkLifetime();
    }

    /**
     * Generates a workspace preview link.
     *
     * @param int $uid The ID of the record to be linked
     * @param int $languageId the language to link to
     * @return string the full domain including the protocol http:// or https://, but without the trailing '/'
     */
    public function buildUriForPage(int $uid, int $languageId = 0): string
    {
        $previewKeyword = $this->compilePreviewKeyword(
            $this->previewLinkLifetime * 3600,
            $this->workspaceService->getCurrentWorkspace()
        );

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            $site = $siteFinder->getSiteByPageId($uid);
            try {
                $language = $site->getLanguageById($languageId);
            } catch (\InvalidArgumentException $e) {
                $language = $site->getDefaultLanguage();
            }
            $uri = $site->getRouter()->generateUri($uid, ['ADMCMD_prev' => $previewKeyword, '_language' => $language], '');
            return (string)$uri;
        } catch (SiteNotFoundException | InvalidRouteArgumentsException $e) {
            throw new UnableToLinkToPageException(sprintf('The link to the page with ID "%d" could not be generated: %s', $uid, $e->getMessage()), 1559794916, $e);
        }
    }

    /**
     * Generate workspace preview links for all available languages of a page
     */
    public function buildUrisForAllLanguagesOfPage(int $pageId): array
    {
        $previewLanguages = $this->getAvailableLanguages($pageId);
        $previewLinks = [];

        foreach ($previewLanguages as $languageUid => $language) {
            $previewLinks[$language] = $this->buildUriForPage($pageId, $languageUid);
        }

        return $previewLinks;
    }

    /**
     * Generates a workspace split-bar preview link.
     *
     * @param int $uid The ID of the record to be linked
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function buildUriForWorkspaceSplitPreview(int $uid): UriInterface
    {
        // In case a $pageUid is submitted we need to make sure it points to a live-page
        if ($uid > 0) {
            $uid = $this->getLivePageUid($uid);
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return $uriBuilder->buildUriFromRoute(
            'workspace_previewcontrols',
            ['id' => $uid],
            UriBuilder::ABSOLUTE_URL
        );
    }

    /**
     * Generates a view URI for an element.
     *
     * @param string $table Table to be used
     * @param int $uid Uid of the version(!) record
     * @param array $liveRecord Optional live record data
     * @param array $versionRecord Optional version record data
     */
    public function buildUriForElement(string $table, int $uid, array $liveRecord = null, array $versionRecord = null): string
    {
        $previewUri = $this->createPreviewUriForElement($table, $uid, $liveRecord, $versionRecord);
        $event = new RetrievedPreviewUrlEvent($table, $uid, $previewUri, [
            'liveRecord' => $liveRecord,
            'versionRecord' => $versionRecord,
        ]);
        $event = $this->eventDispatcher->dispatch($event);
        $previewUri = (string)($event->getPreviewUri() ?? '');
        return $previewUri;
    }

    protected function createPreviewUriForElement(string $table, int $uid, array $liveRecord = null, array $versionRecord = null): ?UriInterface
    {
        if ($table === 'pages') {
            return BackendPreviewUriBuilder::create((int)BackendUtility::getLiveVersionIdOfRecord('pages', $uid))
                ->buildUri();
        }

        if ($liveRecord === null) {
            $liveRecord = BackendUtility::getLiveVersionOfRecord($table, $uid);
        }
        if ($versionRecord === null) {
            $versionRecord = BackendUtility::getRecord($table, $uid);
        }

        // Directly use pid value and consider move placeholders
        $previewPageId = (int)(empty($versionRecord['pid']) ? $liveRecord['pid'] : $versionRecord['pid']);
        $linkParameters = [
            'previewWS' => $versionRecord['t3ver_wsid'],
        ];
        // Add language parameter if record is a localization
        if (BackendUtility::isTableLocalizable($table)) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            if ($versionRecord[$languageField] > 0) {
                $linkParameters['_language'] = $versionRecord[$languageField];
            }
        }

        $pageTsConfig = BackendUtility::getPagesTSconfig($previewPageId);
        // Directly use determined direct page id
        if ($table === 'tt_content') {
            return BackendPreviewUriBuilder::create($previewPageId)
                ->withAdditionalQueryParameters(
                    HttpUtility::buildQueryString($linkParameters, '&')
                )
                ->buildUri();
        }
        if (!empty($pageTsConfig['options.']['workspaces.']['previewPageId.'][$table]) || !empty($pageTsConfig['options.']['workspaces.']['previewPageId'])) {
            // Analyze Page TSconfig options.workspaces.previewPageId
            if (!empty($pageTsConfig['options.']['workspaces.']['previewPageId.'][$table])) {
                $previewConfiguration = $pageTsConfig['options.']['workspaces.']['previewPageId.'][$table];
            } else {
                $previewConfiguration = $pageTsConfig['options.']['workspaces.']['previewPageId'];
            }
            // Extract possible settings (e.g. "field:pid")
            $previewValueParts = explode(':', $previewConfiguration, 2);
            if (count($previewValueParts) === 2 && $previewValueParts[0] === 'field') {
                $previewPageId = (int)($liveRecord[$previewValueParts[1]] ?? 0);
            } else {
                $previewPageId = (int)$previewConfiguration;
            }

            // Add preview parameters from standard backend preview mechanism
            // map record data to GET parameters
            $backendPreviewConfiguration = $pageTsConfig['TCEMAIN.']['preview.'][$table . '.'] ?? [];
            if (isset($backendPreviewConfiguration['fieldToParameterMap.'])) {
                foreach ($backendPreviewConfiguration['fieldToParameterMap.'] as $field => $parameterName) {
                    $value = $versionRecord[$field] ?? '';
                    if ($field === 'uid') {
                        $value = $versionRecord['t3ver_oid'] === 0 ? $versionRecord['uid'] : $versionRecord['t3ver_oid'];
                    }
                    $linkParameters[$parameterName] = $value;
                }
            }
            // add/override parameters by configuration
            if (isset($backendPreviewConfiguration['additionalGetParameters.'])) {
                $additionalGetParameters = GeneralUtility::removeDotsFromTS($backendPreviewConfiguration['additionalGetParameters.']);
                $linkParameters = array_replace($linkParameters, $additionalGetParameters);
            }

            return BackendPreviewUriBuilder::create($previewPageId)
                ->withAdditionalQueryParameters(
                    HttpUtility::buildQueryString($linkParameters, '&')
                )
                ->buildUri();
        }
        return null;
    }

    /**
     * Adds an entry to the sys_preview database table and return the preview keyword.
     *
     * @param int $ttl Time-To-Live for keyword
     * @param int|null $workspaceId Which workspace ID to preview.
     * @return string Returns keyword to use in URL for ADMCMD_prev=, a 32 byte MD5 hash keyword for the URL: "?ADMCMD_prev=[keyword]
     */
    protected function compilePreviewKeyword(int $ttl = 172800, int $workspaceId = null): string
    {
        $keyword = md5(StringUtility::getUniqueId());
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_preview')
            ->insert(
                'sys_preview',
                [
                    'keyword' => $keyword,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'endtime' => $GLOBALS['EXEC_TIME'] + $ttl,
                    'config' => json_encode([
                        'fullWorkspace' => $workspaceId,
                    ]),
                ]
            );

        return $keyword;
    }

    /**
     * Find the Live-Uid for a given page,
     * the results are cached at run-time to avoid too many database-queries
     *
     * @throws \InvalidArgumentException
     */
    protected function getLivePageUid(int $uid): int
    {
        if (!isset($this->pageCache[$uid])) {
            $pageRecord = BackendUtility::getRecord('pages', $uid);
            if (is_array($pageRecord)) {
                $this->pageCache[$uid] = $pageRecord['t3ver_oid'] ? (int)$pageRecord['t3ver_oid'] : $uid;
            } else {
                throw new \InvalidArgumentException('uid is supposed to point to an existing page - given value was: ' . $uid, 1290628113);
            }
        }
        return $this->pageCache[$uid];
    }

    /**
     * Get the available languages of a certain page, including language=0 if the user has access to it.
     *
     * @return array assoc array with the languageId as key and the languageTitle as value
     */
    protected function getAvailableLanguages(int $pageId): array
    {
        $languageOptions = [];
        $translationConfigurationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $systemLanguages = $translationConfigurationProvider->getSystemLanguages($pageId);

        if ($this->getBackendUser()->checkLanguageAccess(0)) {
            // Use configured label for default language
            $languageOptions[0] = $systemLanguages[0]['title'];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        $result = $queryBuilder->select('sys_language_uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $languageId = (int)$row['sys_language_uid'];
            // Only add links to active languages the user has access to
            if (isset($systemLanguages[$languageId]) && $this->getBackendUser()->checkLanguageAccess($languageId)) {
                $languageOptions[$languageId] = $systemLanguages[$languageId]['title'];
            }
        }

        return $languageOptions;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
