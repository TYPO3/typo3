<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

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

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Render permission icon group (user / group / others) of the "Access" module.
 *
 * Most of that could be done in fluid directly, but this view helper
 * is much better performance wise.
 */
class PermissionsViewHelper extends AbstractViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var array Cached labels for a single permission mask like "Delete page"
     */
    protected static $permissionLabels = array();

    /**
     * Return permissions.
     *
     * @param int $permission Current permission
     * @param string $scope "user" / "group" / "everybody"
     * @param int $pageId
     * @return string
     */
    public function render($permission, $scope, $pageId)
    {
        return static::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * Static rendering method
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $masks = array(1, 16, 2, 4, 8);

        if (empty(static::$permissionLabels)) {
            foreach ($masks as $mask) {
                static::$permissionLabels[$mask] = LocalizationUtility::translate(
                    'LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:' . $mask,
                    'be_user'
                );
            }
        }

        $icon = '';
        foreach ($masks as $mask) {
            if ($arguments['permission'] & $mask) {
                $permissionClass = 'fa-check text-success';
                $mode = 'delete';
            } else {
                $permissionClass = 'fa-times text-danger';
                $mode = 'add';
            }

            $label = static::$permissionLabels[$mask];
            $icon .= '<span style="cursor:pointer"'
                . ' title="' . htmlspecialchars($label) . '"'
                . ' data-toggle="tooltip"'
                . ' data-page="' . htmlspecialchars($arguments['pageId']) . '"'
                . ' data-permissions="' . htmlspecialchars($arguments['permission']) . '"'
                . ' data-who="' . htmlspecialchars($arguments['scope']) . '"'
                . ' data-bits="' . htmlspecialchars($mask) . '"'
                . ' data-mode="' . htmlspecialchars($mode) . '"'
                . ' class="t3-icon change-permission fa ' . htmlspecialchars($permissionClass) . '"></span>';
        }

        return '<span id="' . htmlspecialchars($arguments['pageId'] . '_' . $arguments['scope']) . '">' . $icon . '</span>';
    }
}
