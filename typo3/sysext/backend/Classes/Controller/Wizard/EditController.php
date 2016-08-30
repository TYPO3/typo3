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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Script Class for redirecting a backend user to the editing form when an "Edit wizard" link was clicked in FormEngine somewhere
 */
class EditController extends AbstractWizardController
{
    /**
     * Wizard parameters, coming from FormEngine linking to the wizard.
     *
     * @var array
     */
    public $P;

    /**
     * Boolean; if set, the window will be closed by JavaScript
     *
     * @var int
     */
    public $doClose;

    /**
     * A little JavaScript to close the open window.
     *
     * @var string
     */
    protected $closeWindow = '<script language="javascript" type="text/javascript">close();</script>';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_wizards.xlf');
        $GLOBALS['SOBE'] = $this;

        $this->init();
    }

    /**
     * Initialization of the script
     *
     * @return void
     */
    protected function init()
    {
        $this->P = GeneralUtility::_GP('P');
        // Used for the return URL to FormEngine so that we can close the window.
        $this->doClose = GeneralUtility::_GP('doClose');
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
        $content = $this->main();
        $response->getBody()->write($content);
        return $response;
    }

    /**
     * Main function
     * Makes a header-location redirect to an edit form IF POSSIBLE from the passed data - otherwise the window will
     * just close.
     *
     * @return string
     */
    public function main()
    {
        if ($this->doClose) {
            return $this->closeWindow;
        }
        // Initialize:
        $table = $this->P['table'];
        $field = $this->P['field'];
        $config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        $fTable = $config['foreign_table'];

        $urlParameters = [
            'returnUrl' => BackendUtility::getModuleUrl('wizard_edit', ['doClose' => 1])
        ];

        // Detecting the various allowed field type setups and acting accordingly.
        if (is_array($config)
            && $config['type'] === 'select'
            && !$config['MM']
            && $config['maxitems'] <= 1 && MathUtility::canBeInterpretedAsInteger($this->P['currentValue'])
            && $this->P['currentValue'] && $fTable
        ) {
            // SINGLE value
            $urlParameters['edit[' . $fTable . '][' . $this->P['currentValue'] . ']'] = 'edit';
            // Redirect to FormEngine
            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
            HttpUtility::redirect($url);
        } elseif (is_array($config)
            && $this->P['currentSelectedValues']
            && ($config['type'] === 'select'
                && $config['foreign_table']
                || $config['type'] === 'group'
                && $config['internal_type'] === 'db'
            )
        ) {
            // MULTIPLE VALUES:
            // Init settings:
            $allowedTables = $config['type'] === 'group' ? $config['allowed'] : $config['foreign_table'];
            $prependName = 1;
            // Selecting selected values into an array:
            /** @var RelationHandler $relationHandler */
            $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
            $relationHandler->start($this->P['currentSelectedValues'], $allowedTables);
            $value = $relationHandler->getValueArray($prependName);
            // Traverse that array and make parameters for FormEngine
            foreach ($value as $rec) {
                $recTableUidParts = GeneralUtility::revExplode('_', $rec, 2);
                $urlParameters['edit[' . $recTableUidParts[0] . '][' . $recTableUidParts[1] . ']'] = 'edit';
            }
            // Redirect to FormEngine
            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
            HttpUtility::redirect($url);
        } else {
            return $this->closeWindow;
        }
    }

    /**
     * Printing a little JavaScript to close the open window.
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function closeWindow()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->closeWindow;
        die;
    }
}
