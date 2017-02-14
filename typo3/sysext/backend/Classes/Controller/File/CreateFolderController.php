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
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script class for the create-new script
 *
 * Displays forms for creating folders (1 to 10), a media asset or a new file.
 */
class CreateFolderController extends AbstractModule
{
    /**
     * @var int
     */
    public $folderNumber = 10;

    /**
     * Name of the filemount
     *
     * @var string
     */
    public $title;

    /**
     * @var int
     */
    public $number;

    /**
     * Set with the target path inputted in &target
     *
     * @var string
     */
    public $target;

    /**
     * The folder object which is  the target directory
     *
     * @var \TYPO3\CMS\Core\Resource\Folder $folderObject
     */
    protected $folderObject;

    /**
     * Return URL of list module.
     *
     * @var string
     */
    public $returnUrl;

    /**
     * @var array
     */
    protected $pathInfo;

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
        $this->init();
    }

    /**
     * @throws InsufficientFolderAccessPermissionsException
     * @throws \RuntimeException
     */
    protected function init()
    {
        // Initialize GPvars:
        $this->number = GeneralUtility::_GP('number');
        $this->target = ($combinedIdentifier = GeneralUtility::_GP('target'));
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        // create the folder object
        if ($combinedIdentifier) {
            $this->folderObject = ResourceFactory::getInstance()
                ->getFolderObjectFromCombinedIdentifier($combinedIdentifier);
        }
        // Cleaning and checking target directory
        if (!$this->folderObject) {
            $title = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_file_list.xlf:paramError');
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_file_list.xlf:targetNoDir');
            throw new \RuntimeException($title . ': ' . $message, 1294586845);
        }
        if ($this->folderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFolderAccessPermissionsException(
                'You are not allowed to access folders outside your storages',
                1375889838
            );
        }

        $pathInfo = [
            'combined_identifier' => $this->folderObject->getCombinedIdentifier(),
        ];
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pathInfo);
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->moduleTemplate->addJavaScriptCode(
            'CreateFolderInlineJavaScript',
            'var path = "' . $this->target . '";
            var confirmTitle = '
            . GeneralUtility::quoteJSvalue(
                $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:pleaseConfirm')
            )
            . ';
            var confirmText = '
            . GeneralUtility::quoteJSvalue(
                $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.redraw')
            )
            . ';
            function reload(a) {
                var params = "&target="+encodeURIComponent(path)+"&number="+a+"&returnUrl=' . rawurlencode($this->returnUrl) . '";
                var url = \'' . BackendUtility::getModuleUrl('file_newfolder') . '\';
                if (!changed) {
                    window.location.href = url + params;
                } else {
                    var modal = top.TYPO3.Modal.confirm(confirmTitle, confirmText);
                    modal.on(\'confirm.button.cancel\', function(e) {
                        top.TYPO3.Modal.currentModal.trigger(\'modal-dismiss\');
                    });
                    modal.on(\'confirm.button.ok\', function(e) {
                        top.TYPO3.Modal.currentModal.trigger(\'modal-dismiss\');
                        window.location.href = url + params;
                    });
                }
            }
            function backToList() {
                top.goToModule("file_FilelistList");
            }
            var changed = 0;'
        );
    }

    /**
     * Main function, rendering the main module content
     */
    public function main()
    {
        $lang = $this->getLanguageService();
        $assigns = [];
        $assigns['target'] = $this->target;
        if ($this->folderObject->checkActionPermission('add')) {
            $assigns['moduleUrlTceFile'] = BackendUtility::getModuleUrl('tce_file');
            $assigns['cshFileNewFolder'] = BackendUtility::cshItem('xMOD_csh_corebe', 'file_newfolder');
            // Making the selector box for the number of concurrent folder-creations
            $this->number = MathUtility::forceIntegerInRange($this->number, 1, 10);
            for ($a = 1; $a <= $this->folderNumber; $a++) {
                $options = [];
                $options['value'] = $a;
                $options['selected'] = ($this->number == $a ? ' selected="selected"' : '');
                $assigns['options'][] = $options;
            }
            // Making the number of new-folder boxes needed:
            for ($a = 0; $a < $this->number; $a++) {
                $folder = [];
                $folder['this'] = $a;
                $folder['next'] = $a + 1;
                $assigns['folders'][] = $folder;
            }
            // Making submit button for folder creation:
            $assigns['returnUrl'] = $this->returnUrl;
        }

        if ($this->folderObject->getStorage()->checkUserActionPermission('add', 'File')) {
            $assigns['moduleUrlOnlineMedia'] = BackendUtility::getModuleUrl('online_media');
            $assigns['cshFileNewMedia'] = BackendUtility::cshItem('xMOD_csh_corebe', 'file_newMedia');
            // Create a list of allowed file extensions with the readable format "youtube, vimeo" etc.
            $fileExtList = [];
            $onlineMediaFileExt = OnlineMediaHelperRegistry::getInstance()->getSupportedFileExtensions();
            foreach ($onlineMediaFileExt as $fileExt) {
                if (GeneralUtility::verifyFilenameAgainstDenyPattern('.' . $fileExt)) {
                    $fileExtList[] = strtoupper(htmlspecialchars($fileExt));
                }
            }
            $assigns['fileExtList'] = $fileExtList;

            $assigns['moduleUrlTceFile'] = BackendUtility::getModuleUrl('tce_file');
            $assigns['cshFileNewFile'] = BackendUtility::cshItem('xMOD_csh_corebe', 'file_newfile');
            // Create a list of allowed file extensions with a text format "*.txt, *.css" etc.
            $fileExtList = [];
            $textFileExt = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], true);
            foreach ($textFileExt as $fileExt) {
                if (GeneralUtility::verifyFilenameAgainstDenyPattern('.' . $fileExt)) {
                    $fileExtList[] = strtoupper(htmlspecialchars($fileExt));
                }
            }
            $assigns['txtFileExtList'] = $fileExtList;
        }

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // CSH button
        $helpButton = $buttonBar->makeHelpButton()
            ->setFieldName('file_new')
            ->setModuleName('xMOD_csh_corebe');
        $buttonBar->addButton($helpButton);

        // Back
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
               ->setHref($this->returnUrl)
               ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
               ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:backend/Resources/Private/Templates/File/CreateFolder.html'
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
}
