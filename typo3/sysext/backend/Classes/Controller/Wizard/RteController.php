<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

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
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script class for rendering the full screen RTE display
 */
class RteController extends AbstractWizardController
{
    /**
     * Content accumulation for the module.
     *
     * @var string
     */
    public $content;

    /**
     * Wizard parameters, coming from FormEngine linking to the wizard.
     *
     * @var array
     */
    public $P;

    /**
     * If set, launch a new window with the current records pid.
     *
     * @var string
     */
    public $popView;

    /**
     * Set to the URL of this script including variables which is needed to re-display the form. See main()
     *
     * @var string
     */
    public $R_URI;

    /**
     * Module configuration
     *
     * @var array
     */
    public $MCONF = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_wizards.xlf');
        $GLOBALS['SOBE'] = $this;

        $this->init();
    }

    /**
     * Initialization of the class
     *
     * @return void
     */
    protected function init()
    {
        // Setting GPvars:
        $this->P = GeneralUtility::_GP('P');
        $this->popView = GeneralUtility::_GP('popView');
        $this->R_URI = GeneralUtility::linkThisScript(['popView' => '']);
        // "Module name":
        $this->MCONF['name'] = 'wizard_rte';
        // Need to NOT have the page wrapped in DIV since if we do that we destroy
        // the feature that the RTE spans the whole height of the page!!!
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->main();

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Main function, rendering the document with the iFrame with the RTE in.
     *
     * @return void
     */
    public function main()
    {
        $this->content .= '<form action="'
            . htmlspecialchars(BackendUtility::getModuleUrl('tce_db'))
            . '" method="post" enctype="multipart/form-data" id="RteController" name="editform" '
            . ' onsubmit="return TBE_EDITOR.checkSubmit(1);">';
        // Translate id to the workspace version:
        if ($versionedRecord = BackendUtility::getWorkspaceVersionOfRecord(
            $this->getBackendUserAuthentication()->workspace,
            $this->P['table'],
            $this->P['uid'],
            'uid'
        )) {
            $this->P['uid'] = $versionedRecord['uid'];
        }
        // If all parameters are available:
        if ($this->P['table']
            && $this->P['field']
            && $this->P['uid']
            && $this->checkEditAccess($this->P['table'], $this->P['uid'])) {
            /** @var TcaDatabaseRecord $formDataGroup */
            $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
            /** @var FormDataCompiler $formDataCompiler */
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
            /** @var NodeFactory $nodeFactory */
            $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

            $formDataCompilerInput = [
                'vanillaUid' => (int)$this->P['uid'],
                'tableName' => $this->P['table'],
                'command' => 'edit',
                'disabledWizards' => true,
            ];

            $formData = $formDataCompiler->compile($formDataCompilerInput);

            $formData['fieldListToRender'] = $this->P['field'];
            $formData['renderType'] = 'outerWrapContainer';
            $formResult = $nodeFactory->create($formData)->render();

            /** @var FormResultCompiler $formResultCompiler */
            $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
            $formResultCompiler->mergeResult($formResult);

            // override the default jumpToUrl
            $this->moduleTemplate->addJavaScriptCode(
                'RteWizardInlineCode',
                'function jumpToUrl(URL,formEl) {
					if (document.editform) {
						if (!TBE_EDITOR.isFormChanged()) {
							window.location.href = URL;
						} else if (formEl) {
							if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
						}
					} else {
						window.location.href = URL;
					}
				}
			'
            );

            // Setting JavaScript of the pid value for viewing:
            if ($this->popView) {
                $this->moduleTemplate->addJavaScriptCode(
                    'PopupViewInlineJS',
                    BackendUtility::viewOnClick(
                        $formData['databaseRow']['pid'],
                        '',
                        BackendUtility::BEgetRootLine($formData['databaseRow']['pid'])
                    )
                );
            }

            $pageTsConfigMerged = $formData['pageTsConfigMerged'];
            if ((string)$pageTsConfigMerged['TCEFORM.'][$this->P['table'] . '.'][$this->P['field'] . '.']['RTEfullScreenWidth'] !== '') {
                $width = (string)$pageTsConfigMerged['TCEFORM.'][$this->P['table'] . '.'][$this->P['field'] . '.']['RTEfullScreenWidth'];
            } else {
                $width = '100%';
            }
            // Get the form field and wrap it in the table with the buttons:
            $formContent = $formResult['html'];
            $formContent = '
				<table border="0" cellpadding="0" cellspacing="0" width="' . $width . '" id="typo3-rtewizard">
					<tr>
						<td width="' . $width . '" colspan="2" id="c-formContent">' . $formContent . '</td>
						<td></td>
					</tr>
				</table>';

            // Adding hidden fields:
            $formContent .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($this->R_URI) . '" />
						<input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />';
            // Finally, add the whole setup:
            $this->content .= $formResultCompiler->JStop()
                . $formContent
                . $formResultCompiler->printNeededJSFunctions();
        } else {
            // ERROR:
            $this->content .= '<h2>' . $this->getLanguageService()->getLL('forms_title', true) . '</h2>'
                . '<div><span class="text-danger">'
                . $this->getLanguageService()->getLL('table_noData', true)
                . '</span></div>';
        }
        // Setting up the buttons and markers for docHeader
        $this->getButtons();
        // Build the <body> for the module

        $this->content .= '</form>';
        $this->moduleTemplate->setContent($this->content);
    }

    /**
     * Outputting the accumulated content to screen
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use mainAction() instead
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->content;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return array All available buttons as an assoc. array
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->P['table']
            && $this->P['field']
            && $this->P['uid']
            && $this->checkEditAccess($this->P['table'], $this->P['uid'])) {
            $closeUrl = GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']);
            // Getting settings for the undo button:
            $undoButton = 0;
            $databaseConnection = $this->getDatabaseConnection();
            $undoRes = $databaseConnection->exec_SELECTquery(
                'tstamp',
                'sys_history',
                'tablename=' . $databaseConnection->fullQuoteStr(
                    $this->P['table'],
                    'sys_history'
                ) . ' AND recuid=' . (int)$this->P['uid'],
                '',
                'tstamp DESC',
                '1'
            );
            if ($undoButtonR = $databaseConnection->sql_fetch_assoc($undoRes)) {
                $undoButton = 1;
            }

            // Close
            $closeButton = $buttonBar->makeLinkButton()
                ->setHref($closeUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-close', Icon::SIZE_SMALL));
            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 10);

            // Save
            $saveButton = $buttonBar->makeInputButton()
                ->setName('_savedok_x')
                ->setValue('1')
                ->setForm('RteController')
                ->setOnClick('TBE_EDITOR.checkAndDoSubmit(1); return false;')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL));
            // Save & View
            $saveAndViewButton = $buttonBar->makeInputButton()
                ->setName('_savedokview_x')
                ->setValue('1')
                ->setForm('RteController')
                ->setOnClick('document.editform.redirect.value+= ' . GeneralUtility::quoteJSvalue('&popView=1') . '; '
                    . ' TBE_EDITOR.checkAndDoSubmit(1); return false;')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDocShow'))
                ->setIcon(
                    $this->moduleTemplate->getIconFactory()->getIcon('actions-document-save-view', Icon::SIZE_SMALL)
                );

            // Save & Close
            $saveAndCloseButton = $buttonBar->makeInputButton()
                ->setName('_saveandclosedok_x')
                ->setValue('1')
                ->setForm('RteController')
                ->setOnClick('document.editform.redirect.value=' . GeneralUtility::quoteJSvalue($closeUrl)
                    . '; TBE_EDITOR.checkAndDoSubmit(1); return false;')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-document-save-close',
                    Icon::SIZE_SMALL
                ));

            // Save SplitButton
            $saveSplitButton = $buttonBar->makeSplitButton()
                ->addItem($saveButton)
                ->addItem($saveAndViewButton)
                ->addItem($saveAndCloseButton);
            $buttonBar->addButton($saveSplitButton, ButtonBar::BUTTON_POSITION_LEFT, 20);

            // Undo/Revert:
            if ($undoButton) {
                $aOnClick = 'window.location.href=' .
                    GeneralUtility::quoteJSvalue(
                        BackendUtility::getModuleUrl(
                            'record_history',
                            [
                                'element' => $this->P['table'] . ':' . $this->P['uid'],
                                'revert' => 'field:' . $this->P['field'],
                                'sumUp' => -1,
                                'returnUrl' => $this->R_URI,
                            ]
                        )
                    ) . '; return false;';

                $undoText = $this->getLanguageService()->sL(
                    'LLL:EXT:lang/locallang_wizards.xlf:rte_undoLastChange'
                );
                $lastChangeLabel = sprintf(
                    $undoText,
                    BackendUtility::calcAge(
                        ($GLOBALS['EXEC_TIME'] - $undoButtonR['tstamp']),
                        $this->getLanguageService()->sL(
                            'LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears'
                        )
                    )
                );

                $undoRevertButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($aOnClick)
                    ->setTitle($lastChangeLabel)
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-edit-undo', Icon::SIZE_SMALL));
                $buttonBar->addButton($undoRevertButton, ButtonBar::BUTTON_POSITION_LEFT, 30);
            }
            // Shortcut
            $shortButton = $buttonBar->makeShortcutButton()
                ->setModuleName($this->MCONF['name'])
                ->setGetVariables(['P']);
            $buttonBar->addButton($shortButton);
        }
    }
}
