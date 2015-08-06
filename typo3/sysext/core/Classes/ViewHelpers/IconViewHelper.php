<?php
namespace TYPO3\CMS\Core\ViewHelpers;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Displays sprite icon identified by iconName key
 */
class IconViewHelper extends AbstractViewHelper implements CompilableInterface {

	/**
	 * Prints icon html for $identifier key
	 *
	 * @param string $identifier
	 * @param string $size
	 * @param string $overlay
	 * @return string
	 */
	public function render($identifier, $size = Icon::SIZE_SMALL, $overlay = NULL) {
		return static::renderStatic(
			array(
				'identifier' => $identifier,
				'size' => $size,
				'overlay' => $overlay
			),
			$this->buildRenderChildrenClosure(),
			$this->renderingContext
		);
	}

	/**
	 * Print icon html for $identifier key
	 *
	 * @param array $arguments
	 * @param \Closure $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 * @return string
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) {
		$identifier = $arguments['identifier'];
		$size = $arguments['size'];
		$overlay = $arguments['overlay'];
		/** @var IconFactory $iconFactory */
		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		return $iconFactory->getIcon($identifier, $size, $overlay)->render();
	}

}
