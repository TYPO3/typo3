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
use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Backend\ToolbarItems\ShortcutToolbarItem;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller for shortcut processing
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ShortcutController
{
    /**
     * @var ShortcutToolbarItem
     */
    protected $shortcutToolbarItem;

    /**
     * @var ShortcutRepository
     */
    protected $shortcutRepository;

    /**
     * @var ModuleLoader
     */
    protected $moduleLoader;

    /**
     * Set up dependencies
     */
    public function __construct()
    {
        $this->shortcutToolbarItem = GeneralUtility::makeInstance(ShortcutToolbarItem::class);
        $this->shortcutRepository = GeneralUtility::makeInstance(ShortcutRepository::class);
        // Needed to get the correct icons when reloading the menu after saving it
        $this->moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
        $this->moduleLoader->load($GLOBALS['TBE_MODULES']);
    }

    /**
     * Renders the menu so that it can be returned as response to an AJAX call
     *
     * @return ResponseInterface
     */
    public function menuAction(): ResponseInterface
    {
        return new HtmlResponse($this->shortcutToolbarItem->getDropDown());
    }

    /**
     * Creates a shortcut through an AJAX call
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function addAction(ServerRequestInterface $request): ResponseInterface
    {
        $result = 'success';
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $url = rawurldecode($parsedBody['url'] ?? $queryParams['url'] ?? '');

        if ($this->shortcutRepository->shortcutExists($url)) {
            $result = 'alreadyExists';
        } else {
            $moduleName = $parsedBody['module'] ?? '';
            $parentModuleName = $parsedBody['motherModName'] ?? '';
            $shortcutName = $parsedBody['displayName'] ?? '';
            $success = $this->shortcutRepository->addShortcut($url, $moduleName, $parentModuleName, $shortcutName);

            if (!$success) {
                $result = 'failed';
            }
        }

        return new HtmlResponse($result);
    }

    /**
     * Fetches the available shortcut groups, renders a form so it can be saved later on, called via AJAX
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface the full HTML for the form
     */
    public function showEditFormAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $selectedShortcutId = (int)($parsedBody['shortcutId'] ?? $queryParams['shortcutId']);
        $selectedShortcutGroupId = (int)($parsedBody['shortcutGroup'] ?? $queryParams['shortcutGroup']);
        $selectedShortcut = $this->shortcutRepository->getShortcutById($selectedShortcutId);
        $shortcutGroups = $this->shortcutRepository->getShortcutGroups();

        $editFormView = $this->getFluidTemplateObject('EditForm.html');
        $editFormView->assign('selectedShortcutId', $selectedShortcutId);
        $editFormView->assign('selectedShortcutGroupId', $selectedShortcutGroupId);
        $editFormView->assign('selectedShortcut', $selectedShortcut);
        $editFormView->assign('shortcutGroups', $shortcutGroups);

        return new HtmlResponse($editFormView->render());
    }

    /**
     * Gets called when a shortcut is changed, checks whether the user has
     * permissions to do so and saves the changes if everything is ok
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function updateAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $shortcutId = (int)($parsedBody['shortcutId'] ?? $queryParams['shortcutId'] ?? 0);
        $shortcutTitle = strip_tags($parsedBody['shortcutTitle'] ?? $queryParams['shortcutTitle'] ?? '');
        $shortcutGroupId = (int)($parsedBody['shortcutGroup'] ?? $queryParams['shortcutGroup'] ?? 0);

        $success = $this->shortcutRepository->updateShortcut($shortcutId, $shortcutTitle, $shortcutGroupId);

        return new HtmlResponse($success ? $shortcutTitle : 'failed');
    }

    /**
     * Deletes a shortcut through an AJAX call
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function removeAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $shortcutId = (int)($parsedBody['shortcutId'] ?? $queryParams['shortcutId'] ?? 0);
        $success = $this->shortcutRepository->removeShortcut($shortcutId);

        return new JsonResponse(['success' => $success]);
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @param string $templateFilename
     * @return StandaloneView
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \InvalidArgumentException
     * @internal param string $templateFile
     */
    protected function getFluidTemplateObject(string $templateFilename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/ShortcutToolbarItem']);
        $view->setTemplate($templateFilename);
        $view->getRequest()->setControllerExtensionName('Backend');

        return $view;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
