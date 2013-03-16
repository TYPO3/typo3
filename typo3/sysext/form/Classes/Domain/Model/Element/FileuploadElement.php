<?php
namespace TYPO3\CMS\Form\Domain\Model\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 * File upload model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FileuploadElement extends \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accesskey' => '',
		'alt' => '',
		'class' => '',
		'dir' => '',
		'disabled' => '',
		'id' => '',
		'lang' => '',
		'name' => '',
		'size' => '',
		'style' => '',
		'tabindex' => '',
		'title' => '',
		'type' => 'file'
	);

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array(
		'name',
		'id'
	);

	/**
	 * Gets the original filename.
	 *
	 * @return string
	 */
	public function getData() {
		$uploadData = $this->requestHandler->get($this->name);
		return $uploadData['originalFilename'];
	}

	/**
	 * Gets the file type.
	 *
	 * @return string
	 */
	public function getType() {
		$uploadData = $this->requestHandler->get($this->name);
		return $uploadData['type'];
	}

	/**
	 * Gets the file size.
	 *
	 * @return integer
	 */
	public function getSize() {
		$uploadData = $this->requestHandler->get($this->name);
		return $uploadData['size'];
	}

}

?>