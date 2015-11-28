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
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Script Class for the rename-file form
 */
class ReplaceFileController extends AbstractModule
{
    /**
     * Document template object
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * Name of the filemount
     *
     * @var string
     */
    public $title;

    /**
     * sys_file uid
     *
     * @var int
     */
    public $uid;

    /**
     * The file or folder object that should be renamed
     *
     * @var \TYPO3\CMS\Core\Resource\ResourceInterface $fileOrFolderObject
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
     */
    public $content;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $GLOBALS['SOBE'] = $this;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->init();
    }

    /**
     * Init
     *
     * @return void
     * @throws \RuntimeException
     * @throws InsufficientFileAccessPermissionsException
     */
    protected function init()
    {
        // Initialize GPvars:
        $this->uid = (int)GeneralUtility::_GP('uid');
        $lang = $this->getLanguageService();

        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        // Cleaning and checking uid
        if ($this->uid > 0) {
            $this->fileOrFolderObject = ResourceFactory::getInstance()
                ->retrieveFileOrFolderObject('file:' . $this->uid);
        }
        if (!$this->fileOrFolderObject) {
            $title = $lang->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:paramError', true);
            $message = $lang->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:targetNoDir', true);
            throw new \RuntimeException($title . ': ' . $message, 1436895930);
        }
        if ($this->fileOrFolderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFileAccessPermissionsException(
                'You are not allowed to access files outside your storages',
                1436895931
            );
        }

        // If a folder should be renamed, AND the returnURL should go to the old directory name, the redirect is forced
        // so the redirect will NOT end in an error message
        // this case only happens if you select the folder itself in the foldertree and then use the clickmenu to
        // rename the folder
        if ($this->fileOrFolderObject instanceof Folder) {
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

        $pathInfo = [
            'combined_identifier' => $this->fileOrFolderObject->getCombinedIdentifier(),
        ];
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pathInfo);
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
        $this->moduleTemplate->addJavaScriptCode(
            'ReplaceFileOnlineJavaScript',
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
        $lang = $this->getLanguageService();
        $code = '<form action="'
            . htmlspecialchars(BackendUtility::getModuleUrl('tce_file'))
            . '" role="form" method="post" name="editform" enctype="multipart/form-data">';

        // Making the formfields for renaming:
        $code .= '
			<div class="form-group">
				<input type="checkbox" value="1" id="keepFilename" name="file[replace][1][keepFilename]"> <label for="keepFilename">'
            . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.keepfiletitle')
            . '</label>
			</div>

			<div class="form-group">
				<label for="file_replace">' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.selectfile') . '</label>
				<div class="input-group col-xs-6">
					<input type="text" name="fakefile" id="fakefile" class="form-control input-xlarge" readonly>
					<a class="input-group-addon btn btn-primary" onclick="TYPO3.jQuery(\'#file_replace\').click();">'
            . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.browse')
            . '</a>
				</div>
				<input class="form-control" type="file" id="file_replace" name="replace_1" style="visibility: hidden;" />
			</div>

			<script>
			TYPO3.jQuery(\'#file_replace\').change(function(){
				TYPO3.jQuery(\'#fakefile\').val(TYPO3.jQuery(this).val());
			});
			</script>

			<input type="hidden" name="overwriteExistingFiles" value="replace" />
			<input type="hidden" name="file[replace][1][data]" value="1" />
			<input type="hidden" name="file[replace][1][uid]" value="' . $this->uid . '" />
		';
        // Making submit button:
        $code .= '
				<div class="form-group">
					<input class="btn btn-primary" type="submit" value="'
            . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.submit', true) . '" />
					<input class="btn btn-danger" type="submit" value="'
            . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.cancel', true)
            . '" onclick="backToList(); return false;" />
					<input type="hidden" name="redirect" value="' . htmlspecialchars($this->returnUrl) . '" />
				</div>
		';
        $code .= '</form>';

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // csh button
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('file_rename');
        $buttonBar->addButton($cshButton);

        // Back button
        if ($this->returnUrl) {
            $returnButton = $buttonBar->makeLinkButton()
                ->setHref(GeneralUtility::linkThisUrl($this->returnUrl))
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($returnButton);
        }

        $this->content .= '<h1>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.pagetitle') . '</h1>';
        // Add the HTML as a section:
        $this->content .= '<div>' . $code . '</div>';

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
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
