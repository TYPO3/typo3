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
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for rendering the file editing screen
 */
class EditFileController extends AbstractModule
{
    /**
     * Module content accumulated.
     *
     * @var string
     */
    public $content;

    /**
     * @var string
     */
    public $title;

    /**
     * Document template object
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * Original input target
     *
     * @var string
     */
    public $origTarget;

    /**
     * The original target, but validated.
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
     * the file that is being edited on
     *
     * @var \TYPO3\CMS\Core\Resource\AbstractFile
     */
    protected $fileObject;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $GLOBALS['SOBE'] = $this;
        $this->init();
    }

    /**
     * Initialize script class
     *
     * @throws InsufficientFileAccessPermissionsException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function init()
    {
        // Setting target, which must be a file reference to a file within the mounts.
        $this->target = ($this->origTarget = ($fileIdentifier = GeneralUtility::_GP('target')));
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        // create the file object
        if ($fileIdentifier) {
            $this->fileObject = ResourceFactory::getInstance()
                ->retrieveFileOrFolderObject($fileIdentifier);
        }
        // Cleaning and checking target directory
        if (!$this->fileObject) {
            $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:paramError', true);
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:targetNoDir', true);
            throw new \RuntimeException($title . ': ' . $message, 1294586841);
        }
        if ($this->fileObject->getStorage()->getUid() === 0) {
            throw new InsufficientFileAccessPermissionsException(
                'You are not allowed to access files outside your storages',
                1375889832
            );
        }

        // Setting the title and the icon
        $icon = $this->iconFactory->getIcon('apps-filetree-root', Icon::SIZE_SMALL)->render();
        $this->title = $icon
            . htmlspecialchars(
                $this->fileObject->getStorage()->getName()
            ) . ': ' . htmlspecialchars(
                $this->fileObject->getIdentifier()
            );

        // Setting template object
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->moduleTemplate->addJavaScriptCode(
            'FileEditBackToList',
            'function backToList() {
				top.goToModule("file_FilelistList");
			}'
        );
    }

    /**
     * Main function, redering the actual content of the editing page
     *
     * @return void
     */
    public function main()
    {
        $this->getButtons();
        // Hook	before compiling the output
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook'])) {
            $preOutputProcessingHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook'];
            if (is_array($preOutputProcessingHook)) {
                $hookParameters = [
                    'content' => &$this->content,
                    'target' => &$this->target
                ];
                foreach ($preOutputProcessingHook as $hookFunction) {
                    GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
                }
            }
        }

        $pageContent = '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_file')) . '" method="post" id="EditFileController" name="editform">';
        $pageContent .= '<h1>'
            . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_edit.php.pagetitle')
            . ' ' . htmlspecialchars($this->fileObject->getName()) . '</h1>';

        $code = '';
        $extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
        try {
            if (!$extList || !GeneralUtility::inList($extList, $this->fileObject->getExtension())) {
                throw new \Exception('Files with that extension are not editable.');
            }

            // Read file content to edit:
            $fileContent = $this->fileObject->getContents();

            // Making the formfields
            $hValue = BackendUtility::getModuleUrl('file_edit', [
                'target' => $this->origTarget,
                'returnUrl' => $this->returnUrl
            ]);
            $code .= '
                <div id="c-edit">
					<textarea rows="30" name="file[editfile][0][data]" wrap="off"  class="form-control text-monospace t3js-enable-tab">' . htmlspecialchars($fileContent) . '</textarea>
					<input type="hidden" name="file[editfile][0][target]" value="' . $this->fileObject->getUid() . '" />
					<input type="hidden" name="redirect" value="' . htmlspecialchars($hValue) . '" />
				</div>
				<br />';
        } catch (\Exception $e) {
            $code .= sprintf(
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_edit.php.coundNot'),
                $extList
            );
        }

        // Ending of section and outputting editing form:
        $pageContent .= $code;

        // Hook	after compiling the output
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook'])) {
            $postOutputProcessingHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook'];
            if (is_array($postOutputProcessingHook)) {
                $hookParameters = [
                    'pageContent' => &$pageContent,
                    'target' => &$this->target
                ];
                foreach ($postOutputProcessingHook as $hookFunction) {
                    GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
                }
            }
        }
        $pageContent .= '</form>';
        $this->content = $pageContent;

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
     * Builds the buttons for the docheader and returns them as an array
     *
     * @return array
     */
    public function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $lang = $this->getLanguageService();
        // CSH button
        $helpButton = $buttonBar->makeHelpButton()
            ->setFieldName('file_edit')
            ->setModuleName('xMOD_csh_corebe');
        $buttonBar->addButton($helpButton);

        // Save button
        $saveButton = $buttonBar->makeInputButton()
            ->setName('_save')
            ->setValue('1')
            ->setOnClick('document.editform.submit();')
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:file_edit.php.submit'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL));

        // Save and Close button
        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setName('_saveandclose')
            ->setValue('1')
            ->setOnClick(
                'document.editform.redirect.value='
                . GeneralUtility::quoteJSvalue($this->returnUrl)
                . '; document.editform.submit();'
            )
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:file_edit.php.saveAndClose'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                'actions-document-save-close',
                Icon::SIZE_SMALL
            ));

        $splitButton = $buttonBar->makeSplitButton()
            ->addItem($saveButton)
            ->addItem($saveAndCloseButton);
        $buttonBar->addButton($splitButton, ButtonBar::BUTTON_POSITION_LEFT, 20);

        // Cancel button
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setOnClick('backToList(); return false;')
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.cancel'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 10);

        // Make shortcut:
        $shortButton = $buttonBar->makeShortcutButton()
            ->setModuleName('file_edit')
            ->setGetVariables(['target']);
        $buttonBar->addButton($shortButton);
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
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
