<?php
namespace TYPO3\CMS\Form\Domain\Model\Element;

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
