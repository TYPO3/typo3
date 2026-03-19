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

namespace TYPO3\CMS\Opendocs\Backend\ToolbarItems;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Toolbar item for displaying open documents in the backend top bar.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[Autoconfigure(public: true)]
class OpenDocumentToolbarItem implements ToolbarItemInterface, RequestAwareToolbarItemInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly BackendViewFactory $backendViewFactory,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Checks whether the user has access to this toolbar item.
     */
    public function checkAccess(): bool
    {
        return !(bool)($this->getBackendUser()->getTSConfig()['backendToolbarItem.']['tx_opendocs.']['disabled'] ?? false);
    }

    /**
     * Render toolbar icon via Fluid
     */
    public function getItem(): string
    {
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-opendocs']);
        return $view->render('ToolbarItems/ToolbarItem');
    }

    /**
     * This item has a drop-down.
     */
    public function hasDropDown(): bool
    {
        return true;
    }

    /**
     * Render drop-down.
     */
    public function getDropDown(): string
    {
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-opendocs']);
        return $view->render('ToolbarItems/DropDown');
    }

    /**
     * No additional attributes
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * Position relative to others
     */
    public function getIndex(): int
    {
        return 50;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
