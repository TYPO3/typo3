<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
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
    use PublicPropertyDeprecationTrait;

    /**
     * Properties which have been moved to protected status from public
     *
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'flags' => 'Using $flags of class SimpleDataHandlerController from the outside is discouraged, as this variable is only used for internal storage.',
        'data' => 'Using $data of class SimpleDataHandlerController from the outside is discouraged, as this variable is only used for internal storage.',
        'cmd' => 'Using $cmd of class SimpleDataHandlerController from the outside is discouraged, as this variable is only used for internal storage.',
        'mirror' => 'Using $mirror of class SimpleDataHandlerController from the outside is discouraged, as this variable is only used for internal storage.',
        'cacheCmd' => 'Using $cacheCmd of class SimpleDataHandlerController from the outside is discouraged, as this variable is only used for internal storage.',
        'redirect' => 'Using $redirect of class SimpleDataHandlerController from the outside is discouraged, as this variable is only used for internal storage.',
        'CB' => 'Using $CB of class SimpleDataHandlerController from the outside is discouraged, as this variable is only used for internal storage.',
        'tce' => 'Using $tce of class SimpleDataHandlerController from the outside is discouraged, as this variable is only used for internal storage.',
    ];

    /**
     * Array. Accepts options to be set in TCE object. Currently it supports "reverseOrder" (bool).
     *
     * @var array
     */
    protected $flags;

    /**
     * Data array on the form [tablename][uid][fieldname] = value
     *
     * @var array
     */
    protected $data;

    /**
     * Command array on the form [tablename][uid][command] = value.
     * This array may get additional data set internally based on clipboard commands send in CB var!
     *
     * @var array
     */
    protected $cmd;

    /**
     * Array passed to ->setMirror.
     *
     * @var array
     */
    protected $mirror;

    /**
     * Cache command sent to ->clear_cacheCmd
     *
     * @var string
     */
    protected $cacheCmd;

    /**
     * Redirect URL. Script will redirect to this location after performing operations (unless errors has occurred)
     *
     * @var string
     */
    protected $redirect;

    /**
     * Clipboard command array. May trigger changes in "cmd"
     *
     * @var array
     */
    protected $CB;

    /**
     * TYPO3 Core Engine
     *
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected $tce;

    /**
     * Constructor
     */
    public function __construct()
    {
        // @deprecated since TYPO3 v9, will be obsolete in TYPO3 v10.0 with removal of init()
        $request = $GLOBALS['TYPO3_REQUEST'];
        $GLOBALS['SOBE'] = $this;
        // @deprecated since TYPO3 v9, will be moved out of __construct() in TYPO3 v10.0
        $this->init($request);
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the processRequest() method, it just redirects to the given URL afterwards.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeClipboard();
        $this->processRequest();

        // Write errors to flash message queue
        $this->tce->printLogErrorMessages();
        if ($this->redirect) {
            return new RedirectResponse(GeneralUtility::locationHeaderUrl($this->redirect), 303);
        }
        return new HtmlResponse('');
    }

    /**
     * Processes all AJAX calls and returns a JSON formatted string
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function processAjaxRequest(ServerRequestInterface $request): ResponseInterface
    {
        // do the regular / main logic
        $this->initializeClipboard();
        $this->processRequest();

        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        $content = [
            'redirect' => $this->redirect,
            'messages' => [],
            'hasErrors' => false
        ];

        // Prints errors (= write them to the message queue)
        $this->tce->printLogErrorMessages();

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
        return new JsonResponse($content);
    }

    /**
     * Initialization of the class
     *
     * @param ServerRequestInterface $request
     */
    public function init(ServerRequestInterface $request = null): void
    {
        if ($request === null) {
            // Method signature in TYPO3 v10.0: protected function init(ServerRequestInterface $request)
            trigger_error('SimpleDataHandlerController->init() will be set to protected in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
            $request = $GLOBALS['TYPO3_REQUEST'];
        }

        $beUser = $this->getBackendUser();

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // GPvars:
        $this->flags = $parsedBody['flags'] ?? $queryParams['flags'] ?? null;
        $this->data = $parsedBody['data'] ?? $queryParams['data'] ?? null;
        $this->cmd = $parsedBody['cmd'] ?? $queryParams['cmd'] ?? null;
        $this->mirror = $parsedBody['mirror'] ?? $queryParams['mirror'] ?? null;
        $this->cacheCmd = $parsedBody['cacheCmd'] ?? $queryParams['cacheCmd'] ?? null;
        $redirect = $parsedBody['redirect'] ?? $queryParams['redirect'] ?? '';
        $this->redirect = GeneralUtility::sanitizeLocalUrl($redirect);
        $this->CB = $parsedBody['CB'] ?? $queryParams['CB'] ?? null;
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
        // Reverse order.
        if ($this->flags['reverseOrder']) {
            $this->tce->reverseOrder = 1;
        }
    }

    /**
     * Clipboard pasting and deleting.
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function initClipboard()
    {
        trigger_error('SimpleDataHandlerController->initClipboard() will be replaced by protected method initializeClipboard() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->initializeClipboard();
    }

    /**
     * Executing the posted actions ...
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function main()
    {
        trigger_error('SimpleDataHandlerController->main() will be replaced by protected method processRequest() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->processRequest();
    }

    /**
     * Clipboard pasting and deleting.
     */
    protected function initializeClipboard(): void
    {
        if (is_array($this->CB)) {
            $clipObj = GeneralUtility::makeInstance(Clipboard::class);
            $clipObj->initializeClipboard();
            if ($this->CB['paste']) {
                $clipObj->setCurrentPad($this->CB['pad']);
                $this->cmd = $clipObj->makePasteCmdArray(
                    $this->CB['paste'],
                    $this->cmd,
                    $this->CB['update'] ?? null
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
    protected function processRequest(): void
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
        if (isset($this->data['pages']) || isset($this->cmd['pages'])) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
