<?php
namespace TYPO3\CMS\Backend\View\BackendLayout;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Oliver Hader <oliver.hader@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class to represent a backend layout.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class BackendLayout {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var string
	 */
	protected $iconPath;

	/**
	 * @var string
	 */
	protected $configuration;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @param string $identifier
	 * @param string $title
	 * @param string $configuration
	 * @return BackendLayout
	 */
	static public function create($identifier, $title, $configuration) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayout',
			$identifier,
			$title,
			$configuration
		);
	}

	/**
	 * @param string $identifier
	 * @param string $title
	 * @param string $configuration
	 */
	public function __construct($identifier, $title, $configuration) {
		$this->setIdentifier($identifier);
		$this->setTitle($title);
		$this->setConfiguration($configuration);
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @param string $identifier
	 * @throws \UnexpectedValueException
	 */
	public function setIdentifier($identifier) {
		if (strpos($identifier, '__') !== FALSE) {
			throw new \UnexpectedValueException(
				'Identifier "' . $identifier . '" must not contain "__"',
				1381597630
			);
		}

		$this->identifier = $identifier;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getIconPath() {
		return $this->iconPath;
	}

	/**
	 * @param string $iconPath
	 */
	public function setIconPath($iconPath) {
		$this->iconPath = $iconPath;
	}

	/**
	 * @return string
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * @param string $configuration
	 */
	public function setConfiguration($configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param array $data
	 */
	public function setData(array $data) {
		$this->data = $data;
	}

}