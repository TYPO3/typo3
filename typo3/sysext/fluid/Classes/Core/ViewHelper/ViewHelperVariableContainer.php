<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @api
 */
class ViewHelperVariableContainer {

	/**
	 * Two-dimensional object array storing the values. The first dimension is the fully qualified ViewHelper name,
	 * and the second dimension is the identifier for the data the ViewHelper wants to store.
	 *
	 * @var array
	 */
	protected $objects = array();

	/**
	 * @var \TYPO3\CMS\Fluid\View\AbstractTemplateView
	 */
	protected $view;

	/**
	 * Add a variable to the Variable Container. Make sure that $viewHelperName is ALWAYS set
	 * to your fully qualified ViewHelper Class Name
	 *
	 * In case the value is already inside, an exception is thrown.
	 *
	 * @param string $viewHelperName The ViewHelper Class name (Fully qualified, like \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper)
	 * @param string $key Key of the data
	 * @param mixed $value The value to store
	 * @return void
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException if there was no key with the specified name
	 * @api
	 */
	public function add($viewHelperName, $key, $value) {
		if ($this->exists($viewHelperName, $key)) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException('The key "' . $viewHelperName . '->' . $key . '" was already stored and you cannot override it.', 1243352010);
		}
		$this->addOrUpdate($viewHelperName, $key, $value);
	}

	/**
	 * Add a variable to the Variable Container. Make sure that $viewHelperName is ALWAYS set
	 * to your fully qualified ViewHelper Class Name.
	 * In case the value is already inside, it is silently overridden.
	 *
	 * @param string $viewHelperName The ViewHelper Class name (Fully qualified, like \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper)
	 * @param string $key Key of the data
	 * @param mixed $value The value to store
	 * @return void
	 */
	public function addOrUpdate($viewHelperName, $key, $value) {
		if (!isset($this->objects[$viewHelperName])) {
			$this->objects[$viewHelperName] = array();
		}
		$this->objects[$viewHelperName][$key] = $value;
	}

	/**
	 * Gets a variable which is stored
	 *
	 * @param string $viewHelperName The ViewHelper Class name (Fully qualified, like \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper)
	 * @param string $key Key of the data
	 * @return mixed The object stored
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException if there was no key with the specified name
	 * @api
	 */
	public function get($viewHelperName, $key) {
		if (!$this->exists($viewHelperName, $key)) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException('No value found for key "' . $viewHelperName . '->' . $key . '"', 1243325768);
		}
		return $this->objects[$viewHelperName][$key];
	}

	/**
	 * Determine whether there is a variable stored for the given key
	 *
	 * @param string $viewHelperName The ViewHelper Class name (Fully qualified, like \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper)
	 * @param string $key Key of the data
	 * @return boolean TRUE if a value for the given ViewHelperName / Key is stored, FALSE otherwise.
	 * @api
	 */
	public function exists($viewHelperName, $key) {
		return isset($this->objects[$viewHelperName][$key]);
	}

	/**
	 * Remove a value from the variable container
	 *
	 * @param string $viewHelperName The ViewHelper Class name (Fully qualified, like \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper)
	 * @param string $key Key of the data to remove
	 * @return void
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException if there was no key with the specified name
	 * @api
	 */
	public function remove($viewHelperName, $key) {
		if (!$this->exists($viewHelperName, $key)) {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException('No value found for key "' . $viewHelperName . '->' . $key . '", thus the key cannot be removed.', 1243352249);
		}
		unset($this->objects[$viewHelperName][$key]);
	}

	/**
	 * Set the view to pass it to ViewHelpers.
	 *
	 * @param \TYPO3\CMS\Fluid\View\AbstractTemplateView $view View to set
	 * @return void
	 */
	public function setView(\TYPO3\CMS\Fluid\View\AbstractTemplateView $view) {
		$this->view = $view;
	}

	/**
	 * Get the view.
	 *
	 * !!! This is NOT a public API and might still change!!!
	 *
	 * @return \TYPO3\CMS\Fluid\View\AbstractTemplateView The View
	 */
	public function getView() {
		return $this->view;
	}

	/**
	 * Clean up for serializing.
	 *
	 * @return array
	 */
	public function __sleep() {
		return array('objects');
	}
}

?>