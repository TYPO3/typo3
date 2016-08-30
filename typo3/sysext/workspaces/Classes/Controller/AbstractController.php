<?php
namespace TYPO3\CMS\Workspaces\Controller;

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

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract action controller.
 */
class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
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
     * @var string Key of the extension this controller belongs to
     */
    protected $extensionName = 'Workspaces';

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var int
     */
    protected $pageId;

    /**
     * Initializes the controller before invoking an action method.
     *
     * @return void
     */
    protected function initializeAction()
    {
        $this->pageRenderer = $this->getPageRenderer();
        // @todo Evaluate how the intval() call can be used with Extbase validators/filters
        $this->pageId = (int)GeneralUtility::_GP('id');
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icons = [
            'language' => $iconFactory->getIcon('flags-multiple', Icon::SIZE_SMALL)->render(),
            'integrity' => $iconFactory->getIcon('status-dialog-information', Icon::SIZE_SMALL)->render(),
            'success' => $iconFactory->getIcon('status-dialog-ok', Icon::SIZE_SMALL)->render(),
            'info' => $iconFactory->getIcon('status-dialog-information', Icon::SIZE_SMALL)->render(),
            'warning' => $iconFactory->getIcon('status-dialog-warning', Icon::SIZE_SMALL)->render(),
            'error' => $iconFactory->getIcon('status-dialog-error', Icon::SIZE_SMALL)->render()
        ];
        $this->pageRenderer->addInlineSetting('Workspaces', 'icons', $icons);
        $this->pageRenderer->addInlineSetting('Workspaces', 'id', $this->pageId);
        $this->pageRenderer->addInlineSetting('Workspaces', 'depth', $this->pageId === 0 ? 999 : 1);
        $this->pageRenderer->addInlineSetting('Workspaces', 'language', $this->getLanguageSelection());
        $this->pageRenderer->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/Css/module.css');
        $this->pageRenderer->addInlineLanguageLabelArray([
            'title' => $GLOBALS['LANG']->getLL('title'),
            'path' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.path'),
            'table' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.table'),
            'depth' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_perm.xlf:Depth'),
            'depth_0' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
            'depth_1' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
            'depth_2' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
            'depth_3' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
            'depth_4' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_4'),
            'depth_infi' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
        ]);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');
        $this->assignExtensionSettings();
    }

    /**
     * Assigns additional Workspace settings to TYPO3.settings.Workspaces.extension
     *
     * @return void
     */
    protected function assignExtensionSettings()
    {
        $extension = [
            'AdditionalColumn' => [
                'Definition' => [],
                'Handler' => [],
            ],
        ];

        $extension['AdditionalColumn']['Definition'] = $this->getAdditionalColumnService()->getDefinition();
        $extension['AdditionalColumn']['Handler'] = $this->getAdditionalColumnService()->getHandler();
        $this->pageRenderer->addInlineSetting('Workspaces', 'extension', $extension);
    }

    /**
     * Gets the selected language.
     *
     * @return string
     */
    protected function getLanguageSelection()
    {
        $language = 'all';
        $backendUser = $this->getBackendUser();
        if (isset($backendUser->uc['moduleData']['Workspaces'][$backendUser->workspace]['language'])) {
            $language = $backendUser->uc['moduleData']['Workspaces'][$backendUser->workspace]['language'];
        }
        return $language;
    }

    /**
     * @return \TYPO3\CMS\Workspaces\Service\AdditionalColumnService
     */
    protected function getAdditionalColumnService()
    {
        return $this->objectManager->get(\TYPO3\CMS\Workspaces\Service\AdditionalColumnService::class);
    }

    /**
     * @return \TYPO3\CMS\Workspaces\Service\AdditionalResourceService
     */
    protected function getAdditionalResourceService()
    {
        return $this->objectManager->get(\TYPO3\CMS\Workspaces\Service\AdditionalResourceService::class);
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
