<?php
namespace TYPO3\CMS\Form\Domain\Model\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Oliver Hader <oliver.hader@typo3.org>
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
 * Header model object
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class HeaderElement extends \TYPO3\CMS\Form\Domain\Model\Element\AbstractPlainElement {

	/**
	 * Gets the data.
	 *
	 * @return string
	 */
	public function getData() {
		return $this->wrapContent($this->getContent());
	}

	/**
	 * Wraps the content.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function wrapContent($content) {
		if (isset($this->properties['headingSize']) && preg_match('#^h[1-5]$#', $this->properties['headingSize'])) {
			$content = '<' . $this->properties['headingSize'] . '>' . $content . '</' . $this->properties['headingSize'] . '>';
		}
		return $content;
	}

}

?>