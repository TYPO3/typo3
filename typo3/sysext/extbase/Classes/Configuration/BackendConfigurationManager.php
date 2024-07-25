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

namespace TYPO3\CMS\Extbase\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Utility\FrontendSimulatorUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Load TypoScript of a page in backend mode.
 *
 * Extbase Backend modules can be configured with Frontend TypoScript. This is of course a very
 * bad thing, but it is how it is ^^ (we'll get rid of this at some point, promised!)
 *
 * First, this means Backend extbase module performance scales with the amount of Frontend
 * TypoScript. Furthermore, in contrast to Frontend, Backend Modules are not necessarily bound to
 * pages in the first place - they may not have a page tree und thus no page id at all, like
 * for instance the ext:beuser module.
 *
 * Unfortunately, extbase *still* has to calculate *some* TypoScript in any case, even if there
 * is no page id at all: The default configuration of extbase Backend modules is the "module."
 * TypoScript setup top-level key. The base config of this is delivered by extbase extensions that
 * have Backend Modules using ext_typoscript_setup.typoscript, and/or via TYPO3_CONF_VARS TypoScript
 * setup defaults. Those have to be loaded in any case, even if there is no page at all in the page
 * tree.
 *
 * The code thus has to hop through quite some loops to "find" some relevant page id it can guess
 * if none is incoming from the request. It even fakes a default sys_template row to trigger
 * TypoScript loading of globals and ext_typoscript_setup.typoscript if it couldn't find anything.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class BackendConfigurationManager
{
    public function __construct(
        private TypoScriptService $typoScriptService,
        #[Autowire(service: 'cache.typoscript')]
        private PhpFrontend $typoScriptCache,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
        private SysTemplateRepository $sysTemplateRepository,
        private SiteFinder $siteFinder,
        private FrontendTypoScriptFactory $frontendTypoScriptFactory,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * Loads the Extbase Framework configuration.
     *
     * The Extbase framework configuration HAS TO be retrieved using this method, as they are come from different places than the normal settings.
     * Framework configuration is, in contrast to normal settings, needed for the Extbase framework to operate correctly.
     *
     * @param array $configuration low level configuration from outside, typically ContentObjectRenderer TypoScript element config
     * @param string|null $extensionName if specified, the configuration for the given extension will be returned (plugin.tx_extensionname)
     * @param string|null $pluginName if specified, the configuration for the given plugin will be returned (plugin.tx_extensionname_pluginname)
     * @return array the Extbase framework configuration
     */
    public function getConfiguration(ServerRequestInterface $request, array $configuration, ?string $extensionName = null, ?string $pluginName = null): array
    {
        $extensionNameFromConfig = $configuration['extensionName'] ?? null;
        $pluginNameFromConfig = $configuration['pluginName'] ?? null;
        $configuration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($configuration);

        $frameworkConfiguration = $this->getExtbaseConfiguration($request);
        if (!isset($frameworkConfiguration['persistence']['storagePid'])) {
            $currentPageId = $this->getCachedCurrentPageId($request);
            $frameworkConfiguration['persistence']['storagePid'] = $currentPageId;
        }
        // only merge $configuration and override controller configuration when retrieving configuration of the current plugin
        if ($extensionName === null || $extensionName === $extensionNameFromConfig && $pluginName === $pluginNameFromConfig) {
            $pluginConfiguration = $this->getPluginConfiguration($request, (string)$extensionNameFromConfig, (string)$pluginNameFromConfig);
            ArrayUtility::mergeRecursiveWithOverrule($pluginConfiguration, $configuration);
            $pluginConfiguration['controllerConfiguration'] = [];
        } else {
            $pluginConfiguration = $this->getPluginConfiguration($request, $extensionName, (string)$pluginName);
            $pluginConfiguration['controllerConfiguration'] = [];
        }
        ArrayUtility::mergeRecursiveWithOverrule($frameworkConfiguration, $pluginConfiguration);

        if (!empty($frameworkConfiguration['persistence']['storagePid'])) {
            if (is_array($frameworkConfiguration['persistence']['storagePid'])) {
                // We simulate the frontend to enable the use of cObjects in
                // stdWrap. We then convert the configuration to normal TypoScript
                // and apply the stdWrap to the storagePid
                // Use makeInstance here since extbase Bootstrap always setContentObject(null) in Backend, no need to call getContentObject().
                $conf = $this->typoScriptService->convertPlainArrayToTypoScriptArray($frameworkConfiguration['persistence']);
                FrontendSimulatorUtility::simulateFrontendEnvironment(GeneralUtility::makeInstance(ContentObjectRenderer::class));
                $frameworkConfiguration['persistence']['storagePid'] = $GLOBALS['TSFE']->cObj->stdWrapValue('storagePid', $conf);
                FrontendSimulatorUtility::resetFrontendEnvironment();
            }

            if (!empty($frameworkConfiguration['persistence']['recursive'])) {
                $storagePids = $this->getRecursiveStoragePids(
                    GeneralUtility::intExplode(',', (string)($frameworkConfiguration['persistence']['storagePid'] ?? '')),
                    (int)$frameworkConfiguration['persistence']['recursive']
                );
                $frameworkConfiguration['persistence']['storagePid'] = implode(',', $storagePids);
            }
        }
        return $frameworkConfiguration;
    }

    /**
     * Returns TypoScript Setup array from current Environment.
     *
     * @return array the raw TypoScript setup
     */
    public function getTypoScriptSetup(ServerRequestInterface $request): array
    {
        $currentPageId = $this->getCachedCurrentPageId($request);

        $cacheIdentifier = 'extbase-backend-typoscript-pageId-' . $currentPageId;
        $setupArray = $this->runtimeCache->get($cacheIdentifier);
        if (is_array($setupArray)) {
            return $setupArray;
        }

        $site = $request->getAttribute('site');
        if (($site === null || $site instanceof NullSite) && $currentPageId > 0) {
            // Due to the weird magic of getting the pid of the first root template when
            // not having a pageId (extbase BE modules without page tree / no page selected),
            // we also have no proper site in this case.
            // So we try to get the site for this pageId. This way, site settings for this
            // first TS page are turned into constants and can be used in setup and setup
            // conditions.
            try {
                $site = $this->siteFinder->getSiteByPageId($currentPageId);
            } catch (SiteNotFoundException) {
                // Keep null / NullSite when no site could be determined for whatever reason.
            }
        }
        if ($site === null) {
            // If still no site object, have NullSite (usually pid 0).
            $site = new NullSite();
        }

        $rootLine = [];
        $sysTemplateFakeRow = [
            'uid' => 0,
            'pid' => 0,
            'title' => 'Fake sys_template row to force extension statics loading',
            'root' => 1,
            'clear' => 3,
            'include_static_file' => '',
            'basedOn' => '',
            'includeStaticAfterBasedOn' => 0,
            'static_file_mode' => false,
            'constants' => '',
            'config' => '',
            'deleted' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'sorting' => 0,
        ];
        if ($currentPageId > 0) {
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $currentPageId)->get();
            $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootline($rootLine, $request);
            ksort($rootLine);
        }
        if (empty($sysTemplateRows)) {
            // If there is no page (pid 0 only), or if the first 'is_siteroot' site has no sys_template record,
            // then we "fake" a sys_template row: This triggers inclusion of 'global' and 'extension static' TypoScript.
            $sysTemplateRows[] = $sysTemplateFakeRow;
        }

        $expressionMatcherVariables = [
            'request' => $request,
            'pageId' => $currentPageId,
            'page' => !empty($rootLine) ? $rootLine[array_key_first($rootLine)] : [],
            'fullRootLine' => $rootLine,
            'site' => $site,
        ];

        $typoScript = $this->frontendTypoScriptFactory->createSettingsAndSetupConditions($site, $sysTemplateRows, $expressionMatcherVariables, $this->typoScriptCache);
        $typoScript = $this->frontendTypoScriptFactory->createSetupConfigOrFullSetup(true, $typoScript, $site, $sysTemplateRows, $expressionMatcherVariables, '0', $this->typoScriptCache, null);
        $setupArray = $typoScript->getSetupArray();
        $this->runtimeCache->set($cacheIdentifier, $setupArray);
        return $setupArray;
    }

    /**
     * Returns the TypoScript configuration found in config.tx_extbase
     */
    private function getExtbaseConfiguration(ServerRequestInterface $request): array
    {
        $setup = $this->getTypoScriptSetup($request);
        $extbaseConfiguration = [];
        if (isset($setup['config.']['tx_extbase.'])) {
            $extbaseConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['config.']['tx_extbase.']);
        }
        return $extbaseConfiguration;
    }

    /**
     * Returns the TypoScript configuration found in module.tx_yourextension_yourmodule
     * merged with the global configuration of your extension from module.tx_yourextension
     *
     * @param string|null $pluginName in BE mode this is actually the module signature. But we're using it just like the plugin name in FE
     */
    private function getPluginConfiguration(ServerRequestInterface $request, string $extensionName, ?string $pluginName = null): array
    {
        $setup = $this->getTypoScriptSetup($request);
        $pluginConfiguration = [];
        if (is_array($setup['module.']['tx_' . strtolower($extensionName) . '.'] ?? false)) {
            $pluginConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['module.']['tx_' . strtolower($extensionName) . '.']);
        }
        if ($pluginName !== null) {
            $pluginSignature = strtolower($extensionName . '_' . $pluginName);
            if (is_array($setup['module.']['tx_' . $pluginSignature . '.'] ?? false)) {
                $overruleConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['module.']['tx_' . $pluginSignature . '.']);
                ArrayUtility::mergeRecursiveWithOverrule($pluginConfiguration, $overruleConfiguration);
            }
        }
        return $pluginConfiguration;
    }

    private function getCachedCurrentPageId(ServerRequestInterface $request): int
    {
        $currentPageId = $this->runtimeCache->get('extbase-backend-typoscript-currentPageId');
        if (!is_int($currentPageId)) {
            $currentPageId = $this->getCurrentPageId($request);
            $this->runtimeCache->set('extbase-backend-typoscript-currentPageId', $currentPageId);
        }
        return $currentPageId;
    }

    /**
     * The full madness to guess a page id:
     * - First try to get one from the request, accessing POST / GET 'id'
     * - else, fetch the first page in page tree that has 'is_siteroot' set
     * - else, fetch the first sys_template record that has 'root' flag set, and use its pid
     * - else, 0, indicating "there are no 'is_siteroot' pages and no sys_template 'root' records"
     *
     * @return int current page id. If no page is selected current root page id is returned
     */
    private function getCurrentPageId(ServerRequestInterface $request): int
    {
        $currentPageId = $this->getCurrentPageIdFromRequest($request);
        $currentPageId = $currentPageId ?: $this->getCurrentPageIdFromCurrentSiteRoot();
        $currentPageId = $currentPageId ?: $this->getCurrentPageIdFromRootTemplate();
        return $currentPageId ?: 0;
    }

    /**
     * Gets the current page ID from the GET/POST data.
     *
     * @return int the page UID, will be 0 if none has been set
     */
    private function getCurrentPageIdFromRequest(ServerRequestInterface $request): int
    {
        // @todo: This misuses 'id' as a broken convention for pages-uid. The filelist module for instance
        //        uses 'id' as "storage-uid:path", which is only mitigated here by testing the argument
        //        with MU:canBeInterpretedAsInteger().
        //        This is in-line with a similar misuse in BackendModuleValidator.
        $id = 0;
        $potentialId = $request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0;
        if (MathUtility::canBeInterpretedAsInteger($potentialId) && $potentialId > 0) {
            $id = (int)$potentialId;
        }
        return $id;
    }

    /**
     * Gets the current page ID from the first site root in tree.
     *
     * @return int the page UID, will be 0 if none has been set
     */
    private function getCurrentPageIdFromCurrentSiteRoot(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $rootPage = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('is_siteroot', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                // Only consider live root page IDs, never return a versioned root page ID
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->orderBy('sorting')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if (empty($rootPage)) {
            return 0;
        }
        return (int)$rootPage['uid'];
    }

    /**
     * Gets the current page ID from the first created root template.
     *
     * @return int the page UID, will be 0 if none has been set
     */
    private function getCurrentPageIdFromRootTemplate(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $rootTemplate = $queryBuilder
            ->select('pid')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->eq('root', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->orderBy('crdate')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if (empty($rootTemplate)) {
            return 0;
        }
        return (int)$rootTemplate['pid'];
    }

    /**
     * Returns an array of storagePIDs that are below a list of storage pids.
     *
     * @param int[] $storagePids Storage PIDs to start at; multiple PIDs possible as comma-separated list
     * @param int $recursionDepth Maximum number of levels to search, 0 to disable recursive lookup
     * @return int[] Uid list including the start $storagePids
     */
    private function getRecursiveStoragePids(array $storagePids, int $recursionDepth = 0): array
    {
        if ($recursionDepth <= 0) {
            return $storagePids;
        }
        $permsClause = QueryHelper::stripLogicalOperatorPrefix(
            $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
        );
        $recursiveStoragePids = [];
        foreach ($storagePids as $startPid) {
            $startPid = abs($startPid);
            $recursiveStoragePids = array_merge(
                $recursiveStoragePids,
                [ $startPid ],
                $this->getPageChildrenRecursive($startPid, $recursionDepth, 0, $permsClause)
            );
        }
        return array_unique($recursiveStoragePids);
    }

    /**
     * Recursively fetch all children of a given page
     *
     * @param int $pid uid of the page
     * @return int[] List of child row $uid's
     */
    private function getPageChildrenRecursive(int $pid, int $depth, int $begin, string $permsClause): array
    {
        $children = [];
        if ($pid && $depth > 0) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $statement = $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0),
                    $permsClause
                )
                ->orderBy('uid')
                ->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $children[] = (int)$row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = $this->getPageChildrenRecursive((int)$row['uid'], $depth - 1, $begin - 1, $permsClause);
                    $children = array_merge($children, $theSubList);
                }
            }
        }
        return $children;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
