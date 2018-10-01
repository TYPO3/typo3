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
use TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller for system information processing
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class SystemInformationController
{
    /**
     * @var SystemInformationToolbarItem
     */
    protected $toolbarItem;

    /**
     * Set up dependencies
     */
    public function __construct()
    {
        $this->toolbarItem = GeneralUtility::makeInstance(SystemInformationToolbarItem::class);
    }

    /**
     * Renders the menu for AJAX calls
     *
     * @return ResponseInterface
     */
    public function renderMenuAction(): ResponseInterface
    {
        return new HtmlResponse($this->toolbarItem->getDropDown());
    }
}
