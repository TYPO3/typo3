<?php
namespace TYPO3\CMS\Documentation\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Andrea Schmuttermair <spam@schmutt.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * An extension helper model to be used in ext:documentation context
 *
 * @entity
 * @author Andrea Schmuttermair <spam@schmutt.de>
 */
class DocumentFormat extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * format
	 *
	 * @var string
	 * @validate NotEmpty
	 */
	protected $format;

	/**
	 * path
	 *
	 * @var string
	 * @validate NotEmpty
	 */
	protected $path;

	/**
	 * Returns the format.
	 *
	 * @return string $format
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Sets the format.
	 *
	 * @param string $format
	 * @return DocumentFormat
	 */
	public function setFormat($format) {
		$this->format = $format;
		return $this;
	}

	/**
	 * Returns the path.
	 *
	 * @return string $path
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Sets the path.
	 *
	 * @param string $path
	 * @return DocumentFormat
	 */
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}

}
