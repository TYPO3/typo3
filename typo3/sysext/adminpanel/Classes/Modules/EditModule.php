<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules;

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

use TYPO3\CMS\Adminpanel\Service\EditToolbarService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Admin Panel Edit Module
 */
class EditModule extends AbstractModule
{
    /**
     * Creates the content for the "edit" section ("module") of the Admin Panel
     *
     * @return string HTML content for the section. Consists of a string with table-rows with four columns.
     */
    public function getContent(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = $this->extResources . '/Templates/Modules/Edit.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths([$this->extResources . '/Partials']);

        $editToolbarService = GeneralUtility::makeInstance(EditToolbarService::class);
        $view->assignMultiple([
            'feEdit' => ExtensionManagementUtility::isLoaded('feedit'),
            'display' => [
                'edit' => $this->getBackendUser()->uc['TSFE_adminConfig']['display_edit'],
                'fieldIcons' => $this->getConfigurationOption('displayFieldIcons'),
                'displayIcons' => $this->getConfigurationOption('displayIcons'),
            ],
            'toolbar' => $editToolbarService->createToolbar(),
            'script' => [
                'pageUid' => (int)$this->getTypoScriptFrontendController()->page['uid'],
                'pageModule' => $this->getPageModule(),
                'backendScript' => BackendUtility::getBackendScript(),
                't3BeSitenameMd5' => \md5('Typo3Backend-' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']),
            ],
        ]);

        return $view->render();
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return string
     */
    private function getPageModule(): string
    {
        $newPageModule = \trim(
            (string)$this->getBackendUser()
                ->getTSConfigVal('options.overridePageModule')
        );
        return BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'edit';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->extGetLL('edit');
    }

    /**
     * Initialize the edit module
     * Includes the frontend edit initialization
     *
     * @param ServerRequest $request
     *
     * @todo move into fe_edit (including the module)
     */
    public function initializeModule(ServerRequest $request): void
    {
        $extFeEditLoaded = ExtensionManagementUtility::isLoaded('feedit');
        $typoScriptFrontend = $this->getTypoScriptFrontendController();
        $typoScriptFrontend->displayEditIcons = $this->getConfigurationOption('displayIcons');
        $typoScriptFrontend->displayFieldEditIcons = $this->getConfigurationOption('displayFieldIcons');

        if (GeneralUtility::_GP('ADMCMD_editIcons')) {
            $typoScriptFrontend->displayFieldEditIcons = '1';
        }
        if ($extFeEditLoaded && $typoScriptFrontend->displayEditIcons) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Display edit icons', true);
        }
        if ($extFeEditLoaded && $typoScriptFrontend->displayFieldEditIcons) {
            $typoScriptFrontend->set_no_cache('Admin Panel: Display field edit icons', true);
        }
    }

    /**
     * @inheritdoc
     */
    public function showFormSubmitButton(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Edit/OpenBackendHandler.js'];
    }
}
