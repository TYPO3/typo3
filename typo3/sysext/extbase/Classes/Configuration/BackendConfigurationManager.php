<?php
namespace TYPO3\CMS\Extbase\Configuration;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\BackendRequestHandler;
use TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * A general purpose configuration manager used in backend mode.
 */
class BackendConfigurationManager extends AbstractConfigurationManager
{
    /**
     * @var array
     */
    protected $typoScriptSetupCache = [];

    /**
     * stores the current page ID
     * @var int
     */
    protected $currentPageId;

    /**
     * Returns TypoScript Setup array from current Environment.
     *
     * @return array the raw TypoScript setup
     */
    public function getTypoScriptSetup()
    {
        $pageId = $this->getCurrentPageId();

        if (!array_key_exists($pageId, $this->typoScriptSetupCache)) {
            /** @var $template TemplateService */
            $template = GeneralUtility::makeInstance(TemplateService::class);
            // do not log time-performance information
            $template->tt_track = 0;
            // Explicitly trigger processing of extension static files
            $template->setProcessExtensionStatics(true);
            $template->init();
            // Get the root line
            $rootline = [];
            if ($pageId > 0) {
                /** @var $sysPage PageRepository */
                $sysPage = GeneralUtility::makeInstance(PageRepository::class);
                // Get the rootline for the current page
                $rootline = $sysPage->getRootLine($pageId, '', true);
            }
            // This generates the constants/config + hierarchy info for the template.
            $template->runThroughTemplates($rootline, 0);
            $template->generateConfig();
            $this->typoScriptSetupCache[$pageId] = $template->setup;
        }
        return $this->typoScriptSetupCache[$pageId];
    }

    /**
     * Returns the TypoScript configuration found in module.tx_yourextension_yourmodule
     * merged with the global configuration of your extension from module.tx_yourextension
     *
     * @param string $extensionName
     * @param string $pluginName in BE mode this is actually the module signature. But we're using it just like the plugin name in FE
     * @return array
     */
    protected function getPluginConfiguration($extensionName, $pluginName = null)
    {
        $setup = $this->getTypoScriptSetup();
        $pluginConfiguration = [];
        if (is_array($setup['module.']['tx_' . strtolower($extensionName) . '.'])) {
            $pluginConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['module.']['tx_' . strtolower($extensionName) . '.']);
        }
        if ($pluginName !== null) {
            $pluginSignature = strtolower($extensionName . '_' . $pluginName);
            if (is_array($setup['module.']['tx_' . $pluginSignature . '.'])) {
                $overruleConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['module.']['tx_' . $pluginSignature . '.']);
                ArrayUtility::mergeRecursiveWithOverrule($pluginConfiguration, $overruleConfiguration);
            }
        }
        return $pluginConfiguration;
    }

    /**
     * Returns the configured controller/action pairs of the specified module in the format
     * array(
     * 'Controller1' => array('action1', 'action2'),
     * 'Controller2' => array('action3', 'action4')
     * )
     *
     * @param string $extensionName
     * @param string $pluginName in BE mode this is actually the module signature. But we're using it just like the plugin name in FE
     * @return array
     */
    protected function getSwitchableControllerActions($extensionName, $pluginName)
    {
        $switchableControllerActions = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers'];
        if (!is_array($switchableControllerActions)) {
            $switchableControllerActions = [];
        }
        return $switchableControllerActions;
    }

    /**
     * Returns the page uid of the current page.
     * If no page is selected, we'll return the uid of the first root page.
     *
     * @return int current page id. If no page is selected current root page id is returned
     */
    protected function getCurrentPageId()
    {
        if ($this->currentPageId !== null) {
            return $this->currentPageId;
        }

        $this->currentPageId = $this->getCurrentPageIdFromGetPostData() ?: $this->getCurrentPageIdFromCurrentSiteRoot();
        $this->currentPageId = $this->currentPageId ?: $this->getCurrentPageIdFromRootTemplate();
        $this->currentPageId = $this->currentPageId ?: self::DEFAULT_BACKEND_STORAGE_PID;

        return $this->currentPageId;
    }

    /**
     * Gets the current page ID from the GET/POST data.
     *
     * @return int the page UID, will be 0 if none has been set
     */
    protected function getCurrentPageIdFromGetPostData()
    {
        return (int)GeneralUtility::_GP('id');
    }

    /**
     * Gets the current page ID from the first site root in tree.
     *
     * @return int the page UID, will be 0 if none has been set
     */
    protected function getCurrentPageIdFromCurrentSiteRoot()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');

        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

        $rootPage = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('is_siteroot', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->orderBy('sorting')
            ->execute()
            ->fetch();

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
    protected function getCurrentPageIdFromRootTemplate()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_template');

        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

        $rootTemplate = $queryBuilder
            ->select('pid')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->eq('root', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            )
            ->orderBy('crdate')
            ->execute()
            ->fetch();

        if (empty($rootTemplate)) {
            return 0;
        }

        return (int)$rootTemplate['pid'];
    }

    /**
     * Returns the default backend storage pid
     *
     * @return string
     */
    public function getDefaultBackendStoragePid()
    {
        return $this->getCurrentPageId();
    }

    /**
     * We need to set some default request handler if the framework configuration
     * could not be loaded; to make sure Extbase also works in Backend modules
     * in all contexts.
     *
     * @param array $frameworkConfiguration
     * @return array
     */
    protected function getContextSpecificFrameworkConfiguration(array $frameworkConfiguration)
    {
        if (!isset($frameworkConfiguration['mvc']['requestHandlers'])) {
            $frameworkConfiguration['mvc']['requestHandlers'] = [
                FrontendRequestHandler::class => FrontendRequestHandler::class,
                BackendRequestHandler::class => BackendRequestHandler::class
            ];
        }
        return $frameworkConfiguration;
    }

    /**
     * Returns a comma separated list of storagePid that are below a certain storage pid.
     *
     * @param string $storagePid Storage PID to start at; multiple PIDs possible as comma-separated list
     * @param int $recursionDepth Maximum number of levels to search, 0 to disable recursive lookup
     * @return string storage PIDs
     */
    protected function getRecursiveStoragePids($storagePid, $recursionDepth = 0)
    {
        if ($recursionDepth <= 0) {
            return $storagePid;
        }

        $recursiveStoragePids = '';
        $storagePids = GeneralUtility::intExplode(',', $storagePid);
        $permsClause = $this->getBackendUser()->getPagePermsClause(1);
        $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
        foreach ($storagePids as $startPid) {
            $pids = $queryGenerator->getTreeList($startPid, $recursionDepth, 0, $permsClause);
            if ((string)$pids !== '') {
                $recursiveStoragePids .= $pids . ',';
            }
        }

        return rtrim($recursiveStoragePids, ',');
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
