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
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Script Class, creating object of \TYPO3\CMS\Core\DataHandling\DataHandler and
 * sending the posted data to the object.
 *
 * Used by many smaller forms/links in TYPO3, including the QuickEdit module.
 * Is not used by FormEngine though (main form rendering script) - that uses the same class (DataHandler) but makes its own initialization (to save the redirect request).
 * For all other cases than FormEngine it is recommended to use this script for submitting your editing forms - but the best solution in any case would probably be to link your application to FormEngine, that will give you easy form-rendering as well.
 */
class SimpleDataHandlerController
{
    /**
     * Array. Accepts options to be set in TCE object. Currently it supports "reverseOrder" (bool).
     *
     * @var array
     */
    public $flags;

    /**
     * Data array on the form [tablename][uid][fieldname] = value
     *
     * @var array
     */
    public $data;

    /**
     * Command array on the form [tablename][uid][command] = value.
     * This array may get additional data set internally based on clipboard commands send in CB var!
     *
     * @var array
     */
    public $cmd;

    /**
     * Array passed to ->setMirror.
     *
     * @var array
     */
    public $mirror;

    /**
     * Cache command sent to ->clear_cacheCmd
     *
     * @var string
     */
    public $cacheCmd;

    /**
     * Redirect URL. Script will redirect to this location after performing operations (unless errors has occurred)
     *
     * @var string
     */
    public $redirect;

    /**
     * Boolean. If set, errors will be printed on screen instead of redirection. Should always be used, otherwise you will see no errors if they happen.
     *
     * @var int
     */
    public $prErr;

    /**
     * Clipboard command array. May trigger changes in "cmd"
     *
     * @var array
     */
    public $CB;

    /**
     * Boolean. Update Page Tree Trigger. If set and the manipulated records are pages then the update page tree signal will be set.
     *
     * @var int
     */
    public $uPT;

    /**
     * TYPO3 Core Engine
     *
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    public $tce;

    /**
     * Constructor
     */
    public function __construct()
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
    }

    /**
     * Initialization of the class
     */
    public function init()
    {
        $beUser = $this->getBackendUser();
        // GPvars:
        $this->flags = GeneralUtility::_GP('flags');
        $this->data = GeneralUtility::_GP('data');
        $this->cmd = GeneralUtility::_GP('cmd');
        $this->mirror = GeneralUtility::_GP('mirror');
        $this->cacheCmd = GeneralUtility::_GP('cacheCmd');
        $this->redirect = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('redirect'));
        $this->prErr = GeneralUtility::_GP('prErr');
        $this->CB = GeneralUtility::_GP('CB');
        $this->uPT = GeneralUtility::_GP('uPT');
        // Creating DataHandler object
        $this->tce = GeneralUtility::makeInstance(DataHandler::class);
        // Configuring based on user prefs.
        if ($beUser->uc['recursiveDelete']) {
            // TRUE if the delete Recursive flag is set.
            $this->tce->deleteTree = 1;
        }
        if ($beUser->uc['copyLevels']) {
            // Set to number of page-levels to copy.
            $this->tce->copyTree = MathUtility::forceIntegerInRange($beUser->uc['copyLevels'], 0, 100);
        }
        if ($beUser->uc['neverHideAtCopy']) {
            $this->tce->neverHideAtCopy = 1;
        }
        $TCAdefaultOverride = $beUser->getTSConfigProp('TCAdefaults');
        if (is_array($TCAdefaultOverride)) {
            $this->tce->setDefaultsFromUserTS($TCAdefaultOverride);
        }
        // Reverse order.
        if ($this->flags['reverseOrder']) {
            $this->tce->reverseOrder = 1;
        }
    }

    /**
     * Clipboard pasting and deleting.
     */
    public function initClipboard()
    {
        if (is_array($this->CB)) {
            $clipObj = GeneralUtility::makeInstance(Clipboard::class);
            $clipObj->initializeClipboard();
            if ($this->CB['paste']) {
                $clipObj->setCurrentPad($this->CB['pad']);
                $this->cmd = $clipObj->makePasteCmdArray(
                    $this->CB['paste'],
                    $this->cmd,
                    isset($this->CB['update']) ? $this->CB['update'] : null
                );
            }
            if ($this->CB['delete']) {
                $clipObj->setCurrentPad($this->CB['pad']);
                $this->cmd = $clipObj->makeDeleteCmdArray($this->cmd);
            }
        }
    }

    /**
     * Executing the posted actions ...
     */
    public function main()
    {
        // LOAD DataHandler with data and cmd arrays:
        $this->tce->start($this->data, $this->cmd);
        if (is_array($this->mirror)) {
            $this->tce->setMirror($this->mirror);
        }
        // Register uploaded files
        $this->tce->process_uploads($_FILES);
        // Execute actions:
        $this->tce->process_datamap();
        $this->tce->process_cmdmap();
        // Clearing cache:
        if (!empty($this->cacheCmd)) {
            $this->tce->clear_cacheCmd($this->cacheCmd);
        }
        // Update page tree?
        if ($this->uPT && (isset($this->data['pages']) || isset($this->cmd['pages']))) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it just redirects to the given URL afterwards.
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->initClipboard();
        $this->main();

        // Write errors to flash message queue
        if ($this->prErr) {
            $this->tce->printLogErrorMessages();
        }
        if ($this->redirect) {
            $response = $response
                ->withHeader('Location', GeneralUtility::locationHeaderUrl($this->redirect))
                ->withStatus(303);
        }
        return $response;
    }

    /**
     * Processes all AJAX calls and returns a JSON formatted string
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function processAjaxRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        // do the regular / main logic
        $this->initClipboard();
        $this->main();

        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        $content = [
            'redirect' => $this->redirect,
            'messages' => [],
            'hasErrors' => false
        ];

        // Prints errors (= write them to the message queue)
        if ($this->prErr) {
            $content['hasErrors'] = true;
            $this->tce->printLogErrorMessages();
        }

        $messages = $flashMessageService->getMessageQueueByIdentifier()->getAllMessagesAndFlush();
        if (!empty($messages)) {
            foreach ($messages as $message) {
                $content['messages'][] = [
                    'title'    => $message->getTitle(),
                    'message'  => $message->getMessage(),
                    'severity' => $message->getSeverity()
                ];
                if ($message->getSeverity() === AbstractMessage::ERROR) {
                    $content['hasErrors'] = true;
                }
            }
        }

        $response->getBody()->write(json_encode($content));
        return $response;
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
