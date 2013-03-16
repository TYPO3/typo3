<?php
namespace TYPO3\CMS\Lang\ViewHelpers\Be;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Kai Vogel <kai.vogel@speedprogs.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
	 * @param string $title Title attribte
	 * @param string $class Class attribte
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
?>