<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Workspaces\Preview;

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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
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

    public function __construct()
    {
        $this->workspaceService = GeneralUtility::makeInstance(WorkspaceService::class);
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
            $this->getPreviewLinkLifetime() * 3600,
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
            throw new UnableToLinkToPageException('The page ' . $uid . ' had no proper connection to a site, no link could be built.', 1559794916);
        }
    }

    /**
     * Generate workspace preview links for all available languages of a page
     *
     * @param int $pageId
     * @return array
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
     * @return UriInterface
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
     * Generates a view Uri for an element.
     *
     * @param string $table Table to be used
     * @param int $uid Uid of the version(!) record
     * @param array $liveRecord Optional live record data
     * @param array $versionRecord Optional version record data
     * @return string
     */
    public function buildUriForElement(string $table, int $uid, array $liveRecord = null, array $versionRecord = null): string
    {
        $movePlaceholder = [];
        if ($table === 'pages') {
            return BackendUtility::viewOnClick(BackendUtility::getLiveVersionIdOfRecord('pages', $uid));
        }

        if ($liveRecord === null) {
            $liveRecord = BackendUtility::getLiveVersionOfRecord($table, $uid);
        }
        if ($versionRecord === null) {
            $versionRecord = BackendUtility::getRecord($table, $uid);
        }
        if (VersionState::cast($versionRecord['t3ver_state'])->equals(VersionState::MOVE_POINTER)) {
            $movePlaceholder = BackendUtility::getMovePlaceholder($table, $liveRecord['uid'], 'pid');
        }

        // Directly use pid value and consider move placeholders
        $previewPageId = (empty($movePlaceholder['pid']) ? $liveRecord['pid'] : $movePlaceholder['pid']);
        $additionalParameters = '&previewWS=' . $versionRecord['t3ver_wsid'];
        // Add language parameter if record is a localization
        if (BackendUtility::isTableLocalizable($table)) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            if ($versionRecord[$languageField] > 0) {
                $additionalParameters .= '&L=' . $versionRecord[$languageField];
            }
        }

        $pageTsConfig = BackendUtility::getPagesTSconfig($previewPageId);
        $viewUrl = '';

        // Directly use determined direct page id
        if ($table === 'tt_content') {
            $viewUrl = BackendUtility::viewOnClick($previewPageId, '', null, '', '', $additionalParameters);
        } elseif (!empty($pageTsConfig['options.']['workspaces.']['previewPageId.'][$table]) || !empty($pageTsConfig['options.']['workspaces.']['previewPageId'])) {
            // Analyze Page TSconfig options.workspaces.previewPageId
            if (!empty($pageTsConfig['options.']['workspaces.']['previewPageId.'][$table])) {
                $previewConfiguration = $pageTsConfig['options.']['workspaces.']['previewPageId.'][$table];
            } else {
                $previewConfiguration = $pageTsConfig['options.']['workspaces.']['previewPageId'];
            }
            // Extract possible settings (e.g. "field:pid")
            [$previewKey, $previewValue] = explode(':', $previewConfiguration, 2);
            if ($previewKey === 'field') {
                $previewPageId = (int)$liveRecord[$previewValue];
            } else {
                $previewPageId = (int)$previewConfiguration;
            }
            $viewUrl = BackendUtility::viewOnClick($previewPageId, '', null, '', '', $additionalParameters);
        } elseif (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['viewSingleRecord'])) {
            // Call user function to render the single record view
            $_params = [
                'table' => $table,
                'uid' => $uid,
                'record' => $liveRecord,
                'liveRecord' => $liveRecord,
                'versionRecord' => $versionRecord,
            ];
            $_funcRef = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['viewSingleRecord'];
            $null = null;
            $viewUrl = GeneralUtility::callUserFunction($_funcRef, $_params, $null);
        }

        return $viewUrl;
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
        $keyword = md5(uniqid(microtime(), true));
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_preview')
            ->insert(
                'sys_preview',
                [
                    'keyword' => $keyword,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'endtime' => $GLOBALS['EXEC_TIME'] + $ttl,
                    'config' => json_encode([
                        'fullWorkspace' => $workspaceId
                    ])
                ]
            );

        return $keyword;
    }

    /**
     * easy function to just return the number of hours
     * a preview link is valid, based on the TSconfig value "options.workspaces.previewLinkTTLHours"
     * by default, it's 48hs
     *
     * @return int The hours as a number
     */
    protected function getPreviewLinkLifetime(): int
    {
        $ttlHours = (int)($this->getBackendUser()->getTSConfig()['options.']['workspaces.']['previewLinkTTLHours'] ?? 0);
        return $ttlHours ?: 24 * 2;
    }

    /**
     * Find the Live-Uid for a given page,
     * the results are cached at run-time to avoid too many database-queries
     *
     * @throws \InvalidArgumentException
     * @param int $uid
     * @return int
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
     * @param int $pageId
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
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        $result = $queryBuilder->select('sys_language_uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->execute();

        while ($row = $result->fetch()) {
            $languageId = (int)$row['sys_language_uid'];
            // Only add links to active languages the user has access to
            if (isset($systemLanguages[$languageId]) && $this->getBackendUser()->checkLanguageAccess($languageId)) {
                $languageOptions[$languageId] = $systemLanguages[$languageId]['title'];
            }
        }

        return $languageOptions;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
