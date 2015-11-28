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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
            $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:paramError', true);
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:targetNoDir', true);
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
                $this->returnUrl = str_replace(urlencode($queryParts['id']),
                    urlencode($this->fileOrFolderObject->getStorage()->getRootLevelFolder()->getCombinedIdentifier()),
                    $this->returnUrl);
            }
        }

        // building pathInfo for metaInformation
        $pathInfo = [
            'combined_identifier' => $this->fileOrFolderObject->getCombinedIdentifier(),
        ];
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pathInfo);

        // Setting up the context sensitive menu
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');

        // Add javaScript
        $this->moduleTemplate->addJavaScriptCode(
            'RenameFileInlineJavaScript',
            'function backToList() {top.goToModule("file_FilelistList");}'
        );
    }

    /**
     * Main function, rendering the content of the rename form
     *
     * @return void
     */
    public function main()
    {
        if ($this->fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
            $fileIdentifier = $this->fileOrFolderObject->getCombinedIdentifier();
        } else {
            $fileIdentifier = $this->fileOrFolderObject->getUid();
        }
        $pageContent = '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_file')) . '" method="post" name="editform" role="form">';
        // Making the formfields for renaming:
        $pageContent .= '

			<div class="form-group">
				<input class="form-control" type="text" name="file[rename][0][target]" value="' . htmlspecialchars($this->fileOrFolderObject->getName()) . '" ' . $this->getDocumentTemplate()->formWidth(40) . ' />
				<input type="hidden" name="file[rename][0][data]" value="' . htmlspecialchars($fileIdentifier) . '" />
			</div>
		';
        // Making submit button:
        $pageContent .= '
			<div class="form-group">
				<input class="btn btn-primary" type="submit" value="' .
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_rename.php.submit', true) . '" />
				<input class="btn btn-danger" type="submit" value="' .
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.cancel', true) .
                '" onclick="backToList(); return false;" />
				<input type="hidden" name="redirect" value="' . htmlspecialchars($this->returnUrl) . '" />
			</div>
		';
        $pageContent .= '</form>';

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
                ->sethref(GeneralUtility::linkThisUrl($this->returnUrl))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }

        // set header
        $this->content = '<h1>' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_rename.php.pagetitle') . '</h1>';

        // add section
        $this->content .= '<div>' . $pageContent . '</div>';
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
