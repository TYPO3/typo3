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
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for display up to 10 upload fields
 */
class FileUploadController extends AbstractModule
{
    /**
     * Name of the filemount
     *
     * @var string
     */
    public $title;

    /**
     * Set with the target path inputted in &target
     *
     * @var string
     */
    public $target;

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
     */
    public $content;

    /**
     * The folder object which is the target directory for the upload
     *
     * @var \TYPO3\CMS\Core\Resource\Folder $folderObject
     */
    protected $folderObject;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $GLOBALS['SOBE'] = $this;
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_misc.xlf');
        $this->init();
    }

    /**
     * Initialize
     *
     * @throws InsufficientFolderAccessPermissionsException
     */
    protected function init()
    {
        // Initialize GPvars:
        $this->target = GeneralUtility::_GP('target');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        if (!$this->returnUrl) {
            $this->returnUrl = BackendUtility::getModuleUrl('file_list', [
                'id' => rawurlencode($this->target)
            ]);
        }
        // Create the folder object
        if ($this->target) {
            $this->folderObject = ResourceFactory::getInstance()
                ->retrieveFileOrFolderObject($this->target);
        }
        if ($this->folderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFolderAccessPermissionsException(
                'You are not allowed to access folders outside your storages',
                1375889834
            );
        }

        // Cleaning and checking target directory
        if (!$this->folderObject) {
            $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:paramError', true);
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:targetNoDir', true);
            throw new \RuntimeException($title . ': ' . $message, 1294586843);
        }

        // Setting up the context sensitive menu
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');

        // building pathInfo for metaInformation
        $pathInfo = [
            'combined_identifier' => $this->folderObject->getCombinedIdentifier(),
        ];
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pathInfo);
    }

    /**
     * Main function, rendering the upload file form fields
     *
     * @return void
     */
    public function main()
    {
        $lang = $this->getLanguageService();

        // set page title
        $this->moduleTemplate->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.pagetitle'));

        $pageContent = '<form action="'
            . htmlspecialchars(BackendUtility::getModuleUrl('tce_file'))
            . '" method="post" id="FileUploadController" name="editform" enctype="multipart/form-data">';
        // Make page header:
        $pageContent .= '<h1>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.pagetitle') . '</h1>';
        $pageContent .= $this->renderUploadForm();

        // Header Buttons
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // csh button
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('file_upload');
        $buttonBar->addButton($cshButton);

        // back button
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref(GeneralUtility::linkThisUrl($this->returnUrl))
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }

        $pageContent .= '</form>';
        $this->content .= '<div>' . $pageContent . '</div>';
        $this->moduleTemplate->setContent($this->content);
    }

    /**
     * This function renders the upload form
     *
     * @return string The HTML form as a string, ready for outputting
     */
    public function renderUploadForm()
    {
        // Make checkbox for "overwrite"
        $content = '
			<div id="c-override">
				<p><label for="overwriteExistingFiles"><input type="checkbox" class="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="replace" /> ' . $this->getLanguageService()->getLL('overwriteExistingFiles', 1) . '</label></p>
				<p>&nbsp;</p>
				<p>' . $this->getLanguageService()->getLL('uploadMultipleFilesInfo', true) . '</p>
			</div>
			';
        // Produce the number of upload-fields needed:
        $content .= '
			<div id="c-upload">
		';
        // Adding 'size="50" ' for the sake of Mozilla!
        $content .= '
				<input type="file" multiple="multiple" name="upload_1[]" />
				<input type="hidden" name="file[upload][1][target]" value="' . htmlspecialchars($this->folderObject->getCombinedIdentifier()) . '" />
				<input type="hidden" name="file[upload][1][data]" value="1" /><br />
			';
        $content .= '
			</div>
		';
        // Submit button:
        $content .= '
			<div id="c-submit">
				<input type="hidden" name="redirect" value="' . $this->returnUrl . '" /><br />
				<input class="btn btn-default" type="submit" value="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.submit', true) . '" />
			</div>
		';
        return $content;
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
}
