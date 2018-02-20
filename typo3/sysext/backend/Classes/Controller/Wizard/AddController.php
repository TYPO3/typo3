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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Script Class for adding new items to a group/select field. Performs proper redirection as needed.
 * Script is typically called after new child record was added and then adds the new child to select value of parent.
 */
class AddController extends AbstractWizardController
{
    /**
     * Content accumulation for the module.
     *
     * @var string
     */
    public $content;

    /**
     * If set, the DataHandler class is loaded and used to add the returning ID to the parent record.
     *
     * @var int
     */
    public $processDataFlag = 0;

    /**
     * Create new record -pid (pos/neg). If blank, return immediately
     *
     * @var int
     */
    public $pid;

    /**
     * The parent table we are working on.
     *
     * @var string
     */
    public $table;

    /**
     * Loaded with the created id of a record FormEngine returns ...
     *
     * @var int
     */
    public $id;

    /**
     * Wizard parameters, coming from TCEforms linking to the wizard.
     *
     * @var array
     */
    public $P;

    /**
     * Information coming back from the FormEngine script, telling what the table/id was of the newly created record.
     *
     * @var array
     */
    public $returnEditConf;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_wizards.xlf');
        $GLOBALS['SOBE'] = $this;

        $this->init();
    }

    /**
     * Initialization of the class.
     */
    protected function init()
    {
        // Init GPvars:
        $this->P = GeneralUtility::_GP('P');
        $this->returnEditConf = GeneralUtility::_GP('returnEditConf');
        // Get this record
        $record = BackendUtility::getRecord($this->P['table'], $this->P['uid']);
        // Set table:
        $this->table = $this->P['params']['table'];
        // Get TSconfig for it.
        $TSconfig = BackendUtility::getTCEFORM_TSconfig(
            $this->P['table'],
            is_array($record) ? $record : ['pid' => $this->P['pid']]
        );
        // Set [params][pid]
        if (substr($this->P['params']['pid'], 0, 3) === '###' && substr($this->P['params']['pid'], -3) === '###') {
            $keyword = substr($this->P['params']['pid'], 3, -3);
            if (strpos($keyword, 'PAGE_TSCONFIG_') === 0) {
                $this->pid = (int)$TSconfig[$this->P['field']][$keyword];
            } else {
                $this->pid = (int)$TSconfig['_' . $keyword];
            }
        } else {
            $this->pid = (int)$this->P['params']['pid'];
        }
        // Return if new record as parent (not possibly/allowed)
        if ($this->pid === '') {
            HttpUtility::redirect(GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']));
        }
        // Else proceed:
        // If a new id has returned from a newly created record...
        if ($this->returnEditConf) {
            $editConfiguration = json_decode($this->returnEditConf, true);
            if (is_array($editConfiguration[$this->table]) && MathUtility::canBeInterpretedAsInteger($this->P['uid'])) {
                // Getting id and cmd from returning editConf array.
                reset($editConfiguration[$this->table]);
                $this->id = (int)key($editConfiguration[$this->table]);
                $cmd = current($editConfiguration[$this->table]);
                // ... and if everything seems OK we will register some classes for inclusion and instruct the object
                // to perform processing later.
                if ($this->P['params']['setValue']
                    && $cmd === 'edit'
                    && $this->id
                    && $this->P['table']
                    && $this->P['field'] && $this->P['uid']
                ) {
                    $liveRecord = BackendUtility::getLiveVersionOfRecord($this->table, $this->id, 'uid');
                    if ($liveRecord) {
                        $this->id = $liveRecord['uid'];
                    }
                    $this->processDataFlag = 1;
                }
            }
        }
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
        return $response;
    }

    /**
     * Main function
     * Will issue a location-header, redirecting either BACK or to a new FormEngine instance...
     */
    public function main()
    {
        if ($this->returnEditConf) {
            if ($this->processDataFlag) {
                // Because OnTheFly can't handle MM relations with intermediate tables we use TcaDatabaseRecord here
                // Otherwise already stored relations are overwritten with the new entry
                /** @var TcaDatabaseRecord $formDataGroup */
                $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
                /** @var FormDataCompiler $formDataCompiler */
                $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
                $input = [
                    'tableName' => $this->P['table'],
                    'vanillaUid' => (int)$this->P['uid'],
                    'command' => 'edit',
                ];
                $result = $formDataCompiler->compile($input);
                $currentParentRow = $result['databaseRow'];

                // If that record was found (should absolutely be...), then init DataHandler and set, prepend or append
                // the record
                if (is_array($currentParentRow)) {
                    /** @var DataHandler $dataHandler */
                    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                    $data = [];
                    $recordId = $this->table . '_' . $this->id;
                    // Setting the new field data:
                    // If the field is a flexForm field, work with the XML structure instead:
                    if ($this->P['flexFormPath']) {
                        // Current value of flexForm path:
                        $currentFlexFormData = $currentParentRow[$this->P['field']];
                        /** @var FlexFormTools $flexFormTools */
                        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                        $currentFlexFormValueByPath = $flexFormTools->getArrayValueByPath(
                            $this->P['flexFormPath'],
                            $currentFlexFormData
                        );

                        // Compile currentFlexFormData to functional string
                        $currentFlexFormValues = [];
                        foreach ($currentFlexFormValueByPath as $value) {
                            $currentFlexFormValues[] = $value['table'] . '_' . $value['uid'];
                        }
                        $currentFlexFormValue = implode(',', $currentFlexFormValues);

                        $insertValue = '';
                        switch ((string)$this->P['params']['setValue']) {
                            case 'set':
                                $insertValue = $recordId;
                                break;
                            case 'append':
                                $insertValue = $currentFlexFormValue . ',' . $recordId;
                                break;
                            case 'prepend':
                                $insertValue = $recordId . ',' . $currentFlexFormValue;
                                break;
                        }
                        $insertValue = implode(',', GeneralUtility::trimExplode(',', $insertValue, true));
                        $data[$this->P['table']][$this->P['uid']][$this->P['field']] = [];
                        $flexFormTools->setArrayValueByPath(
                            $this->P['flexFormPath'],
                            $data[$this->P['table']][$this->P['uid']][$this->P['field']],
                            $insertValue
                        );
                    } else {
                        $currentValue = $currentParentRow[$this->P['field']];

                        // Normalize CSV values
                        if (!is_array($currentValue)) {
                            $currentValue = GeneralUtility::trimExplode(',', $currentValue, true);
                        }

                        // Normalize all items to "<table>_<uid>" format
                        $currentValue = array_map(function ($item) {
                            // Handle per-item table for "group" elements
                            if (is_array($item)) {
                                $item = $item['table'] . '_' . $item['uid'];
                            } else {
                                $item = $this->table . '_' . $item;
                            }

                            return $item;
                        }, $currentValue);

                        switch ((string)$this->P['params']['setValue']) {
                            case 'set':
                                $currentValue = [$recordId];
                                break;
                            case 'append':
                                $currentValue[] = $recordId;
                                break;
                            case 'prepend':
                                array_unshift($currentValue, $recordId);
                                break;
                        }

                        $data[$this->P['table']][$this->P['uid']][$this->P['field']] = implode(',', $currentValue);
                    }
                    // Submit the data:
                    $dataHandler->start($data, []);
                    $dataHandler->process_datamap();
                }
            }
            // Return to the parent FormEngine record editing session:
            HttpUtility::redirect(GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']));
        } else {
            // Redirecting to FormEngine with instructions to create a new record
            // AND when closing to return back with information about that records ID etc.
            $redirectUrl = BackendUtility::getModuleUrl('record_edit', [
                'returnEditConf' => 1,
                'edit[' . $this->P['params']['table'] . '][' . $this->pid . ']' => 'new',
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ]);
            HttpUtility::redirect($redirectUrl);
        }
    }
}
