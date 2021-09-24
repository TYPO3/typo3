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

namespace TYPO3\CMS\Opendocs\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Opendocs\Backend\ToolbarItems\OpendocsToolbarItem;
use TYPO3\CMS\Opendocs\Service\OpenDocumentService;

/**
 * Controller for documents processing
 *
 * Contains AJAX endpoints of the open docs toolbar item click actions
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class OpenDocumentController
{
    /**
     * @var OpenDocumentService
     */
    protected $documents;

    /**
     * @var OpendocsToolbarItem
     */
    protected $toolbarItem;

    /**
     * Set up dependencies
     */
    public function __construct()
    {
        $this->documents = GeneralUtility::makeInstance(OpenDocumentService::class);
        $this->toolbarItem = GeneralUtility::makeInstance(OpendocsToolbarItem::class);
    }

    /**
     * Renders the menu so that it can be returned as response to an AJAX call
     *
     * @return ResponseInterface
     */
    public function renderMenu(): ResponseInterface
    {
        return new HtmlResponse($this->toolbarItem->getDropDown());
    }

    /**
     * Closes a document and returns the updated menu
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function closeDocument(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = $request->getParsedBody()['md5sum'] ?? $request->getQueryParams()['md5sum'] ?? null;

        if ($identifier) {
            $this->documents->closeDocument($identifier);
        } else {
            $this->documents->closeAllDocuments();
        }

        return $this->renderMenu();
    }
}
