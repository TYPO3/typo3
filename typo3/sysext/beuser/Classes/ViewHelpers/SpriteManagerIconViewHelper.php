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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Displays sprite icon identified by iconName key
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @internal
 */
class SpriteManagerIconViewHelper extends AbstractViewHelper implements CompilableInterface {

	/**
	 * Prints sprite icon html for $iconName key
	 *
	 * @param string $iconName
	 * @param array $options
	 * @return string
	 */
	public function render($iconName, $options = array()) {
		return self::renderStatic(array('iconName' => $iconName, 'options' => $options), $this->buildRenderChildrenClosure(), $this->renderingContext);
	}

	/**
	 * Print sprite icon html for $iconName key
	 *
	 * @param array $arguments
	 * @param \Closure $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 * @return string
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) {
		$iconName = $arguments['iconName'];
		$options = $arguments['options'];
		return IconUtility::getSpriteIcon($iconName, $options);
	}

}
