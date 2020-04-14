<?php

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

namespace TYPO3\CMS\Impexp\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Main script class for the Import / Export facility.
 *
 * @todo: In TYPO3 v11 this class is about to become an abstract class
 * @internal this is a TYPO3 Backend controller implementation and not part of TYPO3's Core API.
 */
class ImportExportController
{
    /**
     * The integer value of the GET/POST var, 'id'. Used for submodules to the 'Web' module (page id)
     *
     * @var int
     */
    protected $id;

    /**
     * Array containing the current page.
     *
     * @var array
     */
    protected $pageinfo;

    /**
     * A WHERE clause for selection records from the pages table based on read-permissions of the current backend user.
     *
     * @var string
     */
    protected $perms_clause;

    /**
     * @var LanguageService
     */
    protected $lang;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'xMOD_tximpexp';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     *  The name of the shortcut for this page
     *
     * @var string
     */
    protected $shortcutName;

    /**
     * @var StandaloneView
     */
    protected $standaloneView;

    /**
     * Return URL
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);

        $templatePath = ExtensionManagementUtility::extPath('impexp') . 'Resources/Private/';

        $this->standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $this->standaloneView->setTemplateRootPaths([$templatePath . 'Templates/ImportExport/']);
        $this->standaloneView->setLayoutRootPaths([$templatePath . 'Layouts/']);
        $this->standaloneView->setPartialRootPaths([$templatePath . 'Partials/']);
        $this->standaloneView->getRequest()->setControllerExtensionName('impexp');

        $this->id = (int)GeneralUtility::_GP('id');
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        $this->lang = $this->getLanguageService();
    }

    /**
     * Injects the request object for the current request and gathers all data
     *
     * IMPORTING DATA:
     *
     * Incoming array has syntax:
     * GETvar 'id' = import page id (must be readable)
     *
     * file = pointing to filename relative to public web path
     *
     * [all relation fields are clear, but not files]
     * - page-tree is written first
     * - then remaining pages (to the root of import)
     * - then all other records are written either to related included pages or if not found to import-root (should be a sysFolder in most cases)
     * - then all internal relations are set and non-existing relations removed, relations to static tables preserved.
     *
     * EXPORTING DATA:
     *
     * Incoming array has syntax:
     *
     * file[] = file
     * dir[] = dir
     * list[] = table:pid
     * record[] = table:uid
     *
     * pagetree[id] = (single id)
     * pagetree[levels]=1,2,3, -1 = currently unpacked tree, -2 = only tables on page
     * pagetree[tables][]=table/_ALL
     *
     * external_ref[tables][]=table/_ALL
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundException
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        // @todo: This method will become abstract in TYPO3 v11.
        trigger_error('Route identifier xMOD_tximpexp is deprecated and will be removed in TYPO3 v11. Consider using tx_impexp_export or tx_impexp_import instead.', E_USER_DEPRECATED);

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $inData = GeneralUtility::_GP('tx_impexp');
        $routeName = 'tx_impexp_export';
        if ($inData === null || $inData['action'] === 'import') {
            $routeName = 'tx_impexp_import';
        }
        unset($inData['action']);
        $target = (string)$uriBuilder->buildUriFromRoute($routeName, ['tx_impexp' => $inData]);
        return new RedirectResponse($target, 308);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setGetVariables(['tx_impexp'])
                ->setDisplayName($this->shortcutName)
                ->setModuleName($this->moduleName);
            $buttonBar->addButton($shortcutButton);
        }
        // back button
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($this->lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }
    }

    /**
     * Returns a \TYPO3\CMS\Core\Resource\Folder object for saving export files
     * to the server and is also used for uploading import files.
     *
     * @throws \InvalidArgumentException
     * @return Folder|null
     */
    protected function getDefaultImportExportFolder(): ?Folder
    {
        $defaultImportExportFolder = null;

        $defaultTemporaryFolder = $this->getBackendUser()->getDefaultUploadTemporaryFolder();
        if ($defaultTemporaryFolder !== null) {
            $importExportFolderName = 'importexport';
            $createFolder = !$defaultTemporaryFolder->hasFolder($importExportFolderName);
            if ($createFolder === true) {
                try {
                    $defaultImportExportFolder = $defaultTemporaryFolder->createFolder($importExportFolderName);
                } catch (Exception $folderAccessException) {
                }
            } else {
                $defaultImportExportFolder = $defaultTemporaryFolder->getSubfolder($importExportFolderName);
            }
        }

        return $defaultImportExportFolder;
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
