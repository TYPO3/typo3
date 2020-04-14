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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
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
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the processRequest() method, it just redirects to the given URL afterwards.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $GLOBALS['SOBE'] = $this;
        $this->init($request);

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
        $GLOBALS['SOBE'] = $this;
        $this->init($request);

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
    protected function init(ServerRequestInterface $request): void
    {
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
