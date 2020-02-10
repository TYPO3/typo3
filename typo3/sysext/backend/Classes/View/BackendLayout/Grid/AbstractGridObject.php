<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\View\BackendLayout\Grid;

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

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Base class for objects which constitute a page layout grid.
 *
 * Contains shared properties and functions available to all such objects.
 *
 * @see Grid
 * @see GridRow
 * @see GridColumn
 * @see GridColumnItem
 * @see LanguageColumn
 */
abstract class AbstractGridObject
{
    /**
     * @var BackendLayout
     */
    protected $backendLayout;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var string|null
     */
    protected $uniqueId;

    public function __construct(BackendLayout $backendLayout)
    {
        $this->backendLayout = $backendLayout;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId ?? $this->uniqueId = StringUtility::getUniqueId();
    }

    public function getBackendLayout(): BackendLayout
    {
        return $this->backendLayout;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
