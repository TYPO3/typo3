<?php
namespace TYPO3\CMS\Viewpage\Controller;

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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Controller for viewing the frontend
 */
class ViewModuleController extends ActionController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $this->registerDocHeader();
    }

    /**
     * Registers the docheader
     */
    protected function registerDocHeader()
    {
        $languages = $this->getPreviewLanguages();
        if (count($languages) > 1) {
            $languageMenu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('_langSelector');
            $languageUid = $this->getCurrentLanguage();
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            foreach ($languages as $value => $label) {
                $href = (string)$uriBuilder->buildUriFromRoute(
                    'web_ViewpageView',
                    [
                        'id' => (int)GeneralUtility::_GP('id'),
                        'language' => (int)$value
                    ]
                );
                $menuItem = $languageMenu->makeMenuItem()
                    ->setTitle($label)
                    ->setHref($href);
                if ($languageUid === (int)$value) {
                    $menuItem->setActive(true);
                }
                $languageMenu->addMenuItem($menuItem);
            }
            $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        }

        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $showButton = $buttonBar->makeLinkButton()
            ->setHref($this->getTargetUrl())
            ->setOnClick('window.open(this.href, \'newTYPO3frontendWindow\').focus();return false;')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-page', Icon::SIZE_SMALL));
        $buttonBar->addButton($showButton);

        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref('javascript:document.getElementById(\'tx_viewpage_iframe\').contentWindow.location.reload(true);')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:refreshPage'))
            ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);

        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();
        $extensionName = $currentRequest->getControllerExtensionName();
        if (count($getVars) === 0) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars = ['id', 'route', $modulePrefix];
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
    }

    /**
     * Gets called before each action
     */
    public function initializeAction()
    {
        $this->getLanguageService()->includeLLFile('EXT:viewpage/Resources/Private/Language/locallang.xlf');
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addInlineLanguageLabelFile('EXT:viewpage/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Show selected page from pagetree in iframe
     */
    public function showAction()
    {
        $this->view->getModuleTemplate()->setBodyTag('<body class="typo3-module-viewpage">');
        $this->view->getModuleTemplate()->setModuleName('typo3-module-viewpage');
        $this->view->getModuleTemplate()->setModuleId('typo3-module-viewpage');

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icons = [];
        $icons['orientation'] = $iconFactory->getIcon('actions-device-orientation-change', Icon::SIZE_SMALL)->render('inline');
        $icons['fullscreen'] = $iconFactory->getIcon('actions-fullscreen', Icon::SIZE_SMALL)->render('inline');
        $icons['expand'] = $iconFactory->getIcon('actions-expand', Icon::SIZE_SMALL)->render('inline');
        $icons['desktop'] = $iconFactory->getIcon('actions-device-desktop', Icon::SIZE_SMALL)->render('inline');
        $icons['tablet'] = $iconFactory->getIcon('actions-device-tablet', Icon::SIZE_SMALL)->render('inline');
        $icons['mobile'] = $iconFactory->getIcon('actions-device-mobile', Icon::SIZE_SMALL)->render('inline');
        $icons['unidentified'] = $iconFactory->getIcon('actions-device-unidentified', Icon::SIZE_SMALL)->render('inline');

        $current = ($this->getBackendUser()->uc['moduleData']['web_view']['States']['current'] ?: []);
        $current['label'] = (isset($current['label']) ? $current['label'] : $this->getLanguageService()->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:custom'));
        $current['width'] = (isset($current['width']) && (int) $current['width'] >= 300 ? (int) $current['width'] : 320);
        $current['height'] = (isset($current['height']) && (int) $current['height'] >= 300 ? (int) $current['height'] : 480);

        $custom = ($this->getBackendUser()->uc['moduleData']['web_view']['States']['custom'] ?: []);
        $custom['width'] = (isset($current['custom']) && (int) $current['custom'] >= 300 ? (int) $current['custom'] : 320);
        $custom['height'] = (isset($current['custom']) && (int) $current['custom'] >= 300 ? (int) $current['custom'] : 480);

        $this->view->assign('icons', $icons);
        $this->view->assign('current', $current);
        $this->view->assign('custom', $custom);
        $this->view->assign('presetGroups', $this->getPreviewPresets());
        $this->view->assign('url', $this->getTargetUrl());
    }

    /**
     * Determine the url to view
     *
     * @return string
     */
    protected function getTargetUrl()
    {
        $pageIdToShow = (int)GeneralUtility::_GP('id');

        $permissionClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRecord = BackendUtility::readPageAccess($pageIdToShow, $permissionClause);
        if ($pageRecord) {
            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($pageRecord);

            $adminCommand = $this->getAdminCommand($pageIdToShow);
            $domainName = $this->getDomainName($pageIdToShow);
            $languageParameter = $this->getLanguageParameter();
            // Mount point overlay: Set new target page id and mp parameter
            /** @var \TYPO3\CMS\Frontend\Page\PageRepository $sysPage */
            $sysPage = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
            $sysPage->init(false);
            $mountPointMpParameter = '';
            $finalPageIdToShow = $pageIdToShow;
            $mountPointInformation = $sysPage->getMountPointInfo($pageIdToShow);
            if ($mountPointInformation && $mountPointInformation['overlay']) {
                // New page id
                $finalPageIdToShow = $mountPointInformation['mount_pid'];
                $mountPointMpParameter = '&MP=' . $mountPointInformation['MPvar'];
            }
            // Modify relative path to protocol with host if domain record is given
            $protocolAndHost = '..';
            if ($domainName) {
                // TCEMAIN.previewDomain can contain the protocol, check prevents double protocol URLs
                if (strpos($domainName, '://') !== false) {
                    $protocolAndHost = $domainName;
                } else {
                    $protocol = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https' : 'http';
                    $protocolAndHost = $protocol . '://' . $domainName;
                }
            }
            return $protocolAndHost . '/index.php?id=' . $finalPageIdToShow . $this->getTypeParameterIfSet($finalPageIdToShow) . $mountPointMpParameter . $adminCommand . $languageParameter;
        }
        return '#';
    }

    /**
     * Get admin command
     *
     * @param int $pageId
     * @return string
     */
    protected function getAdminCommand($pageId)
    {
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $pageinfo = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        $addCommand = '';
        if (is_array($pageinfo)) {
            $addCommand = '&ADMCMD_editIcons=1' . BackendUtility::ADMCMD_previewCmds($pageinfo);
        }
        return $addCommand;
    }

    /**
     * With page TS config it is possible to force a specific type id via mod.web_view.type
     * for a page id or a page tree.
     * The method checks if a type is set for the given id and returns the additional GET string.
     *
     * @param int $pageId
     * @return string
     */
    protected function getTypeParameterIfSet($pageId)
    {
        $typeParameter = '';
        $modTSconfig = BackendUtility::getModTSconfig($pageId, 'mod.web_view');
        $typeId = (int)$modTSconfig['properties']['type'];
        if ($typeId > 0) {
            $typeParameter = '&type=' . $typeId;
        }
        return $typeParameter;
    }

    /**
     * Get domain name for requested page id
     *
     * @param int $pageId
     * @return string|null Domain name from first sys_domains-Record or from TCEMAIN.previewDomain, NULL if neither is configured
     */
    protected function getDomainName($pageId)
    {
        $previewDomainConfig = $this->getBackendUser()->getTSConfig('TCEMAIN.previewDomain', BackendUtility::getPagesTSconfig($pageId));
        if ($previewDomainConfig['value']) {
            $domain = $previewDomainConfig['value'];
        } else {
            $domain = BackendUtility::firstDomainRecord(BackendUtility::BEgetRootLine($pageId));
        }
        return $domain;
    }

    /**
     * Get available presets for preview frame
     *
     * @return array
     */
    protected function getPreviewPresets()
    {
        $pageId = (int)GeneralUtility::_GP('id');
        $modTSconfig = BackendUtility::getModTSconfig($pageId, 'mod.web_view');
        $presetGroups = [
            'desktop' => [],
            'tablet' => [],
            'mobile' => [],
            'unidentified' => []
        ];
        if (is_array($modTSconfig['properties']['previewFrameWidths.'])) {
            foreach ($modTSconfig['properties']['previewFrameWidths.'] as $item => $conf) {
                $data = [
                    'key' => substr($item, 0, -1),
                    'label' => (isset($conf['label']) ? $conf['label'] : null),
                    'type' => (isset($conf['type']) ? $conf['type'] : 'unknown'),
                    'width' => ((isset($conf['width']) && (int) $conf['width'] > 0 && strpos($conf['width'], '%') === false) ? (int) $conf['width'] : null),
                    'height' => ((isset($conf['height']) && (int) $conf['height'] > 0 && strpos($conf['height'], '%') === false) ? (int) $conf['height'] : null),
                ];
                $width = (int) substr($item, 0, -1);
                if (!isset($data['width']) && $width > 0) {
                    $data['width'] = $width;
                }
                if (!isset($data['label'])) {
                    $data['label'] = $data['key'];
                } elseif (strpos($data['label'], 'LLL:') === 0) {
                    $data['label'] = $this->getLanguageService()->sL(trim($data['label']));
                }

                if (array_key_exists($data['type'], $presetGroups)) {
                    $presetGroups[$data['type']][$data['key']] = $data;
                } else {
                    $presetGroups['unidentified'][$data['key']] = $data;
                }
            }
        }

        return $presetGroups;
    }

    /**
     * Returns the preview languages
     *
     * @return array
     */
    protected function getPreviewLanguages()
    {
        $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $pageIdToShow = (int)GeneralUtility::_GP('id');
        $modSharedTSconfig = BackendUtility::getModTSconfig($pageIdToShow, 'mod.SHARED');
        if ($modSharedTSconfig['properties']['view.']['disableLanguageSelector'] === '1') {
            return [];
        }
        $languages = [
            0 => isset($modSharedTSconfig['properties']['defaultLanguageLabel'])
                    ? $modSharedTSconfig['properties']['defaultLanguageLabel'] . ' (' . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage') . ')'
                    : $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage')
        ];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        if (!$this->getBackendUser()->isAdmin()) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }

        $result = $queryBuilder->select('sys_language.uid', 'sys_language.title')
            ->from('sys_language')
            ->join(
                'sys_language',
                'pages',
                'o',
                $queryBuilder->expr()->eq('o.' . $languageField, $queryBuilder->quoteIdentifier('sys_language.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'o.' . $localizationParentField,
                    $queryBuilder->createNamedParameter($pageIdToShow, \PDO::PARAM_INT)
                )
            )
            ->groupBy('sys_language.uid', 'sys_language.title', 'sys_language.sorting')
            ->orderBy('sys_language.sorting')
            ->execute();

        while ($row = $result->fetch()) {
            if ($this->getBackendUser()->checkLanguageAccess($row['uid'])) {
                $languages[$row['uid']] = $row['title'];
            }
        }
        return $languages;
    }

    /**
     * Returns the current language
     *
     * @return string
     */
    protected function getCurrentLanguage()
    {
        $languageUid = GeneralUtility::_GP('language');
        if ($languageUid === null) {
            $states = $this->getBackendUser()->uc['moduleData']['web_view']['States'];
            $languages = $this->getPreviewLanguages();
            if (isset($states['languageSelectorValue']) && isset($languages[$states['languageSelectorValue']])) {
                $languageUid = $states['languageSelectorValue'];
            }
        } else {
            $this->getBackendUser()->uc['moduleData']['web_view']['States']['languageSelectorValue'] = (int)$languageUid;
            $this->getBackendUser()->writeUC($this->getBackendUser()->uc);
        }
        return (int)$languageUid;
    }

    /**
     * Gets the L parameter from the user session
     *
     * @return string
     */
    protected function getLanguageParameter()
    {
        $languageParameter = '';
        $languageUid = $this->getCurrentLanguage();
        if ($languageUid) {
            $languageParameter = '&L=' . $languageUid;
        }
        return $languageParameter;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
