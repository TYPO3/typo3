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

namespace TYPO3\CMS\Beuser\ViewHelpers;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Render permission icon group (user / group / others) of the "Access" module.
 *
 * Most of that could be done in fluid directly, but this ViewHelper
 * is much better performance wise.
 *
 * @internal
 */
final class PermissionsViewHelper extends AbstractViewHelper
{
    protected const MASKS = [1, 16, 2, 4, 8];

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    protected static array $cachePermissionLabels = [];

    public function initializeArguments(): void
    {
        $this->registerArgument('permission', 'int', 'Current permission', true);
        $this->registerArgument('scope', 'string', '"user" / "group" / "everybody"', true);
        $this->registerArgument('pageId', 'int', 'Page ID to evaluate permission for', true);
    }

    public function render(): string
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon = '';
        foreach (self::MASKS as $mask) {
            if ($this->arguments['permission'] & $mask) {
                $iconIdentifier = 'actions-check';
                $iconClass = 'text-success';
                $mode = 'delete';
            } else {
                $iconIdentifier = 'actions-close';
                $iconClass = 'text-danger';
                $mode = 'add';
            }

            $label = self::resolvePermissionLabel($mask);
            $icon .= '<button'
                . ' aria-label="' . htmlspecialchars($label) . ', ' . htmlspecialchars($mode) . ', ' . htmlspecialchars($this->arguments['scope']) . '"'
                . ' title="' . htmlspecialchars($label) . '"'
                . ' data-page="' . htmlspecialchars((string)$this->arguments['pageId']) . '"'
                . ' data-permissions="' . htmlspecialchars((string)$this->arguments['permission']) . '"'
                . ' data-who="' . htmlspecialchars($this->arguments['scope']) . '"'
                . ' data-bits="' . htmlspecialchars((string)$mask) . '"'
                . ' data-mode="' . htmlspecialchars($mode) . '"'
                . ' class="btn btn-default btn-icon btn-borderless change-permission ' . htmlspecialchars($iconClass) . '">'
                . $iconFactory->getIcon($iconIdentifier, IconSize::SMALL)->render(SvgIconProvider::MARKUP_IDENTIFIER_INLINE)
                . '</button>';
        }
        return $icon;
    }

    protected static function resolvePermissionLabel(int $mask): string
    {
        if (!isset(self::$cachePermissionLabels[$mask])) {
            self::$cachePermissionLabels[$mask] = htmlspecialchars(self::getLanguageService()->sL(
                'LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:' . $mask,
            ));
        }
        return self::$cachePermissionLabels[$mask];
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
