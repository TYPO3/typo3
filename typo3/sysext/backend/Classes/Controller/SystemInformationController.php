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
use TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem;
use TYPO3\CMS\Core\Http\HtmlResponse;

/**
 * Controller for system information processing. Used as ajax end point to update drop down
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class SystemInformationController
{
    public function __construct(
        private readonly SystemInformationToolbarItem $systemInformationToolbarItem,
    ) {}

    /**
     * Renders the menu for AJAX calls
     */
    public function renderMenuAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->systemInformationToolbarItem->setRequest($request);
        return new HtmlResponse($this->systemInformationToolbarItem->getDropDown());
    }
}
