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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Controller for shortcut processing.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class ShortcutController
{
    public function __construct(
        protected readonly ShortcutToolbarItem $shortcutToolbarItem,
        protected readonly ShortcutRepository $shortcutRepository,
        protected readonly BackendViewFactory $backendViewFactory,
    ) {
    }

    /**
     * Renders the menu so that it can be returned as response to an AJAX call.
     */
    public function menuAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->shortcutToolbarItem->setRequest($request);
        return new HtmlResponse($this->shortcutToolbarItem->getDropDown());
    }

    /**
     * Creates a shortcut through an AJAX call.
     */
    public function addAction(ServerRequestInterface $request): ResponseInterface
    {
        $result = 'success';
        $responseCode = 201;
        $parsedBody = $request->getParsedBody();
        $routeIdentifier = $parsedBody['routeIdentifier'] ?? '';
        $arguments = $parsedBody['arguments'] ?? '';
        if ($routeIdentifier === '') {
            $result = 'missingRoute';
            $responseCode = 400;
        } elseif ($this->shortcutRepository->shortcutExists($routeIdentifier, $arguments)) {
            $result = 'alreadyExists';
            $responseCode = 200;
        } else {
            $shortcutName = $parsedBody['displayName'] ?? '';
            $success = $this->shortcutRepository->addShortcut($routeIdentifier, $arguments, $shortcutName);
            if (!$success) {
                $result = 'failed';
                $responseCode = 500;
            }
        }
        return new JsonResponse(['result' => $result], $responseCode);
    }

    /**
     * Fetches the available shortcut groups, renders a form so it can be saved later on, called via AJAX.
     */
    public function showEditFormAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $selectedShortcutId = (int)($queryParams['shortcutId'] ?? 0);
        $selectedShortcutGroupId = (int)($queryParams['shortcutGroup'] ?? '');
        $selectedShortcut = $this->shortcutRepository->getShortcutById($selectedShortcutId);
        $shortcutGroups = $this->shortcutRepository->getShortcutGroups();
        $view = $this->backendViewFactory->create($request);
        $view->assignMultiple([
            'selectedShortcutId' => $selectedShortcutId,
            'selectedShortcutGroupId' => $selectedShortcutGroupId,
            'selectedShortcut' => $selectedShortcut,
            'shortcutGroups' => $shortcutGroups,
        ]);
        return new HtmlResponse($view->render('ToolbarItems/ShortcutToolbarItemEditForm'));
    }

    /**
     * Gets called when a shortcut is changed, checks whether the user has
     * permissions to do so and saves the changes if everything is ok.
     */
    public function updateAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $shortcutId = (int)($parsedBody['shortcutId'] ?? 0);
        $shortcutTitle = strip_tags($parsedBody['shortcutTitle'] ?? '');
        $shortcutGroupId = (int)($parsedBody['shortcutGroup'] ?? 0);
        $success = $this->shortcutRepository->updateShortcut($shortcutId, $shortcutTitle, $shortcutGroupId);
        return new HtmlResponse($success ? $shortcutTitle : 'failed');
    }

    /**
     * Deletes a shortcut through an AJAX call.
     */
    public function removeAction(ServerRequestInterface $request): ResponseInterface
    {
        $success = $this->shortcutRepository->removeShortcut((int)($request->getParsedBody()['shortcutId'] ?? 0));
        return new JsonResponse(['success' => $success]);
    }
}
