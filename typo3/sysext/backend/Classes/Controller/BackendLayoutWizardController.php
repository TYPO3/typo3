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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for grid wizard
 */
class BackendLayoutWizardController extends AbstractModule
{
    // GET vars:
    // Wizard parameters, coming from TCEforms linking to the wizard.
    /**
     * @var array
     */
    public $P;

    // Accumulated content.
    /**
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
        $pageRenderer->loadExtJS();
        $pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('backend')
            . 'Resources/Public/JavaScript/grideditor.js');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $pageRenderer->addInlineSetting(
            'ContextHelp',
            'moduleUrl',
            BackendUtility::getModuleUrl(
                'help_CshmanualCshmanual',
                [
                    'tx_cshmanual_help_cshmanualcshmanual' => [
                        'controller' => 'Help',
                        'action' => 'detail'
                    ]
                ]
            )
        );
        $pageRenderer->addJsInlineCode('storeData', '
			function storeData(data) {
				if (parent.opener && parent.opener.document && parent.opener.document.' . $this->formName . ' && parent.opener.document.' . $this->formName . '[' . GeneralUtility::quoteJSvalue($this->fieldName) . ']) {
					parent.opener.document.' . $this->formName . '[' . GeneralUtility::quoteJSvalue($this->fieldName) . '].value = data;
					parent.opener.TBE_EDITOR.fieldChanged("backend_layout","' . $uid . '","config","data[backend_layout][' . $uid . '][config]");
				}
			}
			', false);
        $languageLabels = [
            'save' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_labelSave', true),
            'title' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_windowTitle', true),
            'editCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_editCell', true),
            'mergeCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_mergeCell', true),
            'splitCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_splitCell', true),
            'name' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_name', true),
            'column' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_column', true),
            'notSet' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_notSet', true),
            'nameHelp' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_nameHelp', true),
            'columnHelp' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_columnHelp', true)
        ];
        $pageRenderer->addInlineLanguageLabelArray($languageLabels);
        // Select record
        $record = $this->getDatabaseConnection()->exec_SELECTgetRows(
            $this->P['field'],
            $this->P['table'],
            'uid=' . (int)$this->P['uid']
        );
        if (trim($record[0][$this->P['field']]) == '') {
            $rows = [[['colspan' => 1, 'rowspan' => 1, 'spanned' => false, 'name' => '']]];
            $colCount = 1;
            $rowCount = 1;
        } else {
            // load TS parser
            $parser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $parser->parse($record[0][$this->P['field']]);
            $data = $parser->setup['backend_layout.'];
            $rows = [];
            $colCount = $data['colCount'];
            $rowCount = $data['rowCount'];
            $dataRows = $data['rows.'];
            $spannedMatrix = [];
            for ($i = 1; $i <= $rowCount; $i++) {
                $cells = [];
                $row = array_shift($dataRows);
                $columns = $row['columns.'];
                for ($j = 1; $j <= $colCount; $j++) {
                    $cellData = [];
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
                        $cellData = ['colspan' => 1, 'rowspan' => 1, 'spanned' => 1];
                    }
                    $cells[] = $cellData;
                }
                $rows[] = $cells;
                if (!empty($spannedMatrix[$i]) && is_array($spannedMatrix[$i])) {
                    ksort($spannedMatrix[$i]);
                }
            }
        }
        $pageRenderer->addExtOnReadyCode('
			t3Grid = new TYPO3.Backend.t3Grid({
				data: ' . json_encode($rows, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . ',
				colCount: ' . (int)$colCount . ',
				rowCount: ' . (int)$rowCount . ',
				targetElement: \'editor\'
			});
			t3Grid.drawTable();
			');

        $this->moduleTemplate->getPageRenderer()->addCssFile(ExtensionManagementUtility::extRelPath('backend')
            . 'Resources/Public/Css/grideditor.css');
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

        $resourcePath = ExtensionManagementUtility::extRelPath('backend')
            . 'Resources/Public/Images/BackendLayoutWizard/';

        $saveButton = $buttonBar->makeInputButton()
            ->setName('_savedok')
            ->setValue('1')
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'))
            ->setOnClick('storeData(t3Grid.export2LayoutRecord());return true;')
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL));

        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setName('_savedokandclose')
            ->setValue('1')
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc'))
            ->setOnClick('storeData(t3Grid.export2LayoutRecord());window.close();return true;')
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

        $this->content .= '
		<table border="0" width="90%" height="90%" id="outer_container">
			<tr>
				<td class="editor_cell">
					<div id="editor">
					</div>
				</td>
				<td width="20" valign="center">
					<a class="addCol" href="#" title="' . $lang->getLL('grid_addColumn') . '" onclick="t3Grid.addColumn(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tableright.png" border="0" />
					</a><br />
					<a class="removeCol" href="#" title="' . $lang->getLL('grid_removeColumn') . '" onclick="t3Grid.removeColumn(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tableleft.png" border="0" />
					</a>
				</td>
			</tr>
			<tr>
				<td colspan="2" height="20" align="center">
					<a class="addCol" href="#" title="' . $lang->getLL('grid_addRow') . '" onclick="t3Grid.addRow(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tabledown.png" border="0" />
					</a>
					<a class="removeCol" href="#" title="' . $lang->getLL('grid_removeRow') . '" onclick="t3Grid.removeRow(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tableup.png" border="0" />
					</a>
				</td>
			</tr>
		</table>
		';
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
