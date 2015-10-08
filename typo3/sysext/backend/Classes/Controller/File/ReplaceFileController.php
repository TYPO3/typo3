<?php
namespace TYPO3\CMS\Backend\Controller\File;

/**
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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Script Class for the rename-file form
 */
class ReplaceFileController
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
        $GLOBALS['SOBE'] = $this;
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
        $this->uid = (int) GeneralUtility::_GP('uid');

        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        // Cleaning and checking uid
        if ($this->uid > 0) {
            $this->fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject('file:' . $this->uid);
        }
        if (!$this->fileOrFolderObject) {
            $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:paramError', true);
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:targetNoDir', true);
            throw new \RuntimeException($title . ': ' . $message, 1436895930);
        }
        if ($this->fileOrFolderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1436895931);
        }

        // If a folder should be renamed, AND the returnURL should go to the old directory name, the redirect is forced
        // so the redirect will NOT end in an error message
        // this case only happens if you select the folder itself in the foldertree and then use the clickmenu to
        // rename the folder
        if ($this->fileOrFolderObject instanceof Folder) {
            $parsedUrl = parse_url($this->returnUrl);
            $queryParts = GeneralUtility::explodeUrl2Array(urldecode($parsedUrl['query']));
            if ($queryParts['id'] === $this->fileOrFolderObject->getCombinedIdentifier()) {
                $this->returnUrl = str_replace(urlencode($queryParts['id']), urlencode($this->fileOrFolderObject->getStorage()->getRootLevelFolder()->getCombinedIdentifier()), $this->returnUrl);
            }
        }
        // Setting icon and title
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon = $iconFactory->getIcon('apps-filetree-root', Icon::SIZE_SMALL)->render();
        $this->title = $icon . htmlspecialchars($this->fileOrFolderObject->getStorage()->getName()) . ': ' . htmlspecialchars($this->fileOrFolderObject->getIdentifier());
        // Setting template object
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/file_replace.html');
        $this->doc->JScode = $this->doc->wrapScriptTags('
			function backToList() {
				top.goToModule("file_FilelistList");
			}
		');
    }

    /**
     * Main function, rendering the content of the rename form
     *
     * @return void
     */
    public function main()
    {
        // Make page header:
        $this->content = $this->doc->startPage($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.pagetitle'));
        $pageContent = $this->doc->header($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.pagetitle'));
        $pageContent .= $this->doc->spacer(5);
        $pageContent .= $this->doc->divider(5);

        $code = '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_file')) . '" role="form" method="post" name="editform" enctype="multipart/form-data">';

        // Making the formfields for renaming:
        $code .= '
			<div class="form-group">
				<input type="checkbox" value="1" id="keepFilename" name="file[replace][1][keepFilename]"> <label for="keepFilename">' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.keepfiletitle') . '</label>
			</div>

			<div class="form-group">
				<label for="file_replace">' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.selectfile') . '</label>
				<div class="input-group col-xs-6">
					<input type="text" name="fakefile" id="fakefile" class="form-control input-xlarge" readonly>
					<a class="input-group-addon btn btn-primary" onclick="TYPO3.jQuery(\'#file_replace\').click();">' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.browse') . '</a>
				</div>
				<input class="form-control" type="file" id="file_replace" multiple="false" name="replace_1" style="visibility: hidden;" />
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
					<input class="btn btn-primary" type="submit" value="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_replace.php.submit', true) . '" />
					<input class="btn btn-danger" type="submit" value="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.cancel', true) . '" onclick="backToList(); return false;" />
					<input type="hidden" name="redirect" value="' . htmlspecialchars($this->returnUrl) . '" />
				</div>
		';
        $code .= '</form>';
        // Add the HTML as a section:
        $pageContent .= $code;
        $docHeaderButtons = array(
                'back' => ''
        );
        $docHeaderButtons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'file_rename');
        // Back
        if ($this->returnUrl) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $docHeaderButtons['back'] = '<a href="' . htmlspecialchars(GeneralUtility::linkThisUrl($this->returnUrl))
                . '" class="typo3-goBack" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', true) . '">'
                . $iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL)->render()
                . '</a>';
        }
        // Add the HTML as a section:
        $markerArray = array(
                'CSH' => $docHeaderButtons['csh'],
                'FUNC_MENU' => BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']),
                'CONTENT' => $pageContent,
                'PATH' => $this->title
        );
        $this->content .= $this->doc->moduleBody(array(), $docHeaderButtons, $markerArray);
        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);
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

        $response->getBody()->write($this->content);
        return $response;
    }

    /**
     * Outputting the accumulated content to screen
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use the mainAction() method instead
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->content;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
