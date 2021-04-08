<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Controller\Wizard;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Script Class for rendering the Table Wizard
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class TableController extends AbstractWizardController
{
    /**
     * If TRUE, <input> fields are shown instead of textareas.
     *
     * @var bool
     */
    protected $inputStyle = false;

    /**
     * If set, the string version of the content is interpreted/written as XML
     * instead of the original line-based kind. This variable still needs binding
     * to the wizard parameters - but support is ready!
     *
     * @var int
     */
    protected $xmlStorage = 0;

    /**
     * Number of new rows to add in bottom of wizard
     *
     * @var int
     */
    protected $numNewRows = 1;

    /**
     * Name of field in parent record which MAY contain the number of columns for the table
     * here hardcoded to the value of tt_content. Should be set by FormEngine parameters (from P)
     *
     * @var string
     */
    protected $colsFieldName = 'cols';

    /**
     * Wizard parameters, coming from FormEngine linking to the wizard.
     *
     * @var array
     */
    protected $P;

    /**
     * The array which is constantly submitted by the multidimensional form of this wizard.
     *
     * @var array
     */
    protected $TABLECFG;

    /**
     * Table parsing
     * quoting of table cells
     *
     * @var string
     */
    protected $tableParsing_quote;

    /**
     * delimiter between table cells
     *
     * @var string
     */
    protected $tableParsing_delimiter;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Element/TableWizardElement');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_wizards.xlf', 'table_');
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_wizards.xlf');
        $this->init($request);

        $normalizedParams = $request->getAttribute('normalizedParams');
        $requestUri = $normalizedParams->getRequestUri();
        [$rUri] = explode('#', $requestUri);
        $content = '<form action="' . htmlspecialchars($rUri) . '" method="post" id="TableController" name="wizardForm">';
        if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
            $tableWizard = $this->renderTableWizard($request);

            if ($tableWizard instanceof RedirectResponse) {
                return $tableWizard;
            }

            $content .= '<h2>' . htmlspecialchars($this->getLanguageService()->getLL('table_title')) . '</h2>'
                . '<div>' . $tableWizard . '</div>';
        } else {
            $content .= '<h2>' . htmlspecialchars($this->getLanguageService()->getLL('table_title')) . '</h2>'
                . '<div><span class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('table_noData')) . '</span></div>';
        }
        $content .= '</form>';

        // Setting up the buttons and markers for docHeader
        $this->getButtons();
        // Build the <body> for the module
        $this->moduleTemplate->setContent($content);

        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Initialization of the class
     *
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        // GPvars:
        $this->P = $parsedBody['P'] ?? $queryParams['P'] ?? null;
        $this->TABLECFG = $parsedBody['TABLE'] ?? $queryParams['TABLE'] ?? null;
        // Setting options:
        $this->xmlStorage = $this->P['params']['xmlOutput'];
        $this->numNewRows = MathUtility::forceIntegerInRange($this->P['params']['numNewRows'], 1, 10, 1);
        // Textareas or input fields:
        $this->inputStyle = (bool)($this->TABLECFG['textFields'] ?? true);
        $this->tableParsing_delimiter = '|';
        $this->tableParsing_quote = '';
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->P['table'] && $this->P['field'] && $this->P['uid']) {
            // CSH
            $cshButton = $buttonBar->makeHelpButton()
                ->setModuleName('xMOD_csh_corebe')
                ->setFieldName('wizard_table_wiz');
            $buttonBar->addButton($cshButton);
            // Close
            $closeButton = $buttonBar->makeLinkButton()
                ->setHref($this->P['returnUrl'])
                ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                ->setShowLabelText(true);
            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            // Save
            $saveButton = $buttonBar->makeInputButton()
                ->setName('_savedok')
                ->setValue('1')
                ->setForm('TableController')
                ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
                ->setShowLabelText(true);
            $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            // Reload
            $reloadButton = $buttonBar->makeInputButton()
                ->setName('_refresh')
                ->setValue('1')
                ->setForm('TableController')
                ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->getLL('forms_refresh'));
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }

    /**
     * Draws the table wizard content
     *
     * @param ServerRequestInterface $request
     * @return string|ResponseInterface HTML content for the form.
     * @throws \RuntimeException
     */
    protected function renderTableWizard(ServerRequestInterface $request)
    {
        if (!$this->checkEditAccess($this->P['table'], $this->P['uid'])) {
            throw new \RuntimeException('Wizard Error: No access', 1349692692);
        }
        // First, check the references by selecting the record:
        $row = BackendUtility::getRecord($this->P['table'], $this->P['uid']);
        if (!is_array($row)) {
            throw new \RuntimeException('Wizard Error: No reference to record', 1294587125);
        }
        // This will get the content of the form configuration code field to us - possibly cleaned up,
        // saved to database etc. if the form has been submitted in the meantime.
        $tableCfgArray = $this->getConfiguration($row, $request);

        if ($tableCfgArray instanceof ResponseInterface) {
            return $tableCfgArray;
        }

        // Generation of the Table Wizards HTML code:
        $content = $this->getTableWizard($tableCfgArray);
        // Return content:
        return $content;
    }

    /**
     * Will get and return the configuration code string
     * Will also save (and possibly redirect/exit) the content if a save button has been pressed
     *
     * @param array $row Current parent record row
     * @param ServerRequestInterface $request
     * @return array|ResponseInterface Table config code in an array
     */
    protected function getConfiguration(array $row, ServerRequestInterface $request)
    {
        // Get delimiter settings
        $this->tableParsing_quote = $row['table_enclosure'] ? chr((int)$row['table_enclosure']) : '';
        $this->tableParsing_delimiter = $row['table_delimiter'] ? chr((int)$row['table_delimiter']) : '|';
        // If some data has been submitted, then construct
        if (isset($this->TABLECFG['c'])) {
            // Process incoming:
            $this->manipulateTable();
            // Convert to string (either line based or XML):
            if ($this->xmlStorage) {
                // Convert the input array to XML:
                $bodyText = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . GeneralUtility::array2xml($this->TABLECFG['c'], '', 0, 'T3TableWizard');
                // Setting cfgArr directly from the input:
                $configuration = $this->TABLECFG['c'];
            } else {
                // Convert the input array to a string of configuration code:
                $bodyText = $this->configurationArrayToString($this->TABLECFG['c']);
                // Create cfgArr from the string based configuration - that way it is cleaned up
                // and any incompatibilities will be removed!
                $configuration = $this->configurationStringToArray($bodyText, (int)$row[$this->colsFieldName]);
            }
            // If a save button has been pressed, then save the new field content:
            if ($_POST['_savedok'] || $_POST['_saveandclosedok']) {
                // Get DataHandler object:
                /** @var DataHandler $dataHandler */
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                // Put content into the data array:
                $data = [];
                if ($this->P['flexFormPath']) {
                    // Current value of flexForm path:
                    $currentFlexFormData = GeneralUtility::xml2array($row[$this->P['field']]);
                    /** @var FlexFormTools $flexFormTools */
                    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                    $flexFormTools->setArrayValueByPath($this->P['flexFormPath'], $currentFlexFormData, $bodyText);
                    $data[$this->P['table']][$this->P['uid']][$this->P['field']] = $currentFlexFormData;
                } else {
                    $data[$this->P['table']][$this->P['uid']][$this->P['field']] = $bodyText;
                }
                // Perform the update:
                $dataHandler->start($data, []);
                $dataHandler->process_datamap();
                // If the save/close button was pressed, then redirect the screen:
                if ($_POST['_saveandclosedok']) {
                    return new RedirectResponse(GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']));
                }
            }
        } else {
            // If nothing has been submitted, load the $bodyText variable from the selected database row:
            if ($this->xmlStorage) {
                $configuration = GeneralUtility::xml2array($row[$this->P['field']]);
            } else {
                if ($this->P['flexFormPath']) {
                    // Current value of flexForm path:
                    $currentFlexFormData = GeneralUtility::xml2array($row[$this->P['field']]);
                    /** @var FlexFormTools $flexFormTools */
                    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                    $configuration = $flexFormTools->getArrayValueByPath(
                        $this->P['flexFormPath'],
                        $currentFlexFormData
                    );
                    $configuration = $this->configurationStringToArray($configuration, 0);
                } else {
                    // Regular line based table configuration:
                    $columns = $row[$this->colsFieldName] ?? 0;
                    $configuration = $this->configurationStringToArray($row[$this->P['field']] ?? '', (int)$columns);
                }
            }
            $configuration = is_array($configuration) ? $configuration : [];
        }
        return $configuration;
    }

    /**
     * Creates the HTML for the Table Wizard:
     *
     * @param array $configuration Table config array
     * @return string HTML for the table wizard
     */
    protected function getTableWizard(array $configuration): string
    {
        return sprintf(
            '<typo3-backend-table-wizard %s></typo3-backend-table-wizard>',
            GeneralUtility::implodeAttributes([
                'id' => 'typo3-tablewizard',
                'type' => $this->inputStyle ? 'input' : 'textarea',
                'append-rows' => (string)$this->numNewRows,
                'table' => GeneralUtility::jsonEncodeForHtmlAttribute($configuration, false),
            ], true)
        );
    }

    /**
     * Detects if a control button (up/down/around/delete) has been pressed for an item and accordingly it will
     * manipulate the internal TABLECFG array
     */
    protected function manipulateTable(): void
    {
        // Convert line breaks to <br /> tags:
        foreach ($this->TABLECFG['c'] as $a => $value) {
            foreach ($this->TABLECFG['c'][$a] as $b => $value2) {
                $this->TABLECFG['c'][$a][$b] = str_replace(
                    [CR, LF],
                    ['', '<br />'],
                    $this->TABLECFG['c'][$a][$b]
                );
            }
        }
    }

    /**
     * Converts the input array to a configuration code string
     *
     * @param array $cfgArr Array of table configuration (follows the input structure from the table wizard POST form)
     * @return string The array converted into a string with line-based configuration.
     * @see configurationStringToArray()
     */
    protected function configurationArrayToString(array $cfgArr): string
    {
        $inLines = [];
        // Traverse the elements of the table wizard and transform the settings into configuration code.
        foreach ($cfgArr as $valueA) {
            $thisLine = [];
            foreach ($valueA as $valueB) {
                $thisLine[] = $this->tableParsing_quote
                    . str_replace($this->tableParsing_delimiter, '', $valueB) . $this->tableParsing_quote;
            }
            $inLines[] = implode($this->tableParsing_delimiter, $thisLine);
        }
        // Finally, implode the lines into a string:
        return implode(LF, $inLines);
    }

    /**
     * Converts the input configuration code string into an array
     *
     * @param string $configurationCode Configuration code
     * @param int $columns Default number of columns
     * @return array Configuration array
     * @see configurationArrayToString()
     */
    protected function configurationStringToArray(string $configurationCode, int $columns): array
    {
        // Explode lines in the configuration code - each line is a table row.
        $tableLines = explode(LF, $configurationCode);
        // Setting number of columns
        // auto...
        if (!$columns && trim($tableLines[0])) {
            $exploded = explode($this->tableParsing_delimiter, $tableLines[0]);
            $columns = is_array($exploded) ? count($exploded) : 0;
        }
        $columns = $columns ?: 4;
        // Traverse the number of table elements:
        $configurationArray = [];
        foreach ($tableLines as $key => $value) {
            // Initialize:
            $valueParts = explode($this->tableParsing_delimiter, $value);
            // Traverse columns:
            for ($a = 0; $a < $columns; $a++) {
                if ($this->tableParsing_quote
                    && $valueParts[$a][0] === $this->tableParsing_quote
                    && substr($valueParts[$a], -1, 1) === $this->tableParsing_quote
                ) {
                    $valueParts[$a] = substr(trim($valueParts[$a]), 1, -1);
                }
                $configurationArray[$key][$a] = (string)$valueParts[$a];
            }
        }
        return $configurationArray;
    }
}
