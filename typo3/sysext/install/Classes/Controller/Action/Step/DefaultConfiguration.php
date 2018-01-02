<?php
namespace TYPO3\CMS\Install\Controller\Action\Step;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Set production defaults
 */
class DefaultConfiguration extends AbstractStepAction
{
    /**
     * Set defaults of auto configuration, mark installation as completed
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function execute()
    {
        /** @var \TYPO3\CMS\Install\Configuration\FeatureManager $featureManager */
        $featureManager = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Configuration\FeatureManager::class);
        // Get best matching configuration presets
        $configurationValues = $featureManager->getBestMatchingConfigurationForAllFeatures();
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // let the admin user redirect to the distributions page on first login
        switch ($this->postValues['values']['sitesetup']) {
            // Update the admin backend user to show the distribution management on login
            case 'loaddistribution':
                $adminUserFirstLogin = [
                    'startModuleOnFirstLogin' => 'tools_ExtensionmanagerExtensionmanager->tx_extensionmanager_tools_extensionmanagerextensionmanager%5Baction%5D=distributions&tx_extensionmanager_tools_extensionmanagerextensionmanager%5Bcontroller%5D=List',
                    'ucSetByInstallTool' => '1',
                ];
                $connectionPool->getConnectionForTable('be_users')->update(
                    'be_users',
                    ['uc' => serialize($adminUserFirstLogin)],
                    ['admin' => 1]
                );
            break;

            // Create a page with UID 1 and PID1 and fluid_styled_content for page TS config, respect ownership
            case 'createsite':
                $databaseConnectionForPages = $connectionPool->getConnectionForTable('pages');
                $databaseConnectionForPages->insert(
                    'pages',
                    [
                        'pid' => 0,
                        'crdate' => time(),
                        'cruser_id' => 1,
                        'tstamp' => time(),
                        'title' => 'Home',
                        'doktype' => 1,
                        'is_siteroot' => 1,
                        'perms_userid' => 1,
                        'perms_groupid' => 1,
                        'perms_user' => 31,
                        'perms_group' => 31,
                        'perms_everybody' => 1
                    ]
                );
                $pageUid = $databaseConnectionForPages->lastInsertId('pages');

                // add a root sys_template with fluid_styled_content and a default PAGE typoscript snippet
                $connectionPool->getConnectionForTable('sys_template')->insert(
                    'sys_template',
                    [
                        'pid' => $pageUid,
                        'crdate' => time(),
                        'cruser_id' => 1,
                        'tstamp' => time(),
                        'title' => 'Main TypoScript Rendering',
                        'sitetitle' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                        'root' => 1,
                        'clear' => 1,
                        'include_static_file' => 'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:fluid_styled_content/Configuration/TypoScript/Styling/',
                        'constants' => '',
                        'config' => 'page = PAGE
page.10 = TEXT
page.10.value (
   <div style="width: 800px; margin: 15% auto;">
      <div style="width: 300px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 42"><path d="M60.2 14.4v27h-3.8v-27h-6.7v-3.3h17.1v3.3h-6.6zm20.2 12.9v14h-3.9v-14l-7.7-16.2h4.1l5.7 12.2 5.7-12.2h3.9l-7.8 16.2zm19.5 2.6h-3.6v11.4h-3.8V11.1s3.7-.3 7.3-.3c6.6 0 8.5 4.1 8.5 9.4 0 6.5-2.3 9.7-8.4 9.7m.4-16c-2.4 0-4.1.3-4.1.3v12.6h4.1c2.4 0 4.1-1.6 4.1-6.3 0-4.4-1-6.6-4.1-6.6m21.5 27.7c-7.1 0-9-5.2-9-15.8 0-10.2 1.9-15.1 9-15.1s9 4.9 9 15.1c.1 10.6-1.8 15.8-9 15.8m0-27.7c-3.9 0-5.2 2.6-5.2 12.1 0 9.3 1.3 12.4 5.2 12.4 3.9 0 5.2-3.1 5.2-12.4 0-9.4-1.3-12.1-5.2-12.1m19.9 27.7c-2.1 0-5.3-.6-5.7-.7v-3.1c1 .2 3.7.7 5.6.7 2.2 0 3.6-1.9 3.6-5.2 0-3.9-.6-6-3.7-6H138V24h3.1c3.5 0 3.7-3.6 3.7-5.3 0-3.4-1.1-4.8-3.2-4.8-1.9 0-4.1.5-5.3.7v-3.2c.5-.1 3-.7 5.2-.7 4.4 0 7 1.9 7 8.3 0 2.9-1 5.5-3.3 6.3 2.6.2 3.8 3.1 3.8 7.3 0 6.6-2.5 9-7.3 9"/><path fill="#FF8700" d="M31.7 28.8c-.6.2-1.1.2-1.7.2-5.2 0-12.9-18.2-12.9-24.3 0-2.2.5-3 1.3-3.6C12 1.9 4.3 4.2 1.9 7.2 1.3 8 1 9.1 1 10.6c0 9.5 10.1 31 17.3 31 3.3 0 8.8-5.4 13.4-12.8M28.4.5c6.6 0 13.2 1.1 13.2 4.8 0 7.6-4.8 16.7-7.2 16.7-4.4 0-9.9-12.1-9.9-18.2C24.5 1 25.6.5 28.4.5"/></svg>
      </div>
      <h4 style="font-family: sans-serif;">Welcome to a default website made with <a href="https://typo3.org">TYPO3</a></h4>
   </div>
)
page.100 =< styles.content.get',
                        'description' => 'This is an Empty Site Package TypoScript template.

For each website you need a TypoScript template on the main page of your website (on the top level). For better maintenance all TypoScript should be extracted into external files via <INCLUDE_TYPOSCRIPT: source="FILE:EXT:site_myproject/Configuration/TypoScript/setup.typoscript">.'
                    ]
                );
            break;
        }

        // Setting SYS/isInitialInstallationInProgress to FALSE marks this instance installation as complete
        $configurationValues['SYS/isInitialInstallationInProgress'] = false;

        // Mark upgrade wizards as done
        $this->loadExtLocalconfDatabaseAndExtTables();
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'])) {
            $registry = GeneralUtility::makeInstance(Registry::class);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $updateClassName) {
                $registry->set('installUpdate', $updateClassName, 1);
            }
        }

        /** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationValues);

        /** @var \TYPO3\CMS\Install\Service\SessionService $session */
        $session = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\SessionService::class);
        $session->destroySession();

        /** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
        $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
            \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class
        );
        $formProtection->clean();

        if (EnableFileService::installToolEnableFileExists() && !EnableFileService::isInstallToolEnableFilePermanent()) {
            EnableFileService::removeInstallToolEnableFile();
        }

        \TYPO3\CMS\Core\Utility\HttpUtility::redirect(GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'index.php', \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303);
    }

    /**
     * Step needs to be executed if 'isInitialInstallationInProgress' is set to TRUE in LocalConfiguration
     *
     * @return bool
     */
    public function needsExecution()
    {
        $result = false;
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['isInitialInstallationInProgress'])
            && $GLOBALS['TYPO3_CONF_VARS']['SYS']['isInitialInstallationInProgress'] === true
        ) {
            $result = true;
        }
        return $result;
    }

    /**
     * Executes the step
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        $this->assignSteps();
        $this->view->assign('composerMode', Bootstrap::usesComposerClassLoading());
        return $this->view->render();
    }
}
