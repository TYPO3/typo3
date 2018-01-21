<?php
namespace TYPO3\CMS\Backend\Controller\File;

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
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for the rename-file form.
 */
class RenameFileController extends AbstractModule
{
    /**
     * Name of the filemount
     *
     * @var string
     */
    public $title;

    /**
     * Target path
     *
     * @var string
     * @internal
     */
    public $target;

    /**
     * The file or folder object that should be renamed
     *
     * @var \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $fileOrFolderObject
     */
    protected $fileOrFolderObject;

    /**
     * Return URL of list module.
     *
     * @var string
     */
    public $returnUrl;

    /**
     * Accumulating content
     *
     * @var string
     * @internal
     */
    public $content;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $GLOBALS['SOBE'] = $this;
        $this->init();
    }

    /**
     * Initialize
     *
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException
     */
    protected function init()
    {
        // Initialize GPvars:
        $this->target = GeneralUtility::_GP('target');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        // Cleaning and checking target
        if ($this->target) {
            $this->fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->target);
        }
        if (!$this->fileOrFolderObject) {
            $title = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_file_list.xlf:paramError');
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_file_list.xlf:targetNoDir');
            throw new \RuntimeException($title . ': ' . $message, 1294586844);
        }
        if ($this->fileOrFolderObject->getStorage()->getUid() === 0) {
            throw new \TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1375889840);
        }

        // If a folder should be renamed, AND the returnURL should go to the old directory name, the redirect is forced
        // so the redirect will NOT end in an error message
        // this case only happens if you select the folder itself in the foldertree and then use the clickmenu to
        // rename the folder
        if ($this->fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
            $parsedUrl = parse_url($this->returnUrl);
            $queryParts = GeneralUtility::explodeUrl2Array(urldecode($parsedUrl['query']));
            if ($queryParts['id'] === $this->fileOrFolderObject->getCombinedIdentifier()) {
                $this->returnUrl = str_replace(
                    urlencode($queryParts['id']),
                    urlencode($this->fileOrFolderObject->getStorage()->getRootLevelFolder()->getCombinedIdentifier()),
                    $this->returnUrl
                );
            }
        }

        // building pathInfo for metaInformation
        $pathInfo = [
            'combined_identifier' => $this->fileOrFolderObject->getCombinedIdentifier(),
        ];
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pathInfo);

        // Setting up the context sensitive menu
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/RenameFile');

        // Add javaScript
        $this->moduleTemplate->addJavaScriptCode(
            'RenameFileInlineJavaScript',
            'function backToList() {top.goToModule("file_FilelistList");}'
        );
    }

    /**
     * Main function, rendering the content of the rename form
     */
    public function main()
    {
        $assigns = [];
        $assigns['moduleUrlTceFile'] = BackendUtility::getModuleUrl('tce_file');
        $assigns['returnUrl'] = $this->returnUrl;

        if ($this->fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
            $fileIdentifier = $this->fileOrFolderObject->getCombinedIdentifier();
        } else {
            $fileIdentifier = $this->fileOrFolderObject->getUid();
            $assigns['conflictMode'] = DuplicationBehavior::cast(DuplicationBehavior::RENAME);
            $assigns['destination'] = $this->fileOrFolderObject->getParentFolder()->getCombinedIdentifier();
        }

        $assigns['fileName'] = $this->fileOrFolderObject->getName();
        $assigns['fileIdentifier'] = $fileIdentifier;

        // Create buttons
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // csh button
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('file_rename');
        $buttonBar->addButton($cshButton);

        // back button
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }

        $this->moduleTemplate->getPageRenderer()->addInlineLanguageLabelArray([
            'file_rename.actions.cancel' => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:file_rename.actions.cancel'),
            'file_rename.actions.rename' => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:file_rename.actions.rename'),
            'file_rename.actions.override' => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:file_rename.actions.override'),
            'file_rename.exists.title' => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:file_rename.exists.title'),
            'file_rename.exists.description' => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:file_rename.exists.description'),
        ]);

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:backend/Resources/Private/Templates/File/RenameFile.html'
        ));
        $view->assignMultiple($assigns);
        $this->content = $view->render();
        $this->moduleTemplate->setContent($this->content);
    }

    /**
     * Processes the request, currently everything is handled and put together via "main()"
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->main();
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns an instance of DocumentTemplate
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }
}
