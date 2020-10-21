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

namespace TYPO3\CMS\Backend\View\BackendLayout\Grid;

use TYPO3\CMS\Backend\View\PageLayoutContext;
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
 *
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
abstract class AbstractGridObject
{
    /**
     * @var PageLayoutContext
     */
    protected $context;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    public function __construct(PageLayoutContext $context)
    {
        $this->context = $context;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    public function getUniqueId(): string
    {
        return StringUtility::getUniqueId();
    }

    public function getContext(): PageLayoutContext
    {
        return $this->context;
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
