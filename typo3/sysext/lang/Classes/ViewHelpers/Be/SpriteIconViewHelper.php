<?php
namespace TYPO3\CMS\Lang\ViewHelpers\Be;
/**
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

/**
 * Sprite icon view helper
 *
 * Usage:
 *
 * {namespace myext=ENET\MyExt\ViewHelpers}
 * <myext:be.spriteIcon icon="actions-document-close" title="Close" class="myClass" />
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class SpriteIconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Renders an icon as known from the TYPO3 backend
	 *
	 * @param string $icon Icon to be used
	 * @param string $title Title attribute
	 * @param string $class Class attribute
	 * @return string the rendered icon
	 */
	public function render($icon, $title = '', $class = '') {
		$options = array(
			'title' => $title,
			'class' => $class,
		);
		return \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, $options);
	}

}
