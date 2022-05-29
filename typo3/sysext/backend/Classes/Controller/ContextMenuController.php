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
use TYPO3\CMS\Backend\ContextMenu\ContextMenu;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for the Context Sensitive Menu in TYPO3
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ContextMenuController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
    }

    /**
     * Renders a context menu
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getContextMenuAction(ServerRequestInterface $request): ResponseInterface
    {
        $contextMenu = GeneralUtility::makeInstance(ContextMenu::class);

        $params = $request->getQueryParams();
        $context = $params['context'] ?? '';
        $items = $contextMenu->getItems($params['table'], $params['uid'], $context);
        if (!is_array($items)) {
            $items = [];
        }
        return new JsonResponse($items);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function clipboardAction(ServerRequestInterface $request): ResponseInterface
    {
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard($request);
        $clipboard->lockToNormal();

        $CB = array_replace_recursive($request->getQueryParams()['CB'] ?? [], $request->getParsedBody()['CB'] ?? []);
        $clipboard->setCmd($CB);
        $clipboard->cleanCurrent();

        $clipboard->endClipboard();
        return new JsonResponse([]);
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
