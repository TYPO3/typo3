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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller which behaves as endpoint for clipboard requests, dispatched from
 * either the clipboard panel web component or any of the corresponding modules.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class ClipboardController
{
    private const ALLOWED_ACTIONS = ['getClipboardData'];

    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;
    protected Clipboard $clipboard;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->clipboard = GeneralUtility::makeInstance(Clipboard::class);
    }

    /**
     * Process incoming clipboard request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->clipboard->initializeClipboard($request);

        $CB = (array)($request->getParsedBody()['CB'] ?? []);
        if ($CB !== []) {
            // Execute commands.
            $this->clipboard->setCmd($CB);
        }

        // Clean up pad
        $this->clipboard->cleanCurrent();
        // Save the clipboard content
        $this->clipboard->endClipboard();

        $action = (string)($request->getQueryParams()['action'] ?? '');
        if (in_array($action, self::ALLOWED_ACTIONS, true)) {
            return $this->{$action . 'Action'}($request);
        }

        // Default response in case no dedicated action is requested.
        // This is usually done if only internal clipboard state is changed.
        return $this->createResponse(['success' => true, 'data' => []]);
    }

    protected function getClipboardDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $clipboardData = $this->clipboard->getClipboardData($request->getParsedBody()['table'] ?? '');

        // Add labels for the panel
        $lang = $this->getLanguageService();
        $clipboardLabels = [
            'clipboard' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.clipboard'),
            'copyElements' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:copyElements'),
            'moveElements' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:moveElements'),
            'copy' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy'),
            'cut' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut'),
            'info' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info'),
            'removeAll' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.removeAll'),
            'removeItem' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.removeItem'),
        ];

        return $this->createResponse([
            'success' => $clipboardData !== [],
            'data' => array_merge($clipboardData, ['labels' => $clipboardLabels]),
        ]);
    }

    protected function createResponse(array $data): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream(json_encode($data)));
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
