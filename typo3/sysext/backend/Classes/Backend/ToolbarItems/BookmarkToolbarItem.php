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

namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Backend\Bookmark\BookmarkService;
use TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;

/**
 * Class to render the bookmark menu toolbar.
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
class BookmarkToolbarItem implements ToolbarItemInterface, RequestAwareToolbarItemInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly BookmarkService $bookmarkService,
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
        return $this->bookmarkService->isEnabled();
    }

    /**
     * Render bookmark icon.
     */
    public function getItem(): string
    {
        $view = $this->backendViewFactory->create($this->request);
        return $view->render('ToolbarItems/BookmarkToolbarItemItem');
    }

    /**
     * This item has a drop-down.
     */
    public function hasDropDown(): bool
    {
        return true;
    }

    /**
     * Render drop-down content.
     * The dropdown contains a custom element that fetches data via AJAX.
     */
    public function getDropDown(): string
    {
        $view = $this->backendViewFactory->create($this->request);
        return $view->render('ToolbarItems/BookmarkToolbarItemDropDown');
    }

    /**
     * This toolbar item needs no additional attributes.
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * Position relative to others.
     */
    public function getIndex(): int
    {
        return 20;
    }
}
