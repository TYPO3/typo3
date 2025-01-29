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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder as BackendPreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
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

/**
 * Create links to pages when in a workspace for previewing purposes
 *
 * @internal
 */
#[Autoconfigure(public: true)]
readonly class PreviewUriBuilder
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
        private SiteFinder $siteFinder,
        private UriBuilder $uriBuilder,
        private ConnectionPool $connectionPool,
        private TranslationConfigurationProvider $translationConfigurationProvider,
    ) {}

    /**
     * Generates a workspace preview link.
     *
     * @param int $uid The ID of the record to be linked
     * @param int $languageId the language to link to
     * @return string the full domain including the protocol http:// or https://, but without the trailing '/'
     */
    public function buildUriForPage(int $uid, int $languageId = 0): string
    {
        $previewKeyword = $this->compilePreviewKeyword();
        try {
            $site = $this->siteFinder->getSiteByPageId($uid);
            try {
                $language = $site->getLanguageById($languageId);
            } catch (\InvalidArgumentException) {
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
        return $this->uriBuilder->buildUriFromRoute(
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
     */
    public function buildUriForElement(string $table, int $uid, ?array $liveRecord = null, ?array $versionRecord = null): string
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

    protected function createPreviewUriForElement(string $table, int $uid, ?array $liveRecord = null, ?array $versionRecord = null): ?UriInterface
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
     * Cached in runtime cache to only create *one* link per request, even if the service
     * method is called multiple times.
     *
     * @return string Returns a 32 byte MD5 hash keyword for use in URL for ADMCMD_prev=[keyword]
     */
    protected function compilePreviewKeyword(): string
    {
        $workspaceId = $this->getBackendUser()->workspace;
        $userId = $this->getBackendUser()->getUserId();
        $cacheIdentifier = 'workspaces-preview-keyword-' . $workspaceId . '-' . $userId;
        if ($keyword = $this->runtimeCache->get($cacheIdentifier)) {
            return $keyword;
        }
        $lifetimeInSeconds = $this->getPreviewLinkLifetime() * 3600;
        $keyword = md5(StringUtility::getUniqueId());
        $this->connectionPool->getConnectionForTable('sys_preview')
            ->insert(
                'sys_preview',
                [
                    'keyword' => $keyword,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'endtime' => $GLOBALS['EXEC_TIME'] + $lifetimeInSeconds,
                    'config' => json_encode([
                        'fullWorkspace' => $this->getBackendUser()->workspace,
                    ]),
                ]
            );
        $this->runtimeCache->set($cacheIdentifier, $keyword);
        return $keyword;
    }

    /**
     * Determine the number of hours a preview link should be valid after creation, default 48 hours.
     */
    protected function getPreviewLinkLifetime(): int
    {
        $workspaceId = $this->getBackendUser()->workspace;
        if ($workspaceId > 0) {
            $wsRecord = BackendUtility::getRecord('sys_workspace', $workspaceId, 'previewlink_lifetime');
            if (($wsRecord['previewlink_lifetime'] ?? 0) > 0) {
                return (int)$wsRecord['previewlink_lifetime'];
            }
        }
        return (int)($this->getBackendUser()->getTSConfig()['options.']['workspaces.']['previewLinkTTLHours'] ?? 0) ?: 24 * 2;
    }

    /**
     * Find the Live-Uid for a given page uid and runtime cache the result
     * to avoid too many database-queries.
     */
    protected function getLivePageUid(int $uid): int
    {
        $cacheIdentifier = 'workspaces-page-uid-to-live-uid-' . $uid;
        if ($liveUid = $this->runtimeCache->get($cacheIdentifier)) {
            return $liveUid;
        }
        $pageRecord = BackendUtility::getRecord('pages', $uid);
        if (!is_array($pageRecord)) {
            throw new \InvalidArgumentException('uid is supposed to point to an existing page - given value was: ' . $uid, 1290628113);
        }
        $liveUid = $pageRecord['t3ver_oid'] ? (int)$pageRecord['t3ver_oid'] : $uid;
        $this->runtimeCache->set($cacheIdentifier, $liveUid);
        return $liveUid;
    }

    /**
     * Get the available languages of a certain page, including language=0 if the user has access to it.
     *
     * @return array assoc array with the languageId as key and the languageTitle as value
     */
    protected function getAvailableLanguages(int $pageId): array
    {
        $languageOptions = [];
        $systemLanguages = $this->translationConfigurationProvider->getSystemLanguages($pageId);

        if ($this->getBackendUser()->checkLanguageAccess(0)) {
            // Use configured label for default language
            $languageOptions[0] = $systemLanguages[0]['title'];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
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
