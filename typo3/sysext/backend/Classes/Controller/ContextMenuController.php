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
use TYPO3\CMS\Backend\ContextMenu\ContextMenu;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Script Class for the Context Sensitive Menu in TYPO3
 */
class ContextMenuController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_misc.xlf');
    }

    /**
     * Renders a context menu
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getContextMenuAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $contextMenu = GeneralUtility::makeInstance(ContextMenu::class);

        $params = $request->getQueryParams();
        $context = isset($params['context']) ? $params['context'] : '';
        $items = $contextMenu->getItems($params['table'], $params['uid'], $context);
        if (!is_array($items)) {
            $items = [];
        }
        $response->getBody()->write(json_encode($items));
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function clipboardAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var Clipboard $clipboard */
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard();
        $clipboard->lockToNormal();

        $clipboard->setCmd($request->getQueryParams()['CB']);
        $clipboard->cleanCurrent();

        $clipboard->endClipboard();
        $response->getBody()->write(json_encode([]));
        return $response;
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
