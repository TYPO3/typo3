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
use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * Controller for shortcut processing.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ShortcutController
{
    protected ShortcutToolbarItem $shortcutToolbarItem;
    protected ShortcutRepository $shortcutRepository;
    protected ModuleLoader $moduleLoader;

    public function __construct(
        ShortcutToolbarItem $shortcutToolbarItem,
        ShortcutRepository $shortcutRepository,
        ModuleLoader $moduleLoader
    ) {
        $this->shortcutToolbarItem = $shortcutToolbarItem;
        $this->shortcutRepository = $shortcutRepository;
        // Needed to get the correct icons when reloading the menu after saving it
        $moduleLoader->load($GLOBALS['TBE_MODULES']);
        $this->moduleLoader = $moduleLoader;
    }

    /**
     * Renders the menu so that it can be returned as response to an AJAX call.
     */
    public function menuAction(): ResponseInterface
    {
        return new HtmlResponse($this->shortcutToolbarItem->getDropDown());
    }

    /**
     * Creates a shortcut through an AJAX call.
     */
    public function addAction(ServerRequestInterface $request): ResponseInterface
    {
        $result = 'success';
        $parsedBody = $request->getParsedBody();
        $routeIdentifier = $parsedBody['routeIdentifier'] ?? '';
        $arguments = $parsedBody['arguments'] ?? '';
        if ($routeIdentifier === '') {
            $result = 'missingRoute';
        } elseif ($this->shortcutRepository->shortcutExists($routeIdentifier, $arguments)) {
            $result = 'alreadyExists';
        } else {
            $shortcutName = $parsedBody['displayName'] ?? $queryParams['displayName'] ?? '';
            $success = $this->shortcutRepository->addShortcut($routeIdentifier, $arguments, $shortcutName);
            if (!$success) {
                $result = 'failed';
            }
        }
        return new HtmlResponse($result);
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
        $editFormView = $this->getFluidTemplateObject();
        $editFormView->assignMultiple([
            'selectedShortcutId' => $selectedShortcutId,
            'selectedShortcutGroupId' => $selectedShortcutGroupId,
            'selectedShortcut' => $selectedShortcut,
            'shortcutGroups' => $shortcutGroups,
        ]);
        return new HtmlResponse($editFormView->render('ToolbarItems/ShortcutToolbarItemEditForm'));
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

    protected function getFluidTemplateObject(): BackendTemplateView
    {
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        return $view;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
