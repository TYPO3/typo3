<?php
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for grid wizard
 */
class BackendLayoutWizardController extends AbstractModule
{
    /**
     * GET vars:
     * Wizard parameters, coming from TCEforms linking to the wizard.
     * @var array
     */
    public $P;

    /**
     * Accumulated content.
     * @var string
     */
    public $content;

    /**
     * @var string
     */
    public $formName;

    /**
     * @var string
     */
    public $fieldName;

    /**
     * @var array
     */
    protected $rows;

    /**
     * @var int
     */
    protected $colCount;

    /**
     * @var int
     */
    protected $rowCount;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * Initialises the Class
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function init()
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:lang/locallang_wizards.xlf');

        // Setting GET vars (used in frameset script):
        $this->P = GeneralUtility::_GP('P');
        $this->formName = $this->P['formName'];
        $this->fieldName = $this->P['itemName'];
        $hmac_validate = GeneralUtility::hmac($this->formName . $this->fieldName, 'wizard_js');
        if (!$this->P['hmac'] || ($this->P['hmac'] !== $hmac_validate)) {
            throw new \InvalidArgumentException('Hmac Validation failed for backend_layout wizard', 1385811397);
        }
        $uid = (int)$this->P['uid'];

        /** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/GridEditor');
        $pageRenderer->addInlineSetting(
            'ContextHelp',
            'moduleUrl',
            BackendUtility::getModuleUrl(
                'help_CshmanualCshmanual',
                array(
                    'tx_cshmanual_help_cshmanualcshmanual' => array(
                        'controller' => 'Help',
                        'action' => 'detail'
                    )
                )
            )
        );
        $pageRenderer->addJsInlineCode('storeData', '
            function storeData(data) {
                if (parent.opener && parent.opener.document && parent.opener.document.' . $this->formName
                . ' && parent.opener.document.' . $this->formName . '['
                    . GeneralUtility::quoteJSvalue($this->fieldName) . ']) {
                    parent.opener.document.' . $this->formName . '['
                    . GeneralUtility::quoteJSvalue($this->fieldName) . '].value = data;
                    parent.opener.TBE_EDITOR.fieldChanged("backend_layout","' . $uid . '","config",'
                    . '"data[backend_layout][' . $uid . '][config]");
                }
            }
            ', false);
        $languageLabels = array(
            'save' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_labelSave')),
            'title' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_windowTitle')),
            'editCell' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_editCell')),
            'mergeCell' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_mergeCell')),
            'splitCell' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_splitCell')),
            'name' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_name')),
            'column' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_column')),
            'notSet' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_notSet')),
            'nameHelp' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_nameHelp')),
            'columnHelp' => htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_columnHelp'))
        );
        $pageRenderer->addInlineLanguageLabelArray($languageLabels);
        // Select record
        $record = $this->getDatabaseConnection()->exec_SELECTgetRows(
            $this->P['field'],
            $this->P['table'],
            'uid=' . (int)$this->P['uid']
        );
        if (trim($record[0][$this->P['field']]) == '') {
            $rows = array(array(array('colspan' => 1, 'rowspan' => 1, 'spanned' => false, 'name' => '')));
            $colCount = 1;
            $rowCount = 1;
        } else {
            // load TS parser
            $parser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $parser->parse($record[0][$this->P['field']]);
            $data = $parser->setup['backend_layout.'];
            $rows = array();
            $colCount = $data['colCount'];
            $rowCount = $data['rowCount'];
            $dataRows = $data['rows.'];
            $spannedMatrix = array();
            for ($i = 1; $i <= $rowCount; $i++) {
                $cells = array();
                $row = array_shift($dataRows);
                $columns = $row['columns.'];
                for ($j = 1; $j <= $colCount; $j++) {
                    $cellData = array();
                    if (!$spannedMatrix[$i][$j]) {
                        if (is_array($columns) && !empty($columns)) {
                            $column = array_shift($columns);
                            if (isset($column['colspan'])) {
                                $cellData['colspan'] = (int)$column['colspan'];
                                $columnColSpan = (int)$column['colspan'];
                                if (isset($column['rowspan'])) {
                                    $columnRowSpan = (int)$column['rowspan'];
                                    for ($spanRow = 0; $spanRow < $columnRowSpan; $spanRow++) {
                                        for ($spanColumn = 0; $spanColumn < $columnColSpan; $spanColumn++) {
                                            $spannedMatrix[$i + $spanRow][$j + $spanColumn] = 1;
                                        }
                                    }
                                } else {
                                    for ($spanColumn = 0; $spanColumn < $columnColSpan; $spanColumn++) {
                                        $spannedMatrix[$i][$j + $spanColumn] = 1;
                                    }
                                }
                            } else {
                                $cellData['colspan'] = 1;
                                if (isset($column['rowspan'])) {
                                    $columnRowSpan = (int)$column['rowspan'];
                                    for ($spanRow = 0; $spanRow < $columnRowSpan; $spanRow++) {
                                        $spannedMatrix[$i + $spanRow][$j] = 1;
                                    }
                                }
                            }
                            if (isset($column['rowspan'])) {
                                $cellData['rowspan'] = (int)$column['rowspan'];
                            } else {
                                $cellData['rowspan'] = 1;
                            }
                            if (isset($column['name'])) {
                                $cellData['name'] = $column['name'];
                            }
                            if (isset($column['colPos'])) {
                                $cellData['column'] = (int)$column['colPos'];
                            }
                        }
                    } else {
                        $cellData = array('colspan' => 1, 'rowspan' => 1, 'spanned' => 1);
                    }
                    $cells[] = $cellData;
                }
                $rows[] = $cells;
                if (!empty($spannedMatrix[$i]) && is_array($spannedMatrix[$i])) {
                    ksort($spannedMatrix[$i]);
                }
            }
        }
        $this->rows = $rows;
        $this->colCount = (int)$colCount;
        $this->rowCount = (int)$rowCount;
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

        $this->setPagePath($this->P['table'], $this->P['uid']);

        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Creates the correct path to the current record
     *
     * @param string $table
     * @param int $uid
     */
    protected function setPagePath($table, $uid)
    {
        $uid = (int)$uid;

        if ($table === 'pages') {
            $pageId = $uid;
        } else {
            $record = BackendUtility::getRecord($table, $uid, '*', '', false);
            $pageId = $record['pid'];
        }

        $pageAccess = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(1));
        if (is_array($pageAccess)) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageAccess);
        }
    }

    /**
     * Main Method, rendering either colorpicker or frameset depending on ->showPicker
     *
     * @return void
     */
    public function main()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $lang = $this->getLanguageService();

        $saveButton = $buttonBar->makeInputButton()
            ->setName('_savedok')
            ->setValue('1')
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'))
            ->setClasses('t3js-grideditor-savedok')
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL));

        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setName('_savedokandclose')
            ->setValue('1')
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc'))
            ->setClasses('t3js-grideditor-savedokclose')
            ->setIcon(
                $this->moduleTemplate->getIconFactory()->getIcon('actions-document-save-close', Icon::SIZE_SMALL)
            );

        $splitButton = $buttonBar->makeSplitButton()
            ->addItem($saveButton)
            ->addItem($saveAndCloseButton);
        $buttonBar->addButton($splitButton);

        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
            ->setOnClick('window.close();return true;')
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 30);

        $markup = array();
        $markup[] = '';
        $markup[] = '<table class="grideditor table table-bordered"">';
        $markup[] = '    <tr>';
        $markup[] = '        <td class="editor_cell">';
        $markup[] = '           <div id="editor" class="t3js-grideditor" data-data="' . htmlspecialchars(
            json_encode(
                $this->rows,
                JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
            )
        ) . '" '
        . 'data-rowcount="' . (int)$this->rowCount . '" '
        . 'data-colcount="' . (int)$this->colCount . '">';
        $markup[] = '            </div>';
        $markup[] = '        </td>';
        $markup[] = '        <td>';
        $markup[] = '            <div class="btn-group-vertical">';
        $markup[] = '               <a class="btn btn-default btn-sm t3js-grideditor-addcolumn" href="#" title="'
            . htmlspecialchars($lang->getLL('grid_addColumn')) . '">';
        $markup[] = '                <i class="fa fa-fw fa-arrow-right"></i>';
        $markup[] = '               </a>';
        $markup[] = '               <a class="btn btn-default btn-sm t3js-grideditor-removecolumn" href="#" title="'
            . htmlspecialchars($lang->getLL('grid_removeColumn')) . '">';
        $markup[] = '                <i class="fa fa-fw fa-arrow-left"></i>';
        $markup[] = '               </a>';
        $markup[] = '            </div>';
        $markup[] = '        </td>';
        $markup[] = '    </tr>';
        $markup[] = '    <tr>';
        $markup[] = '        <td colspan="2" align="center">';
        $markup[] = '            <div class="btn-group">';
        $markup[] = '               <a class="btn btn-default btn-sm t3js-grideditor-addrow" href="#" title="'
            . htmlspecialchars($lang->getLL('grid_addRow')) . '">';
        $markup[] = '                <i class="fa fa-fw fa-arrow-down"></i>';
        $markup[] = '               </a>';
        $markup[] = '               <a class="btn btn-default btn-sm t3js-grideditor-removerow" href="#" title="'
            . htmlspecialchars($lang->getLL('grid_removeRow')) . '">';
        $markup[] = '                <i class="fa fa-fw fa-arrow-up"></i>';
        $markup[] = '               </a>';
        $markup[] = '            </div>';
        $markup[] = '        </td>';
        $markup[] = '    </tr>';
        $markup[] = '</table>';

        $this->content .= implode(LF, $markup);
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
     * Returns the database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Gets the current backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
