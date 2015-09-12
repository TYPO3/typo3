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

use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Render permission icon group (user / group / others) of the "Access" module.
 *
 * Most of that could be done in fluid directly, but this view helper
 * is much better performance wise.
 */
class PermissionsViewHelper extends AbstractViewHelper implements CompilableInterface {

	/**
	 * @var string Cached Css classes for a "granted" icon
	 */
	static protected $grantedCssClasses = '';

	/**
	 * @var string Cached Css classes for a "denied" icon
	 */
	static protected $deniedCssClasses = '';

	/**
	 * @var array Cached labels for a single permission mask like "Delete page"
	 */
	static protected $permissionLabels = array();

	/**
	 * Return permissions.
	 *
	 * @param int $permission Current permission
	 * @param string $scope "user" / "group" / "everybody"
	 * @param int $pageId
	 * @return string
	 */
	public function render($permission, $scope, $pageId) {
		return static::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
	}

	/**
	 * Implementing CompilableInterface suppresses object instantiation of this view helper
	 *
	 * @param array $arguments
	 * @param \Closure $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 * @return string
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) {
		// The two main icon classes are static during one call. They trigger relatively expensive
		// calculation with a signal and object creation and thus make sense to have them cached.
		/** @var IconFactory $iconFactory */
		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		if (!static::$grantedCssClasses) {
			static::$grantedCssClasses = IconUtility::getSpriteIconClasses('status-status-permission-granted');
		}
		if (!static::$deniedCssClasses) {
			static::$deniedCssClasses = $iconFactory->getIcon('status-status-permission-denied')->render();
		}

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
				$icon .= '<span' .
					' title="' . static::$permissionLabels[$mask] . '"' .
					' class="' . static::$grantedCssClasses . ' change-permission text-success"' .
					' data-page="' . $arguments['pageId'] . '"' .
					' data-permissions="' . $arguments['permission'] . '"' .
					' data-mode="delete"' .
					' data-who="' . $arguments['scope'] . '"' .
					' data-bits="' . $mask . '"' .
					' style="cursor:pointer"' .
				'></span>';
			} else {
				$icon .= '<span' .
					' title="' . static::$permissionLabels[$mask] . '"' .
					' class="' . static::$deniedCssClasses . ' change-permission text-danger"' .
					' data-page="' . $arguments['pageId'] . '"' .
					' data-permissions="' . $arguments['permission'] . '"' .
					' data-mode="add"' .
					' data-who="' . $arguments['scope'] . '"' .
					' data-bits="' . $mask . '"' .
					' style="cursor:pointer"' .
				'></span>';
			}
		}

		return '<span id="' . $arguments['pageId'] . '_' . $arguments['scope'] . '">' . $icon . '</span>';
	}

}
