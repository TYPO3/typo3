<?php
namespace TYPO3\CMS\Form;

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
 * Layout class for the form elements
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class Layout implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Layout array from form configuration
	 *
	 * @var array
	 */
	protected $layout = array();

	/**
	 * Constructor
	 *
	 * @param $layout array Layout array from form configuration
	 */
	public function __construct(array $layout = array()) {
		$this->setLayout($layout);
	}

	/**
	 * Get the layout of the object
	 * Looks if there is an assigned layout by configuration of the element
	 * otherwise it will look if there is a layout set in the form configuration
	 * If both values are not assigned, take the default one
	 *
	 * @param string $elementName Type of object
	 * @param string $layoutDefault The default layout of the object
	 * @param string $layoutOverride Assigned layout to this object
	 * @return string The new layout if changed
	 */
	public function getLayoutByObject($elementName, $layoutDefault, $layoutOverride = '') {
		if (!empty($layoutOverride)) {
			$layout = $layoutOverride;
		} elseif (!empty($this->layout[$elementName])) {
			$layout = $this->layout[$elementName];
		} else {
			$layout = $layoutDefault;
		}
		return $layout;
	}

	/**
	 * Overrides the default layout configuration for one or more elements
	 *
	 * @param array $layout The layout array
	 * @return \TYPO3\CMS\Form\Layout
	 */
	public function setLayout(array $layout = array()) {
		if (!empty($layout)) {
			$this->layout = $layout;
		}
		return $this;
	}

	/**
	 * Overrides the default layout configuration for one element
	 * identified by the element name
	 *
	 * @param string $elementName Type of object
	 * @param string $layout XML containing layout for element
	 * @return \TYPO3\CMS\Form\Layout
	 */
	public function setLayoutByElement($elementName, $layout) {
		$this->layout[$elementName] = (string) $layout;
		return $this;
	}

}

?>