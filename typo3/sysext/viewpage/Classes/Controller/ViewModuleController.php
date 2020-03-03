<?php
declare(strict_types = 1);
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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Controller for viewing the frontend
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class ViewModuleController
{
    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * View
     *
     * @var ViewInterface
     */
    protected $view;

    /**
     * Initialize module template and language service
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:viewpage/Resources/Private/Language/locallang.xlf');
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addInlineLanguageLabelFile('EXT:viewpage/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Initialize view
     *
     * @param string $templateName
     */
    protected function initializeView(string $templateName)
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getRequest()->setControllerExtensionName('Viewpage');
        $this->view->setTemplate($templateName);
        $this->view->setTemplateRootPaths(['EXT:viewpage/Resources/Private/Templates/ViewModule']);
        $this->view->setPartialRootPaths(['EXT:viewpage/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:viewpage/Resources/Private/Layouts']);
    }

    /**
     * Registers the docheader
     *
     * @param int $pageId
     * @param int $languageId
     * @param string $targetUrl
     */
    protected function registerDocHeader(int $pageId, int $languageId, string $targetUrl)
    {
        $languages = $this->getPreviewLanguages($pageId);
        if (count($languages) > 1) {
            $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('_langSelector');
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            foreach ($languages as $value => $label) {
                $href = (string)$uriBuilder->buildUriFromRoute(
                    'web_ViewpageView',
                    [
                        'id' => $pageId,
                        'language' => (int)$value
                    ]
                );
                $menuItem = $languageMenu->makeMenuItem()
                    ->setTitle($label)
                    ->setHref($href);
                if ($languageId === (int)$value) {
                    $menuItem->setActive(true);
                }
                $languageMenu->addMenuItem($menuItem);
            }
            $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        }

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $showButton = $buttonBar->makeLinkButton()
            ->setHref($targetUrl)
            ->setOnClick('window.open(this.href, \'newTYPO3frontendWindow\').focus();return false;')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-page', Icon::SIZE_SMALL));
        $buttonBar->addButton($showButton);

        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref('javascript:document.getElementById(\'tx_viewpage_iframe\').contentWindow.location.reload(true);')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:refreshPage'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);

        // Shortcut
        $mayMakeShortcut = $this->getBackendUser()->mayMakeShortcut();
        if ($mayMakeShortcut) {
            $getVars = ['id', 'route'];

            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName('web_ViewpageView')
                ->setGetVariables($getVars);
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }

    /**
     * Show selected page from pagetree in iframe
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function showAction(ServerRequestInterface $request): ResponseInterface
    {
        $pageId = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);

        $this->initializeView('show');
        $this->moduleTemplate->setBodyTag('<body class="typo3-module-viewpage">');
        $this->moduleTemplate->setModuleName('typo3-module-viewpage');
        $this->moduleTemplate->setModuleId('typo3-module-viewpage');

        if (!$this->isValidDoktype($pageId)) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->getLL('noValidPageSelected'),
                '',
                FlashMessage::INFO
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        } else {
            $languageId = $this->getCurrentLanguage($pageId, $request->getParsedBody()['language'] ?? $request->getQueryParams()['language'] ?? null);
            $targetUrl = BackendUtility::getPreviewUrl(
                $pageId,
                '',
                null,
                '',
                '',
                $this->getTypeParameterIfSet($pageId) . '&L=' . $languageId
            );
            $this->registerDocHeader($pageId, $languageId, $targetUrl);

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
            $current['label'] = ($current['label'] ?? $this->getLanguageService()->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:custom'));
            $current['width'] = (isset($current['width']) && (int)$current['width'] >= 300 ? (int)$current['width'] : 320);
            $current['height'] = (isset($current['height']) && (int)$current['height'] >= 300 ? (int)$current['height'] : 480);

            $custom = ($this->getBackendUser()->uc['moduleData']['web_view']['States']['custom'] ?: []);
            $custom['width'] = (isset($current['custom']) && (int)$current['custom'] >= 300 ? (int)$current['custom'] : 320);
            $custom['height'] = (isset($current['custom']) && (int)$current['custom'] >= 300 ? (int)$current['custom'] : 480);

            $this->view->assign('icons', $icons);
            $this->view->assign('current', $current);
            $this->view->assign('custom', $custom);
            $this->view->assign('presetGroups', $this->getPreviewPresets($pageId));
            $this->view->assign('url', $targetUrl);
        }

        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * With page TS config it is possible to force a specific type id via mod.web_view.type
     * for a page id or a page tree.
     * The method checks if a type is set for the given id and returns the additional GET string.
     *
     * @param int $pageId
     * @return string
     */
    protected function getTypeParameterIfSet(int $pageId): string
    {
        $typeParameter = '';
        $typeId = (int)(BackendUtility::getPagesTSconfig($pageId)['mod.']['web_view.']['type'] ?? 0);
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
    protected function getDomainName(int $pageId)
    {
        $previewDomainConfig = BackendUtility::getPagesTSconfig($pageId)['TCEMAIN.']['previewDomain'] ?? '';
        return $previewDomainConfig ?: BackendUtility::firstDomainRecord(BackendUtility::BEgetRootLine($pageId));
    }

    /**
     * Get available presets for page id
     *
     * @param int $pageId
     * @return array
     */
    protected function getPreviewPresets(int $pageId): array
    {
        $presetGroups = [
            'desktop' => [],
            'tablet' => [],
            'mobile' => [],
            'unidentified' => []
        ];
        $previewFrameWidthConfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_view.']['previewFrameWidths.'] ?? [];
        foreach ($previewFrameWidthConfig as $item => $conf) {
            $data = [
                'key' => substr($item, 0, -1),
                'label' => $conf['label'] ?? null,
                'type' => $conf['type'] ?? 'unknown',
                'width' => (isset($conf['width']) && (int)$conf['width'] > 0 && strpos($conf['width'], '%') === false) ? (int)$conf['width'] : null,
                'height' => (isset($conf['height']) && (int)$conf['height'] > 0 && strpos($conf['height'], '%') === false) ? (int)$conf['height'] : null,
            ];
            $width = (int)substr($item, 0, -1);
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

        return $presetGroups;
    }

    /**
     * Returns the preview languages
     *
     * @param int $pageId
     * @return array
     */
    protected function getPreviewLanguages(int $pageId): array
    {
        $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $modSharedTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['SHARED.'] ?? [];
        if ($modSharedTSconfig['view.']['disableLanguageSelector'] === '1') {
            return [];
        }
        $languages = [
            0 => isset($modSharedTSconfig['defaultLanguageLabel'])
                    ? $modSharedTSconfig['defaultLanguageLabel'] . ' (' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage') . ')'
                    : $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage')
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
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
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
     * @param int $pageId
     * @param string $languageParam
     * @return int
     */
    protected function getCurrentLanguage(int $pageId, string $languageParam = null): int
    {
        $languageId = (int)$languageParam;
        if ($languageParam === null) {
            $states = $this->getBackendUser()->uc['moduleData']['web_view']['States'];
            $languages = $this->getPreviewLanguages($pageId);
            if (isset($states['languageSelectorValue']) && isset($languages[$states['languageSelectorValue']])) {
                $languageId = (int)$states['languageSelectorValue'];
            }
        } else {
            $this->getBackendUser()->uc['moduleData']['web_view']['States']['languageSelectorValue'] = $languageId;
            $this->getBackendUser()->writeUC($this->getBackendUser()->uc);
        }
        return $languageId;
    }

    /**
     * Verifies if doktype of given page is valid
     *
     * @param int $pageId
     * @return bool
     */
    protected function isValidDoktype(int $pageId = 0): bool
    {
        if ($pageId === 0) {
            return false;
        }

        $page = BackendUtility::getRecord('pages', $pageId);
        $pageType = (int)$page['doktype'] ?? 0;

        return $page !== null
            && $pageType !== PageRepository::DOKTYPE_SPACER
            && $pageType !== PageRepository::DOKTYPE_SYSFOLDER
            && $pageType !== PageRepository::DOKTYPE_RECYCLER;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
